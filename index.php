<?php

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('lukaskleinschmidt/resolve', [
    'options' => [
        'cache' => true,
    ],
    'hooks' => [
        'system.loadPlugins:after' => function () {
            kirby()->extend([
                'hooks' => [
                    'page.changeSlug:before' => function ($page) {
                        kirby()->cache('lukaskleinschmidt.resolve')->flush();
                    },
                    'route:before' => function ($route, $path, $method) {
                        if (option('lukaskleinschmidt.resolve.cache') === false) {
                            return;
                        }

                        $kirby = kirby();
                        $proxy = $kirby->cache('lukaskleinschmidt.resolve')
                                       ->get($path, false);

                        if ($proxy === false) {
                            return;
                        }

                        $parts  = explode('/', $proxy['path']);
                        $root   = $kirby->root('content');
                        $parent = null;
                        $page   = null;

                        foreach ($parts as $part) {
                            $root .= '/' . $part;

                            if ($part === '_draft') {
                                continue;
                            }

                            if (preg_match('/^([0-9]+)_(.*)$/', $part, $match)) {
                                $num  = $match[1];
                                $slug = $match[2];
                            } else {
                                $num  = null;
                                $slug = $part;
                            }

                            $params = [
                                'root'   => $root,
                                'parent' => $parent,
                                'slug'   => $slug,
                                'num'    => $num,
                            ];

                            if (empty(Page::$models) === false) {
                                $extension = $kirby->contentExtension();

                                if ($kirby->multilang()) {
                                    $extension = $kirby->defaultLanguage()->code() . '.' . $extension;
                                }

                                foreach (array_keys(Page::$models) as $model) {
                                    if (file_exists($params['root'] . '/' . $model . '.' . $extension) === true) {
                                        $params['model'] = $model;
                                        break;
                                    }
                                }
                            }

                            $parent = $page = Page::factory($params);
                        }

                        $kirby->extend([
                            'pages' => [$path => $page]
                        ]);
                    },
                    'route:after' => function ($route, $path, $method, $result) {
                        if (option('lukaskleinschmidt.resolve.cache') === false) {
                            return;
                        }

                        $kirby = kirby();
                        $cache = $kirby->cache('lukaskleinschmidt.resolve');
                        $proxy = $cache->get($path, false);

                        if ($proxy !== false) {
                            $kirby->setCurrentTranslation($proxy['lang']);
                            $kirby->setCurrentLanguage($proxy['lang']);
                        }

                        if ($proxy === false && is_a($result, 'Kirby\Cms\Page')) {
                            $cache->set($path, [
                                'path' => $result->diruri(),
                                'lang' => $kirby->languageCode(),
                            ]);
                        }

                        return $result;
                    },
                ]
            ]);
        }
    ]
]);

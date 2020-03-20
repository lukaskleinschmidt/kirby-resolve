<?php

include_once __DIR__ . '/helpers.php';

$flush = function() {
    kirby()->cache('lukaskleinschmidt.resolve')->flush();
};

Kirby::plugin('lukaskleinschmidt/resolve', [
    'options' => [
        'cache' => true,
    ],
    'hooks' => [
        'page.changeNum:before' => $flush,
        'page.changeSlug:before' => $flush,
        'page.changeStatus:before' => $flush,
        'route:after' => function ($route, $path, $method, $result) {
            if (option('lukaskleinschmidt.resolve.cache') === false) {
                return $result;
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
        'route:before' => function ($route, $path, $method) {
            if (option('lukaskleinschmidt.resolve.cache') === false) {
                return;
            }

            $kirby = kirby();
            $proxy = $kirby->cache('lukaskleinschmidt.resolve')
                           ->get($path, false);

            if ($proxy !== false && $page = resolveDir($proxy['path'])) {
                $kirby->extend([
                    'pages' => [$path => $page]
                ]);
            }
        },
    ]
]);

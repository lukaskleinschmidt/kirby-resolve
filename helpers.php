<?php

/**
 * Resolve a page by directory path
 * 
 * @param string $path
 * @return \Kirby\Cms\Page|null
 */
function resolveDir(string $path): ?Page
{
    $kirby = kirby();
    $root  = $kirby->root('content');

    if (is_dir($root . '/' . $path) === false) {
        return null;
    }

    $parts  = explode('/', $path);
    $draft  = false;
    $parent = null;
    $page   = null;

    foreach ($parts as $part) {
        $root .= '/' . $part;

        if ($part === '_drafts') {
            $draft = true;
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
            'root'    => $root,
            'parent'  => $parent,
            'slug'    => $slug,
            'num'     => $num,
        ];

        if ($draft === true) {
            $params['isDraft'] = $draft;

            // Only direct subpages are marked as drafts
            $draft = false;
        }

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

    return $page;
}
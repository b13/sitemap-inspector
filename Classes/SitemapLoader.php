<?php

declare(strict_types=1);

namespace B13\SitemapInspector;

/*
 * This file is part of the b13 TYPO3 extensions family.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Http\Uri;
use vipnytt\SitemapParser;

class SitemapLoader
{
    public function sanitizeBase(string $url): string
    {
        if (str_ends_with($url, '/sitemap.xml')) {
            $url = str_replace('/sitemap.xml', '', $url);
        }
        return rtrim($url, '/');
    }

    public function getSitemapUrl(string $base): string
    {
        if (!str_ends_with($base, '.xml')) {
            return rtrim($base, '/') . '/sitemap.xml';
        }
        return $base;
    }

    public function getUrlsFromSitemap(string $sitemapUrl): array
    {
        $parser = new SitemapParser('Sitemap-Inspector', ['guzzle' => ['verify' => false]]);
        $parser->parseRecursive($sitemapUrl);
        $urls = $parser->getURLs();
        if (!empty($urls)) {
            ksort($urls);
            return $urls;
        }
        return [];
    }

    public function removePrefixFromUrls(string $base, array $urls): array
    {
        $baseWithoutPath = new Uri($base);
        $baseWithoutPath = rtrim((string)$baseWithoutPath->withPath('/'), '/');
        return array_map(function ($url) use ($base, $baseWithoutPath) {
            if (str_starts_with($url, $baseWithoutPath)) {
                return str_replace($baseWithoutPath, '', $url);
            }
            return $url;
        }, $urls);
    }
}

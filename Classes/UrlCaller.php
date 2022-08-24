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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use vipnytt\SitemapParser\UrlParser;

class UrlCaller
{
    use UrlParser;

    public function getHttpResponseCode(string $url): int
    {
        if (!$this->urlValidate($url)) {
            throw new \InvalidArgumentException('invalide url');
        }
        $client = new Client();
        try {
            $response = $client->request('HEAD', $url, []);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
        }
        return $response->getStatusCode();
    }
}

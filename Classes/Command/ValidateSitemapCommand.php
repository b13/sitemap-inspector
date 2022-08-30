<?php

declare(strict_types=1);

namespace B13\SitemapInspector\Command;

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

use B13\SitemapInspector\SitemapLoader;
use B13\SitemapInspector\UrlCaller;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Call all URLs found in sitemaps for given sites or base URLs
 */
class ValidateSitemapCommand extends Command
{
    public function configure()
    {
        $this
            ->addOption(
                'sites',
                null,
                InputOption::VALUE_REQUIRED,
                'CSV of Site Identifierers',
            )
            ->addOption(
                'urls',
                null,
                InputOption::VALUE_REQUIRED,
                'CSV of Urls',
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $urls = $this->getAllUrls($input);
        if (empty($urls)) {
            $io->error('either sites or urls option is required');
            return 1;
        }
        $sitemapLoader = new SitemapLoader();
        $urlCaller = new UrlCaller();
        foreach ($urls as $url) {
            $errors = [];
            $countPerStatusCode = [];
            $url = $sitemapLoader->sanitizeBase($url);
            $sitemapUrl = $sitemapLoader->getSitemapUrl($url);
            $io->section('Fetching all URLs from "' . $sitemapUrl . '"');
            $urlsToCall = $sitemapLoader->getUrlsFromSitemap($sitemapUrl);
            $urlsToCall = array_keys($urlsToCall);
            foreach ($urlsToCall as $urlToCall) {
                try {
                    $code = $urlCaller->getHttpResponseCode($urlToCall);
                    if ($code !== 200) {
                        $errors[] = $code . ' ' . $urlToCall;
                    }
                    if (!isset($countPerStatusCode[$code])) {
                        $countPerStatusCode[$code] = 0;
                    }
                    $countPerStatusCode[$code]++;
                } catch (\Exception $e) {
                    $io->warning($url . ' ' . $e->getMessage());
                }
            }
            $io->listing($errors);
            $info = [];
            foreach ($countPerStatusCode as $code => $count) {
                $info[] = 'called ' . $count . ' URLs with status code ' . $code;
            }
            $io->info($info);
        }
        return 0;
    }

    protected function getAllUrls(InputInterface $input): array
    {
        $siteIdentifiers = $input->getOption('sites');
        $allUrls = [];
        if ($siteIdentifiers !== null) {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $siteIdentifiers = GeneralUtility::trimExplode(',', $siteIdentifiers);
            foreach ($siteIdentifiers as $siteIdentifier) {
                $site = $siteFinder->getSiteByIdentifier($siteIdentifier);
                $allUrls[] = (string)$site->getBase();
            }
        }
        $urls = $input->getOption('urls');
        if ($urls !== null) {
            $allUrls = array_merge($allUrls, GeneralUtility::trimExplode(',', $urls));
        }
        $allUrls = array_unique($allUrls);
        return $allUrls;
    }
}

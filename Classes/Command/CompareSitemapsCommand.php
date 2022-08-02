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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CompareSitemapsCommand extends Command
{
    public function configure()
    {
        $this
            ->addArgument(
                'local',
                InputArgument::REQUIRED
            )
            ->addArgument(
                'remote',
                InputArgument::REQUIRED
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $sitemapLoader = new SitemapLoader();
        $io = new SymfonyStyle($input, $output);
        $localDomain = $input->getArgument('local');
        $remoteDomain = $input->getArgument('remote');

        $localDomain = $sitemapLoader->sanitizeBase($localDomain);
        $remoteDomain = $sitemapLoader->sanitizeBase($remoteDomain);

        $localSitemapUrl = $sitemapLoader->getSitemapUrl($localDomain);
        $remoteSitemapUrl = $sitemapLoader->getSitemapUrl($remoteDomain);

        $io->section('Comparing "' . $localSitemapUrl . '" to "' . $remoteSitemapUrl . '"');
        $localUrls = $sitemapLoader->getUrlsFromSitemap($localSitemapUrl);
        $localUrls = array_keys($localUrls);
        $remoteUrls = $sitemapLoader->getUrlsFromSitemap($remoteSitemapUrl);
        $remoteUrls = array_keys($remoteUrls);

        $cleanedLocalUrls = $sitemapLoader->removePrefixFromUrls($localDomain, $localUrls);
        $cleanedRemoteUrls = $sitemapLoader->removePrefixFromUrls($remoteDomain, $remoteUrls);
        $commonUrls = array_intersect($cleanedLocalUrls, $cleanedRemoteUrls);

        $onlyInLocal = [];
        $onlyRemote = [];
        foreach ($cleanedLocalUrls as $localUrl) {
            if (!in_array($localUrl, $cleanedRemoteUrls)) {
                $onlyInLocal[] = $localUrl;
            }
        }
        foreach ($cleanedRemoteUrls as $localUrl) {
            if (!in_array($localUrl, $cleanedLocalUrls)) {
                $onlyRemote[] = $localUrl;
            }
        }

        $io->section('The following ' . count($onlyInLocal) . ' URLs are only available in "' . $localSitemapUrl . '"');
        $io->listing($onlyInLocal);
        $io->section('The following ' . count($onlyRemote) . ' URLs are only available in "' . $remoteSitemapUrl . '"');
        $io->listing($onlyRemote);
        $io->info([
            'Found ' . count($localUrls) . ' total URLs in "' . $localSitemapUrl . '"',
            'Found ' . count($remoteUrls) . ' total URLs in "' . $remoteSitemapUrl . '"',
            'Found ' . count($commonUrls) . ' URLs available in both sitemaps',
        ]);
        return 0;
    }
}

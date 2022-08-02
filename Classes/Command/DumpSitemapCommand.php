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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Resolves index sitemaps and delivers all URLs as list or as a JSON / TSV.
 */
class DumpSitemapCommand extends Command
{
    public function configure()
    {
        $this
            ->addArgument(
                'url',
                InputArgument::REQUIRED
            )
            ->addOption(
                'format',
                null,
                InputOption::VALUE_REQUIRED,
                'Can be "json", or "tsv", if empty will be dumped',
                'stdout'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $sitemapLoader = new SitemapLoader();
        $io = new SymfonyStyle($input, $output);
        $sitemapUrl = $input->getArgument('url');
        $format = $input->getOption('format');

        $sitemapBase = $sitemapLoader->sanitizeBase($sitemapUrl);
        $sitemapUrl = $sitemapLoader->getSitemapUrl($sitemapBase);
        if ($format === 'stdout' || !$format) {
            $io->section('Fetching all URLs from "' . $sitemapUrl . '"');
        }
        $urls = $sitemapLoader->getUrlsFromSitemap($sitemapUrl);
        $urlsForOutput = array_map(function ($urlData) {
            if (is_array($urlData['loc'])) {
                var_dump($urlData);
            }
            $location = $urlData['loc'] ?? '';
            $lastModified = $urlData['lastmod'] ?? '';
            $priority = $urlData['priority'] ?? '';
            $changeFrequency = $urlData['changefreq'] ?? '';
            return [
                is_array($location) ? reset($location) : $location,
                is_array($lastModified) ? implode(', ', $lastModified) : $lastModified,
                is_array($priority) ? implode(', ', $priority) : $priority,
                is_array($changeFrequency) ? implode(', ', $changeFrequency) : $changeFrequency,
            ];
        }, $urls);

        switch ($format) {
            case 'tsv':
            case 'csv':
                $content = $this->createCsvString(['URL', 'Last Modified', 'Priority', 'Change Frequency'], $urlsForOutput, ($format === 'tsv' ? "\t" : ';'));
                $io->write($content);
                break;
            case 'json':
                $io->write(json_encode($urlsForOutput, JSON_PRETTY_PRINT));
                break;
            default:
                $io->table(['URL', 'Last Modified', 'Priority', 'Change Frequency'], $urlsForOutput);
        }

        return 0;
    }

    /**
     * see http://www.metashock.de/2014/02/create-csv-file-in-memory-php/
     */
    protected function createCsvString(array $headers, array $rows, string $delimiter, $enclosure = '"'): string
    {
        // we use a threshold of 1 MB (1024 * 1024), it's just an example
        $fd = fopen('php://temp/maxmemory:1048576', 'w');
        if ($fd === false) {
            die('Failed to open temporary file');
        }

        fputcsv($fd, $headers, $delimiter, $enclosure);
        foreach ($rows as $row) {
            fputcsv($fd, $row, $delimiter, $enclosure);
        }

        rewind($fd);
        $csv = stream_get_contents($fd);
        fclose($fd);
        return $csv;
    }
}

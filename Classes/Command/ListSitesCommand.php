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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Show a list of all base URLs
 */
class ListSitesCommand extends Command
{
    public function configure()
    {
        $this
            ->addOption(
                'env',
                null,
                InputOption::VALUE_REQUIRED,
                'Simulate an environment',
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $env = $input->getOption('env');

        $items = [];
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        foreach ($siteFinder->getAllSites(false) as $site) {
            $items[$site->getIdentifier()] = [
                $site->getIdentifier(),
                $site->getBase(),
            ];
        }

        if ($env) {
            $this->simulateApplicationContext($env);
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            foreach ($siteFinder->getAllSites(false) as $site) {
                $items[$site->getIdentifier()][2] = $site->getBase();
            }
            $io->table(['Site Identifier', 'Base URL', 'Base URL ' . (string)Environment::getContext()], $items);
        } else {
            $io->table(['Site Identifier', 'Base URL'], $items);
        }

        return 0;
    }

    protected function simulateApplicationContext(string $env): void
    {
        $context = new ApplicationContext($env);
        Environment::initialize(
            $context,
            Environment::isCli(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
    }
}

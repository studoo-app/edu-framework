<?php

namespace Studoo\EduFramework\Commands;

use Studoo\EduFramework\Commands\Extends\CkeckStack;
use Studoo\EduFramework\Commands\Extends\CommandBanner;
use Studoo\EduFramework\Commands\Extends\CommandManage;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckStackCommand
 * Classe permettant de vérifier les prérequis
 * @package Studoo\EduFramework\Commands
 * Example command line:
 * ```
 * $ php bin/edu check:config
 * ```
 */
#[AsCommand(
    name: 'check:config',
    description: 'Check les prérequis',
)]
class CheckStackCommand extends CommandManage
{
    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        self::$stdOutput->writeln([
            CommandBanner::getBanner(),
            'Check des prérequis d ' . ConfigCore::getConfig('name'),
            ''
        ]);

        $check = new CkeckStack($output, self::$stdOutput);
        $check->render();

        self::$stdOutput->writeln([
            '',
            'Documentation : https://studoo-app.github.io/edu-framework-doc/',
            ''
        ]);

        return Command::SUCCESS;
    }
}

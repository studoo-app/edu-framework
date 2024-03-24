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
 * Class StartCommand
 * Classe démarrer le serveur de développement
 * @package Studoo\EduFramework\Commands
 * Example command line:
 * ```
 * $ php bin/edu start
 * ```
 */
#[AsCommand(
    name: 'start',
    description: 'Démarrer le serveur de développement',
)]
class StartCommand extends CommandManage
{
    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        self::$stdOutput->writeln([
            CommandBanner::getBanner(),
            'Check des prérequis d\'' . ConfigCore::getConfig('name'),
            ''
        ]);

        $check = new CkeckStack($output, self::$stdOutput);
        $check->render();

        self::$stdOutput->writeln([
            '',
            'Démarage du serveur de développement...',
            ''
        ]);

        self::$stdOutput->note(
            'Pour arrêter le serveur de développement, appuyer sur CTRL+X CTRL+C'
        );

        exec("composer edu:start");

        return Command::SUCCESS;
    }
}

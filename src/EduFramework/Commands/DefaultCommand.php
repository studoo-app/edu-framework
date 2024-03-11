<?php

namespace Studoo\EduFramework\Commands;

use Studoo\EduFramework\Commands\Extends\CkeckStack;
use Studoo\EduFramework\Commands\Extends\CommandBanner;
use Studoo\EduFramework\Commands\Extends\CommandManage;
use Studoo\EduFramework\Commands\Extends\listCommand;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DefaultCommand
 * Classe permettant l'utilisation de la commande par défaut
 * @package Studoo\EduFramework\Commands
 */
#[AsCommand(
    name: 'default',
    description: 'Liste des commandes disponibles',
)]
class DefaultCommand extends CommandManage
{
    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        self::$stdOutput->writeln([
            CommandBanner::getBanner(),
            'Bienvenue dans la console ' . ConfigCore::getConfig('name'),
            ''
        ]);

        $check = new CkeckStack($output, self::$stdOutput);
        $check->render();

        $check = new listCommand($output, self::$stdOutput);
        $check->render();

        self::$stdOutput->writeln([
            'Si vous avez un problème, https://github.com/studoo-app/edu-framework/discussions',
            ''
        ]);

        return Command::SUCCESS;
    }
}

<?php

namespace Studoo\EduFramework\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DefaultCommand
 * Classe permettant l'utilisation de la commande par défaut
 * @package Studoo\EduFramework\Commands
 */
class DefaultCommand extends CommandManage
{
    protected static $defaultName = 'default';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        self::$stdOutput->writeln([
            'Bienvenu dans la console EDU-Framwork !',
            ''
        ]);

        return Command::SUCCESS;
    }
}
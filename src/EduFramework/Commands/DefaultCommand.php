<?php
/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Commands;

use Studoo\EduFramework\Commands\Extends\CommandBanner;
use Studoo\EduFramework\Commands\Extends\CommandManage;
use Studoo\EduFramework\Commands\Extends\ListCommand;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DefaultCommand
 * Classe permettant l'utilisation de la commande par défaut
 * @package Studoo\EduFramework\Commands
 * Example command line:
 * ```
 * $ php bin/edu
 * ```
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

        $check = new ListCommand($output, self::$stdOutput);
        $check->render();

        self::$stdOutput->info(CommandBanner::getDoc());

        return Command::SUCCESS;
    }
}

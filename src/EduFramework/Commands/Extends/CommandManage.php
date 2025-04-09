<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Commands\Extends;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommandManage extends Command
{
    /**
     * @var SymfonyStyle $stdOutput Sortie standard
     */
    protected static SymfonyStyle $stdOutput;

    protected static OutputInterface $outPut;

    /**
     * Initialisation
     *
     * @param InputInterface $input Interface d'entrée
     * @param OutputInterface $output Interface de sortie
     * @return void
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        // Debug mode
        if ($output->isVerbose() === true) {
            error_reporting($output->isDebug() ? E_ALL : E_ALL & ~E_DEPRECATED);
        } elseif ($output->isQuiet() === true) {
            error_reporting(false);
        }

        self::$stdOutput = new SymfonyStyle($input, $output);
        self::$outPut = $output;
    }

    /**
     * Récupération de la sortie standard
     *
     * @return SymfonyStyle Sortie standard
     */
    public static function getStdOutPut(): SymfonyStyle
    {
        return self::$stdOutput;
    }
}

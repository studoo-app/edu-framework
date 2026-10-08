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
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ClearCacheCommand
 * Classe permettant de supprimer le cache de l'application (dossier var/cache)
 * Example command line:
 * ```
 * $ php bin/edu cache:clear
 * ```
 * @package Studoo\EduFramework\Commands
 */
#[AsCommand(
    name: 'cache:clear',
    description: 'Suppression du cache de l\'application (dossier var/cache)',
)]
class ClearCacheCommand extends CommandManage
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        self::$stdOutput->writeln([
            CommandBanner::getBanner(),
            'Suppression du cache',
            ''
        ]);

        // Le chemin du cache est défini dans la configuration (clé cache_path)
        $cachePath = ConfigCore::getConfig('cache_path');

        if (is_dir($cachePath) === false) {
            self::$stdOutput->info("Aucun cache à supprimer. Le dossier <$cachePath> n'existe pas");
            return Command::SUCCESS;
        }

        // Suppression du contenu du dossier de cache Exemple: le cache TWIG (var/cache/twig)
        // Le dossier de cache lui-même est conservé
        $fileSystem = new Filesystem();
        foreach (scandir($cachePath) as $item) {
            if ($item !== '.' && $item !== '..') {
                $fileSystem->remove($cachePath . DIRECTORY_SEPARATOR . $item);
            }
        }

        self::$stdOutput->success("Le cache a été supprimé : " . realpath($cachePath));

        return Command::SUCCESS;
    }
}

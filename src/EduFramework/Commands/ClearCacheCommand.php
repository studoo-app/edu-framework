<?php

/*
 * Edu Framework by studoo labs
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Commands;

use Studoo\EduFramework\Commands\Extends\CommandBanner;
use Studoo\EduFramework\Commands\Extends\CommandManage;
use Studoo\EduFramework\Core\Cache\CacheService;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Exception\ErrorCacheException;
use Studoo\EduFramework\Core\Exception\ErrorConfigException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class ClearCacheCommand
 * Classe permettant de supprimer le cache de l'application :
 *     - le dossier var/cache (cache TWIG, cache applicatif si CACHE_DRIVER=file...)
 *     - le cache applicatif CacheService (clés Redis du préfixe CACHE_PREFIX si CACHE_DRIVER=redis)
 * Example command line:
 * ```
 * $ php bin/edu cache:clear
 * $ php bin/edu cache:clear --app-only     # uniquement le cache applicatif (Exemple : les clés Redis)
 * $ php bin/edu cache:clear --files-only   # uniquement le dossier var/cache
 * ```
 * @package Studoo\EduFramework\Commands
 */
#[AsCommand(
    name: 'cache:clear',
    description: 'Suppression du cache de l\'application (dossier var/cache et cache applicatif)',
)]
class ClearCacheCommand extends CommandManage
{
    /**
     * Déclare les options --app-only et --files-only
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'app-only',
                null,
                InputOption::VALUE_NONE,
                'Vide uniquement le cache applicatif (CacheService : fichiers ou clés Redis)'
            )
            ->addOption(
                'files-only',
                null,
                InputOption::VALUE_NONE,
                'Vide uniquement le dossier var/cache (cache TWIG...)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        self::$stdOutput->writeln([
            CommandBanner::getBanner(),
            'Suppression du cache',
            ''
        ]);

        $appOnly = (bool) $input->getOption('app-only');
        $filesOnly = (bool) $input->getOption('files-only');
        if ($appOnly === true && $filesOnly === true) {
            self::$stdOutput->error('Les options --app-only et --files-only ne peuvent pas être utilisées ensemble');
            return Command::FAILURE;
        }

        if ($appOnly === false) {
            $this->clearCacheDirectory();
        }

        if ($filesOnly === false && $this->clearApplicationCache() === false && $appOnly === true) {
            // Avec --app-only, l'échec est le résultat de la commande (utile dans un script)
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Supprime le contenu du dossier de cache (clé de configuration cache_path)
     * Exemple : le cache TWIG (var/cache/twig), le cache applicatif fichier (var/cache/data)
     * Le dossier de cache lui-même est conservé
     * @return void
     */
    private function clearCacheDirectory(): void
    {
        $cachePath = ConfigCore::getConfig('cache_path');

        if (is_dir($cachePath) === false) {
            self::$stdOutput->info("Aucun cache à supprimer. Le dossier <$cachePath> n'existe pas");
            return;
        }

        $fileSystem = new Filesystem();
        foreach (scandir($cachePath) as $item) {
            if ($item !== '.' && $item !== '..') {
                $fileSystem->remove($cachePath . DIRECTORY_SEPARATOR . $item);
            }
        }

        self::$stdOutput->success("Le cache a été supprimé : " . realpath($cachePath));
    }

    /**
     * Vide le cache applicatif (CacheService) selon CACHE_DRIVER :
     *     - redis : suppression des clés du préfixe CACHE_PREFIX (les autres clés du serveur sont conservées)
     *     - file  : suppression des fichiers de var/cache/data
     *     - none  : rien à faire
     * @return bool false si le cache n'a pas pu être vidé (Exemple : serveur Redis arrêté)
     */
    private function clearApplicationCache(): bool
    {
        try {
            $cache = CacheService::getCache();
            $driver = $cache->getDriverName();
            if ($driver === 'none') {
                self::$stdOutput->info('Cache applicatif désactivé (CACHE_DRIVER=none) : rien à vider');
                return true;
            }

            if ($cache->clear() === false) {
                throw new ErrorCacheException('le driver ' . $driver . ' n\'a pas pu supprimer toutes les valeurs');
            }
        } catch (ErrorCacheException | ErrorConfigException $exception) {
            self::$stdOutput->warning('Le cache applicatif n\'a pas été vidé : ' . $exception->getMessage());
            return false;
        }

        $prefix = ConfigCore::existEnv('CACHE_PREFIX') ? ConfigCore::getEnv('CACHE_PREFIX') : 'edu:';
        self::$stdOutput->success($driver === 'redis'
            ? "Le cache applicatif Redis a été vidé (clés $prefix*)"
            : "Le cache applicatif a été vidé (driver $driver)");

        return true;
    }
}

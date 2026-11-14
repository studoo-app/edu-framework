<?php

/*
 * Edu Framework by studoo labs
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Commands\Extends;

use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Service\DatabaseService;
use Symfony\Component\Console\Application;
use Symfony\Component\Yaml\Yaml;

class AppCommand extends Application
{
    public function __construct()
    {
        (new ConfigCore(
            [
                'base_path' => "./",
                'route_config_path' => "./app/Config/",
                'sqlite_path' => "./var/sqlite/",
                'sqlite_logs_path' => "./var/logs/"
            ]
        ));
        parent::__construct(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));

        // Gestion des variables d'environnement (environnement, .env.local, .env)
        ConfigCore::loadEnv(ConfigCore::getConfig('base_path'));

        // Gestion de la couche Model et de la connexion à la base de données
        if (ConfigCore::getEnv('DB_HOST_STATUS') === 'true') {
            (new DatabaseService());
        }

        $this->addCommand(new \Studoo\EduFramework\Commands\DefaultCommand());
        $this->addCommand(new \Studoo\EduFramework\Commands\CreateControllerCommand());
        $this->addCommand(new \Studoo\EduFramework\Commands\CheckStackCommand());
        $this->addCommand(new \Studoo\EduFramework\Commands\StartCommand());
        $this->addCommand(new \Studoo\EduFramework\Commands\CreateCliCommand());
        $this->addCommand(new \Studoo\EduFramework\Commands\CreateApiCommand());
        $this->addCommand(new \Studoo\EduFramework\Commands\CreateEntityCommand());
        $this->addCommand(new \Studoo\EduFramework\Commands\ClearCacheCommand());
        $this->addCommand(new \Studoo\EduFramework\Commands\CreateDeployCommand());
        $this->addCommand(new \Studoo\EduFramework\Commands\RoutesListCommand());

        if (file_exists(ConfigCore::getConfig('command_config_path') . 'commands.yaml') === true) {
            $commandList = Yaml::parseFile(ConfigCore::getConfig('command_config_path') . 'commands.yaml');
            if (is_array($commandList)) {
                foreach ($commandList as $command) {
                    if (class_exists($command) === true) {
                        $this->addCommand(new $command());
                    }
                }
            }
        }

        $this->setDefaultCommand('default');
    }
}

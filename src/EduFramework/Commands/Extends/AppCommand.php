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

use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Application;
use Symfony\Component\Yaml\Yaml;

class AppCommand extends Application
{
    public function __construct()
    {
        (new ConfigCore([]));
        parent::__construct(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));

        $this->add(new \Studoo\EduFramework\Commands\DefaultCommand());
        $this->add(new \Studoo\EduFramework\Commands\CreateControllerCommand());
        $this->add(new \Studoo\EduFramework\Commands\CheckStackCommand());
        $this->add(new \Studoo\EduFramework\Commands\StartCommand());
        $this->add(new \Studoo\EduFramework\Commands\CreateCliCommand());
        $this->add(new \Studoo\EduFramework\Commands\CreateApiCommand());

        if (file_exists(ConfigCore::getConfig('command_config_path') . 'commands.yaml') === true) {
            $commandList = Yaml::parseFile(ConfigCore::getConfig('command_config_path') . 'commands.yaml');
            if (is_array($commandList)) {
                foreach ($commandList as $command) {
                    if (class_exists($command) === true) {
                        $this->add(new $command());
                    }
                }
            }
        }

        $this->setDefaultCommand('default');
    }
}

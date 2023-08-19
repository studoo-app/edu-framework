<?php
/*
 * Ce fichier fait partie du Studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core;

/**
 * Class ConfigCore
 * Elle permet de gérer la configuration de l'application
 * @package Studoo\EduFramework\Core
 */
class ConfigCore
{

    /**
     * Tableau de configuration
     * @var array<string>
     */
    private static array $config;

    /**
     * ConfigCore constructor.
     * @param array<string> $config Tableau de configuration
     */
    public function __construct(array $config)
    {
        self::$config = array_merge(
            [
                'base_path'         => '/',
                'twig_path'         => '/app/Template',
                'route_config_path'  => '/app/config/'
            ],
            $config
        );
    }

    /**
     * Permet de récupérer une configuration
     * @param string $key Clé de la configuration
     * @return mixed
     */
    public static function getConfig(string $key): mixed
    {
        return self::$config[$key] ?? null;
    }

    /**
     * Permet de récupérer la configuration du fichier .env à la racine du projet
     * @param string $key Clé de la configuration
     * @return mixed
     */
    public static function getEnv(string $key): mixed
    {
        return $_ENV[$key] ?? null;
    }
}

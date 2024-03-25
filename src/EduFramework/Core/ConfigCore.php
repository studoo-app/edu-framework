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

use Studoo\EduFramework\Core\Controller\Request;

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

    private static Request $resquest;

    /**
     * ConfigCore constructor.
     * @param array<string> $config Tableau de configuration
     */
    public function __construct(array $config)
    {
        self::$config = array_merge(
            [
                'name' => 'EduFramework',
                'version' => 'v1.2.0@main',
                'date_version' => '2024-03-25', // Date de la livraison de la version
                'php_version' => '8.1', // Warning : bin/edu require PHP 8.1 or higher
                'base_path' => '/',
                'twig_path' => '/app/Template',
                'route_config_path' => '/app/Config/'
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
        return strip_tags($_ENV[$key]);
    }

    public static function existEnv(string $key): bool
    {
        return isset($_ENV[$key]);
    }

    public static function setRequest(Request $request): void
    {
        self::$resquest = $request;
    }

    public static function getRequest(): Request
    {
        return self::$resquest;
    }
}

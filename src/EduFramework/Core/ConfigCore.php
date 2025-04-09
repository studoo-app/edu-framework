<?php

/*
 * Edu Framework by studoo
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
                'version' => 'v2.3.0@stable',
                'date_version' => '2025-04-10', // Date de la livraison de la version
                'php_version' => '8.2', // Warning : bin/edu require PHP 8.2 or higher
                'base_path' => '/',
                'twig_path' => '/app/Template',
                'route_config_path' => '/app/Config/',
                'command_config_path' => 'app/Config/',
                'sqlite_path' => '/var/sqlite/'
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
     * Permet de modifier une configuration
     * @param string $key Clé de la configuration
     * @param mixed $value Valeur de la configuration
     * @return void
     */
    public static function setConfig(string $key, mixed $value): void
    {
        if (isset(self::$config[$key]) === true) {
            self::$config[$key] = $value;
        }
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

    /**
     * Permet de vérifier si une configuration existe
     * @param string $key Clé de la configuration
     * @return bool
     */
    public static function existEnv(string $key): bool
    {
        return isset($_ENV[$key]);
    }

    /**
     * Permet de renseigner les informations de la requête HTTP
     * @param Request $request Récupère les informations de la requête HTTP
     * @return void
     */
    public static function setRequest(Request $request): void
    {
        self::$resquest = $request;
    }

    /**
     * Retourne les informations de la requête HTTP
     * @return Request
     */
    public static function getRequest(): Request
    {
        return self::$resquest;
    }
}

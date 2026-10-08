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
use Studoo\EduFramework\Core\Exception\ErrorConfigException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

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
     * Les paramètres d'identité du framework (name, version, date_version, php_version)
     * sont personnalisables via le fichier eduframe.yml situé à la racine du framework.
     * Les valeurs ci-dessous sont utilisées uniquement si ce fichier est absent.
     * @param array<string> $config Tableau de configuration
     */
    public function __construct(array $config)
    {
        self::$config = array_merge(
            [
                'name' => 'EduFramework',
                'version' => 'v2.5.0@alpha',
                'date_version' => '2026-10-08', // Date de la livraison de la version
                'php_version' => '8.4', // Warning : bin/edu require PHP 8.4 or higher
                'base_path' => '/',
                'twig_path' => '/app/Template',
                'route_config_path' => '/app/Config/',
                'command_config_path' => 'app/Config/',
                'cache_path' => './var/cache/',
                'sqlite_path' => './var/sqlite/',
                'sqlite_logs_path' => './var/logs/',
            ],
            $config
        );

        // Gestion du fichier de personnalisation des paramètres du framework (eduframe.yml)
        $this->loadEduframeConfig();
    }

    /**
     * Charge le fichier de paramètres du framework (eduframe.yml)
     * Le fichier se trouve à la racine du framework :
     *     - à la racine du dépôt en environnement de développement
     *     - dans vendor/studoo/edu-framework/ dans un projet installé via Composer
     * S'il n'existe pas, les valeurs par défaut du framework sont conservées
     * Les paramètres personnalisables sont : name, version, date_version, php_version
     * @return void
     * @throws ErrorConfigException
     */
    private function loadEduframeConfig(): void
    {
        // Racine du framework : src/EduFramework/Core -> 3 niveaux au-dessus
        $pathFile = dirname(__DIR__, 3) . '/eduframe.yml';

        // Le fichier est optionnel : s'il n'existe pas, on garde les valeurs par défaut
        if (is_file($pathFile) === false) {
            return;
        }

        try {
            $fileData = Yaml::parseFile($pathFile);
        } catch (ParseException $exception) {
            throw new ErrorConfigException(
                "Le fichier de configuration <" . $pathFile . "> est invalide : " . $exception->getMessage()
            );
        }

        if (is_array($fileData) === false) {
            throw new ErrorConfigException(
                "Le fichier de configuration <" . $pathFile . "> est invalide : "
                . "il doit contenir une liste de paramètres"
            );
        }

        // Seuls les paramètres d'identité du framework sont personnalisables
        // Les autres clés du fichier sont ignorées
        foreach (['name', 'version', 'date_version', 'php_version'] as $param) {
            if (array_key_exists($param, $fileData) === true) {
                if (is_string($fileData[$param]) === false) {
                    throw new ErrorConfigException(
                        "Le paramètre <" . $param . "> du fichier <" . $pathFile
                        . "> doit être une chaine de caractères. Exemple: " . $param . ": 'valeur'"
                    );
                }
                self::$config[$param] = $fileData[$param];
            }
        }
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

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

use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;
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
     * Schémas de DATABASE_URL reconnus => valeur de DB_TYPE
     */
    private const DATABASE_URL_TYPES = [
        'mysql' => 'mysql',
        'mysql2' => 'mysql',
        'mariadb' => 'mariadb',
        'pgsql' => 'pgsql',
        'postgres' => 'pgsql',
        'postgresql' => 'pgsql',
    ];

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
     * Charge les variables d'environnement du projet
     * Ordre de priorité (la première valeur trouvée est conservée) :
     *     1. les variables d'environnement du processus (Docker, serveur web)
     *     2. le fichier .env.local (secrets de production, jamais versionné)
     *     3. le fichier .env
     * Les fichiers sont optionnels : en production (image Docker), le .env est absent
     * et la configuration provient de l'environnement et du .env.local
     * Si DATABASE_URL est définie, les variables DB_* manquantes en sont déduites
     * @param string $basePath Dossier contenant les fichiers .env
     * @return void
     */
    public static function loadEnv(string $basePath): void
    {
        // Fichiers lus dans l'ordre : une valeur du .env.local remplace celle du .env,
        // mais jamais une variable déjà présente dans l'environnement (mode immutable),
        // y compris une variable visible uniquement par getenv() (php -S, mod_php)
        $repository = RepositoryBuilder::createWithDefaultAdapters()
            ->addReader(PutenvAdapter::class)
            ->immutable()
            ->make();
        Dotenv::create($repository, $basePath, ['.env', '.env.local'], false)->safeLoad();
        self::loadDatabaseUrl();
    }

    /**
     * Déduit les variables DB_* de la variable DATABASE_URL
     * Format : mysql://user:password@host:3306/db_name?serverVersion=11
     * (format utilisé par FrankenDeploy et Symfony pour une base gérée)
     * Une variable DB_* déjà définie n'est jamais écrasée
     * Une DATABASE_URL d'un autre type (Exemple: sqlite:///...) est ignorée
     * @return void
     * @throws ErrorConfigException Si la DATABASE_URL MySQL / MariaDB / PostgreSQL est mal formée
     */
    private static function loadDatabaseUrl(): void
    {
        $databaseUrl = self::readEnv('DATABASE_URL') ?? '';
        $type = self::DATABASE_URL_TYPES[strstr($databaseUrl, '://', true) ?: ''] ?? null;
        if ($type === null) {
            return;
        }

        // Exemple : un mot de passe contenant # est tronqué à la lecture du fichier .env.local
        $url = parse_url($databaseUrl);
        if ($url === false || isset($url['host']) === false) {
            throw new ErrorConfigException(
                "La variable DATABASE_URL est invalide. Format attendu : "
                . "mysql://utilisateur:motdepasse@hote:3306/base. Les caractères spéciaux "
                . "de l'utilisateur et du mot de passe doivent être encodés (# => %23, @ => %40, / => %2F)"
            );
        }

        $values = [
            'DB_HOST_STATUS' => 'true',
            'DB_TYPE' => $type,
            'DB_HOST' => $url['host'] ?? '',
            'DB_SOCKET' => (string) ($url['port'] ?? ''),
            'DB_USER' => $url['user'] ?? '',
            'DB_PASSWORD' => $url['pass'] ?? '',
            'DB_NAME' => ltrim($url['path'] ?? '', '/'),
        ];

        // Dépôt "immutable" : une variable déjà définie n'est pas écrasée
        $repository = self::envRepository();
        foreach (array_filter($values, 'strlen') as $key => $value) {
            $repository->set($key, rawurldecode($value));
        }
    }

    /**
     * Dépôt des variables d'environnement (librairie phpdotenv) :
     *     - lecture : $_ENV (fichiers .env) puis l'environnement du processus
     *       (variables passées par Docker, absentes de $_ENV lorsque variables_order
     *       ne contient pas "E", comme avec php.ini-production)
     *     - écriture : $_ENV, sans écraser une variable déjà définie
     * @return RepositoryInterface
     */
    private static function envRepository(): RepositoryInterface
    {
        return RepositoryBuilder::createWithNoAdapters()
            ->addReader(EnvConstAdapter::class)
            ->addReader(PutenvAdapter::class)
            ->addWriter(EnvConstAdapter::class)
            ->immutable()
            ->make();
    }

    /**
     * Lit une variable d'environnement brute (sans filtrage)
     * @param string $key Clé de la variable
     * @return string|null
     */
    private static function readEnv(string $key): ?string
    {
        return self::envRepository()->get($key);
    }

    /**
     * Permet de récupérer la configuration du fichier .env à la racine du projet
     * @param string $key Clé de la configuration
     * @return mixed
     */
    public static function getEnv(string $key): mixed
    {
        return strip_tags(self::readEnv($key) ?? '');
    }

    /**
     * Permet de vérifier si une configuration existe
     * @param string $key Clé de la configuration
     * @return bool
     */
    public static function existEnv(string $key): bool
    {
        return self::readEnv($key) !== null;
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

    /**
     * Indique si les informations de la requête HTTP sont renseignées
     * @return bool
     */
    public static function hasRequest(): bool
    {
        return isset(self::$resquest);
    }
}

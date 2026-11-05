<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Logs;

use PDO;
use Studoo\EduFramework\Core\ConfigCore;

/**
 * Class HttpRequestLogger
 * Journalise chaque requête HTTP traitée par le framework, quel que soit le serveur
 * qui la sert (serveur PHP intégré, FrankenPHP, FPM, ...). La journalisation se fait
 * en deux temps, comme le profiler de Symfony :
 * - start() réserve la ligne en début de requête : son identifiant (token) est alors
 *   disponible pour la barre de debug, qui fait le lien vers le descriptif de la requête ;
 * - finish() complète la ligne en fin de requête avec le statut final, le temps
 *   d'exécution (execution_time_ms), la mémoire utilisée (memory_mb) et, le cas
 *   échéant, le message de l'erreur à l'origine de la réponse (error_message).
 * Les événements produits ont exactement le même format que ceux issus de
 * l'analyse de la sortie du serveur intégré (BufferToServer), afin que le
 * profiler affiche et filtre les requêtes de tous les serveurs de la même façon.
 *
 * @package Studoo\EduFramework\Core\Logs
 */
class HttpRequestLogger
{
    /**
     * Identifiant du log de la requête en cours (réservé par start(), null si la
     * requête n'est pas journalisée : pas une requête HTTP, hors mode dev, ...)
     * @var int|null
     */
    private static ?int $currentLogId = null;

    /**
     * Description de l'erreur à l'origine de la requête en cours (message, fichier
     * et ligne de l'exception attrapée par le framework), null si la requête s'est
     * déroulée sans erreur. Elle est ajoutée au log par finish() puis réinitialisée.
     * @var string|null
     */
    private static ?string $errorMessage = null;

    /**
     * Variables GET et POST de la requête en cours, capturées par start() telles
     * qu'envoyées par le client (avant d'éventuelles modifications par le code),
     * puis réutilisées par finish() pour compléter le log
     * @var array{get: array<mixed>, post: array<mixed>}
     */
    private static array $requestParams = ['get' => [], 'post' => []];

    /**
     * Profondeur maximale des valeurs de session enregistrées (objets et tableaux
     * imbriqués) : au-delà, la valeur est remplacée par un marqueur
     */
    private const MAX_DEPTH = 6;

    /**
     * Options d'encodage JSON des événements : un caractère non UTF-8 ou une valeur
     * non encodable (INF, NAN, ...) ne doit jamais faire perdre tout le log
     */
    private const JSON_FLAGS = JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR;

    /**
     * Valeur affichée à la place des variables sensibles (mots de passe, secrets)
     */
    public const MASKED_VALUE = '******';

    /**
     * Réserve la ligne de log de la requête HTTP en cours dans la base des logs.
     * L'identifiant retourné par getCurrentLogId() est utilisé par la barre de debug
     * pour faire le lien vers le descriptif de la requête dans le profiler.
     *
     * Ne fait rien :
     * - sans requête HTTP en cours (exécution en ligne de commande) ;
     * - hors mode développement (`APP_ENV=dev`) ou sans l'extension `pdo_sqlite` ;
     * - pour les routes internes du profiler (`/_debug/...` et `/edu-logs`).
     *
     * En cas d'échec de l'enregistrement, l'erreur est envoyée dans les logs PHP
     * (error_log) : la journalisation ne doit jamais faire échouer la requête.
     *
     * @return void
     */
    public static function start(): void
    {
        self::$currentLogId = null;
        self::$requestParams = ['get' => [], 'post' => []];

        if (self::shouldLog() === false) {
            return;
        }

        self::$requestParams = [
            'get' => self::maskSensitive($_GET),
            'post' => self::maskSensitive($_POST),
        ];

        try {
            self::$currentLogId = LogsService::addLog((string) json_encode(self::buildEvent(
                (string) $_SERVER['REQUEST_METHOD'],
                self::requestPath(),
                self::clientIpPort(),
                (int) (http_response_code() ?: 200),
                PHP_SAPI,
                null,
                null,
                null,
                self::$requestParams['get'],
                self::$requestParams['post']
            ), self::JSON_FLAGS));
        } catch (\Throwable $exception) {
            error_log('[EduFramework Logs] ' . $exception->getMessage());
        }
    }

    /**
     * Complète la ligne de log de la requête HTTP en cours (réservée par start())
     * avec le statut final, le temps d'exécution, la mémoire utilisée et, si une
     * erreur a été enregistrée par setError(), sa description.
     * Si la requête n'est pas journalisée, ne fait rien.
     *
     * @return void
     */
    public static function finish(): void
    {
        $logId = self::$currentLogId;
        self::$currentLogId = null;
        $errorMessage = self::$errorMessage;
        self::$errorMessage = null;
        $requestParams = self::$requestParams;
        self::$requestParams = ['get' => [], 'post' => []];

        if ($logId === null) {
            return;
        }

        // Temps d'exécution et mémoire utilisée, mesurés comme la barre de debug
        $executionTimeMs = round((microtime(true) - (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 1);
        $memoryMb = round(memory_get_peak_usage(true) / 1048576, 2);

        // Variables de session en fin de requête (après l'exécution du controller),
        // uniquement si l'application a démarré une session
        $sessionParams = (isset($_SESSION) && is_array($_SESSION))
            ? self::maskSensitive((array) self::normalize($_SESSION))
            : null;

        try {
            LogsService::updateLog($logId, (string) json_encode(self::buildEvent(
                (string) $_SERVER['REQUEST_METHOD'],
                self::requestPath(),
                self::clientIpPort(),
                (int) (http_response_code() ?: 200),
                PHP_SAPI,
                $executionTimeMs,
                $memoryMb,
                $errorMessage,
                $requestParams['get'],
                $requestParams['post'],
                $sessionParams
            ), self::JSON_FLAGS));
        } catch (\Throwable $exception) {
            error_log('[EduFramework Logs] ' . $exception->getMessage());
        }
    }

    /**
     * Enregistre l'exception à l'origine de l'erreur de la requête HTTP en cours
     * (Exemple : un controller qui lève une erreur 500)
     * La description de l'exception (message, fichier et ligne) est ajoutée au log
     * de la requête par finish(), afin d'aider les développeurs à identifier
     * et corriger l'erreur directement depuis les logs
     *
     * @param \Throwable|null $exception L'exception attrapée, null pour réinitialiser
     * @return void
     */
    public static function setError(\Throwable|null $exception): void
    {
        if ($exception === null) {
            self::$errorMessage = null;
            return;
        }

        self::$errorMessage = sprintf(
            '%s : %s (fichier %s, ligne %d)',
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
    }

    /**
     * Retourne l'identifiant (token) du log de la requête en cours,
     * ou null si la requête n'est pas journalisée
     *
     * @return int|null
     */
    public static function getCurrentLogId(): int|null
    {
        return self::$currentLogId;
    }

    /**
     * Indique si la requête HTTP en cours doit être journalisée
     *
     * @return bool
     */
    private static function shouldLog(): bool
    {
        if (isset($_SERVER['REQUEST_METHOD']) === false) {
            return false;
        }

        if (ConfigCore::existEnv('APP_ENV') === false || ConfigCore::getEnv('APP_ENV') !== 'dev'
            || in_array('sqlite', PDO::getAvailableDrivers(), true) === false) {
            return false;
        }

        $path = self::requestPath();

        return str_starts_with($path, '/_debug/') === false
            && str_starts_with($path, '/edu-logs') === false
            && str_starts_with($path, '/edu-sql-logs') === false
            && str_starts_with($path, '/edu-code-profile') === false;
    }

    /**
     * Retourne le chemin de la requête en cours (avec la chaîne de requête)
     *
     * @return string
     */
    private static function requestPath(): string
    {
        return (string) ($_SERVER['REQUEST_URI'] ?? '/');
    }

    /**
     * Construit l'événement de la requête, au même format que BufferToServer
     * (analyse de la sortie du serveur PHP intégré), par exemple :
     * `[Mon Oct  5 12:34:56 2026] 127.0.0.1:65229 [200]: GET /`
     * La clé `sapi` identifie le serveur à l'origine de la requête
     * (par défaut le SAPI courant : frankenphp, cli-server, ...)
     * Les indicateurs de performance `execution_time_ms` et `memory_mb`
     * sont ajoutés uniquement s'ils sont fournis
     * La clé `error_message` est ajoutée uniquement si une erreur a été
     * enregistrée par setError() : elle décrit l'exception à l'origine
     * de la réponse (message, fichier et ligne)
     * Les clés `get` et `post` sont ajoutées uniquement si la requête contient
     * des variables GET ($_GET) ou POST ($_POST), afin de pouvoir les consulter
     * dans le profiler pour déboguer
     * La clé `session` est ajoutée uniquement si la session contient des
     * variables ($_SESSION en fin de requête)
     *
     * @param string $method Méthode HTTP (GET, POST, ...)
     * @param string $path Chemin de la requête (avec la chaîne de requête)
     * @param string $ipPort Adresse IP et port du client (ip:port)
     * @param int $status Code statut de la réponse HTTP
     * @param string $sapi SAPI ayant servi la requête (PHP_SAPI par défaut)
     * @param float|null $executionTimeMs Temps d'exécution en millisecondes
     * @param float|null $memoryMb Mémoire utilisée en mégaoctets (pic)
     * @param string|null $errorMessage Description de l'erreur à l'origine de la réponse
     *     (message, fichier et ligne de l'exception)
     * @param array<mixed>|null $getParams Variables GET de la requête
     * @param array<mixed>|null $postParams Variables POST de la requête
     * @param array<mixed>|null $sessionParams Variables de session en fin de requête
     * @return array<string, mixed> Événement au format BufferToServer
     *        (clés raw, timestamp, ip_port, status_code, method, path, sapi,
     *        et execution_time_ms / memory_mb / error_message / get / post / session
     *        si fournis)
     */
    public static function buildEvent(
        string $method,
        string $path,
        string $ipPort,
        int $status,
        string $sapi = PHP_SAPI,
        float|null $executionTimeMs = null,
        float|null $memoryMb = null,
        string|null $errorMessage = null,
        array|null $getParams = null,
        array|null $postParams = null,
        array|null $sessionParams = null
    ): array {
        $timestamp = date('D M ') . sprintf('%2d', (int) date('j')) . date(' H:i:s Y');

        $event = [
            'raw' => sprintf('[%s] %s [%d]: %s %s', $timestamp, $ipPort, $status, $method, $path),
            'timestamp' => $timestamp,
            'ip_port' => $ipPort,
            'status_code' => (string) $status,
            'method' => $method,
            'path' => $path,
            'sapi' => $sapi,
        ];

        if ($executionTimeMs !== null) {
            $event['execution_time_ms'] = round($executionTimeMs, 1);
        }
        if ($memoryMb !== null) {
            $event['memory_mb'] = round($memoryMb, 2);
        }
        if ($errorMessage !== null && $errorMessage !== '') {
            $event['error_message'] = $errorMessage;
        }
        if ($getParams !== null && $getParams !== []) {
            $event['get'] = $getParams;
        }
        if ($postParams !== null && $postParams !== []) {
            $event['post'] = $postParams;
        }
        if ($sessionParams !== null && $sessionParams !== []) {
            $event['session'] = $sessionParams;
        }

        return $event;
    }

    /**
     * Masque récursivement les valeurs des variables sensibles (mots de passe,
     * secrets) afin qu'elles ne soient jamais enregistrées en clair dans les logs.
     * Une variable est sensible si son nom contient `pass`, `pwd`, `secret`, `mot_de_passe`
     * ou `mdp` (insensible à la casse : password, mot_de_passe, user_pwd, ...).
     *
     * @param array<mixed> $params Variables de la requête ($_GET, $_POST)
     * @return array<mixed> Variables avec les valeurs sensibles masquées
     */
    public static function maskSensitive(array $params): array
    {
        foreach ($params as $key => $value) {
            if (is_string($key) && preg_match('/pass|pwd|secret|mot_?de_?passe|mdp/i', $key) === 1) {
                $params[$key] = self::MASKED_VALUE;
            } elseif (is_array($value)) {
                $params[$key] = self::maskSensitive($value);
            }
        }

        return $params;
    }

    /**
     * Convertit une valeur (de session) en données encodables en JSON :
     * - les objets deviennent un tableau de toutes leurs propriétés (y compris
     *   privées et protégées), précédé de la clé `__class` (nom de la classe) ;
     * - les ressources deviennent une chaîne `resource(type)` ;
     * - au-delà de MAX_DEPTH niveaux, la valeur est remplacée par `…`.
     *
     * @param mixed $value Valeur à convertir
     * @param int $depth Profondeur courante
     * @return mixed Valeur encodable en JSON
     */
    public static function normalize(mixed $value, int $depth = 0): mixed
    {
        if (is_array($value) === false && is_object($value) === false) {
            if (is_resource($value) === true || gettype($value) === 'resource (closed)') {
                return 'resource(' . get_resource_type($value) . ')';
            }
            if (is_float($value) && is_finite($value) === false) {
                return (string) $value;
            }
            return $value;
        }

        if ($depth >= self::MAX_DEPTH) {
            return '…';
        }

        $normalized = [];
        if (is_object($value) === true) {
            $normalized['__class'] = $value::class;
            if ($value instanceof \Closure) {
                return $normalized;
            }
            foreach ((array) $value as $key => $item) {
                // Les propriétés privées / protégées sont préfixées par \0Classe\0 ou \0*\0
                $name = (string) $key;
                $pos = strrpos($name, "\0");
                $normalized[$pos === false ? $name : substr($name, $pos + 1)] = self::normalize($item, $depth + 1);
            }
            return $normalized;
        }

        foreach ($value as $key => $item) {
            $normalized[$key] = self::normalize($item, $depth + 1);
        }
        return $normalized;
    }

    /**
     * Retourne l'adresse IP et le port du client au format ip:port
     * (port à 0 si le SAPI ne fournit pas le port du client)
     *
     * @return string
     */
    private static function clientIpPort(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $port = (string) ($_SERVER['REMOTE_PORT'] ?? '0');

        return $ip . ':' . $port;
    }
}

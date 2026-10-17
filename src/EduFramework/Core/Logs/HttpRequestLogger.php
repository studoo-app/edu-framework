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
 *   d'exécution (execution_time_ms) et la mémoire utilisée (memory_mb).
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

        if (self::shouldLog() === false) {
            return;
        }

        try {
            self::$currentLogId = LogsService::addLog((string) json_encode(self::buildEvent(
                (string) $_SERVER['REQUEST_METHOD'],
                self::requestPath(),
                self::clientIpPort(),
                (int) (http_response_code() ?: 200),
                PHP_SAPI
            )));
        } catch (\Throwable $exception) {
            error_log('[EduFramework Logs] ' . $exception->getMessage());
        }
    }

    /**
     * Complète la ligne de log de la requête HTTP en cours (réservée par start())
     * avec le statut final, le temps d'exécution et la mémoire utilisée.
     * Si la requête n'est pas journalisée, ne fait rien.
     *
     * @return void
     */
    public static function finish(): void
    {
        $logId = self::$currentLogId;
        self::$currentLogId = null;

        if ($logId === null) {
            return;
        }

        // Temps d'exécution et mémoire utilisée, mesurés comme la barre de debug
        $executionTimeMs = round((microtime(true) - (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))) * 1000, 1);
        $memoryMb = round(memory_get_peak_usage(true) / 1048576, 2);

        try {
            LogsService::updateLog($logId, (string) json_encode(self::buildEvent(
                (string) $_SERVER['REQUEST_METHOD'],
                self::requestPath(),
                self::clientIpPort(),
                (int) (http_response_code() ?: 200),
                PHP_SAPI,
                $executionTimeMs,
                $memoryMb
            )));
        } catch (\Throwable $exception) {
            error_log('[EduFramework Logs] ' . $exception->getMessage());
        }
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

        return str_starts_with($path, '/_debug/') === false && str_starts_with($path, '/edu-logs') === false;
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
     *
     * @param string $method Méthode HTTP (GET, POST, ...)
     * @param string $path Chemin de la requête (avec la chaîne de requête)
     * @param string $ipPort Adresse IP et port du client (ip:port)
     * @param int $status Code statut de la réponse HTTP
     * @param string $sapi SAPI ayant servi la requête (PHP_SAPI par défaut)
     * @param float|null $executionTimeMs Temps d'exécution en millisecondes
     * @param float|null $memoryMb Mémoire utilisée en mégaoctets (pic)
     * @return array<string, string|float> Événement au format BufferToServer
     *        (clés raw, timestamp, ip_port, status_code, method, path, sapi,
     *        et execution_time_ms / memory_mb si fournis)
     */
    public static function buildEvent(
        string $method,
        string $path,
        string $ipPort,
        int $status,
        string $sapi = PHP_SAPI,
        float|null $executionTimeMs = null,
        float|null $memoryMb = null
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

        return $event;
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

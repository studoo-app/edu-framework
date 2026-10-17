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
 * Journalise les requêtes HTTP reçues lorsque l'application n'est pas servie
 * par le serveur PHP intégré (SAPI `cli-server`), par exemple sous FrankenPHP.
 * Les événements produits ont exactement le même format que ceux issus de
 * l'analyse de la sortie du serveur intégré (BufferToServer), afin que le
 * profiler affiche et filtre les requêtes de tous les serveurs de la même façon.
 *
 * @package Studoo\EduFramework\Core\Logs
 */
class HttpRequestLogger
{
    /**
     * Journalise la requête HTTP en cours dans la base des logs du profiler.
     *
     * Ne fait rien :
     * - en SAPI `cli-server` : ces requêtes sont déjà journalisées par la commande
     *   `php bin/edu start` (analyse de la sortie du serveur), on évite un doublon ;
     * - sans requête HTTP en cours (exécution en ligne de commande) ;
     * - hors mode développement (`APP_ENV=dev`) ou sans l'extension `pdo_sqlite` ;
     * - pour les routes internes du profiler (`/_debug/...` et `/edu-logs`).
     *
     * En cas d'échec de l'enregistrement, l'erreur est envoyée dans les logs PHP
     * (error_log) : la journalisation ne doit jamais faire échouer la requête.
     *
     * @return void
     */
    public static function log(): void
    {
        if (PHP_SAPI === 'cli-server') {
            return;
        }

        if (isset($_SERVER['REQUEST_METHOD']) === false) {
            return;
        }

        if (ConfigCore::existEnv('APP_ENV') === false || ConfigCore::getEnv('APP_ENV') !== 'dev'
            || in_array('sqlite', PDO::getAvailableDrivers(), true) === false) {
            return;
        }

        $path = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        if (str_starts_with($path, '/_debug/') === true || str_starts_with($path, '/edu-logs') === true) {
            return;
        }

        $event = json_encode(self::buildEvent(
            (string) $_SERVER['REQUEST_METHOD'],
            $path,
            self::clientIpPort(),
            (int) (http_response_code() ?: 200)
        ));

        if (is_string($event) === true) {
            try {
                LogsService::addLog($event);
            } catch (\Throwable $exception) {
                error_log('[EduFramework Logs] ' . $exception->getMessage());
            }
        }
    }

    /**
     * Construit l'événement de la requête, au même format que BufferToServer
     * (analyse de la sortie du serveur PHP intégré), par exemple :
     * `[Mon Oct  5 12:34:56 2026] 127.0.0.1:65229 [200]: GET /`
     * La clé `sapi` identifie le serveur à l'origine de la requête
     * (par défaut le SAPI courant : frankenphp, fpm-fcgi, ...)
     *
     * @param string $method Méthode HTTP (GET, POST, ...)
     * @param string $path Chemin de la requête (avec la chaîne de requête)
     * @param string $ipPort Adresse IP et port du client (ip:port)
     * @param int $status Code statut de la réponse HTTP
     * @param string $sapi SAPI ayant servi la requête (PHP_SAPI par défaut)
     * @return array<string, string> Événement au format BufferToServer
     *        (clés raw, timestamp, ip_port, status_code, method, path, sapi)
     */
    public static function buildEvent(string $method, string $path, string $ipPort, int $status, string $sapi = PHP_SAPI): array
    {
        $timestamp = date('D M ') . sprintf('%2d', (int) date('j')) . date(' H:i:s Y');

        return [
            'raw' => sprintf('[%s] %s [%d]: %s %s', $timestamp, $ipPort, $status, $method, $path),
            'timestamp' => $timestamp,
            'ip_port' => $ipPort,
            'status_code' => (string) $status,
            'method' => $method,
            'path' => $path,
            'sapi' => $sapi,
        ];
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

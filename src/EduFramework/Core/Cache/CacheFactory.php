<?php

/*
 * Edu Framework by studoo labs
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Cache;

use Predis\Client;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Exception\ErrorConfigException;

/**
 * Class CacheFactory
 * Fabrique du cache applicatif à partir des variables d'environnement :
 *     - CACHE_DRIVER : none | file | redis (file par défaut)
 *     - CACHE_PREFIX : préfixe des clés (edu: par défaut)
 *     - CACHE_TTL    : durée de vie par défaut en secondes (3600 par défaut, 0 = pas d'expiration)
 *     - REDIS_HOST, REDIS_PORT, REDIS_PASSWORD, REDIS_DB (ou REDIS_URL, voir ConfigCore)
 * En mode développement (APP_ENV=dev), le driver est enveloppé dans un TraceableCache
 * qui alimente la barre de debug.
 * @package Studoo\EduFramework\Core\Cache
 */
class CacheFactory
{
    /**
     * Drivers disponibles
     */
    public const DRIVERS = ['none', 'file', 'redis'];

    /**
     * Crée le cache correspondant à la configuration
     * @return CacheInterface
     * @throws ErrorConfigException
     */
    public static function create(): CacheInterface
    {
        $driver = self::env('CACHE_DRIVER', 'file');
        $prefix = ConfigCore::existEnv('CACHE_PREFIX') ? ConfigCore::getEnv('CACHE_PREFIX') : 'edu:';
        $ttl = self::intEnv('CACHE_TTL', 3600);

        $cache = match ($driver) {
            'none' => new NullCache(),
            'file' => new FileCache(ConfigCore::getConfig('cache_path') . 'data/', $prefix, $ttl),
            'redis' => self::createRedisCache($prefix, $ttl),
            default => throw new ErrorConfigException(
                "La variable CACHE_DRIVER=" . $driver . " est invalide. Valeurs possibles : "
                . implode(', ', self::DRIVERS)
            ),
        };

        return self::isDev() ? new TraceableCache($cache) : $cache;
    }

    /**
     * Crée le driver Redis et son client Predis
     * @param string $prefix Préfixe des clés
     * @param int $ttl Durée de vie par défaut
     * @return RedisCache
     * @throws ErrorConfigException
     */
    private static function createRedisCache(string $prefix, int $ttl): RedisCache
    {
        // Une REDIS_URL mal formée n'est signalée qu'ici, quand le driver redis est réellement utilisé
        $redisUrlError = ConfigCore::getRedisUrlError();
        if ($redisUrlError !== null) {
            throw new ErrorConfigException($redisUrlError);
        }

        $parameters = [
            'scheme' => self::env('REDIS_SCHEME', 'tcp'),
            'host' => self::env('REDIS_HOST', '127.0.0.1'),
            'port' => self::intEnv('REDIS_PORT', 6379),
            'database' => self::intEnv('REDIS_DB', 0),
            // Délai de connexion court : une page ne doit pas rester bloquée si Redis est arrêté
            'timeout' => (float) self::env('REDIS_TIMEOUT', '2'),
        ];
        foreach (['username' => 'REDIS_USERNAME', 'password' => 'REDIS_PASSWORD'] as $parameter => $key) {
            if (self::env($key, '') !== '') {
                $parameters[$parameter] = self::env($key, '');
            }
        }

        // La connexion est ouverte à la première commande, pas à la création du client
        $client = new Client($parameters, $prefix !== '' ? ['prefix' => $prefix] : []);

        // En production, un Redis injoignable ne doit pas rendre le site indisponible
        return new RedisCache(
            $client,
            $prefix,
            $ttl,
            self::isDev() === false,
            $parameters['host'] . ':' . $parameters['port']
        );
    }

    /**
     * Lit une variable d'environnement texte
     * @param string $key Nom de la variable
     * @param string $default Valeur si la variable est absente ou vide
     * @return string
     */
    private static function env(string $key, string $default): string
    {
        $value = trim((string) ConfigCore::getEnv($key));

        return $value === '' ? $default : $value;
    }

    /**
     * Lit une variable d'environnement entière positive
     * @param string $key Nom de la variable
     * @param int $default Valeur si la variable est absente ou vide
     * @return int
     * @throws ErrorConfigException
     */
    private static function intEnv(string $key, int $default): int
    {
        $value = self::env($key, (string) $default);
        if (ctype_digit($value) === false) {
            throw new ErrorConfigException(
                "La variable " . $key . "=" . $value . " est invalide : un nombre entier positif est attendu"
            );
        }

        return (int) $value;
    }

    /**
     * Indique si l'application est en mode développement
     * @return bool
     */
    private static function isDev(): bool
    {
        return ConfigCore::existEnv('APP_ENV') === true && ConfigCore::getEnv('APP_ENV') === 'dev';
    }
}

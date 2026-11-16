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

use Predis\ClientInterface;
use Studoo\EduFramework\Core\Exception\ErrorCacheException;
use Studoo\EduFramework\Core\Exception\ErrorConfigException;

/**
 * Class CacheService
 * Point d'accès au cache applicatif, depuis un controller, un repository ou une commande CLI.
 *
 * Le cache est créé à la première utilisation, selon CACHE_DRIVER (none, file ou redis) :
 * une page qui n'utilise pas le cache n'ouvre aucune connexion à Redis.
 *
 * Exemple :
 * ```
 * use Studoo\EduFramework\Core\Cache\CacheService;
 *
 * $cache = CacheService::getCache();
 * $villes = $cache->get('ville:list', []);
 *
 * // Commandes Redis directes (CACHE_DRIVER=redis uniquement) : hset, rename, sadd...
 * CacheService::getRedis()->hset('ville:1', 'nom', 'Paris');
 * ```
 * @package Studoo\EduFramework\Core\Cache
 */
class CacheService
{
    /**
     * Cache de l'application, créé à la première utilisation
     * @var CacheInterface|null
     */
    private static ?CacheInterface $cache = null;

    /**
     * Retourne le cache de l'application
     * @return CacheInterface
     * @throws ErrorConfigException Si la configuration du cache est invalide
     */
    public static function getCache(): CacheInterface
    {
        return self::$cache ??= CacheFactory::create();
    }

    /**
     * Retourne le client Predis pour envoyer des commandes Redis (hset, hgetall, rename, sadd...)
     * Le client utilise le préfixe CACHE_PREFIX : $redis->get('ville:list') lit la clé edu:ville:list
     * Documentation des commandes : https://redis.io/docs/latest/commands/
     * @return ClientInterface
     * @throws ErrorCacheException Si le driver du cache n'est pas redis
     * @throws ErrorConfigException Si la configuration du cache est invalide
     */
    public static function getRedis(): ClientInterface
    {
        $cache = self::getCache();
        if ($cache instanceof TraceableCache) {
            $cache = $cache->getInnerCache();
        }

        if ($cache instanceof RedisCache === false) {
            throw new ErrorCacheException(
                "Les commandes Redis nécessitent CACHE_DRIVER=redis dans le .env (driver actuel : "
                . $cache->getDriverName() . ")"
            );
        }

        return $cache->getClient();
    }

    /**
     * Remplace le cache de l'application (tests unitaires)
     * @param CacheInterface|null $cache Cache à utiliser, null => recréé depuis la configuration
     * @return void
     */
    public static function setCache(?CacheInterface $cache): void
    {
        self::$cache = $cache;
    }
}

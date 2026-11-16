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

/**
 * Class TraceableCache
 * Décorateur du cache, utilisé uniquement en mode développement (APP_ENV=dev) :
 * il mesure chaque opération du driver réel et la transmet au CacheCollector
 * (affichage dans la barre de debug). Le comportement du cache est inchangé.
 * @package Studoo\EduFramework\Core\Cache
 */
class TraceableCache implements CacheInterface
{
    /**
     * @param CacheInterface $cache Driver réel (redis, file ou none)
     */
    public function __construct(private readonly CacheInterface $cache)
    {
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Le marqueur permet de savoir si la clé a été trouvée, même si elle contient null
        $missing = new \stdClass();
        $start = microtime(true);
        $value = $this->cache->get($key, $missing);
        CacheCollector::collect('get', $key, $value !== $missing, self::elapsedMs($start));

        return $value === $missing ? $default : $value;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $start = microtime(true);
        $result = $this->cache->set($key, $value, $ttl);
        CacheCollector::collect('set', $key, null, self::elapsedMs($start));

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        $start = microtime(true);
        $result = $this->cache->has($key);
        CacheCollector::collect('has', $key, $result, self::elapsedMs($start));

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        $start = microtime(true);
        $result = $this->cache->delete($key);
        CacheCollector::collect('delete', $key, null, self::elapsedMs($start));

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $start = microtime(true);
        $result = $this->cache->clear();
        CacheCollector::collect('clear', '', null, self::elapsedMs($start));

        return $result;
    }

    /**
     * La lecture et l'éventuelle écriture passent par get() et set() : elles sont donc tracées
     * @inheritDoc
     */
    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        return AbstractCache::rememberWith($this, $key, $ttl, $callback);
    }

    /**
     * @inheritDoc
     */
    public function getDriverName(): string
    {
        return $this->cache->getDriverName();
    }

    /**
     * Retourne le driver réel
     * @return CacheInterface
     */
    public function getInnerCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * Retourne le temps écoulé depuis $start, en millisecondes arrondi à 2 décimales
     * @param float $start Timestamp microtime(true) du début de la mesure
     * @return float
     */
    private static function elapsedMs(float $start): float
    {
        return round((microtime(true) - $start) * 1000, 2);
    }
}

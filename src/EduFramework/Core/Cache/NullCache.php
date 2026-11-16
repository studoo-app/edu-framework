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
 * Class NullCache
 * Driver "pas de cache" (CACHE_DRIVER=none) : rien n'est enregistré,
 * chaque lecture retourne la valeur par défaut et remember() exécute toujours le calcul.
 * Pratique pour comparer une page avec et sans cache.
 * @package Studoo\EduFramework\Core\Cache
 */
class NullCache implements CacheInterface
{
    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        return $callback();
    }

    /**
     * @inheritDoc
     */
    public function getDriverName(): string
    {
        return 'none';
    }
}

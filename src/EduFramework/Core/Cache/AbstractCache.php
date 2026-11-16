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

use JsonException;
use Studoo\EduFramework\Core\Exception\ErrorCacheException;

/**
 * Class AbstractCache
 * Socle commun des drivers qui stockent réellement les valeurs (redis, file) :
 *     - conversion des valeurs en JSON (lisible dans Redis Insight ou dans le fichier)
 *     - contrôle du nom des clés et de la durée de vie
 *     - méthode remember()
 * Chaque driver n'a plus qu'à lire, écrire et supprimer un texte JSON (méthodes fetch, store, remove).
 * @package Studoo\EduFramework\Core\Cache
 */
abstract class AbstractCache implements CacheInterface
{
    /**
     * @param int $defaultTtl Durée de vie par défaut en secondes (CACHE_TTL), 0 => pas d'expiration
     */
    public function __construct(protected readonly int $defaultTtl = 0)
    {
    }

    /**
     * Lit le texte JSON stocké pour une clé
     * @param string $key Nom de la clé (déjà contrôlé)
     * @return string|null null si la clé est absente ou expirée
     * @throws ErrorCacheException
     */
    abstract protected function fetch(string $key): ?string;

    /**
     * Enregistre le texte JSON d'une clé
     * @param string $key Nom de la clé (déjà contrôlé)
     * @param string $payload Valeur convertie en JSON
     * @param int $ttl Durée de vie en secondes, 0 => pas d'expiration
     * @return bool
     * @throws ErrorCacheException
     */
    abstract protected function store(string $key, string $payload, int $ttl): bool;

    /**
     * Supprime une clé
     * @param string $key Nom de la clé (déjà contrôlé)
     * @return bool
     * @throws ErrorCacheException
     */
    abstract protected function remove(string $key): bool;

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $payload = $this->fetch($this->checkKey($key));
        if ($payload === null) {
            return $default;
        }

        return $this->decode($key, $payload);
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->store($this->checkKey($key), $this->encode($key, $value), $this->resolveTtl($ttl));
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->fetch($this->checkKey($key)) !== null;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        return $this->remove($this->checkKey($key));
    }

    /**
     * @inheritDoc
     */
    public function remember(string $key, ?int $ttl, callable $callback): mixed
    {
        return self::rememberWith($this, $key, $ttl, $callback);
    }

    /**
     * Implémentation de remember() commune à tous les drivers (y compris TraceableCache) :
     * lecture, puis calcul et écriture si la clé est absente
     * @param CacheInterface $cache Cache utilisé
     * @param string $key Nom de la clé
     * @param int|null $ttl Durée de vie en secondes
     * @param callable(): mixed $callback Calcule la valeur si elle est absente du cache
     * @return mixed
     * @throws ErrorCacheException
     */
    public static function rememberWith(CacheInterface $cache, string $key, ?int $ttl, callable $callback): mixed
    {
        // Un objet "marqueur" : une valeur JSON décodée ne peut jamais être cet objet,
        // on distingue ainsi une clé absente d'une clé qui contient null ou false
        $missing = new \stdClass();
        $value = $cache->get($key, $missing);
        if ($value !== $missing) {
            return $value;
        }

        $value = $callback();
        $cache->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Contrôle le nom d'une clé
     * @param string $key Nom de la clé
     * @return string
     * @throws ErrorCacheException
     */
    protected function checkKey(string $key): string
    {
        if (trim($key) === '') {
            throw new ErrorCacheException("Le nom d'une clé du cache ne peut pas être vide (Exemple : ville:list)");
        }

        return $key;
    }

    /**
     * Retourne la durée de vie à appliquer
     * @param int|null $ttl Durée demandée : null => CACHE_TTL, 0 => pas d'expiration
     * @return int
     * @throws ErrorCacheException
     */
    protected function resolveTtl(?int $ttl): int
    {
        $ttl ??= $this->defaultTtl;
        if ($ttl < 0) {
            throw new ErrorCacheException(
                "La durée de vie d'une valeur du cache doit être positive ou nulle (0 = pas d'expiration)"
            );
        }

        return $ttl;
    }

    /**
     * Convertit une valeur en JSON
     * @param string $key Nom de la clé (pour le message d'erreur)
     * @param mixed $value Valeur à convertir
     * @return string
     * @throws ErrorCacheException
     */
    private function encode(string $key, mixed $value): string
    {
        try {
            return json_encode(
                $value,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
            );
        } catch (JsonException $exception) {
            throw new ErrorCacheException(
                "La valeur de la clé <" . $key . "> ne peut pas être convertie en JSON : " . $exception->getMessage()
            );
        }
    }

    /**
     * Convertit un texte JSON en valeur PHP (les objets JSON deviennent des tableaux associatifs)
     * @param string $key Nom de la clé (pour le message d'erreur)
     * @param string $payload Texte JSON
     * @return mixed
     * @throws ErrorCacheException
     */
    private function decode(string $key, string $payload): mixed
    {
        try {
            return json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ErrorCacheException(
                "La valeur de la clé <" . $key . "> n'est pas un JSON valide : " . $exception->getMessage()
            );
        }
    }
}

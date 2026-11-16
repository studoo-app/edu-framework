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

use Studoo\EduFramework\Core\Exception\ErrorCacheException;

/**
 * Interface CacheInterface
 * Contrat commun à tous les drivers du cache applicatif (redis, file, none).
 *
 * Les valeurs sont stockées en JSON : on peut mettre en cache un texte, un nombre,
 * un booléen, null ou un tableau (Exemple : le résultat d'un fetchAll()).
 * Un objet est stocké comme un tableau : il faut le reconstruire à la lecture.
 *
 * Exemple :
 * ```
 * $cache = CacheService::getCache();
 * $cache->set('ville:list', $villes, 600);      // stocke pendant 10 minutes
 * $villes = $cache->get('ville:list', []);      // [] si la clé est absente ou expirée
 * ```
 * @package Studoo\EduFramework\Core\Cache
 */
interface CacheInterface
{
    /**
     * Retourne la valeur d'une clé, ou $default si la clé est absente ou expirée
     * @param string $key Nom de la clé (Exemple : ville:list)
     * @param mixed $default Valeur retournée si la clé est absente
     * @return mixed
     * @throws ErrorCacheException
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Enregistre une valeur
     * @param string $key Nom de la clé
     * @param mixed $value Valeur à stocker (convertie en JSON)
     * @param int|null $ttl Durée de vie en secondes : null => CACHE_TTL du .env, 0 => pas d'expiration
     * @return bool true si la valeur a été enregistrée
     * @throws ErrorCacheException
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Indique si une clé existe (et n'est pas expirée)
     * @param string $key Nom de la clé
     * @return bool
     * @throws ErrorCacheException
     */
    public function has(string $key): bool;

    /**
     * Supprime une clé (aucune erreur si elle n'existe pas)
     * @param string $key Nom de la clé
     * @return bool true si la clé n'existe plus
     * @throws ErrorCacheException
     */
    public function delete(string $key): bool;

    /**
     * Supprime toutes les clés de l'application (celles qui portent le préfixe CACHE_PREFIX)
     * Les autres clés du serveur Redis ne sont jamais supprimées
     * @return bool
     * @throws ErrorCacheException
     */
    public function clear(): bool;

    /**
     * Retourne la valeur d'une clé ; si elle est absente, exécute $callback,
     * enregistre son résultat dans le cache puis le retourne
     *
     * Exemple :
     * ```
     * $villes = $cache->remember('ville:list', 600, fn () => $repository->findAll());
     * ```
     * @param string $key Nom de la clé
     * @param int|null $ttl Durée de vie en secondes : null => CACHE_TTL du .env, 0 => pas d'expiration
     * @param callable(): mixed $callback Calcule la valeur si elle est absente du cache
     * @return mixed
     * @throws ErrorCacheException
     */
    public function remember(string $key, ?int $ttl, callable $callback): mixed;

    /**
     * Nom du driver (redis, file ou none), affiché dans la barre de debug
     * @return string
     */
    public function getDriverName(): string;
}

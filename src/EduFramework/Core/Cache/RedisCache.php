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
use Predis\Collection\Iterator\Keyspace;
use Predis\CommunicationException;
use Predis\Connection\Resource\Exception\StreamInitException;
use Studoo\EduFramework\Core\Exception\ErrorCacheException;

/**
 * Class RedisCache
 * Driver Redis du cache applicatif (CACHE_DRIVER=redis), client Predis.
 *
 * Le client Predis est créé avec l'option "prefix" (CACHE_PREFIX) : la clé ville:list
 * est enregistrée dans Redis sous le nom edu:ville:list. Le même client est retourné
 * par CacheService::getRedis() : les commandes Redis écrites à la main utilisent donc
 * le même préfixe que le cache.
 *
 * Serveur injoignable :
 *     - en développement : une ErrorCacheException explique comment démarrer Redis
 *     - en production (mode "failSafe") : l'erreur est journalisée une fois et le cache
 *       se comporte comme un cache vide, pour que le site reste en ligne
 * @package Studoo\EduFramework\Core\Cache
 */
class RedisCache extends AbstractCache
{
    /**
     * Indique si l'erreur de connexion a déjà été journalisée (mode failSafe)
     * @var bool
     */
    private bool $errorLogged = false;

    /**
     * @param ClientInterface $client Client Predis (créé avec l'option prefix)
     * @param string $prefix Préfixe des clés (CACHE_PREFIX), utilisé par clear()
     * @param int $defaultTtl Durée de vie par défaut en secondes (CACHE_TTL)
     * @param bool $failSafe true => un serveur injoignable se comporte comme un cache vide (production)
     * @param string $server Adresse du serveur, pour les messages d'erreur (Exemple : 127.0.0.1:6379)
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly string $prefix,
        int $defaultTtl = 0,
        private readonly bool $failSafe = false,
        private readonly string $server = ''
    ) {
        parent::__construct($defaultTtl);
    }

    /**
     * @inheritDoc
     */
    protected function fetch(string $key): ?string
    {
        return $this->call(fn () => $this->client->get($key), null);
    }

    /**
     * @inheritDoc
     */
    protected function store(string $key, string $payload, int $ttl): bool
    {
        return $this->call(function () use ($key, $payload, $ttl) {
            // SET cle valeur EX secondes : la clé est supprimée automatiquement par Redis à expiration
            $response = ($ttl > 0)
                ? $this->client->set($key, $payload, 'EX', $ttl)
                : $this->client->set($key, $payload);

            return (string) $response === 'OK';
        }, false);
    }

    /**
     * @inheritDoc
     */
    protected function remove(string $key): bool
    {
        return $this->call(function () use ($key) {
            $this->client->del($key);
            return true;
        }, false);
    }

    /**
     * Supprime les clés qui portent le préfixe CACHE_PREFIX, sans jamais vider toute la base (FLUSHDB) :
     * un serveur Redis peut être partagé par plusieurs applications
     * @return bool
     * @throws ErrorCacheException
     */
    public function clear(): bool
    {
        if ($this->prefix === '') {
            throw new ErrorCacheException(
                "Le vidage du cache Redis nécessite un préfixe : renseignez CACHE_PREFIX dans le .env (Exemple : edu:)"
            );
        }

        return $this->call(function () {
            // SCAN parcourt les clés par paquets sans bloquer le serveur (contrairement à KEYS).
            // Le motif de SCAN n'est pas préfixé par Predis : on le préfixe nous-même...
            $pattern = addcslashes($this->prefix, '*?[]\\') . '*';
            foreach (new Keyspace($this->client, $pattern, 500) as $fullKey) {
                // ... mais DEL l'est : on retire le préfixe du nom retourné par SCAN
                $this->client->del(substr((string) $fullKey, strlen($this->prefix)));
            }
            return true;
        }, false);
    }

    /**
     * @inheritDoc
     */
    public function getDriverName(): string
    {
        return 'redis';
    }

    /**
     * Retourne le client Predis (commandes Redis : hset, rename, sadd...)
     * @return ClientInterface
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * Exécute une commande Redis en gérant l'indisponibilité du serveur
     * @template T
     * @param callable(): T $command Commande à exécuter
     * @param T $fallback Valeur retournée en mode failSafe si le serveur est injoignable
     * @return T
     * @throws ErrorCacheException
     */
    private function call(callable $command, mixed $fallback): mixed
    {
        try {
            return $command();
        } catch (CommunicationException | StreamInitException $exception) {
            // StreamInitException : connexion impossible (serveur arrêté, mauvais hôte ou port)
            // CommunicationException : connexion perdue ou délai dépassé pendant une commande
            $message = "Le serveur Redis <" . $this->server . "> est injoignable : " . $exception->getMessage()
                . ". Démarrez-le (docker compose up -d redis) ou utilisez CACHE_DRIVER=file dans le .env";

            if ($this->failSafe === false) {
                throw new ErrorCacheException($message, 500, $exception);
            }

            // Production : on journalise une seule fois par requête et on continue sans cache
            if ($this->errorLogged === false) {
                error_log('[EduFramework] ' . $message);
                $this->errorLogged = true;
            }

            return $fallback;
        }
    }
}

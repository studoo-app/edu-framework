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
 * Class FileCache
 * Driver fichier du cache applicatif (CACHE_DRIVER=file), sans serveur à installer.
 *
 * Chaque clé est un fichier JSON du dossier var/cache/data/ :
 * ```
 * {"key":"edu:ville:list","expiresAt":1763312400,"value":"[{\"id\":1,\"nom\":\"Paris\"}]"}
 * ```
 * expiresAt vaut 0 si la valeur n'expire pas. La commande "php bin/edu cache:clear" vide ce dossier.
 * @package Studoo\EduFramework\Core\Cache
 */
class FileCache extends AbstractCache
{
    /**
     * @param string $directory Dossier des fichiers du cache (Exemple : var/cache/data/)
     * @param string $prefix Préfixe des clés (CACHE_PREFIX)
     * @param int $defaultTtl Durée de vie par défaut en secondes (CACHE_TTL)
     */
    public function __construct(
        private readonly string $directory,
        private readonly string $prefix = '',
        int $defaultTtl = 0
    ) {
        parent::__construct($defaultTtl);
    }

    /**
     * @inheritDoc
     */
    protected function fetch(string $key): ?string
    {
        $file = $this->path($key);
        $content = is_file($file) ? @file_get_contents($file) : false;
        if ($content === false) {
            return null;
        }

        $entry = json_decode($content, true);
        if (is_array($entry) === false || is_string($entry['value'] ?? null) === false) {
            // Fichier corrompu (écriture interrompue...) : il est supprimé, la clé est considérée absente
            @unlink($file);
            return null;
        }

        $expiresAt = (int) ($entry['expiresAt'] ?? 0);
        if ($expiresAt !== 0 && $expiresAt <= time()) {
            @unlink($file);
            return null;
        }

        return $entry['value'];
    }

    /**
     * @inheritDoc
     */
    protected function store(string $key, string $payload, int $ttl): bool
    {
        $this->ensureDirectory();

        $content = json_encode(
            ['key' => $this->prefix . $key, 'expiresAt' => $ttl > 0 ? time() + $ttl : 0, 'value' => $payload],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        // Écriture dans un fichier temporaire puis renommage : un lecteur ne voit jamais un fichier à moitié écrit
        $file = $this->path($key);
        $tmpFile = $file . '.' . uniqid('', true) . '.tmp';
        if (@file_put_contents($tmpFile, $content) === false) {
            return false;
        }

        if (@rename($tmpFile, $file) === false) {
            @unlink($tmpFile);
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function remove(string $key): bool
    {
        $file = $this->path($key);

        return is_file($file) === false || @unlink($file) === true;
    }

    /**
     * Supprime tous les fichiers du cache applicatif
     * @return bool
     */
    public function clear(): bool
    {
        $success = true;
        foreach (glob($this->directory . '*.cache') ?: [] as $file) {
            $success = @unlink($file) && $success;
        }

        return $success;
    }

    /**
     * @inheritDoc
     */
    public function getDriverName(): string
    {
        return 'file';
    }

    /**
     * Chemin du fichier d'une clé : le nom est un hash, une clé peut donc contenir
     * n'importe quel caractère (: / espace...)
     * @param string $key Nom de la clé
     * @return string
     */
    private function path(string $key): string
    {
        return $this->directory . sha1($this->prefix . $key) . '.cache';
    }

    /**
     * Crée le dossier du cache s'il n'existe pas
     * @return void
     * @throws ErrorCacheException
     */
    private function ensureDirectory(): void
    {
        if (is_dir($this->directory) === false && @mkdir($this->directory, 0775, true) === false
            && is_dir($this->directory) === false) {
            throw new ErrorCacheException(
                "Impossible de créer le dossier du cache <" . $this->directory . ">. Vérifiez les droits d'écriture"
            );
        }
    }
}

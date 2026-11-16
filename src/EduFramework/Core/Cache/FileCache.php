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
        $content = is_file($file) === true ? self::quietly(static fn () => file_get_contents($file)) : false;
        if ($content === false) {
            return null;
        }

        $entry = json_decode($content, true);
        if (is_array($entry) === false || is_string($entry['value'] ?? null) === false) {
            // Fichier corrompu (écriture interrompue...) : il est supprimé, la clé est considérée absente
            self::deleteFile($file);
            return null;
        }

        $expiresAt = (int) ($entry['expiresAt'] ?? 0);
        if ($expiresAt !== 0 && $expiresAt <= time()) {
            self::deleteFile($file);
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
        if (self::quietly(static fn () => file_put_contents($tmpFile, $content)) === false) {
            return false;
        }

        if (self::quietly(static fn () => rename($tmpFile, $file)) === false) {
            self::deleteFile($tmpFile);
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function remove(string $key): bool
    {
        return self::deleteFile($this->path($key));
    }

    /**
     * Supprime tous les fichiers du cache applicatif
     * @return bool
     */
    public function clear(): bool
    {
        $success = true;
        $files = glob($this->directory . '*.cache');
        foreach ($files === false ? [] : $files as $file) {
            $success = self::deleteFile($file) && $success;
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
     * Chemin du fichier d'une clé : le nom est une empreinte SHA-256, une clé peut donc contenir
     * n'importe quel caractère (: / espace...)
     * @param string $key Nom de la clé
     * @return string
     */
    private function path(string $key): string
    {
        return $this->directory . hash('sha256', $this->prefix . $key) . '.cache';
    }

    /**
     * Crée le dossier du cache s'il n'existe pas
     * @return void
     * @throws ErrorCacheException
     */
    private function ensureDirectory(): void
    {
        if (is_dir($this->directory) === false
            && self::quietly(fn () => mkdir($this->directory, 0755, true)) === false
            && is_dir($this->directory) === false) {
            throw new ErrorCacheException(
                "Impossible de créer le dossier du cache <" . $this->directory . ">. Vérifiez les droits d'écriture"
            );
        }
    }

    /**
     * Supprime un fichier s'il existe
     * @param string $file Chemin du fichier
     * @return bool true si le fichier n'existe plus
     */
    private static function deleteFile(string $file): bool
    {
        return is_file($file) === false || self::quietly(static fn () => unlink($file)) === true
            || is_file($file) === false;
    }

    /**
     * Exécute une opération sur un fichier sans afficher d'avertissement PHP :
     * un autre processus peut supprimer ou remplacer un fichier entre deux instructions
     * (Exemple : cache:clear pendant une requête). L'échec est signalé par la valeur false.
     * @param callable(): mixed $operation Opération à exécuter
     * @return mixed Résultat de l'opération
     */
    private static function quietly(callable $operation): mixed
    {
        set_error_handler(static fn (): bool => true);
        try {
            return $operation();
        } finally {
            restore_error_handler();
        }
    }
}

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
 * Class JsonPayload
 * Conversion des valeurs du cache en JSON, et inversement :
 * le JSON reste lisible dans Redis Insight ou dans les fichiers du cache
 * @package Studoo\EduFramework\Core\Cache
 */
final class JsonPayload
{
    /**
     * Convertit une valeur en JSON
     * @param string $key Nom de la clé (pour le message d'erreur)
     * @param mixed $value Valeur à convertir
     * @return string
     * @throws ErrorCacheException
     */
    public static function encode(string $key, mixed $value): string
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
    public static function decode(string $key, string $payload): mixed
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

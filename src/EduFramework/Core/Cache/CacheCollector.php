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
 * Class CacheCollector
 * Collecteur des opérations du cache applicatif pendant une requête (mode dev uniquement) :
 * chaque opération mesurée par TraceableCache est gardée en mémoire pour la barre de debug
 * (lectures trouvées "hit" ou absentes "miss", écritures, suppressions, temps passé).
 * @package Studoo\EduFramework\Core\Cache
 */
class CacheCollector
{
    /**
     * Opérations collectées pendant la requête en cours
     * @var list<array{operation: string, key: string, hit: bool|null, duration_ms: float}>
     */
    private static array $operations = [];

    /**
     * Enregistre une opération du cache
     * @param string $operation get, has, set, delete ou clear
     * @param string $key Nom de la clé ('' pour clear)
     * @param bool|null $hit Pour une lecture : true si la clé a été trouvée, null pour une écriture
     * @param float $durationMs Durée de l'opération en millisecondes
     * @return void
     */
    public static function collect(string $operation, string $key, ?bool $hit, float $durationMs): void
    {
        self::$operations[] = [
            'operation' => $operation,
            'key' => $key,
            'hit' => $hit,
            'duration_ms' => $durationMs,
        ];
    }

    /**
     * Retourne les opérations collectées
     * @return list<array{operation: string, key: string, hit: bool|null, duration_ms: float}>
     */
    public static function all(): array
    {
        return self::$operations;
    }

    /**
     * Retourne la synthèse des opérations collectées
     * @return array{hits: int, misses: int, writes: int, deletes: int, duration_ms: float}
     */
    public static function stats(): array
    {
        $stats = ['hits' => 0, 'misses' => 0, 'writes' => 0, 'deletes' => 0, 'duration_ms' => 0.0];
        foreach (self::$operations as $operation) {
            match ($operation['operation']) {
                'get', 'has' => $operation['hit'] === true ? $stats['hits']++ : $stats['misses']++,
                'set' => $stats['writes']++,
                default => $stats['deletes']++,
            };
            $stats['duration_ms'] += $operation['duration_ms'];
        }
        $stats['duration_ms'] = round($stats['duration_ms'], 2);

        return $stats;
    }

    /**
     * Vide le collecteur
     * @return void
     */
    public static function reset(): void
    {
        self::$operations = [];
    }
}

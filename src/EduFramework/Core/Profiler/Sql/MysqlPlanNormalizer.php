<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Profiler\Sql;

/**
 * Class MysqlPlanNormalizer
 * Normalise un plan EXPLAIN (ou ANALYZE) MySQL / MariaDB en accès aux tables
 * communs aux moteurs (voir SqlExplainer::accesses()) :
 * - access : full_scan (parcours complet de la table), index_scan (parcours complet
 *   d'un index), range (plage d'index), index_lookup (recherche par index),
 *   const (une seule ligne), none (aucune table) ;
 * - indicateurs : filesort (tri sans index), temporary (table temporaire),
 *   join_buffer (jointure sans index), covering (index couvrant),
 *   dependent (sous-requête corrélée), auto_index (index temporaire SQLite)
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql
 */
class MysqlPlanNormalizer
{
    /**
     * Accès normalisé vide : valeurs par défaut de chaque clé
     */
    public const array EMPTY_ACCESS = [
        'step' => 0, 'alias' => null, 'type' => null, 'access' => 'none', 'key' => null, 'possible_keys' => null,
        'rows' => null, 'actual_rows' => null, 'filtered' => null, 'select_type' => '', 'extra' => '',
        'filesort' => false, 'temporary' => false, 'join_buffer' => false, 'covering' => false,
        'dependent' => false, 'auto_index' => false, 'index_columns' => [], 'detail' => null,
    ];

    /**
     * Types d'accès MySQL / MariaDB (colonne "type" de EXPLAIN) => accès normalisé
     */
    private const array ACCESS_TYPES = [
        'system' => 'const',
        'const' => 'const',
        'eq_ref' => 'index_lookup',
        'ref' => 'index_lookup',
        'ref_or_null' => 'index_lookup',
        'fulltext' => 'index_lookup',
        'unique_subquery' => 'index_lookup',
        'index_subquery' => 'index_lookup',
        'index_merge' => 'index_lookup',
        'range' => 'range',
        'index' => 'index_scan',
        'ALL' => 'full_scan',
    ];

    /**
     * Normalise les lignes d'un plan MySQL / MariaDB
     *
     * @param array<int, array<string, mixed>> $rows Lignes du plan
     * @return array<int, array<string, mixed>>
     */
    public static function normalize(array $rows): array
    {
        $accesses = [];
        foreach (array_values($rows) as $index => $row) {
            $accesses[] = self::access($index + 1, array_change_key_case($row, CASE_LOWER));
        }

        return $accesses;
    }

    /**
     * Normalise une ligne du plan
     *
     * @param int $step Numéro de l'étape
     * @param array<string, mixed> $row Ligne du plan (clés en minuscules)
     * @return array<string, mixed>
     */
    private static function access(int $step, array $row): array
    {
        $type = self::text($row, 'type');

        return array_merge(self::EMPTY_ACCESS, self::extraFlags($row), [
            'step' => $step,
            'alias' => self::text($row, 'table'),
            'type' => $type,
            'access' => ($type === null ? 'none' : (self::ACCESS_TYPES[$type] ?? 'other')),
            'key' => self::text($row, 'key'),
            'possible_keys' => self::text($row, 'possible_keys'),
            'rows' => self::number($row, 'rows', true),
            'actual_rows' => self::number($row, 'r_rows'),
            'filtered' => self::number($row, 'filtered'),
        ]);
    }

    /**
     * Retourne les indicateurs de la colonne Extra et du type de SELECT
     *
     * @param array<string, mixed> $row Ligne du plan (clés en minuscules)
     * @return array<string, mixed>
     */
    private static function extraFlags(array $row): array
    {
        $extra = (string) ($row['extra'] ?? '');
        $items = array_map('trim', explode(';', $extra));
        $selectType = (string) ($row['select_type'] ?? '');

        return [
            'select_type' => $selectType,
            'extra' => $extra,
            'filesort' => in_array('Using filesort', $items, true),
            'temporary' => in_array('Using temporary', $items, true),
            'join_buffer' => stripos($extra, 'join buffer') !== false,
            'covering' => in_array('Using index', $items, true),
            'dependent' => preg_match('/DEPENDENT|UNCACHEABLE/i', $selectType) === 1,
        ];
    }

    /**
     * Retourne une valeur texte de la ligne, null si absente
     *
     * @param array<string, mixed> $row Ligne du plan
     * @param string $key Colonne
     * @return string|null
     */
    private static function text(array $row, string $key): ?string
    {
        return (isset($row[$key]) === true ? (string) $row[$key] : null);
    }

    /**
     * Retourne une valeur numérique de la ligne, null si absente ou non numérique
     *
     * @param array<string, mixed> $row Ligne du plan
     * @param string $key Colonne
     * @param bool $integer Retourner un entier
     * @return int|float|null
     */
    private static function number(array $row, string $key, bool $integer = false): int|float|null
    {
        $value = $row[$key] ?? null;
        if (is_numeric($value) === false) {
            return null;
        }

        return ($integer === true ? (int) $value : (float) $value);
    }
}

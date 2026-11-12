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
 * Class SqlitePlanNormalizer
 * Normalise un plan EXPLAIN QUERY PLAN SQLite en accès aux tables communs aux
 * moteurs (voir MysqlPlanNormalizer) : SCAN (parcours complet), SEARCH (recherche
 * par index), index automatique, index couvrant et sous-requête corrélée.
 * Les étapes "USE TEMP B-TREE" (tri ou regroupement sans index) sont rattachées
 * au premier accès, comme MySQL rattache "Using filesort" à la première table.
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql
 */
class SqlitePlanNormalizer
{
    /**
     * Étape d'accès à une table : SCAN ou SEARCH, table, alias et méthode (USING ...)
     */
    private const string ACCESS_STEP = '/^(SCAN|SEARCH)\s+(?:TABLE\s+)?(\S+)(?:\s+AS\s+(\S+))?(?:\s+USING\s+(.*))?$/i';

    /**
     * Normalise les lignes d'un plan SQLite (id, parent, detail)
     *
     * @param array<int, array<string, mixed>> $rows Lignes du plan
     * @return array<int, array<string, mixed>>
     */
    public static function normalize(array $rows): array
    {
        $accesses = [];
        $correlated = [];
        $flags = [];
        foreach (array_values($rows) as $index => $row) {
            $detail = (string) ($row['detail'] ?? '');
            if (preg_match('/^(?:CORRELATED|DEPENDENT) .*SUBQUERY/i', $detail) === 1) {
                $correlated[(int) ($row['id'] ?? 0)] = true;
                continue;
            }
            if (preg_match('/^USE TEMP B-TREE FOR (.+)$/i', $detail, $match) === 1) {
                $flags[stripos($match[1], 'ORDER BY') !== false ? 'filesort' : 'temporary'] = true;
                continue;
            }
            if (preg_match(self::ACCESS_STEP, $detail, $match) === 1) {
                $accesses[] = self::access($index + 1, $match, isset($correlated[(int) ($row['parent'] ?? 0)]));
            }
        }

        if ($accesses !== []) {
            $accesses[0] = array_merge($accesses[0], $flags);
        }

        return $accesses;
    }

    /**
     * Normalise une étape d'accès à une table
     *
     * @param int $step Numéro de l'étape
     * @param array<int, string> $match Étape lue (SCAN|SEARCH, table, alias, méthode)
     * @param bool $correlated L'étape appartient à une sous-requête corrélée
     * @return array<string, mixed>
     */
    private static function access(int $step, array $match, bool $correlated): array
    {
        $using = $match[4] ?? '';
        $isAuto = stripos($using, 'AUTOMATIC') !== false;

        return array_merge(MysqlPlanNormalizer::EMPTY_ACCESS, [
            'step' => $step,
            'alias' => (($match[3] ?? '') !== '' ? $match[3] : $match[2]),
            'type' => strtoupper($match[1]),
            'access' => self::accessType(strtoupper($match[1]), $using),
            'key' => ($isAuto === true ? null : self::key($using)),
            'select_type' => ($correlated === true ? 'CORRELATED SUBQUERY' : ''),
            'extra' => $using,
            'covering' => stripos($using, 'COVERING INDEX') !== false,
            'dependent' => $correlated,
            'auto_index' => $isAuto,
            'index_columns' => self::indexColumns($using),
            'detail' => $match[0],
        ]);
    }

    /**
     * Retourne l'accès normalisé d'une étape : recherche par clé primaire (const),
     * plage (range), recherche par index (index_lookup), parcours d'index ou de table
     *
     * @param string $kind SCAN ou SEARCH
     * @param string $using Méthode d'accès (Exemple : INDEX idx_nom (nom=?))
     * @return string
     */
    private static function accessType(string $kind, string $using): string
    {
        if ($kind === 'SCAN') {
            return ($using === '' ? 'full_scan' : 'index_scan');
        }
        if (preg_match('/[<>]/', $using) === 1) {
            return 'range';
        }

        return (stripos($using, 'PRIMARY KEY') !== false ? 'const' : 'index_lookup');
    }

    /**
     * Retourne l'index utilisé par une étape (PRIMARY pour la clé primaire), null si aucun
     *
     * @param string $using Méthode d'accès
     * @return string|null
     */
    private static function key(string $using): ?string
    {
        if (preg_match('/INDEX\s+(\S+)/i', $using, $match) === 1) {
            return $match[1];
        }

        return (stripos($using, 'PRIMARY KEY') !== false ? 'PRIMARY' : null);
    }

    /**
     * Retourne les colonnes de l'index cherchées par une étape (Exemple : (auteur_id=?) => auteur_id)
     *
     * @param string $using Méthode d'accès
     * @return array<int, string>
     */
    private static function indexColumns(string $using): array
    {
        if (preg_match('/\(([^)]*)\)/', $using, $match) !== 1) {
            return [];
        }
        preg_match_all('/(?:^|\bAND\s+)([A-Za-z_]\w*)\s*[=<>]/', $match[1], $columns);

        return array_values(array_filter($columns[1], static fn (string $column): bool => strtolower($column) !== 'rowid'));
    }
}

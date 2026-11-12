<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Profiler\Sql\Advisor;

use Studoo\EduFramework\Core\Profiler\Sql\SqlExplainAdvisor;

/**
 * Class FullScanRule
 * Constats d'un parcours complet de table (type ALL, SCAN) :
 * - index manquant sur une colonne de jointure ou de filtre : index proposé ;
 * - index existant mais ignoré par le moteur : causes possibles ;
 * - aucune recherche sur la table : parcours attendu.
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql\Advisor
 */
class FullScanRule
{
    /**
     * FullScanRule constructor.
     *
     * @param PlanContext $context Plan et requête
     * @param TableSchema $schema Structure des tables
     * @param IndexSuggester $indexes Index proposés
     */
    public function __construct(
        private PlanContext $context,
        private TableSchema $schema,
        private IndexSuggester $indexes
    ) {
    }

    /**
     * Retourne le constat du parcours complet d'une table
     *
     * @param array<string, mixed> $access Accès normalisé
     * @param string $table Nom de la table
     * @param string $alias Alias de la table (minuscules)
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}
     */
    public function finding(array $access, string $table, string $alias): array
    {
        $rows = $this->context->rowsOf($access, $table);
        $scan = [
            'access' => $access,
            'table' => $table,
            'alias' => $alias,
            'rows' => $rows,
            'title' => 'Parcours complet de la table ' . FindingFormatter::tableLabel($table, $alias) . FindingFormatter::rowsLabel($rows),
        ];
        $joins = $this->context->joinColumnsOf($alias, $table);
        $filters = $this->context->filterColumnsOf($alias, $table);
        $missingJoins = $this->unindexed($table, $joins);
        $missingFilters = $this->unindexed($table, $filters);

        if ($missingJoins !== [] || $missingFilters !== []) {
            return $this->missingIndexFinding($scan, $missingJoins, $missingFilters);
        }
        if ($joins !== [] || $filters !== []) {
            return $this->ignoredIndexFinding($scan, array_values(array_unique(array_merge($joins, $filters))));
        }

        return FindingFormatter::finding(
            ($rows !== null && $rows >= SqlExplainAdvisor::LARGE_TABLE * 10 ? SqlExplainAdvisor::WARNING : SqlExplainAdvisor::INFO),
            $scan['title'],
            'Aucun filtre (WHERE) ni jointure indexable ne porte sur cette table : toutes ses lignes sont '
            . 'nécessaires au résultat, le parcours complet est donc attendu. Pour lire moins de lignes, '
            . 'filtrez (WHERE) ou paginez (LIMIT) si tout le résultat n\'est pas utile.',
            null,
            $table
        );
    }

    /**
     * Constat d'un index manquant sur des colonnes de jointure ou de filtre
     *
     * @param array{access: array<string, mixed>, table: string, alias: string, rows: int|null, title: string} $scan Parcours
     * @param array<int, string> $missingJoins Colonnes de jointure sans index
     * @param array<int, string> $missingFilters Colonnes filtrées sans index
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}
     */
    private function missingIndexFinding(array $scan, array $missingJoins, array $missingFilters): array
    {
        $suggestions = $this->suggestions($scan, $missingJoins, $missingFilters);
        $details = $this->missingDetails($scan, $missingJoins, $missingFilters);
        if ($this->indexes->isWiderThan($suggestions, count($missingJoins) + count($missingFilters)) === true) {
            $details[] = 'L\'index proposé ajoute les colonnes du tri (ORDER BY) : un seul index règle les deux points.';
        }

        return FindingFormatter::finding(
            self::missingLevel($scan['access'], $scan['rows']),
            $scan['title'],
            implode(' ', $details),
            IndexSuggester::combine($suggestions),
            $scan['table']
        );
    }

    /**
     * Explications d'un index manquant
     *
     * @param array{access: array<string, mixed>, table: string, alias: string, rows: int|null, title: string} $scan Parcours
     * @param array<int, string> $missingJoins Colonnes de jointure sans index
     * @param array<int, string> $missingFilters Colonnes filtrées sans index
     * @return array<int, string>
     */
    private function missingDetails(array $scan, array $missingJoins, array $missingFilters): array
    {
        $details = [];
        if ($missingJoins !== []) {
            $details[] = 'La jointure sur ' . $this->columnsLabel($scan['alias'], $missingJoins) . ' n\'utilise pas d\'index : '
                . 'pour chaque ligne de la table précédente, le moteur doit relire toute la table ' . $scan['table']
                . ($scan['access']['join_buffer'] === true ? ' (tampon de jointure, « Using join buffer »)' : '') . '.';
        }
        if ($missingFilters !== []) {
            $details[] = 'Le filtre WHERE sur ' . $this->columnsLabel($scan['alias'], $missingFilters) . ' ne peut pas utiliser '
                . 'd\'index : toutes les lignes sont lues puis filtrées une par une.';
        }
        $details[] = 'Un index sur ' . (count($missingJoins) + count($missingFilters) > 1 ? 'ces colonnes' : 'cette colonne')
            . ' permet d\'aller directement aux lignes utiles.'
            . ($scan['rows'] !== null && $scan['rows'] < SqlExplainAdvisor::SMALL_TABLE
                ? ' La table est encore petite : l\'impact sera visible quand le volume augmentera.' : '');

        return $details;
    }

    /**
     * Index proposés : un index composite pour plusieurs égalités du WHERE sur la table,
     * un index par colonne sinon
     *
     * @param array{access: array<string, mixed>, table: string, alias: string, rows: int|null, title: string} $scan Parcours
     * @param array<int, string> $missingJoins Colonnes de jointure sans index
     * @param array<int, string> $missingFilters Colonnes filtrées sans index
     * @return array<int, string|null>
     */
    private function suggestions(array $scan, array $missingJoins, array $missingFilters): array
    {
        $suggestions = [];
        $equalities = array_values(array_intersect(
            $missingFilters,
            $this->context->filterColumnsOf($scan['alias'], $scan['table'], true)
        ));
        if (count($equalities) > 1) {
            $suggestions[] = $this->indexes->createIndex($scan['table'], $equalities);
            $missingFilters = array_values(array_diff($missingFilters, $equalities));
        }
        foreach (array_unique(array_merge($missingJoins, $missingFilters)) as $column) {
            $suggestions[] = $this->indexes->createIndex($scan['table'], [$column]);
        }

        return $suggestions;
    }

    /**
     * Constat d'un index existant mais ignoré par le moteur
     *
     * @param array{access: array<string, mixed>, table: string, alias: string, rows: int|null, title: string} $scan Parcours
     * @param array<int, string> $columns Colonnes indexées utilisées pour rechercher la table
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}
     */
    private function ignoredIndexFinding(array $scan, array $columns): array
    {
        $possibleKeys = (string) ($scan['access']['possible_keys'] ?? '');

        return FindingFormatter::finding(
            ($scan['rows'] !== null && $scan['rows'] >= SqlExplainAdvisor::LARGE_TABLE ? SqlExplainAdvisor::WARNING : SqlExplainAdvisor::INFO),
            $scan['title'],
            'Un index existe sur ' . $this->columnsLabel($scan['alias'], $columns) . ' mais le moteur a préféré lire toute '
            . 'la table' . ($possibleKeys !== '' ? ' (index possibles : ' . $possibleKeys . ')' : '') . '. Causes fréquentes : la table '
            . 'est petite, la condition correspond à une grande partie des lignes (index peu sélectif), '
            . 'ou la condition empêche l\'usage de l\'index (fonction sur la colonne, LIKE \'%…\', '
            . 'comparaison entre types différents).',
            null,
            $scan['table']
        );
    }

    /**
     * Niveau d'un index manquant : critique sur une grande table (ou une jointure en
     * tampon sur une table moyenne), à améliorer sur une table moyenne, à savoir sinon
     *
     * @param array<string, mixed> $access Accès normalisé
     * @param int|null $rows Nombre de lignes
     * @return string
     */
    private static function missingLevel(array $access, ?int $rows): string
    {
        if ($rows === null || $rows >= SqlExplainAdvisor::LARGE_TABLE
            || ($access['join_buffer'] === true && $rows >= SqlExplainAdvisor::SMALL_TABLE)) {
            return SqlExplainAdvisor::CRITICAL;
        }

        return ($rows >= SqlExplainAdvisor::SMALL_TABLE ? SqlExplainAdvisor::WARNING : SqlExplainAdvisor::INFO);
    }

    /**
     * Retourne les colonnes qui ne commencent aucun index de la table
     *
     * @param string $table Nom de la table
     * @param array<int, string> $columns Colonnes
     * @return array<int, string>
     */
    private function unindexed(string $table, array $columns): array
    {
        return array_values(array_filter($columns, fn (string $column): bool => $this->schema->isIndexed($table, $column) === false));
    }

    /**
     * Libellé de colonnes de la table, préfixées par son alias si la requête a plusieurs tables
     *
     * @param string $alias Alias de la table (minuscules)
     * @param array<int, string> $columns Colonnes
     * @return string
     */
    private function columnsLabel(string $alias, array $columns): string
    {
        return FindingFormatter::columnsLabel($alias, $columns, count($this->context->getInspector()->tableNames()) === 1);
    }
}

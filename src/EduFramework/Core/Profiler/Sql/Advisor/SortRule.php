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
 * Class SortRule
 * Constats de tri sans index (Using filesort / USE TEMP B-TREE FOR ORDER BY) et
 * de table temporaire (Using temporary / USE TEMP B-TREE FOR GROUP BY ou DISTINCT),
 * avec l'index qui permet de les éviter quand le tri ou le regroupement porte sur
 * des colonnes de la table lue en premier.
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql\Advisor
 */
class SortRule
{
    /**
     * SortRule constructor.
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
     * Constat de tri sans index, null si le plan ne trie pas
     *
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}|null
     */
    public function sortFinding(): ?array
    {
        $access = $this->context->firstAccessWith('filesort');
        if ($access === null) {
            return null;
        }

        $ordering = $this->context->getOrdering();
        $computed = $ordering->computedOrderBy();
        if ($ordering->orderByItems() === [] || $computed !== []) {
            return $this->computedSortFinding($computed);
        }

        [$table, $alias] = $this->context->tableOf($access);
        $rows = ($table === null ? null : $this->context->rowsOf($access, $table));
        $where = ($table === null ? '' : ' sur ' . FindingFormatter::tableLabel($table, $alias) . FindingFormatter::rowsLabel($rows));

        return FindingFormatter::finding(
            ($rows !== null && $rows >= SqlExplainAdvisor::LARGE_TABLE ? SqlExplainAdvisor::WARNING : SqlExplainAdvisor::INFO),
            'Tri sans index (ORDER BY)' . $where,
            'Le résultat est trié après avoir été lu (« Using filesort »). Un index dont les colonnes suivent '
            . 'l\'ordre du ORDER BY permet de lire les lignes directement dans le bon ordre, sans tri. '
            . 'Si la requête filtre aussi par égalité (WHERE col = …), placez ces colonnes en premier dans l\'index.',
            ($table === null ? null : $this->sortSuggestion($table, $alias)),
            $table
        );
    }

    /**
     * Constat de table temporaire, null si le plan n'en utilise pas
     *
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}|null
     */
    public function temporaryFinding(): ?array
    {
        $access = $this->context->firstAccessWith('temporary');
        if ($access === null) {
            return null;
        }

        [$table] = $this->context->tableOf($access);
        $rows = ($table === null ? null : $this->context->rowsOf($access, $table));
        $reason = $this->temporaryReason($table);

        return FindingFormatter::finding(
            ($rows !== null && $rows >= SqlExplainAdvisor::LARGE_TABLE && $reason === '' ? SqlExplainAdvisor::WARNING : SqlExplainAdvisor::INFO),
            'Table temporaire (GROUP BY, DISTINCT ou UNION)',
            'Le moteur stocke un résultat intermédiaire pour regrouper ou dédoublonner les lignes (« Using temporary »). '
            . 'Quand le GROUP BY porte sur les colonnes de la table lue en premier, un index sur ces colonnes '
            . 'permet de regrouper les lignes dans l\'ordre de l\'index, sans table temporaire.' . $reason,
            ($reason === '' ? $this->groupSuggestion($table) : null),
            $table
        );
    }

    /**
     * Constat d'un tri sur une valeur calculée : aucun index ne peut l'éviter
     *
     * @param array<int, string> $computed Valeurs calculées du ORDER BY
     * @return array{level: string, title: string, detail: string, suggestion: string|null, table: string|null}
     */
    private function computedSortFinding(array $computed): array
    {
        $limit = ($this->context->getInspector()->hasLimit() === true
            ? 'Avec la LIMIT, seules les premières lignes sont conservées pendant le tri : le coût reste raisonnable.'
            : 'Vérifiez que tout le résultat est utile : avec une LIMIT, le tri ne conserve que les premières lignes.');

        return FindingFormatter::finding(
            SqlExplainAdvisor::INFO,
            'Tri sans index (ORDER BY)',
            'Le tri porte sur une valeur calculée' . ($computed === [] ? '' : ' (' . implode(', ', $computed) . ')')
            . ' : aucun index ne peut l\'éviter, le moteur trie le résultat en mémoire (« Using filesort »). ' . $limit
        );
    }

    /**
     * Index qui évite le tri : colonnes filtrées par égalité (ou jointes à une table
     * réduite à une ligne), puis colonnes du ORDER BY. Null si les colonnes du tri ne
     * sont pas toutes des colonnes de la table ou si un tel index existe déjà
     *
     * @param string $table Nom de la table triée
     * @param string $alias Alias de la table (minuscules)
     * @return string|null
     */
    private function sortSuggestion(string $table, string $alias): ?string
    {
        $columns = $this->context->itemsColumns($this->context->getOrdering()->orderByItems(), $table);
        if ($columns === null || $this->schema->hasIndexStartingWith($table, $columns) === true) {
            return null;
        }

        $equalities = array_values(array_diff(array_unique(array_merge(
            $this->context->filterColumnsOf($alias, $table, true),
            $this->context->joinColumnsOf($alias, $table, true)
        )), $columns));

        return $this->indexes->createIndex($table, array_merge($equalities, $columns));
    }

    /**
     * Raison pour laquelle la table temporaire est difficile à éviter (vide sinon) :
     * tri sur une valeur calculée, ou regroupement sur une autre table que celle lue en premier
     *
     * @param string|null $table Table lue en premier
     * @return string
     */
    private function temporaryReason(?string $table): string
    {
        $ordering = $this->context->getOrdering();
        $groupItems = $ordering->groupByItems();
        $computed = $ordering->computedOrderBy();
        if ($groupItems === []) {
            return '';
        }
        if ($computed !== []) {
            return ' Ici, le tri porte sur une valeur calculée (' . implode(', ', $computed) . ') : le résultat '
                . 'regroupé doit être stocké avant d\'être trié, la table temporaire est attendue.';
        }
        if ($table === null || $this->context->itemsColumns($groupItems, $table) === null) {
            return ' Ici, le regroupement ne porte pas uniquement sur des colonnes de la table lue en premier : '
                . 'la table temporaire est difficile à éviter, réduisez plutôt le nombre de lignes à regrouper (WHERE).';
        }

        return '';
    }

    /**
     * Index sur les colonnes du GROUP BY de la table lue en premier, null si un index
     * les couvre déjà (ou si une clé unique est regroupée)
     *
     * @param string|null $table Table lue en premier
     * @return string|null
     */
    private function groupSuggestion(?string $table): ?string
    {
        $columns = ($table === null ? null : $this->context->itemsColumns($this->context->getOrdering()->groupByItems(), $table));
        if ($table === null || $columns === null || $this->schema->hasIndexStartingWith($table, $columns) === true
            || $this->schema->hasUniqueIndexWithin($table, $columns) === true) {
            return null;
        }

        return $this->indexes->createIndex($table, $columns);
    }
}

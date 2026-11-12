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

use Studoo\EduFramework\Core\Profiler\Sql\SqlOrderReader;
use Studoo\EduFramework\Core\Profiler\Sql\SqlQueryInspector;

/**
 * Class PlanContext
 * Relie le plan d'exécution normalisé au texte de la requête et à la structure
 * des tables : table de chaque accès, ordre de lecture des tables, colonnes
 * utilisées pour rechercher une table (jointures, filtres WHERE), colonnes du
 * ORDER BY / GROUP BY qui appartiennent à une table.
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql\Advisor
 */
class PlanContext
{
    /**
     * Tables de la requête (alias en minuscules => nom de la table)
     * @var array<string, string>
     */
    private array $aliases;

    /**
     * Position de chaque alias dans le plan (ordre de lecture des tables)
     * @var array<string, int>
     */
    private array $positions = [];

    /**
     * Conditions de jointure de la requête
     * @var array<int, array{left_alias: string, left_column: string, right_alias: string, right_column: string}>
     */
    private array $joins;

    /**
     * Colonnes filtrées dans le WHERE
     * @var array<int, array{alias: string|null, column: string, operator: string}>
     */
    private array $filters;

    /**
     * Colonnes qu'un index ne peut pas servir (fonction, LIKE '%…'), en minuscules
     * @var array<int, string>
     */
    private array $unindexable;

    /**
     * PlanContext constructor.
     *
     * @param SqlQueryInspector $inspector Lecture du texte de la requête
     * @param array<int, array<string, mixed>> $accesses Accès aux tables (SqlExplainer::accesses())
     * @param TableSchema $schema Structure des tables
     */
    public function __construct(
        private SqlQueryInspector $inspector,
        private array $accesses,
        private TableSchema $schema
    ) {
        $this->aliases = $inspector->tables();
        foreach (array_values($accesses) as $position => $access) {
            $this->positions[strtolower((string) ($access['alias'] ?? ''))] ??= $position;
        }
        $conditions = $inspector->getConditions();
        $this->joins = $conditions->joinConditions();
        $this->filters = $conditions->whereColumns();
        $this->unindexable = $conditions->unindexableColumns();
    }

    /**
     * Retourne la lecture du texte de la requête
     *
     * @return SqlQueryInspector
     */
    public function getInspector(): SqlQueryInspector
    {
        return $this->inspector;
    }

    /**
     * Retourne la lecture des tris et regroupements de la requête
     *
     * @return SqlOrderReader
     */
    public function getOrdering(): SqlOrderReader
    {
        return $this->inspector->getOrdering();
    }

    /**
     * Retourne la table et l'alias (minuscules) d'un accès, table null si elle est inconnue
     * (Exemple : table dérivée <derived2>)
     *
     * @param array<string, mixed> $access Accès normalisé
     * @return array{0: string|null, 1: string}
     */
    public function tableOf(array $access): array
    {
        $alias = strtolower((string) ($access['alias'] ?? ''));

        return [$this->aliases[$alias] ?? $this->schema->nameOf($alias), $alias];
    }

    /**
     * Retourne le nombre de lignes lues par un accès : estimé par le plan, sinon mesuré
     * (ANALYZE), sinon celui de la table. L'estimation passe en premier : la mesure de
     * MariaDB (r_rows) compte les lignes conservées après le filtre poussé dans le
     * parcours, pas les lignes lues (Exemple : 6 au lieu de 6 000 pour un parcours complet)
     *
     * @param array<string, mixed> $access Accès normalisé
     * @param string $table Nom de la table
     * @return int|null
     */
    public function rowsOf(array $access, string $table): ?int
    {
        $rows = $access['rows'] ?? $access['actual_rows'] ?? null;

        return (is_numeric($rows) === true ? (int) round((float) $rows) : $this->schema->rowsOf($table));
    }

    /**
     * Retourne le premier accès du plan qui porte l'indicateur donné (filesort, temporary).
     * MySQL peut rattacher l'indicateur à une table réduite à une ligne (const) : c'est
     * alors la table suivante, réellement parcourue, qui est triée ou regroupée
     *
     * @param string $flag Indicateur
     * @return array<string, mixed>|null
     */
    public function firstAccessWith(string $flag): ?array
    {
        $flagged = array_keys(array_filter($this->accesses, static fn (array $access): bool => ($access[$flag] ?? false) === true));
        if ($flagged === []) {
            return null;
        }

        $following = array_slice($this->accesses, (int) $flagged[0]);
        foreach ($following as $access) {
            if ($access['access'] !== 'const') {
                return $access;
            }
        }

        return $following[0];
    }

    /**
     * Retourne les colonnes de la table utilisées pour la rechercher dans une jointure
     * (a.x = b.y) : seules comptent les jointures vers une table lue avant elle dans le
     * plan (la table lue en premier n'est pas recherchée, elle est parcourue)
     *
     * @param string $alias Alias de la table (minuscules)
     * @param string $table Nom de la table
     * @param bool $constOnly Ne retourner que les jointures vers une table réduite à une ligne (const) :
     *        elles équivalent à une égalité avec une valeur
     * @return array<int, string>
     */
    public function joinColumnsOf(string $alias, string $table, bool $constOnly = false): array
    {
        $columns = [];
        foreach ($this->joins as $join) {
            foreach ([['left', 'right'], ['right', 'left']] as [$side, $other]) {
                if ($join[$side . '_alias'] === $alias && $this->isJoinedFrom($join[$other . '_alias'], $alias, $constOnly)) {
                    $columns[] = $join[$side . '_column'];
                }
            }
        }

        return $this->schema->existingColumns($table, $columns);
    }

    /**
     * Retourne les colonnes de la table filtrées dans le WHERE (hors fonctions et LIKE '%…')
     *
     * @param string $alias Alias de la table (minuscules)
     * @param string $table Nom de la table
     * @param bool $equalityOnly Ne retourner que les colonnes comparées par égalité (= ou IN)
     * @return array<int, string>
     */
    public function filterColumnsOf(string $alias, string $table, bool $equalityOnly = false): array
    {
        $columns = [];
        foreach ($this->filters as $filter) {
            $operatorMatches = ($equalityOnly === false || in_array($filter['operator'], ['=', 'IN', '<=>'], true));
            if ($operatorMatches === true && $this->isFilterOn($filter, $alias, $table) === true
                && in_array(strtolower($filter['column']), $this->unindexable, true) === false) {
                $columns[] = $filter['column'];
            }
        }

        return $this->schema->existingColumns($table, $columns);
    }

    /**
     * Retourne les colonnes d'éléments ORDER BY / GROUP BY si ce sont toutes des colonnes
     * de la table donnée (null sinon : expression, autre table, colonne inconnue)
     *
     * @param array<int, array{alias: string|null, column: string|null, expression: string, direction: string}> $items Éléments
     * @param string $table Nom de la table
     * @return array<int, string>|null
     */
    public function itemsColumns(array $items, string $table): ?array
    {
        $columns = [];
        foreach ($items as $item) {
            $owner = $this->ownerOfItem($item);
            if ($owner === null || strtolower($owner) !== strtolower($table)
                || $this->schema->hasColumn($table, (string) $item['column']) === false) {
                return null;
            }
            $columns[] = (string) $item['column'];
        }

        return ($columns === [] ? null : array_values(array_unique($columns)));
    }

    /**
     * Indique si la table d'alias donné est réduite à une seule ligne dans le plan (accès const)
     *
     * @param string $alias Alias de la table (minuscules)
     * @return bool
     */
    public function isConst(string $alias): bool
    {
        $position = $this->positions[$alias] ?? null;

        return $position !== null && ($this->accesses[$position]['access'] ?? null) === 'const';
    }

    /**
     * Indique si une jointure vers l'alias $other sert à rechercher la table $alias :
     * $other est lue avant elle dans le plan (ou l'ordre est inconnu)
     *
     * @param string $other Alias de l'autre table de la jointure
     * @param string $alias Alias de la table recherchée
     * @param bool $constOnly L'autre table doit être réduite à une ligne (const)
     * @return bool
     */
    private function isJoinedFrom(string $other, string $alias, bool $constOnly): bool
    {
        $readBefore = (($this->positions[$other] ?? -1) < ($this->positions[$alias] ?? PHP_INT_MAX)
            || isset($this->positions[$other], $this->positions[$alias]) === false);

        return $other !== $alias && $readBefore === true && ($constOnly === false || $this->isConst($other) === true);
    }

    /**
     * Indique si un filtre du WHERE porte sur la table : même alias, ou colonne non
     * préfixée qui appartient à cette table
     *
     * @param array{alias: string|null, column: string, operator: string} $filter Filtre
     * @param string $alias Alias de la table (minuscules)
     * @param string $table Nom de la table
     * @return bool
     */
    private function isFilterOn(array $filter, string $alias, string $table): bool
    {
        if ($filter['alias'] !== null) {
            return $filter['alias'] === $alias;
        }

        return strtolower((string) $this->ownerOf($filter['column'])) === strtolower($table);
    }

    /**
     * Retourne la table d'un élément ORDER BY / GROUP BY, null si c'est une valeur calculée
     *
     * @param array{alias: string|null, column: string|null, expression: string, direction: string} $item Élément
     * @return string|null
     */
    private function ownerOfItem(array $item): ?string
    {
        if ($item['column'] === null) {
            return null;
        }

        return ($item['alias'] === null ? $this->ownerOf($item['column']) : ($this->aliases[$item['alias']] ?? null));
    }

    /**
     * Retourne la table qui possède une colonne non préfixée : la seule table de la
     * requête, ou la seule table qui a cette colonne
     *
     * @param string $column Nom de la colonne
     * @return string|null
     */
    private function ownerOf(string $column): ?string
    {
        $names = $this->inspector->tableNames();
        if (count($names) === 1) {
            return $names[0];
        }

        $owners = array_values(array_filter($names, fn (string $name): bool => $this->schema->hasColumn($name, $column, false)));

        return (count($owners) === 1 ? $owners[0] : null);
    }
}

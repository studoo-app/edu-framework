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

/**
 * Class TableSchema
 * Structure des tables d'une requête (SqlTableInspector::tables()) : nombre de
 * lignes, colonnes et index existants, pour savoir si une colonne est déjà indexée.
 * Une table dont la structure est inconnue est considérée comme ayant toutes les
 * colonnes demandées et aucun index.
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql\Advisor
 */
class TableSchema
{
    /**
     * TableSchema constructor.
     *
     * @param array<string, array{name: string, rows: int|null, columns: array<int, string>,
     *        indexes: array<int, array{name: string, columns: array<int, string>, unique: bool, primary: bool}>}> $tables
     *        Structure des tables, indexées par nom en minuscules
     */
    public function __construct(private array $tables)
    {
    }

    /**
     * Retourne le nom d'une table à partir de son nom en minuscules, null si elle est inconnue
     *
     * @param string $name Nom de la table (minuscules)
     * @return string|null
     */
    public function nameOf(string $name): ?string
    {
        return $this->tables[$name]['name'] ?? null;
    }

    /**
     * Retourne le nombre de lignes de la table, null s'il est inconnu
     *
     * @param string $table Nom de la table
     * @return int|null
     */
    public function rowsOf(string $table): ?int
    {
        return $this->tables[strtolower($table)]['rows'] ?? null;
    }

    /**
     * Indique si une colonne est la première colonne d'un index de la table
     *
     * @param string $table Nom de la table
     * @param string $column Nom de la colonne
     * @return bool
     */
    public function isIndexed(string $table, string $column): bool
    {
        return $this->hasIndexStartingWith($table, [$column]);
    }

    /**
     * Indique si un index de la table commence par les colonnes données, dans cet ordre
     *
     * @param string $table Nom de la table
     * @param array<int, string> $columns Colonnes
     * @return bool
     */
    public function hasIndexStartingWith(string $table, array $columns): bool
    {
        $wanted = array_map('strtolower', array_values($columns));
        foreach ($this->indexesOf($table) as $index) {
            if (array_map('strtolower', array_slice($index['columns'], 0, count($wanted))) === $wanted) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indique si un index unique (ou la clé primaire) de la table est entièrement
     * compris dans les colonnes données : chaque groupe correspond alors à une seule ligne
     *
     * @param string $table Nom de la table
     * @param array<int, string> $columns Colonnes
     * @return bool
     */
    public function hasUniqueIndexWithin(string $table, array $columns): bool
    {
        $wanted = array_map('strtolower', $columns);
        foreach ($this->indexesOf($table) as $index) {
            if ($index['unique'] === true && $index['columns'] !== []
                && array_diff(array_map('strtolower', $index['columns']), $wanted) === []) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indique si la table a la colonne donnée
     *
     * @param string $table Nom de la table
     * @param string $column Nom de la colonne
     * @param bool $whenUnknown Valeur retournée si la structure de la table est inconnue
     * @return bool
     */
    public function hasColumn(string $table, string $column, bool $whenUnknown = true): bool
    {
        $columns = $this->tables[strtolower($table)]['columns'] ?? null;
        if ($columns === null) {
            return $whenUnknown;
        }

        return in_array(strtolower($column), array_map('strtolower', $columns), true);
    }

    /**
     * Retourne les colonnes qui existent dans la table, sans doublon, avec la casse de la table
     * (toutes les colonnes données si la structure de la table est inconnue)
     *
     * @param string $table Nom de la table
     * @param array<int, string> $columns Colonnes
     * @return array<int, string>
     */
    public function existingColumns(string $table, array $columns): array
    {
        $known = $this->tables[strtolower($table)]['columns'] ?? $columns;
        $byName = array_combine(array_map('strtolower', $known), $known);
        $result = [];
        foreach ($columns as $column) {
            if (isset($byName[strtolower($column)]) === true) {
                $result[strtolower($column)] = $byName[strtolower($column)];
            }
        }

        return array_values($result);
    }

    /**
     * Retourne les index de la table (aucun si la structure de la table est inconnue)
     *
     * @param string $table Nom de la table
     * @return array<int, array{name: string, columns: array<int, string>, unique: bool, primary: bool}>
     */
    private function indexesOf(string $table): array
    {
        return $this->tables[strtolower($table)]['indexes'] ?? [];
    }
}

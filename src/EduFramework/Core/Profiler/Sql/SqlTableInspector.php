<?php

/*
 * Edu Framework by studoo labs
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Profiler\Sql;

use PDO;

/**
 * Class SqlTableInspector
 * Lit la structure des tables d'une requête sur la base de données de
 * l'application, pour l'analyse EXPLAIN du profiler : nombre de lignes
 * (estimé pour MySQL / MariaDB), colonnes et index (nom, colonnes dans
 * l'ordre, unique, clé primaire).
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql
 */
class SqlTableInspector
{
    /**
     * SqlTableInspector constructor.
     *
     * @param PDO $pdo Connexion à la base de données de l'application
     */
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Retourne la structure des tables, indexées par nom en minuscules.
     * Les tables introuvables (alias de sous-requête, CTE, ...) et les noms
     * qui ne sont pas des identifiants valides sont ignorés
     *
     * @param array<int, string> $tableNames Noms des tables
     * @return array<string, array{name: string, rows: int|null, columns: array<int, string>,
     *         indexes: array<int, array{name: string, columns: array<int, string>, unique: bool, primary: bool}>}>
     */
    public function tables(array $tableNames): array
    {
        $sqlite = ((string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
        $tables = [];
        foreach ($tableNames as $name) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_$]*$/', $name) !== 1) {
                continue;
            }
            try {
                $tables[strtolower($name)] = ($sqlite === true ? $this->sqliteTable($name) : $this->mysqlTable($name));
            } catch (\Throwable) {
                // Table introuvable ou illisible : elle n'apparaît pas dans la structure
                continue;
            }
        }

        return array_filter($tables, static fn (?array $table): bool => $table !== null);
    }

    /**
     * Structure d'une table MySQL / MariaDB (SHOW COLUMNS, SHOW INDEX, information_schema)
     *
     * @param string $name Nom de la table (identifiant validé)
     * @return array{name: string, rows: int|null, columns: array<int, string>,
     *         indexes: array<int, array{name: string, columns: array<int, string>, unique: bool, primary: bool}>}
     */
    private function mysqlTable(string $name): array
    {
        $columns = $this->rows('SHOW COLUMNS FROM `' . $name . '`');

        $indexes = [];
        foreach ($this->rows('SHOW INDEX FROM `' . $name . '`') as $row) {
            $key = (string) $row['Key_name'];
            $indexes[$key] ??= ['name' => $key, 'columns' => [], 'unique' => ((int) $row['Non_unique'] === 0), 'primary' => ($key === 'PRIMARY')];
            $indexes[$key]['columns'][(int) $row['Seq_in_index']] = (string) ($row['Column_name'] ?? $row['Expression'] ?? '');
        }
        foreach ($indexes as $key => $index) {
            ksort($index['columns']);
            $indexes[$key]['columns'] = array_values($index['columns']);
        }

        $stmt = $this->pdo->prepare(
            'SELECT TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :name'
        );
        $stmt->execute([':name' => $name]);
        $rows = $stmt->fetchColumn();

        return [
            'name' => $name,
            'rows' => (is_numeric($rows) === true ? (int) $rows : null),
            'columns' => array_map('strval', array_column($columns, 'Field')),
            'indexes' => array_values($indexes),
        ];
    }

    /**
     * Structure d'une table SQLite (PRAGMA table_info, index_list, index_info, COUNT(*)),
     * null si la table n'existe pas
     *
     * @param string $name Nom de la table (identifiant validé)
     * @return array{name: string, rows: int|null, columns: array<int, string>,
     *         indexes: array<int, array{name: string, columns: array<int, string>, unique: bool, primary: bool}>}|null
     */
    private function sqliteTable(string $name): ?array
    {
        $columns = $this->rows('PRAGMA table_info("' . $name . '")');
        if ($columns === []) {
            return null;
        }

        // Clé primaire INTEGER PRIMARY KEY : alias du rowid, sans index déclaré dans index_list
        $indexes = [];
        $primary = array_values(array_filter($columns, static fn (array $column): bool => (int) $column['pk'] > 0));
        if (count($primary) === 1 && strtoupper((string) $primary[0]['type']) === 'INTEGER') {
            $indexes[] = ['name' => 'PRIMARY', 'columns' => [(string) $primary[0]['name']], 'unique' => true, 'primary' => true];
        }

        foreach ($this->rows('PRAGMA index_list("' . $name . '")') as $index) {
            $indexColumns = $this->rows('PRAGMA index_info("' . str_replace('"', '""', (string) $index['name']) . '")');
            usort($indexColumns, static fn (array $left, array $right): int => (int) $left['seqno'] <=> (int) $right['seqno']);
            $indexes[] = [
                'name' => (string) $index['name'],
                'columns' => array_map('strval', array_column($indexColumns, 'name')),
                'unique' => ((int) $index['unique'] === 1),
                'primary' => (($index['origin'] ?? '') === 'pk'),
            ];
        }

        return [
            'name' => $name,
            'rows' => (int) ($this->rows('SELECT COUNT(*) AS total FROM "' . $name . '"')[0]['total'] ?? 0),
            'columns' => array_map('strval', array_column($columns, 'name')),
            'indexes' => $indexes,
        ];
    }

    /**
     * Exécute une requête de lecture de la structure et retourne ses lignes
     *
     * @param string $sql Requête SQL (identifiants validés)
     * @return array<int, array<string, mixed>>
     */
    private function rows(string $sql): array
    {
        $result = $this->pdo->query($sql);

        return ($result === false ? [] : $result->fetchAll(PDO::FETCH_ASSOC));
    }
}

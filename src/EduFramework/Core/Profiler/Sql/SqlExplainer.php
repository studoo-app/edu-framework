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

use PDO;
use PDOStatement;

/**
 * Class SqlExplainer
 * Exécute le plan d'exécution d'une requête SQL sur la base de données de
 * l'application, pour l'analyse EXPLAIN du profiler :
 * - MySQL / MariaDB : EXPLAIN (plan estimé, sans exécuter la requête) ;
 *   EXPLAIN ANALYZE (MySQL 8) ou ANALYZE (MariaDB) exécutent réellement
 *   la requête et ajoutent les mesures réelles ;
 * - SQLite : EXPLAIN QUERY PLAN ; le mode ANALYZE exécute la requête et mesure
 *   son temps d'exécution (SQLite ne fournit pas de plan mesuré).
 *
 * Le plan est aussi normalisé en une liste d'accès aux tables (parcours
 * complet, recherche par index, tri, table temporaire, ...) commune aux
 * moteurs, utilisée par SqlExplainAdvisor pour proposer des axes d'amélioration.
 * Le mode ANALYZE est réservé aux requêtes de lecture (SELECT) et s'exécute
 * dans une transaction annulée.
 *
 * @package Studoo\EduFramework\Core\Profiler\Sql
 */
class SqlExplainer
{
    public const string DRIVER_MYSQL = 'mysql';
    public const string DRIVER_MARIADB = 'mariadb';
    public const string DRIVER_SQLITE = 'sqlite';

    /**
     * Types d'accès MySQL / MariaDB (colonne "type" de EXPLAIN) => accès normalisé
     */
    private const array MYSQL_ACCESS = [
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
     * SqlExplainer constructor.
     *
     * @param PDO $pdo Connexion à la base de données de l'application
     */
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Retourne le moteur de base de données : mysql, mariadb, sqlite (ou le nom du pilote PDO)
     *
     * @return string
     */
    public function driver(): string
    {
        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql' && stripos($this->serverVersion(), 'mariadb') !== false) {
            return self::DRIVER_MARIADB;
        }

        return $driver;
    }

    /**
     * Retourne la version du serveur de base de données (Exemple : 11.4.2-MariaDB)
     *
     * @return string
     */
    public function serverVersion(): string
    {
        try {
            return (string) $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Indique si le moteur est pris en charge par l'analyse EXPLAIN
     *
     * @return bool
     */
    public function isSupported(): bool
    {
        return in_array($this->driver(), [self::DRIVER_MYSQL, self::DRIVER_MARIADB, self::DRIVER_SQLITE], true);
    }

    /**
     * Retourne le plan d'exécution estimé de la requête (la requête n'est pas exécutée)
     *
     * @param string $sql Requête SQL
     * @param array<int, array{name: int|string, value: mixed, type: int}> $params Paramètres de la requête
     * @return array{statement: string, columns: array<int, string>, rows: array<int, array<string, mixed>>}
     * @throws \PDOException Si la requête ne peut pas être expliquée
     */
    public function explain(string $sql, array $params): array
    {
        $prefix = ($this->driver() === self::DRIVER_SQLITE ? 'EXPLAIN QUERY PLAN ' : 'EXPLAIN ');
        $rows = $this->run($prefix . $sql, $params)->fetchAll(PDO::FETCH_ASSOC);

        return [
            'statement' => trim($prefix),
            'columns' => ($rows === [] ? [] : array_keys($rows[0])),
            'rows' => $rows,
        ];
    }

    /**
     * Exécute réellement la requête (lecture seule, dans une transaction annulée)
     * et retourne son plan mesuré : ANALYZE (MariaDB, tableau avec les colonnes r_*),
     * EXPLAIN ANALYZE (MySQL 8, arbre texte), ou le temps d'exécution et le nombre
     * de lignes pour SQLite
     *
     * @param string $sql Requête SQL (SELECT uniquement)
     * @param array<int, array{name: int|string, value: mixed, type: int}> $params Paramètres de la requête
     * @return array{statement: string, columns: array<int, string>, rows: array<int, array<string, mixed>>,
     *         tree: string|null, time_ms: float, rows_count: int|null}
     * @throws \PDOException Si la requête ne peut pas être exécutée
     */
    public function analyze(string $sql, array $params): array
    {
        $driver = $this->driver();
        $statement = match ($driver) {
            self::DRIVER_MARIADB => 'ANALYZE',
            self::DRIVER_MYSQL => 'EXPLAIN ANALYZE',
            default => '',
        };

        $transaction = false;
        try {
            $transaction = ($this->pdo->inTransaction() === false && $this->pdo->beginTransaction() === true);
        } catch (\PDOException) {
            $transaction = false;
        }

        try {
            $start = microtime(true);
            $rows = $this->run(trim($statement . ' ' . $sql), $params)->fetchAll(PDO::FETCH_ASSOC);
            $timeMs = round((microtime(true) - $start) * 1000, 1);
        } finally {
            if ($transaction === true && $this->pdo->inTransaction() === true) {
                $this->pdo->rollBack();
            }
        }

        if ($driver === self::DRIVER_SQLITE) {
            return [
                'statement' => '',
                'columns' => [],
                'rows' => [],
                'tree' => null,
                'time_ms' => $timeMs,
                'rows_count' => count($rows),
            ];
        }

        // MySQL 8 : une seule colonne contenant l'arbre du plan mesuré
        if ($driver === self::DRIVER_MYSQL) {
            $tree = ($rows === [] ? '' : (string) reset($rows[0]));
            return [
                'statement' => $statement,
                'columns' => [],
                'rows' => [],
                'tree' => $tree,
                'time_ms' => $timeMs,
                'rows_count' => null,
            ];
        }

        return [
            'statement' => $statement,
            'columns' => ($rows === [] ? [] : array_keys($rows[0])),
            'rows' => $rows,
            'tree' => null,
            'time_ms' => $timeMs,
            'rows_count' => null,
        ];
    }

    /**
     * Normalise les lignes d'un plan d'exécution en accès aux tables, communs aux moteurs :
     * - access : full_scan (parcours complet de la table), index_scan (parcours complet
     *   d'un index), range (plage d'index), index_lookup (recherche par index),
     *   const (une seule ligne), none (aucune table) ;
     * - indicateurs : filesort (tri sans index), temporary (table temporaire),
     *   join_buffer (jointure sans index), covering (index couvrant),
     *   dependent (sous-requête corrélée), auto_index (index temporaire SQLite)
     *
     * @param array<int, array<string, mixed>> $rows Lignes du plan (EXPLAIN ou ANALYZE)
     * @return array<int, array<string, mixed>>
     */
    public function accesses(array $rows): array
    {
        return ($this->driver() === self::DRIVER_SQLITE ? self::sqliteAccesses($rows) : self::mysqlAccesses($rows));
    }

    /**
     * Normalise un plan EXPLAIN MySQL / MariaDB (voir accesses())
     *
     * @param array<int, array<string, mixed>> $rows Lignes du plan
     * @return array<int, array<string, mixed>>
     */
    public static function mysqlAccesses(array $rows): array
    {
        $accesses = [];
        foreach ($rows as $index => $row) {
            $row = array_change_key_case($row, CASE_LOWER);
            $type = ($row['type'] === null ? null : (string) $row['type']);
            $extra = (string) ($row['extra'] ?? '');
            $extraItems = array_map('trim', explode(';', $extra));
            $selectType = (string) ($row['select_type'] ?? '');
            $accesses[] = [
                'step' => $index + 1,
                'alias' => ($row['table'] === null ? null : (string) $row['table']),
                'type' => $type,
                'access' => ($type === null ? 'none' : (self::MYSQL_ACCESS[$type] ?? 'other')),
                'key' => (isset($row['key']) === true ? (string) $row['key'] : null),
                'possible_keys' => (isset($row['possible_keys']) === true ? (string) $row['possible_keys'] : null),
                'rows' => (isset($row['rows']) === true && is_numeric($row['rows']) === true ? (int) $row['rows'] : null),
                'actual_rows' => (isset($row['r_rows']) === true && is_numeric($row['r_rows']) === true
                    ? (float) $row['r_rows'] : null),
                'filtered' => (isset($row['filtered']) === true && is_numeric($row['filtered']) === true
                    ? (float) $row['filtered'] : null),
                'select_type' => $selectType,
                'extra' => $extra,
                'filesort' => in_array('Using filesort', $extraItems, true),
                'temporary' => in_array('Using temporary', $extraItems, true),
                'join_buffer' => stripos($extra, 'join buffer') !== false,
                'covering' => in_array('Using index', $extraItems, true),
                'dependent' => stripos($selectType, 'DEPENDENT') !== false || stripos($selectType, 'UNCACHEABLE') !== false,
                'auto_index' => false,
                'index_columns' => [],
                'detail' => null,
            ];
        }

        return $accesses;
    }

    /**
     * Normalise un plan EXPLAIN QUERY PLAN SQLite (voir accesses()) : les étapes
     * "USE TEMP B-TREE" (tri ou regroupement sans index) sont rattachées à l'accès
     * précédent, comme MySQL rattache "Using filesort" à la première table
     *
     * @param array<int, array<string, mixed>> $rows Lignes du plan
     * @return array<int, array<string, mixed>>
     */
    public static function sqliteAccesses(array $rows): array
    {
        $accesses = [];
        $dependent = [];
        foreach ($rows as $index => $row) {
            $detail = (string) ($row['detail'] ?? '');
            $id = (int) ($row['id'] ?? 0);
            $parent = (int) ($row['parent'] ?? 0);
            $isDependent = isset($dependent[$parent]);

            if (preg_match('/^(?:CORRELATED|DEPENDENT) .*SUBQUERY/i', $detail) === 1) {
                $dependent[$id] = true;
                continue;
            }
            if (preg_match('/^USE TEMP B-TREE FOR (.+)$/i', $detail, $match) === 1) {
                $first = array_key_first($accesses);
                if ($first !== null) {
                    $flag = (stripos($match[1], 'ORDER BY') !== false ? 'filesort' : 'temporary');
                    $accesses[$first][$flag] = true;
                }
                continue;
            }
            $pattern = '/^(SCAN|SEARCH)\s+(?:TABLE\s+)?(\S+)(?:\s+AS\s+(\S+))?(?:\s+USING\s+(.*))?$/i';
            if (preg_match($pattern, $detail, $match) !== 1) {
                continue;
            }

            $using = $match[4] ?? '';
            $access = 'full_scan';
            if (strtoupper($match[1]) === 'SEARCH') {
                $access = (stripos($using, 'PRIMARY KEY') !== false && stripos($using, '=') !== false
                    && stripos($using, '>') === false && stripos($using, '<') === false)
                    ? 'const' : (preg_match('/[<>]/', $using) === 1 ? 'range' : 'index_lookup');
            } elseif ($using !== '') {
                $access = 'index_scan';
            }

            $key = null;
            if (preg_match('/INDEX\s+(\S+)/i', $using, $keyMatch) === 1) {
                $key = $keyMatch[1];
            } elseif (stripos($using, 'PRIMARY KEY') !== false) {
                $key = 'PRIMARY';
            }

            $indexColumns = [];
            if (preg_match('/\(([^)]*)\)/', $using, $columnsMatch) === 1) {
                foreach (explode(' AND ', $columnsMatch[1]) as $condition) {
                    if (preg_match('/^\s*([A-Za-z_]\w*)\s*[=<>]/', $condition, $columnMatch) === 1
                        && strtolower($columnMatch[1]) !== 'rowid') {
                        $indexColumns[] = $columnMatch[1];
                    }
                }
            }

            $isAuto = stripos($using, 'AUTOMATIC') !== false;
            $accesses[] = [
                'step' => $index + 1,
                'alias' => ($match[3] ?? '') !== '' ? $match[3] : $match[2],
                'type' => strtoupper($match[1]),
                'access' => $access,
                'key' => ($isAuto === true ? null : $key),
                'possible_keys' => null,
                'rows' => null,
                'actual_rows' => null,
                'filtered' => null,
                'select_type' => ($isDependent === true ? 'CORRELATED SUBQUERY' : ''),
                'extra' => $using,
                'filesort' => false,
                'temporary' => false,
                'join_buffer' => false,
                'covering' => stripos($using, 'COVERING INDEX') !== false,
                'dependent' => $isDependent,
                'auto_index' => $isAuto,
                'index_columns' => $indexColumns,
                'detail' => $detail,
            ];
        }

        return $accesses;
    }

    /**
     * Retourne la structure des tables : nombre de lignes (estimé pour MySQL / MariaDB),
     * colonnes et index (nom, colonnes dans l'ordre, unique, clé primaire).
     * Les tables introuvables (alias de sous-requête, CTE, ...) sont ignorées
     *
     * @param array<int, string> $tableNames Noms des tables
     * @return array<string, array{name: string, rows: int|null, columns: array<int, string>,
     *         indexes: array<int, array{name: string, columns: array<int, string>, unique: bool, primary: bool}>}>
     */
    public function tables(array $tableNames): array
    {
        $tables = [];
        foreach ($tableNames as $name) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_$]*$/', $name) !== 1) {
                continue;
            }
            try {
                $info = ($this->driver() === self::DRIVER_SQLITE ? $this->sqliteTable($name) : $this->mysqlTable($name));
            } catch (\Throwable) {
                $info = null;
            }
            if ($info !== null) {
                $tables[strtolower($name)] = $info;
            }
        }

        return $tables;
    }

    /**
     * Structure d'une table MySQL / MariaDB (SHOW COLUMNS, SHOW INDEX, information_schema)
     *
     * @param string $name Nom de la table (identifiant validé)
     * @return array{name: string, rows: int|null, columns: array<int, string>,
     *         indexes: array<int, array{name: string, columns: array<int, string>, unique: bool, primary: bool}>}|null
     */
    private function mysqlTable(string $name): ?array
    {
        $columns = $this->pdo->query('SHOW COLUMNS FROM `' . $name . '`');
        if ($columns === false) {
            return null;
        }
        $columnNames = array_map('strval', array_column($columns->fetchAll(PDO::FETCH_ASSOC), 'Field'));

        $indexes = [];
        $result = $this->pdo->query('SHOW INDEX FROM `' . $name . '`');
        foreach ($result === false ? [] : $result->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = (string) $row['Key_name'];
            $indexes[$key] ??= [
                'name' => $key,
                'columns' => [],
                'unique' => ((int) $row['Non_unique'] === 0),
                'primary' => ($key === 'PRIMARY'),
            ];
            $indexes[$key]['columns'][(int) $row['Seq_in_index']] = (string) ($row['Column_name'] ?? $row['Expression'] ?? '');
        }
        foreach ($indexes as $key => $index) {
            ksort($index['columns']);
            $indexes[$key]['columns'] = array_values($index['columns']);
        }

        $rows = null;
        $stmt = $this->pdo->prepare(
            'SELECT TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :name'
        );
        $stmt->bindValue(':name', $name);
        if ($stmt->execute() === true) {
            $value = $stmt->fetchColumn();
            $rows = (is_numeric($value) === true ? (int) $value : null);
        }

        return ['name' => $name, 'rows' => $rows, 'columns' => $columnNames, 'indexes' => array_values($indexes)];
    }

    /**
     * Structure d'une table SQLite (PRAGMA table_info, index_list, index_info, COUNT(*))
     *
     * @param string $name Nom de la table (identifiant validé)
     * @return array{name: string, rows: int|null, columns: array<int, string>,
     *         indexes: array<int, array{name: string, columns: array<int, string>, unique: bool, primary: bool}>}|null
     */
    private function sqliteTable(string $name): ?array
    {
        $result = $this->pdo->query('PRAGMA table_info("' . $name . '")');
        $columns = ($result === false ? [] : $result->fetchAll(PDO::FETCH_ASSOC));
        if ($columns === []) {
            return null;
        }

        $indexes = [];
        // Clé primaire INTEGER PRIMARY KEY : alias du rowid, sans index déclaré dans index_list
        $primary = array_values(array_filter($columns, static fn (array $column): bool => (int) $column['pk'] > 0));
        if (count($primary) === 1 && strtoupper((string) $primary[0]['type']) === 'INTEGER') {
            $indexes[] = ['name' => 'PRIMARY', 'columns' => [(string) $primary[0]['name']], 'unique' => true, 'primary' => true];
        }

        $list = $this->pdo->query('PRAGMA index_list("' . $name . '")');
        foreach ($list === false ? [] : $list->fetchAll(PDO::FETCH_ASSOC) as $index) {
            $indexName = (string) $index['name'];
            $info = $this->pdo->query('PRAGMA index_info("' . str_replace('"', '""', $indexName) . '")');
            $indexColumns = ($info === false ? [] : $info->fetchAll(PDO::FETCH_ASSOC));
            usort($indexColumns, static fn (array $a, array $b): int => (int) $a['seqno'] <=> (int) $b['seqno']);
            $indexes[] = [
                'name' => $indexName,
                'columns' => array_map(static fn (array $column): string => (string) $column['name'], $indexColumns),
                'unique' => ((int) $index['unique'] === 1),
                'primary' => (($index['origin'] ?? '') === 'pk'),
            ];
        }

        $count = $this->pdo->query('SELECT COUNT(*) FROM "' . $name . '"');

        return [
            'name' => $name,
            'rows' => ($count === false ? null : (int) $count->fetchColumn()),
            'columns' => array_map(static fn (array $column): string => (string) $column['name'], $columns),
            'indexes' => $indexes,
        ];
    }

    /**
     * Prépare et exécute une requête avec ses paramètres
     *
     * @param string $sql Requête SQL
     * @param array<int, array{name: int|string, value: mixed, type: int}> $params Paramètres de la requête
     * @return PDOStatement
     */
    private function run(string $sql, array $params): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $param) {
            $stmt->bindValue($param['name'], $param['value'], self::pdoType($param['value'], $param['type']));
        }
        $stmt->execute();

        return $stmt;
    }

    /**
     * Retourne le type PDO correspondant à la valeur d'un paramètre
     *
     * @param mixed $value Valeur du paramètre
     * @param int $type Type PDO enregistré
     * @return int
     */
    private static function pdoType(mixed $value, int $type): int
    {
        return match (true) {
            $value === null => PDO::PARAM_NULL,
            is_bool($value) => PDO::PARAM_BOOL,
            is_int($value) => PDO::PARAM_INT,
            default => ($type === PDO::PARAM_INT || $type === PDO::PARAM_BOOL ? PDO::PARAM_STR : $type),
        };
    }
}

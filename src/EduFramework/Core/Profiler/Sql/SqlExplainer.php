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
 * Le plan est aussi normalisé en une liste d'accès aux tables commune aux
 * moteurs (MysqlPlanNormalizer, SqlitePlanNormalizer), utilisée par
 * SqlExplainAdvisor pour proposer des axes d'amélioration.
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
     * Instruction qui exécute réellement la requête, par moteur (SQLite : la requête seule)
     */
    private const array ANALYZE_STATEMENTS = [
        self::DRIVER_MARIADB => 'ANALYZE',
        self::DRIVER_MYSQL => 'EXPLAIN ANALYZE',
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
        if ($driver === self::DRIVER_MYSQL && stripos($this->serverVersion(), 'mariadb') !== false) {
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
        $statement = ($this->driver() === self::DRIVER_SQLITE ? 'EXPLAIN QUERY PLAN' : 'EXPLAIN');
        $rows = $this->run($statement . ' ' . $sql, $params)->fetchAll(PDO::FETCH_ASSOC);

        return ['statement' => $statement, 'columns' => self::columnsOf($rows), 'rows' => $rows];
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
        $statement = self::ANALYZE_STATEMENTS[$driver] ?? '';
        $start = microtime(true);
        $rows = $this->runInRolledBackTransaction(trim($statement . ' ' . $sql), $params);
        $analysis = [
            'statement' => $statement,
            'columns' => [],
            'rows' => [],
            'tree' => null,
            'time_ms' => round((microtime(true) - $start) * 1000, 1),
            'rows_count' => null,
        ];

        return match ($driver) {
            // SQLite : la requête elle-même a été exécutée
            self::DRIVER_SQLITE => array_merge($analysis, ['rows_count' => count($rows)]),
            // MySQL 8 : une seule colonne contenant l'arbre du plan mesuré
            self::DRIVER_MYSQL => array_merge($analysis, ['tree' => ($rows === [] ? '' : (string) reset($rows[0]))]),
            default => array_merge($analysis, ['columns' => self::columnsOf($rows), 'rows' => $rows]),
        };
    }

    /**
     * Normalise les lignes d'un plan d'exécution en accès aux tables communs aux moteurs
     * (voir MysqlPlanNormalizer)
     *
     * @param array<int, array<string, mixed>> $rows Lignes du plan (EXPLAIN ou ANALYZE)
     * @return array<int, array<string, mixed>>
     */
    public function accesses(array $rows): array
    {
        return ($this->driver() === self::DRIVER_SQLITE
            ? SqlitePlanNormalizer::normalize($rows)
            : MysqlPlanNormalizer::normalize($rows));
    }

    /**
     * Exécute la requête dans une transaction annulée, quand le moteur le permet
     *
     * @param string $sql Requête SQL
     * @param array<int, array{name: int|string, value: mixed, type: int}> $params Paramètres de la requête
     * @return array<int, array<string, mixed>>
     */
    private function runInRolledBackTransaction(string $sql, array $params): array
    {
        $transaction = false;
        try {
            $transaction = ($this->pdo->inTransaction() === false && $this->pdo->beginTransaction() === true);
        } catch (\PDOException) {
            $transaction = false;
        }

        try {
            return $this->run($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            if ($transaction === true && $this->pdo->inTransaction() === true) {
                $this->pdo->rollBack();
            }
        }
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

    /**
     * Retourne les colonnes d'un résultat (clés de la première ligne)
     *
     * @param array<int, array<string, mixed>> $rows Lignes du résultat
     * @return array<int, string>
     */
    private static function columnsOf(array $rows): array
    {
        return ($rows === [] ? [] : array_map('strval', array_keys($rows[0])));
    }
}

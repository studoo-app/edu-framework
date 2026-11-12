<?php

namespace Core\Profiler\Sql;

use PDO;
use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\Profiler\Sql\SqlExplainer;

/**
 * Tests de l'exécution du plan d'exécution sur SQLite (EXPLAIN QUERY PLAN),
 * de sa normalisation en accès aux tables et de la lecture des index,
 * et de la normalisation d'un plan MySQL / MariaDB
 */
class SqlExplainerTest extends TestCase
{
    private PDO $pdo;

    /**
     * Base SQLite en mémoire : bibliothèque sans index sur les clés étrangères
     */
    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('CREATE TABLE auteurs (id INTEGER PRIMARY KEY, nom TEXT, prenom TEXT)');
        $this->pdo->exec('CREATE TABLE livres (id INTEGER PRIMARY KEY, titre TEXT, auteur_id INT, isbn TEXT UNIQUE, annee INT)');
        $this->pdo->exec('CREATE TABLE emprunts (id INTEGER PRIMARY KEY, livre_id INT, utilisateur_id INT)');
        $this->pdo->exec('CREATE INDEX idx_livres_titre ON livres (titre, auteur_id)');
        $this->pdo->exec("INSERT INTO auteurs (nom, prenom) VALUES ('Hugo', 'Victor'), ('Sand', 'George')");
        $this->pdo->exec("INSERT INTO livres (titre, auteur_id) VALUES ('Les Misérables', 1), ('Indiana', 2)");
    }

    /**
     * Vérifie le moteur détecté et la prise en charge
     */
    public function testDriverIsDetected(): void
    {
        $explainer = new SqlExplainer($this->pdo);

        $this->assertSame(SqlExplainer::DRIVER_SQLITE, $explainer->driver());
        $this->assertTrue($explainer->isSupported());
    }

    /**
     * Vérifie le plan SQLite : parcours complet, recherche par clé primaire,
     * tri sans index rattaché à la table lue en premier
     */
    public function testSqlitePlanIsNormalized(): void
    {
        $explainer = new SqlExplainer($this->pdo);
        $plan = $explainer->explain(
            'SELECT l.titre FROM livres l JOIN auteurs a ON l.auteur_id = a.id WHERE a.id = :id ORDER BY l.annee',
            [['name' => ':id', 'value' => 1, 'type' => PDO::PARAM_INT]]
        );

        $this->assertSame('EXPLAIN QUERY PLAN', $plan['statement']);
        $this->assertContains('detail', $plan['columns']);

        $accesses = $explainer->accesses($plan['rows']);
        $byAlias = array_column($accesses, null, 'alias');
        $this->assertSame('const', $byAlias['a']['access']);
        $this->assertSame('PRIMARY', $byAlias['a']['key']);
        $this->assertSame('full_scan', $byAlias['l']['access']);
        $this->assertTrue($accesses[0]['filesort']);
    }

    /**
     * Vérifie la normalisation des étapes SQLite écrites à la main : index temporaire,
     * index couvrant, sous-requête corrélée, regroupement
     */
    public function testSqliteSpecialStepsAreNormalized(): void
    {
        $accesses = SqlExplainer::sqliteAccesses([
            ['id' => 2, 'parent' => 0, 'detail' => 'SCAN u'],
            ['id' => 5, 'parent' => 0, 'detail' => 'SEARCH e USING AUTOMATIC COVERING INDEX (utilisateur_id=?) LEFT-JOIN'],
            ['id' => 8, 'parent' => 0, 'detail' => 'USE TEMP B-TREE FOR GROUP BY'],
            ['id' => 9, 'parent' => 0, 'detail' => 'CORRELATED SCALAR SUBQUERY 1'],
            ['id' => 11, 'parent' => 9, 'detail' => 'SEARCH l USING COVERING INDEX idx_t (titre>?)'],
        ]);

        $this->assertCount(3, $accesses);
        $this->assertSame('full_scan', $accesses[0]['access']);
        $this->assertTrue($accesses[0]['temporary']);
        $this->assertTrue($accesses[1]['auto_index']);
        $this->assertSame(['utilisateur_id'], $accesses[1]['index_columns']);
        $this->assertNull($accesses[1]['key']);
        $this->assertSame('range', $accesses[2]['access']);
        $this->assertTrue($accesses[2]['covering']);
        $this->assertTrue($accesses[2]['dependent']);
        $this->assertSame('idx_t', $accesses[2]['key']);
    }

    /**
     * Vérifie la normalisation d'un plan MySQL / MariaDB (types d'accès et colonne Extra)
     */
    public function testMysqlPlanIsNormalized(): void
    {
        $accesses = SqlExplainer::mysqlAccesses([
            ['id' => 1, 'select_type' => 'SIMPLE', 'table' => 'u', 'type' => 'ALL', 'key' => null,
                'rows' => '4000', 'r_rows' => '4000.00', 'Extra' => 'Using temporary; Using filesort'],
            ['id' => 1, 'select_type' => 'SIMPLE', 'table' => 'e', 'type' => 'ref', 'key' => 'idx_e',
                'rows' => 15, 'Extra' => 'Using index'],
            ['id' => 2, 'select_type' => 'DEPENDENT SUBQUERY', 'table' => 'r', 'type' => 'ALL', 'key' => null,
                'rows' => 10, 'Extra' => 'Using where; Using join buffer (flat, BNL join)'],
        ]);

        $this->assertSame(['full_scan', 'index_lookup', 'full_scan'], array_column($accesses, 'access'));
        $this->assertTrue($accesses[0]['filesort']);
        $this->assertTrue($accesses[0]['temporary']);
        $this->assertSame(4000.0, $accesses[0]['actual_rows']);
        $this->assertTrue($accesses[1]['covering']);
        $this->assertTrue($accesses[2]['dependent']);
        $this->assertTrue($accesses[2]['join_buffer']);
        $this->assertFalse($accesses[2]['covering']);
    }

    /**
     * Vérifie la lecture de la structure des tables : clé primaire INTEGER (rowid),
     * index unique, index composite, colonnes et nombre de lignes
     */
    public function testTablesStructureIsRead(): void
    {
        $tables = (new SqlExplainer($this->pdo))->tables(['livres', 'inconnue', 'livres; DROP TABLE x']);

        $this->assertSame(['livres'], array_keys($tables));
        $this->assertSame(2, $tables['livres']['rows']);
        $this->assertSame(['id', 'titre', 'auteur_id', 'isbn', 'annee'], $tables['livres']['columns']);
        $indexes = array_column($tables['livres']['indexes'], null, 'name');
        $this->assertTrue($indexes['PRIMARY']['primary']);
        $this->assertSame(['titre', 'auteur_id'], $indexes['idx_livres_titre']['columns']);
        $this->assertFalse($indexes['idx_livres_titre']['unique']);
        $unique = array_values(array_filter($tables['livres']['indexes'], static fn (array $i): bool => $i['columns'] === ['isbn']));
        $this->assertTrue($unique[0]['unique']);
    }

    /**
     * Vérifie que le mode ANALYZE exécute la requête (temps et nombre de lignes)
     * dans une transaction annulée
     */
    public function testAnalyzeRunsTheQueryInARolledBackTransaction(): void
    {
        $analysis = (new SqlExplainer($this->pdo))->analyze(
            'SELECT * FROM livres WHERE auteur_id = ?',
            [['name' => 1, 'value' => 1, 'type' => PDO::PARAM_INT]]
        );

        $this->assertSame(1, $analysis['rows_count']);
        $this->assertGreaterThanOrEqual(0, $analysis['time_ms']);
        $this->assertNull($analysis['tree']);
        $this->assertFalse($this->pdo->inTransaction());
    }
}

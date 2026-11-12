<?php

namespace Controller;

use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\Debug\DebugSqlExplainController;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Logs\HttpRequestLogger;
use Studoo\EduFramework\Core\Logs\LogsService;
use Studoo\EduFramework\Core\Logs\SqlQueryLogger;
use Studoo\EduFramework\Core\Service\DatabaseService;

/**
 * Tests de l'endpoint /edu-sql-explain : analyse EXPLAIN d'une requête SQL
 * enregistrée, sur une base applicative SQLite en mémoire
 */
class DebugSqlExplainControllerTest extends TestCase
{
    private static string $logsPath;

    /**
     * Crée le dossier temporaire dédié aux logs avant l'ensemble des tests
     */
    public static function setUpBeforeClass(): void
    {
        self::$logsPath = sys_get_temp_dir() . '/edu-framework-sql-explain-test/';
        if (is_dir(self::$logsPath) === false) {
            mkdir(self::$logsPath, 0755, true);
        }
    }

    /**
     * Réinitialise les états statiques, pointe les logs vers le dossier de test
     * et branche une base applicative SQLite en mémoire
     */
    public function setUp(): void
    {
        (new ReflectionProperty(LogsService::class, 'dbConnectLogs'))->setValue(null, null);
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, null);
        SqlQueryLogger::reset();
        (new ConfigCore(['sqlite_logs_path' => self::$logsPath]));

        $app = new PDO('sqlite::memory:');
        $app->exec('CREATE TABLE auteurs (id INTEGER PRIMARY KEY, nom TEXT)');
        $app->exec('CREATE TABLE livres (id INTEGER PRIMARY KEY, titre TEXT, auteur_id INT)');
        $app->exec("INSERT INTO livres (titre, auteur_id) VALUES ('A', 1), ('B', 2)");
        (new ReflectionProperty(DatabaseService::class, 'dbConnect'))->setValue(null, $app);
    }

    /**
     * Réinitialise le collecteur de requêtes SQL après chaque test
     */
    public function tearDown(): void
    {
        SqlQueryLogger::reset();
    }

    /**
     * Supprime la base SQLite des logs et le dossier temporaire des tests
     */
    public static function tearDownAfterClass(): void
    {
        if (is_file(self::$logsPath . 'core_logs.sqlite') === true) {
            unlink(self::$logsPath . 'core_logs.sqlite');
        }
        if (is_dir(self::$logsPath) === true) {
            rmdir(self::$logsPath);
        }
    }

    /**
     * Enregistre une requête SQL comme pendant une requête HTTP et retourne son identifiant
     * @param array<int, array{name: int|string, value: mixed, type: int}> $params
     */
    private function record(int $token, string $query, array $params = []): int
    {
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, $token);
        SqlQueryLogger::collect($query, 12.5, 1, $params);
        SqlQueryLogger::flush();
        $queries = LogsService::getSqlQueries($token);

        return end($queries)['id'];
    }

    /**
     * Exécute le contrôleur avec les paramètres GET donnés
     * @param array<string, string> $params
     * @return array<string, mixed>
     */
    private function runController(array $params): array
    {
        $request = new Request('/edu-sql-explain', 'GET');
        $request->setVars($params);

        return json_decode((string) (new DebugSqlExplainController())->execute($request), true);
    }

    /**
     * Vérifie l'analyse complète d'une requête avec paramètre enregistré : plan,
     * accès, structure des tables, axes d'amélioration et index proposés
     */
    public function testRecordedQueryIsExplainedWithItsParameters(): void
    {
        $id = $this->record(20, 'SELECT titre FROM livres WHERE auteur_id = :auteur_id', [
            ['name' => ':auteur_id', 'value' => 1, 'type' => PDO::PARAM_INT],
        ]);

        $response = $this->runController(['id' => (string) $id]);

        $this->assertNull($response['message']);
        $this->assertNull($response['error']);
        $this->assertSame(20, $response['token']);
        $this->assertSame('sqlite', $response['driver']);
        $this->assertTrue($response['read_only']);
        $this->assertSame([['name' => ':auteur_id', 'value' => 1, 'provided' => true]], $response['params']);
        $this->assertSame('EXPLAIN QUERY PLAN', $response['plan']['statement']);
        $this->assertSame('full_scan', $response['accesses'][0]['access']);
        $this->assertSame('livres', $response['tables'][0]['name']);
        $this->assertSame(
            ['CREATE INDEX idx_livres_auteur_id ON livres (auteur_id);'],
            $response['suggestions']
        );
        $this->assertSame('CREATE INDEX idx_livres_auteur_id ON livres (auteur_id);', $response['findings'][0]['suggestion']);
        $this->assertNull($response['analysis']);
    }

    /**
     * Vérifie le mode ANALYZE (exécution réelle) et les valeurs de paramètres saisies
     */
    public function testAnalyzeModeAndOverriddenParameters(): void
    {
        $id = $this->record(21, 'SELECT titre FROM livres WHERE auteur_id = :auteur_id', [
            ['name' => ':auteur_id', 'value' => 1, 'type' => PDO::PARAM_INT],
        ]);

        $response = $this->runController(['id' => (string) $id, 'analyze' => '1', 'params' => '{":auteur_id":"2"}']);

        $this->assertSame(2, $response['params'][0]['value']);
        $this->assertSame(1, $response['analysis']['rows_count']);
    }

    /**
     * Vérifie qu'une requête dont les paramètres sont inconnus (enregistrée avant
     * leur capture) demande leur saisie, puis s'analyse une fois les valeurs fournies
     */
    public function testMissingParametersAreRequested(): void
    {
        $id = $this->record(22, 'SELECT * FROM livres WHERE id = ? AND titre = ?');

        $response = $this->runController(['id' => (string) $id]);
        $this->assertStringContainsString('Renseignez la valeur des paramètres 1, 2', $response['message']);
        $this->assertNull($response['plan']);
        $this->assertSame([false, false], array_column($response['params'], 'provided'));

        $response = $this->runController(['id' => (string) $id, 'params' => '{"1":"1","2":"A"}']);
        $this->assertNull($response['message']);
        $this->assertSame('const', $response['accesses'][0]['access']);
    }

    /**
     * Vérifie les refus : identifiant invalide ou inconnu, requête sans plan,
     * requêtes multiples, ANALYZE d'une requête qui modifie les données, erreur SQL
     */
    public function testInvalidRequestsAreRefused(): void
    {
        $this->assertSame('Identifiant de requête SQL invalide.', $this->runController(['id' => 'abc'])['message']);
        $this->assertStringContainsString('introuvable', $this->runController(['id' => '999999'])['message']);

        $create = $this->record(23, 'CREATE TABLE t (id INT)');
        $this->assertStringContainsString('Seules les requêtes', $this->runController(['id' => (string) $create])['message']);

        $multiple = $this->record(23, 'SELECT 1; DELETE FROM livres');
        $this->assertStringContainsString('plusieurs requêtes', $this->runController(['id' => (string) $multiple])['message']);

        $delete = $this->record(23, 'DELETE FROM livres WHERE id = 1');
        $response = $this->runController(['id' => (string) $delete, 'analyze' => '1']);
        $this->assertStringContainsString('réservé aux requêtes de lecture', $response['message']);
        $this->assertNotNull($response['plan']);
        $this->assertNull($response['analysis']);
        // La requête n'a pas été exécutée
        $this->assertSame(2, (int) DatabaseService::getConnect()->query('SELECT COUNT(*) FROM livres')->fetchColumn());

        $unknown = $this->record(23, 'SELECT * FROM table_inconnue');
        $response = $this->runController(['id' => (string) $unknown]);
        $this->assertStringContainsString('refusé', $response['message']);
        $this->assertStringContainsString('table_inconnue', $response['error']);
    }
}

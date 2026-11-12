<?php

namespace Service;

use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Logs\HttpRequestLogger;
use Studoo\EduFramework\Core\Logs\LogsService;
use Studoo\EduFramework\Core\Logs\SqlQueryLogger;
use Studoo\EduFramework\Core\Service\DatabaseSqlite;
use Studoo\EduFramework\Core\Service\PdoFactory;
use Studoo\EduFramework\Core\Service\TraceablePDO;

/**
 * Tests de TraceablePDO et PdoFactory : traçage des requêtes SQL en mode dev
 * uniquement (query(), exec(), statements préparés) et écriture au flush()
 */
class TraceablePdoTest extends TestCase
{
    private static string $logsPath;

    /**
     * @var array<string, mixed> Sauvegarde de $_ENV pour restauration après chaque test
     */
    private array $envBackup;

    /**
     * Crée le dossier temporaire dédié aux logs avant l'ensemble des tests
     */
    public static function setUpBeforeClass(): void
    {
        // Dossier temporaire dédié aux tests : isole les logs de la base applicative var/logs/
        self::$logsPath = sys_get_temp_dir() . '/edu-framework-traceable-pdo-test/';
        if (is_dir(self::$logsPath) === false) {
            mkdir(self::$logsPath, 0755, true);
        }
    }

    /**
     * Sauvegarde $_ENV et réinitialise les états statiques pour chaque test
     */
    public function setUp(): void
    {
        $this->envBackup = $_ENV;

        // Réinitialisation des états statiques pour isoler chaque test
        (new ReflectionProperty(LogsService::class, 'dbConnectLogs'))->setValue(null, null);
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, null);
        SqlQueryLogger::reset();

        (new ConfigCore([
            'sqlite_logs_path' => self::$logsPath,
            'sqlite_path' => self::$logsPath
        ]));
    }

    /**
     * Restaure $_ENV et vide le collecteur de requêtes après chaque test
     */
    public function tearDown(): void
    {
        $_ENV = $this->envBackup;
        SqlQueryLogger::reset();
    }

    /**
     * Supprime les bases SQLite créées par les tests et le dossier temporaire
     */
    public static function tearDownAfterClass(): void
    {
        foreach (glob(self::$logsPath . '*.sqlite') ?: [] as $database) {
            if (is_file($database) === true) {
                unlink($database);
            }
        }
        if (is_dir(self::$logsPath) === true) {
            rmdir(self::$logsPath);
        }
    }

    /**
     * Simule une requête HTTP journalisée : le token est réservé par HttpRequestLogger::start()
     */
    private function startHttpRequest(int $logId): void
    {
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, $logId);
    }

    /**
     * Vérifie qu'en mode dev la connexion retournée par PdoFactory est traçable,
     * tout en restant un PDO compatible avec DatabaseService::getConnect()
     */
    public function testDevModeReturnsATraceableConnection(): void
    {
        $_ENV['APP_ENV'] = 'dev';

        $connection = PdoFactory::create('sqlite::memory:');

        // En dev, la connexion est traçable tout en restant un PDO
        // (DatabaseService::getConnect() reste compatible)
        $this->assertInstanceOf(TraceablePDO::class, $connection);
        $this->assertInstanceOf(PDO::class, $connection);
    }

    /**
     * Vérifie qu'en production la connexion retournée est un PDO natif, sans tracing
     */
    public function testProdModeReturnsAPlainPdoConnection(): void
    {
        $_ENV['APP_ENV'] = 'prod';

        $connection = PdoFactory::create('sqlite::memory:');

        // En production, aucune classe de tracing n'est instanciée
        $this->assertInstanceOf(PDO::class, $connection);
        $this->assertNotInstanceOf(TraceablePDO::class, $connection);
    }

    /**
     * Vérifie que le tracing suit la même condition que la journalisation HTTP :
     * actif uniquement en mode dev
     */
    public function testShouldTraceIsEnabledOnlyInDevMode(): void
    {
        // Le collecteur écrit dans la base SQLite des logs :
        // le tracing suit la même condition que la journalisation des requêtes HTTP
        $_ENV['APP_ENV'] = 'prod';
        $this->assertFalse(PdoFactory::shouldTrace());
        $this->assertNotInstanceOf(TraceablePDO::class, PdoFactory::create('sqlite::memory:'));

        $_ENV['APP_ENV'] = 'dev';
        $this->assertTrue(PdoFactory::shouldTrace());
        $this->assertInstanceOf(TraceablePDO::class, PdoFactory::create('sqlite::memory:'));
    }

    /**
     * Vérifie que la connexion applicative est tracée en dev, contrairement à la
     * connexion aux logs qui ne doit jamais apparaître dans les requêtes collectées
     */
    public function testAppSqlConnectionIsTraceableOnlyInDevMode(): void
    {
        // La connexion applicative SQLite (appel sans paramètre) est tracée en dev
        $_ENV['APP_ENV'] = 'dev';
        $_ENV['DB_NAME'] = 'traceable_app_test';

        $connection = (new DatabaseSqlite())->getManager();
        $this->assertInstanceOf(TraceablePDO::class, $connection);

        // La connexion aux logs (paramètres explicites) n'est jamais tracée,
        // même en dev : elle ne doit pas apparaître dans les requêtes collectées
        $logsConnection = (new DatabaseSqlite())->getManager('core_logs', self::$logsPath);
        $this->assertNotInstanceOf(TraceablePDO::class, $logsConnection);
        $this->assertInstanceOf(PDO::class, $logsConnection);

        // En production, la connexion applicative est un PDO natif
        $_ENV['APP_ENV'] = 'prod';
        $prodConnection = (new DatabaseSqlite())->getManager();
        $this->assertNotInstanceOf(TraceablePDO::class, $prodConnection);
    }

    /**
     * Vérifie que query() et exec() ne sont collectées qu'en présence d'une requête
     * HTTP journalisée
     */
    public function testQueryAndExecAreCollectedOnlyWithAnHttpRequest(): void
    {
        $_ENV['APP_ENV'] = 'dev';

        // Sans requête HTTP en cours (Exemple : ligne de commande),
        // aucune requête SQL n'est collectée
        $pdo = PdoFactory::create('sqlite::memory:');
        $pdo->exec('CREATE TABLE test (id INTEGER)');
        $pdo->query('SELECT 1')->fetch();
        $this->assertSame(0, SqlQueryLogger::count());

        // Avec une requête HTTP journalisée, exec() et query() sont collectées
        $this->startHttpRequest(42);
        $pdo->exec('CREATE TABLE ville (id INTEGER PRIMARY KEY, nom TEXT)');
        $statement = $pdo->query('SELECT 1');
        $statement->fetch();

        $this->assertSame(2, SqlQueryLogger::count());
    }

    /**
     * Vérifie que chaque execute() d'un statement préparé est collecté comme une exécution
     */
    public function testPreparedStatementExecutionsAreCollected(): void
    {
        $_ENV['APP_ENV'] = 'dev';
        $this->startHttpRequest(42);

        $pdo = PdoFactory::create('sqlite::memory:');
        $pdo->exec('CREATE TABLE ville (id INTEGER PRIMARY KEY, nom TEXT)');
        SqlQueryLogger::reset();

        // Chaque appel à execute() est une exécution de requête collectée
        $statement = $pdo->prepare('SELECT * FROM ville WHERE nom = :nom');
        $statement->execute([':nom' => 'Nice']);
        $statement->execute([':nom' => 'Cannes']);

        $this->assertSame(2, SqlQueryLogger::count());
    }

    /**
     * Vérifie que les requêtes collectées portent leur durée et leur nombre de lignes
     */
    public function testCollectedQueriesCarryTheDurationAndRowsCount(): void
    {
        $_ENV['APP_ENV'] = 'dev';
        $this->startHttpRequest(42);

        $pdo = PdoFactory::create('sqlite::memory:');
        $pdo->exec('CREATE TABLE ville (id INTEGER PRIMARY KEY, nom TEXT)');

        // exec() retourne le nombre de lignes affectées : il est collecté
        $rows = $pdo->exec("INSERT INTO ville (nom) VALUES ('Nice')");
        $this->assertSame(1, $rows);

        $this->assertSame(2, SqlQueryLogger::count());
        $this->assertGreaterThanOrEqual(0, SqlQueryLogger::totalDurationMs());
    }

    /**
     * Vérifie que flush() écrit les requêtes collectées avec le token de la requête HTTP,
     * dans l'ordre d'exécution et avec leurs mesures
     */
    public function testFlushWritesTheCollectedQueriesWithTheRequestToken(): void
    {
        $_ENV['APP_ENV'] = 'dev';
        $this->startHttpRequest(100);

        $pdo = PdoFactory::create('sqlite::memory:');
        $pdo->exec('CREATE TABLE ville (id INTEGER PRIMARY KEY, nom TEXT)');
        SqlQueryLogger::reset();

        $pdo->exec("INSERT INTO ville (nom) VALUES ('Nice')");
        $statement = $pdo->prepare('SELECT * FROM ville WHERE nom = :nom');
        $statement->execute([':nom' => 'Nice']);

        SqlQueryLogger::flush();

        // Les requêtes sont rattachées au token de la requête HTTP,
        // dans l'ordre d'exécution, avec la durée et le nombre de lignes
        $queries = LogsService::getSqlQueries(100);
        $this->assertCount(2, $queries);
        $this->assertSame("INSERT INTO ville (nom) VALUES ('Nice')", $queries[0]['query']);
        $this->assertSame(1, $queries[0]['rows_count']);
        $this->assertGreaterThanOrEqual(0, $queries[0]['duration_ms']);
        $this->assertSame('SELECT * FROM ville WHERE nom = :nom', $queries[1]['query']);
        $this->assertSame(2, LogsService::countSqlQueries(100));

        // Le tampon est vidé après l'écriture
        $this->assertSame(0, SqlQueryLogger::count());
    }

    /**
     * Vérifie que les exécutions successives d'une même requête forment un groupe
     */
    public function testIdenticalQueriesAreGrouped(): void
    {
        $_ENV['APP_ENV'] = 'dev';
        $this->startHttpRequest(101);

        $pdo = PdoFactory::create('sqlite::memory:');
        $pdo->exec('CREATE TABLE ville (id INTEGER PRIMARY KEY, nom TEXT)');
        $pdo->exec("INSERT INTO ville (nom) VALUES ('Nice')");
        SqlQueryLogger::reset();

        $statement = $pdo->prepare('SELECT * FROM ville WHERE nom = :nom');
        $statement->execute([':nom' => 'Nice']);
        $statement->execute([':nom' => 'Nice']);
        $statement->execute([':nom' => 'Cannes']);

        SqlQueryLogger::flush();

        // La même requête exécutée 3 fois forme un groupe
        $groups = LogsService::getSqlGroups(101);
        $this->assertCount(1, $groups);
        $this->assertSame('SELECT * FROM ville WHERE nom = :nom', $groups[0]['query']);
        $this->assertSame(3, $groups[0]['count']);
        $this->assertGreaterThanOrEqual(0, $groups[0]['duration_ms']);
    }

    /**
     * Vérifie que les paramètres d'un statement préparé sont enregistrés avec la requête
     * (execute() avec tableau, bindValue(), bindParam() lu au moment de execute(), position)
     * pour que le profiler puisse rejouer la requête avec EXPLAIN
     */
    public function testBoundParametersAreRecordedWithTheQuery(): void
    {
        $_ENV['APP_ENV'] = 'dev';
        $this->startHttpRequest(102);

        $pdo = PdoFactory::create('sqlite::memory:');
        $pdo->exec('CREATE TABLE ville (id INTEGER PRIMARY KEY, nom TEXT, cp INTEGER)');
        SqlQueryLogger::reset();

        $pdo->prepare('SELECT * FROM ville WHERE nom = :nom')->execute([':nom' => 'Nice']);

        $byValue = $pdo->prepare('SELECT * FROM ville WHERE cp = :cp');
        $byValue->bindValue(':cp', 6000, PDO::PARAM_INT);
        $byValue->execute();

        $byParam = $pdo->prepare('SELECT * FROM ville WHERE nom = :nom');
        $nom = 'Avant';
        $byParam->bindParam(':nom', $nom);
        $nom = 'Cannes';
        $byParam->execute();

        $pdo->prepare('SELECT * FROM ville WHERE id = ? AND nom = ?')->execute([3, 'Antibes']);
        $pdo->query('SELECT * FROM ville');

        SqlQueryLogger::flush();
        $queries = LogsService::getSqlQueries(102);
        $params = array_map(
            static fn (array $query): array => LogsService::getSqlQuery($query['id'])['params'],
            $queries
        );

        $this->assertSame([['name' => ':nom', 'value' => 'Nice', 'type' => PDO::PARAM_STR]], $params[0]);
        $this->assertSame([['name' => ':cp', 'value' => 6000, 'type' => PDO::PARAM_INT]], $params[1]);
        // bindParam() : la valeur est celle de la variable au moment de execute()
        $this->assertSame('Cannes', $params[2][0]['value']);
        // Paramètres positionnels numérotés à partir de 1, comme bindValue()
        $this->assertSame([1, 2], array_column($params[3], 'name'));
        $this->assertSame([3, 'Antibes'], array_column($params[3], 'value'));
        // Une requête sans paramètre n'en enregistre aucun
        $this->assertSame([], $params[4]);
        $this->assertTrue($queries[0]['has_params']);
        $this->assertFalse($queries[4]['has_params']);
    }

    /**
     * Vérifie que getSqlQuery() compte les exécutions identiques de la requête HTTP
     * et tronque les valeurs de paramètres trop longues
     */
    public function testRecordedQueryCountsIdenticalExecutionsAndTruncatesLongValues(): void
    {
        $_ENV['APP_ENV'] = 'dev';
        $this->startHttpRequest(103);

        $pdo = PdoFactory::create('sqlite::memory:');
        $pdo->exec('CREATE TABLE ville (id INTEGER PRIMARY KEY, nom TEXT)');
        SqlQueryLogger::reset();

        $statement = $pdo->prepare('SELECT * FROM ville WHERE nom = :nom');
        $statement->execute([':nom' => str_repeat('a', SqlQueryLogger::PARAM_MAX_LENGTH + 50)]);
        $statement->execute([':nom' => 'Nice']);
        SqlQueryLogger::flush();

        $queries = LogsService::getSqlQueries(103);
        $first = LogsService::getSqlQuery($queries[0]['id']);

        $this->assertSame(2, $first['executions']);
        $this->assertSame(103, $first['request_id']);
        $this->assertSame(SqlQueryLogger::PARAM_MAX_LENGTH, strlen($first['params'][0]['value']));
        $this->assertNull(LogsService::getSqlQuery(999999));
    }

    /**
     * Vérifie qu'une base des logs créée par une version précédente (sans la colonne
     * params) est mise à jour à l'ouverture
     */
    public function testLogsDatabaseWithoutParamsColumnIsUpgraded(): void
    {
        $database = new PDO('sqlite:' . self::$logsPath . 'core_logs.sqlite');
        $database->exec('DROP TABLE IF EXISTS serv_sql_logs');
        $database->exec('CREATE TABLE serv_sql_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, request_id INTEGER NOT NULL, '
            . 'query TEXT NOT NULL, duration_ms REAL NOT NULL, rows_count INTEGER, event_date TEXT)');
        $database = null;

        $columns = array_column(
            LogsService::getConnect()->query('PRAGMA table_info(serv_sql_logs)')->fetchAll(PDO::FETCH_ASSOC),
            'name'
        );

        $this->assertContains('params', $columns);
    }
}

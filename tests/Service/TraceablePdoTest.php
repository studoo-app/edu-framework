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

class TraceablePdoTest extends TestCase
{
    private static string $logsPath;

    /**
     * @var array<string, mixed> Sauvegarde de $_ENV pour restauration après chaque test
     */
    private array $envBackup;

    public static function setUpBeforeClass(): void
    {
        // Dossier temporaire dédié aux tests : isole les logs de la base applicative var/logs/
        self::$logsPath = sys_get_temp_dir() . '/edu-framework-traceable-pdo-test/';
        if (is_dir(self::$logsPath) === false) {
            mkdir(self::$logsPath, 0777, true);
        }
    }

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

    public function tearDown(): void
    {
        $_ENV = $this->envBackup;
        SqlQueryLogger::reset();
    }

    public static function tearDownAfterClass(): void
    {
        foreach (glob(self::$logsPath . '*.sqlite') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir(self::$logsPath);
    }

    /**
     * Simule une requête HTTP journalisée : le token est réservé par HttpRequestLogger::start()
     */
    private function startHttpRequest(int $logId): void
    {
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, $logId);
    }

    public function testDevModeReturnsATraceableConnection(): void
    {
        $_ENV['APP_ENV'] = 'dev';

        $connection = PdoFactory::create('sqlite::memory:');

        // En dev, la connexion est traçable tout en restant un PDO
        // (DatabaseService::getConnect() reste compatible)
        $this->assertInstanceOf(TraceablePDO::class, $connection);
        $this->assertInstanceOf(PDO::class, $connection);
    }

    public function testProdModeReturnsAPlainPdoConnection(): void
    {
        $_ENV['APP_ENV'] = 'prod';

        $connection = PdoFactory::create('sqlite::memory:');

        // En production, aucune classe de tracing n'est instanciée
        $this->assertInstanceOf(PDO::class, $connection);
        $this->assertNotInstanceOf(TraceablePDO::class, $connection);
    }

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
}

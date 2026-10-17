<?php

namespace Core\Logs;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Logs\HttpRequestLogger;
use Studoo\EduFramework\Core\Logs\LogsService;
use Studoo\EduFramework\Core\Logs\SqlQueryLogger;

/**
 * Tests de SqlQueryLogger : collecte des requêtes SQL en mémoire,
 * rattachement au token de la requête HTTP et écriture en base au flush()
 */
class SqlQueryLoggerTest extends TestCase
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
        self::$logsPath = sys_get_temp_dir() . '/edu-framework-sql-logger-test/';
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
            'sqlite_logs_path' => self::$logsPath
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
     * Simule une requête HTTP journalisée : le token est réservé par HttpRequestLogger::start()
     */
    private function startHttpRequest(int $logId): void
    {
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, $logId);
    }

    /**
     * Vérifie que la collecte est inactive sans requête HTTP en cours (ligne de commande, profiler)
     */
    public function testCollectDoesNothingWithoutHttpRequest(): void
    {
        // Sans token de requête HTTP (ligne de commande, profiler, ...),
        // la requête SQL n'est pas collectée
        SqlQueryLogger::collect('SELECT 1', 0.5, 1);

        $this->assertSame(0, SqlQueryLogger::count());
        $this->assertSame(0.0, SqlQueryLogger::totalDurationMs());
        $this->assertNull(SqlQueryLogger::slowestQuery());
    }

    /**
     * Vérifie que la requête collectée est mise en tampon avec le token de la requête HTTP
     */
    public function testCollectBuffersTheQueryWithTheRequestToken(): void
    {
        $this->startHttpRequest(7);

        SqlQueryLogger::collect('SELECT 1', 0.5, 1);
        SqlQueryLogger::collect('SELECT 2', 1.25, null);

        $this->assertSame(2, SqlQueryLogger::count());
        // La durée est arrondie à 1 décimale, comme le reste du profiler
        $this->assertSame(1.8, SqlQueryLogger::totalDurationMs());
        // La requête la plus lente est la deuxième
        $slowest = SqlQueryLogger::slowestQuery();
        $this->assertNotNull($slowest);
        $this->assertSame('SELECT 2', $slowest['query']);
        $this->assertSame(1.3, $slowest['duration_ms']);
    }

    /**
     * Vérifie que chaque requête collectée conserve le token capturé au moment
     * de son exécution (requêtes HTTP successives dans le même processus)
     */
    public function testEachCollectedQueryKeepsItsOwnRequestToken(): void
    {
        // Deux requêtes HTTP successives dans le même processus (Exemple : FrankenPHP)
        $this->startHttpRequest(7);
        SqlQueryLogger::collect('SELECT requete-7', 0.5);

        $this->startHttpRequest(8);
        SqlQueryLogger::collect('SELECT requete-8', 0.5);

        // Le tampon conserve le token capturé au moment de chaque exécution :
        // l'ordre des flush n'importe pas
        SqlQueryLogger::flush();

        $this->assertSame(1, LogsService::countSqlQueries(7));
        $this->assertSame(['SELECT requete-8'], array_column(LogsService::getSqlQueries(8), 'query'));
    }

    /**
     * Vérifie que flush() écrit toutes les requêtes en attente en une seule passe
     * et vide le tampon
     */
    public function testFlushWritesAllBufferedQueriesInOnePass(): void
    {
        $this->startHttpRequest(9);

        foreach (['SELECT a', 'SELECT b', 'SELECT c'] as $query) {
            SqlQueryLogger::collect($query, 0.5, 12);
        }
        SqlQueryLogger::flush();

        $queries = LogsService::getSqlQueries(9);
        $this->assertCount(3, $queries);
        $this->assertSame(['SELECT a', 'SELECT b', 'SELECT c'], array_column($queries, 'query'));
        $this->assertSame([12, 12, 12], array_column($queries, 'rows_count'));
        $this->assertSame(0, SqlQueryLogger::count());
    }

    /**
     * Vérifie que flush() avec un tampon vide n'initialise même pas la connexion aux logs
     */
    public function testFlushWithAnEmptyBufferDoesNotOpenTheLogsDatabase(): void
    {
        // Le tampon est vide : flush() ne doit même pas initialiser la connexion
        // aux logs (aucune écriture, aucun échec possible en production)
        SqlQueryLogger::flush();

        $this->assertNull(
            (new ReflectionProperty(LogsService::class, 'dbConnectLogs'))->getValue(),
            'La connexion à la base des logs ne doit pas être initialisée pour un tampon vide'
        );
    }

    /**
     * Vérifie qu'un échec d'écriture en base ne fait pas échouer la requête HTTP
     */
    public function testFlushNeverFailsTheRequest(): void
    {
        $this->startHttpRequest(10);
        SqlQueryLogger::collect('SELECT ecriture-impossible', 0.5);

        // La table serv_sql_logs est supprimée : l'écriture échoue
        // mais la requête HTTP ne doit pas échouer pour autant
        LogsService::getConnect()->exec('DROP TABLE serv_sql_logs');

        SqlQueryLogger::flush(); // Ne doit pas lever d'exception

        $this->assertSame(0, SqlQueryLogger::count());
        // Pas d'erreur fatale : le test atteint cette ligne
        $this->assertTrue(true);
    }

    /**
     * Vérifie qu'un token inconnu retourne des listes vides et un compteur à zéro
     */
    public function testGetSqlQueriesReturnsAnEmptyListForAnUnknownToken(): void
    {
        $this->assertSame([], LogsService::getSqlQueries(999999));
        $this->assertSame(0, LogsService::countSqlQueries(999999));
        $this->assertSame([], LogsService::getSqlGroups(999999));
    }
}

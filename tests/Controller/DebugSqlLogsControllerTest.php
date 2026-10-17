<?php

namespace Controller;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\Debug\DebugSqlLogsController;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Logs\HttpRequestLogger;
use Studoo\EduFramework\Core\Logs\LogsService;
use Studoo\EduFramework\Core\Logs\SqlQueryLogger;

/**
 * Les contrôleurs du profiler envoient un header Content-Type : ils ne
 * l'envoient que si la sortie n'a pas déjà commencé (headers_sent())
 */
class DebugSqlLogsControllerTest extends TestCase
{
    private static string $logsPath;

    public static function setUpBeforeClass(): void
    {
        // Dossier temporaire dédié aux tests : isole les logs de la base applicative var/logs/
        self::$logsPath = sys_get_temp_dir() . '/edu-framework-sql-endpoint-test/';
        if (is_dir(self::$logsPath) === false) {
            mkdir(self::$logsPath, 0777, true);
        }
    }

    public function setUp(): void
    {
        // Réinitialisation des états statiques pour isoler chaque test
        (new ReflectionProperty(LogsService::class, 'dbConnectLogs'))->setValue(null, null);
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, null);
        SqlQueryLogger::reset();

        (new ConfigCore([
            'sqlite_logs_path' => self::$logsPath
        ]));
    }

    public function tearDown(): void
    {
        SqlQueryLogger::reset();
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$logsPath . 'core_logs.sqlite');
        @rmdir(self::$logsPath);
    }

    /**
     * Exécute le contrôleur avec les paramètres GET donnés
     */
    private function runController(array $params): array
    {
        $request = new Request('/edu-sql-logs', 'GET');
        $request->setVars($params);

        return json_decode((string) (new DebugSqlLogsController())->execute($request), true);
    }

    public function testResponseIsAJsonWithTheDocumentedKeys(): void
    {
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, 7);
        SqlQueryLogger::collect('SELECT 1', 0.5, 1);
        SqlQueryLogger::collect('SELECT 1', 1.5, 1);
        SqlQueryLogger::flush();

        $response = $this->runController(['token' => '7']);

        $this->assertSame(7, $response['token']);
        $this->assertSame(2, $response['total']);
        // Le JSON ne distingue pas 2.0 de 2 : la valeur est numérique
        $this->assertEquals(2.0, $response['total_duration_ms']);
        $this->assertArrayHasKey('groups', $response);
        $this->assertArrayHasKey('queries', $response);
        $this->assertCount(2, $response['queries']);
        $this->assertSame('SELECT 1', $response['queries'][0]['query']);
        $this->assertArrayHasKey('duration_ms', $response['queries'][0]);
        $this->assertArrayHasKey('rows_count', $response['queries'][0]);
        $this->assertArrayHasKey('event_date', $response['queries'][0]);

        // La même requête exécutée 2 fois forme un groupe
        $this->assertCount(1, $response['groups']);
        $this->assertSame('SELECT 1', $response['groups'][0]['query']);
        $this->assertSame(2, $response['groups'][0]['count']);
        $this->assertEquals(2.0, $response['groups'][0]['duration_ms']);
    }

    public function testTokenWithSharpPrefixIsAccepted(): void
    {
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, 8);
        SqlQueryLogger::collect('SELECT 1', 0.5, null);
        SqlQueryLogger::flush();

        // La barre de debug affiche le token sous la forme #8
        $response = $this->runController(['token' => '#8']);

        $this->assertSame(8, $response['token']);
        $this->assertSame(1, $response['total']);
        $this->assertNull($response['queries'][0]['rows_count']);
    }

    public function testInvalidTokenReturnsAnEmptyResponse(): void
    {
        foreach ( ['', 'abc', '7.5', '-1', '99999999999999999999' ] as $token) {
            $response = $this->runController(['token' => $token]);

            $this->assertNull($response['token'], 'Le token <' . $token . '> ne doit pas être accepté');
            $this->assertSame(0, $response['total']);
            $this->assertSame(0, $response['total_duration_ms']);
            $this->assertSame([], $response['groups']);
            $this->assertSame([], $response['queries']);
        }

        // Sans paramètre du tout
        $response = $this->runController([]);
        $this->assertNull($response['token']);
        $this->assertSame(0, $response['total']);
    }

    public function testValidTokenWithoutQueriesReturnsAnEmptyList(): void
    {
        $response = $this->runController(['token' => '999999']);

        $this->assertSame(999999, $response['token']);
        $this->assertSame(0, $response['total']);
        $this->assertSame([], $response['queries']);
    }
}

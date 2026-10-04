<?php

namespace Core\Logs;

use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Logs\LogsService;

class LogsServiceTest extends TestCase
{
    private static string $logsPath;

    public static function setUpBeforeClass(): void
    {
        // Dossier temporaire dédié aux tests : isole les logs de la base applicative var/sqlite/
        self::$logsPath = sys_get_temp_dir() . '/edu-framework-logs-test/';
    }

    public function setUp(): void
    {
        // Réinitialisation de la connexion statique pour tester l'initialisation à chaque test
        $reflection = new ReflectionProperty(LogsService::class, 'dbConnectLogs');
        $reflection->setValue(null, null);

        (new ConfigCore([
            'sqlite_logs_path' => self::$logsPath
        ]));
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$logsPath . 'core_logs.sqlite');
        @rmdir(self::$logsPath);
    }

    public function testGetConnectInitializesTheConnectionLazily(): void
    {
        $this->assertInstanceOf(PDO::class, LogsService::getConnect());
    }

    public function testConstructorCreatesTheServLogsTable(): void
    {
        new LogsService();

        $result = LogsService::getConnect()
            ->query("SELECT name FROM sqlite_master WHERE type='table' AND name='serv_logs'");

        $this->assertNotFalse($result->fetch(), 'La table serv_logs doit exister après initialisation');
    }

    public function testAddLogInsertsExactlyOneNewRow(): void
    {
        $pdo = LogsService::getConnect();
        $countBefore = (int) $pdo->query('SELECT COUNT(*) FROM serv_logs')->fetchColumn();

        LogsService::addLog('{"raw":"[Mon May  5 08:02:06 2025] 127.0.0.1:65229 [200]: GET /"}');

        $countAfter = (int) $pdo->query('SELECT COUNT(*) FROM serv_logs')->fetchColumn();
        $this->assertSame($countBefore + 1, $countAfter);
    }

    public function testAddLogStoresTheEventDescription(): void
    {
        $eventDesc = '{"method":"GET","path":"/test"}';

        LogsService::addLog($eventDesc);

        $stmt = LogsService::getConnect()
            ->prepare('SELECT event_desc FROM serv_logs WHERE id = (SELECT MAX(id) FROM serv_logs)');
        $stmt->execute();
        $this->assertSame($eventDesc, $stmt->fetchColumn());
    }
}

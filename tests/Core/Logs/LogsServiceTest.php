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

    public function testGetLogsReturnsJsonEventsDecodedAndNewestFirst(): void
    {
        LogsService::addLog('{"method":"GET","path":"/first","status_code":"200"}');
        LogsService::addLog('{"method":"POST","path":"/second","status_code":"404"}');

        $logs = LogsService::getLogs(2, 0);

        $this->assertCount(2, $logs);
        $this->assertSame('POST', $logs[0]['event']['method']);
        $this->assertSame('/second', $logs[0]['event']['path']);
        $this->assertSame('GET', $logs[1]['event']['method']);
        $this->assertSame('/first', $logs[1]['event']['path']);
        $this->assertGreaterThan($logs[1]['id'], $logs[0]['id']);
    }

    public function testGetLogsReturnsRawFallbackForNonJsonEvents(): void
    {
        LogsService::addLog('message brut du serveur');

        $logs = LogsService::getLogs(1, 0);

        $this->assertSame(['raw' => 'message brut du serveur'], $logs[0]['event']);
    }

    public function testGetLogsPaginatesWithLimitAndOffset(): void
    {
        LogsService::addLog('{"method":"GET","path":"/page-1"}');
        LogsService::addLog('{"method":"GET","path":"/page-2"}');

        // Ordre DESC : offset 0 = la plus récente (/page-2), offset 1 = la suivante (/page-1)
        $page = LogsService::getLogs(1, 1);

        $this->assertCount(1, $page);
        $this->assertSame('/page-1', $page[0]['event']['path']);
    }

    public function testCountLogs(): void
    {
        $countBefore = LogsService::countLogs();

        LogsService::addLog('{"method":"GET","path":"/count"}');

        $this->assertSame($countBefore + 1, LogsService::countLogs());
    }

    public function testGetLogsFiltersByMethod(): void
    {
        LogsService::addLog('{"method":"GET","path":"/filter-method","status_code":"200","ip_port":"127.0.0.1:1111","timestamp":"now"}');
        LogsService::addLog('{"method":"POST","path":"/filter-method","status_code":"200","ip_port":"127.0.0.1:2222","timestamp":"now"}');

        $logs = LogsService::getLogs(50, 0, ['method' => 'POST']);

        $this->assertNotEmpty($logs);
        foreach ($logs as $log) {
            $this->assertSame('POST', $log['event']['method']);
        }
    }

    public function testGetLogsFiltersByStatusFamily(): void
    {
        LogsService::addLog('{"method":"GET","path":"/filter-status-ok","status_code":"200"}');
        LogsService::addLog('{"method":"GET","path":"/filter-status-err","status_code":"404"}');

        $logs = LogsService::getLogs(50, 0, ['status' => '4']);

        $this->assertNotEmpty($logs);
        foreach ($logs as $log) {
            $this->assertStringStartsWith('4', (string) $log['event']['status_code']);
        }
    }

    public function testGetLogsFiltersBySearch(): void
    {
        LogsService::addLog('{"method":"GET","path":"/needle-search","status_code":"200"}');

        $logs = LogsService::getLogs(50, 0, ['search' => 'needle-search']);

        $this->assertNotEmpty($logs);
        foreach ($logs as $log) {
            $this->assertStringContainsString('needle-search', (string) $log['event']['path']);
        }
    }

    public function testCountLogsRespectsFilters(): void
    {
        $totalAll = LogsService::countLogs();
        $totalPost = LogsService::countLogs(['method' => 'POST']);

        $this->assertLessThanOrEqual($totalAll, $totalPost);

        LogsService::addLog('{"method":"POST","path":"/filter-count","status_code":"200"}');

        $this->assertSame($totalPost + 1, LogsService::countLogs(['method' => 'POST']));
        $this->assertSame($totalAll + 1, LogsService::countLogs());
    }

    public function testGetLogsFiltersCombined(): void
    {
        LogsService::addLog('{"method":"POST","path":"/combined","status_code":"500","ip_port":"127.0.0.1:3333","timestamp":"now"}');

        $logs = LogsService::getLogs(50, 0, ['method' => 'POST', 'status' => '5', 'search' => 'combined']);

        $this->assertNotEmpty($logs);
        foreach ($logs as $log) {
            $this->assertSame('POST', $log['event']['method']);
            $this->assertSame('500', $log['event']['status_code']);
        }
    }

    public function testGetLogsFiltersByExactStatusCode(): void
    {
        LogsService::addLog('{"method":"GET","path":"/exact-500","status_code":"500"}');
        LogsService::addLog('{"method":"GET","path":"/exact-503","status_code":"503"}');

        $logs = LogsService::getLogs(50, 0, ['status' => '503']);

        $this->assertNotEmpty($logs);
        foreach ($logs as $log) {
            $this->assertSame('503', $log['event']['status_code']);
        }
    }

    public function testGetLogsFiltersByIpUrlAndToken(): void
    {
        // Même encodage que StartCommand : les "/" sont échappés en "\/"
        LogsService::addLog((string) json_encode(['method' => 'GET', 'path' => '/blog/post', 'status_code' => '200', 'ip_port' => '10.1.2.3:4444']));
        $id = (int) LogsService::getConnect()->lastInsertId();

        $this->assertSame([$id], array_column(LogsService::getLogs(50, 0, ['ip' => '10.1.2.3', 'url' => '/blog/post']), 'id'));
        $this->assertSame([$id], array_column(LogsService::getLogs(50, 0, ['token' => (string) $id]), 'id'));
        $this->assertSame([], LogsService::getLogs(50, 0, ['token' => (string) $id, 'url' => '/unknown']));
    }

    public function testGetLogsFiltersByDateRange(): void
    {
        LogsService::addLog('{"method":"GET","path":"/dated","status_code":"200"}');
        $today = date('Y-m-d');

        $this->assertNotEmpty(LogsService::getLogs(50, 0, ['from' => $today, 'until' => $today]));
        $this->assertSame(0, LogsService::countLogs(['until' => '2000-01-01']));
        $this->assertSame(0, LogsService::countLogs(['from' => '2999-01-01']));
    }

    public function testCountByStatusFamilyIgnoresTheStatusFilter(): void
    {
        LogsService::addLog('{"method":"GET","path":"/family","status_code":"404"}');

        $counts = LogsService::countByStatusFamily(['status' => '2']);

        $this->assertSame(['2', '3', '4', '5'], array_map('strval', array_keys($counts)));
        $this->assertGreaterThanOrEqual(1, $counts['4']);
        $this->assertSame(LogsService::countLogs(['status' => '4']), $counts['4']);
    }
}

<?php

namespace Core\Logs;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Formatter\BufferToServer;
use Studoo\EduFramework\Core\Logs\HttpRequestLogger;
use Studoo\EduFramework\Core\Logs\LogsService;

class HttpRequestLoggerTest extends TestCase
{
    private static string $logsPath;

    /**
     * @var array<string, mixed> Sauvegarde de $_SERVER pour restauration après chaque test
     */
    private array $serverBackup;

    /**
     * @var array<string, mixed> Sauvegarde de $_ENV pour restauration après chaque test
     */
    private array $envBackup;

    public static function setUpBeforeClass(): void
    {
        // Dossier temporaire dédié aux tests : isole les logs de la base applicative var/logs/
        self::$logsPath = sys_get_temp_dir() . '/edu-framework-http-logger-test/';
    }

    public function setUp(): void
    {
        $this->serverBackup = $_SERVER;
        $this->envBackup = $_ENV;

        // Réinitialisation de la connexion statique pour tester l'initialisation à chaque test
        $reflection = new ReflectionProperty(LogsService::class, 'dbConnectLogs');
        $reflection->setValue(null, null);

        (new ConfigCore([
            'sqlite_logs_path' => self::$logsPath
        ]));

        // L'enregistrement des requêtes est un outil du mode dev
        $_ENV['APP_ENV'] = 'dev';
    }

    public function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        $_ENV = $this->envBackup;
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$logsPath . 'core_logs.sqlite');
        @rmdir(self::$logsPath);
    }

    private function countLogs(): int
    {
        return (int) LogsService::getConnect()
            ->query('SELECT COUNT(*) FROM serv_logs')
            ->fetchColumn();
    }

    public function testBuildEventHasTheSameFormatAsTheBuiltInServerOutput(): void
    {
        $event = HttpRequestLogger::buildEvent('GET', '/', '127.0.0.1:65229', 200, 'cli-server');

        // La ligne brute doit être analysable par le même pipeline que le serveur intégré,
        // et produire exactement les mêmes informations, SAPI compris
        $parsed = (new BufferToServer($event['raw']))->getFormatBuffer();

        $this->assertSame($event['raw'], $parsed['raw']);
        $this->assertSame($event['timestamp'], $parsed['timestamp']);
        $this->assertSame($event['ip_port'], $parsed['ip_port']);
        $this->assertSame($event['status_code'], $parsed['status_code']);
        $this->assertSame($event['method'], $parsed['method']);
        $this->assertSame($event['path'], $parsed['path']);
        $this->assertSame($event['sapi'], $parsed['sapi']);
    }

    public function testBuildEventDefaultsToTheCurrentSapi(): void
    {
        $event = HttpRequestLogger::buildEvent('GET', '/', '127.0.0.1:65229', 200);

        // Par défaut, l'événement identifie le serveur qui sert la requête
        $this->assertSame(PHP_SAPI, $event['sapi']);
    }

    public function testBuildEventPadsTheDayLikeTheBuiltInServer(): void
    {
        // Le serveur intégré écrit le jour sur 2 caractères complété par un espace (ex : "Oct  5")
        $event = HttpRequestLogger::buildEvent('GET', '/', '127.0.0.1:65229', 200);

        $this->assertMatchesRegularExpression(
            '/^\[[A-Za-z]{3} [A-Za-z]{3} {1,2}\d{1,2} \d{2}:\d{2}:\d{2} \d{4}\] /',
            $event['raw']
        );
        $this->assertStringStartsWith('[' . $event['timestamp'] . '] ', $event['raw']);
    }

    public function testLogInsertsTheHttpRequestInDevMode(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/ville?nom=Nice';
        $_SERVER['REMOTE_ADDR'] = '10.1.2.3';
        $_SERVER['REMOTE_PORT'] = '4444';

        $countBefore = $this->countLogs();
        HttpRequestLogger::log();

        $this->assertSame($countBefore + 1, $this->countLogs());

        // Le log le plus récent (ORDER BY id DESC) est celui qui vient d'être enregistré
        $logs = LogsService::getLogs(1, 0);
        $event = $logs[0]['event'];

        $this->assertSame('POST', $event['method']);
        $this->assertSame('/ville?nom=Nice', $event['path']);
        $this->assertSame('10.1.2.3:4444', $event['ip_port']);
        // http_response_code() renvoie false en ligne de commande : statut par défaut 200
        $this->assertSame('200', $event['status_code']);
        $this->assertSame(PHP_SAPI, $event['sapi']);
        $this->assertSame(
            '[' . $event['timestamp'] . '] ' . $event['ip_port']
            . ' [' . $event['status_code'] . ']: ' . $event['method'] . ' ' . $event['path'],
            $event['raw']
        );
    }

    public function testLogsCanBeFilteredBySapi(): void
    {
        foreach (['frankenphp', 'cli-server'] as $sapi) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = '/sapi-filter-' . $sapi;
            HttpRequestLogger::log();

            // Le log le plus récent porte le SAPI courant de la requête journalisée
            $logs = LogsService::getLogs(1, 0, ['url' => '/sapi-filter-' . $sapi]);
            $this->assertSame(PHP_SAPI, $logs[0]['event']['sapi']);
        }

        // Le filtre sapi ne retourne que les requêtes du serveur correspondant
        LogsService::addLog((string) json_encode(
            HttpRequestLogger::buildEvent('GET', '/sapi-filter-explicit', '127.0.0.1:1111', 200, 'frankenphp')
        ));

        $filtered = LogsService::getLogs(50, 0, ['sapi' => 'frankenphp', 'search' => '/sapi-filter-explicit']);
        $this->assertCount(1, $filtered);
        $this->assertSame('frankenphp', $filtered[0]['event']['sapi']);
    }

    public function testLogDoesNothingWithoutHttpRequest(): void
    {
        unset($_SERVER['REQUEST_METHOD']);

        $countBefore = $this->countLogs();
        HttpRequestLogger::log();

        $this->assertSame($countBefore, $this->countLogs());
    }

    public function testLogDoesNothingOutsideDevMode(): void
    {
        $_ENV['APP_ENV'] = 'prod';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/prod-path';

        $countBefore = $this->countLogs();
        HttpRequestLogger::log();

        $this->assertSame($countBefore, $this->countLogs());
    }

    public function testLogDoesNothingForTheProfilerAndLogsRoutes(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Parité avec StartCommand : les routes internes ne sont pas journalisées
        foreach (['/_debug/profiler', '/_debug/autre', '/edu-logs', '/edu-logs?page=1'] as $uri) {
            $_SERVER['REQUEST_URI'] = $uri;

            $countBefore = $this->countLogs();
            HttpRequestLogger::log();

            $this->assertSame($countBefore, $this->countLogs(), "L'URI <" . $uri . "> ne doit pas être journalisée");
        }
    }
}

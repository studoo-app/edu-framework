<?php

namespace Core\Profiler;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Logs\HttpRequestLogger;
use Studoo\EduFramework\Core\Logs\LogsService;
use Studoo\EduFramework\Core\Profiler\CodeProfiler;
use Studoo\EduFramework\Core\Profiler\ProfilerDriverInterface;

/**
 * Tests du profiler de code : conditions d'activation (EDU_PROFILER, cookie,
 * paramètre ?_profile=1, token de la requête), enregistrement du profil et
 * robustesse face aux erreurs du driver
 */
class CodeProfilerTest extends TestCase
{
    private static string $logsPath;

    /**
     * Sauvegarde des variables modifiées par un test
     * @var array<string, mixed>
     */
    private array $backup = [];

    public static function setUpBeforeClass(): void
    {
        self::$logsPath = sys_get_temp_dir() . '/edu-framework-profiler-test/';
        if (is_dir(self::$logsPath) === false) {
            mkdir(self::$logsPath, 0755, true);
        }
    }

    protected function setUp(): void
    {
        (new ReflectionProperty(LogsService::class, 'dbConnectLogs'))->setValue(null, null);
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, null);
        CodeProfiler::reset();
        new ConfigCore(['sqlite_logs_path' => self::$logsPath]);
        $this->backup = [
            'env' => $_ENV['EDU_PROFILER'] ?? null,
            'cookie' => $_COOKIE[CodeProfiler::COOKIE] ?? null,
            'get' => $_GET['_profile'] ?? null,
            'error_log' => ini_get('error_log'),
        ];
        unset($_ENV['EDU_PROFILER'], $_COOKIE[CodeProfiler::COOKIE], $_GET['_profile']);
    }

    protected function tearDown(): void
    {
        CodeProfiler::reset();
        CodeProfiler::setDriver(null);
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, null);
        foreach (['env' => 'EDU_PROFILER'] as $key => $name) {
            if ($this->backup[$key] === null) {
                unset($_ENV[$name]);
            } else {
                $_ENV[$name] = $this->backup[$key];
            }
        }
        unset($_COOKIE[CodeProfiler::COOKIE], $_GET['_profile']);
        if ($this->backup['cookie'] !== null) {
            $_COOKIE[CodeProfiler::COOKIE] = $this->backup['cookie'];
        }
        if ($this->backup['get'] !== null) {
            $_GET['_profile'] = $this->backup['get'];
        }
        ini_set('error_log', (string) $this->backup['error_log']);
    }

    public static function tearDownAfterClass(): void
    {
        (new ReflectionProperty(LogsService::class, 'dbConnectLogs'))->setValue(null, null);
        if (is_file(self::$logsPath . 'core_logs.sqlite') === true) {
            unlink(self::$logsPath . 'core_logs.sqlite');
        }
        if (is_dir(self::$logsPath) === true) {
            rmdir(self::$logsPath);
        }
    }

    /**
     * Driver factice : compte les démarrages et retourne un profil fixe
     */
    private function fakeDriver(bool $available = true, bool $failOnStop = false): ProfilerDriverInterface
    {
        return new class ($available, $failOnStop) implements ProfilerDriverInterface {
            public int $starts = 0;

            public function __construct(private bool $available, private bool $failOnStop)
            {
            }

            public function name(): string
            {
                return 'fake';
            }

            public function isAvailable(): bool
            {
                return $this->available;
            }

            public function start(): void
            {
                $this->starts++;
            }

            public function stop(): array
            {
                if ($this->failOnStop === true) {
                    throw new \RuntimeException('panne du driver');
                }

                return [
                    'period_ms' => 0.1,
                    'samples' => 10,
                    'functions' => ['f' => ['file' => '/tmp/f.php', 'line' => 1, 'self' => 10, 'inclusive' => 10]],
                    'speedscope' => [],
                ];
            }
        };
    }

    /**
     * Simule une requête HTTP journalisée (token réservé par HttpRequestLogger)
     */
    private function withToken(int $token): void
    {
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, $token);
    }

    /**
     * Vérifie le mode par défaut et la validation de EDU_PROFILER
     */
    public function testModeDefaultsToTrigger(): void
    {
        $this->assertSame('trigger', CodeProfiler::mode());
        $_ENV['EDU_PROFILER'] = 'on';
        $this->assertSame('on', CodeProfiler::mode());
        $_ENV['EDU_PROFILER'] = 'nimporte';
        $this->assertSame('trigger', CodeProfiler::mode());
    }

    /**
     * Vérifie qu'en mode trigger la requête n'est profilée qu'avec le cookie ou ?_profile=1
     */
    public function testTriggerModeNeedsTheCookieOrTheQueryParameter(): void
    {
        $this->assertFalse(CodeProfiler::isRequested());
        $_COOKIE[CodeProfiler::COOKIE] = '1';
        $this->assertTrue(CodeProfiler::isRequested());
        unset($_COOKIE[CodeProfiler::COOKIE]);
        $_GET['_profile'] = '1';
        $this->assertTrue(CodeProfiler::isRequested());
        $_ENV['EDU_PROFILER'] = 'off';
        $this->assertFalse(CodeProfiler::isRequested());
    }

    /**
     * Vérifie qu'une requête profilée enregistre son profil, rattaché au token
     */
    public function testProfiledRequestSavesItsProfile(): void
    {
        $_ENV['EDU_PROFILER'] = 'on';
        $driver = $this->fakeDriver();
        CodeProfiler::setDriver($driver);
        $this->withToken(42);

        CodeProfiler::start();
        $this->assertTrue(CodeProfiler::isActive());
        CodeProfiler::stop();

        $this->assertFalse(CodeProfiler::isActive());
        $this->assertSame(1, $driver->starts);
        $profile = LogsService::getCodeProfile(42);
        $this->assertNotNull($profile);
        $this->assertSame('fake', $profile['driver']);
        $this->assertSame(10, $profile['samples']);
        $this->assertSame('f', $profile['top'][0]['name']);
    }

    /**
     * Vérifie qu'une requête sans token (CLI, hors mode dev, route du profiler) n'est pas profilée
     */
    public function testRequestWithoutTokenIsNotProfiled(): void
    {
        $_ENV['EDU_PROFILER'] = 'on';
        $driver = $this->fakeDriver();
        CodeProfiler::setDriver($driver);

        CodeProfiler::start();

        $this->assertFalse(CodeProfiler::isActive());
        $this->assertSame(0, $driver->starts);
    }

    /**
     * Vérifie que rien n'est démarré sans demande de profilage ou sans extension
     */
    public function testNothingStartsWhenNotRequestedOrUnavailable(): void
    {
        $this->withToken(43);
        $driver = $this->fakeDriver();
        CodeProfiler::setDriver($driver);
        CodeProfiler::start();
        $this->assertFalse(CodeProfiler::isActive());

        $_ENV['EDU_PROFILER'] = 'on';
        $unavailable = $this->fakeDriver(false);
        CodeProfiler::setDriver($unavailable);
        CodeProfiler::start();
        $this->assertFalse(CodeProfiler::isActive());
        $this->assertSame(0, $driver->starts + $unavailable->starts);
    }

    /**
     * Vérifie qu'une erreur du driver ne fait jamais échouer la requête
     */
    public function testDriverErrorDoesNotBreakTheRequest(): void
    {
        ini_set('error_log', '/dev/null');
        $_ENV['EDU_PROFILER'] = 'on';
        CodeProfiler::setDriver($this->fakeDriver(true, true));
        $this->withToken(44);

        CodeProfiler::start();
        CodeProfiler::stop();

        $this->assertFalse(CodeProfiler::isActive());
        $this->assertNull(LogsService::getCodeProfile(44));
    }
}

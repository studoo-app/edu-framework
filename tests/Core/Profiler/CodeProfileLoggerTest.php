<?php

namespace Core\Profiler;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Logs\HttpRequestLogger;
use Studoo\EduFramework\Core\Logs\LogsService;
use Studoo\EduFramework\Core\Profiler\CodeProfileLogger;

/**
 * Tests de l'enregistrement des profils de code : écriture SQLite, compression
 * des données, lecture par token, rétention des derniers profils et repère
 * "profiled" dans la liste des logs
 */
class CodeProfileLoggerTest extends TestCase
{
    private static string $logsPath;

    /**
     * Crée le dossier temporaire dédié aux logs avant l'ensemble des tests
     */
    public static function setUpBeforeClass(): void
    {
        self::$logsPath = sys_get_temp_dir() . '/edu-framework-profile-logger-test/';
        if (is_dir(self::$logsPath) === false) {
            mkdir(self::$logsPath, 0755, true);
        }
    }

    /**
     * Pointe les logs vers le dossier de test et vide la table des profils
     */
    protected function setUp(): void
    {
        (new ReflectionProperty(LogsService::class, 'dbConnectLogs'))->setValue(null, null);
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, null);
        new ConfigCore(['sqlite_logs_path' => self::$logsPath]);
        LogsService::getConnect()->exec('DELETE FROM serv_code_profiles');
    }

    /**
     * Supprime la base SQLite des logs et le dossier temporaire des tests
     */
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
     * @return array<string, mixed> Profil au format du driver Excimer
     */
    private function data(int $samples = 20): array
    {
        return [
            'period_ms' => 0.1,
            'samples' => $samples,
            'functions' => [
                'a' => ['file' => '/tmp/a.php', 'line' => 1, 'self' => $samples, 'inclusive' => $samples],
                'b' => ['file' => '/tmp/b.php', 'line' => 2, 'self' => 0, 'inclusive' => $samples],
            ],
            'speedscope' => ['shared' => ['frames' => [['name' => 'b'], ['name' => 'a']]], 'profiles' => [['samples' => [[0, 1]], 'weights' => [100000]]]],
        ];
    }

    /**
     * Vérifie l'écriture puis la lecture d'un profil, résumé et données complètes
     */
    public function testSavedProfileCanBeReadBack(): void
    {
        CodeProfileLogger::save(5, 'excimer', 2.5, 4.0, $this->data());

        $summary = LogsService::getCodeProfile(5);
        $this->assertSame('excimer', $summary['driver']);
        $this->assertEquals(2.5, $summary['wall_ms']);
        $this->assertSame(20, $summary['samples']);
        $this->assertSame(2, $summary['functions_count']);
        // Seules les fonctions avec du temps propre sont dans le top
        $this->assertCount(1, $summary['top']);
        $this->assertArrayNotHasKey('data', $summary);

        $full = LogsService::getCodeProfile(5, true);
        $this->assertSame($this->data(), $full['data']);
    }

    /**
     * Vérifie qu'un profil corrompu est lu sans erreur, avec des données vides
     */
    public function testCorruptedProfileDataIsReadAsEmpty(): void
    {
        CodeProfileLogger::save(6, 'excimer', 2.5, 4.0, $this->data());
        LogsService::getConnect()->exec("UPDATE serv_code_profiles SET data = 'pas du gzip' WHERE request_id = 6");

        $errorLog = ini_get('error_log');
        ini_set('error_log', '/dev/null');
        try {
            $full = LogsService::getCodeProfile(6, true);
        } finally {
            ini_set('error_log', (string) $errorLog);
        }

        $this->assertSame('excimer', $full['driver']);
        $this->assertSame([], $full['data']);
    }

    /**
     * Vérifie qu'un token sans profil retourne null
     */
    public function testUnknownTokenHasNoProfile(): void
    {
        $this->assertNull(LogsService::getCodeProfile(999));
    }

    /**
     * Vérifie que seuls les derniers profils sont conservés
     */
    public function testPurgeKeepsTheLatestProfiles(): void
    {
        foreach ([1, 2, 3, 4] as $token) {
            CodeProfileLogger::save($token, 'excimer', 1.0, 1.0, $this->data());
        }

        CodeProfileLogger::purge(2);

        $this->assertNull(LogsService::getCodeProfile(1));
        $this->assertNull(LogsService::getCodeProfile(2));
        $this->assertNotNull(LogsService::getCodeProfile(3));
        $this->assertNotNull(LogsService::getCodeProfile(4));
    }

    /**
     * Vérifie que la liste des logs signale les requêtes qui ont un profil de code
     */
    public function testLogsListFlagsProfiledRequests(): void
    {
        $profiledId = LogsService::addLog('{"method":"GET","path":"\/profilee"}');
        $plainId = LogsService::addLog('{"method":"GET","path":"\/simple"}');
        CodeProfileLogger::save($profiledId, 'excimer', 1.0, 1.0, $this->data());

        $flags = [];
        foreach (LogsService::getLogs(10) as $log) {
            $flags[$log['id']] = $log['profiled'];
        }

        $this->assertTrue($flags[$profiledId]);
        $this->assertFalse($flags[$plainId]);
    }
}

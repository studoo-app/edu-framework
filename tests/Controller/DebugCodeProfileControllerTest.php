<?php

namespace Controller;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\Debug\DebugCodeProfileController;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Logs\LogsService;
use Studoo\EduFramework\Core\Profiler\CodeProfileLogger;

/**
 * Tests de l'endpoint /edu-code-profile : profil de code d'une requête HTTP
 * (résumé, détail complet, export Speedscope)
 */
class DebugCodeProfileControllerTest extends TestCase
{
    private static string $logsPath;

    /**
     * Crée le dossier temporaire dédié aux logs avant l'ensemble des tests
     */
    public static function setUpBeforeClass(): void
    {
        self::$logsPath = sys_get_temp_dir() . '/edu-framework-code-endpoint-test/';
        if (is_dir(self::$logsPath) === false) {
            mkdir(self::$logsPath, 0755, true);
        }
    }

    /**
     * Pointe les logs vers le dossier de test
     */
    public function setUp(): void
    {
        (new ReflectionProperty(LogsService::class, 'dbConnectLogs'))->setValue(null, null);
        new ConfigCore([
            'sqlite_logs_path' => self::$logsPath,
            'base_path' => '/projet/',
        ]);
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
     * Exécute le contrôleur avec les paramètres GET donnés
     */
    private function runController(array $params): mixed
    {
        $request = new Request('/edu-code-profile', 'GET');
        $request->setVars($params);

        return json_decode((string) (new DebugCodeProfileController())->execute($request), true);
    }

    /**
     * Profil Excimer simplifié : main() appelle work() (2 échantillons dans work, 1 dans main)
     */
    private function saveProfile(int $token): void
    {
        $data = [
            'period_ms' => 0.1,
            'samples' => 3,
            'functions' => [
                'work' => ['self' => 2, 'inclusive' => 2, 'file' => '/projet/app/Work.php', 'line' => 10, 'function' => 'work'],
                'main' => ['self' => 1, 'inclusive' => 3, 'file' => '/projet/public/index.php', 'line' => 3, 'function' => 'main'],
            ],
            'speedscope' => [
                'shared' => ['frames' => [['name' => 'main'], ['name' => 'work']]],
                'profiles' => [[
                    'type' => 'sampled',
                    'unit' => 'nanoseconds',
                    'samples' => [[0, 1], [0, 1], [0]],
                    'weights' => [100000, 100000, 100000],
                ]],
            ],
        ];
        CodeProfileLogger::save($token, 'excimer', 1.5, 2.0, $data);
    }

    /**
     * Un token invalide retourne une réponse vide
     */
    public function testInvalidTokenReturnsAnEmptyResponse(): void
    {
        foreach (['', 'abc', '7.5', '-1', '99999999999999999999'] as $token) {
            $response = $this->runController(['token' => $token]);

            $this->assertNull($response['token'], 'Le token <' . $token . '> ne doit pas être accepté');
            $this->assertSame('pending', $response['status']);
            $this->assertNull($response['summary']);
            $this->assertSame([], $response['top']);
        }
    }

    /**
     * Un token sans profil enregistré est en attente (la page s'affiche avant la fin de la requête)
     */
    public function testUnknownTokenIsPending(): void
    {
        $response = $this->runController(['token' => '#404']);

        $this->assertSame(404, $response['token']);
        $this->assertSame('pending', $response['status']);
    }

    /**
     * Un profil enregistré retourne le résumé et le top des fonctions
     */
    public function testSavedProfileReturnsTheSummaryAndTop(): void
    {
        $this->saveProfile(21);

        $response = $this->runController(['token' => '21']);

        $this->assertSame('ready', $response['status']);
        $this->assertSame('excimer', $response['summary']['driver']);
        $this->assertSame(3, $response['summary']['samples']);
        $this->assertSame(2, $response['summary']['functions_count']);
        $this->assertSame('work', $response['top'][0]['name']);
        $this->assertArrayNotHasKey('functions', $response);
        $this->assertArrayNotHasKey('edges', $response);
    }

    /**
     * full=1 ajoute toutes les fonctions et le graphe d'appels
     */
    public function testFullProfileAddsFunctionsAndEdges(): void
    {
        $this->saveProfile(22);

        $response = $this->runController(['token' => '22', 'full' => '1']);

        $this->assertCount(2, $response['functions']);
        $this->assertSame('app', $response['functions'][0]['origin']);
        $this->assertSame(
            [['caller' => 'main', 'callee' => 'work', 'samples' => 2]],
            array_map(
                static fn (array $edge): array => [
                    'caller' => $edge['caller'], 'callee' => $edge['callee'], 'samples' => $edge['samples'],
                ],
                $response['edges']
            )
        );
    }

    /**
     * format=speedscope retourne les piles d'appels au format Speedscope
     */
    public function testSpeedscopeExport(): void
    {
        $this->saveProfile(23);

        $response = $this->runController(['token' => '23', 'format' => 'speedscope']);

        $this->assertSame('Edu Framework #23', $response['name']);
        $this->assertSame('Requête #23', $response['profiles'][0]['name']);
        $this->assertSame([[0, 1], [0, 1], [0]], $response['profiles'][0]['samples']);
    }
}

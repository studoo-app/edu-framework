<?php

namespace Core\Profiler;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\Profiler\ExcimerDriver;

/**
 * Test d'intégration du driver Excimer : profile réellement une boucle.
 * Ignoré si l'extension Excimer n'est pas installée.
 */
class ExcimerDriverTest extends TestCase
{
    /**
     * Ignore les tests si l'extension Excimer n'est pas installée
     */
    protected function setUp(): void
    {
        if (\extension_loaded('excimer') === false) {
            $this->markTestSkipped('Extension excimer non installée');
        }
    }

    /**
     * Vérifie qu'un vrai profil contient des échantillons, la fonction profilée
     * et les piles d'appels au format Speedscope
     */
    public function testProfilesARealWorkload(): void
    {
        $driver = new ExcimerDriver();
        $this->assertTrue($driver->isAvailable());

        $driver->start();
        $this->busyLoop();
        $data = $driver->stop();

        $this->assertEquals(0.1, $data['period_ms']);
        $this->assertGreaterThan(0, $data['samples']);
        $this->assertArrayHasKey(self::class . '::busyLoop', $data['functions']);
        $this->assertArrayHasKey('profiles', $data['speedscope']);
    }

    /**
     * Vérifie que deux requêtes successives ont des profils séparés (mode worker)
     */
    public function testEachStopReturnsOnlyItsOwnSamples(): void
    {
        $driver = new ExcimerDriver();
        $driver->start();
        $this->busyLoop();
        $driver->stop();

        $driver->start();
        $second = $driver->stop();

        $this->assertArrayNotHasKey(self::class . '::busyLoop', $second['functions']);
    }

    /**
     * Charge CPU d'environ 20 ms, assez pour plusieurs échantillons à 0,1 ms
     */
    private function busyLoop(): void
    {
        $end = microtime(true) + 0.02;
        $x = 0;
        while (microtime(true) < $end) {
            $x += sqrt($x + 1);
        }
    }
}

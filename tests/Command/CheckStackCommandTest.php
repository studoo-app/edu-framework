<?php

namespace Command;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Commands\CheckStackCommand;
use Studoo\EduFramework\Core\Cache\CacheService;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests de la commande check:config (prérequis PHP et cache)
 */
class CheckStackCommandTest extends TestCase
{
    /**
     * Testeur de la commande check:config
     * @var CommandTester|null
     */
    private ?CommandTester $commandeTester;

    /**
     * Prépare la commande check:config avec la configuration par défaut du cache
     * @return void
     */
    protected function setUp(): void
    {
        unset($_ENV['CACHE_DRIVER'], $_ENV['REDIS_HOST'], $_ENV['REDIS_PORT']);
        CacheService::setCache(null);
        (new ConfigCore([]));
        $application = new Application(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));
        $application->addCommand(new CheckStackCommand());
        $command = $application->find("check:config");
        $this->commandeTester = new CommandTester($command);
    }

    /**
     * Remet à zéro les variables du cache et le cache partagé
     * @return void
     */
    protected function tearDown(): void
    {
        unset($_ENV['CACHE_DRIVER'], $_ENV['REDIS_HOST'], $_ENV['REDIS_PORT']);
        CacheService::setCache(null);
        $this->commandeTester = null;
    }

    /**
     * La section des prérequis est affichée
     * @return void
     */
    public function testCommandPrerequis(): void
    {
        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('Check des prérequis', $output);
    }

    /**
     * L'extension PDO_MYSQL est contrôlée
     * @return void
     */
    public function testCommandPdo(): void
    {
        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('PDO_MYSQL', $output);
    }

    /**
     * L'extension OPENSSL est contrôlée
     * @return void
     */
    public function testCommandOpenSsl(): void
    {
        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('OPENSSL', $output);
    }

    /**
     * L'extension JSON est contrôlée
     * @return void
     */
    public function testCommandJson(): void
    {
        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('JSON', $output);
    }

    /**
     * L'extension MBSTRING est contrôlée
     * @return void
     */
    public function testCommandMbString(): void
    {
        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('MBSTRING', $output);
    }

    /**
     * Le driver du cache est affiché (fichier par défaut)
     * @return void
     */
    public function testCommandCacheDriver(): void
    {
        $this->commandeTester->execute([]);

        $this->assertStringContainsString('Driver du cache : file', $this->commandeTester->getDisplay());
    }

    /**
     * Un serveur Redis injoignable est signalé avec la marche à suivre, sans faire échouer la commande
     * @return void
     */
    public function testCommandRedisInjoignable(): void
    {
        $_ENV['CACHE_DRIVER'] = 'redis';
        $_ENV['REDIS_HOST'] = '127.0.0.1';
        $_ENV['REDIS_PORT'] = '1';

        $this->assertSame(0, $this->commandeTester->execute([]));
        $output = $this->commandeTester->getDisplay();
        $this->assertStringContainsString('[X] Serveur Redis 127.0.0.1:1 injoignable', $output);
        $this->assertStringContainsString('CACHE_DRIVER=file', $output);
    }

    /**
     * Un serveur Redis joignable est signalé avec sa version
     * Serveur utilisé : EDU_TEST_REDIS_HOST / EDU_TEST_REDIS_PORT (test ignoré s'il est injoignable)
     * @return void
     */
    public function testCommandRedisJoignable(): void
    {
        $_ENV['CACHE_DRIVER'] = 'redis';
        $_ENV['REDIS_HOST'] = getenv('EDU_TEST_REDIS_HOST') ?: '127.0.0.1';
        $_ENV['REDIS_PORT'] = getenv('EDU_TEST_REDIS_PORT') ?: '6379';

        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();
        if (str_contains($output, 'injoignable')) {
            $this->markTestSkipped('Serveur Redis de test injoignable');
        }
        $this->assertMatchesRegularExpression('/\[\*\] Serveur Redis .+ joignable \(version \d+\.\d+/', $output);
    }
}

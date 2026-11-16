<?php

namespace Command;

use PHPUnit\Framework\TestCase;
use Predis\Client;
use Predis\PredisException;
use Studoo\EduFramework\Commands\ClearCacheCommand;
use Studoo\EduFramework\Core\Cache\CacheService;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class ClearCacheCommandTest extends TestCase
{
    /**
     * Dossier de cache isolé pour ne pas toucher au vrai dossier var/cache
     * @var string
     */
    private string $cachePath;

    private $commandeTester;

    /**
     * Variables d'environnement du cache modifiées par les tests
     */
    private const CACHE_KEYS = ['APP_ENV', 'CACHE_DRIVER', 'CACHE_PREFIX', 'REDIS_HOST', 'REDIS_PORT'];

    protected function setUp(): void
    {
        foreach (self::CACHE_KEYS as $key) {
            unset($_ENV[$key]);
        }
        CacheService::setCache(null);

        $this->cachePath = sys_get_temp_dir() . '/edu-framework-test-cache-' . uniqid() . '/';

        // Simulation du contenu d'un dossier de cache (Exemple: le cache TWIG)
        mkdir($this->cachePath . 'twig', 0777, true);
        file_put_contents($this->cachePath . 'twig/template.html.php', 'cache compile');
        file_put_contents($this->cachePath . 'fichier-test.txt', 'cache');

        (new ConfigCore(['cache_path' => $this->cachePath]));
        $application = new Application(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));
        $application->addCommand(new ClearCacheCommand());
        $command = $application->find('cache:clear');
        $this->commandeTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        // Nettoyage du dossier de cache temporaire
        (new Filesystem())->remove($this->cachePath);
        $this->commandeTester = null;
        foreach (self::CACHE_KEYS as $key) {
            unset($_ENV[$key]);
        }
        CacheService::setCache(null);
    }

    public function testCommandClearCache(): void
    {
        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('Suppression du cache', $output);
        $this->assertStringContainsString('Le cache a été supprimé', $output);

        // Le contenu du dossier de cache est supprimé
        $this->assertFileDoesNotExist($this->cachePath . 'fichier-test.txt');
        $this->assertFileDoesNotExist($this->cachePath . 'twig/template.html.php');

        // Le dossier de cache lui-même est conservé
        $this->assertDirectoryExists($this->cachePath);
    }

    public function testCommandClearCacheWithDirNotExist(): void
    {
        // Le dossier de cache n'existe pas
        (new ConfigCore(['cache_path' => sys_get_temp_dir() . '/edu-framework-test-cache-inexistant-' . uniqid() . '/']));

        $application = new Application(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));
        $application->addCommand(new ClearCacheCommand());
        $command = $application->find('cache:clear');
        $commandeTester = new CommandTester($command);

        $commandeTester->execute([]);
        $output = $commandeTester->getDisplay();

        $this->assertStringContainsString('Aucun cache à supprimer', $output);
    }

    /**
     * Par défaut (CACHE_DRIVER=file), le cache applicatif est vidé avec le dossier var/cache
     */
    public function testCommandClearCacheVideLeCacheApplicatifFichier(): void
    {
        CacheService::getCache()->set('ville:list', ['Paris']);
        $this->assertTrue(CacheService::getCache()->has('ville:list'));

        $this->assertSame(Command::SUCCESS, $this->commandeTester->execute([]));

        $this->assertFalse(CacheService::getCache()->has('ville:list'));
        $this->assertStringContainsString('Le cache applicatif a été vidé (driver file)', $this->commandeTester->getDisplay());
    }

    /**
     * --files-only ne touche pas au cache applicatif Redis (message absent)
     */
    public function testCommandFilesOnly(): void
    {
        $this->assertSame(Command::SUCCESS, $this->commandeTester->execute(['--files-only' => true]));

        $output = $this->commandeTester->getDisplay();
        $this->assertStringContainsString('Le cache a été supprimé', $output);
        $this->assertStringNotContainsString('cache applicatif', $output);
    }

    /**
     * --app-only conserve le dossier var/cache (cache TWIG)
     */
    public function testCommandAppOnly(): void
    {
        $this->assertSame(Command::SUCCESS, $this->commandeTester->execute(['--app-only' => true]));

        $this->assertFileExists($this->cachePath . 'twig/template.html.php');
        $this->assertStringContainsString('Le cache applicatif a été vidé', $this->commandeTester->getDisplay());
    }

    /**
     * Les deux options ensemble sont refusées
     */
    public function testCommandOptionsIncompatibles(): void
    {
        $this->assertSame(Command::FAILURE, $this->commandeTester->execute(['--app-only' => true, '--files-only' => true]));
        $this->assertFileExists($this->cachePath . 'fichier-test.txt');
    }

    /**
     * Cache désactivé (CACHE_DRIVER=none) : rien à vider
     */
    public function testCommandCacheDesactive(): void
    {
        $_ENV['CACHE_DRIVER'] = 'none';

        $this->assertSame(Command::SUCCESS, $this->commandeTester->execute([]));
        $this->assertStringContainsString('rien à vider', $this->commandeTester->getDisplay());
    }

    /**
     * Redis arrêté : avertissement sans échec par défaut (le dossier var/cache est quand même vidé),
     * échec avec --app-only
     */
    public function testCommandRedisInjoignable(): void
    {
        $_ENV['APP_ENV'] = 'dev';
        $_ENV['CACHE_DRIVER'] = 'redis';
        $_ENV['REDIS_HOST'] = '127.0.0.1';
        $_ENV['REDIS_PORT'] = '1';

        $this->assertSame(Command::SUCCESS, $this->commandeTester->execute([]));
        $this->assertStringContainsString('cache applicatif n\'a pas été vidé', $this->commandeTester->getDisplay());
        $this->assertFileDoesNotExist($this->cachePath . 'fichier-test.txt');

        CacheService::setCache(null);
        $this->assertSame(Command::FAILURE, $this->commandeTester->execute(['--app-only' => true]));
    }

    /**
     * Redis joignable : seules les clés du préfixe CACHE_PREFIX sont supprimées
     * Serveur utilisé : EDU_TEST_REDIS_HOST / EDU_TEST_REDIS_PORT (test ignoré s'il est injoignable)
     */
    public function testCommandRedisVideLesClesDuPrefixe(): void
    {
        $host = getenv('EDU_TEST_REDIS_HOST') ?: '127.0.0.1';
        $port = getenv('EDU_TEST_REDIS_PORT') ?: '6379';
        $prefix = 'edu-test-clear-' . uniqid() . ':';
        $raw = new Client(['host' => $host, 'port' => (int) $port, 'timeout' => 0.5]);
        try {
            $raw->ping();
        } catch (PredisException) {
            $this->markTestSkipped('Serveur Redis de test injoignable');
        }

        $_ENV['CACHE_DRIVER'] = 'redis';
        $_ENV['CACHE_PREFIX'] = $prefix;
        $_ENV['REDIS_HOST'] = $host;
        $_ENV['REDIS_PORT'] = $port;
        $raw->set($prefix . 'ville:list', '[]');
        $raw->set('edu-test-clear-autre-application', 'conservée');

        try {
            $this->assertSame(Command::SUCCESS, $this->commandeTester->execute(['--app-only' => true]));
            $this->assertSame(0, $raw->exists($prefix . 'ville:list'));
            $this->assertSame('conservée', $raw->get('edu-test-clear-autre-application'));
            $this->assertStringContainsString('clés ' . $prefix . '*', $this->commandeTester->getDisplay());
        } finally {
            $raw->del(['edu-test-clear-autre-application', $prefix . 'ville:list']);
        }
    }
}

<?php

namespace Command;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Commands\ClearCacheCommand;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Application;
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

    protected function setUp(): void
    {
        $this->cachePath = sys_get_temp_dir() . '/edu-framework-test-cache-' . uniqid() . '/';

        // Simulation du contenu d'un dossier de cache (Exemple: le cache TWIG)
        mkdir($this->cachePath . 'twig', 0777, true);
        file_put_contents($this->cachePath . 'twig/template.html.php', 'cache compile');
        file_put_contents($this->cachePath . 'fichier-test.txt', 'cache');

        (new ConfigCore(['cache_path' => $this->cachePath]));
        $application = new Application(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));
        $application->add(new ClearCacheCommand());
        $command = $application->find('cache:clear');
        $this->commandeTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        // Nettoyage du dossier de cache temporaire
        (new Filesystem())->remove($this->cachePath);
        $this->commandeTester = null;
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
        $application->add(new ClearCacheCommand());
        $command = $application->find('cache:clear');
        $commandeTester = new CommandTester($command);

        $commandeTester->execute([]);
        $output = $commandeTester->getDisplay();

        $this->assertStringContainsString('Aucun cache à supprimer', $output);
    }
}

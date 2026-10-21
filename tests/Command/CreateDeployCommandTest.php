<?php

namespace Command;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Commands\CreateDeployCommand;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests de la commande make:deploy (génération du fichier frankendeploy.yaml)
 */
class CreateDeployCommandTest extends TestCase
{
    /**
     * Projet isolé pour ne pas écrire de frankendeploy.yaml dans le dépôt
     * @var string
     */
    private string $projectPath;

    private ?CommandTester $commandeTester;

    /**
     * Crée un projet temporaire isolé et la commande à tester
     * @return void
     */
    protected function setUp(): void
    {
        $this->projectPath = sys_get_temp_dir() . '/edu-framework-test-deploy-' . uniqid() . '/Mon_Projet/';
        mkdir($this->projectPath, 0700, true);

        (new ConfigCore(['base_path' => $this->projectPath]));
        $application = new Application(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));
        $application->add(new CreateDeployCommand());
        $this->commandeTester = new CommandTester($application->find('make:deploy'));
    }

    /**
     * Supprime le projet temporaire
     * @return void
     */
    protected function tearDown(): void
    {
        (new Filesystem())->remove(dirname($this->projectPath));
        $this->commandeTester = null;
    }

    /**
     * Génération avec les valeurs par défaut : nom du dossier, MariaDB gérée, dossiers partagés
     * @return void
     */
    public function testGenerationParDefaut(): void
    {
        $this->assertEquals(Command::SUCCESS, $this->commandeTester->execute(['--domain' => 'mon-app.fr']));

        $config = Yaml::parseFile($this->projectPath . 'frankendeploy.yaml');
        // Le nom par défaut est celui du dossier, au format FrankenDeploy
        $this->assertEquals('mon-projet', $config['name']);
        $this->assertEquals('8.4', $config['php']['version']);
        $this->assertEquals('mariadb', $config['database']['driver']);
        $this->assertTrue($config['database']['managed']);
        $this->assertEquals('mon-app.fr', $config['deploy']['domain']);
        $this->assertContains('.env.local', $config['deploy']['shared_files']);
        $this->assertContains('var/logs', $config['deploy']['shared_dirs']);
        $this->assertContains('public/upload', $config['deploy']['shared_dirs']);
    }

    /**
     * Sans les fichiers Docker du framework, un avertissement indique où les récupérer
     * @return void
     */
    public function testFichiersDockerManquants(): void
    {
        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();
        $this->assertStringContainsString('Fichiers manquants', $output);
        // Le message indique où récupérer les fichiers Docker du framework
        $this->assertStringContainsString('vendor/studoo/edu-framework/', $output);
    }

    /**
     * Un Dockerfile sans stage frankenphp_prod déclenche un avertissement
     * @return void
     */
    public function testDockerfileSansStageProd(): void
    {
        foreach (['Dockerfile', 'docker-entrypoint.sh', 'Caddyfile', '.dockerignore'] as $file) {
            file_put_contents($this->projectPath . $file, '');
        }
        file_put_contents($this->projectPath . 'Dockerfile', "FROM dunglas/frankenphp:php8.4\n");

        $this->commandeTester->execute([]);
        $this->assertStringContainsString('frankenphp_prod', $this->commandeTester->getDisplay());
    }

    /**
     * Dans le conteneur de développement (/app), l'option --name est obligatoire
     * @return void
     */
    public function testNomRequisDansLeConteneur(): void
    {
        // Dans le conteneur de développement, le projet est monté dans /app :
        // le nom "app" ne doit pas être déduit du dossier
        $appPath = dirname($this->projectPath) . '/app/';
        mkdir($appPath, 0700);
        (new ConfigCore(['base_path' => $appPath]));

        $this->assertEquals(Command::FAILURE, $this->commandeTester->execute([]));
        $this->assertStringContainsString('--name', $this->commandeTester->getDisplay());
        $this->assertFileDoesNotExist($appPath . 'frankendeploy.yaml');

        $this->assertEquals(Command::SUCCESS, $this->commandeTester->execute(['--name' => 'mon-app']));
        $this->assertEquals('mon-app', Yaml::parseFile($appPath . 'frankendeploy.yaml')['name']);
    }

    /**
     * Sans domaine ni base de données : aucune clé database ni domain générée
     * @return void
     */
    public function testSansDomaineNiBase(): void
    {
        $this->commandeTester->execute(['--name' => 'demo', '--database' => 'none']);

        $config = Yaml::parseFile($this->projectPath . 'frankendeploy.yaml');
        $this->assertEquals('demo', $config['name']);
        $this->assertArrayNotHasKey('database', $config);
        $this->assertArrayNotHasKey('domain', $config['deploy']);
    }

    /**
     * Base SQLite : chemin dans var/sqlite et une commande env set par variable
     * @return void
     */
    public function testSqlite(): void
    {
        $this->commandeTester->execute(['--database' => 'sqlite']);

        $config = Yaml::parseFile($this->projectPath . 'frankendeploy.yaml');
        $this->assertEquals('sqlite', $config['database']['driver']);
        $this->assertStringStartsWith('var/sqlite/', $config['database']['path']);

        // frankendeploy env set n'accepte qu'une variable par commande
        $content = file_get_contents($this->projectPath . 'frankendeploy.yaml');
        $this->assertDoesNotMatchRegularExpression('/env set prod \S+=\S+ \S+=/', $content);
        $this->assertStringContainsString('env set prod DB_TYPE=sqlite', $content);
    }

    /**
     * Une base de données inconnue est refusée sans générer de fichier
     * @return void
     */
    public function testBaseInconnue(): void
    {
        $this->assertEquals(Command::FAILURE, $this->commandeTester->execute(['--database' => 'oracle']));
        $this->assertFileDoesNotExist($this->projectPath . 'frankendeploy.yaml');
    }

    /**
     * Un nom de domaine invalide est refusé
     * @return void
     */
    public function testDomaineInvalide(): void
    {
        $this->assertEquals(Command::FAILURE, $this->commandeTester->execute(['--domain' => 'pas un domaine']));
    }

    /**
     * Un frankendeploy.yaml existant n'est écrasé qu'avec --force
     * @return void
     */
    public function testFichierExistant(): void
    {
        file_put_contents($this->projectPath . 'frankendeploy.yaml', "name: existant\n");

        $this->assertEquals(Command::FAILURE, $this->commandeTester->execute([]));
        $this->assertEquals("name: existant\n", file_get_contents($this->projectPath . 'frankendeploy.yaml'));

        $this->assertEquals(Command::SUCCESS, $this->commandeTester->execute(['--force' => true]));
        $this->assertStringContainsString('mon-projet', file_get_contents($this->projectPath . 'frankendeploy.yaml'));
    }
}

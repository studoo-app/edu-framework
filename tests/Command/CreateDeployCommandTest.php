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

class CreateDeployCommandTest extends TestCase
{
    /**
     * Projet isolé pour ne pas écrire de frankendeploy.yaml dans le dépôt
     * @var string
     */
    private string $projectPath;

    private ?CommandTester $commandeTester;

    protected function setUp(): void
    {
        $this->projectPath = sys_get_temp_dir() . '/edu-framework-test-deploy-' . uniqid() . '/Mon_Projet/';
        mkdir($this->projectPath, 0777, true);

        (new ConfigCore(['base_path' => $this->projectPath]));
        $application = new Application(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));
        $application->add(new CreateDeployCommand());
        $this->commandeTester = new CommandTester($application->find('make:deploy'));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(dirname($this->projectPath));
        $this->commandeTester = null;
    }

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

    public function testFichiersDockerManquants(): void
    {
        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();
        $this->assertStringContainsString('Fichiers manquants', $output);
        // Le message indique où récupérer les fichiers Docker du framework
        $this->assertStringContainsString('vendor/studoo/edu-framework/', $output);
    }

    public function testDockerfileSansStageProd(): void
    {
        foreach (['Dockerfile', 'docker-entrypoint.sh', 'Caddyfile', '.dockerignore'] as $file) {
            file_put_contents($this->projectPath . $file, '');
        }
        file_put_contents($this->projectPath . 'Dockerfile', "FROM dunglas/frankenphp:php8.4\n");

        $this->commandeTester->execute([]);
        $this->assertStringContainsString('frankenphp_prod', $this->commandeTester->getDisplay());
    }

    public function testNomRequisDansLeConteneur(): void
    {
        // Dans le conteneur de développement, le projet est monté dans /app :
        // le nom "app" ne doit pas être déduit du dossier
        $appPath = dirname($this->projectPath) . '/app/';
        mkdir($appPath);
        (new ConfigCore(['base_path' => $appPath]));

        $this->assertEquals(Command::FAILURE, $this->commandeTester->execute([]));
        $this->assertStringContainsString('--name', $this->commandeTester->getDisplay());
        $this->assertFileDoesNotExist($appPath . 'frankendeploy.yaml');

        $this->assertEquals(Command::SUCCESS, $this->commandeTester->execute(['--name' => 'mon-app']));
        $this->assertEquals('mon-app', Yaml::parseFile($appPath . 'frankendeploy.yaml')['name']);
    }

    public function testSansDomaineNiBase(): void
    {
        $this->commandeTester->execute(['--name' => 'demo', '--database' => 'none']);

        $config = Yaml::parseFile($this->projectPath . 'frankendeploy.yaml');
        $this->assertEquals('demo', $config['name']);
        $this->assertArrayNotHasKey('database', $config);
        $this->assertArrayNotHasKey('domain', $config['deploy']);
    }

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

    public function testBaseInconnue(): void
    {
        $this->assertEquals(Command::FAILURE, $this->commandeTester->execute(['--database' => 'oracle']));
        $this->assertFileDoesNotExist($this->projectPath . 'frankendeploy.yaml');
    }

    public function testDomaineInvalide(): void
    {
        $this->assertEquals(Command::FAILURE, $this->commandeTester->execute(['--domain' => 'pas un domaine']));
    }

    public function testFichierExistant(): void
    {
        file_put_contents($this->projectPath . 'frankendeploy.yaml', "name: existant\n");

        $this->assertEquals(Command::FAILURE, $this->commandeTester->execute([]));
        $this->assertEquals("name: existant\n", file_get_contents($this->projectPath . 'frankendeploy.yaml'));

        $this->assertEquals(Command::SUCCESS, $this->commandeTester->execute(['--force' => true]));
        $this->assertStringContainsString('mon-projet', file_get_contents($this->projectPath . 'frankendeploy.yaml'));
    }
}

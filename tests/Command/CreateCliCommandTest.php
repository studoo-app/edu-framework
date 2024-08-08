<?php

namespace Command;

use PHPUnit\Framework\TestCase;

use Studoo\EduFramework\Commands\CreateCliCommand;
use Studoo\EduFramework\Commands\Exception\CommandAlreadyExistsException;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class CreateCliCommandTest extends TestCase
{
    private $commandeTester;

    protected function setUp(): void
    {
        (new ConfigCore([]));
        $application = new Application(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));
        $application->add(new CreateCliCommand());
        $command = $application->find("make:command");
        $this->commandeTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->commandeTester = null;
    }
    public static function tearDownAfterClass(): void
    {
        // Code de nettoyage à exécuter après tous les tests de la classe
        (new Filesystem())->remove("app/Config/commands.yaml");
        (new Filesystem())->copy("tests/Config/commands.yaml", "app/Config/commands.yaml");
        (new Filesystem())->remove("app/Command/InitCommand.php");
    }

    public function testCreateNewApi(): void
    {
        $this->commandeTester->execute(["command-name" => "init"]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('[OK] Command successfully generated', $output);
    }

    public function testRouteAlreadyExists(): void
    {
        $this->expectException(CommandAlreadyExistsException::class);
        $this->commandeTester->execute(["command-name" => "init"]);
        $this->assertStringContainsString('Route already exists', $this->commandeTester->getDisplay());
    }

    public function testExistFileInitCommand()
    {
        $this->assertTrue(file_exists("app/Command/InitCommand.php"));
    }

    public function testExistFileCommandsYaml()
    {
        $this->assertTrue(file_exists("app/Config/commands.yaml"));
    }

    public function testExistFileCommandsYamlContent()
    {
        $this->assertStringContainsString(
            "init: Command\InitCommand",
            file_get_contents("app/Config/commands.yaml")
        );
    }
}

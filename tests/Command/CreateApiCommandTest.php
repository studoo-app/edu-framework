<?php

namespace Command;

use PHPUnit\Framework\TestCase;

use Studoo\EduFramework\Commands\CreateApiCommand;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class CreateApiCommandTest extends TestCase
{
    private $commandeTester;

    protected function setUp(): void
    {
        (new ConfigCore([]));
        $application = new Application(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));
        $application->add(new CreateApiCommand());
        $command = $application->find("make:api");
        $this->commandeTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->commandeTester = null;
        (new Filesystem())->remove("app/Config/routes.yaml");
        (new Filesystem())->copy("tests/Config/routes.yaml", "app/Config/routes.yaml");
        (new Filesystem())->remove("app/Controller/api/InitController.php");
        (new Filesystem())->remove("app/Controller/api/openapi.php");
    }

    public function testCommandUri(): void
    {
        $this->commandeTester->execute(["controller-name" => "init"]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('URI : /api/init', $output);
    }
}

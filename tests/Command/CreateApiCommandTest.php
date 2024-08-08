<?php

namespace Command;

use PHPUnit\Framework\TestCase;

use Studoo\EduFramework\Commands\CreateApiCommand;
use Studoo\EduFramework\Commands\Exception\RouteAlreadyExistsException;
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
    }
    public static function tearDownAfterClass(): void
    {
        // Code de nettoyage à exécuter après tous les tests de la classe
        (new Filesystem())->remove("app/Config/routes.yaml");
        (new Filesystem())->copy("tests/Config/routes.yaml", "app/Config/routes.yaml");
        (new Filesystem())->remove("app/Controller/api/InitController.php");
        (new Filesystem())->remove("app/Controller/api/openapi.php");
    }

    public function testCreateNewApi(): void
    {
        $this->commandeTester->execute(["controller-name" => "init"]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('URI : /api/init', $output);
    }

    public function testRouteAlreadyExists(): void
    {
        $this->expectException(RouteAlreadyExistsException::class);
        $this->commandeTester->execute(["controller-name" => "init"]);
        $this->assertStringContainsString('Route already exists', $this->commandeTester->getDisplay());
    }

    public function testExistFileOpenApi()
    {
        $this->assertTrue(file_exists("app/Controller/api/openapi.php"));
    }

    public function testExistFileInitController()
    {
        $this->assertTrue(file_exists("app/Controller/api/InitController.php"));
    }

    public function testExistFileRoutes()
    {
        $this->assertTrue(file_exists("app/Config/routes.yaml"));
    }

    public function testContentFileRoutes()
    {
        $this->assertStringContainsString('uri: /api/init', file_get_contents("app/Config/routes.yaml"));
    }

    public function testContentFileNameController()
    {
        $this->assertStringContainsString('api_init:', file_get_contents("app/Config/routes.yaml"));
    }

    public function testContentFileController()
    {
        $this->assertStringContainsString('controller: Controller\api\InitController', file_get_contents("app/Config/routes.yaml"));
    }

    public function testContentFileOpenApi()
    {
        $this->assertStringContainsString('namespace Controller\api;', file_get_contents("app/Controller/api/openapi.php"));
    }

    public function testContentFileInitController()
    {
        $this->assertStringContainsString('namespace Controller\api;', file_get_contents("app/Controller/api/InitController.php"));
    }

    public function testContentFileOpenApiAttribute()
    {
        $this->assertStringContainsString('use OpenApi\Attributes;', file_get_contents("app/Controller/api/openapi.php"));
    }

    public function testContentFileInitControllerAttribute()
    {
        $this->assertStringContainsString('use OpenApi\Attributes;', file_get_contents("app/Controller/api/InitController.php"));
    }

    public function testContentFileOpenApiClass()
    {
        $this->assertStringContainsString('class openapi', file_get_contents("app/Controller/api/openapi.php"));
    }

    public function testContentFileInitControllerClass()
    {
        $this->assertStringContainsString('class InitController', file_get_contents("app/Controller/api/InitController.php"));
    }
}

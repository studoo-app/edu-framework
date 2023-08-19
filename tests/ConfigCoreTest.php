<?php


use Dotenv\Dotenv;
use Studoo\EduFramework\Core\ConfigCore;
use PHPUnit\Framework\TestCase;

class ConfigCoreTest extends TestCase
{
    public function setUp(): void
    {
        // Gestion du fichier des variables d'environnement (.env)
        $dotenv = Dotenv::createImmutable(__DIR__ . '/Config/');
        $dotenv->load();
        (new ConfigCore([]));
    }

    public function testGetBasePathDefault()
    {
        $this->assertEquals('/', ConfigCore::getConfig('base_path'));
    }

    public function testGetBasePathChangeTo()
    {
        (new ConfigCore(['base_path' => '/test/']));
        $this->assertEquals('/test/', ConfigCore::getConfig('base_path'));
    }

    public function testGetTwigPathDefault()
    {
        $this->assertEquals('/app/Template', ConfigCore::getConfig('twig_path'));
    }

    public function testGetTwigPathChangeTo()
    {
        (new ConfigCore(['twig_path' => '/test/']));
        $this->assertEquals('/test/', ConfigCore::getConfig('twig_path'));
    }

    public function testGetRouteConfigPathDefault()
    {
        $this->assertEquals('/app/config/', ConfigCore::getConfig('route_config_path'));
    }

    public function testGetRouteConfigPathChangeTo()
    {
        (new ConfigCore(['route_config_path' => '/test/']));
        $this->assertEquals('/test/', ConfigCore::getConfig('route_config_path'));
    }

    public function testGetEnvDbName()
    {
        $this->assertEquals('app_db', ConfigCore::getEnv('DB_NAME'));
    }
}

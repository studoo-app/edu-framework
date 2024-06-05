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

    public function testExistEnvFileTest()
    {
        $this->assertTrue(ConfigCore::existEnv('ENV_UNIT'));
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
        $this->assertEquals('/app/Config/', ConfigCore::getConfig('route_config_path'));
    }

    public function testGetRouteConfigPathChangeTo()
    {
        (new ConfigCore(['route_config_path' => '/test/']));
        $this->assertEquals('/test/', ConfigCore::getConfig('route_config_path'));
    }

    public function testGetEnvDbName()
    {
        $_ENV["DB_NAME"] = 'app_db';
        $this->assertEquals('app_db', ConfigCore::getEnv('DB_NAME'));
    }

    public function testGetEnvDbHost()
    {
        $_ENV["DB_HOST"] = '127.0.0.1';
        $this->assertEquals('127.0.0.1', ConfigCore::getEnv('DB_HOST'));
    }

    public function testGetEnvDbSocket()
    {
        $_ENV["DB_SOCKET"] = '3306';
        $this->assertEquals('3306', ConfigCore::getEnv('DB_SOCKET'));
    }

    public function testGetEnvDbType()
    {
        $_ENV["DB_TYPE"] = 'mysql';
        $this->assertEquals('mysql', ConfigCore::getEnv('DB_TYPE'));
    }

    public function testGetEnvDbUser()
    {
        $_ENV["DB_USER"] = 'root';
        $this->assertEquals('root', ConfigCore::getEnv('DB_USER'));
    }

    public function testGetEnvDbPwd()
    {
        $_ENV["DB_PASSWORD"] = 'studoo';
        $this->assertEquals('studoo', ConfigCore::getEnv('DB_PASSWORD'));
    }

    public function testNotExistEnvDbHost()
    {
        $this->assertFalse(ConfigCore::existEnv('DB_HOSTABLE'));
    }
}

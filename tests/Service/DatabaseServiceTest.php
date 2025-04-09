<?php

namespace Service;

use Dotenv\Dotenv;
use PDO;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Exception\ErrorDatabaseNotExistException;
use Studoo\EduFramework\Core\Service\DatabaseService;
use PHPUnit\Framework\TestCase;

class DatabaseServiceTest extends TestCase
{

    public function testGetConnectMariaDb()
    {
        $_ENV["DB_TYPE"] = "mariadb";
        $this->assertEquals("mariadb", ConfigCore::getEnv("DB_TYPE"));
    }

    public function testGetConnectMysql()
    {
        $_ENV["DB_TYPE"] = "mysql";
        $this->assertEquals("mysql", ConfigCore::getEnv("DB_TYPE"));
    }

    public function testGetConnectError()
    {
        $_ENV["DB_TYPE"] = "test";
        $this->expectException(ErrorDatabaseNotExistException::class);
        new DatabaseService();
    }

    public function testNoExistDriverPgsql()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../Config/');
        $dotenv->load();
        (new ConfigCore([]));
        $this->expectException(ErrorDatabaseNotExistException::class);
        new DatabaseService();
    }

    public function testInstanceOfPdo()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../Config/');
        $dotenv->load();
        (new ConfigCore([]));

        $_ENV["DB_TYPE"] = "mysql";
        $_ENV["DB_PASSWORD"] = "studoo";
        $_ENV["DB_SOCKET"] = "8006";

        new DatabaseService();
        $this->assertInstanceOf(PDO::class, DatabaseService::getConnect());
    }
}

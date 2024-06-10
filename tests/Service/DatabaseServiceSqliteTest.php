<?php

namespace Service;

use Dotenv\Dotenv;
use PDO;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Exception\ErrorDatabaseNotExistException;
use Studoo\EduFramework\Core\Service\DatabaseService;
use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\Service\DatabaseSqlite;

class DatabaseServiceSqliteTest extends TestCase
{

    public function setUp(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../Config/');
        $dotenv->load();
        (new ConfigCore([
            'sqlite_path' => './var/sqlite/'
        ]));
        $_ENV["DB_TYPE"] = "sqlite";
    }

    public function testInstanceOfPdo()
    {
        new DatabaseService();
        $this->assertInstanceOf(PDO::class, DatabaseService::getConnect());
    }

    /**
     * @throws \Exception
     */
    public function testGetNewConnectSqlite()
    {
       $connection = new DatabaseSqlite();
        $this->assertInstanceOf(PDO::class, $connection->getManager());
    }
}

<?php

namespace Service;

use Dotenv\Dotenv;
use PDO;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Exception\ErrorDatabaseNotExistException;
use Studoo\EduFramework\Core\Service\DatabaseService;
use PHPUnit\Framework\TestCase;

class DatabaseServiceSqliteTest extends TestCase
{

    public function testInstanceOfPdo()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../Config/');
        $dotenv->load();
        (new ConfigCore([
            'sqlite_path' => './var/sqlite/'
        ]));

        $_ENV["DB_TYPE"] = "sqlite";

        new DatabaseService();
        $this->assertInstanceOf(PDO::class, DatabaseService::getConnect());
    }
}

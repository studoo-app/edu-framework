<?php

namespace Studoo\EduFramework\Core\Service;

use Exception;
use PDO;

class DatabaseService
{
    private static $dbConnect;

    /**
     * @return PDO
     */
    public static function getConnect(): PDO
    {
        if (!self::$dbConnect) {
            try {
                self::$dbConnect = new PDO('mysql:host=' . $_ENV["DB_HOST"] . ';port=' . $_ENV["DB_SOCKET"] . ';dbname=' . $_ENV["DB_NAME"],
                    $_ENV["DB_USER"],
                    $_ENV["DB_PASSWORD"],
                    array(PDO::ATTR_PERSISTENT => false, PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8')
                );
            } catch (Exception $e) {
                die('Erreur : ' . $e->getMessage());
            }
        }

        return self::$dbConnect;
    }
}
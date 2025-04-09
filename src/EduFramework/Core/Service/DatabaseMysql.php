<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Service;

use PDO;
use Studoo\EduFramework\Core\ConfigCore;

class DatabaseMysql implements DatabaseInterface
{
    /**
     * @return PDO
     */
    public function getManager(): PDO
    {
        return new PDO(
            'mysql:host=' . ConfigCore::getEnv("DB_HOST") .
            ';port=' . ConfigCore::getEnv("DB_SOCKET") .
            ';dbname=' . ConfigCore::getEnv("DB_NAME"),
            ConfigCore::getEnv("DB_USER"),
            ConfigCore::getEnv("DB_PASSWORD"),
            [
             PDO::ATTR_PERSISTENT           => false,
             PDO::MYSQL_ATTR_INIT_COMMAND   => 'SET NAMES utf8',
            ]
        );
    }
}

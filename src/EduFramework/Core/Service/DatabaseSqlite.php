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

use Exception;
use PDO;
use Studoo\EduFramework\Core\ConfigCore;

/**
 * Class DatabaseSqlite
 * Classe pour gérer la connexion à une base de données SQLite.
 *
 * @package Studoo\EduFramework\Core\Service
 * @property string $dbName Nom de la base de données
 */
class DatabaseSqlite implements DatabaseInterface
{
    /**
     * Permets de récupérer la connexion à la base de données
     * 
     * @param string|null $dbName Nom de la base de données
     * @param string|null $pathToSqliteLogs Chemin vers le fichier SQLite
     * @throws Exception
     * @return PDO
     */
    public function getManager(string|null $dbName = null, string|null $pathToSqliteLogs = null): PDO
    {
        $pathToSqliteFile = ($pathToSqliteLogs === null ? ConfigCore::getConfig('sqlite_path') : $pathToSqliteLogs);

        if (is_dir($pathToSqliteFile) === false) {
            mkdir($pathToSqliteFile, 0777, true);
        }

        if (file_exists($pathToSqliteFile) === false) {
            throw new Exception("SQLite database file does not exist at the provided path. <" . ConfigCore::getConfig('sqlite_path') . "> ");
        }

        return new PDO('sqlite:' . $pathToSqliteFile . ($dbName === null ? ConfigCore::getEnv('DB_NAME') : $dbName) . ".sqlite");
    }
}

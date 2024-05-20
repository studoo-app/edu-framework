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

class DatabaseSqlite implements DatabaseInterface
{
    /**
     * @return PDO
     */
    public function getManager(): PDO
    {
        $pathToSqliteFile = ConfigCore::getConfig('sqlite_path');

        if(is_dir($pathToSqliteFile) === false) {
            mkdir($pathToSqliteFile, 0777, true);
        }

        if (!file_exists($pathToSqliteFile)) {
            throw new Exception("SQLite database file does not exist at the provided path. <" . ConfigCore::getConfig('sqlite_path') . "> ");
        }

        return new PDO('sqlite:' . $pathToSqliteFile . ConfigCore::getEnv('DB_NAME') . ".sqlite");
    }
}

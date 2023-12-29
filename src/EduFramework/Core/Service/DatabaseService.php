<?php
/*
 * Ce fichier fait partie du Studoo
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
use Studoo\EduFramework\Core\Exception\ErrorDatabaseNotExistException;

class DatabaseService
{
    /**
     * Objet PDO pour la connexion à la base de données
     * @var PDO
     */
    private static PDO $dbConnect;

    /**
     * @throws ErrorDatabaseNotExistException
     */
    public function __construct()
    {
        $dbValideType = ['mysql', 'mariadb', 'pgsql', 'sqlite'];

        if (ConfigCore::getEnv("DB_TYPE") !== null &&
            in_array(ConfigCore::getEnv("DB_TYPE"), $dbValideType, true)) {

            $nameClass = "Studoo\\EduFramework\\Core\\Service\\Database" . ucfirst(ConfigCore::getEnv("DB_TYPE"));
            if (class_exists($nameClass) === false
                || in_array(DatabaseInterface::class, class_implements($nameClass), true) === false) {
                throw new ErrorDatabaseNotExistException();
            }

            self::$dbConnect = (new $nameClass())->getManager();
        } else {
            throw new ErrorDatabaseNotExistException();
        }
    }

    /**
     * Permets de récupérer la connexion à la base de données
     *
     * @return PDO
     * @throws Exception
     */
    public static function getConnect(): PDO
    {
        return self::$dbConnect;
    }
}

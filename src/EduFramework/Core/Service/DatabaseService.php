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

class DatabaseService
{
    /**
     * Objet PDO pour la connexion à la base de données
     * @var PDO
     */
    private static PDO $dbConnect;

    /**
     * TODO faire un exemple
     */
    public function __construct()
    {
        try {
            self::$dbConnect = new PDO(
                'mysql:host=' . $_ENV["DB_HOST"] .
                ';port=' . $_ENV["DB_SOCKET"] .
                ';dbname=' . $_ENV["DB_NAME"],
                $_ENV["DB_USER"],
                $_ENV["DB_PASSWORD"],
                // Attention au paramètre
                // PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8 qui ne marche pas sur mariadb
                array(PDO::ATTR_PERSISTENT => false, PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8')
            );
        } catch (Exception $e) {
            die('Erreur : ' . $e->getMessage());
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

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

class DatabaseSqLite implements DatabaseInterface
{
    /**
     * @return PDO
     */
    public function getManager(): PDO
    {
        // Todo Activer le module dans check
        // Todo Activer les droits a autoload class
        // Todo Vérification /var/sqlite
        // Todo Param PDO SqlLite
        return new PDO();
    }
}

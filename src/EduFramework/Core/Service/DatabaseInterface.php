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

interface DatabaseInterface
{
    /**
     * Permets de récupérer la connexion à la base de données
     * @return PDO
     */
    public function getManager(): PDO;
}

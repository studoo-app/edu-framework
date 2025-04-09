<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Exception;

class ErrorDatabaseNotExistException extends \Exception
{
    /**
     * Message de l'exception
     * @var string
     */
    protected $message = "La classe n'existe pas ou n'implémente pas l'interface DatabaseInterface";

    /**
     * Code de l'excpetion
     * @var integer
     */
    protected $code = 400;
}

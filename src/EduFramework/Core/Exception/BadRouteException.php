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

class BadRouteException extends \Exception
{
    /**
     * Message de l'exception
     * @var string
     */
    protected $message = "Le paramètre ou la route n'existe pas";

    /**
     * Code de l'excpetion
     * @var integer
     */
    protected $code = 400;
}

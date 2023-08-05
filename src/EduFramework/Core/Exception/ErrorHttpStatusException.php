<?php
/*
 * Ce fichier fait partie du Studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Exception;

class ErrorHttpStatusException extends \Exception
{
    /**
     * Message de l'exception
     * @var string
     */
    protected $message = "Erreur HTTP status";

    /**
     * Code de l'excpetion
     * @var int
     */
    protected $code = 400;
}

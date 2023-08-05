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

class NotDataResponseException extends \Exception
{
    /**
     * @var string
     */
    protected $message = "Pas de donnée dans la réponse";

    /**
     * @var int
     */
    protected $code = 403;
}

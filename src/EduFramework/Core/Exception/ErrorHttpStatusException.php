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

/**
 * Class ErrorHttpStatusException
 * Elle permet de lever une erreur HTTP depuis un controller
 * Le framework affiche la page d'erreur correspondant au code HTTP
 * Example d'utilisation dans un controller pour interdire l'accès :
 *     throw new ErrorHttpStatusException('Accès interdit', 403);
 * Codes HTTP supportés : 403 (accès interdit), 404 (page introuvable),
 * 405 (méthode non autorisée) et 500 (erreur interne) par défaut
 */
class ErrorHttpStatusException extends \Exception
{
    /**
     * Message de l'exception
     * @var string
     */
    protected $message = "Erreur HTTP status";

    /**
     * Code de l'excpetion
     * @var integer
     */
    protected $code = 400;
}

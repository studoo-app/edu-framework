<?php

/*
 * Edu Framework by studoo labs
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Exception;

/**
 * Class ErrorCacheException
 * Erreur du cache applicatif (CacheService) : serveur Redis injoignable,
 * clé invalide, valeur impossible à convertir en JSON...
 * @package Studoo\EduFramework\Core\Exception
 */
class ErrorCacheException extends \Exception
{
    /**
     * Message de l'exception
     * @var string
     */
    protected $message = "Erreur du cache applicatif";

    /**
     * Code de l'exception
     * @var integer
     */
    protected $code = 500;
}

<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Controller\Error;

use Studoo\EduFramework\Core\ConfigCore;

/**
 * Trait ErrorPageTrait
 * Il permet de transmettre l'exception à l'origine de l'erreur à la page d'erreur
 * Le message de l'exception est affiché sur la page uniquement
 * en mode développement (APP_ENV=dev dans le fichier .env)
 */
trait ErrorPageTrait
{
    /**
     * L'exception à l'origine de l'erreur (facultative)
     * @param \Throwable|null $exception L'exception levée
     */
    public function __construct(private readonly \Throwable|null $exception = null)
    {
    }

    /**
     * Renvoi le message de l'exception à afficher sur la page d'erreur
     * Le message est affiché uniquement en mode développement (APP_ENV=dev)
     * @return string|null
     */
    protected function getExceptionMessage(): string|null
    {
        if ($this->exception === null
            || ConfigCore::existEnv('APP_ENV') === false
            || ConfigCore::getEnv('APP_ENV') !== 'dev') {
            return null;
        }
        return $this->exception->getMessage();
    }
}

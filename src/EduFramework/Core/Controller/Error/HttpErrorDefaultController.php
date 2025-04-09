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

use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\View\TwigCore;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class HttpErrorDefaultController
 * Classe Controller pour les erreurs HTTP
 */
class HttpErrorDefaultController implements ControllerInterface
{
    /**
     * Si y a pas de route valide alors j'affiche la page 404
     * @param Request $request Objet de la requête
     * @return string
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function execute(Request $request): string
    {
        return TwigCore::getEnvironment()->render(
            'error/http-Default.html.twig',
            []
        );
    }
}

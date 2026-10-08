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
 * Class HttpError405Controller
 * Classe Controller pour les erreurs HTTP 405 (méthode non autorisée)
 */
class HttpError405Controller implements ControllerInterface
{
    use ErrorPageTrait;

    /**
     * Si la méthode HTTP n'est pas autorisée alors j'affiche la page 405
     * @param Request $request Objet de la requête
     * @return string
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function execute(Request $request): string
    {
        if (headers_sent() === false) {
            http_response_code(405);
        }
        return TwigCore::getEnvironment()->render(
            'error/http-405.html.twig',
            [
                'code' => 405,
                'exception_message' => $this->getExceptionMessage()
            ]
        );
    }
}

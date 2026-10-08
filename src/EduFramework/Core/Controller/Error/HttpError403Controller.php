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
 * Class HttpError403Controller
 * Classe Controller pour les erreurs HTTP 403 (accès interdit)
 * Exemple d'utilisation dans un controller :
 *     throw new ErrorHttpStatusException('Accès interdit', 403);
 */
class HttpError403Controller implements ControllerInterface
{
    use ErrorPageTrait;

    /**
     * Si l'accès à la page est interdit alors j'affiche la page 403
     * @param Request $request Objet de la requête
     * @return string
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function execute(Request $request): string
    {
        if (headers_sent() === false) {
            http_response_code(403);
        }
        return TwigCore::getEnvironment()->render(
            'error/http-403.html.twig',
            [
                'code' => 403,
                'exception_message' => $this->getExceptionMessage()
            ]
        );
    }
}

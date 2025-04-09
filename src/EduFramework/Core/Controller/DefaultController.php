<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Controller;

use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\View\studooView;
use Studoo\EduFramework\Core\View\TwigCore;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class DefaultController
 * @package Studoo\EduFramework\Core\Controller
 */
class DefaultController implements ControllerInterface
{
    use studooView;

    /**
     * @param Request $request Requête HTTP
     * @return string|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function execute(Request $request): string|null
    {
        ConfigCore::setConfig('twig_path', __DIR__ . '/Template');
        TwigCore::setEnvironment();
        return TwigCore::getEnvironment()->render(
            'default.html.twig',
            [
                'bonjour'   => "Page par défaut, la route est /",
                'logoEF'      => $this->logo()
            ]
        );
    }
}

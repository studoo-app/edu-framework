<?php

namespace Studoo\EduFramework\Core\Controller\Error;

use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\View\TwigCore;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class HttpController implements ControllerInterface
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function execute(Request $request)
    {
        // Si y a pas de GET alors j'affiche tout
        return TwigCore::getEnvironment()->render(
            'error/http-404.html.twig',
            []);
    }
}
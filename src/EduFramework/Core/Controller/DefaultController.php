<?php

namespace Studoo\EduFramework\Core\Controller;

use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\View\TwigCore;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class DefaultController implements ControllerInterface
{
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
                'bonjour'   => "welcome to the home page",
            ]
        );
    }
}

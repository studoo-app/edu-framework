<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Controller\Debug;

use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\View\studooView;
use Studoo\EduFramework\Core\View\TwigCore;

/**
 * Class ProfilerController
 * Page du profiler (style Symfony Profiler : barre latérale sombre,
 * contenu clair) permettant de naviguer dans les logs enregistrés.
 * Cette route est enregistrée uniquement en mode dev (APP_ENV=dev),
 * elle n'est donc pas accessible en production.
 *
 * @package Studoo\EduFramework\Core\Controller\Debug
 */
class ProfilerController implements ControllerInterface
{
    use studooView;

    /**
     * Affiche la page du profiler
     *
     * @param Request $request Requête HTTP
     * @return string|null
     */
    public function execute(Request $request): string|null
    {
        ConfigCore::setConfig('twig_path', __DIR__ . '/../Template');
        TwigCore::setEnvironment();

        return TwigCore::getEnvironment()->render(
            'profiler.html.twig',
            [
                'version'    => ConfigCore::getConfig('version'),
                'phpVersion' => PHP_VERSION,
                'appEnv'     => ConfigCore::getEnv('APP_ENV'),
                'logoEF'     => $this->logo(),
            ]
        );
    }
}

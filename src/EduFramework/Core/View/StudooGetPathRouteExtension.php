<?php

/*
 * Ce fichier fait partie du Studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\View;

use Studoo\EduFramework\Core\Controller\Route;
use Twig\Extension\AbstractExtension;

/**
 * Class StudooDebugExtension
 * Elle permet de déclarer les fonctions de l'extension dd()
 * @package Studoo\EduFramework\Core\View
 */
class StudooGetPathRouteExtension extends AbstractExtension
{
    /**
     * Permet de déclarer les fonctions de l'extension
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
                new \Twig\TwigFunction('getNameToPath', [$this, 'getNameToPath']),
               ];
    }

    /**
     * Permet d'afficher le contenu d'une variable
     * @param string $name Nom de la route à récupérer dans le fichier config/routes.yaml
     * @param array<mixed> $param Tableau associatif des paramètres de la route
     * @return string URL
     * TODO Changer le style de l'affichage
     */
    public function getNameToPath(string $name, array $param = []): string
    {
        $route = new Route();
        return $route->getNameToPath($name, $param);
    }
}

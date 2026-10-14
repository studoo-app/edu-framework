<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\View;

use Studoo\EduFramework\Core\Security\CsrfService;
use Twig\Extension\AbstractExtension;

/**
 * Class StudooCsrfExtension
 * Elle permet de déclarer la fonction csrf_token()
 * Cette fonction génère le champ de formulaire caché contenant
 * le jeton CSRF associé à un nom de route
 * Exemple dans un template : {{ csrf_token('medecin_import') | raw }}
 * @package Studoo\EduFramework\Core\View
 */
class StudooCsrfExtension extends AbstractExtension
{
    /**
     * Permet de déclarer les fonctions de l'extension
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
                new \Twig\TwigFunction('csrf_token', [$this, 'csrfToken']),
               ];
    }

    /**
     * Renvoie le champ de formulaire caché contenant le jeton CSRF du nom de route
     * @param string $routeName Le nom de la route définie dans le fichier Config/routes.yaml
     * @return string Le champ HTML caché
     */
    public function csrfToken(string $routeName): string
    {
        return CsrfService::getTokenField($routeName);
    }
}

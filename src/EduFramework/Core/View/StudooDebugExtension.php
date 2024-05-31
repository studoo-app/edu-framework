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

use Twig\Extension\AbstractExtension;

/**
 * Class StudooDebugExtension
 * Elle permet de déclarer les fonctions de l'extension dd()
 * @package Studoo\EduFramework\Core\View
 */
class StudooDebugExtension extends AbstractExtension
{
    /**
     * Permet de déclarer les fonctions de l'extension
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
                new \Twig\TwigFunction('dd', [$this, 'dd']),
               ];
    }

    /**
     * Permet d'afficher le contenu d'une variable
     * @param mixed $var La variable à debugger
     * @return void
     * TODO Changer le style de l'affichage
     */
    public function dd(mixed $var): void
    {
        ob_start();
        var_dump($var);
        $result = ob_get_clean();

        $output = sprintf("<div style=\"background-color: black; color: white; padding: 10px; margin: 10px 0;\">
            <pre>%s</pre>
        </div>", htmlspecialchars($result));

        echo $output;
    }
}

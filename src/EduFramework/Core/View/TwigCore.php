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

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigCore
{
    private static Environment $twig;

    public function __construct($path)
    {
        // Gestion du moteur de template
        // TODO Warning : ne pas oublier de mettre le chemin vers le dossier templates
        $loader = new FilesystemLoader($path);
        // création de l'objet $twig

        self::$twig = new Environment($loader, []);
    }

    /**
     * @return Environment
     */
    public static function getEnvironment(): Environment
    {
        return self::$twig;
    }
}

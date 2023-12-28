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
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

/**
 * Class TwigCore
 * Elle permet de gérer le moteur de template Twig
 * @package Studoo\EduFramework\Core\View
 */
class TwigCore
{
    /**
     * @var Environment
     * Objet de l'environnement Twig
     */
    private static Environment $twig;

    /**
     * @param string $path Chemin vers le dossier templates
     */
    public function __construct(string $path)
    {
        // Gestion du moteur de template
        $loader = new FilesystemLoader($path);
        // création de l'objet $twig
        self::$twig = new Environment($loader, [
            'cache' => false,
            'debug' => true,
        ]);
        // Ajoutez l'extension Debug
        self::$twig->addExtension(new DebugExtension());
        self::$twig->addExtension(new StudooDebugExtension());
    }

    /**
     * Retourne l'objet de l'environnement Twig pour construire les pages HTML ou JSON ...
     * @return Environment
     */
    public static function getEnvironment(): Environment
    {
        return self::$twig;
    }
}

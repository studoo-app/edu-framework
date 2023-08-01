<?php

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

        self::$twig = new Environment($loader, [
            // TODO mettre en param
            //'cache' => __DIR__ . '/../var/cache',
        ]);
    }

    /**
     * @return Environment
     */
    public static function getEnvironment(): Environment
    {
        return self::$twig;
    }
}
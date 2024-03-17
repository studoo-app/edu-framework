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

use Studoo\EduFramework\Core\ConfigCore;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;

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

    private static TwigCore $instance;

    /**
     * @param string $path Chemin vers le dossier templates
     */
    public function __construct(string $path)
    {
        // Gestion du moteur de template
        $loader = new FilesystemLoader($path);
        // création de l'objet $twig
        self::$twig = new Environment(
            $loader,
            ['cache' => false, 'debug' => true]
        );
        // Ajoutez l'extension Debug
        self::$twig->addExtension(new DebugExtension());
        self::$twig->addExtension(new StudooDebugExtension());
    }

    /**
     * Retourne l'objet de l'environnement Twig pour construire les pages HTML ou JSON ...
     * @return TwigCore
     */
    public static function getEnvironment(): TwigCore
    {
        return self::$instance;
    }

    public static function setEnvironment(): void
    {
        self::$instance = new self(ConfigCore::getConfig('twig_path'));
    }

    /**
     * Permet de générer une page HTML
     * @param string|TemplateWrapper $name Nom du template
     * @param array<string> $context Contexte
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(string|TemplateWrapper $name, array $context = []): string
    {
        $response = self::$twig->render($name, $context);
        if (ConfigCore::getEnv('APP_ENV') === 'dev') {
            $response .= (new studooBarreDebug())->generateStickyBar();
        }
        return $response;
    }
}

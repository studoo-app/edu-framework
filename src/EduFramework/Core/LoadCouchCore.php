<?php
/*
 * Ce fichier fait partie du Studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */


namespace Studoo\EduFramework\Core;

use Dotenv\Dotenv;
use Studoo\EduFramework\Core\Controller\FastRouteCore;
use Studoo\EduFramework\Core\Service\DatabaseService;
use Studoo\EduFramework\Core\View\TwigCore;

/**
 * Class LoadCouchCore
 * Elle permet de charger les différentes couches de l'application
 */
class LoadCouchCore
{
    public function __construct()
    {
        // Gestion du fichier des variables d'environnement (.env)
        $dotenv = Dotenv::createImmutable(ConfigCore::getConfig('base_path'));
        $dotenv->load();

        // Gestion de la couche View
        (new TwigCore(ConfigCore::getConfig('twig_path')));

        // Gestion de la couche Model et de la connexion à la base de données
        if (ConfigCore::getEnv('DB_HOST_STATUS') === 'true') {
            (new DatabaseService());
        }
    }

    /**
     * Permet de lancer l'application
     * @return void
     */
    public function run(): void
    {
        // Gestion des routes
        $route = new FastRouteCore();
        // LoadCouchCore des routes depuis le fichier de configuration
        $route->loadRouteConfig(ConfigCore::getConfig('route_config_path'));

        try {
            // Récupération de la route à appeler
            echo $route->getRoute();
        } catch (\Twig\Error\LoaderError |\Twig\Error\RuntimeError |\Twig\Error\SyntaxError $e) {
            echo $e->getMessage();
        }
    }
}

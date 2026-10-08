<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core;

use Dotenv\Dotenv;
use PDO;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\Debug\DebugLogsController;
use Studoo\EduFramework\Core\Controller\Debug\ProfilerController;
use Studoo\EduFramework\Core\Controller\Error\HttpError403Controller;
use Studoo\EduFramework\Core\Controller\Error\HttpError404Controller;
use Studoo\EduFramework\Core\Controller\Error\HttpError405Controller;
use Studoo\EduFramework\Core\Controller\Error\HttpErrorDefaultController;
use Studoo\EduFramework\Core\Controller\FastRouteCore;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Exception\ErrorHttpStatusException;
use Studoo\EduFramework\Core\Logs\LogsService;
use Studoo\EduFramework\Core\Service\DatabaseService;
use Studoo\EduFramework\Core\View\TwigCore;

/**
 * Class LoadCouchCore
 * Elle permet de charger les différentes couches de l'application
 */
class LoadCouchCore
{
    /**
     * Permet de lancer l'application
     * @return void
     */
    public function run(): void
    {
        // Gestion du fichier des variables d'environnement (.env)
        $dotenv = Dotenv::createImmutable(ConfigCore::getConfig('base_path'));
        $dotenv->load();

        // Gestion de la couche View
        TwigCore::setEnvironment();

        // Gestion de la couche Model et de la connexion à la base de données
        if (ConfigCore::getEnv('DB_HOST_STATUS') === 'true') {
            (new DatabaseService());
        }

        // Gestion des logs (le service de logs nécessite le driver PDO SQLite)
        if (in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            (new LogsService());
        }

        // Gestion des routes
        $route = new FastRouteCore();

        try {
            // LoadCouchCore des routes depuis le fichier de configuration
            $route->loadRouteConfig(ConfigCore::getConfig('route_config_path'));

            // Route interne de la barre de debug (uniquement en mode dev)
            if (ConfigCore::existEnv('APP_ENV') === true && ConfigCore::getEnv('APP_ENV') === 'dev'
                && in_array('sqlite', PDO::getAvailableDrivers(), true)) {
                $route->addRoute('GET', '/_debug/profiler', ProfilerController::class);
                $route->addRoute('GET', '/edu-logs', DebugLogsController::class);
            }

            // Récupération de la route à appeler
            echo $route->getRoute();
        } catch (ErrorHttpStatusException $exception) {
            // Une erreur HTTP a été levée par un controller (Exemple: accès interdit 403)
            // On affiche la page d'erreur correspondant au code HTTP de l'exception
            echo match ($exception->getCode()) {
                403 => (new HttpError403Controller($exception))->execute($this->getRequest()),
                404 => (new HttpError404Controller($exception))->execute($this->getRequest()),
                405 => (new HttpError405Controller($exception))->execute($this->getRequest()),
                default => (new HttpErrorDefaultController($exception))->execute($this->getRequest()),
            };
        } catch (\Throwable $exception) {
            // Toute autre erreur non gérée (y compris les erreurs de configuration)
            // affiche la page d'erreur 500 avec le message de l'exception en mode développement
            echo (new HttpErrorDefaultController($exception))->execute($this->getRequest());
        }
    }

    /**
     * Renvoi la requête HTTP en cours
     * Si la requête n'est pas encore renseignée (erreur avant le dispatch), une requête par défaut est créée
     * @return Request
     */
    private function getRequest(): Request
    {
        if (ConfigCore::hasRequest() === true) {
            return ConfigCore::getRequest();
        }
        return new Request('/', 'GET');
    }
}

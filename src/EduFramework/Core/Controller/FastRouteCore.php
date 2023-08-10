<?php
/*
 * Ce fichier fait partie du Studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Controller;

use FastRoute\Dispatcher;
use Studoo\EduFramework\Core\Controller\Error\HttpError404Controller;
use Studoo\EduFramework\Core\Controller\Error\HttpError405Controller;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class FastRouteCore
 * Classe pour la gestion des routes
 */
class FastRouteCore
{
    use BuildControllerTrait;

    /**
     * Methode pour récupérer la classe controller à appeler
     * Elle retourne le résultat de la méthode execute() du controller
     * @param Dispatcher $dispatcher
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError|SyntaxError|RuntimeError|LoaderError
     * @throws \Exception
     */
    public function getDispatcher($dispatcher): string
    {
        // Recupere les infos de la requete
        // Recupere la methode HTTP (GET, POST, PUT, DELETE, ...)
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        // Recupere l'URI (Uniform Resource Identifier) Exemple: /, /contact, /contact/1, ...
        $uri = $_SERVER['REQUEST_URI'];

        // Supprime les parametres de type ?param1=valeur1&param2=valeur2
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        // Supprime les slashs en fin d'URI Exemple: /contact/ => /contact
        $uri = rawurldecode($uri);

        // Création de l'objet requête HTTP
        $request = new Request($uri, $httpMethod);

        // Dispatche la requete et retourne les infos de la route trouvée dans un tableau
        // [0] => Constante de FastRoute (NOT_FOUND, METHOD_NOT_ALLOWED, FOUND)
        // [1] => Nom de la classe controller à appeler
        // [2] => Tableau des paramètres de la route
        // Exemple: [0] => 1 [1] => App\Controller\HomeController [2] => Array ( [id] => 1 )
        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            // Si la route n'est pas trouvée alors j'affiche la page 404
            case Dispatcher::NOT_FOUND:
                $returnView = (new HttpError404Controller())->execute($request);
                break;
                // Si la route est trouvée mais que la méthode HTTP n'est pas autorisée
                // Exemple une route définie en POST est appelée en GET
            case Dispatcher::METHOD_NOT_ALLOWED:
                $returnView = (new HttpError405Controller())->execute($request);
                break;
                // Si la route est trouvée alors j'appelle le controller correspondant
            case Dispatcher::FOUND:
                // J'ajoute le nom de la classe controller à appeler
                // et les paramètres de la route à l'objet requête HTTP
                $request->setHander($routeInfo[1])->setVars($routeInfo[2]);

                // J'appelle la méthode execute() du controller
                // et je récupère la vue à afficher
                $returnView = $this->buildController($request->getHander())->execute($request);
                break;
            default:
                // TODO Refactoring des erreurs
                $returnView = (new HttpError404Controller())->execute($request);
                break;
        }
        return $returnView;
    }
}

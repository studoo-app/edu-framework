<?php
/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Controller;

use FastRoute\Dispatcher;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\Error\HttpError404Controller;
use Studoo\EduFramework\Core\Controller\Error\HttpError405Controller;
use Studoo\EduFramework\Core\Controller\Error\HttpErrorDefaultController;
use Symfony\Component\Yaml\Yaml;
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
     * Objet pour la gestion des routes
     * @var \FastRoute\RouteCollector
     */
    private \FastRoute\RouteCollector $routeCollector;

    /**
     * FastRouteCore constructor.
     */
    public function __construct()
    {
        // Gestion des routes
        $this->routeCollector = new \FastRoute\RouteCollector(
            new \FastRoute\RouteParser\Std(),
            new \FastRoute\DataGenerator\GroupCountBased()
        );
    }

    /**
     * Methode pour charger les routes depuis un fichier de configuration (Config/route.yaml)
     * @param string $pathConfigFile Chemin vers le fichier de configuration
     * @return $this
     */
    public function loadRouteConfig(string $pathConfigFile): self
    {
        $fileData = Yaml::parseFile($pathConfigFile . 'routes.yaml');
        if (is_array($fileData)) {
            foreach ($fileData as $routeConfig) {
                $this->addRoute($routeConfig['httpMethod'], $routeConfig['uri'], $routeConfig['controller']);
            }
        }

        return $this;
    }

    /**
     * Methode pour ajouter une route
     * Une route est une association entre une URL et un contrôleur
     * Cette route peut avoir des méthodes HTTP associées (GET, POST, PUT, DELETE, ...)
     * @param string|array<mixed> $httpMethod (GET, POST, PUT, DELETE, ...)
     * @param string              $uri La route à appeler
     * @param string              $controller Nom du controller à appeler
     * @return $this
     */
    public function addRoute(string|array $httpMethod, string $uri, string $controller): self
    {
        $this->routeCollector->addRoute($httpMethod, $uri, $controller);

        return $this;
    }

    /**
     * Methode pour récupérer le dispatcher
     * @return Dispatcher
     */
    public function getDispatcher(): Dispatcher
    {
        return new \FastRoute\Dispatcher\GroupCountBased($this->routeCollector->getData());
    }

    /**
     * Methode pour récupérer la classe controller à appeler
     * Elle retourne le résultat de la méthode execute() du controller
     * @return string|null
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError|SyntaxError|RuntimeError|LoaderError
     * @throws \Exception
     */
    public function getRoute(): string|null
    {
        $dispatcher = $this->getDispatcher();

        // Recupere les infos de la requete
        // Recupere la methode HTTP (GET, POST, PUT, DELETE, ...)
        // Si la methode HTTP n'est pas définie alors on met la valeur par défaut GET
        $httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        // Recupere l'URI (Uniform Resource Identifier) Exemple: /, /contact, /contact/1, ...
        // Si l'URI n'est pas définie alors on met la valeur par défaut /
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Supprime les parametres de type ?param1=valeur1&param2=valeur2
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        // Supprime les slashs en fin d'URI Exemple: /contact/ => /contact
        $uri = rawurldecode($uri);

        // Création de l'objet requête HTTP
        $request = new Request($uri, $httpMethod);
        ConfigCore::setRequest($request);

        // Dispatche la requete et retourne les infos de la route trouvée dans un tableau
        // [0] => Constante de FastRoute (NOT_FOUND, METHOD_NOT_ALLOWED, FOUND)
        // [1] => Nom de la classe controller à appeler
        // [2] => Tableau des paramètres de la route
        // Exemple: [0] => 1 [1] => App\Controller\HomeController [2] => Array ( [id] => 1 )
        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            // Si la route n'est pas trouvée alors j'affiche la page 404
            case Dispatcher::NOT_FOUND:
                // Route par defaut sans config route ou sans route / défini
                if ($uri === '/') {
                    $returnView = (new DefaultController())->execute($request);
                    // Route 404
                } else {
                    $returnView = (new HttpError404Controller())->execute($request);
                }
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
                $request->setHander($routeInfo[1])->setVars($_GET);
                $request->setHander($routeInfo[1])->setVars($_POST);
                $request->setHander($routeInfo[1])->setVars($routeInfo[2]);

                // J'appelle la méthode execute() du controller
                // et je récupère la vue à afficher
                $returnView = $this->buildController($request->getHander())->execute($request);
                break;
            default:
                $returnView = (new HttpErrorDefaultController())->execute($request);
                break;
        }
        return $returnView;
    }
}

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

use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\Error\HttpError404Controller;
use Studoo\EduFramework\Core\Controller\Error\HttpError405Controller;
use Studoo\EduFramework\Core\Exception\ErrorConfigException;
use Studoo\EduFramework\Core\Exception\ErrorRouteConfigNotExistException;
use Studoo\EduRoute\Attribute\AttributeRouteLoader;
use Studoo\EduRoute\Attribute\Route as RouteAttribute;
use Studoo\EduRoute\Attribute\RouteGroup;
use Studoo\EduRoute\DataGenerator\MarkBased as MarkBasedDataGenerator;
use Studoo\EduRoute\DeclaredRoute;
use Studoo\EduRoute\Dispatcher;
use Studoo\EduRoute\Dispatcher\MarkBased as MarkBasedDispatcher;
use Studoo\EduRoute\Dispatcher\Result\Matched;
use Studoo\EduRoute\Dispatcher\Result\MethodNotAllowed;
use Studoo\EduRoute\RouteCollector;
use Studoo\EduRoute\RouteParser\Std;
use Symfony\Component\Yaml\Yaml;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class FastRouteCore
 * Classe pour la gestion des routes (moteur de routage : studoo/edu-route)
 */
class FastRouteCore
{
    use BuildControllerTrait;

    /**
     * Objet pour la gestion des routes
     * @var RouteCollector
     */
    private RouteCollector $routeCollector;

    /**
     * FastRouteCore constructor.
     */
    public function __construct()
    {
        // Gestion des routes
        $this->routeCollector = new RouteCollector(
            new Std(),
            new MarkBasedDataGenerator()
        );
    }

    /**
     * Methode pour charger toutes les routes de l'application, dans cet ordre :
     *   1. le fichier de configuration app/Config/routes.yaml (clé route_config_path), s'il existe
     *   2. les attributs #[Route] des controllers du dossier app/Controller (clé controller_path)
     * Les deux modes de déclaration peuvent être utilisés ensemble
     * @return $this
     * @throws ErrorRouteConfigNotExistException Si le fichier routes.yaml est absent et qu'aucun controller
     *         ne déclare de route avec l'attribut #[Route]
     * @throws ErrorConfigException Si une route du fichier routes.yaml est mal décrite
     * @throws \Studoo\EduRoute\BadRouteException Si une route est invalide (route en double, attribut mal placé...)
     */
    public function loadRoutes(): self
    {
        $routeConfigPath = (string) ConfigCore::getConfig('route_config_path');
        $controllers = self::findAttributeControllers(self::getControllerPath(), self::getControllerNamespace());

        // Sans fichier routes.yaml ni attribut #[Route], on garde l'erreur explicite du fichier absent
        if (is_file($routeConfigPath . 'routes.yaml') === true || $controllers === []) {
            $this->loadRouteConfig($routeConfigPath);
        }

        return $this->loadRouteAttributes(...$controllers);
    }

    /**
     * Methode pour charger les routes déclarées avec les attributs #[Route] et #[RouteGroup] des controllers
     * Exemple:
     * ```
     * #[Route('/medecin/{id:\d+}', methods: ['GET', 'POST'], name: 'medecin_show')]
     * public function show(Request $request): string
     * ```
     * La route appelle la méthode du controller (handler "Controller\MedecinController::show")
     * @param class-string ...$controllers Classes des controllers à lire
     * @return $this
     * @throws \Studoo\EduRoute\BadRouteException Si un attribut est invalide (méthode privée, nom en double...)
     */
    public function loadRouteAttributes(string ...$controllers): self
    {
        AttributeRouteLoader::register($this->routeCollector, ...$controllers);

        return $this;
    }

    /**
     * Recherche les controllers qui déclarent des routes avec les attributs #[Route] ou #[RouteGroup]
     * Les fichiers sont lus dans l'ordre alphabétique de leur chemin (sous-dossiers compris),
     * le nom de la classe est déduit du chemin (PSR-4) : app/Controller/Api/UserController.php
     * => Controller\Api\UserController
     * @param string $controllerPath      Dossier des controllers (Exemple: app/Controller/)
     * @param string $controllerNamespace Namespace du dossier (Exemple: Controller\)
     * @return list<class-string> Controllers qui déclarent au moins une route
     */
    public static function findAttributeControllers(string $controllerPath, string $controllerNamespace): array
    {
        if (is_dir($controllerPath) === false) {
            return [];
        }

        $root = rtrim((string) realpath($controllerPath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() === true && $file->getExtension() === 'php') {
                $files[] = substr($file->getPathname(), strlen($root));
            }
        }
        sort($files);

        $controllers = [];
        foreach ($files as $file) {
            $className = rtrim($controllerNamespace, '\\') . '\\'
                . str_replace(DIRECTORY_SEPARATOR, '\\', substr($file, 0, -4));
            if (class_exists($className) === true && self::hasRouteAttribute(new ReflectionClass($className))) {
                $controllers[] = $className;
            }
        }

        return $controllers;
    }

    /**
     * Indique si une classe porte un attribut de route (#[RouteGroup] sur la classe, #[Route] sur une méthode)
     * Les attributs mal placés sont gardés : le moteur de routage les signalera avec un message explicite
     * @param ReflectionClass<object> $class
     * @return bool
     */
    private static function hasRouteAttribute(ReflectionClass $class): bool
    {
        if ($class->getAttributes(RouteGroup::class) !== [] || $class->getAttributes(RouteAttribute::class) !== []) {
            return true;
        }
        foreach ($class->getMethods() as $method) {
            if ($method->getAttributes(RouteAttribute::class) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dossier des controllers : clé controller_path, sinon le dossier app/Controller/ du projet (clé base_path)
     * @return string
     */
    public static function getControllerPath(): string
    {
        return (string) (ConfigCore::getConfig('controller_path')
            ?? rtrim((string) ConfigCore::getConfig('base_path'), '/') . '/app/Controller/');
    }

    /**
     * Namespace du dossier des controllers : clé controller_namespace, sinon Controller\
     * @return string
     */
    public static function getControllerNamespace(): string
    {
        return (string) (ConfigCore::getConfig('controller_namespace') ?? 'Controller\\');
    }

    /**
     * Methode pour charger les routes depuis un fichier de configuration (Config/route.yaml)
     * @param string $pathConfigFile Chemin vers le fichier de configuration
     * @return $this
     * @throws ErrorRouteConfigNotExistException
     * @throws ErrorConfigException Si une route du fichier est mal décrite
     */
    public function loadRouteConfig(string $pathConfigFile): self
    {
        // On vérifie que le fichier de configuration des routes existe
        // pour renvoyer une erreur explicite plutôt qu'une exception du composant Yaml
        if (is_file($pathConfigFile . 'routes.yaml') === false) {
            throw new ErrorRouteConfigNotExistException(
                "Le fichier de configuration des routes <" . $pathConfigFile . "routes.yaml> n'existe pas. "
                . "Vérifiez la configuration 'route_config_path' et la casse du dossier "
                . "(Exemple: app/Config et non app/config, les systèmes de fichiers Linux sont sensibles à la casse)"
            );
        }

        $fileData = Yaml::parseFile($pathConfigFile . 'routes.yaml');
        if (is_array($fileData)) {
            foreach ($fileData as $routeName => $routeConfig) {
                [$httpMethod, $uri, $controller] = $this->readRouteConfig($routeName, $routeConfig);
                // Le nom de la route (clé du fichier routes.yaml) est transmis au moteur de routage
                // pour l'afficher dans la commande routes:list
                $this->addRoute($httpMethod, $uri, $controller, is_string($routeName) ? $routeName : null);
            }
        }

        return $this;
    }

    /**
     * Lit une route du fichier routes.yaml
     * Exemple: home: { uri: /home, controller: Controller\HomeController, httpMethod: [GET] }
     * Une route mal décrite (clé absente, liste à la place d'un texte...) est signalée par un message
     * qui nomme la route, au lieu d'une erreur PHP
     * @param int|string $routeName   Nom de la route (clé du fichier routes.yaml)
     * @param mixed      $routeConfig Description de la route
     * @return array{0: string|array<mixed>, 1: string, 2: string} [httpMethod, uri, controller]
     * @throws ErrorConfigException Si la route est mal décrite
     */
    private function readRouteConfig(int|string $routeName, mixed $routeConfig): array
    {
        if (is_array($routeConfig) === false) {
            throw $this->invalidRoute($routeName, "elle doit définir les clés 'uri', 'controller' et 'httpMethod'");
        }

        foreach (['uri' => '/hello', 'controller' => 'Controller\HelloController'] as $key => $example) {
            if (is_scalar($routeConfig[$key] ?? null) === false) {
                throw $this->invalidRoute(
                    $routeName,
                    "la clé '" . $key . "' est absente ou n'est pas un texte (Exemple: " . $key . ": " . $example . ")"
                );
            }
        }

        $httpMethod = $routeConfig['httpMethod'] ?? null;
        if (is_scalar($httpMethod)) {
            $httpMethod = (string) $httpMethod;
        } elseif (is_array($httpMethod) === false
            || array_filter($httpMethod, static fn ($method) => $method !== null && is_scalar($method) === false) !== []
        ) {
            throw $this->invalidRoute(
                $routeName,
                "la clé 'httpMethod' est absente ou n'est pas une liste de méthodes HTTP (Exemple: httpMethod: [GET, POST])"
            );
        }

        return [$httpMethod, (string) $routeConfig['uri'], (string) $routeConfig['controller']];
    }

    /**
     * Construit l'erreur d'une route mal décrite dans routes.yaml
     * @param int|string $routeName Nom de la route
     * @param string     $reason    Ce qui ne va pas
     * @return ErrorConfigException
     */
    private function invalidRoute(int|string $routeName, string $reason): ErrorConfigException
    {
        return new ErrorConfigException(
            "La route <" . $routeName . "> du fichier routes.yaml est invalide : " . $reason
        );
    }

    /**
     * Methode pour ajouter une route
     * Une route est une association entre une URL et un contrôleur
     * Cette route peut avoir des méthodes HTTP associées (GET, POST, PUT, DELETE, ...)
     * @param string|array<mixed> $httpMethod (GET, POST, PUT, DELETE, ...)
     * @param string              $uri La route à appeler
     * @param string              $controller Nom du controller à appeler.
     *        Deux formes possibles :
     *          - "Controller\MedecinController" : appel de la méthode execute() par défaut
     *          - "Controller\MedecinController::index" : appel de la méthode index()
     * @param string|null         $name Nom de la route (clé du fichier routes.yaml), null si elle n'est pas nommée
     * @return $this
     * @throws ErrorConfigException Si une méthode HTTP est une liste ou un objet
     */
    public function addRoute(string|array $httpMethod, string $uri, string $controller, ?string $name = null): self
    {
        $extraParameters = ($name !== null && $name !== '') ? [RouteCollector::ROUTE_NAME => $name] : [];
        $this->routeCollector->addRoute(
            $this->normalizeHttpMethods($httpMethod, $uri),
            $uri,
            $controller,
            $extraParameters
        );

        return $this;
    }

    /**
     * Methode pour récupérer la liste des routes déclarées, dans l'ordre de déclaration
     * Une entrée par méthode HTTP : une route [GET, POST] donne deux entrées
     * Utilisée par la commande routes:list
     * @return list<DeclaredRoute>
     */
    public function declaredRoutes(): array
    {
        return $this->routeCollector->declaredRoutes();
    }

    /**
     * Methode pour récupérer le dispatcher
     * @return Dispatcher
     */
    public function getDispatcher(): Dispatcher
    {
        return new MarkBasedDispatcher($this->routeCollector->processedRoutes());
    }

    /**
     * Methode pour récupérer la classe controller à appeler
     * Elle retourne le résultat de la méthode execute() du controller
     * ou de la méthode définie dans la route Exemple: Controller\MedecinController::index
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
        // Décode les caractères encodés de l'URI Exemple: /ville/Cr%C3%A9teil => /ville/Créteil
        $uri = rawurldecode($uri);

        // Création de l'objet requête HTTP
        $request = new Request($uri, $httpMethod);
        ConfigCore::setRequest($request);

        // Dispatche la requete et retourne un objet résultat :
        //   - Matched : la route est trouvée ($result->handler = controller, $result->variables = paramètres)
        //   - MethodNotAllowed : la route existe mais pas pour cette méthode HTTP ($result->allowedMethods)
        //   - NotMatched : aucune route ne correspond à l'URI
        $result = $dispatcher->dispatch($httpMethod, $uri);

        // Si la route est trouvée alors j'appelle le controller correspondant
        if ($result instanceof Matched) {
            // Le handler peut être de deux formes :
            //     - "Controller\MedecinController" : la méthode execute() sera appelée par défaut
            //     - "Controller\MedecinController::index" : la méthode index() sera appelée
            [$controller, $action] = $this->resolveHandler($result->handler);

            // J'ajoute le nom de la classe controller à appeler
            // et les paramètres de la route à l'objet requête HTTP
            $request->setHander($controller);
            $request->setVars($_GET);
            $request->setVars($_POST);
            $request->setVars($result->variables);
            // Les fichiers téléversés via un formulaire multipart/form-data
            $request->setFiles($_FILES);

            if ($action === null) {
                // Sans méthode explicite dans la route, le controller doit implémenter l'interface ControllerInterface
                // J'appelle la méthode execute() du controller
                // et je récupère la vue à afficher
                return $this->buildController($request->getHander())->execute($request);
            }

            // Une méthode explicite est définie dans la route Exemple: Controller\MedecinController::index
            // La validation de la méthode est faite dans la méthode callAction()
            // J'appelle la méthode du controller et je récupère la vue à afficher
            $request->setAction($action);
            return $this->callAction($request);
        }

        // Si la route est trouvée mais que la méthode HTTP n'est pas autorisée
        // Exemple une route définie en POST est appelée en GET
        if ($result instanceof MethodNotAllowed) {
            return (new HttpError405Controller())->execute($request);
        }

        // La route n'est pas trouvée
        // Route par defaut sans config route ou sans route / défini
        if ($uri === '/') {
            return (new DefaultController())->execute($request);
        }

        // Sinon j'affiche la page 404
        return (new HttpError404Controller())->execute($request);
    }

    /**
     * Convertit les méthodes HTTP d'une route en chaînes de caractères pour le moteur de routage
     * La conversion reproduit celle de l'ancien moteur (nikic/fast-route), qui utilisait la méthode
     * comme clé de tableau : une route du fichier routes.yaml se comporte exactement comme avant
     *   - ~ ou un tiret orphelin (null) devient '' ; true devient '1' et false '0' ; 405 devient '405'
     *   - la casse est conservée : une route [get] ne répond pas à une requête GET (erreur 405)
     * @param string|array<mixed> $httpMethod Méthode(s) HTTP de la route
     * @param string              $uri        URI de la route (pour le message d'erreur)
     * @return list<string>
     * @throws ErrorConfigException Si une méthode HTTP est une liste ou un objet
     */
    private function normalizeHttpMethods(string|array $httpMethod, string $uri): array
    {
        $methods = [];
        foreach ((array) $httpMethod as $method) {
            $methods[] = match (true) {
                is_string($method) => $method,
                $method === null => '',
                is_bool($method), is_float($method) => (string) (int) $method,
                is_int($method) => (string) $method,
                default => throw new ErrorConfigException(
                    "La méthode HTTP de la route <" . $uri . "> est invalide : "
                    . "httpMethod doit être une méthode ou une liste de méthodes (Exemple: httpMethod: [GET, POST])"
                ),
            };
        }

        return $methods;
    }
}

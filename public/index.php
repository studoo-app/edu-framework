<?php

// Autoloader => chargement automatique des classes depuis le dossier vendor/
require_once __DIR__ . '/../vendor/autoload.php';

// Utilisation des classes utilisées dans le fichier
use Controller\HomeController;
use Dotenv\Dotenv;
use Studoo\EduFramework\Core\Controller\FastRouteCore;
use Studoo\EduFramework\Core\Service\DatabaseService;
use Studoo\EduFramework\Core\View\TwigCore;

// Gestion des fichiers environnement .env
$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

// Gestion de la couche View
(new TwigCore(__DIR__ . '/../app/Template'));
$en = TwigCore::getEnvironment();

// Gestion des routes
// Une route est une association entre une URL et un contrôleur
// Cette route peut avoir des méthodes HTTP associées (GET, POST, PUT, DELETE, ...)
$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $route) {
    $route->addRoute('GET', '/', "Controller\HomeController");
});

// Gestion de la couche Model et de la connexion à la base de données
if ($_ENV['DB_HOST_STATUS'] === 'true') {
    (new DatabaseService());
}


// Le dispatcher va permettre de faire le lien entre l'URL et le contrôleur.
// Le client (navigateur) va envoyer une requête HTTP (GET, POST, PUT, DELETE, ...)
// et le dispatcher va faire le lien entre l'URL et le contrôleur.
try {
    echo (new FastRouteCore)->getDispatcher($dispatcher);
} catch (\Twig\Error\LoaderError|\Twig\Error\RuntimeError|\Twig\Error\SyntaxError|Exception $e) {
    echo $e->getMessage();
}


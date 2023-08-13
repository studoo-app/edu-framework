<?php

// Autoloader => chargement automatique des classes depuis le dossier vendor/
require_once __DIR__ . '/../vendor/autoload.php';

// Utilisation des classes utilisées dans le fichier
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


// Gestion de la couche Model et de la connexion à la base de données
if ($_ENV['DB_HOST_STATUS'] === 'true') {
    (new DatabaseService());
}

// Gestion des routes
$route = new FastRouteCore();
// Load des routes depuis le fichier de configuration
$route->loadRouteConfig(__DIR__ . '/../app/config/');

// Récupération de la route à appeler
try {
    echo $route->getRoute();
} catch (\Twig\Error\LoaderError|\Twig\Error\RuntimeError|\Twig\Error\SyntaxError|Exception $e) {
    echo $e->getMessage();
}


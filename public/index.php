<?php
// Démarrage de la session PHP pour la gestion des variables de session
session_start();

// Autoloader => chargement automatique des classes depuis le dossier vendor/
require_once __DIR__ . '/../vendor/autoload.php';

// Utilisation des classes utilisées dans le fichier
use Dotenv\Dotenv;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\FastRouteCore;
use Studoo\EduFramework\Core\Service\DatabaseService;
use Studoo\EduFramework\Core\View\TwigCore;

(new ConfigCore([
    'base_path'         => __DIR__ . '/../',
    'twig_path'         => __DIR__ . '/../app/Template',
    "route_config_path"  => __DIR__ . '/../app/config/',
]));


// Gestion des fichiers environnement .env
$dotenv = Dotenv::createImmutable(ConfigCore::getConfig('base_path'));
$dotenv->load();

// Gestion de la couche View
(new TwigCore(ConfigCore::getConfig('twig_path')));
$en = TwigCore::getEnvironment();


// Gestion de la couche Model et de la connexion à la base de données
if (ConfigCore::getEnv('DB_HOST_STATUS') === 'true') {
    (new DatabaseService());
}

// Gestion des routes
$route = new FastRouteCore();
// LoadCore des routes depuis le fichier de configuration
$route->loadRouteConfig(ConfigCore::getConfig('route_config_path'));

// Récupération de la route à appeler
try {
    echo $route->getRoute();
} catch (\Twig\Error\LoaderError|\Twig\Error\RuntimeError|\Twig\Error\SyntaxError|Exception $e) {
    echo $e->getMessage();
}


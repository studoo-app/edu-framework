<?php
// Démarrage de la session PHP pour la gestion des variables de session
session_start();

// Autoloader => chargement automatique des classes depuis le dossier vendor/
require_once __DIR__ . '/../vendor/autoload.php';

// Chargement des configurations de l'application
(new Studoo\EduFramework\Core\ConfigCore([
    'base_path'         => __DIR__ . '/../',
    'twig_path'         => __DIR__ . '/../app/Template',
    "route_config_path"  => __DIR__ . '/../app/config/',
]));

// Chargement des classes utilisées par l'application
(new \Studoo\EduFramework\Core\LoadCouchCore())->run();












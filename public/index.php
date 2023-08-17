<?php
// Démarrage de la session PHP pour la gestion des variables de session
session_start();

// Envoie les erreurs à stderr, pas à stdout. ou (0)
ini_set('display_errors', 'stderr');

// Évitez d'afficher deux fois les erreurs PHP.
ini_set('log_errors', '0');

// Masquer les deprecations de PHP 8.1
error_reporting(E_ALL & ~E_DEPRECATED);

if (version_compare(PHP_VERSION, '8.0', '<')) {
    printf("Cet app nécessite au moins PHP8.0. %s est actuellement installé. Veuillez mettre à jour votre version de PHP.\n", PHP_VERSION);
    exit(1);
}

// Autoloader => chargement automatique des classes depuis le dossier vendor/
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    fwrite(STDERR, 'ERROR: Les dépendances du gestionnaire de package (composer) ne sont pas correctement configurées! Exécutez "composer install" ou consultez README.md pour plus de détails' . PHP_EOL);
    exit(1);
}

// Chargement des configurations de l'application
(new Studoo\EduFramework\Core\ConfigCore([
    'base_path'         => __DIR__ . '/../',
    'twig_path'         => __DIR__ . '/../app/Template',
    "route_config_path"  => __DIR__ . '/../app/config/',
]));

// Chargement des classes utilisées par l'application
(new \Studoo\EduFramework\Core\LoadCouchCore())->run();












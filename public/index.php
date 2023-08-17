<?php
// Démarrage de la session PHP pour la gestion des variables de session
session_start();

// Masquer les deprecations de PHP 8.1
error_reporting(E_ALL & ~E_DEPRECATED);

if (version_compare(PHP_VERSION, '8.0', '<') === false) {
    // Autoloader => chargement automatique des classes depuis le dossier vendor/
    if (file_exists(__DIR__ . '/../vendor/autoload.php') === true) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        echo 'ERROR: Les dépendances du gestionnaire de package (composer) 
        ne sont pas correctement configurées! Exécutez "composer install" 
        ou consultez README.md pour plus de détails';
        exit(1);
    }

    // Chargement des configurations de l'application
    (new Studoo\EduFramework\Core\ConfigCore(
            [
                'base_path'         => __DIR__ . '/../',
                'twig_path'         => __DIR__ . '/../app/Template',
                "route_config_path"  => __DIR__ . '/../app/config/'
            ]
        )
    );

    // Chargement des classes utilisées par l'application
    (new \Studoo\EduFramework\Core\LoadCouchCore())->run();

} else {
    echo "Cet app nécessite au moins PHP8.0. "
        . PHP_VERSION .
        " est actuellement installé. Veuillez mettre à jour votre version de PHP.\n";
}








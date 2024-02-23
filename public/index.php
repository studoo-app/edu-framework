<?php
// Démarrage de la session PHP pour la gestion des variables de session
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\LoadCouchCore;

session_start();

// Masquer les deprecations de PHP 8.1
error_reporting(E_ALL & ~E_DEPRECATED);

if (version_compare(PHP_VERSION, '8.1', '<') === false) {
    // Autoloader => chargement automatique des classes depuis le dossier vendor/
    require_once __DIR__ . '/../vendor/autoload.php';

    // Chargement des configurations de l'application
    (new ConfigCore(
                    [
                        'base_path'         => __DIR__ . '/../',
                        'twig_path'         => __DIR__ . '/../app/Template',
                        'route_config_path' => __DIR__ . '/../app/Config/'
                    ]
                  )
    );

    // Chargement des classes utilisées par l'application
    (new LoadCouchCore())->run();
} else {
    echo "Cet app nécessite au moins PHP8.1"
        . PHP_VERSION .
        " est actuellement installé. Veuillez mettre à jour votre version de PHP.\n";
}

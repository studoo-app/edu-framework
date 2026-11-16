<?php
/*     ______    __            ______                                             __  
 *    / ____/___/ /_  __      / ____/________ _____ ___  ___ _      ______  _____/ /__
 *   / __/ / __  / / / /_____/ /_  / ___/ __ `/ __ `__ \/ _ \ | /| / / __ \/ ___/ //_/
 *  / /___/ /_/ / /_/ /_____/ __/ / /  / /_/ / / / / / /  __/ |/ |/ / /_/ / /  / ,<   
 * /_____/\__,_/\__,_/     /_/   /_/   \__,_/_/ /_/ /_/\___/|__/|__/\____/_/  /_/|_|   
 * by studoo labs
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 * 
 * Starting End Point
 */

use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\LoadCouchCore;

// Démarrage de la session PHP pour la gestion des variables de session
session_start();

// Masquer les deprecations de PHP 8.4
error_reporting(E_ALL & ~E_DEPRECATED);

if (version_compare(PHP_VERSION, '8.4', '<') === false) {
    // Autoloader => chargement automatique des classes depuis le dossier vendor/
    require_once __DIR__ . '/../vendor/autoload.php';

    // Chargement des configurations de l'application
    (new ConfigCore(
                    [
                        'base_path'         => __DIR__ . '/../',
                        'twig_path'         => __DIR__ . '/../app/Template',
                        'route_config_path' => __DIR__ . '/../app/Config/',
                        'cache_path' => __DIR__ . '/../var/cache/',
                        'sqlite_path' => __DIR__ . '/../var/sqlite/',
                        'sqlite_logs_path' => __DIR__ . '/../var/logs/'
                    ]
                  )
    );

    // Chargement des classes utilisées par l'application
    (new LoadCouchCore())->run();
} else {
    printf("Cet app nécessite au moins PHP8.4.");
    printf(" Veuillez mettre à jour votre version de PHP.\n");
}

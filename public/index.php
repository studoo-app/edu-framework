<?php
/*
 * Edu Framework by studoo
 *    ____  _             _
 *   / ___|| |_ _   _  __| | ___   ___
 *   \___ \| __| | | |/ _` |/ _ \ / _ \
 *    ___) | |_| |_| | (_| | (_) | (_) |
 *   |____/ \__|\__,_|\__,_|\___/ \___/
 *
 * Starting End Point Edu-Framework
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\LoadCouchCore;

// Démarrage de la session PHP pour la gestion des variables de session
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
                        'route_config_path' => __DIR__ . '/../app/Config/',
                        'sqlite_path' => __DIR__ . '/../var/sqlite/'
                    ]
                  )
    );

    // Chargement des classes utilisées par l'application
    (new LoadCouchCore())->run();
} else {
    printf("Cet app nécessite au moins PHP8.1.");
    printf(" Veuillez mettre à jour votre version de PHP.\n");
}

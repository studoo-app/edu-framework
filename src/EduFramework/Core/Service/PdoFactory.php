<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Service;

use PDO;
use Studoo\EduFramework\Core\ConfigCore;

/**
 * Class PdoFactory
 * Fabrique des connexions PDO de l'application.
 * En mode développement (APP_ENV=dev avec l'extension pdo_sqlite, comme pour
 * la journalisation des requêtes HTTP), la fabrique retourne une connexion
 * traçable TraceablePDO qui mesure le temps d'exécution des requêtes SQL et
 * les rattache à la requête HTTP en cours. Dans tous les autres cas (production,
 * extension pdo_sqlite absente, ...), elle retourne une connexion PDO native :
 * TraceablePDO et TraceablePDOStatement ne sont alors jamais instanciées ni
 * chargées par l'autoloader, aucune mesure ni collecte n'a lieu.
 *
 * @package Studoo\EduFramework\Core\Service
 */
class PdoFactory
{
    /**
     * Retourne la connexion PDO correspondant à l'environnement :
     * TraceablePDO en mode dev, PDO natif sinon
     *
     * @param string $dsn DSN de connexion (Exemple : mysql:host=...)
     * @param string|null $username Nom d'utilisateur
     * @param string|null $password Mot de passe
     * @param array<int|string, mixed>|null $options Options du pilote PDO
     * @return PDO
     */
    public static function create(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        ?array $options = null
    ): PDO {
        if (self::shouldTrace() === true) {
            return new TraceablePDO($dsn, $username, $password, $options);
        }

        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * Indique si les requêtes SQL doivent être tracées :
     * uniquement en mode dev avec l'extension pdo_sqlite disponible
     * (le collecteur écrit dans la base SQLite des logs, même condition
     * que la journalisation des requêtes HTTP)
     *
     * @return bool
     */
    public static function shouldTrace(): bool
    {
        return ConfigCore::existEnv('APP_ENV') === true
            && ConfigCore::getEnv('APP_ENV') === 'dev'
            && in_array('sqlite', PDO::getAvailableDrivers(), true) === true;
    }
}

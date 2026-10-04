<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Logs;

use PDO;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Service\DatabaseSqlite;

class LogsService
{
    private string $dbNameLogs = 'core_logs';

    /**
     * Objet PDO pour la connexion à la base de données des logs
     * @var PDO
     */
    private static PDO $dbConnectLogs;

    /**
     * Initialise la connexion à la base de données des logs (définie par la config sqlite_logs_path)
     * et crée la structure de la table des logs si nécessaire
     *
     * @throws Exception Si le type de base de données n'est pas valide
     */
    public function __construct()
    {
        self::$dbConnectLogs = (new DatabaseSqlite)->getManager(
            $this->dbNameLogs,
            ConfigCore::getConfig('sqlite_logs_path')
        );
        (new LogsInitialiser())->exeStructure();
    }

    /**
     * Permets de récupérer la connexion à la base de données.
     * Si la connexion n'est pas encore initialisée, elle est créée automatiquement.
     *
     * @return PDO
     * @throws Exception Si le type de base de données n'est pas valide
     */
    public static function getConnect(): PDO
    {
        if (isset(self::$dbConnectLogs) === false) {
            new self();
        }
        return self::$dbConnectLogs;
    }

    /**
     * Permets d'enregistrer un log dans la base de données
     *
     * @param string $eventDesc Description de l'événement à enregistrer
     * @return void
     */
    public static function addLog(string $eventDesc): void
    {
        $pdo = self::getConnect();
        $stmt = $pdo->prepare("INSERT INTO serv_logs (event_desc) VALUES (:event_desc)");
        $stmt->bindParam(':event_desc', $eventDesc, PDO::PARAM_STR);
        $stmt->execute();
    }
}
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
     * @var PDO|null
     */
    private static ?PDO $dbConnectLogs = null;

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
        if (self::$dbConnectLogs === null) {
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

    /**
     * Permets de récupérer les logs enregistrés, du plus récent au plus ancien.
     * L'événement (event_desc) est décodé : s'il s'agit d'un JSON, le tableau
     * décodé est retourné, sinon le texte brut est disponible dans la clé 'raw'.
     *
     * @param int $limit Nombre de logs à récupérer
     * @param int $offset Décalage pour la pagination
     * @return array<int, array<string, mixed>> Liste des logs (id, event_date, event)
     */
    public static function getLogs(int $limit = 20, int $offset = 0): array
    {
        $stmt = self::getConnect()->prepare(
            'SELECT id, event_date, event_desc FROM serv_logs ORDER BY id DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $logs = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $event = json_decode((string) $row['event_desc'], true);
            $logs[] = [
                'id' => (int) $row['id'],
                'event_date' => $row['event_date'],
                'event' => (is_array($event) === true ? $event : ['raw' => (string) $row['event_desc']]),
            ];
        }

        return $logs;
    }

    /**
     * Permets de compter le nombre total de logs enregistrés
     *
     * @return int Nombre total de logs
     */
    public static function countLogs(): int
    {
        return (int) self::getConnect()->query('SELECT COUNT(*) FROM serv_logs')->fetchColumn();
    }
}
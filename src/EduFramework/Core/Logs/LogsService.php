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
     * Filtres disponibles :
     * - method : méthode HTTP (GET, POST, ...)
     * - status : famille de code statut (2, 3, 4 ou 5 pour 2xx, 3xx, 4xx, 5xx)
     * - search : recherche textuelle dans l'événement
     *
     * @param int $limit Nombre de logs à récupérer
     * @param int $offset Décalage pour la pagination
     * @param array<string, string|null> $filters Filtres optionnels
     * @return array<int, array<string, mixed>> Liste des logs (id, event_date, event)
     */
    public static function getLogs(int $limit = 20, int $offset = 0, array $filters = []): array
    {
        [$where, $params] = self::buildFilters($filters);

        $stmt = self::getConnect()->prepare(
            'SELECT id, event_date, event_desc FROM serv_logs' . $where
            . ' ORDER BY id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
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
     * Permets de compter le nombre total de logs enregistrés,
     * en tenant compte des mêmes filtres que getLogs()
     *
     * @param array<string, string|null> $filters Filtres optionnels
     * @return int Nombre total de logs
     */
    public static function countLogs(array $filters = []): int
    {
        [$where, $params] = self::buildFilters($filters);

        $stmt = self::getConnect()->prepare('SELECT COUNT(*) FROM serv_logs' . $where);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Construit la clause WHERE et les paramètres SQL correspondant aux filtres
     *
     * @param array<string, string|null> $filters Filtres optionnels
     * @return array{0: string, 1: array<string, string>} Clause WHERE (avec un préfixe espace) et paramètres
     */
    private static function buildFilters(array $filters): array
    {
        $where = [];
        $params = [];

        if (empty($filters['method']) === false) {
            $where[] = "event_desc LIKE :method ESCAPE '\\'";
            $params[':method'] = '%"method":"' . $filters['method'] . '"%';
        }

        if (empty($filters['status']) === false) {
            $where[] = "event_desc LIKE :status ESCAPE '\\'";
            $params[':status'] = '%"status_code":"' . $filters['status'] . '%';
        }

        if (empty($filters['search']) === false) {
            $where[] = "event_desc LIKE :search ESCAPE '\\'";
            $params[':search'] = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['search']) . '%';
        }

        return [
            (count($where) === 0 ? '' : ' WHERE ' . implode(' AND ', $where)),
            $params,
        ];
    }
}
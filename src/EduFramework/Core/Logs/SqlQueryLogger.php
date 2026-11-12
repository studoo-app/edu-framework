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

/**
 * Class SqlQueryLogger
 * Collecteur des requêtes SQL exécutées pendant une requête HTTP :
 * chaque requête mesurée par TraceablePDO / TraceablePDOStatement est
 * mise en mémoire tampon avec le token de la requête HTTP en cours
 * (HttpRequestLogger), puis écrite groupée en fin de requête dans la table
 * serv_sql_logs de la base des logs (flush(), appelé par LoadCouchCore).
 *
 * Les requêtes exécutées hors d'une requête HTTP journalisée (ligne de
 * commande, routes internes du profiler, base de données des logs elle-même)
 * ne sont jamais collectées : sans token, collect() ne fait rien.
 * Cette classe n'est utilisée qu'en mode dev (PdoFactory) : en production,
 * le tampon reste vide et flush() ne fait rien.
 *
 * @package Studoo\EduFramework\Core\Logs
 */
class SqlQueryLogger
{
    /**
     * Requêtes SQL collectées pendant la requête HTTP en cours :
     * chaque entrée porte le token de la requête HTTP au moment de l'exécution
     * @var array<int, array{request_id: int, query: string, duration_ms: float, rows_count: int|null, params: string|null}>
     */
    private static array $buffer = [];

    /**
     * Longueur maximale (en octets) conservée pour la valeur d'un paramètre de type chaîne
     * (au-delà, la valeur est tronquée : elle sert à rejouer la requête pour EXPLAIN)
     */
    public const int PARAM_MAX_LENGTH = 1000;

    /**
     * Enregistre une requête SQL exécutée et son temps d'exécution,
     * rattachée à la requête HTTP en cours.
     * Ne fait rien s'il n'y a pas de requête HTTP journalisée en cours
     * (pas de token) : les requêtes CLI, du profiler et de la base des
     * logs ne sont jamais collectées
     *
     * @param string $query Requête SQL exécutée
     * @param float $durationMs Temps d'exécution en millisecondes
     * @param int|null $rowsCount Nombre de lignes retournées ou affectées, null si indisponible
     * @param array<int, array{name: int|string, value: mixed, type: int}> $params Paramètres liés à
     *        la requête (statement préparé), utilisés par le profiler pour rejouer la requête (EXPLAIN)
     * @return void
     */
    public static function collect(
        string $query,
        float $durationMs,
        int|null $rowsCount = null,
        array $params = []
    ): void {
        $requestId = HttpRequestLogger::getCurrentLogId();
        if ($requestId === null) {
            return;
        }

        self::$buffer[] = [
            'request_id' => $requestId,
            'query' => $query,
            'duration_ms' => round($durationMs, 1),
            'rows_count' => $rowsCount,
            'params' => self::encodeParams($params),
        ];
    }

    /**
     * Encode les paramètres d'une requête en JSON (liste de {name, value, type}),
     * null si la requête n'a pas de paramètre. Seules les valeurs scalaires sont
     * conservées (une ressource ou un objet devient null) et les chaînes sont
     * tronquées à PARAM_MAX_LENGTH octets
     *
     * @param array<int, array{name: int|string, value: mixed, type: int}> $params Paramètres liés
     * @return string|null
     */
    private static function encodeParams(array $params): string|null
    {
        if ($params === []) {
            return null;
        }

        $encoded = [];
        foreach ($params as $param) {
            $value = $param['value'];
            if (is_string($value) === true && strlen($value) > self::PARAM_MAX_LENGTH) {
                $value = substr($value, 0, self::PARAM_MAX_LENGTH);
            } elseif ($value !== null && is_scalar($value) === false) {
                $value = null;
            }
            $encoded[] = [
                'name' => $param['name'],
                'value' => $value,
                // Le type de base, sans les options (Exemple : PDO::PARAM_INPUT_OUTPUT)
                'type' => $param['type'] & ~PDO::PARAM_INPUT_OUTPUT,
            ];
        }

        $json = json_encode($encoded, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION);

        return ($json === false ? null : $json);
    }

    /**
     * Décode les paramètres d'une requête enregistrés en JSON (encodeParams()) :
     * liste de {name, value, type}, vide si la requête n'a pas de paramètre ou si
     * le JSON est illisible
     *
     * @param mixed $json Paramètres enregistrés (colonne params de serv_sql_logs)
     * @return array<int, array{name: int|string, value: mixed, type: int}>
     */
    public static function decodeParams(mixed $json): array
    {
        $decoded = (is_string($json) === true ? json_decode($json, true) : null);
        $params = [];
        foreach (is_array($decoded) === true ? $decoded : [] as $param) {
            $name = (is_array($param) === true ? ($param['name'] ?? null) : null);
            if (is_int($name) === true || is_string($name) === true) {
                $params[] = ['name' => $name, 'value' => $param['value'] ?? null, 'type' => (int) ($param['type'] ?? PDO::PARAM_STR)];
            }
        }

        return $params;
    }

    /**
     * Retourne le nombre de requêtes SQL collectées pour la requête HTTP en cours
     * (utilisé par le bloc SQL de la barre de debug)
     *
     * @return int
     */
    public static function count(): int
    {
        return count(self::$buffer);
    }

    /**
     * Retourne le temps SQL cumulé des requêtes collectées pour la requête
     * HTTP en cours, en millisecondes (utilisé par la barre de debug)
     *
     * @return float
     */
    public static function totalDurationMs(): float
    {
        return round(array_sum(array_column(self::$buffer, 'duration_ms')), 1);
    }

    /**
     * Retourne la requête SQL la plus lente collectée pour la requête HTTP en
     * cours (requête et temps d'exécution), null si aucune requête collectée
     *
     * @return array{query: string, duration_ms: float}|null
     */
    public static function slowestQuery(): array|null
    {
        $slowest = null;
        foreach (self::$buffer as $entry) {
            if ($slowest === null || $entry['duration_ms'] > $slowest['duration_ms']) {
                $slowest = $entry;
            }
        }

        return $slowest;
    }

    /**
     * Écrit les requêtes SQL collectées dans la table serv_sql_logs de la
     * base des logs, en une seule transaction, puis vide le tampon.
     * Appelée en fin de requête HTTP (LoadCouchCore), comme
     * HttpRequestLogger::finish(). En cas d'échec, l'erreur est envoyée dans
     * les logs PHP (error_log) : la collecte ne doit jamais faire échouer
     * la requête. Ne fait rien si le tampon est vide.
     *
     * @return void
     */
    public static function flush(): void
    {
        $buffer = self::$buffer;
        self::$buffer = [];

        if ($buffer === []) {
            return;
        }

        $pdo = null;
        try {
            $pdo = LogsService::getConnect();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare(
                'INSERT INTO serv_sql_logs (request_id, query, duration_ms, rows_count, params) '
                . 'VALUES (:request_id, :query, :duration_ms, :rows_count, :params)'
            );
            foreach ($buffer as $entry) {
                $stmt->bindValue(':request_id', $entry['request_id'], PDO::PARAM_INT);
                $stmt->bindValue(':query', $entry['query'], PDO::PARAM_STR);
                $stmt->bindValue(':duration_ms', $entry['duration_ms']);
                $stmt->bindValue(
                    ':rows_count',
                    $entry['rows_count'],
                    ($entry['rows_count'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT)
                );
                $stmt->bindValue(
                    ':params',
                    $entry['params'],
                    ($entry['params'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR)
                );
                $stmt->execute();
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo !== null && $pdo->inTransaction() === true) {
                $pdo->rollBack();
            }
            error_log('[EduFramework Logs] ' . $exception->getMessage());
        }
    }

    /**
     * Vide le tampon sans l'écrire (isolation des tests, réinitialisation
     * entre deux requêtes HTTP servies par le même processus)
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$buffer = [];
    }
}

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
     * @var array<int, array{request_id: int, query: string, duration_ms: float, rows_count: int|null}>
     */
    private static array $buffer = [];

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
     * @return void
     */
    public static function collect(string $query, float $durationMs, int|null $rowsCount = null): void
    {
        $requestId = HttpRequestLogger::getCurrentLogId();
        if ($requestId === null) {
            return;
        }

        self::$buffer[] = [
            'request_id' => $requestId,
            'query' => $query,
            'duration_ms' => round($durationMs, 1),
            'rows_count' => $rowsCount,
        ];
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
                'INSERT INTO serv_sql_logs (request_id, query, duration_ms, rows_count) '
                . 'VALUES (:request_id, :query, :duration_ms, :rows_count)'
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

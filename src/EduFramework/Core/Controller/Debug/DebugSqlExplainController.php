<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Controller\Debug;

use PDO;
use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Logs\LogsService;
use Studoo\EduFramework\Core\Profiler\Sql\SqlExplainAdvisor;
use Studoo\EduFramework\Core\Profiler\Sql\SqlExplainer;
use Studoo\EduFramework\Core\Profiler\Sql\SqlParamResolver;
use Studoo\EduFramework\Core\Profiler\Sql\SqlQueryInspector;
use Studoo\EduFramework\Core\Profiler\Sql\SqlTableInspector;
use Studoo\EduFramework\Core\Service\DatabaseService;

/**
 * Class DebugSqlExplainController
 * Endpoint interne du profiler : analyse le plan d'exécution (EXPLAIN) d'une
 * requête SQL enregistrée pendant une requête HTTP et propose des axes
 * d'amélioration, au format JSON.
 *
 * La requête analysée est toujours celle enregistrée par le profiler (table
 * serv_sql_logs, désignée par son identifiant) : l'endpoint n'accepte pas de
 * texte SQL. Seules les valeurs de ses paramètres peuvent être modifiées.
 * Le mode ANALYZE exécute réellement la requête : il est réservé aux requêtes
 * de lecture (SELECT) et s'exécute dans une transaction annulée.
 * Cette route est enregistrée uniquement en mode dev (APP_ENV=dev),
 * elle n'est donc pas accessible en production.
 *
 * @package Studoo\EduFramework\Core\Controller\Debug
 */
class DebugSqlExplainController implements ControllerInterface
{
    /**
     * Réponse vide : valeurs par défaut de chaque clé
     */
    private const array EMPTY_RESPONSE = [
        'id' => null,
        'token' => null,
        'query' => null,
        'duration_ms' => null,
        'rows_count' => null,
        'executions' => 0,
        'params' => [],
        'driver' => null,
        'server_version' => null,
        'read_only' => false,
        'plan' => null,
        'analysis' => null,
        'accesses' => [],
        'tables' => [],
        'findings' => [],
        'suggestions' => [],
        'message' => null,
        'error' => null,
    ];

    /**
     * Retourne l'analyse EXPLAIN d'une requête SQL au format JSON
     * Paramètres de la requête :
     * - id : identifiant de la requête SQL enregistrée (obligatoire)
     * - analyze : 1 pour exécuter réellement la requête (EXPLAIN ANALYZE)
     * - params : valeurs des paramètres, objet JSON (Exemple : {":id": "3"}),
     *   à la place des valeurs enregistrées
     *
     * Réponse :
     * - id, token, query, duration_ms, rows_count, executions : requête SQL enregistrée
     * - params : paramètres de la requête (name, value, provided)
     * - driver, server_version : moteur de base de données
     * - read_only : la requête peut être exécutée par ANALYZE
     * - plan : plan estimé (statement, columns, rows)
     * - analysis : plan mesuré (statement, columns, rows, tree, time_ms, rows_count), null sans ANALYZE
     * - accesses : accès aux tables normalisés
     * - tables : structure des tables (rows, columns, indexes)
     * - findings : axes d'amélioration (level, title, detail, suggestion, table)
     * - suggestions : instructions CREATE INDEX proposées, sans doublon
     * - message : raison pour laquelle l'analyse n'a pas pu être faite, null sinon
     * - error : erreur retournée par la base de données, null sinon
     *
     * @param Request $request Requête HTTP
     * @return string|null
     */
    public function execute(Request $request): string|null
    {
        // L'en-tête n'est envoyé que si la sortie n'a pas déjà commencé
        // (Exemple : exécution dans un contexte de test)
        if (headers_sent() === false) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $sqlId = (string) $request->get('id');
        if (preg_match('/^\d{1,18}$/', $sqlId) !== 1) {
            return $this->json(self::EMPTY_RESPONSE, 'Identifiant de requête SQL invalide.');
        }

        $entry = LogsService::getSqlQuery((int) $sqlId);
        if ($entry === null) {
            return $this->json(self::EMPTY_RESPONSE, 'Requête SQL introuvable : les logs ont peut-être été vidés.');
        }

        $inspector = new SqlQueryInspector($entry['query']);
        $params = SqlParamResolver::resolve($entry['params'], $inspector->placeholders(), $request->get('params'));
        $response = $this->entryResponse($entry, $params, $inspector);

        $pdo = $this->connection();
        $refusal = $this->refusal($inspector, $pdo);
        if ($refusal !== null || $pdo === null) {
            return $this->json($response, $refusal);
        }

        $explainer = new SqlExplainer($pdo);
        $response['driver'] = $explainer->driver();
        $response['server_version'] = $explainer->serverVersion();
        $refusal = $this->explainerRefusal($explainer, $params);
        if ($refusal !== null) {
            return $this->json($response, $refusal);
        }

        $bound = array_map(
            static fn (array $param): array => ['name' => $param['name'], 'value' => $param['value'], 'type' => $param['type']],
            $params
        );

        return $this->json($this->analyse($response, $explainer, $inspector, $bound, $request->get('analyze') === '1'));
    }

    /**
     * Réponse décrivant la requête SQL enregistrée et ses paramètres
     *
     * @param array{id: int, request_id: int, query: string, duration_ms: float, rows_count: int|null,
     *        params: array<int, array{name: int|string, value: mixed, type: int}>, executions: int,
     *        event_date: string|null} $entry Requête SQL enregistrée
     * @param array<int, array{name: int|string, value: mixed, type: int, provided: bool}> $params Paramètres
     * @param SqlQueryInspector $inspector Lecture du texte de la requête
     * @return array<string, mixed>
     */
    private function entryResponse(array $entry, array $params, SqlQueryInspector $inspector): array
    {
        return array_merge(self::EMPTY_RESPONSE, [
            'id' => $entry['id'],
            'token' => $entry['request_id'],
            'query' => $entry['query'],
            'duration_ms' => $entry['duration_ms'],
            'rows_count' => $entry['rows_count'],
            'executions' => $entry['executions'],
            'params' => array_map(
                static fn (array $param): array => ['name' => $param['name'], 'value' => $param['value'], 'provided' => $param['provided']],
                $params
            ),
            'read_only' => $inspector->isReadOnly(),
        ]);
    }

    /**
     * Raison pour laquelle la requête ne peut pas être analysée (texte ou connexion), null sinon
     *
     * @param SqlQueryInspector $inspector Lecture du texte de la requête
     * @param PDO|null $pdo Connexion à la base de données de l'application
     * @return string|null
     */
    private function refusal(SqlQueryInspector $inspector, ?PDO $pdo): ?string
    {
        return match (true) {
            $inspector->isExplainable() === false
                => 'Seules les requêtes SELECT, INSERT, UPDATE, DELETE et REPLACE ont un plan d\'exécution.',
            $inspector->hasMultipleStatements() === true
                => 'Le texte contient plusieurs requêtes séparées par « ; » : l\'analyse est refusée.',
            $pdo === null
                => 'Aucune connexion à la base de données de l\'application : vérifiez DB_HOST_STATUS=true dans le fichier .env.',
            default => null,
        };
    }

    /**
     * Raison pour laquelle le moteur ne peut pas analyser la requête (moteur non pris
     * en charge, paramètres sans valeur), null sinon
     *
     * @param SqlExplainer $explainer Analyse du plan d'exécution
     * @param array<int, array{name: int|string, value: mixed, type: int, provided: bool}> $params Paramètres
     * @return string|null
     */
    private function explainerRefusal(SqlExplainer $explainer, array $params): ?string
    {
        if ($explainer->isSupported() === false) {
            return 'L\'analyse EXPLAIN n\'est disponible que pour MySQL, MariaDB et SQLite.';
        }

        $missing = array_column(array_filter($params, static fn (array $param): bool => $param['provided'] === false), 'name');

        return ($missing === [] ? null : 'Renseignez la valeur des paramètres ' . implode(', ', $missing) . ' pour analyser la requête.');
    }

    /**
     * Analyse la requête : plan estimé, plan mesuré (ANALYZE), structure des tables
     * et axes d'amélioration
     *
     * @param array<string, mixed> $response Réponse décrivant la requête
     * @param SqlExplainer $explainer Analyse du plan d'exécution
     * @param SqlQueryInspector $inspector Lecture du texte de la requête
     * @param array<int, array{name: int|string, value: mixed, type: int}> $bound Paramètres liés à la requête
     * @param bool $analyze Exécuter réellement la requête
     * @return array<string, mixed>
     */
    private function analyse(
        array $response,
        SqlExplainer $explainer,
        SqlQueryInspector $inspector,
        array $bound,
        bool $analyze
    ): array {
        try {
            $response['plan'] = $explainer->explain($inspector->getSql(), $bound);
            $rows = $response['plan']['rows'];
            if ($analyze === true && $inspector->isReadOnly() === true) {
                $response['analysis'] = $explainer->analyze($inspector->getSql(), $bound);
                // MariaDB : le plan mesuré (colonnes r_rows, r_filtered) remplace le plan estimé
                $rows = ($response['analysis']['rows'] !== [] ? $response['analysis']['rows'] : $rows);
            }
        } catch (\PDOException $exception) {
            return array_merge($response, [
                'error' => $exception->getMessage(),
                'message' => 'La base de données a refusé l\'analyse de cette requête.',
            ]);
        }

        if ($analyze === true && $inspector->isReadOnly() === false) {
            $response['message'] = 'EXPLAIN ANALYZE exécute réellement la requête : il est réservé aux requêtes de lecture (SELECT).';
        }

        return $this->withAdvice($response, $inspector, $explainer->accesses($rows));
    }

    /**
     * Ajoute à la réponse les accès aux tables, la structure des tables et les axes d'amélioration
     *
     * @param array<string, mixed> $response Réponse
     * @param SqlQueryInspector $inspector Lecture du texte de la requête
     * @param array<int, array<string, mixed>> $accesses Accès aux tables normalisés
     * @return array<string, mixed>
     */
    private function withAdvice(array $response, SqlQueryInspector $inspector, array $accesses): array
    {
        $tables = (new SqlTableInspector(DatabaseService::getConnect()))->tables($inspector->tableNames());
        $advisor = new SqlExplainAdvisor($inspector, $accesses, $tables, [
            'executions' => (int) $response['executions'],
            'rows_count' => $response['rows_count'],
            'driver' => (string) $response['driver'],
        ]);

        return array_merge($response, [
            'accesses' => $accesses,
            'tables' => array_values($tables),
            'findings' => $advisor->advise(),
            'suggestions' => $advisor->suggestions(),
        ]);
    }

    /**
     * Retourne la connexion à la base de données de l'application, null si elle n'est pas ouverte
     *
     * @return PDO|null
     */
    private function connection(): ?PDO
    {
        try {
            return DatabaseService::getConnect();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Encode la réponse en JSON, avec le message donné (sinon celui de la réponse)
     *
     * @param array<string, mixed> $response Réponse
     * @param string|null $message Raison pour laquelle l'analyse n'a pas pu être faite
     * @return string
     */
    private function json(array $response, ?string $message = null): string
    {
        $response['message'] = $message ?? $response['message'];

        return (string) json_encode(
            $response,
            JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION | JSON_PARTIAL_OUTPUT_ON_ERROR
        );
    }
}

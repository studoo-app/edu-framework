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
use Studoo\EduFramework\Core\Profiler\Sql\SqlQueryInspector;
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

        $response = [
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

        $id = (string) $request->get('id');
        if (preg_match('/^\d{1,18}$/', $id) !== 1) {
            return $this->json($response, 'Identifiant de requête SQL invalide.');
        }

        $entry = LogsService::getSqlQuery((int) $id);
        if ($entry === null) {
            return $this->json($response, 'Requête SQL introuvable : les logs ont peut-être été vidés.');
        }

        $inspector = new SqlQueryInspector($entry['query']);
        $params = $this->resolveParams($entry['params'], $inspector->placeholders(), $request->get('params'));
        $response = array_merge($response, [
            'id' => $entry['id'],
            'token' => $entry['request_id'],
            'query' => $entry['query'],
            'duration_ms' => $entry['duration_ms'],
            'rows_count' => $entry['rows_count'],
            'executions' => $entry['executions'],
            'params' => array_map(
                static fn (array $param): array => [
                    'name' => $param['name'],
                    'value' => $param['value'],
                    'provided' => $param['provided'],
                ],
                $params
            ),
            'read_only' => $inspector->isReadOnly(),
        ]);

        if ($inspector->isExplainable() === false) {
            return $this->json($response, 'Seules les requêtes SELECT, INSERT, UPDATE, DELETE et REPLACE ont un plan d\'exécution.');
        }
        if ($inspector->hasMultipleStatements() === true) {
            return $this->json($response, 'Le texte contient plusieurs requêtes séparées par « ; » : l\'analyse est refusée.');
        }

        try {
            $explainer = new SqlExplainer(DatabaseService::getConnect());
            $response['driver'] = $explainer->driver();
            $response['server_version'] = $explainer->serverVersion();
        } catch (\Throwable) {
            return $this->json(
                $response,
                'Aucune connexion à la base de données de l\'application : vérifiez DB_HOST_STATUS=true dans le fichier .env.'
            );
        }

        if ($explainer->isSupported() === false) {
            return $this->json($response, 'L\'analyse EXPLAIN n\'est disponible que pour MySQL, MariaDB et SQLite.');
        }

        $missing = array_filter($params, static fn (array $param): bool => $param['provided'] === false);
        if ($missing !== []) {
            return $this->json(
                $response,
                'Renseignez la valeur des paramètres ' . implode(', ', array_column($missing, 'name'))
                . ' pour analyser la requête.'
            );
        }

        $bound = array_map(
            static fn (array $param): array => ['name' => $param['name'], 'value' => $param['value'], 'type' => $param['type']],
            $params
        );

        try {
            $response['plan'] = $explainer->explain($entry['query'], $bound);
            $accesses = $explainer->accesses($response['plan']['rows']);

            if ($request->get('analyze') === '1') {
                if ($inspector->isReadOnly() === false) {
                    $response['message'] = 'EXPLAIN ANALYZE exécute réellement la requête : il est réservé aux requêtes de lecture (SELECT).';
                } else {
                    $response['analysis'] = $explainer->analyze($entry['query'], $bound);
                    // MariaDB : le plan mesuré (colonnes r_rows, r_filtered) remplace le plan estimé
                    if ($response['analysis']['rows'] !== []) {
                        $accesses = $explainer->accesses($response['analysis']['rows']);
                    }
                }
            }
        } catch (\PDOException $exception) {
            $response['error'] = $exception->getMessage();
            return $this->json($response, 'La base de données a refusé l\'analyse de cette requête.');
        }

        $tables = $explainer->tables($inspector->tableNames());
        $response['accesses'] = $accesses;
        $response['tables'] = array_values($tables);
        $advisor = new SqlExplainAdvisor($inspector, $accesses, $tables, [
            'executions' => $entry['executions'],
            'rows_count' => $entry['rows_count'],
            'driver' => $response['driver'],
        ]);
        $response['findings'] = $advisor->advise();
        $response['suggestions'] = $advisor->suggestions();

        return $this->json($response, $response['message']);
    }

    /**
     * Associe à chaque paramètre de la requête sa valeur : celle saisie dans le
     * profiler (params), sinon celle enregistrée à l'exécution
     *
     * @param array<int, array{name: int|string, value: mixed, type: int}> $captured Paramètres enregistrés
     * @param array<int, int|string> $placeholders Paramètres présents dans la requête
     * @param string|null $override Valeurs saisies, objet JSON (Exemple : {":id": "3"})
     * @return array<int, array{name: int|string, value: mixed, type: int, provided: bool}>
     */
    private function resolveParams(array $captured, array $placeholders, ?string $override): array
    {
        $known = [];
        foreach ($captured as $param) {
            $name = $param['name'];
            if (is_string($name) === true && str_starts_with($name, ':') === false) {
                $name = ':' . $name;
            }
            $known[(string) $name] = $param;
        }

        $values = ($override === null || $override === '' ? null : json_decode($override, true));
        $values = (is_array($values) === true ? $values : []);

        $params = [];
        foreach ($placeholders as $name) {
            $key = (string) $name;
            $type = (int) ($known[$key]['type'] ?? PDO::PARAM_STR);
            if (array_key_exists($key, $values) === true && (is_scalar($values[$key]) === true || $values[$key] === null)) {
                $value = $values[$key];
                $wasInt = (isset($known[$key]) === true
                    && (is_int($known[$key]['value']) === true || $type === PDO::PARAM_INT));
                if (is_string($value) === true && preg_match('/^-?\d{1,18}$/', $value) === 1
                    && ($wasInt === true || isset($known[$key]) === false)) {
                    $value = (int) $value;
                }
                $params[] = ['name' => $name, 'value' => $value, 'type' => $type, 'provided' => true];
            } elseif (isset($known[$key]) === true) {
                $params[] = ['name' => $name, 'value' => $known[$key]['value'], 'type' => $type, 'provided' => true];
            } else {
                $params[] = ['name' => $name, 'value' => null, 'type' => $type, 'provided' => false];
            }
        }

        return $params;
    }

    /**
     * Encode la réponse en JSON avec un éventuel message
     *
     * @param array<string, mixed> $response Réponse
     * @param string|null $message Raison pour laquelle l'analyse n'a pas pu être faite
     * @return string
     */
    private function json(array $response, ?string $message): string
    {
        $response['message'] = $message;

        return (string) json_encode(
            $response,
            JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRESERVE_ZERO_FRACTION | JSON_PARTIAL_OUTPUT_ON_ERROR
        );
    }
}

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

use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Logs\LogsService;

/**
 * Class DebugSqlLogsController
 * Endpoint interne du profiler : retourne les requêtes SQL exécutées
 * pendant une requête HTTP (rattachées par le token du log de la requête),
 * au format JSON.
 * Cette route est enregistrée uniquement en mode dev (APP_ENV=dev),
 * elle n'est donc pas accessible en production.
 *
 * @package Studoo\EduFramework\Core\Controller\Debug
 */
class DebugSqlLogsController implements ControllerInterface
{
    /**
     * Retourne les requêtes SQL d'une requête HTTP au format JSON
     * Paramètres de la requête :
     * - token : identifiant du log de la requête HTTP (obligatoire)
     *
     * Réponse :
     * - token : identifiant du log de la requête HTTP (null si invalide)
     * - total : nombre de requêtes SQL
     * - total_duration_ms : temps SQL cumulé en millisecondes
     * - groups : requêtes identiques regroupées (query, count, duration_ms)
     * - queries : liste chronologique des requêtes (id, query, duration_ms, rows_count, event_date)
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

        $token = ltrim((string) $request->get('token'), '#');
        if (preg_match('/^\d{1,18}$/', $token) !== 1) {
            return json_encode([
                'token' => null,
                'total' => 0,
                'total_duration_ms' => 0,
                'groups' => [],
                'queries' => [],
            ]);
        }

        $tokenId = (int) $token;
        $queries = LogsService::getSqlQueries($tokenId);

        return json_encode([
            'token' => $tokenId,
            'total' => count($queries),
            'total_duration_ms' => round(array_sum(array_column($queries, 'duration_ms')), 1),
            'groups' => LogsService::getSqlGroups($tokenId),
            'queries' => $queries,
        ]);
    }
}

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
 * Class DebugLogsController
 * Endpoint interne du profiler : retourne les logs en JSON.
 * Cette route est enregistrée uniquement en mode dev (APP_ENV=dev),
 * elle n'est donc pas accessible en production.
 *
 * @package Studoo\EduFramework\Core\Controller\Debug
 */
class DebugLogsController implements ControllerInterface
{
    /**
     * Retourne une page de logs au format JSON
     * Paramètres de la requête :
     * - page : numéro de page (défaut 1)
     * - limit : nombre de logs par page (défaut 20, max 100)
     * - method : filtre sur la méthode HTTP (GET, POST, ...)
     * - status : filtre sur la famille de code statut (2, 3, 4 ou 5)
     * - search : recherche textuelle dans l'événement
     *
     * @param Request $request Requête HTTP
     * @return string|null
     */
    public function execute(Request $request): string|null
    {
        header('Content-Type: application/json; charset=utf-8');

        $filters = [
            'method' => $this->filterMethod($request->get('method')),
            'status' => $this->filterStatus($request->get('status')),
            'search' => $request->get('search'),
        ];

        $limit = min(100, max(1, (int) ($request->get('limit') ?? 20)));
        $page = max(1, (int) ($request->get('page') ?? 1));

        $total = LogsService::countLogs($filters);
        $pages = max(1, (int) ceil($total / $limit));
        $page = min($page, $pages);

        return json_encode([
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
            'logs' => LogsService::getLogs($limit, ($page - 1) * $limit, $filters),
        ]);
    }

    /**
     * Valide le filtre sur la méthode HTTP
     *
     * @param string|null $method Méthode HTTP
     * @return string|null
     */
    private function filterMethod(string|null $method): string|null
    {
        $allowed = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];

        return (is_string($method) === true && in_array(strtoupper($method), $allowed, true) === true)
            ? strtoupper($method)
            : null;
    }

    /**
     * Valide le filtre sur la famille de code statut
     *
     * @param string|null $status Famille de code statut (2xx à 5xx)
     * @return string|null
     */
    private function filterStatus(string|null $status): string|null
    {
        return (is_string($status) === true && in_array($status, ['2', '3', '4', '5'], true) === true)
            ? $status
            : null;
    }
}

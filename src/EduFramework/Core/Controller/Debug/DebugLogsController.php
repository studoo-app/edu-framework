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
     * - status : filtre sur le code statut (500) ou sur sa famille (2, 3, 4 ou 5)
     * - search : recherche textuelle dans l'événement
     * - ip : filtre sur l'adresse IP du client
     * - url : filtre sur le chemin de la requête
     * - token : filtre sur l'identifiant du log
     * - from / until : bornes de date (YYYY-MM-DD)
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
            'ip' => $this->filterPattern($request->get('ip'), '/^[0-9a-fA-F:.]{1,45}$/'),
            'url' => $request->get('url'),
            'token' => $this->filterPattern(ltrim((string) $request->get('token'), '#'), '/^\d{1,18}$/'),
            'from' => $this->filterPattern($request->get('from'), '/^\d{4}-\d{2}-\d{2}$/'),
            'until' => $this->filterPattern($request->get('until'), '/^\d{4}-\d{2}-\d{2}$/'),
        ];
        $filters = $filters ?? [];

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
            'counts' => ['all' => LogsService::countLogs(['status' => null] + $filters)]
                + LogsService::countByStatusFamily($filters),
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
     * Valide le filtre sur le code statut (500) ou sur sa famille (5)
     *
     * @param string|null $status Code statut ou famille de code statut (2xx à 5xx)
     * @return string|null
     */
    private function filterStatus(string|null $status): string|null
    {
        return $this->filterPattern($status, '/^(?:[2-5]|[1-5]\d{2})$/');
    }

    /**
     * Valide une valeur de filtre à l'aide d'une expression régulière
     *
     * @param string|null $value Valeur à valider
     * @param string $pattern Expression régulière
     * @return string|null La valeur si elle est valide, sinon null
     */
    private function filterPattern(string|null $value, string $pattern): string|null
    {
        return (is_string($value) === true && preg_match($pattern, $value) === 1) ? $value : null;
    }
}

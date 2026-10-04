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
 * Endpoint interne de la barre de debug : retourne les logs en JSON.
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
     *
     * @param Request $request Requête HTTP
     * @return string|null
     */
    public function execute(Request $request): string|null
    {
        header('Content-Type: application/json; charset=utf-8');

        $limit = min(100, max(1, (int) ($request->get('limit') ?? 20)));
        $page = max(1, (int) ($request->get('page') ?? 1));

        $total = LogsService::countLogs();
        $pages = max(1, (int) ceil($total / $limit));
        $page = min($page, $pages);

        return json_encode([
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
            'logs' => LogsService::getLogs($limit, ($page - 1) * $limit),
        ]);
    }
}

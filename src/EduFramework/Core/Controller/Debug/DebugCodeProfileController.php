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
use Studoo\EduFramework\Core\Profiler\CodeProfileAggregator;

/**
 * Class DebugCodeProfileController
 * Endpoint interne de la barre de debug et du profiler : retourne le profil
 * du code PHP exécuté pendant une requête HTTP (rattaché par le token du log
 * de la requête), au format JSON.
 * Cette route est enregistrée uniquement en mode dev (APP_ENV=dev),
 * elle n'est donc pas accessible en production.
 *
 * @package Studoo\EduFramework\Core\Controller\Debug
 */
class DebugCodeProfileController implements ControllerInterface
{
    /**
     * Retourne le profil de code d'une requête HTTP au format JSON
     * Paramètres de la requête :
     * - token : identifiant du log de la requête HTTP (obligatoire)
     * - full=1 : ajoute le détail de toutes les fonctions et le graphe d'appels
     * - format=speedscope : retourne les piles d'appels au format Speedscope
     *   (fichier à ouvrir sur https://www.speedscope.app pour le flamegraph)
     *
     * Réponse :
     * - token : identifiant du log de la requête HTTP (null si invalide)
     * - status : ready (profil enregistré) ou pending (pas encore enregistré :
     *   la page peut s'afficher avant la fin de la requête)
     * - summary : driver, period_ms, samples, wall_ms, peak_mb, functions_count
     * - top : fonctions qui consomment le plus de temps propre
     * - functions, edges : avec full=1 uniquement
     *
     * @param Request $request Requête HTTP
     * @return string|null
     */
    public function execute(Request $request): string|null
    {
        $speedscope = ((string) $request->get('format') === 'speedscope');

        // L'en-tête n'est envoyé que si la sortie n'a pas déjà commencé
        // (Exemple : exécution dans un contexte de test)
        if (headers_sent() === false) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $token = ltrim((string) $request->get('token'), '#');
        if (preg_match('/^\d{1,18}$/', $token) !== 1) {
            return json_encode(['token' => null, 'status' => 'pending', 'summary' => null, 'top' => []]);
        }

        $tokenId = (int) $token;
        $full = ((string) $request->get('full') === '1');
        $profile = LogsService::getCodeProfile($tokenId, ($full === true || $speedscope === true));

        if ($profile === null) {
            return json_encode(['token' => $tokenId, 'status' => 'pending', 'summary' => null, 'top' => []]);
        }

        if ($speedscope === true) {
            if (headers_sent() === false) {
                header('Content-Disposition: attachment; filename="edu-profile-' . $tokenId . '.speedscope.json"');
            }
            $data = $profile['data']['speedscope'] ?? [];
            if (is_array($data) === true && isset($data['profiles'][0]) === true) {
                $data['name'] = 'Edu Framework #' . $tokenId;
                $data['profiles'][0]['name'] = 'Requête #' . $tokenId;
            }
            return json_encode($data);
        }

        $response = [
            'token' => $tokenId,
            'status' => 'ready',
            'summary' => [
                'driver' => $profile['driver'],
                'period_ms' => $profile['period_ms'],
                'samples' => $profile['samples'],
                'wall_ms' => $profile['wall_ms'],
                'peak_mb' => $profile['peak_mb'],
                'functions_count' => $profile['functions_count'],
            ],
            'top' => $profile['top'],
        ];

        if ($full === true) {
            $data = $profile['data'];
            $response['functions'] = CodeProfileAggregator::byFunction($data);
            $response['edges'] = CodeProfileAggregator::callGraph(
                (is_array($data['speedscope'] ?? null) === true ? $data['speedscope'] : []),
                (float) ($data['period_ms'] ?? $profile['period_ms'])
            );
        }

        return json_encode($response);
    }
}

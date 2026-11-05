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
     * - format=flame : retourne l'arbre du flamegraph intégré au profiler (clé tree), avec :
     *   - order=heavy (défaut, appels regroupés) ou time (ordre chronologique) ;
     *   - compare=token : flamegraph différentiel par rapport à une autre requête ;
     *   - min : part minimale du temps pour garder un appel (défaut 0.001, soit 0,1 %) ;
     *   - depth : profondeur maximale de l'arbre (défaut : illimitée)
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
        $flame = ((string) $request->get('format') === 'flame');

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
        $profile = LogsService::getCodeProfile($tokenId, ($full === true || $speedscope === true || $flame === true));

        if ($profile === null) {
            return json_encode(['token' => $tokenId, 'status' => 'pending', 'summary' => null, 'top' => []]);
        }

        if ($speedscope === true) {
            return $this->speedscope($tokenId, $profile);
        }
        if ($flame === true) {
            return (string) json_encode($this->flame($tokenId, $profile, $request));
        }

        $response = [
            'token' => $tokenId,
            'status' => 'ready',
            'summary' => $this->summary($profile),
            'top' => $profile['top'],
        ];

        if ($full === true) {
            $response += $this->details($profile);
        }

        return json_encode($response);
    }

    /**
     * Résumé d'un profil : driver, période, échantillons, durée, mémoire et nombre de fonctions
     *
     * @param array<string, mixed> $profile Profil (LogsService::getCodeProfile)
     * @return array<string, mixed>
     */
    private function summary(array $profile): array
    {
        return [
            'driver' => $profile['driver'],
            'period_ms' => $profile['period_ms'],
            'samples' => $profile['samples'],
            'wall_ms' => $profile['wall_ms'],
            'peak_mb' => $profile['peak_mb'],
            'functions_count' => $profile['functions_count'],
        ];
    }

    /**
     * Arbre du flamegraph intégré au profiler (format=flame) :
     * Left Heavy, chronologique, ou différentiel avec compare=token
     *
     * @param int $tokenId Identifiant du log de la requête HTTP
     * @param array<string, mixed> $profile Profil avec ses données (LogsService::getCodeProfile)
     * @param Request $request Requête HTTP (order, compare, min, depth)
     * @return array<string, mixed> token, status, order, summary, tree (et compare, compare_summary pour un diff)
     */
    private function flame(int $tokenId, array $profile, Request $request): array
    {
        $order = ((string) $request->get('order') === 'time' ? 'time' : 'heavy');
        $min = (is_numeric($request->get('min')) === true
            ? min(0.1, max(0.0, (float) $request->get('min')))
            : CodeProfileAggregator::FLAME_MIN_RATIO);
        $depth = (is_numeric($request->get('depth')) === true ? min(500, max(0, (int) $request->get('depth'))) : 0);
        $compare = ltrim((string) $request->get('compare'), '#');

        if ($compare === '') {
            $tree = CodeProfileAggregator::flameTree($profile['data'], $order, $min, $depth);
            $tree['name'] = 'Requête #' . $tokenId;

            return ['token' => $tokenId, 'status' => 'ready', 'order' => $order, 'summary' => $this->summary($profile), 'tree' => $tree];
        }

        // Flamegraph différentiel : toujours en Left Heavy (les chemins d'appels sont comparés)
        $compareId = (preg_match('/^\d{1,18}$/', $compare) === 1 ? (int) $compare : null);
        $base = ($compareId === null ? null : LogsService::getCodeProfile($compareId, true));
        if ($base === null) {
            return ['token' => $tokenId, 'compare' => $compareId, 'status' => 'pending', 'order' => 'heavy', 'summary' => null, 'tree' => null];
        }

        $tree = CodeProfileAggregator::diffTree($profile['data'], $base['data'], $min, $depth);
        $tree['name'] = 'Requête #' . $tokenId . ' / #' . $compareId;

        return [
            'token' => $tokenId,
            'compare' => $compareId,
            'status' => 'ready',
            'order' => 'heavy',
            'summary' => $this->summary($profile),
            'compare_summary' => $this->summary($base),
            'tree' => $tree,
        ];
    }

    /**
     * Retourne les piles d'appels du profil au format Speedscope (fichier téléchargé)
     *
     * @param int $tokenId Identifiant du log de la requête HTTP
     * @param array<string, mixed> $profile Profil avec ses données (LogsService::getCodeProfile)
     * @return string
     */
    private function speedscope(int $tokenId, array $profile): string
    {
        if (headers_sent() === false) {
            header('Content-Disposition: attachment; filename="edu-profile-' . $tokenId . '.speedscope.json"');
        }
        $data = $profile['data']['speedscope'] ?? [];
        if (is_array($data) === true && isset($data['profiles'][0]) === true) {
            $data['name'] = 'Edu Framework #' . $tokenId;
            $data['profiles'][0]['name'] = 'Requête #' . $tokenId;
        }

        return (string) json_encode($data);
    }

    /**
     * Détail complet du profil : toutes les fonctions et le graphe d'appels
     *
     * @param array<string, mixed> $profile Profil avec ses données (LogsService::getCodeProfile)
     * @return array{functions: array<int, array<string, mixed>>, edges: array<int, array<string, mixed>>}
     */
    private function details(array $profile): array
    {
        $data = $profile['data'];
        $speedscope = (is_array($data['speedscope'] ?? null) === true ? $data['speedscope'] : []);

        return [
            'functions' => CodeProfileAggregator::byFunction($data),
            'edges' => CodeProfileAggregator::callGraph($speedscope, (float) ($data['period_ms'] ?? $profile['period_ms'])),
        ];
    }
}

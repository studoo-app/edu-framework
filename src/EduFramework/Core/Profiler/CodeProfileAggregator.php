<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Profiler;


use Studoo\EduFramework\Core\ConfigCore;

/**
 * Class CodeProfileAggregator
 * Transforme les données brutes d'un driver de profilage (Excimer) en
 * informations lisibles par la barre de debug et le profiler :
 * - temps propre (self) et inclusif estimés par fonction, en ms et en % ;
 * - origine de chaque fonction : code de l'application (app/), du framework,
 *   des dépendances (vendor/) ou autre ;
 * - graphe d'appels (appelant => appelé) reconstruit depuis les piles d'appels.
 *
 * Rappel : un profiler par échantillonnage relève la pile d'appels toutes les
 * `period_ms` millisecondes. Une fonction présente dans 30 échantillons à 0,1 ms
 * a donc tourné environ 3 ms. Une fonction plus courte que la période peut
 * ne pas apparaître du tout.
 *
 * @package Studoo\EduFramework\Core\Profiler
 */
class CodeProfileAggregator
{
    /**
     * Part minimale du temps total pour qu'un appel soit gardé dans le flamegraph (0,1 %)
     */
    public const FLAME_MIN_RATIO = 0.001;

    /**
     * Calcule le détail par fonction, trié par temps propre décroissant
     *
     * @param array<string, mixed> $data Données du driver (period_ms, samples, functions)
     * @return array<int, array<string, mixed>> Fonctions (name, origin, file, line, self, inclusive,
     *         self_ms, inclusive_ms, self_pct, inclusive_pct)
     */
    public static function byFunction(array $data): array
    {
        $periodMs = (float) ($data['period_ms'] ?? 0);
        $total = max(1, (int) ($data['samples'] ?? 0));
        $functions = [];

        foreach (($data['functions'] ?? []) as $name => $info) {
            $self = (float) ($info['self'] ?? 0);
            $inclusive = (float) ($info['inclusive'] ?? 0);
            $file = (isset($info['file']) === true ? (string) $info['file'] : null);
            $functions[] = [
                'name' => self::displayName((string) $name),
                'origin' => self::origin($file),
                'file' => ($file === null ? null : self::relativePath($file)),
                'line' => (isset($info['line']) === true ? (int) $info['line'] : null),
                'self' => $self,
                'inclusive' => $inclusive,
                'self_ms' => round($self * $periodMs, 2),
                'inclusive_ms' => round($inclusive * $periodMs, 2),
                'self_pct' => round($self * 100 / $total, 1),
                'inclusive_pct' => round($inclusive * 100 / $total, 1),
            ];
        }

        usort(
            $functions,
            static fn (array $left, array $right): int
                => [$right['self'], $right['inclusive']] <=> [$left['self'], $left['inclusive']]
        );

        return $functions;
    }

    /**
     * Retourne les fonctions qui consomment le plus de temps propre
     *
     * @param array<int, array<string, mixed>> $functions Résultat de byFunction()
     * @param int $limit Nombre de fonctions retournées
     * @return array<int, array<string, mixed>>
     */
    public static function top(array $functions, int $limit = 20): array
    {
        $functions = array_values(array_filter($functions, static fn (array $function): bool => $function['self'] > 0));

        return array_slice($functions, 0, $limit);
    }

    /**
     * Reconstruit le graphe d'appels depuis les piles d'appels au format Speedscope :
     * pour chaque couple appelant => appelé, le nombre d'échantillons dans lesquels
     * l'appelant était en train d'exécuter l'appelé
     *
     * @param array<string, mixed> $speedscope Données Speedscope du driver
     * @param float $periodMs Période d'échantillonnage en millisecondes
     * @return array<int, array{caller: string, callee: string, samples: int, ms: float}>
     */
    public static function callGraph(array $speedscope, float $periodMs): array
    {
        $frames = $speedscope['shared']['frames'] ?? [];
        $profile = $speedscope['profiles'][0] ?? [];
        $samples = $profile['samples'] ?? [];
        $weights = $profile['weights'] ?? [];
        $periodNs = $periodMs * 1000000;
        $edges = [];

        foreach ($samples as $index => $stack) {
            // Un échantillon peut représenter plusieurs périodes (dépassement du timer)
            $count = ($periodNs > 0 ? max(1, (int) round(($weights[$index] ?? $periodNs) / $periodNs)) : 1);
            $seen = [];
            $stackSize = count($stack);
            for ($i = 1; $i < $stackSize; $i++) {
                $caller = self::displayName((string) ($frames[$stack[$i - 1]]['name'] ?? '?'));
                $callee = self::displayName((string) ($frames[$stack[$i]]['name'] ?? '?'));
                $key = $caller . "\0" . $callee;
                // Une fonction récursive ne compte qu'une fois par échantillon
                if (isset($seen[$key]) === true) {
                    continue;
                }
                $seen[$key] = true;
                $edges[$key] ??= ['caller' => $caller, 'callee' => $callee, 'samples' => 0, 'ms' => 0.0];
                $edges[$key]['samples'] += $count;
            }
        }

        foreach ($edges as $key => $edge) {
            $edges[$key]['ms'] = round($edge['samples'] * $periodMs, 2);
        }
        usort($edges, static fn (array $left, array $right): int => $right['samples'] <=> $left['samples']);

        return $edges;
    }

    /**
     * Construit l'arbre du flamegraph depuis les piles d'appels au format Speedscope.
     * Deux ordres sont possibles, comme dans Speedscope :
     * - heavy (Left Heavy) : les appels identiques sont regroupés et triés,
     *   le chemin le plus coûteux à gauche ;
     * - time (Time Order) : les piles sont gardées dans l'ordre chronologique,
     *   seuls les échantillons consécutifs identiques sont regroupés.
     *
     * Chaque nœud contient : name, origin, file, line, start et value (position et
     * largeur en échantillons), self, ms, self_ms, pct et children.
     * Les nœuds plus petits que $minRatio du total sont élagués pour garder un JSON
     * léger : regroupés dans un nœud "… (autres)" en Left Heavy, retirés en Time Order
     * (ils laissent un vide, l'axe du temps reste juste).
     *
     * @param array<string, mixed> $data Données du driver (period_ms, functions, speedscope)
     * @param string $order heavy ou time
     * @param float $minRatio Part minimale du temps total pour garder un nœud (0 : aucun élagage)
     * @param int $maxDepth Profondeur maximale exportée (0 : illimitée)
     * @return array<string, mixed> Nœud racine (la requête entière)
     */
    public static function flameTree(array $data, string $order = 'heavy', float $minRatio = self::FLAME_MIN_RATIO, int $maxDepth = 0): array
    {
        $tree = self::buildTree($data, $order);

        return self::exportNode($tree, 0, 0, 0, [
            'total' => max(1, $tree['nodes'][0]['value']),
            'min' => $tree['nodes'][0]['value'] * max(0.0, $minRatio),
            'max_depth' => $maxDepth,
            'order' => $order,
            'base' => null,
        ], 0);
    }

    /**
     * Construit le flamegraph différentiel de deux profils (vue Left Heavy) :
     * la forme est celle du profil comparé ($data), chaque nœud indique aussi
     * le temps du même chemin d'appels dans le profil de référence ($baseData).
     * Ajoute à chaque nœud : base_ms (temps dans la référence) et delta_ms
     * (positif : plus lent que la référence, négatif : plus rapide).
     * Un chemin absent du profil comparé n'est pas dessiné : son temps
     * apparaît dans le delta de son parent.
     *
     * @param array<string, mixed> $data Données du driver du profil comparé
     * @param array<string, mixed> $baseData Données du driver du profil de référence
     * @param float $minRatio Part minimale du temps total pour garder un nœud
     * @param int $maxDepth Profondeur maximale exportée (0 : illimitée)
     * @return array<string, mixed> Nœud racine
     */
    public static function diffTree(array $data, array $baseData, float $minRatio = self::FLAME_MIN_RATIO, int $maxDepth = 0): array
    {
        $tree = self::buildTree($data, 'heavy');
        $base = self::buildTree($baseData, 'heavy');

        return self::exportNode($tree, 0, 0, 0, [
            'total' => max(1, $tree['nodes'][0]['value']),
            'min' => $tree['nodes'][0]['value'] * max(0.0, $minRatio),
            'max_depth' => $maxDepth,
            'order' => 'heavy',
            'base' => $base,
        ], 0);
    }

    /**
     * Fusionne les piles d'appels en un arbre à plat (tableau de nœuds reliés par leurs indices).
     * Le nœud 0 est la racine : la requête entière.
     *
     * @param array<string, mixed> $data Données du driver (period_ms, functions, speedscope)
     * @param string $order heavy (fusion de tous les appels identiques) ou time (fusion des seuls appels consécutifs)
     * @return array{nodes: array<int, array<string, mixed>>, info: array<string, array{file: string|null, line: int|null}>, period: float}
     */
    private static function buildTree(array $data, string $order): array
    {
        $speedscope = (is_array($data['speedscope'] ?? null) === true ? $data['speedscope'] : []);
        $frames = $speedscope['shared']['frames'] ?? [];
        $profile = $speedscope['profiles'][0] ?? [];
        $samples = $profile['samples'] ?? [];
        $weights = $profile['weights'] ?? [];
        $periodMs = (float) ($data['period_ms'] ?? 0);
        $periodNs = $periodMs * 1000000;
        $functions = $data['functions'] ?? [];

        // Nom affiché et emplacement de chaque frame, calculés une seule fois
        $names = [];
        $info = [];
        foreach ($frames as $index => $frame) {
            $raw = (string) ($frame['name'] ?? '?');
            $name = self::displayName($raw);
            $names[$index] = $name;
            $file = $frame['file'] ?? ($functions[$raw]['file'] ?? null);
            $line = $frame['line'] ?? ($functions[$raw]['line'] ?? null);
            $info[$name] ??= [
                'file' => ($file === null || $file === '' ? null : (string) $file),
                'line' => ($line === null ? null : (int) $line),
            ];
        }

        $nodes = [['name' => null, 'start' => 0, 'value' => 0, 'self' => 0, 'children' => []]];
        $time = 0;
        foreach ($samples as $index => $stack) {
            // Un échantillon peut représenter plusieurs périodes (dépassement du timer)
            $count = ($periodNs > 0 ? max(1, (int) round(($weights[$index] ?? $periodNs) / $periodNs)) : 1);
            $current = 0;
            $nodes[0]['value'] += $count;
            foreach ($stack as $frameIndex) {
                $name = $names[$frameIndex] ?? '?';
                $child = null;
                if ($order === 'time') {
                    // Chronologique : on ne prolonge que le dernier appel, s'il vient de se terminer
                    $last = end($nodes[$current]['children']);
                    if ($last !== false && $nodes[$last]['name'] === $name
                        && $nodes[$last]['start'] + $nodes[$last]['value'] === $time) {
                        $child = $last;
                    }
                } else {
                    $child = $nodes[$current]['children'][$name] ?? null;
                }
                if ($child === null) {
                    $child = count($nodes);
                    $nodes[] = ['name' => $name, 'start' => $time, 'value' => 0, 'self' => 0, 'children' => []];
                    if ($order === 'time') {
                        $nodes[$current]['children'][] = $child;
                    } else {
                        $nodes[$current]['children'][$name] = $child;
                    }
                }
                $nodes[$child]['value'] += $count;
                $current = $child;
            }
            $nodes[$current]['self'] += $count;
            $time += $count;
        }

        return ['nodes' => $nodes, 'info' => $info, 'period' => $periodMs];
    }

    /**
     * Exporte un nœud de l'arbre et ses enfants (tri, élagage, temps en ms et en %)
     *
     * @param array{nodes: array<int, array<string, mixed>>, info: array<string, array{file: string|null, line: int|null}>, period: float} $tree Arbre construit par buildTree()
     * @param int $index Indice du nœud exporté
     * @param int $start Position du nœud (en échantillons) dans la vue Left Heavy
     * @param int $depth Profondeur du nœud (0 : la racine)
     * @param array<string, mixed> $context total, min, max_depth, order et base (arbre de référence du diff)
     * @param int|null $baseIndex Indice du même chemin d'appels dans l'arbre de référence
     * @return array<string, mixed>
     */
    private static function exportNode(array $tree, int $index, int $start, int $depth, array $context, ?int $baseIndex): array
    {
        $node = $tree['nodes'][$index];
        $name = ($node['name'] ?? 'Requête');
        $file = ($node['name'] === null ? null : ($tree['info'][$name]['file'] ?? null));
        $periodMs = $tree['period'];
        $exported = [
            'name' => $name,
            'origin' => ($node['name'] === null ? 'other' : self::origin($file)),
            'file' => ($file === null ? null : self::relativePath($file)),
            'line' => ($node['name'] === null ? null : ($tree['info'][$name]['line'] ?? null)),
            'start' => ($context['order'] === 'time' ? $node['start'] : $start),
            'value' => $node['value'],
            'self' => $node['self'],
            'ms' => round($node['value'] * $periodMs, 2),
            'self_ms' => round($node['self'] * $periodMs, 2),
            'pct' => round($node['value'] * 100 / $context['total'], 1),
        ];

        $base = $context['base'];
        if ($base !== null) {
            $baseMs = ($baseIndex === null ? 0.0 : round($base['nodes'][$baseIndex]['value'] * $base['period'], 2));
            $exported['base_ms'] = $baseMs;
            $exported['delta_ms'] = round($exported['ms'] - $baseMs, 2);
        }

        $children = array_values($node['children']);
        if ($context['max_depth'] > 0 && $depth >= $context['max_depth']) {
            $exported['children'] = [];
            return $exported;
        }
        if ($context['order'] !== 'time') {
            usort($children, static fn (int $left, int $right): int
                => $tree['nodes'][$right]['value'] <=> $tree['nodes'][$left]['value']);
        }

        $exported['children'] = [];
        $offset = $start;
        $others = 0;
        foreach ($children as $child) {
            $value = $tree['nodes'][$child]['value'];
            if ($value < $context['min']) {
                $others += $value;
                continue;
            }
            $childBase = null;
            if ($base !== null && $baseIndex !== null) {
                $childBase = $base['nodes'][$baseIndex]['children'][$tree['nodes'][$child]['name']] ?? null;
            }
            $exported['children'][] = self::exportNode($tree, $child, $offset, $depth + 1, $context, $childBase);
            $offset += $value;
        }

        // Left Heavy : les petits appels sont regroupés à droite pour garder la largeur exacte
        if ($others > 0 && $context['order'] !== 'time') {
            $exported['children'][] = [
                'name' => '… (autres)',
                'origin' => 'other',
                'file' => null,
                'line' => null,
                'start' => $offset,
                'value' => $others,
                'self' => 0,
                'ms' => round($others * $periodMs, 2),
                'self_ms' => 0.0,
                'pct' => round($others * 100 / $context['total'], 1),
                'children' => [],
            ];
            if ($base !== null) {
                // Appels regroupés : pas de chemin unique à comparer
                $exported['children'][count($exported['children']) - 1] += ['base_ms' => null, 'delta_ms' => null];
            }
        }

        return $exported;
    }

    /**
     * Détermine l'origine d'une fonction d'après le fichier où elle est définie
     *
     * @param string|null $file Chemin du fichier
     * @return string app, framework, vendor ou other
     */
    public static function origin(?string $file): string
    {
        if ($file === null || $file === '') {
            return 'other';
        }

        $file = str_replace('\\', '/', $file);
        $base = self::basePath();

        if (str_contains($file, '/vendor/studoo/edu-framework/') === true) {
            return 'framework';
        }
        if (str_contains($file, '/vendor/') === true) {
            return 'vendor';
        }
        if ($base !== null && str_starts_with($file, $base) === true) {
            $relative = substr($file, strlen($base));
            return match (true) {
                str_starts_with($relative, 'app/') => 'app',
                str_starts_with($relative, 'src/'), str_starts_with($relative, 'public/'), str_starts_with($relative, 'bin/') => 'framework',
                default => 'other',
            };
        }

        return 'other';
    }

    /**
     * @param string $file Chemin absolu
     * @return string Chemin relatif à la racine du projet (base_path), sinon le chemin d'origine
     */
    private static function relativePath(string $file): string
    {
        $normalized = str_replace('\\', '/', $file);
        $base = self::basePath();

        return ($base !== null && str_starts_with($normalized, $base) === true)
            ? substr($normalized, strlen($base))
            : $file;
    }

    /**
     * Nom affiché d'une fonction : le code exécuté hors fonction (fichier
     * de la page) est affiché par son chemin relatif
     *
     * @param string $name Nom fourni par le driver
     * @return string
     */
    private static function displayName(string $name): string
    {
        if (str_contains($name, '/') === true && str_contains($name, '{closure') === false) {
            return self::relativePath($name);
        }

        return $name;
    }

    /**
     * @return string|null Racine du projet normalisée, terminée par un slash
     */
    private static function basePath(): ?string
    {
        $base = ConfigCore::getConfig('base_path');
        if (is_string($base) === false || $base === '') {
            return null;
        }
        $real = realpath($base);

        return rtrim(str_replace('\\', '/', ($real === false ? $base : $real)), '/') . '/';
    }
}

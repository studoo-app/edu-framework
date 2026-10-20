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

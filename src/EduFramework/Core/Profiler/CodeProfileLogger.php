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


use PDO;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Logs\LogsService;

/**
 * Class CodeProfileLogger
 * Enregistre les profils de code dans la base des logs (table serv_code_profiles),
 * rattachés au token de la requête HTTP comme les requêtes SQL (serv_sql_logs).
 * Un résumé (temps, échantillons, fonctions les plus coûteuses) est calculé à
 * l'écriture pour que la barre de debug l'affiche sans recalcul ; les données
 * complètes sont compressées. Seuls les derniers profils sont conservés
 * (variable EDU_PROFILER_KEEP, 50 par défaut).
 *
 * @package Studoo\EduFramework\Core\Profiler
 */
class CodeProfileLogger
{
    /**
     * Nombre de profils conservés par défaut
     */
    public const DEFAULT_KEEP = 50;

    /**
     * Enregistre le profil de code d'une requête HTTP
     *
     * @param int $requestId Token de la requête HTTP
     * @param string $driver Nom du driver ayant produit le profil
     * @param float $wallMs Durée du profilage en millisecondes
     * @param float $peakMb Pic de mémoire en Mo
     * @param array<string, mixed> $data Données brutes du driver
     * @return void
     */
    public static function save(int $requestId, string $driver, float $wallMs, float $peakMb, array $data): void
    {
        $functions = CodeProfileAggregator::byFunction($data);
        $payload = gzcompress((string) json_encode($data));

        $pdo = LogsService::getConnect();
        $stmt = $pdo->prepare(
            'INSERT OR REPLACE INTO serv_code_profiles '
            . '(request_id, driver, period_ms, samples, wall_ms, peak_mb, functions_count, top_json, data) '
            . 'VALUES (:request_id, :driver, :period_ms, :samples, :wall_ms, :peak_mb, :functions_count, :top_json, :data)'
        );
        $stmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);
        $stmt->bindValue(':driver', $driver, PDO::PARAM_STR);
        $stmt->bindValue(':period_ms', (float) ($data['period_ms'] ?? 0));
        $stmt->bindValue(':samples', (int) ($data['samples'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':wall_ms', $wallMs);
        $stmt->bindValue(':peak_mb', $peakMb);
        $stmt->bindValue(':functions_count', count($functions), PDO::PARAM_INT);
        $stmt->bindValue(':top_json', (string) json_encode(CodeProfileAggregator::top($functions, 10)), PDO::PARAM_STR);
        $stmt->bindValue(':data', ($payload === false ? '' : $payload), PDO::PARAM_LOB);
        $stmt->execute();

        self::purge(self::keep());
    }

    /**
     * Supprime les profils les plus anciens pour n'en garder que $keep
     *
     * @param int $keep Nombre de profils conservés
     * @return void
     */
    public static function purge(int $keep): void
    {
        $stmt = LogsService::getConnect()->prepare(
            'DELETE FROM serv_code_profiles WHERE id NOT IN '
            . '(SELECT id FROM serv_code_profiles ORDER BY id DESC LIMIT :keep)'
        );
        $stmt->bindValue(':keep', max(1, $keep), PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * @return int Nombre de profils conservés (variable EDU_PROFILER_KEEP)
     */
    private static function keep(): int
    {
        if (ConfigCore::existEnv('EDU_PROFILER_KEEP') === true && ctype_digit((string) ConfigCore::getEnv('EDU_PROFILER_KEEP')) === true) {
            return max(1, (int) ConfigCore::getEnv('EDU_PROFILER_KEEP'));
        }

        return self::DEFAULT_KEEP;
    }
}

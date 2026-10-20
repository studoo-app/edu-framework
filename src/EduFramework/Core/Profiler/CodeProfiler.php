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
use Studoo\EduFramework\Core\Logs\HttpRequestLogger;

/**
 * Class CodeProfiler
 * Profiler du code PHP exécuté pendant une requête HTTP, affiché dans la barre
 * de debug (bloc Code) et dans le profiler (onglet Code).
 * Comme les requêtes SQL, le profil est rattaché au token de la requête HTTP :
 * - start() démarre le profilage en début de requête (LoadCouchCore), uniquement
 *   si la requête est journalisée (mode dev, hors routes internes du profiler)
 *   et si le profilage est demandé (variable EDU_PROFILER) ;
 * - stop() l'arrête dans le bloc finally et enregistre le profil (CodeProfileLogger).
 *
 * Variable d'environnement EDU_PROFILER :
 * - off : profiler désactivé
 * - trigger (défaut) : profilage à la demande, via le cookie edu_profile=1
 *   (bouton Code de la barre de debug) ou le paramètre ?_profile=1
 * - on : chaque requête est profilée
 *
 * Le profilage ne doit jamais faire échouer la requête : les erreurs sont
 * envoyées dans les logs PHP (error_log).
 *
 * @package Studoo\EduFramework\Core\Profiler
 */
class CodeProfiler
{
    /**
     * Nom du cookie posé par le bouton Code de la barre de debug
     */
    public const COOKIE = 'edu_profile';

    /**
     * Driver de profilage (Excimer si l'extension est chargée)
     * @var ProfilerDriverInterface|null
     */
    private static ?ProfilerDriverInterface $driver = null;

    /**
     * Token de la requête HTTP en cours de profilage, null si elle n'est pas profilée
     * @var int|null
     */
    private static ?int $requestId = null;

    /**
     * Instant de démarrage du profilage (microtime)
     * @var float
     */
    private static float $startedAt = 0.0;

    /**
     * Démarre le profilage de la requête HTTP en cours si les conditions sont réunies
     *
     * @return void
     */
    public static function start(): void
    {
        self::$requestId = null;

        $requestId = HttpRequestLogger::getCurrentLogId();
        if ($requestId === null || self::isRequested() === false || self::isAvailable() === false) {
            return;
        }

        try {
            self::$startedAt = microtime(true);
            self::driver()->start();
            self::$requestId = $requestId;
        } catch (\Throwable $exception) {
            error_log('[EduFramework Profiler] ' . $exception->getMessage());
        }
    }

    /**
     * Arrête le profilage de la requête HTTP en cours et enregistre le profil.
     * Ne fait rien si la requête n'est pas profilée.
     *
     * @return void
     */
    public static function stop(): void
    {
        $requestId = self::$requestId;
        self::$requestId = null;

        if ($requestId === null) {
            return;
        }

        try {
            $data = self::driver()->stop();
            $wallMs = round((microtime(true) - self::$startedAt) * 1000, 1);
            CodeProfileLogger::save(
                $requestId,
                self::driver()->name(),
                $wallMs,
                round(memory_get_peak_usage(true) / 1048576, 2),
                $data
            );
        } catch (\Throwable $exception) {
            error_log('[EduFramework Profiler] ' . $exception->getMessage());
        }
    }

    /**
     * @return string Mode du profiler : off, trigger ou on (variable EDU_PROFILER, trigger par défaut)
     */
    public static function mode(): string
    {
        $mode = (ConfigCore::existEnv('EDU_PROFILER') === true ? (string) ConfigCore::getEnv('EDU_PROFILER') : 'trigger');

        return (in_array($mode, ['off', 'trigger', 'on'], true) === true ? $mode : 'trigger');
    }

    /**
     * Indique si le profilage est demandé pour la requête en cours
     *
     * @return bool
     */
    public static function isRequested(): bool
    {
        return match (self::mode()) {
            'on' => true,
            'off' => false,
            default => ($_COOKIE[self::COOKIE] ?? '') === '1' || ($_GET['_profile'] ?? '') === '1',
        };
    }

    /**
     * @return bool Indique si l'extension de profilage est installée
     */
    public static function isAvailable(): bool
    {
        return self::driver()->isAvailable();
    }

    /**
     * @return bool Indique si la requête en cours est en train d'être profilée
     */
    public static function isActive(): bool
    {
        return self::$requestId !== null;
    }

    /**
     * Remplace le driver de profilage (tests, autre extension)
     *
     * @param ProfilerDriverInterface|null $driver Driver à utiliser, null pour la détection automatique
     * @return void
     */
    public static function setDriver(?ProfilerDriverInterface $driver): void
    {
        self::$driver = $driver;
    }

    /**
     * Réinitialise l'état du profiler (isolation des tests)
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$requestId = null;
        self::$startedAt = 0.0;
    }

    /**
     * @return ProfilerDriverInterface Driver de profilage, détecté selon les extensions chargées
     */
    private static function driver(): ProfilerDriverInterface
    {
        return self::$driver ??= (\extension_loaded('excimer') === true ? new ExcimerDriver() : new NullDriver());
    }
}

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

/**
 * Class ExcimerDriver
 * Driver du profiler de code basé sur l'extension Excimer (Wikimedia) :
 * un profiler par échantillonnage qui relève la pile d'appels PHP à
 * intervalle régulier. Les temps obtenus sont donc des estimations
 * (nombre d'échantillons x période) et les appels ne sont pas comptés.
 * Doc : https://www.mediawiki.org/wiki/Excimer
 *
 * @package Studoo\EduFramework\Core\Profiler
 */
class ExcimerDriver implements ProfilerDriverInterface
{
    /**
     * Période d'échantillonnage en secondes (0,1 ms) : environ 10 échantillons
     * par milliseconde, assez pour les pages courtes d'une application d'élève
     */
    public const PERIOD = 0.0001;

    /**
     * Profiler réutilisé d'une requête à l'autre (mode worker) : depuis
     * Excimer 1.2.4, créer un timer coûte plus cher que le relancer
     * @var \ExcimerProfiler|null
     */
    private ?\ExcimerProfiler $profiler = null;

    public function name(): string
    {
        return 'excimer';
    }

    public function isAvailable(): bool
    {
        return \extension_loaded('excimer');
    }

    public function start(): void
    {
        $this->profiler ??= new \ExcimerProfiler();
        $this->profiler->setPeriod(self::PERIOD);
        // Temps réel (et non CPU) : l'attente d'une base de données doit apparaître
        $this->profiler->setEventType(EXCIMER_REAL);
        $this->profiler->start();
    }

    public function stop(): array
    {
        if ($this->profiler === null) {
            return [];
        }

        $this->profiler->stop();
        // flush() retourne le log de la requête et en ouvre un neuf pour la suivante
        $log = $this->profiler->flush();

        return [
            'period_ms' => self::PERIOD * 1000,
            'samples' => $log->getEventCount(),
            'functions' => $log->aggregateByFunction(),
            'speedscope' => $log->getSpeedscopeData(),
        ];
    }
}

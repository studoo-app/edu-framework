<?php

namespace Core\Profiler;

use Studoo\EduFramework\Core\Profiler\ProfilerDriverInterface;

/**
 * Driver de profilage factice partagé par les tests (sans extension installée) :
 * disponible ou non, compte les démarrages et retourne un profil fixe
 */
class FakeProfilerDriver implements ProfilerDriverInterface
{
    /**
     * Nombre d'appels à start()
     * @var int
     */
    public int $starts = 0;

    /**
     * @param bool $available Driver disponible ou non
     * @param bool $failOnStop stop() lève une exception
     */
    public function __construct(private bool $available = true, private bool $failOnStop = false)
    {
    }

    /**
     * @return string Nom du driver factice
     */
    public function name(): string
    {
        return 'fake';
    }

    /**
     * @return bool Disponibilité du driver factice
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * Compte les démarrages
     *
     * @return void
     */
    public function start(): void
    {
        $this->starts++;
    }

    /**
     * @return array<string, mixed> Profil fixe (ou exception si failOnStop)
     */
    public function stop(): array
    {
        if ($this->failOnStop === true) {
            throw new \RuntimeException('panne du driver');
        }

        return [
            'period_ms' => 0.1,
            'samples' => 10,
            'functions' => ['f' => ['file' => '/tmp/f.php', 'line' => 1, 'self' => 10, 'inclusive' => 10]],
            'speedscope' => [],
        ];
    }
}

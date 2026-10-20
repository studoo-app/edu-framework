<?php

namespace Core\Profiler;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Profiler\CodeProfileAggregator;

/**
 * Tests de l'agrégation des profils de code : temps estimés, pourcentages,
 * origine des fonctions et graphe d'appels, à partir de données Excimer écrites à la main
 */
class CodeProfileAggregatorTest extends TestCase
{
    /**
     * Racine du projet utilisée pour calculer les chemins relatifs et les origines
     */
    protected function setUp(): void
    {
        new ConfigCore(['base_path' => '/projet/']);
    }

    /**
     * Données au format du driver Excimer : 100 échantillons à 0,1 ms
     * @return array<string, mixed>
     */
    private function data(): array
    {
        return [
            'period_ms' => 0.1,
            'samples' => 100,
            'functions' => [
                '/projet/public/index.php' => ['file' => '/projet/public/index.php', 'line' => 3, 'self' => 0, 'inclusive' => 100],
                'Controller\\HomeController::execute' => [
                    'file' => '/projet/app/Controller/HomeController.php', 'line' => 12,
                    'class' => 'Controller\\HomeController', 'function' => 'execute', 'self' => 60, 'inclusive' => 80,
                ],
                'Twig\\Environment::render' => [
                    'file' => '/projet/vendor/twig/twig/src/Environment.php', 'line' => 40, 'self' => 20, 'inclusive' => 20,
                ],
                'Studoo\\EduFramework\\Core\\LoadCouchCore::run' => [
                    'file' => '/projet/src/EduFramework/Core/LoadCouchCore.php', 'line' => 50, 'self' => 20, 'inclusive' => 100,
                ],
            ],
        ];
    }

    /**
     * Vérifie le calcul des temps estimés (échantillons x période) et des pourcentages,
     * et le tri par temps propre décroissant
     */
    public function testByFunctionEstimatesTimesAndSortsBySelfTime(): void
    {
        $functions = CodeProfileAggregator::byFunction($this->data());

        $this->assertCount(4, $functions);
        $this->assertSame('Controller\\HomeController::execute', $functions[0]['name']);
        $this->assertEquals(6.0, $functions[0]['self_ms']);
        $this->assertEquals(8.0, $functions[0]['inclusive_ms']);
        $this->assertEquals(60.0, $functions[0]['self_pct']);
        $this->assertSame('app/Controller/HomeController.php', $functions[0]['file']);
        $this->assertSame(12, $functions[0]['line']);
        // Le code hors fonction de la page est affiché par son chemin relatif
        $this->assertSame('public/index.php', $functions[3]['name']);
    }

    /**
     * Vérifie le classement des fonctions par origine (application, framework, dépendances, autre)
     */
    public function testOriginIsDeducedFromTheFile(): void
    {
        $this->assertSame('app', CodeProfileAggregator::origin('/projet/app/Controller/HomeController.php'));
        $this->assertSame('framework', CodeProfileAggregator::origin('/projet/src/EduFramework/Core/LoadCouchCore.php'));
        $this->assertSame('framework', CodeProfileAggregator::origin('/projet/public/index.php'));
        $this->assertSame('vendor', CodeProfileAggregator::origin('/projet/vendor/twig/twig/src/Environment.php'));
        $this->assertSame('framework', CodeProfileAggregator::origin('/autre/vendor/studoo/edu-framework/src/Core/ConfigCore.php'));
        $this->assertSame('other', CodeProfileAggregator::origin('/tmp/script.php'));
        $this->assertSame('other', CodeProfileAggregator::origin(null));
    }

    /**
     * Vérifie que le top ne garde que les fonctions avec du temps propre, dans la limite demandée
     */
    public function testTopKeepsFunctionsWithSelfTime(): void
    {
        $top = CodeProfileAggregator::top(CodeProfileAggregator::byFunction($this->data()), 2);

        $this->assertCount(2, $top);
        $this->assertSame('Controller\\HomeController::execute', $top[0]['name']);
        foreach (CodeProfileAggregator::top(CodeProfileAggregator::byFunction($this->data())) as $function) {
            $this->assertGreaterThan(0, $function['self']);
        }
    }

    /**
     * Vérifie la reconstruction du graphe d'appels depuis les piles Speedscope :
     * une fonction récursive ne compte qu'une fois par échantillon et un échantillon
     * peut représenter plusieurs périodes
     */
    public function testCallGraphCountsSamplesPerCallerCalleePair(): void
    {
        $speedscope = [
            'shared' => ['frames' => [['name' => 'main'], ['name' => 'a'], ['name' => 'b']]],
            'profiles' => [[
                'samples' => [[0, 1], [0, 1, 2], [0, 1, 1, 1]],
                // 100 000 ns = 1 période de 0,1 ms ; 200 000 ns = 2 périodes
                'weights' => [100000, 200000, 100000],
            ]],
        ];

        $edges = CodeProfileAggregator::callGraph($speedscope, 0.1);
        $byPair = [];
        foreach ($edges as $edge) {
            $byPair[$edge['caller'] . '>' . $edge['callee']] = $edge;
        }

        $this->assertSame(4, $byPair['main>a']['samples']);
        $this->assertEquals(0.4, $byPair['main>a']['ms']);
        $this->assertSame(2, $byPair['a>b']['samples']);
        // Récursion a > a > a : comptée une seule fois dans son échantillon
        $this->assertSame(1, $byPair['a>a']['samples']);
    }

    /**
     * Vérifie que des données vides ne provoquent pas d'erreur
     */
    public function testEmptyDataGivesEmptyResults(): void
    {
        $this->assertSame([], CodeProfileAggregator::byFunction([]));
        $this->assertSame([], CodeProfileAggregator::callGraph([], 0.1));
    }
}

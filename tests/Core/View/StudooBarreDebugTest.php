<?php

namespace Core\View;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Logs\HttpRequestLogger;
use Studoo\EduFramework\Core\View\studooBarreDebug;

class StudooBarreDebugTest extends TestCase
{
    protected function setUp(): void
    {
        new ConfigCore([]);
    }

    protected function tearDown(): void
    {
        // Réinitialisation de l'identifiant de requête statique entre les tests
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, null);
    }

    public function testToolbarDisplaysMethodRouteAndController(): void
    {
        ConfigCore::setRequest((new Request('/users/12', 'GET'))->setHander('App\\Controller\\UserController'));

        $html = (new studooBarreDebug())->generateBarDebug();

        $this->assertStringContainsString('id="edu-tb"', $html);
        $this->assertStringContainsString('/users/12', $html);
        $this->assertStringContainsString('App\\Controller\\UserController', $html);
        // Le panneau "Environnement" affiche la version de PHP (clé => valeur)
        $this->assertStringContainsString('<span>PHP</span><em>' . PHP_VERSION . '</em>', $html);
    }

    public function testToolbarEscapesTheRoute(): void
    {
        ConfigCore::setRequest(new Request('/<script>alert(1)</script>', 'GET'));

        $html = (new studooBarreDebug())->generateBarDebug();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function testToolbarWithoutControllerDoesNotFail(): void
    {
        ConfigCore::setRequest(new Request('/', 'GET'));

        $this->assertStringContainsString('aucun', (new studooBarreDebug())->generateBarDebug());
    }

    public function testDevToolBlockShowsTheRequestTokenWhenAvailable(): void
    {
        ConfigCore::setRequest(new Request('/', 'GET'));
        // Simulation d'une requête journalisée : identifiant réservé par HttpRequestLogger
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, 131);

        $html = (new studooBarreDebug())->generateBarDebug();

        // Le bloc fait le lien vers le descriptif de la requête dans le profiler
        $this->assertStringContainsString('/_debug/profiler#request/131', $html);
        $this->assertStringContainsString('<span>Token</span><em>#131</em>', $html);
        // Même icône que l'entrée "Requête" du profiler
        $this->assertStringContainsString('<span class="edu-tb-ico">&#9881;</span>', $html);
    }

    public function testDevToolBlockFallsBackToTheRequestListWithoutToken(): void
    {
        ConfigCore::setRequest(new Request('/', 'GET'));

        $html = (new studooBarreDebug())->generateBarDebug();

        $this->assertStringContainsString('/_debug/profiler#logs', $html);
        $this->assertStringNotContainsString('#request/', $html);
    }

    /**
     * Issue #76 : les styles globaux de la page hôte (ex: a { margin-top: 1.5rem; })
     * ne doivent pas casser l'affichage de la barre de debug, dont certains blocs sont des <a>.
     */
    public function testToolbarCssResetsHostPageAnchorStyles(): void
    {
        ConfigCore::setRequest(new Request('/', 'GET'));

        $css = (new studooBarreDebug())->generateCssGlobal();

        $this->assertStringContainsString('#edu-tb a {', $css);
        $this->assertStringContainsString('margin: 0;', $css);
        $this->assertStringContainsString('display: block;', $css);
        $this->assertStringContainsString('background: none;', $css);
    }
}

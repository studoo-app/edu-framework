<?php

namespace Core\View;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Logs\HttpRequestLogger;
use Studoo\EduFramework\Core\View\studooBarreDebug;

/**
 * Tests de la barre de debug : affichage de la méthode, de la route et du contrôleur,
 * échappement des valeurs, bloc DEV avec les services de développement
 */
class StudooBarreDebugTest extends TestCase
{
    /**
     * Sauvegarde des variables d'environnement modifiées par un test
     * (une valeur null signifie que la variable était absente de $_ENV)
     * @var array<string, string|null>
     */
    private array $envBackup = [];

    protected function setUp(): void
    {
        new ConfigCore([]);
    }

    /**
     * Réinitialise l'identifiant de requête statique et restaure $_ENV après chaque test
     */
    protected function tearDown(): void
    {
        // Réinitialisation de l'identifiant de requête statique entre les tests
        (new ReflectionProperty(HttpRequestLogger::class, 'currentLogId'))->setValue(null, null);
        // Restauration des variables d'environnement modifiées par les tests
        foreach ($this->envBackup as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $value;
            }
        }
        $this->envBackup = [];
    }

    /**
     * Modifie des variables d'environnement pour un test
     * (une valeur null retire la variable de $_ENV)
     * @param array<string, string|null> $values
     */
    private function setEnv(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->envBackup[$key] = $_ENV[$key] ?? null;
            if ($value === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $value;
            }
        }
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

    /**
     * Vérifie que le bloc DEV affiche le token de la requête journalisée
     * avec un lien vers le profiler
     */
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

    /**
     * Vérifie que le bloc DEV renvoie à la liste des requêtes du profiler
     * quand aucun token n'est disponible
     */
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

        $this->assertStringContainsString('#edu-tb a, #edu-tb button {', $css);
        $this->assertStringContainsString('margin: 0;', $css);
        $this->assertStringContainsString('display: block;', $css);
        $this->assertStringContainsString('background: none;', $css);
    }

    /**
     * Le panneau du bloc DEV liste les services de développement avec les valeurs
     * par défaut du compose.yaml quand les variables d'environnement sont absentes.
     */
    public function testDevBlockListsTheDevelopmentServicesWithDefaultPorts(): void
    {
        $this->setEnv([
            // Les services de la base (MariaDB, DbGate, phpMyAdmin) ne s'affichent
            // que si la base est active : le test fixe lui-même ses prérequis
            'DB_HOST_STATUS' => 'true',
            'PMA_PORT' => null, 'DBGATE_PORT' => null,
            'MAILER_UI_PORT' => null, 'MAILER_SMTP_PORT' => null, 'DB_HOST' => null, 'DB_SOCKET' => null,
        ]);
        ConfigCore::setRequest(new Request('/', 'GET'));

        $html = (new studooBarreDebug())->generateBarDebug();

        // Les interfaces web sont des liens cliquables ouverts dans un nouvel onglet
        $this->assertStringContainsString(
            '<span>Mailpit</span><em><a href="http://localhost:8025" target="_blank" rel="noopener">http://localhost:8025</a></em>',
            $html
        );
        $this->assertStringContainsString(
            '<span>DbGate</span><em><a href="http://localhost:8082" target="_blank" rel="noopener">http://localhost:8082</a></em>',
            $html
        );
        $this->assertStringContainsString(
            '<span>phpMyAdmin</span><em><a href="http://localhost:8081" target="_blank" rel="noopener">http://localhost:8081</a></em>',
            $html
        );
        // La base de données et le SMTP de Mailpit sont des services TCP : simple texte
        $this->assertStringContainsString('<span>DB_HOST</span><em>localhost:3306</em>', $html);
        $this->assertStringContainsString('<span>Mailpit SMTP</span><em>localhost:1025</em>', $html);
    }

    /**
     * Les URLs des services utilisent les ports personnalisés du .env
     * (mêmes variables que compose.yaml).
     */
    public function testDevBlockServicesUseThePortsFromTheEnvironment(): void
    {
        $this->setEnv([
            'DB_HOST_STATUS' => 'true',
            'PMA_PORT' => '9081', 'DBGATE_PORT' => '9082',
            'MAILER_UI_PORT' => '9025', 'MAILER_SMTP_PORT' => '9026', 'DB_HOST' => '127.0.0.1', 'DB_SOCKET' => '3307',
        ]);
        ConfigCore::setRequest(new Request('/', 'GET'));

        $html = (new studooBarreDebug())->generateBarDebug();

        $this->assertStringContainsString('<span>DB_HOST</span><em>127.0.0.1:3307</em>', $html);
        $this->assertStringContainsString('href="http://localhost:9025"', $html);
        $this->assertStringContainsString('<span>Mailpit SMTP</span><em>localhost:9026</em>', $html);
        $this->assertStringContainsString('href="http://localhost:9082"', $html);
        $this->assertStringContainsString('href="http://localhost:9081"', $html);
    }

    /**
     * Les URLs des services sont échappées comme le reste du panneau.
     */
    public function testDevBlockEscapesTheServiceUrls(): void
    {
        $this->setEnv(['DB_HOST_STATUS' => 'true', 'DBGATE_PORT' => '8082"&port=9090']);
        ConfigCore::setRequest(new Request('/', 'GET'));

        $html = (new studooBarreDebug())->generateBarDebug();

        $this->assertStringNotContainsString('"&port=9090"', $html);
        $this->assertStringContainsString('&quot;&amp;port=9090', $html);
    }
}

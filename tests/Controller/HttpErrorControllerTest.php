<?php

namespace Controller;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\Error\HttpError403Controller;
use Studoo\EduFramework\Core\Controller\Error\HttpError404Controller;
use Studoo\EduFramework\Core\Controller\Error\HttpError405Controller;
use Studoo\EduFramework\Core\Controller\Error\HttpErrorDefaultController;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\View\TwigCore;

class HttpErrorControllerTest extends TestCase
{
    public function setUp(): void
    {
        // Neutralise APP_ENV : le message de l'exception ne doit pas être affiché
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);

        (new ConfigCore([
            'twig_path' => __DIR__ . '/../../app/Template',
            'route_config_path' => __DIR__ . "/../Config/"
        ]));

        TwigCore::setEnvironment();
    }

    public function testPage404(): void
    {
        $html = (new HttpError404Controller())->execute(new Request('/inconnu', 'GET'));

        $this->assertStringContainsString('Erreur 404', $html);
        $this->assertStringContainsString('Page introuvable', $html);
        // Le nom du framework ne doit plus être affiché sur la page d'erreur
        $this->assertStringNotContainsString('EduFramework', $html);
    }

    public function testPage405(): void
    {
        $html = (new HttpError405Controller())->execute(new Request('/test', 'POST'));

        $this->assertStringContainsString('Erreur 405', $html);
        $this->assertStringContainsString('Méthode non autorisée', $html);
    }

    public function testPage403(): void
    {
        $html = (new HttpError403Controller())->execute(new Request('/secret', 'GET'));

        $this->assertStringContainsString('Erreur 403', $html);
        $this->assertStringContainsString('Accès interdit', $html);
    }

    public function testPage500(): void
    {
        $html = (new HttpErrorDefaultController())->execute(new Request('/test', 'GET'));

        $this->assertStringContainsString('Erreur 500', $html);
        $this->assertStringContainsString('Erreur interne du serveur', $html);
    }

    public function testPage500AfficheMessageEnModeDev(): void
    {
        // En mode développement, le message de l'exception est affiché sur la page
        $_ENV['APP_ENV'] = 'dev';

        $exception = new \RuntimeException('Division par zéro dans le controller');
        $html = (new HttpErrorDefaultController($exception))->execute(new Request('/test', 'GET'));

        $this->assertStringContainsString('Division par zéro', $html);

        unset($_ENV['APP_ENV']);
    }

    public function testPage500CacheMessageHorsModeDev(): void
    {
        // Hors mode développement, le message de l'exception ne doit pas être affiché
        $exception = new \RuntimeException('Information interne sensible');
        $html = (new HttpErrorDefaultController($exception))->execute(new Request('/test', 'GET'));

        $this->assertStringNotContainsString('Information interne sensible', $html);
    }
}

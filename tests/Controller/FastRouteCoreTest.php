<?php

namespace Controller;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\FastRouteCore;
use Studoo\EduFramework\Core\Exception\ErrorControllerException;
use Studoo\EduFramework\Core\Exception\ErrorRouteConfigNotExistException;
use Studoo\EduFramework\Core\View\TwigCore;

class FastRouteCoreTest extends TestCase
{

    public function setUp(): void
    {
        // Neutralise APP_ENV : un test précédent (AppCommandTest) charge le .env
        // racine (APP_ENV=dev) de maniere immutable, ce qui declencherait l'injection
        // de la barre de debug dans le rendu et casserait les hashs ci-dessous.
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);

        // Neutralise les fichiers téléversés pour isoler chaque test
        $_FILES = [];

        (new ConfigCore([
            'twig_path' => __DIR__ . '/../../app/Template',
            'route_config_path' => __DIR__ . "/../Config/"
        ]));

        TwigCore::setEnvironment();
        $en = TwigCore::getEnvironment();
    }

    public function testGetDispatcher()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('a2bd992a139c4ec5c9a3cac42e646dfc4a8e026c719d37924f193e7ff06fe520', hash('sha256', $route->getRoute()));
    }

    public function testGetDispatcherWithExceptionNotFound()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals('7f45399c1ecff199069b02b15d54610c6c3b0cfa3c381386e80936b705791c8e', hash('sha256', $route->getRoute()));
    }

    public function testGetDispatcherWithExceptionMethodNotAllowed()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('6b92774361c19fd3ece5ad9a3125684071853ee1a7a3459bfb549b6eb6bcc1c5', hash('sha256', $route->getRoute()));
    }

    public function testGetDispatcherWithExceptionMethodNotAllowedAndNotFound()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals('7f45399c1ecff199069b02b15d54610c6c3b0cfa3c381386e80936b705791c8e', hash('sha256', $route->getRoute()));
    }

    public function testLoadRoutesMatchSuccess()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('a2bd992a139c4ec5c9a3cac42e646dfc4a8e026c719d37924f193e7ff06fe520', hash('sha256', $route->getRoute()));
    }

    public function testLoadRouteConfigWithFileNotExist()
    {
        $route = new FastRouteCore();

        $this->expectException(ErrorRouteConfigNotExistException::class);
        $route->loadRouteConfig(__DIR__ . '/../ConfigNotExist/');
    }

    public function testLoadRoutesMatchSuccessWithExceptionNotFound()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $this->assertEquals('7f45399c1ecff199069b02b15d54610c6c3b0cfa3c381386e80936b705791c8e', hash('sha256', $route->getRoute()));
    }

    public function testLoadRoutesMatchSuccessWithExceptionMethodNotAllowedAndNotFound()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('6b92774361c19fd3ece5ad9a3125684071853ee1a7a3459bfb549b6eb6bcc1c5', hash('sha256', $route->getRoute()));
    }

    public function testLinkToGetnametopathInTwig()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/link';

        $this->assertEquals('703d619fc8bdbb20450b15dea56caf9b193f6f84d8a8b5187ec9fc36ba4c63a8', hash('sha256', $route->getRoute()));
    }

    public function testLinkToGetnametopathInTwigUserid()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/user/21';

        $this->assertEquals('ab2550a7609a2997e9aaef4b49a12388d8978d5507d970cb45fa96fec99e768b', hash('sha256', $route->getRoute()));
    }

    public function testRouteWithMethodExplicitIndex()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/medecin';

        $this->assertEquals('ed13c9c6c5ed3833bd1af6bea040089eff6e48baefcc219a9a04f382f2532931', hash('sha256', $route->getRoute()));
    }

    public function testRouteWithMethodExplicitNew()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/medecin/new';

        $this->assertEquals('e2f7b9a3c530c751e475fca16a4e46e95e249ceca802a947adc17e50bfb96a91', hash('sha256', $route->getRoute()));
    }

    public function testRouteWithMethodNotExist()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/medecin/error', "Controller\MedecinController::inconnue");

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/medecin/error';

        $this->expectException(ErrorControllerException::class);
        $this->expectExceptionMessage("La méthode <inconnue> n'existe pas dans le controller <Controller\MedecinController>");
        $route->getRoute();
    }

    public function testRouteWithMethodNotPublic()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/medecin/error', "Controller\MedecinController::testPrivate");

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/medecin/error';

        $this->expectException(ErrorControllerException::class);
        $route->getRoute();
    }

    public function testRouteImportWithFileInvalid()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/medecin/import';
        $_FILES = [
            'fichier' => [
                'name' => 'medecins.csv',
                'type' => 'text/csv',
                'size' => 123,
                'tmp_name' => '/tmp/phpXYZ',
                'error' => UPLOAD_ERR_INI_SIZE,
            ],
        ];

        // Le fichier téléversé est en erreur : la vue affiche la branche "fichier invalide"
        $this->assertEquals('c3a41ed55dbc9d7f5798e52b5140f32adb704b37859824845ad92b82c54d9794', hash('sha256', $route->getRoute()));
    }

    public function testRouteImportWithoutFile()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/medecin/import';
        $_FILES = [];

        // Aucun fichier envoyé : la vue affiche la branche "aucun fichier"
        $this->assertEquals('b619f84de911fb72a6459e15946fbe934a00a63fcf3a7d6d23c9bd0506c3137a', hash('sha256', $route->getRoute()));
    }
}

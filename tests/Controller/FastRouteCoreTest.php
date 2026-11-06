<?php

namespace Controller;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\FastRouteCore;
use Studoo\EduFramework\Core\Exception\ErrorConfigException;
use Studoo\EduFramework\Core\Exception\ErrorControllerException;
use Studoo\EduFramework\Core\Exception\ErrorHttpStatusException;
use Studoo\EduFramework\Core\Exception\ErrorRouteConfigNotExistException;
use Studoo\EduFramework\Core\Security\CsrfService;
use Studoo\EduFramework\Core\View\TwigCore;
use Studoo\EduRoute\Dispatcher;

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

        // Neutralise les données POST et les jetons CSRF en session pour isoler chaque test
        $_POST = [];
        $_SESSION = [];

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
        $this->assertEquals('65ff3d775df3e8c72a519c7d5e69abb760b605c1d7730387514a48068e6e9ca7', hash('sha256', $route->getRoute()));
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
        $this->assertEquals('65ff3d775df3e8c72a519c7d5e69abb760b605c1d7730387514a48068e6e9ca7', hash('sha256', $route->getRoute()));
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

        $this->assertEquals('d96088a5c7270de3890c35d1b62fcfdf4ac4a483238f3a9dbca9d75d623e7949', hash('sha256', $route->getRoute()));
    }

    public function testLinkToGetnametopathInTwigUserid()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/user/21';

        $this->assertEquals('4a57d9375b9f736fe712287543216ab10ab70bad30a9888b859a55ca462f3ace', hash('sha256', $route->getRoute()));
    }

    public function testRouteWithMethodExplicitIndex()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/medecin';

        $this->assertEquals('18c9106de6b757e423cca403a7429f221a10c35c84a75d02c55e0ed5a8e7bdb9', hash('sha256', $route->getRoute()));
    }

    public function testRouteWithMethodExplicitNew()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/medecin/new';

        $this->assertEquals('e0b2c8e4b1df5dae3d9d504f15fbcbe6a5e265b0e980b437379854a596bbea9a', hash('sha256', $route->getRoute()));
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
        // Le jeton CSRF du formulaire est fourni pour passer la protection
        $_POST = [
            CsrfService::TOKEN_FIELD_NAME => CsrfService::getToken('medecin_import'),
        ];
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
        // Le jeton CSRF renouvelé (aléatoire) est présent dans la page :
        // le rendu n'est donc plus comparable par un hash de page complète
        // (l'apostrophe du message est échappée par Twig en &#039;)
        $html = $route->getRoute();
        $this->assertStringContainsString(
            "Le fichier n&#039;est pas valide (taille trop grande ou téléversement interrompu)",
            $html
        );
        // Le formulaire contient le champ caché du jeton CSRF
        $this->assertStringContainsString('name="_csrf_token"', $html);
    }

    public function testRouteImportWithoutFile()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/medecin/import';
        // Le jeton CSRF du formulaire est fourni pour passer la protection
        $_POST = [
            CsrfService::TOKEN_FIELD_NAME => CsrfService::getToken('medecin_import'),
        ];
        $_FILES = [];

        // Aucun fichier envoyé : la vue affiche la branche "aucun fichier"
        // (l'apostrophe du message est échappée par Twig en &#039;)
        $html = $route->getRoute();
        $this->assertStringContainsString("Aucun fichier n&#039;a été envoyé", $html);
        // Le formulaire contient le champ caché du jeton CSRF
        $this->assertStringContainsString('name="_csrf_token"', $html);
    }

    public function testRouteImportWithCsrfTokenInvalid()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/medecin/import';
        // Aucun jeton CSRF fourni : la soumission est refusée (403)
        $_POST = [];
        $_FILES = [];

        $this->expectException(ErrorHttpStatusException::class);
        $this->expectExceptionMessage("Jeton CSRF invalide ou expiré");
        $route->getRoute();
    }

    /**
     * getDispatcher() renvoie un dispatcher de studoo/edu-route
     */
    public function testGetDispatcherReturnsEduRouteDispatcher()
    {
        $route = new FastRouteCore();
        $route->addRoute('GET', '/', "Controller\HomeController");

        $this->assertInstanceOf(Dispatcher::class, $route->getDispatcher());
    }

    /**
     * La casse de la méthode est conservée : une route [get] ne répond pas à une requête GET (erreur 405)
     */
    public function testHttpMethodKeepsItsCase()
    {
        // Comme avec nikic/fast-route, une méthode écrite en minuscules (get) ne répond pas à une requête GET
        $route = new FastRouteCore();
        $route->addRoute(['get'], '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('6b92774361c19fd3ece5ad9a3125684071853ee1a7a3459bfb549b6eb6bcc1c5', hash('sha256', $route->getRoute()));
    }

    /**
     * Une valeur vide (tiret orphelin ou ~) dans la liste httpMethod ne bloque pas la route GET
     */
    public function testEmptyHttpMethodValueKeepsTheRouteWorking()
    {
        // Un tiret orphelin ou ~ dans la liste httpMethod de routes.yaml donne null : la route GET répond toujours
        $route = new FastRouteCore();
        $route->addRoute(['GET', null], '/', "Controller\HomeController");

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('65ff3d775df3e8c72a519c7d5e69abb760b605c1d7730387514a48068e6e9ca7', hash('sha256', $route->getRoute()));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertEquals('6b92774361c19fd3ece5ad9a3125684071853ee1a7a3459bfb549b6eb6bcc1c5', hash('sha256', $route->getRoute()));
    }

    /**
     * Une méthode HTTP qui est une liste lève une erreur explicite citant l'URI de la route
     */
    public function testInvalidHttpMethodThrowsExplicitError()
    {
        $route = new FastRouteCore();

        $this->expectException(ErrorConfigException::class);
        $this->expectExceptionMessage("La méthode HTTP de la route </> est invalide");
        $route->addRoute(['GET', ['POST']], '/', "Controller\HomeController");
    }

    /**
     * Une route de routes.yaml sans controller lève une erreur qui nomme la route
     */
    public function testLoadRouteConfigWithMissingKey()
    {
        $route = new FastRouteCore();

        $this->expectException(ErrorConfigException::class);
        $this->expectExceptionMessage("La route <sansController> du fichier routes.yaml est invalide : la clé 'controller' est absente");
        $route->loadRouteConfig(__DIR__ . '/../Config/InvalidRoutes/');
    }

    /**
     * Une liste imbriquée dans httpMethod de routes.yaml lève une erreur qui nomme la route
     */
    public function testLoadRouteConfigWithInvalidHttpMethodNamesTheRoute()
    {
        $route = new FastRouteCore();

        $this->expectException(ErrorConfigException::class);
        $this->expectExceptionMessage("La route <index> du fichier routes.yaml est invalide : la clé 'httpMethod'");
        $route->loadRouteConfig(__DIR__ . '/../Config/InvalidMethodRoutes/');
    }

    /**
     * uri: 404 (nombre) et httpMethod: GET (sans crochets) sont acceptés, comme avec nikic/fast-route
     */
    public function testLoadRouteConfigAcceptsScalarValues()
    {
        // uri: 404 (nombre) et httpMethod: GET (sans crochets) sont acceptés, comme avec nikic/fast-route
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/ScalarRoutes/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEquals('65ff3d775df3e8c72a519c7d5e69abb760b605c1d7730387514a48068e6e9ca7', hash('sha256', $route->getRoute()));
    }

    /**
     * Les paramètres obligatoire et optionnel de /user/{id:\d+}[/{name}] sont transmis à la requête
     */
    public function testRouteWithOptionalParameter()
    {
        $route = new FastRouteCore();
        $route->loadRouteConfig(__DIR__ . '/../Config/');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/user/21/benoit';
        $html = $route->getRoute();

        $this->assertSame('21', \Studoo\EduFramework\Core\ConfigCore::getRequest()->get('id'));
        $this->assertSame('benoit', \Studoo\EduFramework\Core\ConfigCore::getRequest()->get('name'));
        $this->assertIsString($html);
    }
}

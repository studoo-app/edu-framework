<?php

namespace Tests\App\Controller;

use Controller\HomeController;
use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\View\TwigCore;

/**
 * Exemple de test d'un controller de l'application : la page d'accueil
 */
class HomeControllerTest extends TestCase
{
    /**
     * Configure Twig sur les templates de l'application et neutralise APP_ENV avant chaque test
     */
    protected function setUp(): void
    {
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);

        new ConfigCore([
            'twig_path' => __DIR__ . '/../../../app/Template',
            'route_config_path' => __DIR__ . '/../../../app/Config/'
        ]);

        TwigCore::setEnvironment();
    }

    /**
     * La page d'accueil est une page HTML complète avec le titre du controller
     */
    public function testLaPageDAccueilAfficheLeTitre(): void
    {
        $html = (new HomeController())->execute(new Request('/', 'GET'));

        // Le bloc "title" de la page remplit la balise <title> de base.html.twig
        $this->assertStringContainsString('<title>HomeController</title>', $html);
        $this->assertStringContainsString('<h1>HomeController</h1>', $html);
        $this->assertStringContainsString('</html>', $html);
    }
}

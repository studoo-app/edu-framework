<?php

namespace Tests\App\Controller;

use Controller\UserController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\View\TwigCore;

/**
 * Exemple de test d'un controller de l'application (repris dans docs/build/tests-unitaires.md) :
 * la requête HTTP est simulée avec un objet Request, puis le HTML rendu est vérifié
 */
class UserControllerTest extends TestCase
{
    /**
     * Configure Twig sur les templates de l'application et neutralise APP_ENV avant chaque test
     */
    protected function setUp(): void
    {
        // Sans APP_ENV=dev, la barre de debug n'est pas injectée dans le rendu
        unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);

        new ConfigCore([
            'twig_path' => __DIR__ . '/../../../app/Template',
            'route_config_path' => __DIR__ . '/../../../app/Config/'
        ]);

        TwigCore::setEnvironment();
    }

    /**
     * La page /user/{id} affiche le titre et l'identifiant passé dans la route
     */
    public function testLaPageAfficheLIdentifiantDeLUtilisateur(): void
    {
        // Arrange : on simule la requête GET /user/42, la variable de route {id} vaut 42
        $request = new Request('/user/42', 'GET');
        $request->setVars(['id' => '42']);

        // Act
        $html = (new UserController())->execute($request);

        // Assert
        $this->assertStringContainsString('<h1>UserController</h1>', $html);
        $this->assertStringContainsString('<b>42</b>', $html);
    }

    /**
     * L'identifiant affiché est échappé par Twig (protection contre les failles XSS)
     */
    #[DataProvider('fournirIdentifiants')]
    public function testLIdentifiantEstEchappe(string $id, string $attendu): void
    {
        $request = (new Request('/user/' . $id, 'GET'))->setVars(['id' => $id]);

        $html = (new UserController())->execute($request);

        $this->assertStringContainsString('<b>' . $attendu . '</b>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    /**
     * Jeux de données : identifiant reçu dans la route => texte attendu dans le HTML
     * @return array<string, array{string, string}>
     */
    public static function fournirIdentifiants(): array
    {
        return [
            'identifiant numérique' => ['7', '7'],
            'balise script'         => ['<script>', '&lt;script&gt;'],
        ];
    }
}

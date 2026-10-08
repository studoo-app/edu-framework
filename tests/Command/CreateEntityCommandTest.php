<?php

namespace Command;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Commands\CreateEntityCommand;
use Studoo\EduFramework\Commands\Exception\EntityAlreadyExistsException;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class CreateEntityCommandTest extends TestCase
{
    private $commandeTester;

    protected function setUp(): void
    {
        (new ConfigCore([]));
        $application = new Application(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));
        $application->add(new CreateEntityCommand());
        $command = $application->find("make:entity");
        $this->commandeTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->commandeTester = null;
    }

    public static function tearDownAfterClass(): void
    {
        // Nettoyage des fichiers générés par les tests
        (new Filesystem())->remove("app/Entity");
        (new Filesystem())->remove("app/Repository");
    }

    public function testCreateEntityInteractive(): void
    {
        // Saisie interactive : nom, type, nom, type, vide pour terminer
        $this->commandeTester->setInputs(['nom', 'string', 'code_postal', 'string', '']);
        $this->commandeTester->execute(["entity-name" => "ville"]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('[OK] Entity successfully generated', $output);
        $this->assertFileExists("app/Entity/Ville.php");
        $this->assertFileExists("app/Repository/VilleRepository.php");
    }

    public function testEntityContent(): void
    {
        $content = (string) file_get_contents("app/Entity/Ville.php");

        $this->assertStringContainsString('class Ville', $content);
        $this->assertStringContainsString('private int $id;', $content);
        $this->assertStringContainsString('private string $nom;', $content);
        // Le constructeur hydrate l'entité
        $this->assertStringContainsString('__construct(int $id, string $nom, string $code_postal)', $content);
        // Getters et setters fluide en camelCase
        $this->assertStringContainsString('public function getId(): int', $content);
        $this->assertStringContainsString('public function getNom(): string', $content);
        $this->assertStringContainsString('public function getCodePostal(): string', $content);
        $this->assertStringContainsString('public function setCodePostal(string $code_postal): self', $content);
        // Pas de setter pour l'identifiant (attribué par la base de données)
        $this->assertStringNotContainsString('setId', $content);
    }

    public function testRepositoryContent(): void
    {
        $content = (string) file_get_contents("app/Repository/VilleRepository.php");

        $this->assertStringContainsString('class VilleRepository', $content);
        $this->assertStringContainsString('use Entity\Ville;', $content);
        $this->assertStringContainsString('use Studoo\EduFramework\Core\Service\DatabaseService;', $content);
        $this->assertStringContainsString('public function getVilles(): array', $content);
        $this->assertStringContainsString("SELECT * FROM ville", $content);
        // Hydratation avec cast pour éviter les erreurs de type (Exemple: SQLite)
        $this->assertStringContainsString("new Ville((int) \$row['id'], \$row['nom'], \$row['code_postal'])", $content);
    }

    public function testCreateEntityFieldsOption(): void
    {
        // Mode direct : --fields "nom:string,tarif:float"
        $this->commandeTester->execute([
            "entity-name" => "medecin",
            "--fields" => "nom:string,tarif:float"
        ]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('[OK] Entity successfully generated', $output);
        $this->assertFileExists("app/Entity/Medecin.php");
        $this->assertFileExists("app/Repository/MedecinRepository.php");

        $repository = (string) file_get_contents("app/Repository/MedecinRepository.php");
        $this->assertStringContainsString('public function getMedecins(): array', $repository);
        $this->assertStringContainsString("SELECT * FROM medecin", $repository);
        $this->assertStringContainsString("(float) \$row['tarif']", $repository);
    }

    public function testEntityAlreadyExists(): void
    {
        $this->expectException(EntityAlreadyExistsException::class);
        $this->commandeTester->execute(["entity-name" => "ville", "--fields" => "nom:string"]);
    }

    public function testFieldsInvalidType(): void
    {
        $this->commandeTester->execute(["entity-name" => "produit", "--fields" => "nom:varchar"]);

        $this->assertEquals(1, $this->commandeTester->getStatusCode());
        $this->assertStringContainsString('invalide', $this->commandeTester->getDisplay());
        // Aucun fichier n'est généré en cas d'erreur
        $this->assertFileDoesNotExist("app/Entity/Produit.php");
    }

    public function testFieldsInvalidFormat(): void
    {
        // Format invalide : le type est manquant
        $this->commandeTester->execute(["entity-name" => "produit", "--fields" => "nom"]);

        $this->assertEquals(1, $this->commandeTester->getStatusCode());
        $this->assertStringContainsString('Format des champs invalide', $this->commandeTester->getDisplay());
    }

    public function testNoFieldsDefined(): void
    {
        // Mode interactif : aucune saisie de champ
        $this->commandeTester->setInputs(['']);
        $this->commandeTester->execute(["entity-name" => "produit"]);

        $this->assertEquals(1, $this->commandeTester->getStatusCode());
        $this->assertStringContainsString('Aucun champ défini', $this->commandeTester->getDisplay());
    }
}

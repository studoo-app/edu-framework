<?php

namespace Controller;

use Studoo\EduFramework\Core\Controller\ControllerInterface;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\Service\DatabaseService;
use Studoo\EduFramework\Core\View\TwigCore;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class DataBaseTestController implements ControllerInterface
{
    /**
     * @param Request $request Requête HTTP
     * @return string|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws \Exception
     */
    public function execute(Request $request): string|null
    {
        // Connexion à la base de données
        $getConnectDb = DatabaseService::getConnect();
        // Requête SQL
        $etudiantStmt = $getConnectDb->query("SELECT * FROM `etudiant`");
        // Récupération des données
        $etudiants = $etudiantStmt->fetchAll();

        return TwigCore::getEnvironment()->render(
            'home/db.html.twig',
            [
             'titre'     => 'Exemple avec connextion à la base de données',
             'etudiants' => $etudiants,
            ]
        );
    }
}

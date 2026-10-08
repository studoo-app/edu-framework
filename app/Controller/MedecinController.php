<?php

namespace Controller;

use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Controller\Request;
use Studoo\EduFramework\Core\View\TwigCore;

class MedecinController
{
	public function index(Request $request): string|null
	{
		return TwigCore::getEnvironment()->render('medecin/index.html.twig',
		    [
		        "titre"   => 'MedecinController - index',
		        "request" => $request
		    ]
		);
	}

	public function new(Request $request): string|null
	{
		return TwigCore::getEnvironment()->render('medecin/new.html.twig',
		    [
		        "titre"   => 'MedecinController - new',
		        "request" => $request
		    ]
		);
	}

	/**
	 * Démo de gestion d'un téléversement de fichier (upload)
	 * GET  : affiche le formulaire d'import
	 * POST : contrôle le fichier téléversé puis le déplace dans le dossier public/upload/
	 */
	public function import(Request $request): string|null
	{
		$message = null;
		$erreur  = null;

		if ($request->getHttpMethod() === "POST") {
			if ($request->hasFile('fichier') === false) {
				$erreur = "Aucun fichier n'a été envoyé";
			} elseif ($request->isValid('fichier') === false) {
				$erreur = "Le fichier n'est pas valide (taille trop grande ou téléversement interrompu)";
			} elseif (in_array($request->getExtension('fichier'), ['csv', 'txt'], true) === false) {
				// Sécurité : liste blanche des extensions autorisées
				$erreur = "Extension non autorisée (seuls les fichiers csv et txt sont acceptés)";
			} else {
				// Le dossier d'upload est créé s'il n'existe pas
				$dossierUpload = ConfigCore::getConfig('base_path') . 'public/upload';
				if (is_dir($dossierUpload) === false) {
					mkdir($dossierUpload, 0777, true);
				}

				// Sécurité : le nom du fichier est maîtrisé côté serveur
				$destination = $dossierUpload . '/medecin-import-' . uniqid() . '.'
				    . $request->getExtension('fichier');

				if ($request->move('fichier', $destination) === true) {
					$message = "Le fichier a bien été téléversé : " . basename($destination);
				} else {
					$erreur = "Problème lors de l'enregistrement du fichier";
				}
			}
		}

		return TwigCore::getEnvironment()->render('medecin/import.html.twig',
		    [
		        "titre"   => 'MedecinController - import',
		        "request" => $request,
		        "message" => $message,
		        "erreur"  => $erreur
		    ]
		);
	}

	/**
	 * Méthode privée pour les tests de validation du framework (méthode non publique)
	 * @return string|null
	 */
	private function testPrivate(Request $request): string|null
	{
		return null;
	}
}

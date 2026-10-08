<?php

namespace Controller;

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
	 * Méthode privée pour les tests de validation du framework (méthode non publique)
	 * @return string|null
	 */
	private function testPrivate(Request $request): string|null
	{
		return null;
	}
}

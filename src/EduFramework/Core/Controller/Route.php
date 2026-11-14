<?php

/*
 * Edu Framework by studoo labs
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Controller;

use Studoo\EduRoute\RouteParser\Std;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Exception\BadRouteException;
use Studoo\EduFramework\Core\Exception\ErrorRouteConfigNotExistException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Route
{
    /**
     * @var array<mixed> $listRoutes Tableau contenant les informations des routes
     */
    private array $listRoutes = [];

    /**
     * Récupère le nom de la route et les paramètres pour renvoyer le chemin de la route
     * @param string $name Nom de la route à récupérer dans le fichier config/routes.yaml
     * @param array<mixed> $param Tableau associatif des paramètres de la route
     * @return string URL
     * @throws ParseException
     * @throws BadRouteException
     */
    public function getNameToPath(string $name, array $param = []): string
    {
        if (count($this->listRoutes) === 0) {
            $this->getRouteInfo();
        }
        if (array_key_exists($name, $this->listRoutes)) {
            $url = "";
            $positionParam = 0;
            $countNbParam = count($param);
            if ($countNbParam > 0) {
                $positionParam = ($countNbParam - 1);
            }
            foreach ($this->listRoutes[$name]["uri_parse"][$positionParam] as $uri) {
                if (is_string($uri)) {
                    $url .= $uri;
                } else {
                    if (!array_key_exists($uri[0], $param)) {
                        throw new BadRouteException("Le paramètre n'existe pas");
                    }
                    $url .= $param[$uri[0]];
                }
            }
            return $url;
        }
        throw new BadRouteException("La route n'existe pas");
    }


    /**
     * Renseigne et renvoi un tableau contenant les informations des routes nommées de l'application :
     * fichier config/routes.yaml et attributs #[Route] des controllers (mêmes routes que le moteur de routage)
     * Exemple: ['home' => ['uri' => '/home', 'controller' => 'Controller\HomeController', 'httpMethod' => ['GET'],
     *          'uri_parse' => [...]]]
     * @return array<mixed> Tableau contenant les informations des routes
     * @throws ErrorRouteConfigNotExistException
     */
    public function getRouteInfo(): array
    {
        $this->listRoutes = [];
        foreach ((new FastRouteCore())->loadRoutes()->declaredRoutes() as $declaredRoute) {
            // Seules les routes nommées servent à la génération d'URL
            if ($declaredRoute->name === null) {
                continue;
            }
            // Une route [GET, POST] est déclarée une fois par méthode HTTP : une seule entrée par nom
            $this->listRoutes[$declaredRoute->name]['uri'] = $declaredRoute->pattern;
            $this->listRoutes[$declaredRoute->name]['controller'] = $declaredRoute->describeHandler();
            $this->listRoutes[$declaredRoute->name]['httpMethod'][] = $declaredRoute->method;
        }

        $routeParse = new Std();
        foreach ($this->listRoutes as $nameRoute => $route) {
            $this->listRoutes[$nameRoute]["uri_parse"] = $routeParse->parse($route["uri"]);
        }
        return $this->listRoutes;
    }

    /**
     * Récupération du contenu brut du fichier config/routes.yaml (sans les routes déclarées par attributs)
     * @return array<mixed>|bool Tableau des routes ou false si le fichier ne contient pas de routes
     * @throws ErrorRouteConfigNotExistException
     */
    public function loadRoute(): array|bool
    {
        $pathFile = ConfigCore::getConfig("route_config_path") . 'routes.yaml';

        // On vérifie que le fichier de configuration des routes existe
        // pour renvoyer une erreur explicite plutôt qu'une exception du composant Yaml
        if (is_file($pathFile) === false) {
            throw new ErrorRouteConfigNotExistException(
                "Le fichier de configuration des routes <" . $pathFile . "> n'existe pas. "
                . "Vérifiez la configuration 'route_config_path' et la casse du dossier "
                . "(Exemple: app/Config et non app/config, les systèmes de fichiers Linux sont sensibles à la casse)"
            );
        }

        $fileData = Yaml::parseFile($pathFile);
        if (is_array($fileData)) {
            return $fileData;
        }
        return false;
    }
}

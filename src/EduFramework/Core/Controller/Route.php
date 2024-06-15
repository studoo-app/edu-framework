<?php
/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Controller;

use FastRoute\RouteParser\Std;
use Studoo\EduFramework\Core\ConfigCore;
use Studoo\EduFramework\Core\Exception\BadRouteException;
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
     * Renseigne et renvoi un tableau contenant les informations des route du fichier config/routes.yaml
     * @return array<mixed> Tableau contenant les informations des routes
     */
    public function getRouteInfo(): array
    {
        $this->listRoutes = $this->loadRoute();
        $routeParse = new Std();
        foreach ($this->listRoutes as $nameRoute => $route) {
            $this->listRoutes[$nameRoute]["uri_parse"] = $routeParse->parse($route["uri"]);
        }
        return $this->listRoutes;
    }

    /**
     * Récupération du fichier config/routes.yaml
     * @return array<mixed>|bool Tableau des routes ou false si le fichier n'existe pas
     */
    public function loadRoute(): array|bool
    {
        $fileData = Yaml::parseFile(ConfigCore::getConfig("route_config_path") . 'routes.yaml');
        if (is_array($fileData)) {
            return $fileData;
        }
        return false;
    }
}

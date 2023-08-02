<?php
/*
 * Ce fichier fait partie du Studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\Controller;

use Studoo\EduFramework\Core\Exception\ErrorHttpStatusException;

/**
 * Class Request
 * Elle permet de définir une requête HTTP
 * On peut récupérer la méthode HTTP (GET, POST ...), la route, le controller et les variables de la requête HTTP
 */
class Request
{
    /**
     * La méthode HTTP de la requête
     * @var string $httpMethod
     */
    private string $httpMethod;

    /**
     * La route de la requête
     * @var string $route
     */
    private string $route;

    /**
     * Nom de la classe du controller
     * @var string $hander
     */
    private string $hander;

    /**
     * Le controller via interface ControllerInterface
     * @var ControllerInterface $controller
     */
    private ControllerInterface $controller;

    /**
     * Les variables de la requête HTTP
     * @var array<mixed> $vars
     */
    private array $vars = [];

    /**
     * Une requete HTTP a obligatoirement une route et une méthode HTTP (GET, POST ...)
     * Cet objet est présent dans la méthode execute() d'un controller
     * @param string $route
     * @param string $httpMethod
     */
    public function __construct(string $route, string $httpMethod)
    {
        $this->httpMethod = $httpMethod;
        $this->route = $route;
    }

    /**
     * Permet de récupérer une variable de la requête HTTP
     * @param string $key
     * @return string|null
     */
    public function get(string $key): string|null
    {
        return $this->vars[$key] ?? null;
    }

    /**
     * Renvoi le nom du controller qui est associé à la requête HTTP
     * @return string
     */
    public function getHander(): string
    {
        return $this->hander;
    }

    /**
     * Permet de définir le nom et instancier le controller qui est associé à la requête HTTP
     * @param string $hander
     * @return Request
     * @throws ErrorHttpStatusException
     */
    public function setHander(string $hander): Request
    {
        $this->hander = $hander;
        // On vérifie que le controller existe
        if (!class_exists($hander)) {
            throw new ErrorHttpStatusException("Le controller $hander n'existe pas");
        }
        // On instancie le controller
        $this->controller = new $hander();

        return $this;
    }

    /**
     * Renvoi le controller qui est associé à la requête HTTP
     * @return ControllerInterface
     */
    public function getController(): ControllerInterface
    {
        return $this->controller;
    }

    /**
     * Renoi les variables de la requête HTTP
     * @return array<mixed>
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * Permet de définir les variables de la requête HTTP
     * @param array<mixed> $vars
     * @return Request
     */
    public function setVars(array $vars): Request
    {
        $this->vars = $vars;
        return $this;
    }

    /**
     * Renvoi la méthode HTTP de la requête
     * @return string
     */
    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    /**
     * Permet de définir la méthode HTTP de la requête
     * @param string $httpMethod
     * @return Request
     */
    public function setHttpMethod(string $httpMethod): Request
    {
        $this->httpMethod = $httpMethod;
        return $this;
    }

    /**
     * Renvoi la route de la requête
     * @return string
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * Permet de définir la route de la requête
     * @param string $route
     * @return Request
     */
    public function setRoute(string $route): Request
    {
        $this->route = $route;
        return $this;
    }
}

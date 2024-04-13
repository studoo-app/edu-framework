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
     * Les variables de la requête HTTP
     * @var array<mixed> $vars
     */
    private array $vars = [];

    /**
     * Une requete HTTP a obligatoirement une route et une méthode HTTP (GET, POST ...)
     * Cet objet est présent dans la méthode execute() d'un controller
     * @param string $route La route de la requête
     * @param string $httpMethod La méthode HTTP de la requête
     */
    public function __construct(string $route, string $httpMethod)
    {
        $this->httpMethod = $httpMethod;
        $this->route = $route;
    }

    /**
     * Permet de récupérer les headers de la requête HTTP
     * @return bool|array<mixed>
     */
    public function getHearder(): bool|array
    {
        if (function_exists('getallheaders') === false) {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) === 'HTTP_') {
                    $headers[str_replace(
                        ' ',
                        '-',
                        ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
                    )] = $value;
                }
            }
            return $headers;
        }
        return getallheaders();
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
     * @param string $hander Le nom de la classe du controller
     * @return Request
     */
    public function setHander(string $hander): Request
    {
        $this->hander = $hander;
        return $this;
    }

    /**
     * Permet de récupérer une variable de la requête HTTP
     * @param string $key Le nom de la variable
     * @return string|null
     */
    public function get(string $key): string|null
    {
        return $this->vars[$key] ?? null;
    }

    /**
     * Renvoi les variables de la requête HTTP
     * @return array<mixed>
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * Permet de définir les variables de la requête HTTP
     * @param array<mixed> $vars Les variables de la requête HTTP
     * @return Request
     */
    public function setVars(array $vars): Request
    {
        $this->vars = array_merge($this->vars, $vars);
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
     * @param string $httpMethod La méthode HTTP de la requête
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
     * @param string $route La route de la requête
     * @return Request
     */
    public function setRoute(string $route): Request
    {
        $this->route = $route;
        return $this;
    }
}

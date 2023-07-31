<?php

namespace Studoo\EduFramework\Core\Controller;

class Request
{
    private string $httpMethod;
    private string $route;
    private string $hander;
    private array $vars = [];

    public function __construct(string $route, string $httpMethod)
    {
        $this->httpMethod = $httpMethod;
        $this->route = $route;
    }

    public function get(string $key): string|null
    {
        return $this->vars[$key] ?? null;
    }

    /**
     * @return string
     */
    public function getHander(): string
    {
        return $this->hander;
    }

    /**
     * @param string $hander
     * @return Request
     */
    public function setHander(string $hander): Request
    {
        $this->hander = $hander;
        return $this;
    }

    /**
     * @return array
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * @param array $vars
     * @return Request
     */
    public function setVars(array $vars): Request
    {
        $this->vars = $vars;
        return $this;
    }

}
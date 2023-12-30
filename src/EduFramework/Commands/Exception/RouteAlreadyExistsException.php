<?php

namespace Studoo\EduFramework\Commands\Exception;

class RouteAlreadyExistsException extends \Exception
{

    /**
     * Message de l'exception
     * @var string
     */
    protected $message = "Route already exists.";
}

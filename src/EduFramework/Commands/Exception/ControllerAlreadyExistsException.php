<?php

namespace Studoo\EduFramework\Commands\Exception;

class ControllerAlreadyExistsException extends \Exception
{

    /**
     * Message de l'exception
     * @var string
     */
    protected $message = "Controller already exists.";
}

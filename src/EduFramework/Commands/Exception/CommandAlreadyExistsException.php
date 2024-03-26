<?php

namespace Studoo\EduFramework\Commands\Exception;

class CommandAlreadyExistsException extends \Exception
{
    /**
     * Message de l'exception
     * @var string
     */
    protected $message = "Command already exists.";
}

<?php

namespace Studoo\EduFramework\Commands\Exception;

class ViewAlreadyExistsException extends \Exception
{

    /**
     * Message de l'exception
     * @var string
     */
    protected $message = "Twig View already exists.";
}

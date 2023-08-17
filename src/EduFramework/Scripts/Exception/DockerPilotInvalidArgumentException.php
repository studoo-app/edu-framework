<?php

namespace Studoo\EduFramework\Scripts\Exception;

class DockerPilotInvalidArgumentException extends \Exception
{
    /**
     * Message de l'exception
     * @var string
     */
    protected $message = "Invalid or missing argument <db-engine>, available engines are : 'mysql' or 'maria-db'";
}

<?php

namespace Studoo\EduFramework\Scripts\Exception;

class DockerPilotInvalidArgumentException extends \Exception
{
    protected $message = "Invalid or missing argument <db-engine>, available engines are : 'mysql' or 'maria-db'";
}

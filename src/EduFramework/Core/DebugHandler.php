<?php

namespace Studoo\EduFramework\Core;

class DebugHandler
{
    public static function dump($input)
    {
        echo "<pre>";
        var_dump($input);
        echo "</pre>";
    }
}
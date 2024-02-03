<?php

namespace Studoo\EduFramework\Commands;

use Symfony\Component\Console\Application;

class AppCommand extends Application
{
    public function __construct()
    {
        parent::__construct("EduFramework", "0.1.0@alpha");

        $this->add(new \Studoo\EduFramework\Commands\DefaultCommand());

        $this->setDefaultCommand('default');
    }
}

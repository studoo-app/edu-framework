<?php

namespace Studoo\EduFramework\Commands\Extends;

use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Application;

class AppCommand extends Application
{
    public function __construct()
    {
        (new ConfigCore([]));
        parent::__construct("EduFramework", ConfigCore::getConfig('version'));

        $this->add(new \Studoo\EduFramework\Commands\DefaultCommand());
        $this->add(new \Studoo\EduFramework\Commands\CreateControllerCommand());

        CommandBanner::setVersion($this->getVersion());

        $this->setDefaultCommand('default');
    }
}

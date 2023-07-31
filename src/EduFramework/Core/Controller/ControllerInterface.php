<?php

namespace Studoo\EduFramework\Core\Controller;

use Twig\Environment;

interface ControllerInterface
{
    public function execute(Request $request);
}
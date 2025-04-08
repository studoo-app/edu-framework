<?php

namespace Command;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Commands\DefaultCommand;
use Studoo\EduFramework\Commands\Extends\AppCommand;
use Studoo\EduFramework\Commands\StartCommand;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class StartCommandTest extends TestCase
{
    protected function setUp(): void
    {
        (new ConfigCore([]));
        $application = new Application(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));
        $command = new StartCommand();
        $application->add($command);
        $this->commandeTester = new CommandTester($application->get("start"));
    }

    protected function tearDown(): void
    {
        $this->commandeTester = null;
    }
    public function testCommandWithCustomPort(): void
    {
        $this->commandeTester->execute(['--port' => 9000]);
        $output = $this->commandeTester->getDisplay();
        $this->assertStringContainsString('php -S localhost:9000 -t public', $output);
    }
}

<?php

namespace Command;

use PHPUnit\Framework\TestCase;
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
        $application->add(new StartCommand());
        $command = $application->find("start");
        $this->commandeTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->commandeTester = null;
    }
    public function testCommandWithCustomPort(): void
    {
        $this->commandeTester->execute(['--port' => 9000]);
        $this->commandeTester->getInput()->setInteractive(false);
        $output = $this->commandeTester->getDisplay();
        $this->assertStringContainsString('php -S localhost:9000 -t public', $output);
    }
}

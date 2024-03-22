<?php

namespace Command;

use PHPUnit\Framework\TestCase;
use Studoo\EduFramework\Commands\CheckStackCommand;
use Studoo\EduFramework\Core\ConfigCore;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CheckStackCommandTest extends TestCase
{
    private $commandeTester;

    protected function setUp(): void
    {
        (new ConfigCore([]));
        $application = new Application(ConfigCore::getConfig('name'), ConfigCore::getConfig('version'));
        $application->add(new CheckStackCommand());
        $command = $application->find("check:config");
        $this->commandeTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->commandeTester = null;
    }

    public function testCommandPrerequis(): void
    {
        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('Check des prérequis', $output);
    }

    public function testCommandPdo(): void
    {
        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('PDO_MYSQL', $output);
    }

    public function testCommandOpenSsl(): void
    {
        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('OPENSSL', $output);
    }

    public function testCommandJson(): void
    {
        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('JSON', $output);
    }

    public function testCommandMbString(): void
    {
        $this->commandeTester->execute([]);
        $output = $this->commandeTester->getDisplay();

        $this->assertStringContainsString('MBSTRING', $output);
    }
}

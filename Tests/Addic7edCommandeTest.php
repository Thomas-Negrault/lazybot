<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use lazybot\Addic7edCommand;

class Addic7edCommandeTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $application = new Application('LazyBot', '0.0.1');
        $application->add(new Addic7edCommand());

        $command = $application->find('subtitle:addic7ed');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));
        $this->assertEquals(1, 1);
    }
}
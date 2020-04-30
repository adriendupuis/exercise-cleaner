<?php

use ExerciseCleaner\DefaultSingleCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Tester\ApplicationTester;

require __DIR__.'/../vendor/autoload.php';

class ApplicationTest extends TestCase
{
    /** @var InputDefinition */
    private $inputDefinition;

    /** @var ApplicationTester */
    private $applicationTester;

    protected function setUp(): void
    {
        $application = new Application();

        $defaultSingleCommand = new DefaultSingleCommand();
        $application->add($defaultSingleCommand);
        $application->setDefaultCommand($defaultSingleCommand->getName(), true);

        $application->setAutoExit(false);

        $this->applicationTester = new ApplicationTester($application);
        $this->inputDefinition = $defaultSingleCommand->getDefinition();
    }

    public function testStepIsNotNumeric()
    {
        $this->applicationTester->run($this->getInputArguments('examples'));
        $this->assertStringContainsString('Step argument is missing or isn\'t numeric', $this->applicationTester->getDisplay());
    }

    public function testExamplesStep1()
    {
        $this->applicationTester->run($this->getInputArguments('1 examples/'));
        $this->assertEmpty($this->applicationTester->getDisplay());
    }

    private function getInputArguments(string $inputString): array
    {
        $stringInput = new StringInput($inputString);
        $stringInput->bind($this->inputDefinition);

        return $stringInput->getArguments();
    }
}
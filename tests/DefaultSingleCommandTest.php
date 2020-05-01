<?php

require __DIR__.'/../vendor/autoload.php';

use ExerciseCleaner\DefaultSingleCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Tester\CommandTester;

class DefaultSingleCommandTest extends TestCase
{
    /** @var DefaultSingleCommand */
    private $defaultSingleCommand;

    /** @var CommandTester */
    private $defaultSingleCommandTester;

    public function setUp(): void
    {
        $this->defaultSingleCommand = new DefaultSingleCommand();
        $this->defaultSingleCommandTester = new CommandTester($this->defaultSingleCommand);
    }

    public function testStepIsNotNumeric(): void
    {
        $this->defaultSingleCommandTester->execute($this->getInput('examples/'));
        $this->assertStringContainsString('Step argument is missing or isn\'t numeric', $this->defaultSingleCommandTester->getDisplay());
        $this->assertGreaterThan(0, $this->defaultSingleCommandTester->getStatusCode());
    }

    public function testExamplesStep1(): void
    {
        $this->defaultSingleCommandTester->execute($this->getInput('--keep-orig 1 examples/'));
        $this->assertStringNotContainsString('Step argument is missing or isn\'t numeric', $this->defaultSingleCommandTester->getDisplay());
        $this->assertEquals(0, $this->defaultSingleCommandTester->getStatusCode());

        $this->defaultSingleCommandTester->execute($this->getInput('--keep-orig 1.0 examples/'));
        $this->assertStringNotContainsString('Step argument is missing or isn\'t numeric', $this->defaultSingleCommandTester->getDisplay());
        $this->assertEquals(0, $this->defaultSingleCommandTester->getStatusCode());

        shell_exec('rm -f examples/*.step*.*');
    }

    private function getInput(string $inputString): array
    {
        $stringInput = new StringInput($inputString);
        $stringInput->bind($this->defaultSingleCommand->getDefinition());

        $options = [];
        foreach ($stringInput->getOptions() as $option => $value) {
            $options["--$option"] = $value;
        }

        return array_merge($stringInput->getArguments(), $options);
    }
}

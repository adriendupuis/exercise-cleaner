<?php

require __DIR__.'/../vendor/autoload.php';

use ExerciseCleaner\DefaultSingleCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
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

        // Avoid a bug where "optional options" with mandatory value become "mandatory options".
        $definition = new InputDefinition();
        $definition->setArguments($this->defaultSingleCommand->getDefinition()->getArguments());
        /** @var InputOption $option */
        foreach ($options = $this->defaultSingleCommand->getDefinition()->getOptions() as $option) {
            if ('config' !== $option->getName() && 'input-ext' !== $option->getName()) {
                $definition->addOption($option);
            }
        }
        $this->defaultSingleCommand->setDefinition($definition);

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
        $this->defaultSingleCommandTester->execute($this->getInput('--output-ext 1 examples/'));
        $this->assertStringNotContainsString('Step argument is missing or isn\'t numeric', $this->defaultSingleCommandTester->getDisplay());
        $this->assertEquals(0, $this->defaultSingleCommandTester->getStatusCode());

        $this->defaultSingleCommandTester->execute($this->getInput('--output-ext 1.0 examples/'));
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

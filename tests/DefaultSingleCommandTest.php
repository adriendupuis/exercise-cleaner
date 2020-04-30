<?php

require __DIR__.'/../vendor/autoload.php';

use ExerciseCleaner\DefaultSingleCommand;
use PHPUnit\Framework\TestCase;
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
        $this->defaultSingleCommandTester->execute([
            'step' => 'src/',
        ]);
        $this->assertStringContainsString('Step argument is missing or isn\'t numeric', $this->defaultSingleCommandTester->getDisplay());
        $this->assertGreaterThan(0, $this->defaultSingleCommandTester->getStatusCode());
    }
}

<?php

require __DIR__.'/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use ExerciseCleaner\ExerciseCleaner;

class ExerciseCleanerTest extends TestCase
{
    /** @var ExerciseCleaner */
    private $exerciseCleaner;

    public function setUp(): void {
        $this->exerciseCleaner = new ExerciseCleaner();
    }

    public function testSimplestTag() {
        $code = <<<'CODE'
TRAINING EXERCISE START STEP 1
test
TRAINING EXERCISE STOP STEP 1
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines);
        $this->assertCount(0, $cleanedCodeLines);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2);
        $this->assertCount(1, $cleanedCodeLines);
        $this->assertEquals('test', $cleanedCodeLines[0]);
    }

    public function testNestedSimpleTags() {
        $code = <<<'CODE'
line#0
line#1 TRAINING EXERCISE START STEP 1
line#2
line#3 TRAINING EXERCISE START STEP 2
line#4
line#5 TRAINING EXERCISE STOP STEP 2
line#6
line#7 TRAINING EXERCISE STOP STEP 1
line#8
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1);
        $this->assertCount(2, $cleanedCodeLines);
        $this->assertEquals('line#0', $cleanedCodeLines[0]);
        $this->assertEquals('line#8', $cleanedCodeLines[1]);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2);
        $this->assertCount(4, $cleanedCodeLines);
        $this->assertEquals('line#0', $cleanedCodeLines[0]);
        $this->assertEquals('line#2', $cleanedCodeLines[1]);
        $this->assertEquals('line#6', $cleanedCodeLines[2]);
        $this->assertEquals('line#8', $cleanedCodeLines[3]);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 3);
        $this->assertCount(5, $cleanedCodeLines);
        $this->assertEquals('line#0', $cleanedCodeLines[0]);
        $this->assertEquals('line#2', $cleanedCodeLines[1]);
        $this->assertEquals('line#4', $cleanedCodeLines[2]);
        $this->assertEquals('line#6', $cleanedCodeLines[3]);
        $this->assertEquals('line#8', $cleanedCodeLines[4]);
    }

}
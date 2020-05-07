<?php

require __DIR__ . '/../vendor/autoload.php';

use ExerciseCleaner\ExerciseCleaner;
use PHPUnit\Framework\TestCase;

class ExerciseCleanerTest extends TestCase
{
    /** @var ExerciseCleaner */
    private $exerciseCleaner;

    protected function setUp(): void
    {
        $this->exerciseCleaner = new ExerciseCleaner();
        set_error_handler([$this, 'errorHandler']);
        $this->resetErrors();
    }

    public function testSimplestTag()
    {
        $code = <<<'CODE'
TRAINING EXERCISE START STEP 2
test
TRAINING EXERCISE STOP STEP 2
CODE;
        $codeLines = explode(PHP_EOL, $code);

        // Step 1's exercise
        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines);
        $this->assertCount(0, $cleanedCodeLines);

        // Step 1's solution
        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1, true);
        $this->assertCount(0, $cleanedCodeLines);

        // Step 2's exercise
        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2);
        $this->assertCount(0, $cleanedCodeLines);

        // Step 2's solution
        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true);
        $this->assertCount(1, $cleanedCodeLines);
        $this->assertEquals('test', $cleanedCodeLines[0]);

        // Step 3's exercise
        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 3);
        $this->assertCount(1, $cleanedCodeLines);
        $this->assertEquals('test', $cleanedCodeLines[0]);

        // Step 3's solution
        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 3, true);
        $this->assertCount(1, $cleanedCodeLines);
        $this->assertEquals('test', $cleanedCodeLines[0]);
    }

    public function testSimplestTagWithFloats()
    {
        $code = <<<'CODE'
TRAINING EXERCISE START STEP 1.0
test
TRAINING EXERCISE STOP STEP 1.0
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines);
        $this->assertCount(0, $cleanedCodeLines);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2);
        $this->assertCount(1, $cleanedCodeLines);
        $this->assertEquals('test', $cleanedCodeLines[0]);
    }

    public function testNestedSimpleTags(): void
    {
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

    public function testNestedSimpleTagsWithFloats(): void
    {
        $code = <<<'CODE'
line#0
line#1 TRAINING EXERCISE START STEP 1.1
line#2
line#3 TRAINING EXERCISE START STEP 1.2
line#4
line#5 TRAINING EXERCISE STOP STEP 1.2
line#6
line#7 TRAINING EXERCISE STOP STEP 1.1
line#8
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1.1);
        $this->assertCount(2, $cleanedCodeLines);
        $this->assertEquals('line#0', $cleanedCodeLines[0]);
        $this->assertEquals('line#8', $cleanedCodeLines[1]);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1.2);
        $this->assertCount(4, $cleanedCodeLines);
        $this->assertEquals('line#0', $cleanedCodeLines[0]);
        $this->assertEquals('line#2', $cleanedCodeLines[1]);
        $this->assertEquals('line#6', $cleanedCodeLines[2]);
        $this->assertEquals('line#8', $cleanedCodeLines[3]);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1.3);
        $this->assertCount(5, $cleanedCodeLines);
        $this->assertEquals('line#0', $cleanedCodeLines[0]);
        $this->assertEquals('line#2', $cleanedCodeLines[1]);
        $this->assertEquals('line#4', $cleanedCodeLines[2]);
        $this->assertEquals('line#6', $cleanedCodeLines[3]);
        $this->assertEquals('line#8', $cleanedCodeLines[4]);
    }

    public function testKeptNestedSimpleTags(): void
    {
        //Same as testNestedSimpleTags but with $keepTags=true
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

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1, false, true);
        $this->assertCount(4, $cleanedCodeLines);
        $this->assertEquals('line#0', $cleanedCodeLines[0]);
        $this->assertEquals($codeLines[0], $cleanedCodeLines[0]);
        $this->assertStringStartsWith('line#1', $cleanedCodeLines[1]);
        $this->assertEquals($codeLines[1], $cleanedCodeLines[1]);
        $this->assertStringStartsWith('line#7', $cleanedCodeLines[2]);
        $this->assertEquals($codeLines[7], $cleanedCodeLines[2]);
        $this->assertEquals('line#8', $cleanedCodeLines[3]);
        $this->assertEquals($codeLines[8], $cleanedCodeLines[3]);

        $slicedCodeLines = array_values($codeLines);
        array_splice($slicedCodeLines, 4, 1);
        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false, true);
        $this->assertCount(8, $cleanedCodeLines);
        $this->assertEquals($slicedCodeLines, $cleanedCodeLines);

        $this->assertEquals($codeLines, $this->exerciseCleaner->cleanCodeLines($codeLines, 3, false, true));
    }

    public function testCommentActionTag(): void
    {
        $code = <<<'CODE'
TRAINING EXERCISE START STEP 1 COMMENT
  Step 1
TRAINING EXERCISE STOP STEP 1
TRAINING EXERCISE START STEP 2
  Step 2+
TRAINING EXERCISE STOP STEP 2
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $this->assertCount(0, $this->exerciseCleaner->cleanCodeLines($codeLines, 1));
        $this->assertEquals(['  Step 1'], $this->exerciseCleaner->cleanCodeLines($codeLines, 1, true));

        // Slashes Style
        $this->assertEquals(['  // Step 1'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false, false, '.php'));
        $this->assertEquals(['  // Step 1', '  Step 2+'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true, false, '.php'));

        // Sharp Style
        $this->assertEquals(['  # Step 1'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false, false, '.sh'));
        $this->assertEquals(['  # Step 1', '  Step 2+'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true, false, '.yaml'));

        // Twig Style
        $this->assertEquals(['  {# Step 1 #}'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false, false, '.twig'));
        $this->assertEquals(['  {# Step 1 #}', '  Step 2+'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true, false, '.twig'));

        // Unsupported
        $this->assertEquals([], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false, false, '.json'));
        $this->assertEquals(['  Step 2+'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true, false, '.json'));
    }

    public function testThresholdActionTag(): void
    {
        $code = <<<'CODE'
# TRAINING EXERCISE START STEP 1 COMMENT
Method 1
# TRAINING EXERCISE STOP STEP 1
# TRAINING EXERCISE START STEP 2 COMMENT
Method 2
# TRAINING EXERCISE STOP STEP 2
# TRAINING EXERCISE START STEP 1 KEEP UNTIL 2 THEN COMMENT
Common to methods 1 & 2
# TRAINING EXERCISE STOP STEP 1
# TRAINING EXERCISE START STEP 3
Method 3
# TRAINING EXERCISE STOP STEP 3
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1);
        $this->assertCount(0, $cleanedCodeLines);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1, true);
        $this->assertCount(2, $cleanedCodeLines);
        $this->assertEquals([
            'Method 1', // Step 1's solution
            'Common to methods 1 & 2', // Steps 1 & 2's solution
        ], $cleanedCodeLines);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2);
        $this->assertCount(2, $cleanedCodeLines);
        $this->assertEquals([
            '# Method 1', // Step 1's method is commented for step 2
            'Common to methods 1 & 2', // Steps 1 & 2 common part is kept
            // Trainee got to implement method 2
        ], $cleanedCodeLines);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true);
        $this->assertCount(3, $cleanedCodeLines);
        $this->assertEquals([
            '# Method 1', // Step 1's method is commented for step 2
            'Method 2', // Step 2's solution
            'Common to methods 1 & 2', // Steps 1 & 2's solution
        ], $cleanedCodeLines);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 3);
        $this->assertCount(3, $cleanedCodeLines);
        $this->assertEquals([
            '# Method 1', // Step 1's method is commented for step 3
            '# Method 2', // Step 2's method is commented for step 3
            '# Common to methods 1 & 2', // Steps 1 & 2's common part is commented for step 3
        ], $cleanedCodeLines);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 3, true);
        $this->assertCount(4, $cleanedCodeLines);
        $this->assertEquals([
            '# Method 1', // Step 1's method is commented for step 3
            '# Method 2', // Step 2's method is commented for step 3
            '# Common to methods 1 & 2', // Steps 1 & 2's common part is commented for step 3
            'Method 3', // Step 3's solution
        ], $cleanedCodeLines);
    }

    public function testPlaceholderTags(): void
    {
        $code = <<<'CODE'
line#0
line#1 TRAINING EXERCISE START STEP 1
line#2 // TRAINING EXERCISE STEP PLACEHOLDER First instruction
line#3
line#4 TRAINING EXERCISE START STEP 2
line#5 // Second instruction TRAINING EXERCISE STEP PLACEHOLDER
line#6
line#7 TRAINING EXERCISE STOP STEP 2
line#8
line#9 TRAINING EXERCISE STOP STEP 1
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1);
        $this->assertCount(2, $cleanedCodeLines);
        $this->assertEquals([
            'line#0',
            'line#2 // First instruction',
        ], $cleanedCodeLines);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1, true);
        $this->assertCount(3, $cleanedCodeLines);
        $this->assertEquals([
            'line#0',
            'line#3',
            'line#8',
        ], $cleanedCodeLines);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2);
        $this->assertCount(4, $cleanedCodeLines);
        $this->assertEquals([
            'line#0',
            'line#3',
            'line#5 // Second instruction',
            'line#8',
        ], $cleanedCodeLines);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true);
        $this->assertCount(4, $cleanedCodeLines);
        $this->assertEquals([
            'line#0',
            'line#3',
            'line#6',
            'line#8',
        ], $cleanedCodeLines);
    }

    public function testParseError(): void
    {
        $code = <<<'CODE'
0 # TRAINING EXERCISE START STEP 1
1 # TRAINING EXERCISE START STEP 2
2 Whatever
3 # TRAINING EXERCISE STOP STEP 1
4 # TRAINING EXERCISE STOP STEP 1
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $this->exerciseCleaner->cleanCodeLines($codeLines);

        $this->assertStringContainsString('Parse Error', $this->getLastErrorString());
        $this->assertStringContainsString('at line 3', $this->getLastErrorString());
        $this->assertEquals(E_USER_ERROR, $this->getLastErrorNumber());
    }

    public function testThresholdWarning(): void
    {
        $code = <<<'CODE'
0 # TRAINING EXERCISE START STEP 1 KEEP UNTIL 1 THEN COMMENT
1 Whatever
2 # TRAINING EXERCISE STOP STEP 1
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $this->exerciseCleaner->cleanCodeLines($codeLines);

        $this->assertStringContainsString('Threshold less or equals to step', $this->getLastErrorString());
        $this->assertStringContainsString('at line 0', $this->getLastErrorString());
        $this->assertEquals(E_USER_WARNING, $this->getLastErrorNumber());
    }

    public function testUnsupportedCommentWarning(): void
    {
        $code = <<<'CODE'
0 # TRAINING EXERCISE START STEP 1 COMMENT
1 Whatever
2 # TRAINING EXERCISE STOP STEP 1
3 # TRAINING EXERCISE START STEP 1 KEEP UNTIL 2 THEN COMMENT
4 Whatever
5 # TRAINING EXERCISE STOP STEP 1
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false, false, '.json');

        $this->assertStringContainsString('Unsupported COMMENT action', $this->errors[0]['string']);
        $this->assertStringContainsString('at line 0', $this->errors[0]['string']);
        $this->assertEquals(E_USER_WARNING, $this->errors[0]['number']);

        $this->assertStringContainsString('Unsupported COMMENT action', $this->errors[1]['string']);
        $this->assertStringContainsString('at line 3', $this->errors[1]['string']);
        $this->assertEquals(E_USER_WARNING, $this->errors[1]['number']);
    }

    /** @var array[] */
    private $errors = [];

    public function errorHandler($number, $string): void
    {
        $this->errors[] = compact('number', 'string');
    }

    private function getLastErrorString(): string
    {
        if (count($this->errors)) {
            return $this->errors[count($this->errors) - 1]['string'];
        }
        return null;
    }

    private function getLastErrorNumber(): int
    {
        if (count($this->errors)) {
            return $this->errors[count($this->errors) - 1]['number'];
        }
        return null;
    }

    private function resetErrors(): void
    {
        $this->errors = [];
    }
}

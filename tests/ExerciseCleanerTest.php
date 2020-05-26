<?php

require __DIR__.'/../vendor/autoload.php';

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

    public function testTagConstantVersusRegex(): void
    {
        $this->assertStringContainsString($this->exerciseCleaner->tagConstant, $this->exerciseCleaner->tagRegex);
    }

    /*********************/
    /* Tag Parsing Tests */

    public function testParseTag(): void
    {
        foreach ([
                     '// TRAINING EXERCISE START STEP 1.0' => [
                         'step' => 1,
                         'name' => null,
                         'boundary' => 'START',
                         'start' => true,
                         'state' => 'SOLUTION',
                         'action' => 'KEEP',
                     ],
                     'TRAINING EXERCISE STOP STEP 1.0' => [
                         'step' => 1.0,
                         'boundary' => 'STOP',
                         'start' => false,
                     ],
                     'TRAINING EXERCISE START STEP 1.0 COMMENT' => [
                         'step' => 1.0,
                         'name' => null,
                         'boundary' => 'START',
                         'start' => true,
                         'state' => 'SOLUTION',
                         'action' => 'COMMENT',
                     ],
                     '# TRAINING EXERCISE START STEP 1.0 PLACEHOLDER' => [
                         'step' => 1.0,
                         'name' => null,
                         'boundary' => 'START',
                         'start' => true,
                         'state' => 'PLACEHOLDER',
                         'action' => 'REMOVE',
                     ],
                     '{* TRAINING EXERCISE START STEP 1.0 PLACEHOLDER KEEP *}' => [
                         'step' => 1.0,
                         'name' => null,
                         'boundary' => 'START',
                         'start' => true,
                         'state' => 'PLACEHOLDER',
                         'action' => 'KEEP',
                     ],
                     'TRAINING EXERCISE START STEP 1.0 UNTIL 3.0' => [
                         'step' => 1,
                         'name' => null,
                         'boundary' => 'START',
                         'start' => true,
                         'state' => 'SOLUTION',
                         'action' => 'KEEP UNTIL 3 THEN REMOVE',
                         'before' => 'KEEP',
                         'threshold' => 3,
                         'after' => 'REMOVE',
                     ],
                     'TRAINING EXERCISE START STEP 1.0 COMMENT UNTIL 3.0' => [
                         'step' => 1,
                         'name' => null,
                         'boundary' => 'START',
                         'start' => true,
                         'state' => 'SOLUTION',
                         'action' => 'COMMENT UNTIL 3 THEN REMOVE',
                         'before' => 'COMMENT',
                         'threshold' => 3,
                         'after' => 'REMOVE',
                     ],
                     '/* TRAINING EXERCISE START STEP 1.1 WORKSHEET KEEP UNTIL 3.1 THEN COMMENT */' => [
                         'step' => 1.1,
                         'name' => null,
                         'boundary' => 'START',
                         'start' => true,
                         'state' => 'WORKSHEET',
                         'action' => 'KEEP UNTIL 3.1 THEN COMMENT',
                         'before' => 'KEEP',
                         'threshold' => 3.1,
                         'after' => 'COMMENT',
                     ],
                 ] as $line => $parsedTag) {
            $parsedTag['tag'] = $this->cleanTag($line);
            $parsedTag['line_number'] = null;

            $this->assertEquals($parsedTag, $this->exerciseCleaner->parseTag($line));
        }
    }

    public function testParseTagBackwardCompatibility(): void
    {
        $parsedIntroTag = [
            'tag' => 'TRAINING EXERCISE START STEP 1 WORKSHEET',
            'boundary' => 'START',
            'start' => true,
            'step' => 1,
            'name' => null,
            'state' => 'WORKSHEET',
            'action' => 'KEEP',
        ];
        foreach ([
                     'TRAINING EXERCISE START STEP 1 INTRO' => [
                         'msg' => 'INTRO keyword is deprecated',
                         'type' => E_USER_DEPRECATED,
                         'parsed' => $parsedIntroTag,
                     ],
                     'TRAINING EXERCISE START STEP INTRO 1' => [
                         'msg' => 'INTRO keyword is deprecated',
                         'type' => E_USER_DEPRECATED,
                         'parsed' => $parsedIntroTag,
                     ],
                     'TRAINING EXERCISE START INTRO STEP 1' => [
                         'msg' => 'INTRO keyword is deprecated',
                         'type' => E_USER_DEPRECATED,
                         'parsed' => $parsedIntroTag,
                     ],
                     'TRAINING EXERCISE INTRO START STEP 1' => [
                         'msg' => 'INTRO keyword is deprecated',
                         'type' => E_USER_DEPRECATED,
                         'parsed' => $parsedIntroTag,
                     ],
                 ] as $line => $feedback) {
            $parsedTag = $this->exerciseCleaner->parseTag($line);

            if (!array_key_exists('tag', $feedback['parsed'])) {
                $feedback['parsed']['tag'] = $this->cleanTag($line);
            }
            $feedback['parsed']['line_number'] = null;

            $this->assertEquals($feedback['parsed'], $parsedTag);
            $this->assertEquals($feedback['type'], $this->getLastErrorType());
            $this->assertStringContainsString($feedback['msg'], $this->getLastErrorMessage());
        }
    }

    public function testParseTagError(): void
    {
        foreach ([
                     'TRAINING EXERCISE',
                     'TRAINING EXERCISE START',
                     'TRAINING EXERCISE START STEP',
                     'TRAINING EXERCISE START STEP Test',
                 ] as $line) {
            //$this->expectException(\ParseError::class); // Can't be used several times
            try {
                $this->exerciseCleaner->parseTag($line);
                $this->fail("No ParseError thrown while parsing “{$line}”");
            } catch (\ParseError $parseError) {
                $this->assertStringContainsStringIgnoringCase('tag parse error', $parseError->getMessage());
            }
        }
    }

    public function testNotATagError(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Not a tag');
        $this->exerciseCleaner->parseTag('TRAINING EXERCICE');
    }

    public function testNotAnEnclosingTagError(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Not an enclosing tag');
        $this->exerciseCleaner->parseTag($this->exerciseCleaner->placeholderTagConstant);
    }

    /** Do not parse, simply remove non alpha-numeric characters */
    private function cleanTag(string $tag): string
    {
        $tag = preg_replace('/^[^A-Z0-9.]*/', '', $tag);
        $tag = preg_replace('/[^A-Z0-9.]*$/', '', $tag);

        return trim($tag);
    }

    /***********************/
    /* Code Cleaning Tests */

    public function testSimplestTag(): void
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

    public function testSimplestTagWithFloats(): void
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
line#1
line#2 TRAINING EXERCISE START STEP 1
line#3
line#4 TRAINING EXERCISE START STEP 2
line#5
line#6 TRAINING EXERCISE STOP STEP 2
line#7
line#8 TRAINING EXERCISE STOP STEP 1
line#9
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1);
        $this->assertCount(2, $cleanedCodeLines);
        $this->assertEquals('line#1', $cleanedCodeLines[0]);
        $this->assertEquals('line#9', $cleanedCodeLines[1]);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2);
        $this->assertCount(4, $cleanedCodeLines);
        $this->assertEquals('line#1', $cleanedCodeLines[0]);
        $this->assertEquals('line#3', $cleanedCodeLines[1]);
        $this->assertEquals('line#7', $cleanedCodeLines[2]);
        $this->assertEquals('line#9', $cleanedCodeLines[3]);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 3);
        $this->assertCount(5, $cleanedCodeLines);
        $this->assertEquals('line#1', $cleanedCodeLines[0]);
        $this->assertEquals('line#3', $cleanedCodeLines[1]);
        $this->assertEquals('line#5', $cleanedCodeLines[2]);
        $this->assertEquals('line#7', $cleanedCodeLines[3]);
        $this->assertEquals('line#9', $cleanedCodeLines[4]);
    }

    public function testNestedSimpleTagsWithFloats(): void
    {
        $code = <<<'CODE'
line#1
line#2 TRAINING EXERCISE START STEP 1.1
line#3
line#4 TRAINING EXERCISE START STEP 1.2
line#5
line#6 TRAINING EXERCISE STOP STEP 1.2
line#7
line#8 TRAINING EXERCISE STOP STEP 1.1
line#9
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1.1);
        $this->assertCount(2, $cleanedCodeLines);
        $this->assertEquals('line#1', $cleanedCodeLines[0]);
        $this->assertEquals('line#9', $cleanedCodeLines[1]);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1.2);
        $this->assertCount(4, $cleanedCodeLines);
        $this->assertEquals('line#1', $cleanedCodeLines[0]);
        $this->assertEquals('line#3', $cleanedCodeLines[1]);
        $this->assertEquals('line#7', $cleanedCodeLines[2]);
        $this->assertEquals('line#9', $cleanedCodeLines[3]);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1.3);
        $this->assertCount(5, $cleanedCodeLines);
        $this->assertEquals('line#1', $cleanedCodeLines[0]);
        $this->assertEquals('line#3', $cleanedCodeLines[1]);
        $this->assertEquals('line#5', $cleanedCodeLines[2]);
        $this->assertEquals('line#7', $cleanedCodeLines[3]);
        $this->assertEquals('line#9', $cleanedCodeLines[4]);
    }

    public function testKeptNestedSimpleTags(): void
    {
        //Same as testNestedSimpleTags but with $keepTags=true
        $code = <<<'CODE'
line#1
line#2 TRAINING EXERCISE START STEP 1
line#3
line#4 TRAINING EXERCISE START STEP 2
line#5
line#6 TRAINING EXERCISE STOP STEP 2
line#7
line#8 TRAINING EXERCISE STOP STEP 1
line#9
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1, false, true);
        $this->assertCount(4, $cleanedCodeLines);
        $this->assertEquals('line#1', $cleanedCodeLines[0]);
        $this->assertEquals($codeLines[0], $cleanedCodeLines[0]);
        $this->assertStringStartsWith('line#2', $cleanedCodeLines[1]);
        $this->assertEquals($codeLines[1], $cleanedCodeLines[1]);
        $this->assertStringStartsWith('line#8', $cleanedCodeLines[2]);
        $this->assertEquals($codeLines[7], $cleanedCodeLines[2]);
        $this->assertEquals('line#9', $cleanedCodeLines[3]);
        $this->assertEquals($codeLines[8], $cleanedCodeLines[3]);

        $slicedCodeLines = array_values($codeLines);
        array_splice($slicedCodeLines, 4, 1);
        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false, true);
        $this->assertCount(8, $cleanedCodeLines);
        $this->assertEquals($slicedCodeLines, $cleanedCodeLines);

        $this->assertEquals($codeLines, $this->exerciseCleaner->cleanCodeLines($codeLines, 3, false, true));
    }

    public function testStateTag(): void
    {
        $code = <<<'CODE'
//TRAINING EXERCISE START STEP 1 WORKSHEET
function example()
{
    //TRAINING EXERCISE START STEP 1 PLACEHOLDER
    // Instructions
    //TRAINING EXERCISE STOP STEP 1
    //TRAINING EXERCISE START STEP 1 SOLUTION
    return 'Solution';
    //TRAINING EXERCISE STOP STEP 1
}
TRAINING EXERCISE STOP STEP 1
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 0, false);
        $this->assertCount(0, $cleanedCodeLines);
        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 0, true);
        $this->assertCount(0, $cleanedCodeLines);
        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1, false);
        $this->assertEquals(explode(PHP_EOL, <<<'CODE'
function example()
{
    // Instructions
}
CODE
        ), $cleanedCodeLines);
        $expectedCodeLines = explode(PHP_EOL, <<<'CODE'
function example()
{
    return 'Solution';
}
CODE
        );
        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1, true);
        $this->assertEquals($expectedCodeLines, $cleanedCodeLines);
        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false);
        $this->assertEquals($expectedCodeLines, $cleanedCodeLines);
        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true);
        $this->assertEquals($expectedCodeLines, $cleanedCodeLines);
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

        // INI Style
        $this->assertEquals(['  ; Step 1'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false, false, '.ini'));
        $this->assertEquals(['  ; Step 1', '  Step 2+'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true, false, '.ini'));

        // Number Sign Style
        $this->assertEquals(['  # Step 1'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false, false, '.sh'));
        $this->assertEquals(['  # Step 1', '  Step 2+'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true, false, '.yaml'));
        // …As default
        $this->assertEquals(['  # Step 1'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false, false));
        $this->assertEquals(['  # Step 1', '  Step 2+'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true, false, null));

        // Twig Style
        $this->assertEquals(['  {# Step 1 #}'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false, false, '.twig'));
        $this->assertEquals(['  {# Step 1 #}', '  Step 2+'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true, false, '.twig'));

        // XML Style
        $this->assertEquals(['  <!-- Step 1 -->'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false, false, '.xml'));
        $this->assertEquals(['  <!-- Step 1 -->', '  Step 2+'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true, false, '.xml'));

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

    public function testIntroKeyword(): void
    {
        $code = <<<'CODE'
TRAINING EXERCISE START STEP 1 INTRO REMOVE
Step 1 Introduction
TRAINING EXERCISE STOP STEP 1
TRAINING EXERCISE START STEP 1 KEEP INTRO UNTIL 2 THEN REMOVE
Steps 1 & 2 Introduction
TRAINING EXERCISE STOP STEP 1
TRAINING EXERCISE START STEP 1 REMOVE
Step 1 Solution
TRAINING EXERCISE STOP STEP 1
TRAINING EXERCISE START STEP 2 INTRO
Step 2+ Introduction
TRAINING EXERCISE STOP STEP 2
TRAINING EXERCISE START STEP 2
Step 2+ Solution
TRAINING EXERCISE STOP STEP 2
TRAINING EXERCISE START STEP 3
Step 3 Solution
TRAINING EXERCISE STOP STEP 3
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $this->assertEquals(['Step 1 Introduction', 'Steps 1 & 2 Introduction'], $this->exerciseCleaner->cleanCodeLines($codeLines, 1, false));
        $this->assertEquals(['Step 1 Introduction', 'Steps 1 & 2 Introduction', 'Step 1 Solution'], $this->exerciseCleaner->cleanCodeLines($codeLines, 1, true));
        $this->assertStringContainsString('INTRO keyword is deprecated', $this->getLastErrorMessage());
        $this->assertEquals(E_USER_DEPRECATED, $this->getLastErrorType());

        $this->assertEquals(['Steps 1 & 2 Introduction', 'Step 2+ Introduction'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false, false, '.php'));
        $this->assertEquals(['Steps 1 & 2 Introduction', 'Step 2+ Introduction', 'Step 2+ Solution'], $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true, false, '.php'));

        $this->assertEquals(['Step 2+ Introduction', 'Step 2+ Solution'], $this->exerciseCleaner->cleanCodeLines($codeLines, 3, false));
        $this->assertEquals(['Step 2+ Introduction', 'Step 2+ Solution', 'Step 3 Solution'], $this->exerciseCleaner->cleanCodeLines($codeLines, 3, true));
    }

    public function testPlaceholderTags(): void
    {
        $code = <<<'CODE'
line#1
line#2 TRAINING EXERCISE START STEP 1
line#3 // TRAINING EXERCISE STEP PLACEHOLDER First instruction
line#4
line#5 TRAINING EXERCISE START STEP 2
line#6 // Second instruction TRAINING EXERCISE STEP PLACEHOLDER
line#7
line#8 TRAINING EXERCISE STOP STEP 2
line#9
line#10 TRAINING EXERCISE STOP STEP 1
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1);
        $this->assertCount(2, $cleanedCodeLines);
        $this->assertEquals([
            'line#1',
            'line#3 // First instruction',
        ], $cleanedCodeLines);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 1, true);
        $this->assertCount(3, $cleanedCodeLines);
        $this->assertEquals([
            'line#1',
            'line#4',
            'line#9',
        ], $cleanedCodeLines);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2);
        $this->assertCount(4, $cleanedCodeLines);
        $this->assertEquals([
            'line#1',
            'line#4',
            'line#6 // Second instruction',
            'line#9',
        ], $cleanedCodeLines);

        $cleanedCodeLines = $this->exerciseCleaner->cleanCodeLines($codeLines, 2, true);
        $this->assertCount(4, $cleanedCodeLines);
        $this->assertEquals([
            'line#1',
            'line#4',
            'line#7',
            'line#9',
        ], $cleanedCodeLines);
    }

    /*****************/
    /* Error Testing */

    public function testEnclosureParseError(): void
    {
        $code = <<<'CODE'
1 # TRAINING EXERCISE START STEP 1
2 # TRAINING EXERCISE START STEP 2
3 Whatever
4 # TRAINING EXERCISE STOP STEP 1
5 # TRAINING EXERCISE STOP STEP 1
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $this->exerciseCleaner->cleanCodeLines($codeLines);

        $this->assertEquals(E_USER_ERROR, $this->getLastErrorType());
        $this->assertStringContainsStringIgnoringCase('STOP tag not matching START', $this->getLastErrorMessage());
        $this->assertStringContainsString('at line 4', $this->getLastErrorMessage());
    }

    public function testUnclosedParseError(): void
    {
        $code = <<<'CODE'
1 # TRAINING EXERCISE START STEP 1
2 # TRAINING EXERCISE START STEP 2
3 Whatever
4 # TRAINING EXERCISE STOP STEP 2
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $this->exerciseCleaner->cleanCodeLines($codeLines);

        $this->assertEquals(E_USER_ERROR, $this->getLastErrorType());
        $this->assertStringContainsStringIgnoringCase('unclosed tag', $this->getLastErrorMessage());
        $this->assertStringContainsString('at line 1', $this->getLastErrorMessage());

        $this->resetErrors();

        $code = <<<'CODE'
1 # TRAINING EXERCISE START STEP 1
2 # TRAINING EXERCISE START STEP 2
3 # TRAINING EXERCISE START STEP 3
4 # TRAINING EXERCISE STOP STEP 3
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $this->exerciseCleaner->cleanCodeLines($codeLines);

        $this->assertCount(2, $this->errors);

        $this->assertEquals(E_USER_ERROR, $this->errors[0]['type']);
        $this->assertStringContainsStringIgnoringCase('unclosed tag', $this->errors[0]['message']);
        $this->assertStringContainsString('at line 2', $this->errors[0]['message']);

        $this->assertEquals(E_USER_ERROR, $this->errors[1]['type']);
        $this->assertStringContainsStringIgnoringCase('unclosed tag', $this->errors[1]['message']);
        $this->assertStringContainsString('at line 1', $this->errors[1]['message']);
    }

    public function testTagParseError(): void
    {
        foreach ([
                     'TRAINING EXERCISE STEP 1 START',
                     'TRAINING EXERCISE STEP START 1',
                     'TRAINING EXERCISE START 1',
                     '',
                     ] as $tag) {
            $this->exerciseCleaner->cleanCodeLines([$tag]);
            $this->assertStringContainsStringIgnoringCase('tag parse error', $this->getLastErrorMessage());
            $this->assertStringContainsString('at line 1', $this->getLastErrorMessage());
        }
    }

    public function testThresholdWarning(): void
    {
        $code = <<<'CODE'
1 # TRAINING EXERCISE START STEP 1 KEEP UNTIL 1 THEN COMMENT
2 Whatever
3 # TRAINING EXERCISE STOP STEP 1
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $this->exerciseCleaner->cleanCodeLines($codeLines);

        $this->assertEquals(E_USER_WARNING, $this->getLastErrorType());
        $this->assertStringContainsString('Threshold less or equals to step', $this->getLastErrorMessage());
        $this->assertStringContainsString('at line 1', $this->getLastErrorMessage());
    }

    public function testUnsupportedCommentWarning(): void
    {
        $code = <<<'CODE'
1 # TRAINING EXERCISE START STEP 1 COMMENT
2 Whatever
3 # TRAINING EXERCISE STOP STEP 1
4 # TRAINING EXERCISE START STEP 1 KEEP UNTIL 2 THEN COMMENT
5 Whatever
6 # TRAINING EXERCISE STOP STEP 1
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $this->exerciseCleaner->cleanCodeLines($codeLines, 2, false, false, '.json');

        $this->assertEquals(E_USER_WARNING, $this->errors[0]['type']);
        $this->assertStringContainsString('Unsupported COMMENT action', $this->errors[0]['message']);
        $this->assertStringContainsString('at line 1', $this->errors[0]['message']);

        $this->assertEquals(E_USER_WARNING, $this->errors[1]['type']);
        $this->assertStringContainsString('Unsupported COMMENT action', $this->errors[1]['message']);
        $this->assertStringContainsString('at line 4', $this->errors[1]['message']);
    }

    public function testPlaceholderOutsideError(): void
    {
        $code = <<<'CODE'
1 # TRAINING EXERCISE START STEP 2
2 Whatever
3 # TRAINING EXERCISE STOP STEP 2
4 # TRAINING EXERCISE STEP PLACEHOLDER instruction
CODE;
        $codeLines = explode(PHP_EOL, $code);

        for ($step = 1; $step < 4; ++$step) {
            $this->exerciseCleaner->cleanCodeLines($codeLines, $step);
            $this->assertEquals(E_USER_ERROR, $this->getLastErrorType());
            $this->assertStringContainsString("can't be used outside", $this->getLastErrorMessage());
            $this->assertStringContainsString('at line 4', $this->getLastErrorMessage());
        }
    }

    public function testPlaceholderUnnecessaryNotice(): void
    {
        $code = <<<'CODE'
1 # TRAINING EXERCISE START STEP 1 PLACEHOLDER
2 TRAINING EXERCISE STEP PLACEHOLDER Instruction:
3 Instruction
4 # TRAINING EXERCISE STOP STEP 1
CODE;
        $codeLines = explode(PHP_EOL, $code);

        $this->exerciseCleaner->cleanCodeLines($codeLines, 1);
        $this->assertEquals(E_USER_NOTICE, $this->getLastErrorType());
        $this->assertStringContainsStringIgnoringCase('unnecessary', $this->getLastErrorMessage());
        $this->assertStringContainsString('at line 2', $this->getLastErrorMessage());
    }

    /********************/
    /* Error Test Tools */

    /** @var array[] */
    private $errors = [];

    public function errorHandler($type, $message): void
    {
        $this->errors[] = compact('type', 'message');
    }

    private function getLastErrorMessage(): ?string
    {
        if (count($this->errors)) {
            return $this->errors[count($this->errors) - 1]['message'];
        }

        return null;
    }

    private function getLastErrorType(): ?int
    {
        if (count($this->errors)) {
            return $this->errors[count($this->errors) - 1]['type'];
        }

        return null;
    }

    private function resetErrors(): void
    {
        $this->errors = [];
    }
}

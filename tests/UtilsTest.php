<?php

require __DIR__.'/../vendor/autoload.php';

use ExerciseCleaner\Utils;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    public function testGetFileListFromShellCmd(): void
    {
        $this->assertEquals([], Utils::getFileListFromShellCmd('echo "\n";'));
        $this->assertEquals(['a', 'b'], Utils::getFileListFromShellCmd('echo "a\nb";'));
    }
}

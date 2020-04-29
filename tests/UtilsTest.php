<?php

require __DIR__.'/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use ExerciseCleaner\Utils;

class UtilsTest extends TestCase
{
    public function testGetFileListFromShellCmd(): void
    {
        $this->assertEquals([], Utils::getFileListFromShellCmd('echo "\n";'));
        $this->assertEquals(['a', 'b'], Utils::getFileListFromShellCmd('echo "a\nb";'));
    }
}
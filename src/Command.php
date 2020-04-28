<?php

require __DIR__ . '/ExerciseCleaner.php';
use ExerciseCleaner\ExerciseCleaner;

// About

$help = <<<'EOD'
Usage: php exercise-cleaner.phar [--keep-orig] [--keep-tags] [--solution] [<STEP> [<FOLDER> [<FOLDER>...]]]
    --keep-orig: Do not rewrite files but write a new one adding .cleaned extension
    --keep-tags: Do not remove start/stop tags
    --solution: Write exercise's solution instead of exercise itself
    STEP: Remove inside tags having this step and greater.
    FOLDER: Search inside this folder(s) (default: app src)

EOD;

$version = 'raw';

// Options

if (in_array('--version', $argv, true)) {
    echo "$version\n";
    exit(0);
}

if (in_array('--help', $argv, true)) {
    echo $help;
    exit(0);
}

$suffix = '';
$isSuffixed = array_search('--keep-orig', $argv, true);
if (false !== $isSuffixed) {
    $suffix = '.cleaned';
    array_splice($argv, $isSuffixed, 1);
}
unset($isSuffixed);

$keepTags = in_array('--keep-tags', $argv, true);
if ($keepTags) {
    array_splice($argv, array_search('--keep-tags', $argv, true), 1);
}

$solution = in_array('--solution', $argv, true);
if ($solution) {
    array_splice($argv, array_search('--solution', $argv, true), 1);
}

// Arguments

$targetStep = 1 < count($argv) ? (int)$argv[1] : 1;

$folders = 'app src';
if (2 < count($argv)) {
    $folders = array_slice($argv, 2);
}

// Treatment

(new ExerciseCleaner())->cleanFiles($folders, $targetStep, $solution, $keepTags, $suffix);

exit(0);

<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use ExerciseCleaner\CleanCommand;

$version = 'raw';

$application = new Application('Exercise Cleaner', $version);
$defaultCommand = new CleanCommand();
$application->add($defaultCommand);
$application->setDefaultCommand($defaultCommand->getName(), true);
$application->run();

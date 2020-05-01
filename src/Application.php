<?php

namespace ExerciseCleaner;

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;

$version = 'raw';

$application = new Application('Exercise Cleaner', $version);

$defaultSingleCommand = new DefaultSingleCommand();
$application->add($defaultSingleCommand);
$application->setDefaultCommand($defaultSingleCommand->getName(), true);

$application->run();

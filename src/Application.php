<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use ExerciseCleaner\Command;

$version = 'raw';

$application = new Application('Exercise Cleaner', $version);

$command = new Command();
$application->add($command);
//$application->setDefaultCommand($command->getName());//"cannot pass any argument or option to the default command" — https://symfony.com/doc/current/components/console/changing_default_command.html

$application->run();

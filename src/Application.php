<?php

namespace ExerciseCleaner;

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;

$version = 'raw';
switch ($version) {
    case 'raw':
    case 'dev':
        $ref = 'develop';
        break;
    default:
        if (preg_match('@(?<version>v.*)-(?<additional_commits>[0-9]+)-g(?<commit>[0-9a-f]{7})$@', $version, $matches)) {
            $ref = $matches['commit'];
        } else {
            $ref = $version;
        }
}

$application = new Application('Exercise Cleaner', $version);

$defaultSingleCommand = new DefaultSingleCommand();
$defaultSingleCommand->setHelp("https://github.com/adriendupuis/exercise-cleaner/blob/$ref/README.md");
$application->add($defaultSingleCommand);
$application->setDefaultCommand($defaultSingleCommand->getName(), true);

$application->run();

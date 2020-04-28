#!/usr/bin/env php
<?php

use PHPUnit\TextUI\Command;

$pharFileName='exercise-cleaner.phar';

$version = shell_exec('git fetch --tags && git describe --tags 2>&-;') ?? 'dev';

$phar = new Phar($pharFileName);
$phar->addFromString('Command.php', preg_replace('@\$version = .+;@', "\$version = '$version';", file_get_contents('src/Command.php')));
$phar->addFile('src/ExerciseCleaner.php', 'ExerciseCleaner.php');
$phar->setStub(Phar::createDefaultStub('Command.php'));

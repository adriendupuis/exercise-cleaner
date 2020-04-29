#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use ExerciseCleaner\Utils;

$pharFileName = 'exercise-cleaner.phar';

$version = trim(shell_exec('git fetch --tags --force --quiet && git describe --tags 2>&-;')) ?? 'dev';

$phar = new Phar($pharFileName);

$phar->addFromString('src/Application.php', preg_replace('@\$version = .+;@', "\$version = '$version';", file_get_contents('src/Application.php')));
$phar->setStub(Phar::createDefaultStub('src/Application.php'));

foreach ([
             // Exercise Cleaner files except stub
             'src/Command.php',
             'src/ExerciseCleaner.php',
             'src/Utils.php',
             // Composer's autoload parts
             'vendor/autoload.php',
             'vendor/composer',
             // Symfony Console & Dependencies
             'vendor/psr/container',
             'vendor/symfony/service-contracts',
             'vendor/symfony/polyfill-php73',
             'vendor/symfony/polyfill-mbstring',
             'vendor/symfony/console'
         ] as $path) {
    if (is_dir($path)) {
        $fileList = Utils::getFileListFromShellCmd("find $path -type f;");
    } elseif (is_file($path)) {
        $fileList = [$path];
    } else {
        trigger_error("$path is not a file nor a directory", E_USER_WARNING);
        continue;
    }
    foreach ($fileList as $file) {
        $phar->addFile($file);
    }
}

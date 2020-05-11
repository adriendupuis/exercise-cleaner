#!/usr/bin/env php
<?php

require __DIR__.'/src/Utils.php';

use ExerciseCleaner\Utils;

$pharFileName = 'exercise-cleaner.phar';
@unlink($pharFileName);

$version = trim(shell_exec('git fetch --tags --force --quiet && git describe --tags 2>&-;')) ?? 'dev';

$phar = new Phar($pharFileName);

$phar->addFromString('src/Application.php', preg_replace('@\$version = .+;@', "\$version = '$version';", file_get_contents('src/Application.php')));
$phar->setStub("#!/usr/bin/env php\n".Phar::createDefaultStub('src/Application.php'));

// Remove dev dependencies (like phpunit)
shell_exec('composer install --no-dev --quiet;');

foreach ([
             // Exercise Cleaner files except stub
             'src/DefaultSingleCommand.php',
             'src/ExerciseCleaner.php',
             'src/Utils.php',
             // Dependencies
             'vendor/',
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

shell_exec("chmod +x $pharFileName");

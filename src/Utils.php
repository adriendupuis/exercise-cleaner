<?php

namespace ExerciseCleaner;

class Utils
{
    static function getFileListFromShellCmd($cmd): array
    {
        $fileList = explode(PHP_EOL, trim(shell_exec($cmd)));

        if (1 === count($fileList) && '' === $fileList[0]) {
            // Command didn't find any appropriate file
            $fileList = [];
        }

        return $fileList;
    }
}
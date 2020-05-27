<?php

namespace ExerciseCleaner;

class Utils
{
    public static function getFileListFromShellCmd($cmd, $fixDoubleSlash = true): array
    {
        $fileList = explode(PHP_EOL, trim(shell_exec($cmd)));

        if (1 === count($fileList) && '' === $fileList[0]) {
            // Command didn't find any appropriate file
            $fileList = [];
        } elseif ($fixDoubleSlash) {
            array_walk($fileList, function (&$value) {
                $value = str_replace('//', '/', $value);
            });
        }

        return $fileList;
    }
}

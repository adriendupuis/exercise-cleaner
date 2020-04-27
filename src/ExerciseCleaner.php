<?php

namespace ExerciseCleaner;

class ExerciseCleaner
{
    // Tag Syntax Config
    public $startTagConstant = 'TRAINING EXERCISE START STEP';
    public $startTagRegex;
    public $stopTagConstant = 'TRAINING EXERCISE STOP STEP';
    public $stopTagRegex;

    public function __construct()
    {
        $this->startTagRegex = "@{$this->startTagConstant} (?<step>[0-9]+) ?(?<action>[A-Z]*)@";
        $this->stopTagRegex = "@{$this->stopTagConstant} (?<step>[0-9]+)@";
    }

    public function cleanCodeLines($lines, $targetStep = 1, $keepTags = false, $fileType=null)
    {
        $keptLines = [];
        $isInside = [];
        $step = 0;
        $commentPattern = '';
        switch ($fileType) {
            case 'json':
                $commentPattern = ''; // There is no comment in JSON
                break;
            case 'twig':
                $commentPattern = '{* %CODE% *}';
                break;
            case 'php':
                $commentPattern = '// %CODE%';
                break;
            case 'sh':
            case 'zsh':
            case 'bash':
            case 'yaml':
            case 'yml':
            default:
                $commentPattern = '# %CODE%';
        }
        foreach ($lines as $lineIndex => $line) {
            if (false !== strpos($line, $this->stopTagConstant)) {
                $matches = [];
                preg_match($this->stopTagRegex, $line, $matches);
                $step = (int)$matches['step'];
                $currentTag = $isInside[count($isInside)-1];
                if ($step !== $currentTag['step']) {
                    //TODO: error or warning
                }
                array_pop($isInside);
                if ($keepTags && $step <= $targetStep) {
                    $keptLines[] = $line;
                }
            } elseif (false !== strpos($line, $this->startTagConstant)) {
                $matches = [];
                preg_match($this->startTagRegex, $line, $matches);
                $step = (int)$matches['step'];
                $action = strtoupper(trim($matches['action']));
                $isInside[] = ['step' => $step, 'action' => $action];
                if (count($isInside) > $step) {
                    //TODO: error or warning
                }
                if ($keepTags && $step <= $targetStep) {
                    $keptLines[] = $line;
                }
            } elseif (count($isInside)) {
                $currentTag = $isInside[count($isInside)-1];
                $step = (int)$currentTag['step'];
                if ($step < $targetStep) {
                    $action = $currentTag['action'];
                    switch ($action) {
                        case 'COMMENT':
                            $keptLines[] = str_replace('%CODE%', $line, $commentPattern);
                            break;
                        case 'REMOVE':
                            break;
                        case 'KEEP':
                        case '':
                        default:
                            $keptLines[] = $line;
                    }
                }
            } else {
                $keptLines[] = $line;
            }
        }

        return $keptLines;
    }

    public function cleanFiles(array $pathList, $targetStep = 1, $keepTags = false, $suffix = '')
    {
        foreach ($pathList as $path) {
            if ('' === $path) {
                continue;
            }
            if ('/' !== $path[0]) {
                $path = trim(`pwd`)."/$path";//TODO: Better fix
            }
            if (is_dir($path)) {
                $fileList = explode(PHP_EOL, trim(shell_exec("grep '{$this->startTagConstant}' -Rl $path;")));
            } elseif (is_file($path)) {
                $fileList = [$path];
            } else {
                trigger_error("$path is not a file nor a directory", E_USER_WARNING);
                continue;
            }
            foreach ($fileList as $file) {
                if (!is_file($file)) {
                    trigger_error("$path is not a file", E_USER_WARNING);
                    continue;
                }
                file_put_contents($file . $suffix, $this->cleanCodeLines(file($file), $targetStep, $keepTags, pathinfo($file, PATHINFO_EXTENSION)));
            }
        }
    }
}

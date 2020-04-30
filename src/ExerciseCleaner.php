<?php

namespace ExerciseCleaner;

class ExerciseCleaner
{
    // Tag Syntax Config
    public $startTagConstant = 'TRAINING EXERCISE START STEP';
    public $startTagRegex;
    public $stopTagConstant = 'TRAINING EXERCISE STOP STEP';
    public $stopTagRegex;
    public $thresholdActionRegex = '@(?<action_before>[A-Z]+) UNTIL (?<threshold_step>[0-9]+) THEN (?<action_after>[A-Z]+)@';

    /** @var bool */
    private $isPhar;

    public function __construct()
    {
        $this->startTagRegex = "@{$this->startTagConstant} (?<step>[0-9]+) ?(?<action>[ A-Z1-9]*)@";
        $this->stopTagRegex = "@{$this->stopTagConstant} (?<step>[0-9]+)@";
        $this->isPhar = (bool) preg_match('@^phar:///@', __DIR__);
    }

    /** @return string[] */
    public function cleanCodeLines(array $lines, int $targetStep = 1, bool $solution = false, bool $keepTags = false, string $file = null): array
    {
        $fileType = $file ? pathinfo($file, PATHINFO_EXTENSION) : null;
        $keptLines = [];
        $nestedTags = [];
        $step = 0;
        switch ($fileType) {
            case 'json':
                $commentPattern = ''; // There is no comment in JSON
                break;
            case 'twig':
                $commentPattern = '{# %CODE% #}';
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
                $step = (int) $matches['step'];
                $currentTag = $nestedTags[count($nestedTags) - 1];
                if ($step !== $currentTag['step']) {
                    trigger_error('Parse Error: STOP tag not matching START tag'.($file ? " in file $file" : '')." at line $lineIndex", E_USER_ERROR);
                }
                array_pop($nestedTags);
                if ($keepTags && $step <= $targetStep) {
                    $keptLines[] = $line;
                }
            } elseif (false !== strpos($line, $this->startTagConstant)) {
                $matches = [];
                preg_match($this->startTagRegex, $line, $matches);
                $step = (int) $matches['step'];
                $action = strtoupper(trim($matches['action']));
                $nestedTags[] = ['step' => $step, 'action' => $action];
                if ('' !== trim($action) && false !== strpos($action, ' ')) {
                    $matches = [];
                    if (preg_match($this->thresholdActionRegex, $action, $matches)) {
                        $nestedTags[count($nestedTags) - 1]['before'] = strtoupper(trim($matches['action_before']));
                        $nestedTags[count($nestedTags) - 1]['threshold'] = (int) $matches['threshold_step'];
                        $nestedTags[count($nestedTags) - 1]['after'] = strtoupper(trim($matches['action_after']));
                    }
                    if ($matches['threshold_step'] <= $step) {
                        trigger_error('Threshold less or equals to step'.($file ? " in file $file" : '')." at line $lineIndex", E_USER_WARNING);
                    }
                }
                if (count($nestedTags) > $step) {
                    trigger_error('More nested tags than steps'.($file ? " in file $file" : '')." at line $lineIndex", E_USER_NOTICE);
                }
                if ($keepTags && $step <= $targetStep) {
                    $keptLines[] = $line;
                }
            } elseif (count($nestedTags)) {
                $currentTag = $nestedTags[count($nestedTags) - 1];
                $step = (int) $currentTag['step'];
                if ($step < $targetStep) {
                    $action = $currentTag['action'];
                    if (array_key_exists('threshold', $currentTag)) {
                        if ($currentTag['threshold'] >= $targetStep) {
                            $action = $currentTag['before'];
                        } else {
                            $action = $currentTag['after'];
                        }
                    }
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
                } elseif ($solution && $step === $targetStep) {
                    $keptLines[] = $line;
                }
            } else {
                $keptLines[] = $line;
            }
        }

        return $keptLines;
    }

    public function cleanFiles(array $pathList, int $targetStep = 1, bool $solution = false, bool $keepTags = false, string $suffix = ''): void
    {
        foreach ($pathList as $path) {
            if ('' === $path) {
                continue;
            }
            if ($this->isPhar && '/' !== $path[0]) {
                $path = trim(`pwd`)."/$path"; //TODO: Better fix
            }
            if (is_dir($path)) {
                if ('/' === substr($path, -1)) {
                    // Avoid double slashes in grep result
                    $path = substr($path, 0, -1);
                }
                $cmd = "grep '{$this->startTagConstant}' -Rl $path;";
                $fileList = Utils::getFileListFromShellCmd($cmd);
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
                file_put_contents($file.$suffix, $this->cleanCodeLines(file($file), $targetStep, $solution, $keepTags, $file));
            }
        }
    }
}

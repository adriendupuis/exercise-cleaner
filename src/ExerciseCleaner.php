<?php

namespace ExerciseCleaner;

use Symfony\Component\Console\Output\OutputInterface;

class ExerciseCleaner
{
    // Tag Syntax Config
    public $startTagConstant = 'TRAINING EXERCISE START STEP';
    public $startTagRegex;
    public $stopTagConstant = 'TRAINING EXERCISE STOP STEP';
    public $stopTagRegex;
    public $thresholdActionRegex = '@(?<action_before>[A-Z]+) UNTIL (?<threshold_step>[\.0-9]+) THEN (?<action_after>[A-Z]+)@';
    public $placeHolderTagConstant = 'TRAINING EXERCISE STEP PLACEHOLDER';

    /** @var bool */
    private $isPhar;

    /** @var array|null */
    private $config;

    /** @var OutputInterface */
    private $output;

    public function __construct(array $config = null, OutputInterface $output = null)
    {
        // Tag Syntax Config
        $this->startTagRegex = "@{$this->startTagConstant} (?<step>[\.0-9]+) ?(?<action>[ A-Z\.0-9]*)@";
        $this->stopTagRegex = "@{$this->stopTagConstant} (?<step>[\.0-9]+)@";

        // Executable Phar
        $this->isPhar = Utils::isPhar();

        if (!is_null($config)) {
            // Step Names Parsing
            if (array_key_exists('steps', $config) && array_key_exists('names', $config['steps'])) {
                $stepNames = [];
                foreach ($config['steps']['names'] as $index => $name) {
                    if (is_numeric($index) && is_string($name)) {
                        $stepNames["step_$index"] = $name;
                    } elseif (is_array($name) && array_key_exists('name', $name)) {
                        $stepNames['step_'.(array_key_exists('n', $name) ? $name['n'] : $name['number'])] = $name['name'];
                    }
                }
                $config['steps']['names'] = $stepNames;
            }
        }

        // Config
        $this->config = $config;
        $this->output = $output;
    }

    /** @return string[] */
    public function cleanCodeLines(array $lines, float $targetStep = 1, bool $solution = false, bool $keepTags = false, string $file = null): array
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
                $step = (float) $matches['step'];
                $stoppedTag = array_pop($nestedTags);
                if ($step !== $stoppedTag['step']) {
                    trigger_error('Parse Error: STOP tag not matching START tag'.($file ? " in file $file" : '')." at line $lineIndex", E_USER_ERROR);
                }

                if ($keepTags && $step <= $targetStep) {
                    $keptLines[] = $line;
                }

                $this->outputWrite("Stop step $step".($stoppedTag['name'] ? " “{$stoppedTag['name']}”" : '')." at line $lineIndex.", OutputInterface::VERBOSITY_VERBOSE);
                if (count($nestedTags)) {
                    $currentTag = $nestedTags[count($nestedTags) - 1];
                    $this->outputWrite("Reenter step {$currentTag['step']}".($currentTag['name'] ? " “{$currentTag['name']}”" : '')." at line $lineIndex:", OutputInterface::VERBOSITY_VERBOSE);
                    $this->outputWrite($this->getActionVerb($currentTag, $targetStep).'…', OutputInterface::VERBOSITY_VERBOSE);
                }
            } elseif (false !== strpos($line, $this->startTagConstant)) {
                $matches = [];
                preg_match($this->startTagRegex, $line, $matches);
                $step = (float) $matches['step'];
                $action = strtoupper(trim($matches['action']));

                $startedTag = [
                    'step' => $step,
                    'action' => $action,
                    'name' => $this->config['steps']['names']["step_$step"] ?? '',
                ];
                if ('' !== trim($action) && false !== strpos($action, ' ')) {
                    $matches = [];
                    if (preg_match($this->thresholdActionRegex, $action, $matches)) {
                        $startedTag['before'] = strtoupper(trim($matches['action_before']));
                        $startedTag['threshold'] = (float) $matches['threshold_step'];
                        $startedTag['after'] = strtoupper(trim($matches['action_after']));
                    }
                    if ($matches['threshold_step'] <= $step) {
                        trigger_error('Threshold less or equals to step'.($file ? " in file $file" : '')." at line $lineIndex", E_USER_WARNING);
                    }
                }
                $nestedTags[] = $startedTag;

                if ($keepTags && $step <= $targetStep) {
                    $keptLines[] = $line;
                }

                $this->outputWrite("Start step $step".($startedTag['name'] ? " “{$startedTag['name']}”" : '')." at line $lineIndex:", OutputInterface::VERBOSITY_VERBOSE);
                $this->outputWrite($this->getActionVerb($startedTag, $targetStep).'…', OutputInterface::VERBOSITY_VERBOSE);
            } elseif (count($nestedTags)) {
                $currentTag = $nestedTags[count($nestedTags) - 1];
                $step = (float) $currentTag['step'];
                if ($step < $targetStep && false === strpos($line, $this->placeHolderTagConstant)) {
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
                            preg_match('@^(?<indent> *)(?<code>.*)$@', $line, $matches);
                            $keptLines[] = ($matches['indent'] ?? '').str_replace('%CODE%', $matches['code'] ?? '', $commentPattern);
                            break;
                        case 'REMOVE':
                            break;
                        case 'KEEP':
                        case '':
                        default:
                            $keptLines[] = $line;
                    }
                } elseif ($step === $targetStep) {
                    if ($solution && false === strpos($line, $this->placeHolderTagConstant)) {
                        $keptLines[] = $line;
                    } else if (!$solution && false !== strpos($line, $this->placeHolderTagConstant)) {
                        $keptLines[] = preg_replace("@ *{$this->placeHolderTagConstant}@", '', $line);
                    }
                }
            } else if (false === strpos($line, $this->placeHolderTagConstant)) {
                $keptLines[] = $line;
            }
        }

        return $keptLines;
    }

    public function cleanFiles(array $pathList, float $targetStep = 1, bool $solution = false, bool $keepTags = false, string $suffix = ''): void
    {
        foreach ($pathList as $path) {
            if ('' === $path) {
                continue;
            }
            if ($this->isPhar) {
                $path = Utils::getAbsolutePath($path);
            }
            if (is_dir($path)) {
                if ('/' === substr($path, -1)) {
                    // Avoid double slashes in grep result
                    $path = substr($path, 0, -1);
                }
                $cmd = "grep '{$this->startTagConstant}' -Rl $path | grep -v '$suffix$';";
                $fileList = Utils::getFileListFromShellCmd($cmd);
            } elseif (is_file($path)) {
                $fileList = [$path];
            } else {
                trigger_error("$path is not a file nor a directory", E_USER_WARNING);
                continue;
            }
            foreach ($fileList as $file) {
                $this->outputWrite("<info>Treat {$file}…</info>", OutputInterface::VERBOSITY_NORMAL);
                if (!is_file($file)) {
                    trigger_error("$path is not a file", E_USER_WARNING);
                    continue;
                }
                if (false !== file_put_contents($file.$suffix, implode(PHP_EOL, $this->cleanCodeLines(file($file, FILE_IGNORE_NEW_LINES), $targetStep, $solution, $keepTags, $file)))) {
                    $this->outputWrite("<info>…{$file}{$suffix} written.</info>", OutputInterface::VERBOSITY_NORMAL);
                } else {
                    trigger_error("$file$suffix couldn't be written", E_USER_ERROR);
                }
            }
        }
    }

    private function getActionVerb(array $tag, float $targetStep): string
    {
        if ($tag['step'] < $targetStep) {
            if (array_key_exists('threshold', $tag)) {
                if ($tag['threshold'] >= $targetStep) {
                    $action = $tag['before'];
                } else {
                    $action = $tag['after'];
                }
            } elseif (array_key_exists('action', $tag)) {
                $action = $tag['action'];
            } else {
                $action = null;
            }
            switch ($action) {
                case 'COMMENT':
                    return 'Comment previous step line(s)';
                case 'REMOVE':
                    return 'Remove previous step line(s)';
                case 'KEEP':
                case '':
                default:
                    return 'Keep previous step line(s)';
            }
        } elseif ($tag['step'] === $targetStep) {
            return 'Remove current step line(s)';
        } else /*if ($tag['step'] > $targetStep)*/ {
            return 'Remove next step line(s)';
        }

        return 'Remove unknown step line(s)';
    }

    private function outputWrite($messages, $verbosity = OutputInterface::VERBOSITY_QUIET)
    {
        if (null !== $this->output) {
            $this->output->writeln($messages, $verbosity);
        }
    }
}

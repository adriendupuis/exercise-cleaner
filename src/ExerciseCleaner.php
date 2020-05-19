<?php

namespace ExerciseCleaner;

use Symfony\Component\Console\Output\OutputInterface;

class ExerciseCleaner
{
    public $tagConstant = 'TRAINING EXERCISE';
    public $tagRegex = '/TRAINING EXERCISE (?<boundary>START|STOP) STEP (?<step>[\.0-9]+) ?(?<state>PLACEHOLDER|SOLUTION|WORKSHEET)? ?(?<action>COMMENT|KEEP|REMOVE)? ?(UNTIL (?<threshold_step>[\.0-9]+))? ?(THEN (?<threshold_action>COMMENT|KEEP|REMOVE))?/';
    public $placeholderTagConstant = 'TRAINING EXERCISE STEP PLACEHOLDER';

    /** @var bool */
    private $isPhar;

    /** @var array|null */
    private $config;

    /** @var OutputInterface */
    private $output;

    public function __construct(array $config = null, OutputInterface $output = null)
    {
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
                        $stepNames['step_' . (array_key_exists('n', $name) ? $name['n'] : $name['number'])] = $name['name'];
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
        $keptLines = [];
        $nestedTags = [];
        $commentPattern = $this->getCommentPattern($file);

        foreach ($lines as $lineIndex => $line) {
            $lineNumber = 1 + $lineIndex;
            if (false !== strpos($line, $this->tagConstant) && false === strpos($line, $this->placeholderTagConstant)) {
                $tag = $this->parseTag($line, $lineNumber, $file);
                if ($tag['start']) {
                    $nestedTags[] = $tag;

                    if ($keepTags && $tag['step'] <= $targetStep) {
                        $keptLines[] = $line;
                    }

                    $this->outputWrite("Start step {$tag['step']}" . ($tag['name'] ? " “{$tag['name']}”" : '') . " at line $lineNumber:", OutputInterface::VERBOSITY_VERY_VERBOSE);
                    $this->outputWrite($this->getActionVerb($tag, $targetStep) . '…', OutputInterface::VERBOSITY_VERY_VERBOSE);
                } else {
                    $stoppedTag = array_pop($nestedTags);
                    if ($tag['step'] !== $stoppedTag['step']) {
                        trigger_error('Parse Error: STOP tag not matching START tag' . ($file ? " in file $file" : '') . " at line $lineNumber", E_USER_ERROR);
                    }

                    if ($keepTags && $tag['step'] <= $targetStep) {
                        $keptLines[] = $line;
                    }

                    $this->outputWrite("Stop step {$tag['step']}" . ($stoppedTag['name'] ? " “{$stoppedTag['name']}”" : '') . " at line $lineNumber.", OutputInterface::VERBOSITY_VERY_VERBOSE);
                    if (count($nestedTags)) {
                        $currentTag = $nestedTags[count($nestedTags) - 1];
                        $this->outputWrite("Reenter step {$currentTag['step']}" . ($currentTag['name'] ? " “{$currentTag['name']}”" : '') . " at line $lineNumber:", OutputInterface::VERBOSITY_VERY_VERBOSE);
                        $this->outputWrite($this->getActionVerb($currentTag, $targetStep) . '…', OutputInterface::VERBOSITY_VERY_VERBOSE);
                    }
                }
            } elseif (count($nestedTags)) {
                $currentTag = $nestedTags[count($nestedTags) - 1];
                if ($targetStep > $currentTag['step'] && false === strpos($line, $this->placeholderTagConstant)) {
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
                            if (null !== $commentPattern) {
                                preg_match('@^(?<indent> *)(?<code>.*)$@', $line, $matches);
                                $keptLines[] = ($matches['indent'] ?? '') . str_replace('%CODE%', $matches['code'] ?? '', $commentPattern);
                            }
                            break;
                        case 'REMOVE':
                            break;
                        case 'KEEP':
                        case '':
                        default:
                            $keptLines[] = $line;
                    }
                } elseif ($targetStep === $currentTag['step']) {
                    //if (false !== strpos($line, $this->placeholderTagConstant)) {
                    //    trigger_error("{$this->placeholderTagConstant} one-line tag is deprecated; TRAINING EXERCISE START STEP <n> PLACEHOLDER syntax should be used instead.", E_USER_DEPRECATED);
                    //}
                    switch ($currentTag['state']) {
                        case 'PLACEHOLDER':
                            if (!$solution) {
                                $keptLines[] = $line;
                            }
                            break;
                        case 'WORKSHEET':
                            if (false !== strpos($line, $this->placeholderTagConstant)) {
                                $line = preg_replace("@ *{$this->placeholderTagConstant}@", '', $line);
                            }
                            $keptLines[] = $line;
                            break;
                        case 'SOLUTION':
                        default;
                            if ($solution && false === strpos($line, $this->placeholderTagConstant)) {
                                $keptLines[] = $line;
                            } else if (!$solution && false !== strpos($line, $this->placeholderTagConstant)) {
                                $keptLines[] = preg_replace("@ *{$this->placeholderTagConstant}@", '', $line);
                            }
                            break;
                    }
                }
            } else {
                if (false !== strpos($line, $this->placeholderTagConstant)) {
                    trigger_error("{$this->placeholderTagConstant} one-line tag can't be used outside a START/STOP STEP tags pair.", E_USER_DEPRECATED);
                }
                $keptLines[] = $line;
            }
        }

        return $keptLines;
    }

    public function parseTag(string $line, int $lineNumber = null, string $file = null): array
    {
        $intro = false !== strpos($line, 'INTRO'); // backward compatibility
        if ($intro) {
            trigger_error('INTRO keyword is deprecated, WORKSHEET should be used instead; ' . ($file ? " in file $file" : '') . " at line $lineNumber", E_USER_DEPRECATED);
            $line = trim(str_replace('  ', ' ', str_replace('INTRO', '', $line)));
        }

        preg_match($this->tagRegex, $line, $matches);

        if (!count($matches)) {
            trigger_error('Parse Error' . ($file ? " in file $file" : '') . ($lineNumber ? " at line $lineNumber" : ''), E_USER_ERROR);
            return [];
            throw new \ParseError('Parse Error' . ($file ? " in file $file" : '') . ($lineNumber ? " at line $lineNumber" : ''));
        }
        $tag = [
            'boundary' => $matches['boundary'],
            'start' => 'START' === $matches['boundary'],
            'step' => (float)$matches['step'],
        ];

        if ($tag['start']) {
            $tag = array_merge($tag, [
                'name' => $this->getStepName($tag['step']),
                'state' => empty($matches['state']) ? 'SOLUTION' : $matches['state'],
            ]);

            if ($intro) {
                $tag['state'] = 'WORKSHEET';
            }

            if (empty($matches['action'])) {
                switch ($tag['state']) {
                    case 'PLACEHOLDER':
                        $tag['action'] = 'REMOVE';
                        break;
                    case 'WORKSHEET':
                    case 'SOLUTION':
                    default:
                        $tag['action'] = 'KEEP';
                        break;
                }
            } else {
                $tag['action'] = $matches['action'];
            }

            if (!empty($matches['threshold_step'])) {
                $tag = array_merge($tag, [
                    'before' => $tag['action'],
                    'threshold' => (float) $matches['threshold_step'],
                    'after' => $matches['threshold_action'] ?? 'REMOVE',
                ]);
                $tag['action'] = "{$tag['before']} UNTIL {$tag['threshold']} THEN {$tag['after']}";

                if ($tag['threshold'] <= $tag['step']) {
                    trigger_error('Threshold less or equals to step' . ($file ? " in file $file" : '') . " at line $lineNumber", E_USER_WARNING);
                }
            }

            if (false !== strpos($tag['action'], 'COMMENT') && null === $this->getCommentPattern($file)) {
                trigger_error("Unsupported COMMENT action at line $lineNumber", E_USER_WARNING);
            }
        }

        return $tag;
    }

    private function getCommentPattern(string $file = null): ?string
    {
        switch ($file ? pathinfo($file, PATHINFO_EXTENSION) : null) {
            case 'json':
                return null; // There is no comment in JSON
            case 'twig':
                return '{# %CODE% #}';
            case 'php':
                return '// %CODE%';
            case 'sh':
            case 'zsh':
            case 'bash':
            case 'yaml':
            case 'yml':
            default:
                return '# %CODE%';
        }
    }

    public function cleanFiles(array $pathList, float $targetStep = 1, bool $solution = false, bool $keepTags = false, string $outputExtension = null, string $inputExtension = null): void
    {
        $targetStepName = $this->getStepName($targetStep);
        $targetStepName = null === $targetStepName ? '' : " “{$targetStepName}”";
        $this->outputWrite("<comment>Get step $targetStep{$targetStepName} in {$this->getStateName($solution)} state…</comment>", OutputInterface::VERBOSITY_NORMAL);

        if (!$inputExtension && !empty($this->config['files']['input']['extension'])) {
            $inputExtension = $this->config['files']['input']['extension'];
        }
        if ($inputExtension && 0 !== strpos($inputExtension, '.')) {
            $inputExtension = ".$inputExtension";
        }
        if ($outputExtension && 0 !== strpos($outputExtension, '.')) {
            $outputExtension = ".$outputExtension";
        }

        foreach ($pathList as $path) {
            if ('' === $path) {
                continue;
            }
            if ($this->isPhar) {
                $path = Utils::getAbsolutePath($path);
            }
            if (is_dir($path)) {
                if ('/' === substr($path, -1)) {
                    // Avoid double slashes in find or grep result
                    $path = substr($path, 0, -1);
                }
                if ($inputExtension) {
                    $cmd = "find $path -name '*$inputExtension'";
                } else {
                    $cmd = "grep '{$this->tagConstant}' -Rl $path";
                    if ($outputExtension) {
                        $cmd .= " | grep -v '$outputExtension$'";
                    }
                }
                $fileList = Utils::getFileListFromShellCmd("$cmd;");
            } elseif (is_file($path)) {
                $fileList = [$path];
            } else {
                trigger_error("$path is not a file nor a directory", E_USER_WARNING);
                continue;
            }
            foreach ($fileList as $inputFile) {
                $this->outputWrite("<info>Treat {$inputFile}…</info>", OutputInterface::VERBOSITY_VERBOSE);
                if (!is_file($inputFile)) {
                    trigger_error("$path is not a file", E_USER_WARNING);
                    continue;
                }
                $outputFile = $inputFile;
                if ($inputExtension) {
                    $outputFile = str_replace($inputExtension, '', $outputFile);
                }
                if ($outputExtension) {
                    $outputFile = "$outputFile$outputExtension";
                }
                if (false !== file_put_contents($outputFile, implode(PHP_EOL, $this->cleanCodeLines(file($inputFile, FILE_IGNORE_NEW_LINES), $targetStep, $solution, $keepTags, $inputFile)))) {
                    $this->outputWrite("<info>…$outputFile written.</info>", OutputInterface::VERBOSITY_VERBOSE);
                } else {
                    trigger_error("$outputFile couldn't be written", E_USER_ERROR);
                }
            }
        }
    }

    public function getStepName(float $step): ?string
    {
        return $this->config['steps']['names']["step_$step"] ?? null;
    }

    public function getStateName(bool $solution): string
    {
        return $solution ? 'solution' : 'exercise';
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

    private function outputWrite($messages, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        if (null !== $this->output) {
            $this->output->writeln($messages, $verbosity);
        }
    }
}

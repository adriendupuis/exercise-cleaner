<?php

namespace ExerciseCleaner;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class DefaultSingleCommand extends Command
{
    protected static $defaultName = 'exercise-cleaner.phar';

    protected function configure(): void
    {
        $this
            ->setDescription('Prepare files for an exercise or its solution at a given step')
            ->setHelp('https://github.com/adriendupuis/exercise-cleaner/blob/develop/README.md') // Updated by Application according to version
            ->addOption('input-ext', 'i', InputOption::VALUE_REQUIRED, 'Treat only for file having this given extension and remove this extension')
            ->addOption('output-ext', 'o', InputOption::VALUE_NONE, 'Do not rewrite files but write a new one adding an extension which includes step number and if it\'s state (exercise or solution)')
            ->addOption('keep-orig', null, InputOption::VALUE_NONE, '<info>--output-ext</info> alias for backward compatibility')
            ->addOption('keep-tags', 't', InputOption::VALUE_NONE, 'Do not remove start/stop tags')
            ->addOption('exercise', 'e', InputOption::VALUE_NONE, 'Write exercise\'s worksheet (default)')
            //->addOption('worksheet', 'w', InputOption::VALUE_NONE, 'Write exercise\'s worksheet (default)')
            ->addOption('solution', 's', InputOption::VALUE_NONE, 'Write exercise\'s solution instead of exercise\'s worksheet')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to YAML config file')
            ->addArgument('step', InputArgument::OPTIONAL, 'Remove inside tags having this step float and greater.', 1)
            ->addArgument('paths', InputArgument::IS_ARRAY, 'Search inside this folder(s).', ['app', 'src'])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->getFormatter()->setStyle('warning', new OutputFormatterStyle('white', 'yellow'));

        $pathList = $input->getArgument('paths');
        $targetStep = $input->getArgument('step');
        $solution = $input->getOption('solution');
        $keepTags = $input->getOption('keep-tags');

        $inputExtension = null;
        if ($this->getDefinition()->hasOption('input-ext')) {
            $inputExtension = $input->getOption('input-ext');
        }
        if ($input->getOption('keep-orig')/* && !$input->getOption('output-ext')*/) {
            // backward compatibility
            $output->writeln('<warning>--keep-orig is deprecated, use --output-ext instead.</warning>');
            $input->setOption('output-ext', true);
        }
        $outputExtension = $input->getOption('output-ext') ? ".step$targetStep.".($solution ? 'solution' : 'exercise') : '';

        $config = null;
        if ($this->getDefinition()->hasOption('config')) {
            $configFile = $input->getOption('config');
            if (!is_null($configFile)) {
                if (is_file(realpath($configFile))) {
                    switch (pathinfo($configFile, PATHINFO_EXTENSION)) {
                        case 'yaml':
                        case 'yml':
                        default:
                            $config = Yaml::parse(file_get_contents($configFile));
                    }
                } elseif (!$output->isQuiet()) {
                    $output->writeln("<warning>Config file $configFile doesn't exist or isn't a file.</warning>");
                }
            }
        }

        if (is_numeric($targetStep)) {
            (new ExerciseCleaner($config, $output))->cleanFiles($pathList, $targetStep, $solution, $keepTags, $outputExtension, $inputExtension);
        } elseif (!$output->isQuiet()) {
            $output->writeln('<error>Step argument is missing or isn\'t numeric</error>');

            return 1;
        }

        return 0;
    }
}

<?php

namespace ExerciseCleaner;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class DefaultSingleCommand extends Command
{
    protected static $defaultName = 'default:single:command';

    protected function configure()
    {
        $this
            ->setDescription('Prepare files for an exercise or its solution at a given step')
            ->setHelp('TODO')
            ->addOption('keep-orig', 'o', InputOption::VALUE_NONE, 'Do not rewrite files but write a new one adding an extension which includes step number and if it\'s an exercise or a solution')
            ->addOption('keep-tags', 't', InputOption::VALUE_NONE, 'Do not remove start/stop tags')
            ->addOption('solution', 's', InputOption::VALUE_NONE, 'Write exercise\'s solution instead of exercise itself')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to YAML config file')
            ->addArgument('step', InputArgument::OPTIONAL, 'Remove inside tags having this step float and greater.', 1)
            ->addArgument('folders', InputArgument::IS_ARRAY, 'Search inside this folder(s).', ['app', 'src'])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $folders = $input->getArgument('folders');
        $targetStep = $input->getArgument('step');
        $solution = $input->getOption('solution');
        $keepTags = $input->getOption('keep-tags');
        $suffix = $input->getOption('keep-orig') ? ".step$targetStep.".($solution ? 'solution' : 'exercise') : '';

        $config = null;
        if ($this->getDefinition()->hasOption('config')) {
            $configFile = $input->getOption('config');
            if (!is_null($configFile)) {
                if (is_file($configFile)) {
                    switch (pathinfo($configFile, PATHINFO_EXTENSION)) {
                        case 'yaml':
                        case 'yml':
                        default:
                            $config = Yaml::parse(file_get_contents($configFile));
                    }
                } else {
                    $output->writeln("<error>Config file $configFile doesn't exist or isn't a file.</error>");
                }
            }
        }

        if (is_numeric($targetStep)) {
            (new ExerciseCleaner($config, $output))->cleanFiles($folders, $targetStep, $solution, $keepTags, $suffix);
        } else if (!$output->isQuiet()) {
            $output->writeln('<error>Step argument is missing or isn\'t numeric</error>');

            return 1;
        }

        return 0;
    }
}

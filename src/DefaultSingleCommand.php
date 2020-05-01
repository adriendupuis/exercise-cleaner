<?php

namespace ExerciseCleaner;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
        $suffix = $input->getOption('keep-orig') ? ".step$targetStep.".($solution?'solution':'exercise') : '';

        if (is_numeric($targetStep)) {
            (new ExerciseCleaner())->cleanFiles($folders, $targetStep, $solution, $keepTags, $suffix);
        } else {
            $output->writeln('<error>Step argument is missing or isn\'t numeric</error>');
            return 1;
        }

        return 0;
    }
}

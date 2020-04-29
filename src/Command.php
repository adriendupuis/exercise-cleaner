<?php

namespace ExerciseCleaner;

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Command\Command as SymfonyConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends SymfonyConsoleCommand
{
    protected static $defaultName = 'exercise:clean';

    protected function configure()
    {
        $this
            ->setAliases(['clean'])
            ->setDescription('Prepare files for an exercise or its solution at a given step')
            ->setHelp('TODO')
            ->addOption('keep-orig', 'o', InputOption::VALUE_NONE, 'Do not rewrite files but write a new one adding an extension which includes step number and if it\'s an exercise or a solution')
            ->addOption('keep-tags', 't', InputOption::VALUE_NONE, 'Do not remove start/stop tags')
            ->addOption('solution', 's',  InputOption::VALUE_NONE, 'Write exercise\'s solution instead of exercise itself')
            ->addArgument('step', InputArgument::OPTIONAL, 'Remove inside tags having this step and greater.', 1)
            ->addArgument('folders', InputArgument::IS_ARRAY, 'Search inside this folder(s).', ['app', 'src'])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $folders = $input->getArgument('folders');
        $targetStep = $input->getArgument('step');
        $solution = $input->getOption('solution');
        $keepTags = $input->getOption('keep-tags');
        $isSuffixed = $input->getOption('keep-orig');

        (new ExerciseCleaner())->cleanFiles($folders, $targetStep, $solution, $keepTags, $isSuffixed ? ".step$targetStep.".($solution?'solution':'exercise') : '');

        return 0;
    }
}

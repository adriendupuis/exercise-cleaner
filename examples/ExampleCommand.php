<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExampleCommand extends Command
{
    protected static $defaultName = 'app:example';

    protected function configure(): void
    {
        // TRAINING EXERCISE START STEP 1
        // TRAINING EXERCISE STEP PLACEHOLDER TODO: Set the description and the help
        $this
            ->setDescription('Just an example')
            // TRAINING EXERCISE START STEP 1 COMMENT
            ->setHelp('Step 1 feature')
            // TRAINING EXERCISE STOP STEP 1
            // TRAINING EXERCISE START STEP 2 PLACEHOLDER
            /* TODO:
                - Add argument(s)
                - Add option(s)
                - Update help
            */
            // TRAINING EXERCISE STOP STEP 2
            // TRAINING EXERCISE START STEP 2
            ->addArgument('argument', InputArgument::OPTIONAL, 'An optional argument')
            ->addOption('option', 'o', InputOption::VALUE_OPTIONAL, 'An option with an optional value')
            ->setHelp("Step 1 feature\nStep 2 feature")
            // TRAINING EXERCISE STOP STEP 2
        ;
        // TRAINING EXERCISE STOP STEP 1
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TRAINING EXERCISE START STEP 2
        $output->writeln('Example!');
        // TRAINING EXERCISE STOP STEP 2
    }
}
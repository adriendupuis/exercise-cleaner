<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExampleCommand extends Command
{
    protected static $defaultName = 'app:example';

    protected function configure()
    {
        // TRAINING EXERCISE START STEP 1
        // TRAINING EXERCISE STEP PLACEHOLDER TODO: Set the description and the help
        $this
            ->setDescription('Just an example')
            // TRAINING EXERCISE START STEP 1 COMMENT
            ->setHelp('Step 1 feature');
            // TRAINING EXERCISE STOP STEP 1
            // TRAINING EXERCISE START STEP 2
            // TRAINING EXERCISE STEP PLACEHOLDER TODO: Update the description
            ->setHelp("Step 1 feature\nStep 2 feature");
            // TRAINING EXERCISE STOP STEP 2
        // TRAINING EXERCISE STOP STEP 1
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TRAINING EXERCISE START STEP 2
        $output->writeln('Example!');
        // TRAINING EXERCISE STOP STEP 2
    }
}
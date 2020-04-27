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
        $this
            // TRAINING EXERCISE START STEP 1 COMMENT
            ->setDescription('Step 1')
            // TRAINING EXERCISE STOP STEP 1
            // TRAINING EXERCISE START STEP 2
            ->setDescription('Step 2+')
            // TRAINING EXERCISE STOP STEP 2
            ->setHelp('Just an example');
        // TRAINING EXERCISE STOP STEP 1
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TRAINING EXERCISE START STEP 2
        $output->writeln('Example!');
        // TRAINING EXERCISE STOP STEP 2
    }
}
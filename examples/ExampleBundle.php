<?php

// TRAINING EXERCISE START STEP 1.1
namespace ExampleBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ExampleBundle extends Bundle
{
    // TRAINING EXERCISE START STEP 1.2
    public function boot(): void
    {
    }
    // TRAINING EXERCISE STOP STEP 1.2

    // TRAINING EXERCISE START STEP 2
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }
    // TRAINING EXERCISE STOP STEP 2
}
// TRAINING EXERCISE STOP STEP 1.1

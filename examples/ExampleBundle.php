<?php

// TRAINING EXERCISE START STEP 1.1
// TRAINING EXERCISE STEP PLACEHOLDER TODO: Init
namespace ExampleBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ExampleBundle extends Bundle
{
    // TRAINING EXERCISE START STEP 1.2
    public function boot(): void
    {
        // TRAINING EXERCISE STEP PLACEHOLDER TODO: Boot
    }
    // TRAINING EXERCISE STOP STEP 1.2

    // TRAINING EXERCISE START STEP 2
    public function build(ContainerBuilder $container): void
    {
        // TRAINING EXERCISE STEP PLACEHOLDER TODO: Build
        parent::build($container);
    }
    // TRAINING EXERCISE STOP STEP 2
}
// TRAINING EXERCISE STOP STEP 1.1

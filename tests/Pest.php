<?php

use Intrfce\EnGuard\Engine\EnvironmentResolver;
use Intrfce\EnGuard\Engine\RuleEngine;
use Intrfce\EnGuard\Manifest\Manifest;
use Intrfce\EnGuard\Rules\RuleFactory;
use Intrfce\EnGuard\Tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * Build a manifest from a plain array, defaulting the environment set.
 */
function manifest(array $variables, array $environments = ['local', 'production']): Manifest
{
    return Manifest::fromArray([
        'environments' => $environments,
        'variables' => $variables,
    ]);
}

function engine(): RuleEngine
{
    return new RuleEngine(new RuleFactory, new EnvironmentResolver);
}

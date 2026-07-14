<?php

use SocialMind\EnGuard\Engine\EnvironmentResolver;
use SocialMind\EnGuard\Engine\RuleEngine;
use SocialMind\EnGuard\Manifest\Manifest;
use SocialMind\EnGuard\Rules\RuleFactory;
use SocialMind\EnGuard\Tests\TestCase;

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

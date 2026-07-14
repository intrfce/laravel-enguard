<?php

namespace SocialMind\EnGuard\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SocialMind\EnGuard\EnGuardServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [EnGuardServiceProvider::class];
    }
}

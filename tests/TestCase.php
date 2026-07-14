<?php

namespace Intrfce\EnGuard\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Intrfce\EnGuard\EnGuardServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [EnGuardServiceProvider::class];
    }
}

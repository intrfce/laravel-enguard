<?php

namespace SocialMind\EnGuard\Engine;

use Illuminate\Contracts\Foundation\Application;
use SocialMind\EnGuard\Manifest\Manifest;

/**
 * Selects the ruleset in force from Laravel's resolved environment (ADR-0003) —
 * the one deliberate read of resolved config, and only to pick a ruleset, never
 * to validate a value. Missing APP_ENV collapses to 'production' upstream.
 */
final class EnvironmentResolver
{
    public function resolve(Application $app): string
    {
        return (string) $app->environment();
    }

    public function isKnown(Manifest $manifest, string $environment): bool
    {
        return $manifest->knowsEnvironment($environment);
    }
}

<?php

namespace SocialMind\EnGuard\Rules;

/**
 * The variable must be absent in this environment.
 */
final class Forbidden implements Rule
{
    public function evaluate(string $key, ?string $value): ?string
    {
        return $value !== null
            ? "{$key} must not be set in this environment."
            : null;
    }
}

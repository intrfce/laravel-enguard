<?php

namespace SocialMind\EnGuard\Rules;

/**
 * The variable must be present. This is the "presence" rule suppressed when
 * config is cached (ADR-0002), since post-cache the raw env may legitimately
 * be absent.
 */
final class Required implements Rule
{
    public function evaluate(string $key, ?string $value): ?string
    {
        return $value === null
            ? "{$key} is required but not set."
            : null;
    }
}

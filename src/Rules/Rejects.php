<?php

namespace SocialMind\EnGuard\Rules;

/**
 * A present value must NOT match the regex — the "no production key locally"
 * rule (e.g. reject "^sk_live_" in local). ADR-0004.
 */
final class Rejects implements Rule
{
    public function __construct(private readonly string $pattern) {}

    public function evaluate(string $key, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $delimited = '#'.str_replace('#', '\#', $this->pattern).'#';

        return preg_match($delimited, $value) === 1
            ? "{$key} must not match /{$this->pattern}/ (got '{$value}')."
            : null;
    }
}

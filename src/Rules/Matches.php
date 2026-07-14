<?php

namespace Intrfce\EnGuard\Rules;

/**
 * A present value must match the regex. Patterns are authored without delimiters
 * (e.g. "^sk_live_") and evaluated as PCRE (ADR-0004).
 */
final class Matches implements Rule
{
    public function __construct(private readonly string $pattern) {}

    public function evaluate(string $key, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $delimited = '#'.str_replace('#', '\#', $this->pattern).'#';

        return preg_match($delimited, $value) === 1
            ? null
            : "{$key} must match /{$this->pattern}/ (got '{$value}').";
    }
}

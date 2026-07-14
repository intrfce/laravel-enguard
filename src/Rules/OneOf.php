<?php

namespace SocialMind\EnGuard\Rules;

/**
 * A present value must be one of the allowed values (non-secrets only).
 */
final class OneOf implements Rule
{
    /** @param list<string> $allowed */
    public function __construct(private readonly array $allowed) {}

    public function evaluate(string $key, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return in_array($value, $this->allowed, true)
            ? null
            : "{$key} must be one of [".implode(', ', $this->allowed)."] (got '{$value}').";
    }
}

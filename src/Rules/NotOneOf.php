<?php

namespace SocialMind\EnGuard\Rules;

/**
 * A present value must not be any of the disallowed values (non-secrets only).
 */
final class NotOneOf implements Rule
{
    /** @param list<string> $disallowed */
    public function __construct(private readonly array $disallowed) {}

    public function evaluate(string $key, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return in_array($value, $this->disallowed, true)
            ? "{$key} must not be any of [".implode(', ', $this->disallowed)."] (got '{$value}')."
            : null;
    }
}

<?php

namespace Intrfce\EnGuard\Rules;

/**
 * A present value must exactly equal the expected value (non-secrets only).
 */
final class Equals implements Rule
{
    public function __construct(private readonly string $expected) {}

    public function evaluate(string $key, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value !== $this->expected
            ? "{$key} must equal '{$this->expected}' (got '{$value}')."
            : null;
    }
}

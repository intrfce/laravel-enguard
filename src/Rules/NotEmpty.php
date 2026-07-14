<?php

namespace Intrfce\EnGuard\Rules;

/**
 * A present value must not be the empty string.
 */
final class NotEmpty implements Rule
{
    public function evaluate(string $key, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value === ''
            ? "{$key} must not be empty."
            : null;
    }
}

<?php

namespace Intrfce\EnGuard\Rules;

/**
 * A present value must parse as the declared type: bool, int, url, or email
 * (ADR-0004).
 */
final class TypeRule implements Rule
{
    private const BOOLS = ['true', 'false', '1', '0', 'on', 'off', 'yes', 'no'];

    public function __construct(private readonly string $type) {}

    public function evaluate(string $key, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $ok = match ($this->type) {
            'bool' => in_array(strtolower($value), self::BOOLS, true),
            'int' => preg_match('/^-?\d+$/', $value) === 1,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            default => true,
        };

        return $ok
            ? null
            : "{$key} must be a valid {$this->type} (got '{$value}').";
    }
}

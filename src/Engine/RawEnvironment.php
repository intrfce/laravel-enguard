<?php

namespace Intrfce\EnGuard\Engine;

/**
 * Reads the RAW process environment (ADR-0002) — never env()/config() — so
 * config-caching can't blind it. An explicit source array makes it testable.
 */
final class RawEnvironment
{
    /** @param array<string, mixed>|null $source */
    public function __construct(private readonly ?array $source = null) {}

    public function get(string $key): ?string
    {
        if ($this->source !== null) {
            return array_key_exists($key, $this->source)
                ? $this->normalize($this->source[$key])
                : null;
        }

        if (array_key_exists($key, $_ENV)) {
            return $this->normalize($_ENV[$key]);
        }

        if (array_key_exists($key, $_SERVER)) {
            return $this->normalize($_SERVER[$key]);
        }

        $value = getenv($key);

        return $value === false ? null : $value;
    }

    private function normalize(mixed $value): ?string
    {
        if ($value === null || $value === false) {
            return null;
        }

        return (string) $value;
    }
}

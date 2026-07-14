<?php

namespace Intrfce\EnGuard\Generation;

/**
 * Reads the KEYS present in an existing .env, so env:generate can compute what's
 * missing without ever touching existing values (ADR-0006).
 */
final class ExistingEnv
{
    /** @return list<string> */
    public static function keys(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $keys = [];

        foreach (preg_split('/\r\n|\r|\n/', (string) file_get_contents($path)) as $line) {
            if (preg_match('/^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=/', $line, $m) === 1) {
                $keys[] = $m[1];
            }
        }

        return array_values(array_unique($keys));
    }
}

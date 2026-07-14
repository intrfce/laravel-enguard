<?php

namespace Intrfce\EnGuard\Manifest;

use RuntimeException;

/**
 * Thrown when the manifest itself is unusable — missing, unparseable, or
 * structurally invalid. Distinct from an environment *violation*: a broken
 * manifest is a loud boot failure, not a "your env is wrong" report.
 */
final class ManifestException extends RuntimeException
{
    public static function missing(string $path): self
    {
        return new self("EnGuard: no manifest found at [{$path}]. Create env.json, or set enguard.required=false.");
    }

    public static function invalidJson(string $path, string $reason): self
    {
        return new self("EnGuard: manifest at [{$path}] is not valid JSON: {$reason}");
    }

    /**
     * @param  list<string>  $problems
     */
    public static function invalid(array $problems): self
    {
        return new self("EnGuard: manifest is invalid:\n  - ".implode("\n  - ", $problems));
    }
}

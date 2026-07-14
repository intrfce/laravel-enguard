<?php

namespace SocialMind\EnGuard\Guard;

use Illuminate\Support\Str;

/**
 * Decides whether the guard steps aside (ADR-0001): the global disable flag, or
 * an allowlisted console command so a broken env can still be repaired. Pure
 * logic — the caller supplies the console state, keeping this testable.
 */
final class GuardBypass
{
    /** @param list<string> $allowlist */
    public function __construct(
        private readonly array $allowlist,
        private readonly bool $disabled,
    ) {}

    public function shouldBypass(bool $runningInConsole, ?string $command): bool
    {
        if ($this->disabled) {
            return true;
        }

        if (! $runningInConsole) {
            return false;
        }

        // Bare `artisan` (no command → the list screen) is always allowed.
        if ($command === null) {
            return true;
        }

        foreach ($this->allowlist as $pattern) {
            if (Str::is($pattern, $command)) {
                return true;
            }
        }

        return false;
    }
}

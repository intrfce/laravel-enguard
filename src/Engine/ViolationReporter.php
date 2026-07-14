<?php

namespace Intrfce\EnGuard\Engine;

/**
 * Formats a ValidationResult into the self-contained report carried by the boot
 * exception (so it reaches logs even when the rendered response is generic) and
 * printed by env:check (ADR-0001).
 */
final class ViolationReporter
{
    public static function summary(ValidationResult $result, string $environment): string
    {
        $lines = array_map(
            static fn (Violation $v): string => '  • '.$v->message,
            $result->violations(),
        );

        return "EnGuard: environment '{$environment}' is invalid — boot halted.\n".implode("\n", $lines);
    }
}

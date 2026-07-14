<?php

namespace SocialMind\EnGuard\Guard;

use RuntimeException;
use SocialMind\EnGuard\Engine\ValidationResult;
use SocialMind\EnGuard\Engine\ViolationReporter;

/**
 * Thrown by the boot guard on a misconfigured environment (ADR-0001). Rendering
 * follows Laravel's normal error rules; the message carries the full violation
 * report so operators reading logs learn what failed even in production.
 */
final class EnvironmentInvalidException extends RuntimeException
{
    public function __construct(
        public readonly ValidationResult $result,
        string $environment,
    ) {
        parent::__construct(ViolationReporter::summary($result, $environment));
    }
}

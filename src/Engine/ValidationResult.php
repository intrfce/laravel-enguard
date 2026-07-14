<?php

namespace SocialMind\EnGuard\Engine;

/**
 * The collect-all outcome of a validation pass (ADR-0001): every violation, not
 * just the first.
 */
final class ValidationResult
{
    /** @param list<Violation> $violations */
    public function __construct(private array $violations = []) {}

    public function add(Violation $violation): void
    {
        $this->violations[] = $violation;
    }

    /** @return list<Violation> */
    public function violations(): array
    {
        return $this->violations;
    }

    public function passes(): bool
    {
        return $this->violations === [];
    }

    public function fails(): bool
    {
        return ! $this->passes();
    }
}

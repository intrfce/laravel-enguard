<?php

namespace SocialMind\EnGuard\Rules;

/**
 * A single constraint. Returns a human-readable violation message, or null when
 * satisfied. By convention, value-rules return null when $value is null (absence
 * is Required/Forbidden's concern, not theirs).
 */
interface Rule
{
    public function evaluate(string $key, ?string $value): ?string;
}

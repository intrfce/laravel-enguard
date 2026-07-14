<?php

namespace Intrfce\EnGuard\Engine;

final class Violation
{
    public function __construct(
        public readonly string $key,
        public readonly string $message,
    ) {}
}

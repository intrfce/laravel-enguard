<?php

namespace SocialMind\EnGuard\Manifest;

/**
 * One entry in the manifest: a variable, its metadata, and its per-environment
 * rulesets (ADR-0004, ADR-0005).
 */
final class VariableDefinition
{
    /**
     * @param  array<string, array<string, mixed>>  $rules  Rulesets keyed by 'default' and environment name.
     */
    public function __construct(
        public readonly string $key,
        public readonly ?string $description,
        public readonly bool $secret,
        public readonly array $rules,
    ) {}

    public static function fromArray(string $key, array $def): self
    {
        return new self(
            key: $key,
            description: $def['description'] ?? null,
            secret: (bool) ($def['secret'] ?? false),
            rules: $def['rules'] ?? [],
        );
    }

    /**
     * The effective ruleset for an environment: the `default` base shallow-merged
     * with the named environment, the named env winning per key (ADR-0005).
     *
     * @return array<string, mixed>
     */
    public function rulesFor(string $environment): array
    {
        return array_merge(
            $this->rules['default'] ?? [],
            $this->rules[$environment] ?? [],
        );
    }
}

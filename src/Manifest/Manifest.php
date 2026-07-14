<?php

namespace SocialMind\EnGuard\Manifest;

/**
 * Parsed env.json: the closed set of environments and the declared variables
 * (ADR-0003, ADR-0007).
 */
final class Manifest
{
    /**
     * @param  list<string>  $environments
     * @param  array<string, VariableDefinition>  $variables
     */
    public function __construct(
        public readonly array $environments,
        public readonly array $variables,
    ) {}

    public static function fromArray(array $data): self
    {
        $variables = [];

        foreach (($data['variables'] ?? []) as $key => $def) {
            $variables[$key] = VariableDefinition::fromArray($key, is_array($def) ? $def : []);
        }

        return new self(
            environments: array_values($data['environments'] ?? []),
            variables: $variables,
        );
    }

    public function knowsEnvironment(string $environment): bool
    {
        return in_array($environment, $this->environments, true);
    }
}

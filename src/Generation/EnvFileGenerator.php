<?php

namespace SocialMind\EnGuard\Generation;

use SocialMind\EnGuard\Manifest\Manifest;
use SocialMind\EnGuard\Manifest\VariableDefinition;

/**
 * Renders .env content from the manifest for a target environment (ADR-0006):
 * description as a # comment, secrets blank, non-secrets seeded from `equals`
 * then `value`.
 */
final class EnvFileGenerator
{
    /**
     * @param  list<string>|null  $onlyKeys  Restrict output to these keys (used by --generate-missing).
     */
    public function generate(Manifest $manifest, string $environment, ?array $onlyKeys = null): string
    {
        $blocks = [];

        foreach ($manifest->variables as $key => $definition) {
            if ($onlyKeys !== null && ! in_array($key, $onlyKeys, true)) {
                continue;
            }

            $lines = [];

            if ($definition->description !== null) {
                $lines[] = '# '.$definition->description;
            }

            $lines[] = $key.'='.$this->seed($definition, $environment);

            $blocks[] = implode("\n", $lines);
        }

        return implode("\n\n", $blocks)."\n";
    }

    private function seed(VariableDefinition $definition, string $environment): string
    {
        if ($definition->secret) {
            return '';
        }

        $ruleSet = $definition->rulesFor($environment);

        if (isset($ruleSet['equals'])) {
            return (string) $ruleSet['equals'];
        }

        if (isset($ruleSet['value'])) {
            return (string) $ruleSet['value'];
        }

        return '';
    }
}

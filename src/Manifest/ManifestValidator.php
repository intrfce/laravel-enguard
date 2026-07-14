<?php

namespace SocialMind\EnGuard\Manifest;

/**
 * The three cross-field checks JSON Schema structurally cannot express
 * (ADR-0005 boundary), plus regex-compilability. This is the runtime guarantee
 * that the schema only hints at in editors.
 */
final class ManifestValidator
{
    /** Rule keys that assert an exact value — illegal for secrets (ADR-0004). */
    private const VALUE_RULES = ['equals', 'oneOf', 'notOneOf', 'value'];

    private const KNOWN_RULES = [
        'required', 'forbidden', 'notEmpty', 'type',
        'matches', 'rejects', 'equals', 'oneOf', 'notOneOf', 'value',
    ];

    private const TYPES = ['bool', 'int', 'url', 'email'];

    /**
     * @throws ManifestException
     */
    public function validate(Manifest $manifest): void
    {
        $problems = [];

        if ($manifest->environments === []) {
            $problems[] = 'no environments are declared; at least one is required.';
        }

        foreach ($manifest->variables as $key => $def) {
            $this->checkVariable($key, $def, $manifest->environments, $problems);
        }

        if ($problems !== []) {
            throw ManifestException::invalid($problems);
        }
    }

    /**
     * @param  list<string>  $environments
     * @param  list<string>  $problems
     */
    private function checkVariable(string $key, VariableDefinition $def, array $environments, array &$problems): void
    {
        $legalRuleKeys = array_merge(['default'], $environments);

        foreach ($def->rules as $env => $ruleSet) {
            // (1) rules keys must be 'default' or a declared environment.
            if (! in_array($env, $legalRuleKeys, true)) {
                $problems[] = "[{$key}] has a ruleset for unknown environment '{$env}'.";

                continue;
            }

            if (! is_array($ruleSet)) {
                $problems[] = "[{$key}] ruleset '{$env}' must be an object.";

                continue;
            }

            foreach ($ruleSet as $rule => $value) {
                if (! in_array($rule, self::KNOWN_RULES, true)) {
                    $problems[] = "[{$key}] ruleset '{$env}' has unknown rule '{$rule}'.";
                }

                // (2) secrets may only be shaped, never value-matched.
                if ($def->secret && in_array($rule, self::VALUE_RULES, true)) {
                    $problems[] = "[{$key}] is secret, so rule '{$rule}' (in '{$env}') is illegal — secrets are validated by shape only.";
                }
            }

            $this->checkRegex($key, $env, $ruleSet, $problems);
            $this->checkType($key, $env, $ruleSet, $problems);
            $this->checkContradictions($key, $env, $ruleSet, $problems);
        }
    }

    /**
     * @param  array<string, mixed>  $ruleSet
     * @param  list<string>  $problems
     */
    private function checkRegex(string $key, string $env, array $ruleSet, array &$problems): void
    {
        foreach (['matches', 'rejects'] as $rule) {
            if (! isset($ruleSet[$rule])) {
                continue;
            }

            $pattern = '#'.str_replace('#', '\#', (string) $ruleSet[$rule]).'#';

            // @-suppression is ignored under PHPUnit's error handler, so scope
            // a no-op handler around the compilation probe.
            set_error_handler(static fn (): bool => true);
            $valid = preg_match($pattern, '') !== false;
            restore_error_handler();

            if (! $valid) {
                $problems[] = "[{$key}] ruleset '{$env}' has an invalid '{$rule}' regex: {$ruleSet[$rule]}";
            }
        }
    }

    /**
     * @param  array<string, mixed>  $ruleSet
     * @param  list<string>  $problems
     */
    private function checkType(string $key, string $env, array $ruleSet, array &$problems): void
    {
        if (isset($ruleSet['type']) && ! in_array($ruleSet['type'], self::TYPES, true)) {
            $problems[] = "[{$key}] ruleset '{$env}' has unknown type '{$ruleSet['type']}'.";
        }
    }

    /**
     * (3) no contradictory rules within a single ruleset.
     *
     * @param  array<string, mixed>  $ruleSet
     * @param  list<string>  $problems
     */
    private function checkContradictions(string $key, string $env, array $ruleSet, array &$problems): void
    {
        if (isset($ruleSet['equals'], $ruleSet['oneOf'])) {
            $problems[] = "[{$key}] ruleset '{$env}' combines contradictory rules 'equals' and 'oneOf'.";
        }

        if (($ruleSet['required'] ?? null) === true && ($ruleSet['forbidden'] ?? null) === true) {
            $problems[] = "[{$key}] ruleset '{$env}' is both required and forbidden.";
        }
    }
}

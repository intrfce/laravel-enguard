<?php

namespace SocialMind\EnGuard\Rules;

/**
 * Turns a merged ruleset (from VariableDefinition::rulesFor) into concrete Rule
 * objects. The `value` key is generation-only (ADR-0004) and produces no rule.
 */
final class RuleFactory
{
    /**
     * @param  array<string, mixed>  $ruleSet
     * @return list<Rule>
     */
    public function fromRuleSet(array $ruleSet): array
    {
        $rules = [];

        if (($ruleSet['required'] ?? false) === true) {
            $rules[] = new Required;
        }

        if (($ruleSet['forbidden'] ?? false) === true) {
            $rules[] = new Forbidden;
        }

        if (($ruleSet['notEmpty'] ?? false) === true) {
            $rules[] = new NotEmpty;
        }

        if (isset($ruleSet['equals'])) {
            $rules[] = new Equals((string) $ruleSet['equals']);
        }

        if (isset($ruleSet['oneOf'])) {
            $rules[] = new OneOf(array_map('strval', (array) $ruleSet['oneOf']));
        }

        if (isset($ruleSet['notOneOf'])) {
            $rules[] = new NotOneOf(array_map('strval', (array) $ruleSet['notOneOf']));
        }

        if (isset($ruleSet['matches'])) {
            $rules[] = new Matches((string) $ruleSet['matches']);
        }

        if (isset($ruleSet['rejects'])) {
            $rules[] = new Rejects((string) $ruleSet['rejects']);
        }

        if (isset($ruleSet['type'])) {
            $rules[] = new TypeRule((string) $ruleSet['type']);
        }

        return $rules;
    }
}

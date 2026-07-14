<?php

namespace SocialMind\EnGuard\Engine;

use SocialMind\EnGuard\Manifest\Manifest;
use SocialMind\EnGuard\Rules\Required;
use SocialMind\EnGuard\Rules\RuleFactory;

/**
 * The single rule engine shared by the Guard and env:check (ADR-0002). Validates
 * only declared variables (ADR-0007) against the raw environment, collecting all
 * violations (ADR-0001).
 */
final class RuleEngine
{
    public function __construct(
        private readonly RuleFactory $factory,
        private readonly EnvironmentResolver $resolver,
    ) {}

    /**
     * @param  bool  $soften  When true (config is cached), suppress the "required"
     *                        presence class, since post-cache the raw env may be
     *                        legitimately absent (ADR-0002).
     */
    public function validate(Manifest $manifest, string $environment, RawEnvironment $env, bool $soften = false): ValidationResult
    {
        $result = new ValidationResult;

        if (! $this->resolver->isKnown($manifest, $environment)) {
            $result->add(new Violation(
                'APP_ENV',
                "Unknown environment '{$environment}'. Declared environments: [".implode(', ', $manifest->environments).'].',
            ));

            return $result;
        }

        foreach ($manifest->variables as $key => $definition) {
            $ruleSet = $definition->rulesFor($environment);
            $value = $env->get($key);

            foreach ($this->factory->fromRuleSet($ruleSet) as $rule) {
                if ($soften && $rule instanceof Required) {
                    continue;
                }

                $message = $rule->evaluate($key, $value);

                if ($message !== null) {
                    $result->add(new Violation($key, $message));
                }
            }
        }

        return $result;
    }
}

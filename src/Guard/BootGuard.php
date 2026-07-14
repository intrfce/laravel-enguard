<?php

namespace Intrfce\EnGuard\Guard;

use Illuminate\Contracts\Foundation\Application;
use Intrfce\EnGuard\Engine\RawEnvironment;
use Intrfce\EnGuard\Engine\RuleEngine;
use Intrfce\EnGuard\Engine\ValidationResult;
use Intrfce\EnGuard\Engine\Violation;
use Intrfce\EnGuard\Manifest\ManifestLoader;
use Intrfce\EnGuard\Manifest\ManifestValidator;

/**
 * Orchestrates the boot-time check (ADR-0001, ADR-0002): bypass? → load & validate
 * manifest → resolve environment → run engine (softened when config is cached) →
 * throw on failure.
 */
final class BootGuard
{
    public function __construct(
        private readonly Application $app,
        private readonly ManifestLoader $loader,
        private readonly ManifestValidator $validator,
        private readonly RuleEngine $engine,
        private readonly GuardBypass $bypass,
        private readonly RawEnvironment $rawEnvironment,
        private readonly string $manifestPath,
        private readonly bool $manifestRequired,
    ) {}

    public function run(bool $runningInConsole, ?string $command): void
    {
        if ($this->bypass->shouldBypass($runningInConsole, $command)) {
            return;
        }

        $environment = (string) $this->app->environment();

        if (! $this->loader->exists($this->manifestPath)) {
            if ($this->manifestRequired) {
                $this->fail($environment, new Violation(
                    'env.json',
                    "No manifest found at [{$this->manifestPath}]. Create env.json, or set enguard.required=false.",
                ));
            }

            return;
        }

        // A malformed/invalid manifest throws ManifestException — a loud boot
        // failure, distinct from an environment violation.
        $manifest = $this->loader->load($this->manifestPath);
        $this->validator->validate($manifest);

        $result = $this->engine->validate(
            $manifest,
            $environment,
            $this->rawEnvironment,
            $this->app->configurationIsCached(),
        );

        if ($result->fails()) {
            throw new EnvironmentInvalidException($result, $environment);
        }
    }

    private function fail(string $environment, Violation $violation): never
    {
        $result = new ValidationResult;
        $result->add($violation);

        throw new EnvironmentInvalidException($result, $environment);
    }
}

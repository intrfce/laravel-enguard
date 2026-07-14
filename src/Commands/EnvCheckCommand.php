<?php

namespace SocialMind\EnGuard\Commands;

use Illuminate\Console\Command;
use SocialMind\EnGuard\Engine\RawEnvironment;
use SocialMind\EnGuard\Engine\RuleEngine;
use SocialMind\EnGuard\Manifest\ManifestException;
use SocialMind\EnGuard\Manifest\ManifestLoader;
use SocialMind\EnGuard\Manifest\ManifestValidator;

/**
 * Runs the same rule engine as the boot guard, on demand, exiting non-zero on
 * any violation — the CI/deploy gate (ADR-0002). Never softens: CI runs with the
 * real environment present and should catch everything.
 */
final class EnvCheckCommand extends Command
{
    protected $signature = 'env:check {--environment= : Environment to check (defaults to the current APP_ENV)}';

    protected $description = 'Validate the environment against env.json.';

    public function handle(
        ManifestLoader $loader,
        ManifestValidator $validator,
        RuleEngine $engine,
    ): int {
        $path = (string) config('enguard.manifest');

        try {
            $manifest = $loader->load($path);
            $validator->validate($manifest);
        } catch (ManifestException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $environment = (string) ($this->option('environment') ?: $this->laravel->environment());

        $result = $engine->validate($manifest, $environment, new RawEnvironment, soften: false);

        if ($result->passes()) {
            $this->info("EnGuard: environment '{$environment}' is valid.");

            return self::SUCCESS;
        }

        $this->error("EnGuard: environment '{$environment}' is INVALID:");

        foreach ($result->violations() as $violation) {
            $this->line('  • '.$violation->message);
        }

        return self::FAILURE;
    }
}

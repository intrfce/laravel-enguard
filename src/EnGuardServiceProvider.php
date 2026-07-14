<?php

namespace Intrfce\EnGuard;

use Illuminate\Support\ServiceProvider;
use Intrfce\EnGuard\Commands\EnvCheckCommand;
use Intrfce\EnGuard\Commands\EnvGenerateCommand;
use Intrfce\EnGuard\Engine\RawEnvironment;
use Intrfce\EnGuard\Engine\RuleEngine;
use Intrfce\EnGuard\Guard\BootGuard;
use Intrfce\EnGuard\Guard\GuardBypass;
use Intrfce\EnGuard\Manifest\ManifestLoader;
use Intrfce\EnGuard\Manifest\ManifestValidator;

final class EnGuardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/enguard.php', 'enguard');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/enguard.php' => config_path('enguard.php'),
            ], 'enguard-config');

            $this->publishes([
                __DIR__.'/../schema/env.schema.json' => base_path('env.schema.json'),
            ], 'enguard-schema');

            $this->commands([
                EnvCheckCommand::class,
                EnvGenerateCommand::class,
            ]);
        }

        // ADR-0001: the guard runs in boot() — raw env + app.env are loaded and
        // the exception handler is installed, yet nothing is handled/executed
        // until every provider has booted. Commands are registered above first,
        // so an allowlisted command survives even when the guard would trip.
        $this->makeGuard()->run(
            $this->app->runningInConsole(),
            $_SERVER['argv'][1] ?? null,
        );
    }

    private function makeGuard(): BootGuard
    {
        $config = $this->app['config']->get('enguard');

        return new BootGuard(
            app: $this->app,
            loader: $this->app->make(ManifestLoader::class),
            validator: $this->app->make(ManifestValidator::class),
            engine: $this->app->make(RuleEngine::class),
            bypass: new GuardBypass(
                (array) ($config['allowlist'] ?? []),
                (bool) ($config['disable'] ?? false),
            ),
            rawEnvironment: new RawEnvironment,
            manifestPath: (string) ($config['manifest'] ?? base_path('env.json')),
            manifestRequired: (bool) ($config['required'] ?? true),
        );
    }
}

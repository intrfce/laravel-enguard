<?php

namespace Intrfce\EnGuard\Commands;

use Illuminate\Console\Command;
use Intrfce\EnGuard\Generation\EnvFileGenerator;
use Intrfce\EnGuard\Generation\ExistingEnv;
use Intrfce\EnGuard\Manifest\ManifestException;
use Intrfce\EnGuard\Manifest\ManifestLoader;
use Intrfce\EnGuard\Manifest\ManifestValidator;

/**
 * Materialises a .env from env.json (ADR-0006). Never clobbers an existing .env;
 * reports missing keys and, with --generate-missing, appends only those.
 */
final class EnvGenerateCommand extends Command
{
    protected $signature = 'env:generate
        {--environment=local : Target environment whose rules/defaults drive generation}
        {--generate-missing : Append only the declared keys absent from an existing .env}';

    protected $description = 'Generate (or top up) a .env from env.json.';

    public function handle(
        ManifestLoader $loader,
        ManifestValidator $validator,
        EnvFileGenerator $generator,
    ): int {
        try {
            $manifest = $loader->load((string) config('enguard.manifest'));
            $validator->validate($manifest);
        } catch (ManifestException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $environment = (string) $this->option('environment');
        $envPath = $this->laravel->basePath('.env');

        if (! is_file($envPath)) {
            file_put_contents($envPath, $generator->generate($manifest, $environment));
            $this->info(".env created from env.json (environment: {$environment}).");

            return self::SUCCESS;
        }

        $existing = ExistingEnv::keys($envPath);
        $missing = array_values(array_diff(array_keys($manifest->variables), $existing));

        if ($missing === []) {
            $this->info('.env already contains every declared variable — nothing to do.');

            return self::SUCCESS;
        }

        $this->warn('.env exists and is missing declared variables:');

        foreach ($missing as $key) {
            $this->line("  - {$key}");
        }

        if (! $this->option('generate-missing') && ! $this->confirm('Append the missing variables?', true)) {
            $this->line('No changes made. Re-run with --generate-missing to append non-interactively.');

            return self::SUCCESS;
        }

        $append = "\n".$generator->generate($manifest, $environment, $missing);
        file_put_contents($envPath, $append, FILE_APPEND);

        $this->info('Appended '.count($missing).' missing variable(s) to .env.');

        return self::SUCCESS;
    }
}

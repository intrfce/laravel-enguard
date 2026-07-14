<?php

use SocialMind\EnGuard\Engine\EnvironmentResolver;
use SocialMind\EnGuard\Engine\RawEnvironment;
use SocialMind\EnGuard\Engine\RuleEngine;
use SocialMind\EnGuard\Guard\BootGuard;
use SocialMind\EnGuard\Guard\EnvironmentInvalidException;
use SocialMind\EnGuard\Guard\GuardBypass;
use SocialMind\EnGuard\Manifest\ManifestLoader;
use SocialMind\EnGuard\Manifest\ManifestValidator;
use SocialMind\EnGuard\Rules\RuleFactory;

/**
 * Testbench boots with APP_ENV=testing, so manifests here declare 'testing'.
 */
function writeManifest(string $json): string
{
    $path = tempnam(sys_get_temp_dir(), 'enguard').'.json';
    file_put_contents($path, $json);

    return $path;
}

function guard(string $manifestPath, RawEnvironment $raw, bool $required = true, bool $disabled = false): BootGuard
{
    return new BootGuard(
        app: app(),
        loader: new ManifestLoader,
        validator: new ManifestValidator,
        engine: new RuleEngine(new RuleFactory, new EnvironmentResolver),
        bypass: new GuardBypass([], $disabled),
        rawEnvironment: $raw,
        manifestPath: $manifestPath,
        manifestRequired: $required,
    );
}

it('throws when the environment is invalid', function () {
    $path = writeManifest('{"environments":["testing"],"variables":{"APP_KEY":{"rules":{"default":{"required":true}}}}}');

    guard($path, new RawEnvironment([]))->run(runningInConsole: false, command: null);
})->throws(EnvironmentInvalidException::class, 'APP_KEY is required');

it('passes when the environment is valid', function () {
    $path = writeManifest('{"environments":["testing"],"variables":{"APP_KEY":{"rules":{"default":{"required":true}}}}}');

    guard($path, new RawEnvironment(['APP_KEY' => 'base64:abc']))->run(runningInConsole: false, command: null);

    expect(true)->toBeTrue(); // reached here without throwing
});

it('does nothing when disabled, even with an invalid environment', function () {
    $path = writeManifest('{"environments":["testing"],"variables":{"APP_KEY":{"rules":{"default":{"required":true}}}}}');

    guard($path, new RawEnvironment([]), disabled: true)->run(runningInConsole: false, command: null);

    expect(true)->toBeTrue();
});

it('fails a missing manifest when required', function () {
    guard('/no/such/env.json', new RawEnvironment([]), required: true)->run(false, null);
})->throws(EnvironmentInvalidException::class, 'No manifest found');

it('ignores a missing manifest when not required', function () {
    guard('/no/such/env.json', new RawEnvironment([]), required: false)->run(false, null);

    expect(true)->toBeTrue();
});

<?php

beforeEach(function () {
    $this->manifestPath = tempnam(sys_get_temp_dir(), 'enguard').'.json';
});

afterEach(function () {
    @unlink($this->manifestPath);
    unset($_ENV['ENGUARD_TEST_VAR']);
});

it('env:check fails (exit 1) when a required variable is missing', function () {
    file_put_contents($this->manifestPath, '{"environments":["testing"],"variables":{"ENGUARD_TEST_VAR":{"rules":{"default":{"required":true}}}}}');
    config(['enguard.manifest' => $this->manifestPath]);

    $this->artisan('env:check')
        ->expectsOutputToContain('ENGUARD_TEST_VAR is required')
        ->assertExitCode(1);
});

it('env:check passes (exit 0) when the environment is valid', function () {
    file_put_contents($this->manifestPath, '{"environments":["testing"],"variables":{"ENGUARD_TEST_VAR":{"rules":{"default":{"required":true}}}}}');
    config(['enguard.manifest' => $this->manifestPath]);
    $_ENV['ENGUARD_TEST_VAR'] = 'present';

    $this->artisan('env:check')->assertExitCode(0);
});

it('env:check reports a malformed manifest instead of throwing', function () {
    file_put_contents($this->manifestPath, '{ not json');
    config(['enguard.manifest' => $this->manifestPath]);

    $this->artisan('env:check')
        ->expectsOutputToContain('not valid JSON')
        ->assertExitCode(1);
});

it('env:generate creates a .env from the manifest when none exists', function () {
    $base = sys_get_temp_dir().'/enguard-gen-'.uniqid();
    mkdir($base);
    app()->setBasePath($base);

    file_put_contents($this->manifestPath, '{"environments":["local"],"variables":{"APP_URL":{"description":"Base URL","rules":{"local":{"value":"http://localhost"}}},"SECRET_KEY":{"secret":true,"rules":{"default":{"required":true}}}}}');
    config(['enguard.manifest' => $this->manifestPath]);

    $this->artisan('env:generate --environment=local')->assertExitCode(0);

    $written = file_get_contents($base.'/.env');
    expect($written)
        ->toContain('# Base URL')
        ->toContain('APP_URL=http://localhost')
        ->toContain("SECRET_KEY=\n");

    @unlink($base.'/.env');
    @rmdir($base);
});

it('env:generate appends only missing keys with --generate-missing', function () {
    $base = sys_get_temp_dir().'/enguard-gen-'.uniqid();
    mkdir($base);
    app()->setBasePath($base);
    file_put_contents($base.'/.env', "APP_URL=http://existing\n");

    file_put_contents($this->manifestPath, '{"environments":["local"],"variables":{"APP_URL":{"rules":{"local":{"value":"http://localhost"}}},"NEW_KEY":{"rules":{"local":{"value":"seeded"}}}}}');
    config(['enguard.manifest' => $this->manifestPath]);

    $this->artisan('env:generate --environment=local --generate-missing')->assertExitCode(0);

    $written = file_get_contents($base.'/.env');
    expect($written)
        ->toContain('APP_URL=http://existing') // untouched
        ->toContain('NEW_KEY=seeded');         // appended

    @unlink($base.'/.env');
    @rmdir($base);
});

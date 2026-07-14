<?php

use SocialMind\EnGuard\Generation\EnvFileGenerator;

it('seeds non-secrets, blanks secrets, and writes descriptions as comments', function () {
    $manifest = manifest([
        'APP_URL' => [
            'description' => 'Base URL',
            'rules' => ['default' => ['type' => 'url'], 'local' => ['value' => 'http://localhost']],
        ],
        'APP_DEBUG' => ['rules' => ['local' => ['equals' => 'true']]],
        'STRIPE_SECRET' => ['secret' => true, 'rules' => ['default' => ['required' => true]]],
    ]);

    $output = (new EnvFileGenerator)->generate($manifest, 'local');

    expect($output)->toContain('# Base URL')
        ->and($output)->toContain('APP_URL=http://localhost')
        ->and($output)->toContain('APP_DEBUG=true')
        ->and($output)->toContain("STRIPE_SECRET=\n");
});

it('restricts output to the requested keys', function () {
    $manifest = manifest([
        'A' => ['rules' => ['local' => ['value' => '1']]],
        'B' => ['rules' => ['local' => ['value' => '2']]],
    ]);

    $output = (new EnvFileGenerator)->generate($manifest, 'local', onlyKeys: ['B']);

    expect($output)->toContain('B=2')
        ->and($output)->not->toContain('A=1');
});

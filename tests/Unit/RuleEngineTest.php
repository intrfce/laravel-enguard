<?php

use SocialMind\EnGuard\Engine\RawEnvironment;

it('catches a live Stripe key in local (the headline use case)', function () {
    $manifest = manifest([
        'STRIPE_SECRET' => [
            'secret' => true,
            'rules' => [
                'default' => ['required' => true],
                'local' => ['matches' => '^sk_test_', 'rejects' => '^sk_live_'],
                'production' => ['matches' => '^sk_live_'],
            ],
        ],
    ]);

    $result = engine()->validate($manifest, 'local', new RawEnvironment(['STRIPE_SECRET' => 'sk_live_abc123']));

    $messages = implode("\n", array_map(fn ($v) => $v->message, $result->violations()));

    expect($result->fails())->toBeTrue()
        ->and($messages)->toContain('must not match /^sk_live_/');
});

it('accepts a test key in local', function () {
    $manifest = manifest([
        'STRIPE_SECRET' => [
            'secret' => true,
            'rules' => [
                'default' => ['required' => true],
                'local' => ['matches' => '^sk_test_', 'rejects' => '^sk_live_'],
            ],
        ],
    ]);

    $result = engine()->validate($manifest, 'local', new RawEnvironment(['STRIPE_SECRET' => 'sk_test_abc123']));

    expect($result->passes())->toBeTrue();
});

it('fails when a required variable is missing', function () {
    $manifest = manifest(['APP_KEY' => ['rules' => ['default' => ['required' => true]]]]);

    $result = engine()->validate($manifest, 'local', new RawEnvironment([]));

    expect($result->fails())->toBeTrue()
        ->and($result->violations()[0]->message)->toContain('required');
});

it('softens the required check when config is cached', function () {
    $manifest = manifest(['APP_KEY' => ['rules' => ['default' => ['required' => true]]]]);

    $result = engine()->validate($manifest, 'local', new RawEnvironment([]), soften: true);

    expect($result->passes())->toBeTrue();
});

it('still catches present-but-wrong values when softened', function () {
    $manifest = manifest([
        'APP_DEBUG' => ['rules' => ['default' => ['required' => true], 'production' => ['equals' => 'false']]],
    ]);

    $result = engine()->validate($manifest, 'production', new RawEnvironment(['APP_DEBUG' => 'true']), soften: true);

    expect($result->fails())->toBeTrue()
        ->and($result->violations()[0]->message)->toContain("must equal 'false'");
});

it('treats an unknown environment as a violation', function () {
    $manifest = manifest([], ['local', 'production']);

    $result = engine()->validate($manifest, 'staging', new RawEnvironment([]));

    expect($result->fails())->toBeTrue()
        ->and($result->violations()[0]->message)->toContain("Unknown environment 'staging'");
});

it('ignores undeclared variables (declared-only scope)', function () {
    $manifest = manifest([]);

    $result = engine()->validate($manifest, 'local', new RawEnvironment(['SOMETHING_UNDECLARED' => 'value']));

    expect($result->passes())->toBeTrue();
});

it('collects all violations, not just the first', function () {
    $manifest = manifest([
        'A' => ['rules' => ['default' => ['required' => true]]],
        'B' => ['rules' => ['default' => ['required' => true]]],
    ]);

    $result = engine()->validate($manifest, 'local', new RawEnvironment([]));

    expect($result->violations())->toHaveCount(2);
});

it('applies base+override so a rule can be turned off per environment', function () {
    $manifest = manifest([
        'OPTIONAL_LOCALLY' => [
            'rules' => [
                'default' => ['required' => true],
                'local' => ['required' => false],
            ],
        ],
    ]);

    expect(engine()->validate($manifest, 'local', new RawEnvironment([]))->passes())->toBeTrue()
        ->and(engine()->validate($manifest, 'production', new RawEnvironment([]))->fails())->toBeTrue();
});

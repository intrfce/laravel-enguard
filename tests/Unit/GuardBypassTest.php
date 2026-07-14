<?php

use Intrfce\EnGuard\Guard\GuardBypass;

it('bypasses everything when disabled', function () {
    $bypass = new GuardBypass([], disabled: true);

    expect($bypass->shouldBypass(false, null))->toBeTrue()
        ->and($bypass->shouldBypass(true, 'migrate'))->toBeTrue();
});

it('never bypasses a non-console entry point', function () {
    $bypass = new GuardBypass(['env:generate'], disabled: false);

    expect($bypass->shouldBypass(false, null))->toBeFalse();
});

it('bypasses allowlisted console commands only', function () {
    $bypass = new GuardBypass(['env:generate', 'env:check'], disabled: false);

    expect($bypass->shouldBypass(true, 'env:generate'))->toBeTrue()
        ->and($bypass->shouldBypass(true, 'migrate'))->toBeFalse()
        ->and($bypass->shouldBypass(true, 'config:cache'))->toBeFalse();
});

it('allows bare artisan (no command)', function () {
    expect((new GuardBypass([], false))->shouldBypass(true, null))->toBeTrue();
});

it('supports glob patterns in the allowlist', function () {
    expect((new GuardBypass(['queue:*'], false))->shouldBypass(true, 'queue:restart'))->toBeTrue();
});

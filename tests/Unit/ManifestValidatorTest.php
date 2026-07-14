<?php

use Intrfce\EnGuard\Manifest\ManifestException;
use Intrfce\EnGuard\Manifest\ManifestValidator;

function validate(array $variables, array $environments = ['local', 'production']): void
{
    (new ManifestValidator)->validate(manifest($variables, $environments));
}

it('accepts a well-formed manifest', function () {
    validate([
        'STRIPE_SECRET' => [
            'secret' => true,
            'rules' => ['default' => ['required' => true], 'local' => ['matches' => '^sk_test_']],
        ],
        'APP_DEBUG' => ['rules' => ['production' => ['equals' => 'false']]],
    ]);
})->throwsNoExceptions();

it('rejects value rules on a secret', function () {
    validate(['S' => ['secret' => true, 'rules' => ['default' => ['equals' => 'x']]]]);
})->throws(ManifestException::class, 'secrets are validated by shape only');

it('rejects a ruleset for an undeclared environment', function () {
    validate(['A' => ['rules' => ['staging' => ['required' => true]]]]);
})->throws(ManifestException::class, "unknown environment 'staging'");

it('rejects required + forbidden together', function () {
    validate(['A' => ['rules' => ['default' => ['required' => true, 'forbidden' => true]]]]);
})->throws(ManifestException::class, 'both required and forbidden');

it('rejects equals + oneOf together', function () {
    validate(['A' => ['rules' => ['default' => ['equals' => 'x', 'oneOf' => ['x', 'y']]]]]);
})->throws(ManifestException::class, 'contradictory');

it('rejects an invalid regex', function () {
    validate(['A' => ['rules' => ['default' => ['matches' => '([unclosed']]]]);
})->throws(ManifestException::class, 'invalid');

it('rejects an unknown rule name', function () {
    validate(['A' => ['rules' => ['default' => ['requird' => true]]]]);
})->throws(ManifestException::class, "unknown rule 'requird'");

it('rejects an unknown type', function () {
    validate(['A' => ['rules' => ['default' => ['type' => 'float']]]]);
})->throws(ManifestException::class, "unknown type 'float'");

it('rejects an empty environment set', function () {
    validate(['A' => ['rules' => []]], []);
})->throws(ManifestException::class, 'no environments');

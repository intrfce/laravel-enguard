<?php

use SocialMind\EnGuard\Manifest\VariableDefinition;

it('merges default under the named environment, named winning', function () {
    $def = VariableDefinition::fromArray('X', [
        'rules' => [
            'default' => ['required' => true, 'type' => 'url'],
            'local' => ['type' => 'bool'],
        ],
    ]);

    expect($def->rulesFor('local'))->toBe(['required' => true, 'type' => 'bool'])
        ->and($def->rulesFor('production'))->toBe(['required' => true, 'type' => 'url']);
});

it('lets a named environment turn a default rule off explicitly', function () {
    $def = VariableDefinition::fromArray('X', [
        'rules' => ['default' => ['required' => true], 'local' => ['required' => false]],
    ]);

    expect($def->rulesFor('local'))->toBe(['required' => false]);
});

it('reads metadata', function () {
    $def = VariableDefinition::fromArray('X', ['description' => 'hi', 'secret' => true, 'rules' => []]);

    expect($def->description)->toBe('hi')
        ->and($def->secret)->toBeTrue()
        ->and($def->key)->toBe('X');
});

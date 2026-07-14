<?php

use SocialMind\EnGuard\Rules\Equals;
use SocialMind\EnGuard\Rules\Forbidden;
use SocialMind\EnGuard\Rules\Matches;
use SocialMind\EnGuard\Rules\NotEmpty;
use SocialMind\EnGuard\Rules\NotOneOf;
use SocialMind\EnGuard\Rules\OneOf;
use SocialMind\EnGuard\Rules\Rejects;
use SocialMind\EnGuard\Rules\Required;
use SocialMind\EnGuard\Rules\TypeRule;

it('Required fires only on absence', function () {
    expect((new Required)->evaluate('K', null))->not->toBeNull()
        ->and((new Required)->evaluate('K', ''))->toBeNull()
        ->and((new Required)->evaluate('K', 'x'))->toBeNull();
});

it('Forbidden fires only on presence', function () {
    expect((new Forbidden)->evaluate('K', 'x'))->not->toBeNull()
        ->and((new Forbidden)->evaluate('K', null))->toBeNull();
});

it('NotEmpty fires on empty string but skips absence', function () {
    expect((new NotEmpty)->evaluate('K', ''))->not->toBeNull()
        ->and((new NotEmpty)->evaluate('K', null))->toBeNull()
        ->and((new NotEmpty)->evaluate('K', 'x'))->toBeNull();
});

it('Equals compares exact value', function () {
    expect((new Equals('yes'))->evaluate('K', 'no'))->not->toBeNull()
        ->and((new Equals('yes'))->evaluate('K', 'yes'))->toBeNull()
        ->and((new Equals('yes'))->evaluate('K', null))->toBeNull();
});

it('OneOf / NotOneOf enforce membership', function () {
    expect((new OneOf(['a', 'b']))->evaluate('K', 'c'))->not->toBeNull()
        ->and((new OneOf(['a', 'b']))->evaluate('K', 'a'))->toBeNull()
        ->and((new NotOneOf(['a']))->evaluate('K', 'a'))->not->toBeNull()
        ->and((new NotOneOf(['a']))->evaluate('K', 'b'))->toBeNull();
});

it('Matches / Rejects apply undelimited PCRE', function () {
    expect((new Matches('^sk_test_'))->evaluate('K', 'sk_live_1'))->not->toBeNull()
        ->and((new Matches('^sk_test_'))->evaluate('K', 'sk_test_1'))->toBeNull()
        ->and((new Rejects('^sk_live_'))->evaluate('K', 'sk_live_1'))->not->toBeNull()
        ->and((new Rejects('^sk_live_'))->evaluate('K', 'sk_test_1'))->toBeNull();
});

it('TypeRule validates each supported type', function () {
    expect((new TypeRule('bool'))->evaluate('K', 'maybe'))->not->toBeNull()
        ->and((new TypeRule('bool'))->evaluate('K', 'true'))->toBeNull()
        ->and((new TypeRule('int'))->evaluate('K', '12a'))->not->toBeNull()
        ->and((new TypeRule('int'))->evaluate('K', '-12'))->toBeNull()
        ->and((new TypeRule('url'))->evaluate('K', 'not a url'))->not->toBeNull()
        ->and((new TypeRule('url'))->evaluate('K', 'https://x.test'))->toBeNull()
        ->and((new TypeRule('email'))->evaluate('K', 'nope'))->not->toBeNull()
        ->and((new TypeRule('email'))->evaluate('K', 'a@b.test'))->toBeNull();
});

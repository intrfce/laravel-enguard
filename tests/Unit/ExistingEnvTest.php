<?php

use Intrfce\EnGuard\Generation\ExistingEnv;

it('extracts keys, ignoring comments and blanks and honouring export', function () {
    $path = tempnam(sys_get_temp_dir(), 'enguard');
    file_put_contents($path, <<<'ENV'
    # a comment
    APP_NAME=Example

    export DB_HOST=localhost
      SPACED_KEY = value
    ENV);

    expect(ExistingEnv::keys($path))->toEqualCanonicalizing(['APP_NAME', 'DB_HOST', 'SPACED_KEY']);

    unlink($path);
});

it('returns an empty list for a missing file', function () {
    expect(ExistingEnv::keys('/no/such/file'))->toBe([]);
});

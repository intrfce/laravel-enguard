<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Manifest location
    |--------------------------------------------------------------------------
    | The single source of truth (ADR-0005). Strict JSON, editor-validated by
    | the published schema.
    */
    'manifest' => base_path('env.json'),

    /*
    |--------------------------------------------------------------------------
    | Manifest required?
    |--------------------------------------------------------------------------
    | When true, a missing env.json is itself a boot failure — the manifest is
    | meant to be the source of truth (ADR-0007). Set false to make EnGuard a
    | no-op when no manifest is present.
    */
    'required' => true,

    /*
    |--------------------------------------------------------------------------
    | Global disable
    |--------------------------------------------------------------------------
    | The blunt escape hatch (ADR-0001).
    */
    'disable' => env('ENGUARD_DISABLE', false),

    /*
    |--------------------------------------------------------------------------
    | Command allowlist
    |--------------------------------------------------------------------------
    | Console commands that bypass the boot guard so a broken environment can
    | still be inspected/repaired (ADR-0001). Glob patterns via Str::is().
    | Note: `config:cache` is deliberately absent — running it IS the production
    | gate (ADR-0002).
    */
    'allowlist' => [
        'env:generate',
        'env:check',
        'key:generate',
        'config:clear',
        'cache:clear',
        'package:discover',
        'vendor:publish',
        'list',
        'help',
    ],

];

# EnGuard

Stop a Laravel app from booting in a misconfigured environment — from a single
`env.json` source of truth. EnGuard replaces `.env.example`, halts boot when the
environment is wrong or dangerous, and generates `.env` on checkout.

> **What it's for:** preventing *mistakes* — a live Stripe key in local, `APP_DEBUG=true`
> in production, a required variable nobody set. It is **not** a security boundary
> against someone who intentionally controls the environment.

## Install

```bash
composer require intrfce/laravel-enguard
php artisan vendor:publish --tag=enguard-config   # optional
php artisan vendor:publish --tag=enguard-schema   # optional: env.schema.json for editor validation
```

## The manifest — `env.json`

```jsonc
{
  "$schema": "./env.schema.json",
  "environments": ["local", "staging", "production"],
  "variables": {
    "APP_DEBUG": {
      "description": "Verbose error output. Off in production.",
      "rules": {
        "default":    { "required": true, "type": "bool" },
        "production": { "equals": "false" }
      }
    },
    "STRIPE_SECRET": {
      "description": "Stripe secret key. Test locally, live in production.",
      "secret": true,
      "rules": {
        "default":    { "required": true },
        "local":      { "matches": "^sk_test_", "rejects": "^sk_live_" },
        "production": { "matches": "^sk_live_" }
      }
    }
  }
}
```

- **`environments`** — the closed set of legal environments. An `APP_ENV` outside
  it is a violation. A missing `APP_ENV` is treated as `production` (Laravel's default).
- **`secret: true`** — validated by *shape* only (`equals`/`oneOf`/`value` are illegal);
  never written with a value by `env:generate`.
- **Rules merge** — a named environment's ruleset merges *over* `default`, key by key.
  Set a rule explicitly (e.g. `"required": false`) to override the base off.
- EnGuard validates **only declared variables**; anything undeclared is ignored.

### Rule vocabulary

| Rule | Meaning |
|------|---------|
| `required` / `forbidden` | must be present / must be absent |
| `notEmpty` | present value must not be `""` |
| `equals` | exact value (non-secret) |
| `oneOf` / `notOneOf` | membership (non-secret) |
| `matches` / `rejects` | value must / must not match a PCRE regex (no delimiters) |
| `type` | `bool`, `int`, `url`, or `email` |
| `value` | generation default only — seeds `env:generate`, not enforced |

## The guard

EnGuard runs during boot and **halts every entry point** (HTTP, queue, scheduler,
artisan) if the environment is invalid, throwing `EnvironmentInvalidException` with
a full report of every violation. Rendering follows your normal `APP_DEBUG` rules;
the report is always in the message, so it reaches your logs in production.

- **Reads the raw environment** (`$_ENV`/`getenv`), so `config:cache` can't blind it.
- Running `php artisan config:cache` naturally runs the full validation — it's the
  production gate.
- When config *is* cached, the "required/missing" checks soften (post-cache the raw
  env may legitimately be absent) while "present-but-wrong" checks still fire.

### Escape hatch

Allowlisted commands bypass the guard so a broken environment can still be repaired
(`env:generate`, `env:check`, `key:generate`, …). Configure in `config/enguard.php`,
or set `ENGUARD_DISABLE=1` to turn the guard off entirely.

## Commands

```bash
# Validate on demand (CI/deploy gate). Exits non-zero on any violation.
php artisan env:check --environment=production

# Create .env from env.json (secrets left blank, descriptions as comments).
php artisan env:generate --environment=local

# Never clobbers an existing .env — appends only the declared keys it's missing.
php artisan env:generate --generate-missing
```

## Configuration

See `config/enguard.php`: `manifest` path, `required` (is a missing manifest a boot
failure?), `disable`, and the command `allowlist`.

## Design

The full design rationale lives in [`docs/adr/`](docs/adr/) (eight ADRs), with a
[glossary](docs/glossary.md) and [implementation plan](docs/implementation-plan.md).

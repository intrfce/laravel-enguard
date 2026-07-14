# EnGuard — Implementation Plan

Derived from ADR-0001 … ADR-0008. Every component below traces to a decision; the ADR is cited so the *why* is one hop away.

## 1. Package layout

```
laravel-enguard/
├── composer.json
├── schema/env.schema.json              # exists — published for editors (ADR-0005)
├── config/enguard.php                  # manifest path, allowlist, disable flag
├── src/
│   ├── EnGuardServiceProvider.php       # wiring + the boot guard (ADR-0001)
│   ├── Manifest/
│   │   ├── ManifestLoader.php           # locate + json_decode env.json
│   │   ├── ManifestValidator.php        # the 3 cross-field checks (ADR-0005 boundary)
│   │   ├── Manifest.php                 # value object: environments[] + variables{}
│   │   ├── VariableDefinition.php       # key, description, secret, rules{}
│   │   └── EffectiveRuleSet.php         # result of base+override merge (ADR-0005)
│   ├── Rules/
│   │   ├── Rule.php  (interface)        # evaluate(key,value,env): ?Violation
│   │   ├── Required.php NotEmpty.php Equals.php OneOf.php NotOneOf.php
│   │   ├── Matches.php Rejects.php TypeRule.php
│   │   └── RuleFactory.php              # merged-ruleset array -> Rule[]
│   ├── Engine/
│   │   ├── RuleEngine.php               # declared vars x raw env -> ValidationResult
│   │   ├── EnvironmentResolver.php      # app.env + closed-set check (ADR-0003)
│   │   ├── RawEnvironment.php           # $_ENV/getenv reader (ADR-0002)
│   │   ├── Violation.php  ValidationResult.php
│   │   └── ViolationReporter.php        # shared formatter: exception msg + env:check
│   ├── Guard/
│   │   ├── BootGuard.php                # orchestrates resolve->load->validate->throw
│   │   ├── GuardBypass.php              # allowlist + ENGUARD_DISABLE + cached-soften
│   │   └── EnvironmentInvalidException.php
│   ├── Generation/
│   │   ├── EnvFileGenerator.php         # manifest+env -> .env content (ADR-0006)
│   │   └── ExistingEnv.php              # parse current .env keys (reuse Dotenv)
│   └── Commands/
│       ├── EnvCheckCommand.php          # env:check   (ADR-0002)
│       └── EnvGenerateCommand.php       # env:generate (ADR-0006)
└── tests/                               # Pest + Orchestra Testbench
```

## 2. Boot & timing — closes ADR-0001's open note

Laravel's bootstrap order is: **LoadEnvironmentVariables → LoadConfiguration → HandleExceptions → RegisterProviders (`register()`) → BootProviders (`boot()`)**.

**Decision: run the guard in the provider's `boot()`.** By then the raw environment and `app.env` are loaded *and* the framework exception handler is installed, so a thrown `EnvironmentInvalidException` renders through normal error handling (ADR-0001). It's still early enough to "stop any entry point" because no request is handled and no command is executed until *after* every provider boots. `register()` was considered and rejected: throwing there risks pre-empting the exception handler.

### `config:cache` needs no special hook (realises ADR-0002 simply)
`php artisan config:cache` boots the framework with the raw env **present**, so the guard fires naturally during the build — *provided `config:cache` is NOT on the allowlist*. That absence IS the production gate. No bespoke hook required.

### Runtime soften when cached
`BootGuard` checks `app()->configurationIsCached()`. When cached, Laravel skips loading `.env`, so `.env`-sourced vars are legitimately absent. In that state the guard runs **only the "present-but-wrong" checks** (`equals`/`oneOf`/`notOneOf`/`matches`/`rejects`/`type` on values that exist) and **suppresses the "missing/required" class** — preventing the self-inflicted outage from ADR-0002.

## 3. GuardBypass (allowlist) — ADR-0001

Order of checks, first hit wins → skip guard:
1. `config('enguard.disable')` (`ENGUARD_DISABLE=1`).
2. Not running the guard at all if there's no manifest file **and** manifest is optional (config flag) — otherwise "no manifest" is itself a boot failure.
3. Console + first `$_SERVER['argv']` token matches an allowlisted command (glob-aware).

Default allowlist (config, user-extendable):
`env:generate`, `env:check`, `key:generate`, `config:clear`, `cache:clear`, `package:discover`, `vendor:publish`, and bare `artisan`/`list`.
**Deliberately excluded:** `config:cache` (the gate), `migrate`, `queue:work`, `serve`.

> Caveat to document: argv-token matching is a pragmatic best-effort (the command object isn't resolved at provider-boot). Good enough for a mistake-prevention tool (ADR-0003); not tamper-proof, which is a non-goal.

## 4. Manifest pipeline — ADR-0004/0005/0007

- **ManifestLoader**: resolve path (`config('enguard.manifest')`, default `base_path('env.json')`) → read → `json_decode`. A JSON parse error throws a clear `ManifestException` (loud boot failure; `env:check` reports it rather than throwing).
- **ManifestValidator** owns the three checks JSON Schema can't (ADR-0005 boundary): (1) every `rules` key is `default` or a declared environment; (2) `secret:true` ⇒ no `equals`/`oneOf`/`notOneOf`/`value`; (3) no contradictory value rules in one ruleset; plus regex-compilability of `matches`/`rejects`. Structural checks are hand-rolled in PHP — **no runtime JSON-Schema dependency** (the schema is for editors only).
- **EffectiveRuleSet** performs the base+override merge (ADR-0005): `default` shallow-merged with the current env's ruleset, named env winning per key; omission inherits, explicit value wins (so `required:false` can turn a `default` requirement off).

## 5. Rule engine — ADR-0004/0007

For each **declared** variable (undeclared ignored, ADR-0007):
1. Build `EffectiveRuleSet` for the resolved environment.
2. Read raw value via `RawEnvironment` (`$_ENV[$k] ?? getenv($k)`, normalising `false`→null).
3. Run each `Rule`; collect `Violation`s (never fail-fast — ADR-0001 collect-all).

Rule semantics: `Required` (null → violation, suppressed when cached), `NotEmpty` (`''`→violation), `Equals`/`OneOf`/`NotOneOf` (string compare), `Matches`/`Rejects` (`preg_match`, **PCRE** — document the flavour vs the schema's ECMA hint), `TypeRule` (`bool`∈accepted truthy/falsy set, `int` via `ctype_digit`/sign, `url`/`email` via `filter_var`).

**EnvironmentResolver**: `app()->environment()`; if not in the manifest's closed `environments` set → an "unknown environment" violation (ADR-0003). Missing `APP_ENV` already collapses to `production` upstream.

## 6. `env:generate` — ADR-0006

- Args: `--environment=` (default `local`; `--env` is reserved by Laravel), `--generate-missing`.
- **`.env` absent** → write full file in manifest order: `# {description}` then `KEY={seed}` where seed = `''` for secrets, else `equals` value ?? `value` default ?? `''`.
- **`.env` present** → never rewrite existing lines. Parse existing keys (reuse `Dotenv\Dotenv::parse` for fidelity: quoting, `export`, comments). Compute missing declared keys. With `--generate-missing` → **append** only those (with comment + seed). Without it → print the missing set and prompt to append (interactive), or instruct to re-run with the flag.
- Secrets always written blank regardless of path.

## 7. `env:check` — ADR-0002

Same `RuleEngine`, non-halting. `--environment=` (default current). Loads + validates manifest, resolves env, runs engine over the raw env, prints the shared `ViolationReporter` output, exits non-zero on any violation or manifest error. **Does not soften** — CI runs with the real env present and should catch everything. This is the deploy gate that complements the `config:cache` boot gate.

## 8. Config file (`config/enguard.php`)

```php
return [
    'manifest'  => base_path('env.json'),
    'required'  => true,           // is a missing manifest itself a boot failure?
    'disable'   => env('ENGUARD_DISABLE', false),
    'allowlist' => [ /* defaults from §3, merged with user additions */ ],
];
```

## 9. Testing (Pest + Orchestra Testbench)

- **Unit**: each `Rule`; `EffectiveRuleSet` merge (incl. override-off); `EnvironmentResolver` (missing/unknown/known); `ManifestValidator` positive + the 3 negative cross-field cases; `EnvFileGenerator` (absent/present/missing-keys/secrets-blank) against tmp files.
- **Feature (Testbench)**: boot an app with a fixture `env.json` + a set `$_ENV`, assert boot throws with the right violations; assert an allowlisted command boots clean; assert cached-config softening drops the "required" violations.
- **Schema**: keep the ajv harness (already green) as a CI step so `example-env.json` can't drift from `schema/env.schema.json`.

## 10. Build order (milestones)

0. **Scaffold** — composer.json, provider, config, Testbench, CI.
1. **Manifest** — loader, validator, value objects, merge (+ their unit tests). *Highest-value core.*
2. **Engine** — rules, RuleEngine, EnvironmentResolver, RawEnvironment.
3. **Guard** — BootGuard, GuardBypass, exception, ViolationReporter; wire into provider `boot()`.
4. **`env:check`** — thin command over the engine.
5. **`env:generate`** — generator + existing-.env parsing.
6. **Publish** — schema URL, README, `env:generate` writes the `$schema` line into new manifests.

## 11. Risks / decisions to confirm during build

- **Exact default allowlist** — §3 is a first draft; will need real-world tuning (esp. around `migrate`, `octane`, `horizon`).
- **argv-based command detection** — accept its best-effort nature (ADR-0003 non-goal), or invest in `Artisan::starting` hook if it proves flaky.
- **`env:check` multi-env** — v1 checks one env; a `--all` sweep over every declared environment may be wanted for CI.
- **Manifest-optional vs required** — default `required:true` means "no `env.json`" fails boot; confirm that's desired for a fresh install (composer `package:discover` is allowlisted, so it survives).
```

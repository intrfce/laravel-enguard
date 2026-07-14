# ADR-0003 — Environment determination & threat model

**Status:** accepted (2026-07-14)

## Threat model (governing principle)

> EnGuard stops you making **mistakes** with production keys locally. It is **not** a security boundary against someone who intentionally wants to break things.

Consequences that follow from this and resolve future questions by default:
- Spoofing (`APP_ENV=local` on a real production box) is **out of scope** — a committed manifest cannot defend against someone who controls the environment.
- We optimise for catching **accidents and typos**, not for tamper-resistance.
- When a choice is between "safe against fat-fingers" and "safe against a determined adversary", pick the former; the latter is a non-goal.

## Environment determination

- The **ruleset in force** is selected from Laravel's resolved environment: `$app->environment()` / `config('app.env')`.
- This is a deliberate, narrow **exception** to ADR-0002's "never read `config()`" rule: resolved config is used *only* to pick the ruleset, never to validate a variable's value.
- Missing `APP_ENV` collapses to Laravel's built-in default `'production'` — the strictest ruleset. **Fail-closed for free.**

## Closed environment set

- `env.json` **declares the legal environments** (e.g. `local`, `staging`, `production`).
- An `APP_ENV` the manifest doesn't recognise (`prod`, `staging-2`, a typo) is itself a **violation** — this is exactly the accident class the package exists to catch.

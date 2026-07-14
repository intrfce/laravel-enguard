# ADR-0002 — Validation mode: raw-environment guard

**Status:** accepted (2026-07-14)

## Context

EnGuard could validate the raw process environment (A), resolved `config()` values (B), or run only as a deploy gate (C). Every use case the author described — detecting variables *missing from the environment*, generating `.env` on checkout, catching production values in a local environment — concerns the **actual environment**, not resolved config.

## Decision

EnGuard is fundamentally a **raw-environment guard (A)**.

1. The guard reads the **raw environment** (`$_ENV` / `getenv()`), never `env()` or `config()`. This makes it immune to `config:cache` blinding it.
2. **Guard and Check share one rule engine, two entry points:**
   - **Guard** — runs during boot (service provider), halts on violation.
   - **Check** (`env:check`) — a command that runs the same rules on demand, exits non-zero. Home for the CI/deploy gate.
3. **`config:cache` resolution (production footgun):**
   - Full validation is hooked into **`config:cache` build time**, when the raw env is genuinely present — this is the production gate.
   - When config **is** cached at runtime, the boot guard **softens**: it skips the "missing" class of check (post-cache, raw vars may legitimately be absent) rather than reporting a false outage.
4. Option B (config-value validation) is rejected: it would make this a config validator, not an env guard, and require mapping manifest keys to config paths.

## Consequence
- Violations are **collected and reported together**, not failed one-at-a-time (recommended and adopted; author may revisit).

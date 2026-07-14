# ADR-0004 — Rule vocabulary & the secrets model

**Status:** accepted (2026-07-14)

## The shape/value split

Because `env.json` is committed, secret *values* can never live in it. Rules therefore split by variable kind:

| Kind | Permitted rules | Value in manifest? |
|---|---|---|
| **Shape** (secrets) | `required`/`forbidden`, `notEmpty`, `matches`/`rejects` (regex), `type` (`bool`,`int`,`url`,`email`) | never |
| **Value** (non-secrets) | `equals`, `oneOf`, `notOneOf`, + all shape rules | yes — safe to commit |

The headline use case ("no production key in local") is a **shape** rule, not a value rule:
`STRIPE_SECRET` → `local: matches ^sk_test_, rejects ^sk_live_`.

## v1 vocabulary (accepted)

`required` / `forbidden`, `notEmpty`, `equals`, `oneOf`, `notOneOf`, `matches` / `rejects` (regex), `type` ∈ {`bool`, `int`, `url`, `email`}.

**Deferred (not v1):** min/max length, cross-variable rules (e.g. "if `MAIL_MAILER=ses` then `AWS_KEY` required").

## `secret: true` is a first-class flag

Explicitly declared, never inferred. It changes two behaviours at once:
- **Validation:** shape-only. `equals`/`oneOf`/`notOneOf` become illegal for that variable.
- **Generation:** `env:generate` writes a placeholder/blank, never a value.

## A declared value is a generation *default*, not a constraint

A non-secret with a value in the manifest (e.g. `APP_URL: "http://localhost"`) is a **seed for `env:generate`**, **not** enforced. To *enforce* an exact value, the author must wrap it explicitly in `equals`. One field never silently means both intents.

## Open structural note
- Whether rules support a `default`/`*` base ruleset overridden per named environment (base+override) vs flat per-environment is a manifest-shape question — folded into the format decision (Q3).

# ADR-0007 — Manifest scope: declared-only

**Status:** accepted (2026-07-14)

## Decision

The manifest is a **subset, not an exhaustive inventory**. EnGuard judges **only the variables it declares**.

- An **undeclared** environment variable (whatever Laravel or a third-party package sets) is **never a violation** — it is simply outside EnGuard's concern.
- Among **declared** variables, absence is a violation **only when a rule requires presence** (`required: true`). A declared-but-optional variable may be absent freely.

## Rationale

- Keeps EnGuard **frictionless on install** — no need to declare `APP_NAME`, `LOG_CHANNEL`, `VITE_*`, `TELESCOPE_ENABLED`, and the long tail of framework/vendor vars just to boot.
- Consistent with the threat model (ADR-0003): a guard that halts boot over an undeclared `VITE_` var is exactly the kind of friction that gets a package removed.

## Trade-off accepted

This posture **cannot catch a mistyped key** (`STRIPE_SCERET=…`) on its own, because "unknown key" is the normal case. Typo protection instead comes from the *declared* side: `STRIPE_SECRET` being `required` means the typo'd variant leaves the real key missing → violation. A stricter "warn on undeclared" mode was considered and rejected for v1.

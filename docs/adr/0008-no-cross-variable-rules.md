# ADR-0008 — Cross-variable rules are out of scope

**Status:** accepted (2026-07-14)

## Decision

EnGuard will **not** support cross-variable / conditional rules (rules that condition one variable's constraints on *another variable's value*, e.g. "if `MAIL_MAILER=ses` then `AWS_*` required"). Rules condition only on the **environment** (base+override, ADR-0005).

## Rationale

- Adds a second mental model to the manifest — relationships spanning multiple variables have no natural owner among them, breaking "one variable = one entry."
- Introduces a condition language, effect-precedence, and contradiction-detection — complexity that the "prevent mistakes, not a security boundary" threat model (ADR-0003) does not justify.
- The common driver→credentials fuck-up is real but not worth a rules engine in a strict-JSON file.

## Preserved design (if ever revisited)

The sketch that was rejected, kept so it need not be re-derived:

- **Location:** a top-level `conditionals` array (not attached to a variable), since a multi-variable rule has no single owner.
- **Effect vocabulary:** `require` / `forbid` only (presence-coupling); *not* the full rule vocabulary.
- **Condition:** a `when` map of `VAR: value` equality checks, multiple keys = AND, no OR/negation (an OR is two conditionals).
- **Evaluation:** single-pass — evaluate every `when` against the raw environment, layer triggered effects on top of each variable's merged base+env ruleset (highest precedence), validate once. Conditions read values; effects only add constraints; nothing mutates the environment ⇒ no cycles, no ordering. Contradictory effects on one variable = a manifest error.

```jsonc
// The rejected shape, for reference only:
"conditionals": [
  { "description": "SES mail requires AWS credentials",
    "when": { "MAIL_MAILER": "ses" },
    "require": ["AWS_ACCESS_KEY_ID", "AWS_SECRET_ACCESS_KEY", "AWS_DEFAULT_REGION"] }
]
```

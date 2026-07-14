# ADR-0005 — Manifest format & structure

**Status:** accepted (2026-07-14)

## Format: strict JSON + published JSON Schema

- `env.json` is **strict JSON** — parseable by `JSON.parse`, `json.loads`, `jq`, any CI tool in any language. This honours the language-agnostic, at-the-root goal.
- EnGuard **publishes a JSON Schema**; the manifest carries a `$schema` reference. This gives editors autocomplete on rule names, structural validation, and hover-documentation — recovering most of what comments would provide.
- **JSONC/JSON5 rejected:** the `//`-comment benefit doesn't outweigh losing universal parseability, once the JSON Schema is carrying the documentation load.
- Known wart: **regex escaping** in JSON strings (`\\d`, `\\/`). The schema can flag malformed patterns in-editor; accepted as the cost of strict JSON.

## Schema vs. runtime-validator boundary

The published JSON Schema (`schema/env.schema.json`, verified against draft 2020-12) enforces **structure and types**: known rule names only (`additionalProperties: false`), valid `type` enum, required top-level keys, env-var-name shape. It catches fat-fingered rule names and unknown keys in-editor.

Three rules are **cross-field** and JSON Schema cannot express them; they are the responsibility of **EnGuard's runtime manifest validator** (which must exist anyway, so a broken manifest fails loudly at boot):

1. `rules` keys must be `default` or a **declared** environment (schema can't reference the sibling `environments` array).
2. `secret: true` ⇒ `equals` / `oneOf` / `notOneOf` / `value` are illegal for that variable.
3. A single ruleset must not combine contradictory value rules (e.g. `equals` + `oneOf`).

## Structure: base + override

Variable-level fields (`description`, `secret`) sit above a `rules` object keyed by environment, with a `default` base:

```jsonc
"STRIPE_SECRET": {
  "description": "Stripe secret key",
  "secret": true,
  "rules": {
    "default":    { "required": true },
    "local":      { "matches": "^sk_test_", "rejects": "^sk_live_" },
    "production": { "matches": "^sk_live_" }
  }
}
```

## Merge semantic

- A named environment **shallow-merges over `default`, key by key**. Effective ruleset = `default` ∪ `<env>`, with the named env winning on shared keys.
- **Omission means inherit** — never "disable."
- To override a rule *off*, set it **explicitly** (`"required": false`). Consequently rules like `required` are **booleans**, not presence-flags.

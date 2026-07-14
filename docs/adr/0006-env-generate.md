# ADR-0006 — `env:generate` & the fate of `.env.example`

**Status:** accepted (2026-07-14)

## `.env.example` is dropped entirely

`env.json` fully replaces `.env.example`. EnGuard does **not** generate, maintain, or sync a `.env.example`. There is one source of truth: the manifest. (Supersedes the earlier proposal to emit `.env.example` as a generated artifact.)

## `env:generate` behaviour

- **`.env` absent** → write a full `.env` from the manifest for the target environment:
  - non-secret with a generation default → the default value;
  - non-secret with `equals X` → `X`;
  - **secret → blank**;
  - each variable's `description` is written as a `#` comment on the line above (self-documenting — the job `.env.example` used to do).
- **`.env` present** → **never overwrite.** The command exits without clobbering, and instead:
  - reports which manifest keys are **missing** from the existing `.env`;
  - **prompts** whether to append them (interactive), or
  - appends them directly when run with **`--generate-missing`** (non-interactive path).
  - `--generate-missing` **only appends missing keys**; every existing value is preserved untouched. Secrets are still written blank.
- **Target environment:** defaults to `local` (`--environment=` to override; not `--env`, which Laravel reserves as a global artisan option) — chooses which rules/defaults drive generation.

## Adopted defaults (unopposed)
- Description → `#` comment above each key.
- Local is the default generation target.

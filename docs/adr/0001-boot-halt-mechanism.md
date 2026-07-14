# ADR-0001 — Boot-halt mechanism

**Status:** accepted (2026-07-14)

## Decision

1. **Trigger point.** The guard runs inside EnGuard's **service provider** so that *every* entry point — HTTP, queue, scheduler, artisan — is stopped when the environment is judged invalid or dangerous. (The provider must run as early as possible; see open note below.)

2. **Escape hatch.** A configurable **allowlist** of commands bypasses the guard so the environment can be inspected/repaired even when it's invalid (e.g. `env:generate`, `env:check`). Defaults TBD in ADR covering `env:generate`.

3. **Failure behaviour.** The guard **throws an exception**. Rendering follows Laravel's normal error rules (governed by `APP_ENV` / `APP_DEBUG`) — no bespoke error screen. Corollary: the exception message must carry the **full, self-contained violation report** so operators reading *logs* (where prod failures surface) learn which variables failed, even though the rendered response is generic in production.

## Resolved notes
- **Timing: run in `boot()`** (resolved in implementation plan §2). By `boot()`, raw env + `app.env` are loaded and the exception handler is installed; nothing is handled/executed until all providers boot, so it's early enough. `register()` rejected (risks pre-empting the exception handler).
- Fail-fast vs collect-all: **collect-all** (confirmed, ADR-0002).

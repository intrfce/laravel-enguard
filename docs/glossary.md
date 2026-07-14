# EnGuard — Glossary

> Living vocabulary for the package. Terms are provisional until an ADR pins them down.

| Term | Working definition | Status |
|------|--------------------|--------|
| **EnGuard** | The package. Guards a Laravel app's boot against a misconfigured environment. | provisional |
| **Manifest** (`env.json`) | Root-level file declaring every environment variable the app knows about, plus rules. Candidate replacement for `.env.example`. | provisional |
| **Variable Definition** | One entry in the manifest: a key, optional description, and its rules. | provisional |
| **Environment** | A named runtime context (maps to `APP_ENV`, e.g. `local`, `staging`, `production`). | provisional |
| **Rule / Constraint** | A condition a variable must satisfy in a given environment (present, absent, equals, matches, type…). | accepted |
| **Shape rule** | A rule about a value's *form* (regex/type/presence), never its content. The only rules legal for secrets. | accepted |
| **Value rule** | A rule about exact content (`equals`/`oneOf`). Legal only for non-secrets. | accepted |
| **Secret** (`secret: true`) | A variable whose value must never be committed; validated by shape only; generated as a placeholder. | accepted |
| **Generation default** | A value declared for a non-secret var, used to seed `.env` — *not* enforced unless wrapped in `equals`. | accepted |
| **Guard** | Boot-time check (service provider) that reads the **raw environment** and halts boot when a constraint is violated. | accepted |
| **Check** (`env:check`) | Command that runs the *same ruleset* as the Guard on demand and exits non-zero. CI/deploy gate. | accepted |
| **Raw environment** | The actual process environment (`$_ENV`/`getenv()`), as opposed to Laravel's resolved `config()`. EnGuard validates this. | accepted |
| **`env:generate`** | Artisan command that materialises a `.env` (or `.env.example`) from the manifest. | provisional |
| **Rule engine** | The single component that evaluates constraints; invoked by both Guard and Check. | accepted |
| **Declared variable** | A variable named in the manifest. EnGuard judges *only* these; undeclared vars are ignored. | accepted |
| **Base / override** | Manifest rules are a `default` base shallow-merged with per-environment overrides; omission inherits, explicit value wins. | accepted |

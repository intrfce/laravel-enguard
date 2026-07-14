# Architecture Decision Records — EnGuard

ADRs capture decisions made during the grilling session. Each starts as an **open question**; once you commit, it becomes an ADR with a number.

## Governing principle (see ADR-0003)

> **EnGuard prevents mistakes, it is not a security boundary.** It stops you fat-fingering production keys into local; it does not defend against someone who intentionally controls the environment. When in doubt, optimise for catching accidents.

## Open questions (grilling agenda)

| # | Question | Status |
|---|----------|--------|
| Q1 | Boot-halt mechanism — *how* and *when* do we stop the app, without bricking the very command that fixes it? | ✅ ADR-0001 |
| Q2 | Does validation run at runtime (every boot) or at build/deploy time? Interaction with `config:cache`. | ✅ ADR-0002 |
| Q3 | Manifest format: JSON vs PHP vs YAML — comments, expressiveness, tooling. | ✅ ADR-0005 |
| Q4 | Secrets — a committed manifest can't hold secret *values*; value-match vs shape-match. | ✅ ADR-0004 |
| Q5 | Constraint vocabulary — the full set of rule types. | ✅ ADR-0004 |
| Q6 | Environment identity — what defines "the environment", given `APP_ENV` is itself an env var. | ✅ ADR-0003 |
| Q7 | `env:generate` semantics — interactive? overwrite? defaults? | ✅ ADR-0006 |
| Q8 | Relationship to `.env.example` — replace outright or generate for compatibility? | ✅ ADR-0006 (dropped entirely) |
| Q9 | Is the manifest exhaustive — are undeclared env vars a violation, a warning, or ignored? | ✅ ADR-0007 (declared-only) |

**Agenda complete — all nine questions resolved (ADR-0001 … ADR-0007).**

## Post-agenda decisions

| ADR | Decision |
|---|---|
| **0008** | Cross-variable / conditional rules — **rejected** for now (design preserved in the ADR). |

Decisions get promoted to `NNNN-title.md` files here as they're settled.

# TEMPLATE BASELINE — Canonical Decisions for vanilla-php

This module records the **declared canonical decisions** of the vanilla-php template. These are
TEMPLATE-scope rules (`01-governance.md`): deliberate architectural choices for every project
using this template — **NOT evidence from any specific repository**. They stand until a project
overrides them explicitly in `MASTER_BUILD_SPECIFICATION.md` or via a project-specific module,
and they apply to every consumer project that installs this template.

> [!IMPORTANT]
> Honesty marker: nothing in this module is claimed to be "verbatim from a live codebase". The
> canonical snippets in `canonical-snippets.md` are the **template's declared forms** — the
> reference shape to copy until the project's own code establishes a different precedent
> (`01-governance.md` Evidence Honesty for Template Rules).

---

## Stack (binding at template level)

| Concern | Decision | Scope |
|---------|----------|-------|
| Language | PHP `^8.2` | TEMPLATE (project may tighten) |
| Package manager | Composer | TEMPLATE |
| Autoloading | PSR-4 (`src/` root, project-defined namespace) | TEMPLATE |
| Framework | **None** — vanilla PHP, no full framework | TEMPLATE |
| DI container | `illuminate/container` — **MUST** | TEMPLATE |
| Support library | `illuminate/support` (`Str`, `Collection`) — **MAY** | TEMPLATE |
| Strict types | NOT used — no `declare(strict_types=1)` (consistent with the architecture DNA) | TEMPLATE |
| DB layer | **Not locked** — PDO, query builder, ORM, or document store per project | PROJECT decision |
| Routing | **Not locked** — per project (front controller, router library, or none) | PROJECT decision |
| Frontend | **Not locked** — per project; conditional modules 14/20 apply only when present | PROJECT decision |
| Testing | PHPUnit (standalone) | TEMPLATE |
| Static analysis | PHPStan (level per `phpstan.neon`, default 5) | TEMPLATE |
| Formatting | Pint (PSR-12-based) or php-cs-fixer — project picks | PROJECT decision |

---

## Architecture Invariants (INVARIANT at template level)

1. **All code is class-based (OOP hard rule).** Every use case is a class (`Action`); there is no
   procedural code outside a class. Even a single-method use case is a class.
2. **Repository for all data access.** The moment data access exists, a Repository owns it; no
   raw queries outside a Repository (INVARIANT).
3. **Constructor injection via container.** Dependencies are injected through constructors;
   `new` inside consumers is only for value objects/factories (INVARIANT).
4. **Actions are the business-logic default.** Single-concern use-case logic lives in Actions.
5. **Services required when warranted.** Logic reused across use cases/aggregates lives in a
   domain-named Service — required when warranted, never gratuitous.
6. **Contracts are DNA.** The canonical Contracts (`ActionContract`, `RuledActionContract`,
   `IndexActionContract`, `RepositoryContract`) exist even with a single implementation — they
   define the architecture's vocabulary.
7. **No dead code.** Every class/method/parameter/`rules()`/`$payload`/import is justified by the
   use case (Necessity Ladder — `03-architecture.md`). Classes are obligatory; only method weight
   adapts.

---

## Necessity Ladder (recap — authority is `03-architecture.md`)

```
USE CASE
├── Validates input?  → RuledAction (rules() + $validatedPayload)
├── No validation?    → Plain Action (NO rules(), NO $validatedPayload)
├── Reads input?      → handler($payload)
├── No input?         → handler() (no parameter)
├── Data access?      → Repository exists for that source
├── Logic reused?     → extract to domain-named Service
└── Any unused member → REMOVE (dead code forbidden)
```

The ladder decides **method weight**, never class existence.

---

## Whitelist Policy

| Package | Status | Purpose |
|---------|--------|---------|
| `illuminate/container` | **MUST** (template) | PSR-11-style DI container; auto-resolution is the default |
| `illuminate/support` | **MAY** (template) | `Str` string helpers, `Collection` — convenience only; not required |
| Anything else | **PROJECT decision** | Adopted only when the spec whitelists it and an audit justifies it (`21-state-delivery-environment.md`) |

Rule: never add a dependency outside the whitelist without a recorded project decision in
`MASTER_BUILD_SPECIFICATION.md`.

---

## Project Override Mechanics

- A project overrides a TEMPLATE decision by recording it in `MASTER_BUILD_SPECIFICATION.md`
  (e.g. "adopt `respect/validation`", "use strict types", "PDO MySQL with a migration library").
- The override wins at LEVEL 2 (project-specific mandatory) over the LEVEL 5 template
  convention (`ai-instructions.md` Priority System).
- When a project's existing code contradicts a template rule, the code wins; record the deviation
  in the decision log (`17-agent-discipline.md`).

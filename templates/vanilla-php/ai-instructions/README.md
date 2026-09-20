# AI INSTRUCTIONS — Module Map & Analysis Report

This directory is the module set of the vanilla-php instruction system. Read the constitution
(`ai-instructions.md` at the template root) first, then the modules relevant to your task.

---

## Module Map

| Module | Topic | Scope |
|--------|-------|-------|
| `01-governance.md` | Priority, conflict resolution, rule scope | GLOBAL |
| `02-agent-workflow.md` | Mandatory workflow, decision trees | GLOBAL |
| `03-architecture.md` | Layers, contracts, Necessity Ladder (anti-dead-code) | UNIVERSAL + PROJECT |
| `04-coding-standards.md` | PHP style (PSR-12), self-explanatory code | UNIVERSAL + PROJECT |
| `05-naming.md` | Naming conventions | UNIVERSAL + PROJECT |
| `06-testing.md` | PHPUnit strategy & conventions | UNIVERSAL + PROJECT |
| `07-security.md` | Auth (no framework), validation, CSRF, uploads, secrets | UNIVERSAL + PROJECT |
| `08-git.md` | Branching (`develop`/`main`), commits, releases | UNIVERSAL + PROJECT |
| `09-tools.md` | Composer, Pint, PHPStan, PHPUnit usage | UNIVERSAL + PROJECT |
| `10-quality-gates.md` | Gates, senior self-review, anti-AI-slop gate | GLOBAL |
| `11-forbidden-behavior.md` | Single source of truth for prohibitions | GLOBAL |
| `12-project-specific/template-baseline.md` | Canonical vanilla-php decisions (declared, honest) | TEMPLATE |
| `12-project-specific/canonical-snippets.md` | Declared snippet bank (full + minimum forms) | TEMPLATE + PROJECT |
| `12-project-specific/{project}.md` | Project invariants (added per consumer project) | PROJECT-SPECIFIC |
| `13-database.md` | Data access invariants (conditional on DB) | UNIVERSAL + PROJECT |
| `14-frontend.md` | Frontend rules (conditional) | CONDITIONAL |
| `15-edge-cases.md` | Boundary probes before "done" | GLOBAL |
| `16-debugging.md` | Systematic debugging loop | GLOBAL |
| `17-agent-discipline.md` | Feedback, decision log, honesty, scope-stop | GLOBAL |
| `18-planning-and-safe-change.md` | Phase decomposition, impact analysis | GLOBAL |
| `19-data-reliability.md` | Schema safety, transactions, jobs, media | UNIVERSAL + PROJECT |
| `20-frontend-and-contracts.md` | Contract discipline, i18n, a11y, errors | CONDITIONAL |
| `21-state-delivery-environment.md` | State machines, reproducibility, dependency audit | UNIVERSAL + PROJECT |

---

## How to Use

1. Read `ai-instructions.md` (constitution) — mandatory.
2. Read `01-governance.md` + `02-agent-workflow.md` — mandatory.
3. Read `12-project-specific/template-baseline.md` + `12-project-specific/canonical-snippets.md`
   — the template's declared decisions and reference forms.
4. Read the topic modules relevant to the task.
5. Read `MASTER_BUILD_SPECIFICATION.md` at the project root — and create it (via detailed
   operator Q&A) if it does not exist yet. This is the project's authoritative definition.

---

## Analysis Notes (authoring)

- **Scope labels** are used consistently per `01-governance.md`: GLOBAL, UNIVERSAL, TEMPLATE,
  PROJECT-SPECIFIC, CONDITIONAL, MODULE, LANGUAGE.
- **Declared, not repurposed evidence:** the vanilla-php set is a template — its canonical
  decisions (`template-baseline.md`) and snippets (`canonical-snippets.md`) are declared forms,
  honestly marked, NOT verbatim repo extracts. The moment a consumer project has real code, that
  code becomes the highest evidence source (`01-governance.md`).
- **Not locked by template (project decisions):** DB layer, routing, frontend stack, validation
  engine, formatter choice. Recorded in `MASTER_BUILD_SPECIFICATION.md`.
- **Whitelist:** `illuminate/container` (MUST), `illuminate/support` (MAY); anything else is a
  recorded project decision.

---

## Consumer Project Notes

When this template is distributed into a consumer project (`ainstruct vanilla-php`):

- Copy the set to the consumer project root: `ai-instructions.md` + `ai-instructions/`.
- Create `MASTER_BUILD_SPECIFICATION.md` at the consumer project root (or confirm the existing
  one) — it is the project's authoritative definition and takes precedence over template rules.
- Add a `12-project-specific/{project}.md` module for project invariants as the project matures.
- Do not commit generated artifacts into the AI-Instructions repository itself.

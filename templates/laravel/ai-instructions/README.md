# AI INSTRUCTION SYSTEM — OVERVIEW

This folder contains the **AI instruction system** used by the **LingSID** project (Laravel 12 + Inertia/Vue 3 + TypeScript). Universal modules stay reusable; project-specific rules live in `12-project-specific/lingusid.md`.

## Architecture

The system separates **universal rules** (apply to every project) from **project-specific rules** (apply to a single project):

| Scope | Meaning |
|-------|---------|
| GLOBAL | Applies to all projects and all tasks |
| UNIVERSAL | Applies to all projects using this system |
| PROJECT-SPECIFIC | Applies only when the repository matches a defined project |
| MODULE / LANGUAGE / FRAMEWORK / TASK | Applies only to a specific part/context |

## File Responsibilities

| File | Responsibility |
|------|----------------|
| root `ai-instructions.md` | **Canonical constitution / single entry point.** How to read the system, file map, shared principles, priority system, rule scope model, semantic strength hierarchy, workflow summary, project onboarding, conflict resolution, quality gates, final verification. |
| `01-governance.md` | Priority hierarchy, rule scope determination, conflict resolution protocol, invariants. |
| `02-agent-workflow.md` | Mandatory workflow: Understand → Inspect → Find Analogues → Plan → Implement → Static Analysis → Test → Diff Review → Style Review → Finalize. Decision trees for each step. |
| `03-architecture.md` | Layer architecture (Controller → Action → Repository → Model), dependency directions, context-based organization, routing, frontend org, abstraction philosophy, binding architectural decisions. |
| `04-coding-standards.md` | PHP & TypeScript/Vue style, comment policy, import ordering, formatting rules (Pint/Prettier/ESLint), file organization. |
| `05-naming.md` | Naming for PHP classes, Actions, methods, DB, routes, frontend, enums, prohibited names. |
| `06-testing.md` | Framework, `#[Test]` attribute, naming, organization, mock rules, patterns, when to run tests. |
| `07-security.md` | Auth, authz (Spatie Permission), validation, rate limiting, sensitive data (NIK/PII), audit trails, media uploads, CSRF, key security rules. |
| `08-git.md` | Branching from `develop`, conventional commits, prohibited operations, CI workflows. |
| `09-tools.md` | Terminal, paths, parallelism, linting (PHPStan/Pint), JS runtime (bun), tests, external tools. |
| `10-quality-gates.md` | Universal quality gates, senior self-review rubric, verification decision tree, final checklist, CI pipeline, when human review is required, project-specific gates pointer; **anti-AI-slop gate** for UI/copy/prose output. |
| `11-forbidden-behavior.md` | Explicit prohibitions (architecture, style, UI/copy, implementation, security, scope). **Single source of truth** — other modules cross-reference it. |
| `13-database.md` | Database rules: migration conventions, FK/indexing, relationships, model conventions, query patterns (N+1, eager load), transactions, factories, seeders, soft deletes, performance lens (N+1/over-fetch/pagination/index). |
| `14-frontend.md` | Frontend rules: Vue 3/TypeScript/Inertia/Tailwind stack, directory org, component structure, typing, form handling (`useForm`), routing (ziggy), styling (`cn()`), composables, frontend testing. |
| `15-edge-cases.md` | Edge-case & boundary probes (input/type, state/lifecycle, permission/ownership, integration/scale) that MUST run before any feature is considered complete. |
| `16-debugging.md` | Systematic debugging loop: reproduce → isolate → hypothesize → minimal fix → verify; root-cause classes to check first in Laravel, when to escalate. |
| `17-agent-discipline.md` | Agent behavior skills: feedback absorption, decision log & assumption surfacing, honesty about verification, scope-stop & escalation timing. |
| `18-planning-and-safe-change.md` | Phase decomposition & incremental verification, change impact analysis (blast radius), safe refactoring, test-to-break/adversarial testing. |
| `19-data-reliability.md` | Data migration safety, transactions/locking/concurrency, queue/job reliability (idempotency, retries), media/upload lifecycle and orphan cleanup. |
| `20-frontend-and-contracts.md` | Frontend↔backend contract discipline (types derived from real backend), UI text & i18n, accessibility & form UX, error contract discipline. |
| `21-state-delivery-environment.md` | Status/state machine discipline, reproduce-everywhere (lockfiles, fresh DB), end-to-end demo verification, dependency hygiene & audit. |
| `12-project-specific/` | Project-specific modules — LingSID invariants in `lingusid.md`, verbatim canonical snippet bank in `canonical-snippets.md`. Add a file here per project. |

## How to Use

1. Read root `ai-instructions.md` as the entry point.
2. Read `01-governance.md` and `02-agent-workflow.md`.
3. Apply the relevant topical modules (`03`–`11`, `13`–`21`) for the task — `13-database.md` when touching schema/data, `14-frontend.md` when touching the Inertia/Vue layer, `15-edge-cases.md` before declaring anything done, `16-debugging.md` when tracing a bug, `18-planning-and-safe-change.md` for multi-layer changes, `20-frontend-and-contracts.md` for cross-layer contract work, `21-state-delivery-environment.md` before finishing a user-visible flow.
4. Load the anti-slop skill (`.opencode/skills/antislop/SKILL.md` + the concern skill: `antislop-copywriting` for copy, `antislop-ui` for UI, `antislop-code` for comments) before writing UI copy, user-facing text, or prose output (see `10-quality-gates.md`).
5. Load matching modules from `12-project-specific/` when they apply.
6. **Read `MASTER_BUILD_SPECIFICATION.md` at the project root** (or create it via detailed operator Q&A if missing) — never write code without it.

## Authoring Instruction Sets

When creating or updating instruction sets in this repository, treat the **`templates/laravel/` set as the
reference template**: mirror its constitution layout, module split (`01`–`11`, `13`–`21`,
`12-project-specific/`), precision, and source-anchored verbatim snippets
(`canonical-snippets.md`). Always re-run `ainstruct <framework>` (or `./bin/setup-ai-rules.sh <framework>`
after removing the stale `ai-instructions/master`) so template, master, and distributed copies stay identical.

## Adding a New Project

1. Each project's unique invariants go into `12-project-specific/{project}.md`.
2. Never generalize project-specific rules into universal rules, and vice versa.
3. Keep the universal modules (`01`–`11`, `13`–`21`) free of project-specific references so they stay reusable.

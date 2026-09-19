# STATE, DELIVERY & ENVIRONMENT — Ship Like It's Production

These four skills are about the boundary between "it works locally" and "it works where it
matters": explicit state machines, reproducible environments, end-to-end proof, and disciplined
dependencies (`09-tools.md` defines the toolchain; this module defines the delivery procedures).

---

## 1. Status / State Machine Discipline

Business statuses are a documented state machine, not loose string columns.

- **Enums, not scattered strings** — statuses are PHP enums (stored as strings per
  `13-database.md`/`05-naming.md`); the frontend consumes the same enum values via the contract
  (`20-frontend-and-contracts.md`), never ad-hoc literals.
- **Transitions are explicit** — define the *allowed transition table* per entity (from → to →
  side effects) wherever state changes; invalid transitions are guarded, not silently ignored.
- **Guard in the Action** — transition legality is enforced in the business layer (RuledAction
  pattern, `12-project-specific/canonical-snippets.md`), with invalid transitions surfacing as
  controlled errors (error contract, `20-frontend-and-contracts.md`).
- **Audit on change** — every important state mutation leaves a trail (audit/activity log) per the
  invariants in `12-project-specific/lingusid.md`; no silent status flips.
- **Transitions are edge probes** — the `15-edge-cases.md` state/lifecycle probes cover re-running
  a transition, skipping a state, and concurrent transitions.

## 2. Reproduce-Everywhere Discipline

A change that only passes on an un-specified machine is not a change that passes.

- **Lock standards are committed** — `composer.lock` / `bun.lock` are committed and CI installs
  from lock (`composer install`, `bun install --frozen-lockfile` in
  `10-quality-gates.md` local-parity commands).
- **Fresh-from-scratch works** — `migrate:fresh --seed` (non-prod) and the documented seed
  sequence produce a working app; a new clone mid-task is a usable definition of done
  (`13-database.md`, `19-data-reliability.md` — migration safety).
- **CI parity, not CI imitation** — the exact commands CI runs (`10-quality-gates.md`) are run
  locally first; "passes on CI" is weaker than "passes locally with the same commands".
- **Environment is documented, not assumed** — env keys/vars a feature depends on are named in the
  PR/decision log; no `storage`-path or `.env` fiddling is committed
  (`11-forbidden-behavior.md`).

## 3. End-to-End Demo Verification

Before a user-visible change is "done", the agent demonstrates the flow **end to end** — not just
the unit that changed.

- **Drive the real flow** — start the app, perform the user journey (UI with the same stack, or
  HTTP with the real routes/validation), and capture the evidence (request/response or a screen
  trace).
- **Both paths** — the happy path and at least one failure path (a rejected validation, a 404)
  are exercised; errors show their contract (`20-frontend-and-contracts.md`).
- **Not covered → not claimed** — anything the demo did not reach is listed explicitly
  (honesty about verification, `17-agent-discipline.md`), and the task stays incomplete for that
  part rather than silently assuming.
- **Combine with probes** — the demo reuses the `15-edge-cases.md` probes for the flows it touches
  (empty list, permission denied, double submit).

## 4. Dependency Hygiene & Audit

Every dependency is a permanent piece of your attack surface and your build time. Adding one is a
decision, not a reflex.

- **Existing alternative first** — before adding a package, verify the same capability does not
  already exist (composer/bun lock, `app/`, vendor usage); impact analysis applies to packages too
  (`18-planning-and-safe-change.md`).
- **Audit before commit** — `composer audit` / `npm audit` are run for new dependencies; a known
  vulnerability in the added version is a blocker unless the operator confirms.
- **Justify the addition** — dependency + reason + size/maintenance/license noted in the decision
  log (`17-agent-discipline.md`); a package that duplicates an existing one is scope creep and is
  forbidden (`11-forbidden-behavior.md`).
- **Remove at the source** — an unused dependency identified during work is *reported*, not
  silently removed in the same diff (scope discipline, `11-forbidden-behavior.md`).

---

Cross-references: toolchain & commands `09-tools.md`; Action/register patterns & audit
`12-project-specific/lingusid.md`; state/scale probes `15-edge-cases.md`; scope discipline
`11-forbidden-behavior.md`.

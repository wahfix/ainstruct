# STATE, DELIVERY & ENVIRONMENT — Ship Like It's Production

These four skills are about the boundary between "it works locally" and "it works where it
matters": explicit state machines, reproducible environments, end-to-end proof, and disciplined
dependencies (`09-tools.md` defines the toolchain; this module defines the delivery procedures).

---

## 1. Status / State Machine Discipline

Business statuses are a documented state machine, not loose string columns.

- **Enums, not scattered strings** — statuses are PHP enums (stored as strings per
  `13-database.md`/`05-naming.md`); all code consumes the enum, never ad-hoc literals.
- **Transitions are explicit** — define the *allowed transition table* per entity (from → to →
  side effects) wherever state changes; invalid transitions are guarded, not silently ignored.
- **Guard in the Action** — transition legality is enforced in the business layer (RuledAction
  pattern, `12-project-specific/canonical-snippets.md`), with invalid transitions surfacing as
  controlled errors (error contract, `20-frontend-and-contracts.md`).
- **Audit on change** — every important state mutation leaves a trail (audit log, event record)
  per the invariants in `MASTER_BUILD_SPECIFICATION.md`; no silent status flips.
- **Transitions are edge probes** — the `15-edge-cases.md` state/lifecycle probes cover
  re-running a transition, skipping a state, and concurrent transitions.

---

## 2. Reproduce-Everywhere Discipline

A change that only passes on an un-specified machine is not a change that passes.

- **Lock standards are committed** — `composer.lock` is committed and CI installs from lock
  (`composer install`); the PHP version in CI matches the project's `.php-version`/CI config.
- **Environment parity** — the project's environment variables are documented in `.env.example`
  (`07-security.md`); CI runs with the same config surface the feature depends on.
- **Fresh-environment verification** — the feature is verified against a fresh checkout + clean
  dependency install, not only the current working tree.

---

## 3. End-to-End Demo Verification

A feature that is user-visible is not "done" when its unit test passes — it is done when the
real flow works.

- **Walk the real flow once** — from Entry through Action to Repository and back (and through the
  frontend when one exists), with real data.
- **Verify the failure path too** — an invalid submission shows the right error; a forbidden
  action is blocked; a missing record 404s correctly.
- **Record what you did NOT run** — if a flow needs a live server, a real mailer, or a payment
  sandbox, say so in the report (`17-agent-discipline.md` honesty).

---

## 4. Dependency Hygiene & Audit

Every dependency is a responsibility.

- **Whitelist discipline** — dependencies are adopted only per
  `MASTER_BUILD_SPECIFICATION.md` (template whitelist: `illuminate/container` MUST,
  `illuminate/support` MAY; anything else is a recorded project decision).
- **Audit before adding** — `composer audit` for known vulnerabilities; check the existing
  codebase for a suitable implementation before adopting a package
  (`11-forbidden-behavior.md` — no dependency without audit + justification).
- **No capability duplication** — a package duplicating an existing capability is scope creep.
- **Keep the lockfile honest** — commit `composer.lock`; never install ad-hoc packages that the
  lockfile does not reflect.

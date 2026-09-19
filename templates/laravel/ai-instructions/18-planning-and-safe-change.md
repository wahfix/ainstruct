# PLANNING, QUALITY & SAFE CHANGE

Coding is cheaper than wrong coding. These four skills shape *how* a change is planned, bounded,
and proven before it lands — turning a single big leap into a sequence of verifiable steps.

> [!IMPORTANT]
> They work with the mandatory workflow (`02-agent-workflow.md`): PLAN is detailed here, and every
> phase this module defines must complete its own verification before the next one begins
> (principle #4 — incremental, not dump). Quality Gate `10-quality-gates.md` checks them off.

---

## 1. Phase Decomposition & Incremental Verification

Every feature that touches more than one layer is decomposed into **ordered, verifiable phases**
before any implementation starts. The decomposition is written down and used as the running plan.

### Standard Phase Order (as applicable)

1. **Schema/migration** — columns, FK, indexes (with `13-database.md` conventions).
2. **Model** — relationships, casts, scopes, soft-delete decisions.
3. **Repository** — query methods the feature needs (no N+1, `13-database.md`).
4. **Action** — business logic, RuledAction array payload (`12-project-specific/canonical-snippets.md`).
5. **Controller/route** — thin orchestration, validation, authorization (`07-security.md`).
6. **Frontend** — types/contract first (`20-frontend-and-contracts.md`), then component/UI.
7. **Tests** — required when requested; otherwise triggered by edge probes (`15-edge-cases.md`) and
   the test-to-break skill (section 4 below).

### Rules

- **One phase at a time; verify before advancing.** Each phase's verification is its own proof:
  migration applies both directions (`php artisan migrate` + `migrate:rollback`), repository
  returns the right shape, action respects forbidden list, etc.
- **Do not build phase N+1 to "fix" a problem in phase N.** Re-enter the failing phase, correct
  it, re-verify it.
- **Decomposition is shared with the operator for multi-layer features** before phase 1, so scope
  and order are agreed — this is part of the decision log (`17-agent-discipline.md`).
- A feature without a written decomposition (more than one layer touched) is under-planned:
  produce one before IMPLEMENT.

## 2. Change Impact Analysis

Before modifying shared or widely-referenced code (models, repositories, base actions, routes,
shared frontend components, contracts), the agent **maps the blast radius** — it does not edit
first and discover damage later.

Done in this order:

1. **List every caller/dependee** — open the codebase (grep usages, read imports). Do not guess
   by name.
2. **Read at least one level out** — the callers' expectations: return types consumed, arguments
   passed, props read, tests that assert on the current shape.
3. **Identify contracts** that must survive: method signatures, array-payload keys, response
   shapes, frontend props (`20-frontend-and-contracts.md`), DB columns/indexes.
4. **Estimate silent-breakage risk** — points where a wrong shape fails late (JavaScript
   properties, untyped array access) get extra care.
5. **Write the impact map into the decision log** — one line per affected caller + expected
   effect; anything that would silently break an out-of-scope caller escalates
   (`17-agent-discipline.md` section 4).

If the impact map shows a change touching code the task did NOT ask to change, stop and ask —
never "fix it quietly" (`11-forbidden-behavior.md`).

## 3. Safe Refactoring

Refactoring is allowed **only when explicitly requested or clearly required by the task** — never
speculatively (`11-forbidden-behavior.md`). When it IS allowed, it is behavior-preserving by
discipline:

- **Get a green baseline first** — run the relevant tests/static analysis *before* touching
  anything, so a regression is yours to see.
- **Small, verifiable steps** — one structural change per step; after each step, re-run the
  baseline until green again. Never a big-bang rewrite.
- **Behavior-preserving by contract** — outputs, side effects, ordering, error paths are
  unchanged. A refactor that also fixes a bug must say so and split the change (refactor commit vs
  fix commit).
- **No collateral edits** — formatting fixes grouped separately; unused-code deletions noted in
  the decision log, not hidden.
- **Delete path is a change too** — removing a method/route/component performs the impact
  analysis from section 2 *before* deletion.

## 4. Test-to-Break / Adversarial Testing

When tests are written (requested, or triggered by the edge probes of `15-edge-cases.md`), they
are written with the intention of **breaking** the code — a green test set that only mirrors the
implementation is ceremony, not verification.

- **Boundary & invalid input** — use the `15-edge-cases.md` probes: null/empty/wrong-type/
  oversized, collisions, soft-deleted, permission edges, large sets.
- **Wrong-path assertions** — assert error contracts, status codes, and *that nothing happened*
  (no partial insert, no orphan file, no failed job) — not just the happy response.
- **Mutation self-check** — after writing the code, introduce one likely bug (wrong `<`, dropped
  `->nullable()`, wrong return) and confirm a test catches it. If no test would catch it, the test
  set is insufficient.
- **Regression from history** — the BAD patterns in `12-project-specific/canonical-snippets.md`
  and past bug classes (`16-debugging.md` root-cause table) become concrete assertions.
- **Test constraints** — respect `06-testing.md` conventions (`#[Test]` attribute, no mocking in
  feature tests, organizer-first).

A test that cannot fail for a meaningfully different reason than the code it tests is a dead line —
either sharpen it or delete it (permission to delete only after impact analysis).

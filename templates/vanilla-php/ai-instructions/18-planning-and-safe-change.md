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

1. **Spec confirm** — read `MASTER_BUILD_SPECIFICATION.md`; confirm entities, columns, flows
   with the operator when the spec is silent (Step 0 of `02-agent-workflow.md`).
2. **Entity** — structure + invariants (when the project uses entities).
3. **Repository** — data-access methods the feature needs (no per-row queries in loops,
   `13-database.md`).
4. **Action** — business logic; RuledAction when validation exists
   (`12-project-specific/canonical-snippets.md`); Service extraction when the logic is reused.
5. **Entry** — thin orchestration, transport validation, authorization (`07-security.md`) — only
   when explicitly requested.
6. **Frontend** — contract first (`20-frontend-and-contracts.md`), then presentation — only when
   the project has a frontend and it is explicitly requested.
7. **Tests** — required when requested; otherwise triggered by edge probes (`15-edge-cases.md`)
   and the test-to-break skill (section 4 below).

### Rules

- **One phase at a time; verify before advancing.** Each phase's verification is its own proof:
  the entity constructs, the repository returns the right shape, the action respects forbidden
  rules, static analysis is green.
- **Order respects the dependency direction** (`03-architecture.md`): Entity → Repository →
  Action → Entry. Do not write an Action against a Repository method that does not exist yet.
- Never build phase N+1 before phase N is verified.

---

## 2. Change Impact Analysis

Before altering anything other code depends on, find every dependent:

- **Renaming/moving a class, method, or module** → grep the whole codebase (and the instruction
  modules that reference it) for the old name; update all references in the same change
  (evidence-anchored — `12-project-specific/canonical-snippets.md` usage rule 6).
- **Changing a signature or return type** → update every caller in the same change; update the
  canonical snippet if the change becomes the new canonical form.
- **Changing a contract** → the canonical Contracts are DNA (`03-architecture.md`); changing one
  ripples through Actions, Repositories, and the snippet bank — verify every reference.
- **Deleting anything** → confirm nothing references it before removing; `git grep` is your
  friend.

---

## 3. Safe Refactoring

- **NO speculative refactoring** — only fix what the current task requires
  (`08-git.md` Speculative Refactoring Policy).
- When a refactor is required, keep behavior identical: run the same tests before and after;
  prefer small behavior-preserving steps over one giant rewrite.
- Extraction (Service, helper method) only when the logic is genuinely reused across use cases —
  never extracted against a single call site (`03-architecture.md`).

---

## 4. Test-to-Break

Write a test that **fails against the broken shape** before (or as part of) fixing it:

- For a bug fix: write the failing test that reproduces the bug, watch it fail, then fix the
  code, then watch it pass (the reproduction in `16-debugging.md`).
- For a new feature: write the behavioral test first where practical; at minimum, add the tests
  that would have caught the edge cases you probed (`15-edge-cases.md`).
- Tests are only run when requested (`06-testing.md`) — but when they are, they must test the
  behavior, not the implementation.

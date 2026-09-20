# QUALITY GATES — Before Considering Work Complete

All the following conditions MUST be met before any task is considered complete.

---

## Mandatory Quality Gates

1. [ ] Code follows existing patterns (has analogous examples in the codebase or a canonical
       snippet — `12-project-specific/canonical-snippets.md`).
2. [ ] Static analysis passes (project's configured level, e.g., PHPStan level 5).
3. [ ] Relevant tests pass (only if tests were requested/run).
4. [ ] No unrelated code was modified.
5. [ ] Code style matches neighboring files.
6. [ ] No comments were added unless asked.
7. [ ] No secrets or sensitive data were introduced.
8. [ ] Scope is limited to the requested feature.
9. [ ] All naming conventions followed.
10. [ ] No speculative changes were made.
11. [ ] No `dd()`, `dump()`, or `ray()` in committed code (see `11-forbidden-behavior.md` —
       single source of truth).
12. [ ] File organization respects the feature's context (`03-architecture.md`).
13. [ ] `MASTER_BUILD_SPECIFICATION.md` was read before writing code — or, if it was missing, it
       was created (complete, detailed, precise) via operator Q&A and confirmed before any code.
14. [ ] Senior self-review done (rubric below) — every condition traced on both branches, diff
       read as a reviewer, not as its author.
15. [ ] Edge-case probes applied to every touched code path (see `15-edge-cases.md`) — boundaries
       tested or explicitly designed for.
16. [ ] Multi-layer feature decomposed into ordered phases, each verified before advancing (see
       `18-planning-and-safe-change.md`).
17. [ ] Contract discipline kept in the same change (entry payload ↔ Action/rules ↔ repository
       columns — see `20-frontend-and-contracts.md` where a frontend exists).
18. [ ] Change reproducible in a clean environment (lockfiles, fresh data store) and, for any
       user-visible flow, verified end-to-end (see `21-state-delivery-environment.md`).
19. [ ] Decision log present — key decisions, assumptions, and anything that was NOT verified are
       surfaced to the operator (see `17-agent-discipline.md`).
20. [ ] No dead code: every class/method/parameter/`rules()`/`$payload`/import is used by the use
       case (Necessity Ladder — `03-architecture.md`; `11-forbidden-behavior.md`).
21. [ ] Anti-AI-slop gate for UI copy, user-facing text, and prose output — the anti-slop filter
       (`.opencode/skills/antislop/SKILL.md` + the relevant concern skill: `antislop-copywriting`,
       `antislop-ui`, `antislop-code`) is loaded BEFORE writing, the output is free of AI-slop
       patterns, and the filter's Delivery Gate ran before finalizing (rules live in the filter,
       not rewritten here). If the filter is not installed, obtain the vendored system from the
       AI-Instructions repository (upstream miqdadbadjuber/anti-slop — MIT) or apply its Empty AI
       Vocabulary rules manually and log the filter's absence in the decision log (see below).

---

## Anti-AI-Slop Gate (UI / Copy / Prose)

The anti-AI-slop system is a vendored filter (MIT — upstream miqdadbadjuber/anti-slop). It is
the canonical rule set for detecting machine-marketing prose and is **NOT rewritten into these
modules** — the rules live in the filter's own skills:

- Load `.opencode/skills/antislop/SKILL.md` (core) + the concern skill
  (`antislop-copywriting` for copy/text, `antislop-ui` for UI work, `antislop-code` for
  code comments) before writing UI copy, user-facing text, or prose-heavy output.
- Run the filter's Delivery Gate before considering such output final. Output carrying
  AI-slop patterns — marketing buzzwords (the Empty AI Vocabulary list in the copywriting
  skill), un-evidenced claims, generic filler — is NOT done.
- If the filter is not installed at `.opencode/skills/antislop/`, obtain the vendored system
  from the AI-Instructions repository (upstream miqdadbadjuber/anti-slop — MIT), or state the
  absence explicitly in the decision log and apply the Empty AI Vocabulary rules manually. Do not
  silently skip the gate.

---

## Senior Self-Review Rubric

Before considering any task complete, read your own diff **as a senior reviewer**, not as its
author. Answer each dimension with **PASS / FAIL + evidence**. Any FAIL blocks finalize:

- **Correctness** — trace every condition through both branches mentally; verify each error path
  surfaces (not swallowed), each return is the right type, and no statement is dead.
- **Boundaries** — the probes from `15-edge-cases.md` were actually run (null/empty, collisions,
  missing vs deleted records, permissions, large data, concurrency).
- **Minimalism & scope** — the diff is the smallest possible change; no unrelated files, no
  speculative edits, no dead code (see `11-forbidden-behavior.md`).
- **Readability & naming** — a stranger reading only the diff can reconstruct the intent; any
  line needing a comment is rewritten instead (self-explanatory rule — `04-coding-standards.md`).
- **Security requirements held** — no new trust of input without validation, authorization still
  decided server-side (`07-security.md`).
- **Data held** — data-layer constraints kept, repository queries avoid per-row queries in loops,
  performance lens from `13-database.md` applied.
- **Verifiable claim** — you can state what you changed, why, and how you know it is right
  (static analysis + the tests/probes you actually ran).

A self-review that cannot produce evidence for a dimension is itself a FAIL — investigate, do
not rationalize. This rubric complements (does not replace) the gate checklist above and the
human-review triggers below.

---

## Verification Decision Tree

```
Have I finished a coding task?
├── Did I read the build specification (MASTER_BUILD_SPECIFICATION.md), or create it via operator Q&A?
│   └── NO → Read/create it. Do not finalize.
├── Did I run static analysis?
│   └── NO → Run it now. Do not finalize.
├── Did I test what I changed (if requested)?
│   └── NO → Run relevant tests. Do not finalize.
├── Did I modify only files relevant to this feature?
│   └── NO → Revert unrelated changes. Do not finalize.
├── Does my code follow existing project patterns?
│   └── NO → Find an analogue and align. Do not finalize.
├── Is every class/method/signature I use verified to exist (evidence-anchored)?
│   └── NO → Open the real file/snippet and verify. Do not finalize.
└── Everything passes → Finalize.
```

---

## Final Verification Checklist

1. All files created/modified are necessary.
2. Code follows all project conventions.
3. Static analysis passes.
4. No speculative changes were made.
5. Scope is limited to the requested feature.
6. `MASTER_BUILD_SPECIFICATION.md` existed (or was created and confirmed) before coding — no
   assumptions contradict it.
7. Self-audit against the Master Self-Audit Checklist (root `ai-instructions.md`, section 10).

---

## Project-Specific Quality Gates

For the vanilla-php template, the following gates apply unless the project overrides them in
`MASTER_BUILD_SPECIFICATION.md`:

- [ ] `./vendor/bin/phpstan analyse` passes at the level configured in `phpstan.neon`.
- [ ] Code formatted with the project's configured formatter (`./vendor/bin/pint --test` when
      Pint is used).
- [ ] No static call to instance methods (`SomeAction::handle()`).
- [ ] No references to non-existent classes/methods; all imports verified against the codebase.
- [ ] No dead code introduced (Necessity Ladder — `03-architecture.md`).
- [ ] No data-layer access outside Repositories.

---

## CI Pipeline (GitHub Actions)

`.github/workflows/` runs the following on push/PR to `develop` and `main` (see `08-git.md`):

| Workflow | Command | Purpose |
|----------|---------|---------|
| `tests` | `composer test` (full suite) | Verify no regression across unit + feature tests |
| `lint` | `./vendor/bin/phpstan analyse`, `./vendor/bin/pint --test` (or the project's formatter) | Static analysis + style enforcement |

**Local parity rule:** before expecting a merge, the local branch MUST pass the same checks the
CI runs:

```bash
./vendor/bin/phpstan analyse
./vendor/bin/pint --test      # or ./vendor/bin/pint to auto-fix first
```

**CI failure handling:**

- Fix CI failures on the feature branch — never merge a red pipeline.
- `lint` failures: run `./vendor/bin/pint` to auto-fix, then re-run the check.
- `tests` failures: reproduce with `./vendor/bin/phpunit --filter={FailingTest}` and fix only
  feature-related code.

---

## When Human Review Is Required

A task is NOT considered complete if any of the following is true — ask the operator for
review/confirmation before finalizing:

1. The change alters data integrity semantics (schema changes, constraint changes, lossy data
   transformations).
2. The change touches security-relevant surfaces (auth, authorization, upload handling, rate
   limiting, secrets/config).
3. The change modifies domain invariants (status transitions, audit/activity behavior).
4. The change introduces a new dependency or a new architectural layer (Service, new validation
   engine, event system) that has no existing analogue.
5. The change requires a new pattern that has no canonical snippet or analogue in the codebase
   (see `11-forbidden-behavior.md` Unknown Territory Handling).
6. `MASTER_BUILD_SPECIFICATION.md` needs updating to stay truthful with a significant feature
   change.

# AGENT DISCIPLINE & LEARNING — Behavior, Decisions, Honesty

The highest-leverage skills of a coding agent are not about the keyboard — they are about how
the agent reacts to feedback, records what it knows, admits what it does not know, and stops at
the right moment. A disciplined process beats a smart guess.

> [!IMPORTANT]
> These four disciplines apply to every task, every phase, and every answer. They are what turn
> "the code works on my path" into "the code works and we can prove it."

---

## 1. Feedback Absorption — Learn from Every Failed Checklist

A failed CI check, a lint error, a rejected PR, or an operator correction is **evidence about a
wrong hypothesis** — not friction, not a personal failure. The skill is converting that evidence
into a corrected habit instead of a local patch.

- **Read the failure before touching anything.** Reproduce the failing check, read the actual
  error, and find the root cause (see `16-debugging.md`). Do not guess from the title of the
  check.
- **Fix the root cause, then re-run the WHOLE relevant check set** — never re-submit after only
  verifying the one line that failed. Local parity rules apply (`10-quality-gates.md`).
- **Generalize the lesson.** If feedback revealed a pattern you missed (e.g. a BAD pattern from
  `12-project-specific/canonical-snippets.md`), check the rest of your diff for the same pattern
  before resubmitting — do not wait to be told twice.
- **Do not repeat the same mistake across the diff.** One correction = one sweep across all
  similar sites.
- **Record the correction commitment** in the decision log (below) so the current session does
  not regress into the same behavior.

If the same check keeps failing after two genuine attempts, **stop and summarize** the situation
for the operator: show the reproduction, state what you tried, and ask for direction — do not
loop silently.

---

## 2. Decision Log & Assumption Surfacing

Every task that makes a judgment call records it. The decision log is a short list of key
decisions + assumptions + what was NOT verified, surfaced to the operator in the final report.

**What must be logged:**

- Decisions that choose between architecture/pattern options (e.g. "added a Service for the
  shared export logic", "used plain Action — no validation needed for this use case").
- Assumptions made because the spec was silent (e.g. "assumed `created_at` column per project
  convention").
- Anything **not verified** — honest negatives (see #3).
- Deviations from template rules justified by existing project code (the repository wins —
  `01-governance.md`).
- Template-level declarations used in place of repo evidence (the canonical snippets are declared
  forms, not repo facts — `01-governance.md` Evidence Honesty).

---

## 3. Honesty About Verification

State what you actually ran, and what you did NOT run. The difference is the whole point of
local parity (`10-quality-gates.md`):

- "Static analysis passes" means `./vendor/bin/phpstan analyse` ran green — say so.
- "Tests pass" means you ran the relevant `--filter` suite — name it.
- If you did NOT run the full suite, say that too. "I did not run the full suite, only
  `--filter=CreateGroupActionTest`" is an honest, acceptable report. Claiming "tests pass"
  without running any is not.
- Never mark a probe dimension PASS without evidence (Senior Self-Review rubric —
  `10-quality-gates.md`).

---

## 4. Scope-Stop & Escalation

- **Scope-stop:** if a task's minimal change exposes a bug or smell outside the task, note it to
  the operator instead of fixing it — unless the fix is required for the task to be correct.
- **Escalation triggers** (stop and ask):
  - A pattern with no analogue/snippet exists in the project.
  - The change requires a new architectural layer or dependency.
  - The spec (`MASTER_BUILD_SPECIFICATION.md`) is silent on something material.
  - A destructive/irreversible operation is needed (`19-data-reliability.md`).
  - Human review is required by `10-quality-gates.md` → When Human Review Is Required.
- **Unknown territory** handling is defined in `11-forbidden-behavior.md`: state the problem,
  propose the minimal fix, get confirmation — do not freewheel.

---

## Additional Discipline for Vanilla PHP Work

- **No framework crutches:** this template has no framework-provided auth, ORM, CLI, or
  validation shortcuts — a missing capability is a real decision point, not something to
  paper over with a half-baked copy. Record the gap in the decision log and propose the minimal
  path (library adoption is a project decision — `MASTER_BUILD_SPECIFICATION.md`).
- **Dead-code vigilance:** every class/method/parameter/`rules()`/`$payload`/import must earn its
  place (Necessity Ladder — `03-architecture.md`). Logging an "I left it out" decision is good;
  silently shipping unused members is not.

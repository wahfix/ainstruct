# SYSTEMATIC DEBUGGING — Method Over Guesswork

Debugging is a skill, not an itch to scratch. Even AI agents produce their worst regressions by
"fixing" a bug they did not actually reproduce. Follow the loop below for every bug. Drift costs
more time than the loop.

> [!IMPORTANT]
> A bug is only understood when it is **reproduced first**. Any change made before a reliable
> reproduction is a guess, and guesses get shipped as regressions (see `11-forbidden-behavior.md`
> — refactoring/fixing unrelated to the task is forbidden; a wrong "fix" is still a change).

---

## The Debugging Loop

1. **Reproduce** — get the smallest, deterministic trigger. Exact input, exact state, exact
   request. If you cannot reproduce, you are not debugging — you are guessing. Reproduce faster:
   a unit/feature test that encodes the bug is the best reproduction.
2. **Isolate** — bisect. Narrow the failing input (binary search on payload/params), use the
   query log / stack trace (`09-tools.md`), and compare a working vs failing case. Find the
   **first** broken link, not the loudest symptom.
3. **Hypothesize** — before changing anything, state one falsifiable hypothesis: *"X returns Y
   at this step, and if I verify it the path changes."* Then verify that single point.
4. **Fix minimally** — apply the smallest change that the hypothesis predicts. No collateral
   editing, no refactoring, no "while I'm here".
5. **Verify regression** — run the reproduction again (now green), then run the tests around the
   touched area. "It works on the happy path" is not confirmation — re-run the failing scenario
   from step 1.

## If the Fix Doesn't Work

- **Revert it.** A change that does not clear the reproduction is noise, not progress.
- Re-examine the hypothesis: which part of it was false?
- Loop again from Isolate — do not stack guesses.

---

## Debugging Tools (per `09-tools.md`)

- `php -l` for syntax sanity on a single file.
- Unit tests that encode the bug (`06-testing.md`) — fastest reproduction.
- Runtime logs (read-only) for the failing request.
- Xdebug/profiler locally when profiling is needed — never ship debug output.

---

## Forbidden During Debugging

- ❌ Adding `dd()`/`dump()`/`ray()` to committed code (`11-forbidden-behavior.md`).
- ❌ "Fixing" without reproduction (shotgun edits).
- ❌ Swallowing the error to make the test green (empty `catch`).
- ❌ Refactoring unrelated code while hunting the bug.
- ❌ Changing the test to match the buggy behavior instead of fixing the code.

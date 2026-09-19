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
   query log / stack trace (`09-tools.md` — Debugbar/Telescope), and compare a working vs failing
   case. Find the **first** broken link, not the loudest symptom.
3. **Hypothesize** — before changing anything, state one falsifiable hypothesis: *"X returns Y
   at this step, and if I verify it the path changes."* Then verify that single point.
4. **Fix minimally** — apply the smallest change that the hypothesis predicts. No collaateral
   editing, no refactoring, no "while I'm here".
5. **Verify regression** — run the reproduction again (now green), then run the tests around the
   touched area. "It works on the happy path" is not confirmation — re-run the failing scenario
   from step 1.

## If the Fix Doesn't Work

- **Revert it.** A change that does not clear the reproduction is noise, not progress.
- Adjust the hypothesis — the previous one was falsified; do not stack a workaround on top of an
  unconfirmed cause.
- A "fix" that merely chooses a different error, or that you cannot explain, is not a fix.

---

## Facts Over Guesses

- **Read the actual code path before editing** — trace the call from entry point to the failing
  line; do not pattern-match from memory of similar APIs.
- **Verify with data, not vibes** — inspect SQL binds and executed queries, cache keys, DB state,
  and the real request/response. State what the data actually is before reasoning about why.
- **Check the environment** — env/config/cache squirting stale values, route caching, a wrong
  queue/scheduler state; often the code is right and the environment is wrong.
- **Temporary instrumentation** — add `dump()`/`logger()` only to observe, only around the
  isolated suspect, and **remove it before finishing** (`dd()`/`dump()`/`ray()` is a style
  violation in committed code — `04-coding-standards.md`, `11-forbidden-behavior.md`).
- **Read the exception** — the first meaningful line (framework line ≠ the cause). Type errors,
  "Undefined method/var", and SQLSTATE codes each point at different root-cause classes.

---

## Root-Cause Classes to Check First (Laravel)

| Symptom | Prime suspect | Check |
|---------|---------------|-------|
| Returned value wrong on save/update | Repository `update()` returns `bool` being reassigned | read the actual return type (`canonical-snippets.md` — evidence-anchored) |
| SQL 23000 / data too long | boundary validation missing | `15-edge-cases.md` probes |
| Slow page, many queries | N+1, no pagination | query log (`09-tools.md`) + `13-database.md` |
| 404 on route with id | route-model binding vs `where()` scope mismatch, soft-delete scope | binding + global scopes |
| Model property null "out of nowhere" | missing eager load masking a relation | `->with()` vs loop access |
| Frontend bug but API correct | TS/Inertia contract drift — prop name, shape, date serialization | `14-frontend.md` |
| Everything broke after one change | circular import/instantiation, wrong branch | the last diff, not the symptom |

---

## When to Escalate

If you cannot reach a confirmed hypothesis within a reasonable number of iterations, **stop and
summarize to the operator**: the reproduction, what was ruled out, and the current best
hypothesis — with evidence, not guesses. Ask before continuing. Escalation is discipline, not
failure.

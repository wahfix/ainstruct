# TOOLS — Usage of Terminal, Search, Linters, Tests

This file defines how the agent should use tools: terminal, search, filesystem, linters,
formatters, tests, static analysis, and external tools.

---

## General Tool Principles

1. **Inspect before modifying.** The codebase is the authority. Search before creating.
2. **Reuse before creating.** Before building new reusable components (Actions, Services,
   Repositories, Contracts), thoroughly search the existing codebase for similar or suitable
   implementations.
3. **Evidence-based.** Every pattern you follow MUST be backed by at least one existing example
   in the codebase (or a canonical snippet — `12-project-specific/canonical-snippets.md`).
4. **Minimize scope.** Only modify files directly relevant to the feature.

---

## Terminal Usage

- **Explain modifying commands first** before running them (git operations, installs, etc.).
- Use **non-interactive** versions of commands when available (`composer init --no-interaction`
  instead of `composer init`).
- Avoid commands likely to require user interaction (e.g., `git rebase -i`) — they may hang.
- **External processes/dev servers:** Do not run external development servers. Run them in a
  separate terminal and ask the user to provide relevant data (logs, errors).

---

## Filesystem & Paths

- **Use absolute paths** when referring to files with tools. Relative paths are not supported.
- Follow the current working directory of the shell.
- Never modify files outside the feature's context.

---

## Parallelism

- Execute multiple independent tool calls in parallel when feasible (e.g., searching the codebase).
- Do not run dependent commands in parallel — chain them sequentially.

---

## Linting & Formatting

### PHP

- Format with **Pint** (`./vendor/bin/pint`) or the project's chosen formatter (see
  `composer.json` scripts and `MASTER_BUILD_SPECIFICATION.md`).
- Check only (no write) for verification: `./vendor/bin/pint --test` (when Pint is the formatter).
- Run PHPStan at the level configured in `phpstan.neon` (default level 5; paths per project —
  typically `src/`, `tests/`) before considering work complete: `./vendor/bin/phpstan analyse`.

---

## Composer Scripts (common project tools)

Reference `composer.json` scripts before inventing commands. Common entries used in this kind of
project:

```bash
# Project/CI parity
composer test                     # runs the test suite (see script definition)
composer lint                     # static analysis + style check combined
./vendor/bin/phpstan analyse      # static analysis (level per phpstan.neon)
./vendor/bin/pint --test          # style check only (when Pint is the formatter)
```

**Rule:** When a dev server / long-running process is needed (`php -S`, a queue worker, a mail
catcher), do NOT start it in the agent session — run in a separate terminal and ask the user for
logs (see Background & Interactive Processes).

---

## Debugging & Diagnostics

- Diagnose a running app by asking the user for logs rather than starting the dev server.
- Check runtime logs (project-defined location, e.g. `storage/logs/app.log` or `php://stderr` of
  the entrypoint) via Read for runtime errors; never truncate/delete logs while diagnosing.
- Use `php -l` for quick syntax checks on a single file:

  ```bash
  php -l src/Actions/Group/CreateGroupAction.php
  ```

- Use Xdebug / a profiler locally when profiling is needed — never ship debug output.

---

## Broken/Interactive Tools (avoid)

- Do NOT run `git rebase -i`, `composer init` (interactive), or any command that prompts
  mid-run — they hang the session.
- Do NOT run long-running dev servers (`php -S`, `composer dev`, a queue worker) — see
  Background & Interactive Processes.
- Never add dependencies just to solve a task; check the codebase and `composer.json` first
  (analogue-first, `03-architecture.md`, whitelist in `MASTER_BUILD_SPECIFICATION.md`).

---

## Static Analysis

- Run static analysis before considering work complete (level per `phpstan.neon`):

  ```bash
  ./vendor/bin/phpstan analyse
  ```

- Fix all issues found. Do not proceed while static analysis errors remain.
- The `tests`/`lint` CI workflows re-run these checks on `develop`/`main` — local parity is
  required.

---

## Tests

- **Do NOT run the full test suite** unless explicitly asked.
- When tests ARE run, run only relevant tests:

  ```bash
  ./vendor/bin/phpunit --filter=TestName
  ```

- If tests fail, fix only the code related to the current feature.
- Do NOT mock in Feature/Integration tests (use real wiring — `06-testing.md`).

---

## External Tools

- Use the project's dependency manager (composer for PHP).
- Do NOT modify vendor code without confirmation.
- Do NOT add dependencies without checking whether the codebase already has a suitable
  implementation and whether the package fits the project whitelist
  (`MASTER_BUILD_SPECIFICATION.md`).

---

## Background & Interactive Processes

- Use background processes (`&`) for commands unlikely to stop on their own.
- **Do NOT run long-running dev servers** (`php -S`, `composer dev`, a queue worker). They may
  hang the session. Run them in a separate terminal and ask the user to provide logs/errors.
- If unsure whether a command may hang, ask the user.
- Always inform the user about commands that make system changes before running them.

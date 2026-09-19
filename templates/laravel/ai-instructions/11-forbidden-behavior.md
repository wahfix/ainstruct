# FORBIDDEN BEHAVIOR — Explicit Prohibitions

> [!IMPORTANT]
> **This file is the SINGLE SOURCE OF TRUTH for prohibited behaviors.** Modules 05, 06,
> 07, 10, 15–18, 20, 21 and project-specific files may reference these prohibitions, but the authoritative
> list lives here. When adding/removing a prohibition, update THIS file — do not drift the other
> modules. If another module conflicts with this file, this file wins (see `01-governance.md`
> conflict resolution).

The following behaviors are explicitly forbidden. These are absolute rules unless the user explicitly overrides them for a specific task.

---

## Architecture Violations

- ❌ Writing any code before reading the build specification (root `MASTER_BUILD_SPECIFICATION.md`), or before creating it via detailed operator Q&A when it does not exist.
- ❌ Guessing project specifications (feature names, columns, relations, conventions) that belong in `MASTER_BUILD_SPECIFICATION.md` instead of discussing with the operator.
- ❌ Putting business logic in Controllers.
- ❌ Accessing Eloquent directly from Actions or Controllers (must go through Repository).
- ❌ Creating generic catch-all `Service`/`Helper`/`Utils` classes or empty DTOs without a clear need (a domain-named **Service** layer is legitimate and allowed when warranted — see `03-architecture.md`; it is not forbidden, only gratuitous services are).
- ❌ Creating unnecessary abstractions (only create contracts when multiple implementations or clear need exists).
- ❌ Creating files outside the feature's context.
- ❌ Modifying routes outside the contextual naming convention.
- ❌ Violating dependency direction rules:
  - Model → Repository
  - Repository → Action
  - Action → Controller
  - Controller → Repository directly (must go through an Action)
  - Leaves calling Eloquent directly (Controller/Model → Model::query)
  - Circular dependencies
- ❌ Introducing circular dependencies.
- ❌ Reproducing legacy duplicate class names when a canonical contextual class exists (e.g. creating `CreateResidentAction` alongside `CreateSidResidentAction`).

---

## Style Violations

- ❌ Using PHPDoc `@test` annotations or omitting the `#[Test]` attribute on test methods (`it_…`/`user_can_…` names are fine **with** the attribute).
- ❌ Adding comments unless asked.
- ❌ Writing code that is not self-explanatory — a line that needs a comment to be understood; fix the code (rename/extract/simplify), do not comment it.
- ❌ Adding comments that re-state what the code does (chit-chat, e.g. `// increment total`).
- ❌ Using `declare(strict_types=1)` (project convention).
- ❌ Using tabs for indentation (use 4 spaces).
- ❌ Using Options API in Vue (always Composition API).
- ❌ Skipping TypeScript types in frontend.
- ❌ Using generic/technical names for domain concepts (`TypeService`, `DataHandler`, `Manager`; `Helper.php`, `Utils.php`).

---

## UI & Copy Violations

- ❌ Producing UI copy, user-facing text, or prose output containing AI-slop patterns — marketing buzzwords (the Empty AI Vocabulary list), un-evidenced claims, generic filler — when the anti-slop filter (`.opencode/skills/antislop/SKILL.md` + concern skill) is available; the filter's Delivery Gate MUST run before such output is final (see `10-quality-gates.md`).
- ❌ Silently bypassing the anti-slop gate or allowlisting its findings without justification in the decision log — the filter, when installed, is authoritative for what counts as AI-slop.

---

## Implementation Violations

- ❌ Modifying vendor code without confirmation.
- ❌ Adding dependencies without checking the existing codebase first.
- ❌ Refactoring working code (speculative refactoring).
- ❌ Fixing bugs not directly related to the current task.
- ❌ Doing work directly on `develop` or `main` (create a feature branch from `develop`).
- ❌ Pushing to `main`, or targeting `main` with a PR, outside the approved release flow (main accepts changes only via a green-CI, reviewed release PR from `develop` — see `08-git.md`).
- ❌ Creating empty commits.
- ❌ Pushing secrets or credentials.
- ❌ Using `dd()`, `dump()`, or `ray()` in committed code.
- ❌ Running the full test suite unless explicitly asked.
- ❌ Mocking in Feature tests.
- ❌ Calling an instance Action `handle()` statically (`XxxAction::handle(...)`) — resolve and call via the container on an instance.
- ❌ Calling `$action->execute(...)` — no such method exists on the Action abstraction (`handle()` is the only invocation API).
- ❌ Passing a second argument to `handle()` (`$action->handle($id, $request->all())`) — the extra argument is ignored and the call breaks the array-payload protocol.
- ❌ Passing a scalar payload to a **RuledAction** (`→handle($id)`) — ruled actions require an array payload or they throw `InvalidArgumentException('Payload must be an array.')`; put `'id'`/a Model inside the array instead (`→handle(['id' => $id])`).
- ❌ Reproducing any `// BAD` pattern from `12-project-specific/canonical-snippets.md` §11.
- ❌ Referencing classes/methods that do not exist (broken imports, undefined variables, unknown repository methods). Always verify the target class/method exists in the codebase before using it.
- ❌ Passing payload keys to Actions that do not match the repository/model columns or the RuledAction validation rules.
- ❌ Inventing classes, methods, signatures, or API behavior from memory without opening the real file in the codebase (`canonical-snippets.md` — evidence-anchored programming). "It probably has `update()`" is a violation; verify first.
- ❌ Marking work complete without the Senior Self-Review Rubric (`10-quality-gates.md`) and the edge-case probes (`15-edge-cases.md`) applied to the touched code paths — the honest estimate of "done" includes the boundaries tested. See `16-debugging.md` for the loop a live bug must follow (reproduce → isolate → hypothesize → minimal fix → verify), not shotgun edits.
- ❌ Swallowing exceptions silently (empty `catch`, `catch () { /**/ }`, `->catch(fn () => null)` without a documented reason) — errors are a contract (`20-frontend-and-contracts.md`); an absorbed error without a decision-logged reason is a defect.
- ❌ Hardcoding user-facing strings into code/components instead of lang files (`20-frontend-and-contracts.md` — UI text & i18n).
- ❌ Adding a dependency without audit + justification — existing-alternative check, `composer audit`/`npm audit`, and a decision-log reason; a package duplicating an existing capability is scope creep (`21-state-delivery-environment.md`).
- ❌ Running irreversible database operations (`dropColumn`, lossy type changes, data deletion) without operator confirmation, and writing migrations without a safe `down()` (`19-data-reliability.md`).
- ❌ Building phase N+1 before phase N is verified, or declaring a multi-layer feature complete without per-phase verification (`18-planning-and-safe-change.md` — phase decomposition).

---

## Security Violations

- ❌ Storing plaintext passwords.
- ❌ Exposing sensitive data in responses.
- ❌ Trusting user input without validation.
- ❌ Committing secrets or API keys.
- ❌ Logging passwords, tokens, or sensitive data.
- ❌ Returning raw database errors to users.

---

## Scope Violations

- ❌ Modifying unrelated files.
- ❌ Making speculative changes.
- ❌ Fixing unrelated issues while implementing a feature.
- ❌ Creating controllers or frontend components unless explicitly asked (feature stops at Action layer by default).

---

## Unknown Territory Handling

When you encounter a situation not covered by these instructions:

1. State the problem clearly.
2. Propose the minimal fix.
3. Get user confirmation before proceeding.
4. Do not invent new patterns without evidence from the codebase.

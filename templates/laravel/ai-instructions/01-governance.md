# GOVERNANCE — Priority, Conflict Resolution, Rule Scope

This file defines how rules are prioritized, how conflicts are resolved, and how rule scope is determined.

---

## Source of Truth Hierarchy

When conflicts arise between instructions, resolve by this priority (highest first):

1. **Explicit user instruction** — overrides everything below
2. **Existing canonical project code** — the codebase IS the authority
3. **Project-specific mandatory rules** (invariants, MUST-level rules)
4. **Global engineering rules** (MUST, REQUIRED)
5. **Project conventions** (SHOULD)
6. **Framework conventions** (Laravel, Vue, Inertia defaults)
7. **Generic best practices** — only when project is silent
8. **AI default behavior** — last resort

**Build specification:** `MASTER_BUILD_SPECIFICATION.md` at the project root is the authoritative
project definition (LEVEL 2 — project-specific mandatory). It overrides all framework, global,
and best-practice rules below it, and can itself only be overridden by an explicit user
instruction (LEVEL 1). If the file does not exist, create it via detailed operator Q&A before
any code (see root `ai-instructions.md`, section 12).

---

## Rule Scope Determination

Before applying a rule, determine its scope:

### GLOBAL Rules

Apply to every project and every task. Examples:

- Inspect before modifying
- Preserve existing behavior
- Minimize unrelated changes
- Never guess when evidence is available
- Never write code before reading the build specification (`MASTER_BUILD_SPECIFICATION.md`); create it via operator Q&A if missing

### UNIVERSAL Rules

Apply to all projects using this instruction system. Examples:

- Follow the mandatory workflow
- Run static analysis before considering work complete
- Use conventional commit messages

### PROJECT-SPECIFIC Rules

Apply only when the current repository matches specific conditions. Examples:

- A loan system may require all monetary values stored as INTEGER
- A regional product may require UI text in a specific language
- A finance project may mandate a specific service layer

### MODULE Rules

Apply only to a specific part of the codebase. Examples:

- Authentication module: always use Laravel Breeze patterns
- Finance module: all mutations wrapped in DB::transaction()

### LANGUAGE Rules

Apply only to a specific programming language. Examples:

- PHP: PSR-12 standard
- TypeScript: semicolons required
- Vue: always use Composition API

### FRAMEWORK Rules

Apply only to a specific framework. Examples:

- Laravel: use auto-resolution over manual binding
- Inertia: use Inertia::render for page responses
- Vue: use <script setup lang="ts">

---

## Conflict Resolution Protocol

### Step 1: Identify the conflict

State both rules clearly with their sources and scopes.

### Step 2: Determine priority

Which rule has higher priority in the Source of Truth Hierarchy?

### Step 3: Check specificity

More specific rules override more general rules.

### Step 4: Check scope

Narrower scope rules override broader scope rules.

### Step 5: Check explicit overrides

Does one rule explicitly mention or override the other?

### Step 6: Apply safety principle

If unresolved, prefer the rule that preserves:

1. Data integrity
2. Financial correctness
3. Security
4. Auditability
5. User experience

### Step 7: Escalate

If still unresolved, stop and ask the user. Do not resolve arbitrarily.

---

## Unresolved Conflicts

If a conflict cannot be resolved through the protocol above:

1. Document the conflict with both rules quoted.
2. State the reason it cannot be resolved.
3. Ask the user for a decision.
4. Record the decision and the reasoning.

Do NOT silently choose one side. Do NOT invent a compromise not supported by the sources.

---

## Invariant Rules

Some rules are marked as INVARIANT or IMMUTABLE. These rules:

- Cannot be overridden by any rule at a lower priority level.
- Can only be changed by explicit user instruction at LEVEL 1.
- Are marked with `[INVARIANT]` in their definition.
- Must be preserved across all projects where they apply.

---

## Retroactive Rule Application

New rules discovered during a task do NOT retroactively invalidate work already completed under previous rules. However:

- If the new rule is at a higher priority level, apply it to remaining work.
- If the new rule is at the same or lower priority, the existing work stands.
- If the new rule is INVARIANT, stop and reassess all completed work.

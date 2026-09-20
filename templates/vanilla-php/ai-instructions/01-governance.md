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
6. **Template conventions** (vanilla-php canonical decisions — stack, whitelist, OOP architecture)
7. **Generic best practices** — only when project is silent
8. **AI default behavior** — last resort

**Build specification:** `MASTER_BUILD_SPECIFICATION.md` at the project root is the authoritative
project definition (LEVEL 2 — project-specific mandatory). It overrides all template, global,
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
- All code is class-based (OOP); constructor injection via container

### TEMPLATE Rules

Apply to every project that uses the **vanilla-php** template. These are **canonical template
decisions** — deliberate architectural choices, NOT evidence from any specific repository. They
stand until a project overrides them explicitly in `MASTER_BUILD_SPECIFICATION.md` or via a
project-specific module. Examples:

- Stack: PHP ^8.2 + Composer + PSR-4, no framework
- Whitelist: `illuminate/container` (MUST); `illuminate/support` (MAY)
- Layer architecture: Entry → Action → Repository → (Entity/Model); Service layer when warranted
- No direct data access outside Repository

A TEMPLATE rule is a **declared decision, not a repo fact**. When a project's existing code
contradicts a template rule, the project's actual code (item 2) wins and the deviation is
recorded in the decision log (`17-agent-discipline.md`).

### PROJECT-SPECIFIC Rules

Apply only when the current repository matches specific conditions. Examples:

- A loan system may require all monetary values stored as INTEGER
- A project may choose a specific PSR-4 root namespace (`src/` mapping)
- A project may mandate a specific database layer (PDO vs query builder)

### MODULE Rules

Apply only to a specific part of the codebase. Examples:

- Auth module: always use the project's session + password_hash pattern
- Billing module: all mutations wrapped in a transaction

### LANGUAGE Rules

Apply only to a specific programming language. Examples:

- PHP: PSR-12 standard
- HTML: semantics over divs
- CSS: utility/class conventions

### CONDITIONAL Rules

Apply only when the project satisfies a condition. Examples:

- `13-database.md`: applies when the project uses a database
- `14-frontend.md` / `20-frontend-and-contracts.md`: apply when the project has a frontend

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

For the vanilla-php template, the following are INVARIANT until a project explicitly overrides
them via `MASTER_BUILD_SPECIFICATION.md`:

- All code is class-based; no procedural code outside a class.
- Constructor injection for all dependencies; no `new` in consumers except value objects/factories.
- No direct data access outside Repository.
- No dead code: no method/parameter/`rules()`/`$payload` that is not used by the use case.

---

## Retroactive Rule Application

New rules discovered during a task do NOT retroactively invalidate work already completed under previous rules. However:

- If the new rule is at a higher priority level, apply it to remaining work.
- If the new rule is at the same or lower priority, the existing work stands.
- If the new rule is INVARIANT, stop and reassess all completed work.

---

## Evidence Honesty for Template Rules

Because the vanilla-php template is a **template-level decision set** (not a snapshot of an
existing repository), TEMPLATE-scope rules and the canonical snippets in
`12-project-specific/canonical-snippets.md` are **declared canonical forms**. They are the
reference shape to copy until the project's own code establishes a different precedent.

When a project's repository already contains a real implementation of a pattern, the repository
becomes the highest evidence source (item 2). Do not claim a template snippet is "verbatim from
the repo" — it is the declared template form. The distinction matters for honest verification
(`17-agent-discipline.md`).

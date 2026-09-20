# AGENT WORKFLOW — Mandatory Development Process

Every code change MUST follow this workflow. Do not skip steps. Do not reorder steps.

---

## Workflow Steps

```
READ BUILD SPECIFICATION
    ↓
UNDERSTAND
    ↓
INSPECT
    ↓
FIND ANALOGUES
    ↓
PLAN
    ↓
IMPLEMENT
    ↓
STATIC ANALYSIS
    ↓
TEST (only if asked)
    ↓
DIFF REVIEW
    ↓
STYLE REVIEW
    ↓
FINALIZE
```

---

## Step 0: READ BUILD SPECIFICATION

Before anything else (even UNDERSTAND), the project's build specification MUST be available:

1. Read `MASTER_BUILD_SPECIFICATION.md` at the project root (see root `ai-instructions.md`, section 12).
   Treat it as the authoritative, precise project definition: names, features, database design, conventions, dependencies, business flows.
2. **If the file does not exist: STOP.** Do not guess. Ask the operator/programmer detailed questions
   (features, entities, DB design, conventions), then create `MASTER_BUILD_SPECIFICATION.md` completely,
   in detail, and precisely. Confirm it with the operator before considering it valid.
3. Only proceed to UNDERSTAND once the specification is read (or created and confirmed).

**Violation equals total failure** — never write code without the build specification.

---

## Step 1: UNDERSTAND

Before writing any code:

0. `MASTER_BUILD_SPECIFICATION.md` at the project root has been read (or created via detailed
   operator Q&A) — Step 0. Never skip it.
1. Read the user's request completely. Do not assume intent.
2. Identify the domain context (if applicable: project-specific contexts from the spec).
3. Identify which layers are involved (Entry, Action, Service, Repository, Entity/Model, Contracts).
4. Understand what the feature is supposed to do.

**Decision tree:**

```
Is MASTER_BUILD_SPECIFICATION.md present at the project root?
├── NO → STOP. Ask the operator detailed questions, create the file
│        (complete, detailed, precise), get confirmation, then continue.
└── YES (or created) → Proceed.

Is this a new feature or modification?
├── New feature
│   ├── Does the project have an existing analogue for this feature type?
│   │   ├── YES → Go to Step 3 (Find Analogues)
│   │   └── NO → Identify required layers, check template conventions
│   └── Does it need validation?
│       ├── YES → Use RuledAction (implements RuledActionContract, define rules())
│       └── NO → Use regular Action (NO rules() method — never add dead code)
├── Modification
│   ├── Which files are affected?
│   └── Is the modification within scope of the original feature?
└── Bug fix
    ├── Can you reproduce the issue?
    └── What is the root cause?
```

**Necessity rule (see `03-architecture.md`):** whether an Action needs `rules()`/`$payload`
depends on the use case, NOT on the template shape. A use case without validation does NOT get a
`rules()` method. A handler that does not read input does NOT take `$payload`. Every class is
still a class (OOP is mandatory); only the method weight adapts.

---

## Step 2: INSPECT

Search the codebase for existing patterns. The codebase is the authority.

```
1. Check MASTER_BUILD_SPECIFICATION.md for the feature's shape
2. Check src/ (or project root) for contexts/domains
3. Check existing entry points (public/index.php, bin/, CLI commands)
4. Check existing actions in the context
5. Check existing services and repositories
6. Check existing entities/models
7. Check existing tests
```

**Rule:** ALWAYS inspect before creating. Never create something that already exists.

---

## Step 3: FIND ANALOGUES

Find the closest existing implementation to what you need to build. If no analogue exists in the
project's code, use the canonical snippets in `12-project-specific/canonical-snippets.md` as the
template form.

**Decision tree:**

```
What type of feature are you building?
├── CRUD feature → Look at the canonical CRUD example (bank snippets)
├── Validation needed → RuledAction form (bank: ruled action)
├── Read-only/list → Plain Action form (bank: plain action)
├── Cross-cutting logic → Service form (bank: service)
├── Data access → Repository form (bank: repository)
└── Custom feature → Find the closest analogue and adapt
```

The analogue determines your implementation pattern. Follow it character-for-character.

---

## Step 4: PLAN

Before writing code, plan:

1. List every file that needs to be created or modified.
2. Identify the exact patterns to follow from the analogue.
3. Note any deviations and justify them.
4. Ensure scope discipline: no unrelated changes.

**Scope discipline rule:** Unless explicitly asked, implementation should stop at the Action
layer. Do not create entry handlers, CLI commands, or frontend components unless explicitly
requested.

---

## Step 5: IMPLEMENT

Write code following the analogue exactly:

1. Create/modify files in order of dependency: Entity/Model → Repository → Service → Action → Entry.
2. Follow naming conventions exactly.
3. Match code style character-for-character.
4. Use existing base classes, contracts, and traits.
5. Do NOT add comments unless asked.
6. Do NOT add types/parameters not present in the analogue unless the use case requires them.
7. OOP is mandatory: every use case is a class; do NOT fall back to procedural code.

---

## Step 6: STATIC ANALYSIS

Run the project's static analysis tool:

```bash
./vendor/bin/phpstan analyse
```

Fix any issues found. Do not proceed until static analysis passes.

---

## Step 7: TEST (only if explicitly asked)

If the user asks to run tests:

1. Run only the relevant test file(s):

   ```bash
   ./vendor/bin/phpunit --filter=TestName
   ```

2. Do NOT run the full test suite unless explicitly asked.
3. If tests fail, fix only the code related to the current feature.

---

## Step 8: DIFF REVIEW

Review all changes:

1. Verify only intended files were modified.
2. Verify no unrelated code was changed.
3. Verify patterns match analogue code.
4. Verify naming conventions are followed.
5. Verify no secrets or sensitive data were added.

---

## Step 9: STYLE REVIEW

Verify code style:

1. PHP: PSR-12 compliance (`./vendor/bin/pint` / php-cs-fixer as configured).
2. Import ordering matches project convention.
3. Indentation is consistent (check .editorconfig).
4. No dead code: no unused imports, unused parameters, or methods nothing calls.

---

## Step 10: FINALIZE

Before considering work complete, verify ALL of the following:

1. All files created/modified are necessary.
2. Code follows all project conventions.
3. Static analysis passes.
4. No speculative changes were made.
5. Scope is limited to the requested feature.
6. Self-audit against the Master Self-Audit Checklist (root `ai-instructions.md`, section 10).

---

## Feature Implementation Checklist (CRUD)

For a new CRUD feature:

- [ ] Entity/Model created (structure + behavior as specified)
- [ ] Repository created (query methods the feature needs; no empty shells)
- [ ] Actions created: Create, Update, Delete, Get, GetAll (as needed)
- [ ] RuledActions have `rules()` method (ONLY if validation needed)
- [ ] Services created only if cross-cutting logic is reused
- [ ] Entry point created (thin — delegates to Actions) if explicitly requested
- [ ] Frontend created (if requested): Index, Create, Edit, Show
- [ ] Tests created (if asked)
- [ ] No dead code: no method/parameter/`rules()`/`$payload` that is not used

---

## When Encountering Issues

If you encounter a clear technical blocker that prevents completing the task:

1. State the problem clearly.
2. Propose the minimal fix required.
3. Get user confirmation before fixing.
4. Focus on making existing code work as intended.
5. Do not introduce new patterns or make large refactors.
6. Do not fix issues that are not directly related to the current task.

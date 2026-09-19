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
2. Identify the domain context (if applicable: SID, Web, System, or project-specific context).
3. Identify which layers are involved (Controller, Action, Repository, Model, Frontend).
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
│   │   └── NO → Identify required layers, check conventions
│   └── Does it need validation?
│       ├── YES → Use RuledAction (implements RuledActionContract)
│       └── NO → Use regular Action
├── Modification
│   ├── Which files are affected?
│   └── Is the modification within scope of the original feature?
└── Bug fix
    ├── Can you reproduce the issue?
    └── What is the root cause?
```

---

## Step 2: INSPECT

Search the codebase for existing patterns. The codebase is the authority.

```
1. Check routes → routes/web.php (or equivalent)
2. Check existing controllers in the context
3. Check existing actions in the context
4. Check existing repositories
5. Check existing models
6. Check existing Vue/frontend pages
7. Check existing tests
```

**Rule:** ALWAYS inspect before creating. Never create something that already exists.

---

## Step 3: FIND ANALOGUES

Find the closest existing implementation to what you need to build.

**Decision tree:**

```
What type of feature are you building?
├── CRUD feature → Look at the canonical CRUD example in the project
├── SID feature → Look at the SID canonical example
├── Taxonomy/group feature → Look at the group-based canonical example
├── System action → Look at the Ensure pattern canonical example
├── Navigation/menu feature → Look at the sidebar menu canonical example
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

**Scope discipline rule:** Unless explicitly asked, implementation should stop at the Action layer. Do not create controllers or frontend components unless explicitly requested.

---

## Step 5: IMPLEMENT

Write code following the analogue exactly:

1. Create/modify files in order of dependency: Model → Repository → Action → Controller → Frontend.
2. Follow naming conventions exactly.
3. Match code style character-for-character.
4. Use existing base classes and traits.
5. Do NOT add comments unless asked.
6. Do NOT add types not present in analogue code.

---

## Step 6: STATIC ANALYSIS

Run the project's static analysis tool:

```bash
# PHP projects
./vendor/bin/phpstan analyse

# If the project uses a different tool, use that tool instead.
```

Fix any issues found. Do not proceed until static analysis passes.

---

## Step 7: TEST (only if explicitly asked)

If the user asks to run tests:

1. Run only the relevant test file(s):

   ```bash
   php artisan test --filter=TestName
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

1. PHP: PSR-12 compliance.
2. TypeScript: Prettier formatting.
3. Vue: Consistent component structure.
4. Import ordering matches project convention.
5. Indentation is consistent (check .editorconfig).

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

- [ ] Route added in correct route file with proper prefix/naming
- [ ] Model created with fillable, casts, relationships
- [ ] Repository created (extending ModelRepository or equivalent base)
- [ ] Actions created: Create, Update, Delete, Get, GetAll (as needed)
- [ ] RuledActions have `rules()` method (if validation needed)
- [ ] Controller created (thin — delegates to Actions)
- [ ] Frontend pages created (if requested): Index, Create, Edit, Show
- [ ] Breadcrumbs defined (if applicable)
- [ ] Tests created (if asked)
- [ ] Factory created (if needed for tests)
- [ ] Seeder updated (if system data needed)

---

## When Encountering Issues

If you encounter a clear technical blocker that prevents completing the task:

1. State the problem clearly.
2. Propose the minimal fix required.
3. Get user confirmation before fixing.
4. Focus on making existing code work as intended.
5. Do not introduce new patterns or make large refactors.
6. Do not fix issues that are not directly related to the current task.

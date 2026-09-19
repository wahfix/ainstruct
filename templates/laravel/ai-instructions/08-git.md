# GIT — Branching, Commits, Version Control

This file defines git branching and commit conventions for LingSID. The default/mainline branch is **`develop`**; `main` holds verified releases.

---

## Branching

### Universal Rules

- **`develop`** is the default integration branch (verified, tested code).
- **`main`** is the protected release branch.
- **DO NOT** commit, push, or merge directly to `develop` or `main`.
- **`main` accepts changes ONLY via an approved PR** from `develop` (or a `hotfix/…` branch when a release is in flight) with all CI status checks green — see «Release to Main» below.
- Each feature/bugfix gets its own short-lived branch created from `develop`.
- Do NOT push or open a PR unless explicitly asked.

### Branch Base Decision Tree

```
What are you working on?
├── A new feature → create branch from develop:  feat/{short-description}
├── A bug fix     → create branch from develop:  fix/{short-description}
├── Refactor/tests/docs/chore → branch from develop
└── A hotfix      → create branch from the affected release (only when one is in flight)
            └── hotfix/{short-description} → landed via `develop`, released to `main` via release PR
```

### Branch Workflow (Standard)

```
1. git checkout develop
2. git pull origin develop
3. git checkout -b feat/{short-description}
4. ... work ...
5. git add <intended files only>
6. git commit -m "feat: concise description"
7. (do NOT push unless asked)
```

---

## Commits

### Required Discipline

- Inspect `git status`, `git diff`, and `git log` before committing.
- Stage only intended files; never commit secrets or artifacts.
- Do not `git add .` blindly — review what is staged.
- Write a concise summary commit message matching repo style.
- **DO NOT** create empty commits.

### Prohibited Operations

- Committing directly to `develop` or `main`.
- Pushing to `main`, or creating a PR that targets `main`, outside the approved release flow (see «Release to Main»).
- Force-pushing.
- Pushing secrets or credentials.
- Committing generated/vendor files (`node_modules`, `vendor`, build output, `.env`).
- Amending commits unless explicitly asked.

---

## Commit Message Format (Conventional Commits)

**Format:**

```
<type>(<optional scope>): <concise description>
```

**Allowed types:**

| Type | Purpose | Example |
|------|---------|---------|
| `feat` | New feature | `feat: add article management feature` |
| `fix` | Bug fix | `fix: pass validated payload to create article action` |
| `refactor` | Refactor without behavior change | `refactor: extract resident update into action` |
| `test` | Add/fix tests | `test: cover circular membership guard` |
| `docs` | Documentation | `docs: update instruction system` |
| `chore` | Maintenance/deps | `chore: bump laravel/pint` |
| `style` | Formatting, no logic change | `style: pint format actions` |

**Rules:**

1. Description concise and specific (imperative mood).
2. Lowercase type and description; no period at the end.
3. Max ~72 characters for the subject line.
4. Use precise domain terms (`feat: add sid residents export`, not `feat: do stuff`).

**Examples:**

```
feat: add sid residents index page
fix: guard against circular group membership
refactor: move article validation into ruled action
```

---

## CI (GitHub Actions)

- `.github/workflows/` runs on push/PR to `develop` and `main`:
  - **tests** — PHPUnit suite.
  - **lint** — frontend ESLint/Prettier checks.
- Local work must pass the same checks the CI runs before a merge is expected: PHPStan level 5, `laravel/pint`, `bun run lint`, `bun run format:check`.

---

## Release to Main (Branch Protection)

`main` is a **protected release branch**. No code reaches `main` outside the flow below:

```
feature branch (feat/…, fix/…, hotfix/…)  →  PR →  develop  →  release PR →  main
```

**MUST:**

- Changes enter `develop` via PR from a short-lived feature/bugfix branch.
- `main` accepts changes ONLY via a **release PR** from `develop` — never from a feature branch, and never by direct push/merge.
- A release PR to `main` requires **all CI status checks green** (`tests` + `lint`) and at least **one human review** (see `10-quality-gates.md` → When Human Review Is Required).
- Hotfixes land in `develop` first (so `main` never diverges from `develop`), then flow to `main` via a release PR. A hotfix targets `main` only when the release is already in flight and the fix MUST ship immediately — and even then via an approved PR, never a direct push.
- After a release, `develop` is fast-forwarded/re-tagged so it stays the ancestor of `main` (`main` ⊆ history of `develop`, never diverged).

**MUST NOT:**

- `git push origin main` — direct push to `main` is a violation.
- Merging `main` into `develop` by hand to "sync"; use a normal `develop`-to-`main` release PR.
- Overriding failed CI checks to merge a release PR.

### Enforcement (GitHub branch protection)

If the repository lives on GitHub, the following protection settings on `main` are the **machine enforcement** of the rules above and MUST be configured (by the operator) on the repo settings page — do not rely on instructions alone:

- [ ] **Require a pull request before merging** — required approvals: 1.
- [ ] **Require status checks to pass before merging** — require the `tests` and `lint` workflows.
- [ ] **Do not allow bypassing the above settings** — unchecked "Do not allow bypassing the above settings".
- [ ] **Do not allow force pushes** and **Do not allow deletions** on `main`.
- [ ] **Restrict who can push to `main`** (only release managers / CI bot).
- **Solo-operator adaptation:** with no second reviewer (personal repo), set required approvals
  to `0` but KEEP "Require a pull request before merging", "Do not allow bypassing the above
  settings", no force pushes, and no deletions. Direct pushes to `main` stay blocked; the
  operator reviews and merges their own PRs. This is the minimum viable enforcement — team
  repos should keep `1` approval + status checks.
- Apply the same require-status-checks protection to `develop` when direct-push discipline needs backing.

---

## New Project Initialization

When creating a new project from scratch, initialize git first (`git init`) with `develop` as the primary branch before any feature work. Do not start committing onto `main`/`master` directly.

---

## Speculative Refactoring Policy

- **DO NOT refactor working code** — only fix what is broken for the current feature.
- **DO NOT fix issues** not directly related to the current task.
- **DO NOT "improve"** existing code during a feature implementation.

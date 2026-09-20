# FRONTEND — Conditional (applies only when the project has a frontend)

This module applies **only when the project has a frontend layer**. The template does not lock
the frontend stack (server-rendered templates, a vanilla JS SPA, or a framework are all project
decisions). The chosen stack is recorded in `MASTER_BUILD_SPECIFICATION.md` — never invent it.

> [!IMPORTANT]
> These rules apply only to the project's frontend, whatever it is. If the project is backend-only,
> this module is inert. When a frontend exists, the backend still owns business logic and
> validation; the frontend is a presentation/contract consumer (`20-frontend-and-contracts.md`).

---

## Stack (Binding)

The frontend stack is a **project decision**, recorded in `MASTER_BUILD_SPECIFICATION.md`:
server-rendered templates (e.g. plain PHP templates), a JS SPA, or a hybrid. The template only
binds the following invariants:

- **Business logic lives in the backend** (Actions/Services). Templates/JS never implement domain
  rules that the backend does not own.
- **Validation is authoritative server-side.** Frontend validation is convenience, not security.
- **The frontend consumes a contract** — payload shapes come from the backend
  (`20-frontend-and-contracts.md`), never invented client-side.

---

## Directory Organization (illustrative — per project decision)

When a frontend exists, follow the project's recorded layout (e.g. `public/` for served
templates/assets, `resources/` for source assets). Examples:

```
public/
├── index.php            ← front controller (or template entry)
├── assets/              ← compiled/served static assets
└── uploads/             ← only non-sensitive public uploads (07-security.md)
```

```
resources/
├── views/               ← templates (if server-rendered)
├── js/                  ← JS source (if a frontend build exists)
└── css/                 ← CSS source
```

**Rules:**

- Templates/views MUST NOT contain business logic — only presentation and the contract values the
  backend provides.
- User input rendered into output is **escaped** (never raw) — XSS prevention
  (`07-security.md`).
- Static assets are served from the project's public path; never expose private files.

---

## Frontend Conventions (per project decision)

When a frontend stack exists, its specific conventions (framework, component structure, styling
system, build tool) are defined in `MASTER_BUILD_SPECIFICATION.md` and `14-frontend.md`-adjacent
project modules. Universal rules:

- Same code style discipline as the backend: no dead code, self-explanatory naming, no comments
  unless asked (`04-coding-standards.md` applies to JS/TS/CSS by analogy).
- No client-side duplication of server-side business rules (single source of truth is the
  backend).
- Accessibility is not optional for user-facing surfaces (`20-frontend-and-contracts.md`).

---

## When a Frontend Does Not Exist

Do NOT invent a frontend layer for a backend-only feature. Feature work stops at the Action layer
by default (`03-architecture.md` — Architectural Decision #6); build frontend pieces only when
explicitly requested and the stack is recorded.

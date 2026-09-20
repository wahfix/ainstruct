# ARCHITECTURE — Patterns, Layers, Boundaries

This file defines the architectural patterns, layer responsibilities, and dependency rules of a
**vanilla PHP** project: PHP ^8.2 + Composer + PSR-4, no full framework, with
`illuminate/container` as the DI container (`illuminate/support` — `Str`, `Collection` —
permitted). The architecture is class-based end to end: every use case is an `Action`, data
access lives in `Repository` classes, cross-cutting logic lives in domain-named `Service`
classes, and architectural contracts are part of the design (a contract exists even when it has
one implementation).

---

## Layer Architecture

```
Entry (HTTP handler / CLI command — thin)
    ↓
Action (business logic orchestration + validation)
    ↓
Repository (data access — the ONLY layer allowed to touch the data layer)
    ↓
Entity / Model (data structure, invariants)
```

A **Service layer** is equally legitimate and equally important as Actions — it is introduced
whenever business logic is genuinely cross-cutting or reused across use cases. See the
**Service Layer** section below.

### Entry Layer

- **Responsibility:** Handle the transport (HTTP request, CLI argv), delegate to an Action or
  Service, return the response.
- **Terminology:** in an HTTP project the entry is a front controller + handler classes
  (`public/index.php` + `src/Entry/Http/...`); in a CLI project it is `bin/...` scripts that
  resolve an Action from the container and call it.
- **Rules:**
  - Entry classes MUST be thin — no business logic.
  - Entry CAN handle authentication and authorization for HTTP transport.
  - Entry MUST NOT validate business data inline — validation lives in RuledActions.
  - Entry MUST NOT access the data layer directly.
  - Entry MUST NOT call Repositories directly (only via Actions/Services).
  - Use constructor injection for the Actions an entry needs; resolve them from the container
    (composition root — see Bootstrap section).
  - Entry passes the whole user input to Actions as a **single array payload**:
    `$action->handle($input)` (or `['id' => $id] + $input` for model-aware updates). Never pass
    a second argument.

### Action Layer

- **Responsibility:** Orchestrate business logic, validate input, coordinate between Repositories.
- **Rules:**
  - Actions orchestrate — they call Repositories, other Actions, and, when warranted, Services.
  - Actions MUST NOT handle authentication/authorization.
  - Actions with validation implement `RuledActionContract` and define `rules(array $payload): array`.
  - Actions without validation are plain Actions — they MUST NOT define a `rules()` method or
    receive a `$validatedPayload` parameter they do not use (no dead code — Necessity Ladder below).
  - Actions return entities, collections, arrays, or primitives — never responses.
  - Actions inject Repositories via constructor.
  - Actions live under `src/Actions/{Context}/…` and extend `src/Abstractions/Action` (or
    `IndexAction` for list actions).
  - Ruled actions receive validated data as the second `handler($payload, $validatedPayload)`
    argument; plain actions receive only what the use case needs.

### Repository Layer

- **Responsibility:** Data access only — query the chosen data layer (PDO, query builder, an
  external API, in-memory store), return entities/collections/records.
- **Rules:**
  - Repositories are the ONLY layer that talks to the data layer. Direct queries anywhere else
    are forbidden.
  - Extend `src/Abstractions/Repository/Repository` for standard CRUD, or implement
    `RepositoryContract` when a custom shape is needed.
  - Add custom query methods on the concrete repository when needed.
  - A project with **no data access yet** has no repositories; the moment data access exists,
    repositories are REQUIRED (see Necessity Ladder).

### Entity / Model Layer

- **Responsibility:** Define data structure, invariants, and domain behavior that belongs to the
  record itself.
- **Rules:**
  - Entities are plain PHP classes (readonly or with private constructor + named constructors
    where beneficial).
  - Entities MAY validate their own invariants on construction.
  - Entities do NOT contain orchestration or data-access logic.
  - A project that stores raw arrays/records without a dedicated entity class MAY skip this layer
    until records gain behavior; introducing it is a project decision recorded in
    `MASTER_BUILD_SPECIFICATION.md`.

---

## Service Layer (mandatory when warranted)

A **Service layer is required the moment logic is cross-cutting** — shared across multiple
Actions/aggregates, spans several repositories, coordinates transactions, or integrates external
systems. Single-concern use-case logic stays in Actions (the default).

- **When to use:** business logic that is reused by more than one use case, coordinates
  transactions spanning several repositories, or integrates external systems. If you are about
  to copy the same logic into a second Action, that logic belongs in a Service instead.
- **Naming & placement:** name it for its domain (`ArticleService`, `ResidentReportService`),
  put it in the matching context directory (`src/Services/{Context}/…`). NEVER create a generic
  `Service.php` / `Helper.php` / `Utils.php`.
- **Rules (same as Actions):** constructor injection; no data-access directly (delegate to
  Repositories); no business logic in Entry classes.
- **Dependency direction:** a Service MAY call Repositories, other Services, and Actions.
  Entry MAY delegate to Services instead of Actions — Services and Actions are peer layers.
- **Data passing:** entities/arrays by default.
- This is a **mandatory** allowance when warranted: introduce Services with a real need, never
  gratuitously, but never copy-paste logic either.

---

## Contracts (part of the architecture DNA)

Architectural contracts are first-class citizens of this template. They define the shape of the
layers and exist **even when there is a single implementation** — that is the point: they are the
architecture's vocabulary, not a speculative abstraction.

**Canonical (always present):**

| Contract | Shape | Purpose |
|----------|-------|---------|
| `src/Contracts/Action/ActionContract.php` | `handle(...)`, `handler(...)` | Base action shape; implemented by the `Action` base class |
| `src/Contracts/Action/RuledActionContract.php` | `rules(array $payload): array` | Validation-enabled action shape |
| `src/Contracts/Action/IndexActionContract.php` | list-returning action shape | Collection-returning actions |
| `src/Contracts/Repository/RepositoryContract.php` | CRUD + query methods | Data-access shape |

**Rule:** ad-hoc contracts beyond the canonical set are still created only when they earn their
place (a second implementation, a boundary the architecture must enforce, or a domain seam). The
canonical set is what makes contracts DNA; inventing a new contract per use case is ceremony.

---

## File Structure (Action / Repository / Service)

All application layers follow **context-based organization** — mirror domain context, not
technical type-first:

```
src/
├── Abstractions/                 ← base classes the layers extend
│   ├── Action.php                (base Action class — handle()/handler())
│   ├── IndexAction.php           (base for list actions)
│   └── Repository/Repository.php (base Repository class)
├── Contracts/                    ← canonical architecture contracts (see above)
│   ├── Action/
│   │   ├── ActionContract.php
│   │   ├── RuledActionContract.php
│   │   └── IndexActionContract.php
│   └── Repository/RepositoryContract.php
├── Actions/{Context}/…          ← use-case orchestration (default for single-concern logic)
├── Services/{Context}/…         ← cross-cutting / shared domain logic (required when warranted)
├── Repositories/{Context}/…     ← data access (the ONLY layer that touches the data layer)
├── Entities/{Context}/…         ← data structure + invariants (when the project uses entities)
├── Entry/…                      ← thin transport handlers (HTTP handlers, CLI commands)
└── Bootstrap/                   ← composition root (container wiring)
```

Contexts are project-defined (e.g. `Auth`, `Billing`, `Catalog`). Contexts and the PSR-4 root
namespace must be recorded in `MASTER_BUILD_SPECIFICATION.md`.

**Composition rules:**

- A Service MAY call Repositories, other Services, and Actions — it composes several
  services/actions/repositories for one cohesive workflow.
- An Action stays single-concern (one use case) and calls Repositories (and other Actions).
- A Repository is always the bottom application layer for data — Services and Actions both
  delegate data access to Repositories.
- Entry delegates to either an Action or a Service (never to a Repository directly).

---

## Dependency Direction Rules

```
Entry → Action → Repository
Entry → Service → Repository / Action / Service
Action → Repository
Action → Action (orchestration)
Action → Service (when the logic is shared)
Service → Repository, Service → Action, Service → Service
```

**FORBIDDEN:**

- Entity → Repository
- Repository → Action
- Action → Entry
- Entry → Repository directly (must go through an Action or Service)
- Any data-layer access outside a Repository (raw PDO/query outside Repository classes)
- Any circular dependencies

---

## Bootstrap (Composition Root)

Dependency injection is done via `illuminate/container` (`Illuminate\Container\Container`).
The composition root is the only place that decides how the graph is built:

- **Auto-resolution is the default.** Constructor parameters that can be resolved from the
  container (concrete classes) are auto-resolved — no binding needed.
- **Explicit binding only where needed** (interface → implementation, primitive parameters,
  values from environment). Bindings live in `src/Bootstrap/` and are recorded in
  `MASTER_BUILD_SPECIFICATION.md`.
- Entry scripts resolve from the container (`$container->get(SomeAction::class)`) — never build
  the graph by hand in an entry handler.
- Consumers receive dependencies via constructor injection; `new` inside a consumer is only
  allowed for value objects and factories, never for wired services.

---

## Necessity Ladder (anti-dead-code — normalize on the actual use case)

Classes and layers are **obligatory**; the weight of each class adapts to the use case. Use this
ladder every time you plan a class:

```
USE CASE
├── Validates input?
│   ├── YES → RuledAction: rules(array $payload): array + handler($payload, $validatedPayload)
│   └── NO  → Plain Action: NO rules() method, NO $validatedPayload parameter
├── Reads input data?
│   ├── YES → handler($payload) receives the payload
│   └── NO  → handler() with no parameter (list/dashboard style use cases)
├── Accesses data (DB, API, files)?
│   ├── YES → a Repository exists for that data source; all access goes through it
│   └── NO  → no Repository invented for the sake of it
├── Logic reused across use cases?
│   ├── YES → extract into a domain-named Service
│   └── NO  → keep it in the Action (single-concern default)
└── Every member justified?
    ├── Method/param/import/rule/payload used by this use case → keep
    └── Anything unused → REMOVE (dead code is forbidden, see 11-forbidden-behavior.md)
```

The ladder decides **method weight**, never class existence: a use case with no validation is
still a class; a handler that reads no input still has a `handler()`. Only the signatures adapt.

---

## Abstraction Philosophy

**DO:**

- Use the base `Action` / `IndexAction` classes for business logic.
- Use a domain **Service** layer when a use case spans multiple Actions/aggregates or is shared
  across contexts — required when warranted, never gratuitous.
- Copy canonical snippets from `12-project-specific/canonical-snippets.md` (or the project's own
  analogues), changing only identifiers/values.
- Use the base `Repository` for data access; the Repository layer is the ONLY data-access layer.
- Use `RuledActionContract` when the action needs to validate input; use a plain Action otherwise.
- Use the canonical Contracts (`ActionContract`, `RuledActionContract`, `IndexActionContract`,
  `RepositoryContract`) as the architecture's vocabulary.
- Prefer auto-resolution over manual container bindings.

**DON'T:**

- Create generic catch-all classes (`Service.php`, `Helper.php`, `Utils.php`) — a Service MUST be
  domain-named and serve a concrete reused concern.
- Introduce Services gratuitously (without a cross-cutting/reused need).
- Add methods, parameters, `rules()`, or `$payload` a use case does not use (dead code).
- Add unnecessary layers of abstraction beyond the canonical Contracts.
- Use manual container bindings unless required.

---

## Architectural Decisions

Key binding decisions for the vanilla-php template (TEMPLATE scope — recorded in
`12-project-specific/template-baseline.md`):

1. **Class-Based Architecture (OOP hard rule)** — all code is wrapped in classes; no procedural
   code outside a class. Use cases are Actions; data access is Repositories; shared logic is
   Services; shape is defined by Contracts. (INVARIANT)
2. **Repository for All Data Access** — only the Repository layer interacts with the data layer;
   direct queries outside a Repository are forbidden. (INVARIANT)
3. **Services required when warranted** — no gratuitous Services; no copy-pasted logic either.
4. **Validation in Actions** — RuledActions validate via `rules(array $payload)`; entry
   validation is transport-level only.
5. **Context-Based Directory Organization** — code organized by domain context.
6. **Feature Stops at Action Layer** — unless explicitly asked, do not create Entry handlers or
   frontend components.
7. **Contract DNA** — the canonical Contracts exist even with a single implementation; ad-hoc
   contracts are created only with a clear need.
8. **Auto-Resolution Over Manual Binding** — prefer `illuminate/container` auto-resolution.
9. **No Dead Code** — every method/parameter/`rules()`/`$payload`/import is justified by the use
   case (Necessity Ladder). (INVARIANT)

---

## Canonical Snippets & Invocation Protocol

**`12-project-specific/canonical-snippets.md` is the authoritative snippet bank** (Action base,
ruled/plain actions, repositories, services, contracts, entities, bootstrap wiring, tests). Its
snippets are the **declared canonical forms** of this template — copy them verbatim for new code
until the project's own code establishes a different precedent.

**Invocation protocol (MUST):**

- `$action->handle($payload)` — single array payload (ruled actions validate inside).
- `$action->handle(['id' => $id])` — id-driven use cases keep the id inside the payload array.
- `$action->handle()` — non-ruled, no-input list/dashboard actions.
- `$action->handle($entity)` — only a **non-ruled** action may receive a non-array payload.
- **Never** pass a second argument to `handle()` and **never** call `execute()` (no such method
  exists).

Follow the bank's canonical forms rather than inventing invocation styles.

# NAMING — Conventions for All Artifacts

This file defines naming conventions for context/action-based, Repository-driven projects. Apply
them where the technology matches. The PSR-4 root namespace and context names come from
`MASTER_BUILD_SPECIFICATION.md` — never invent them.

---

## PHP Classes

### Naming Pattern: `{Namespace}\{Layer}\{Context}\{ClassName}`

Every PHP class follows the pattern rooted at the project's PSR-4 root namespace (example below
uses `App\` as placeholder — the real root is project-defined):

```
App\{Layer}\{Context}\{ClassName}
```

### Layer Naming

| Layer | Namespace | Pattern | Example |
|-------|-----------|---------|---------|
| Actions | `App\Actions\{Context}\` | `{Verb}{Entity}Action` | `CreateGroupAction`, `UpdateResidentAction` |
| Services | `App\Services\{Context}\` | `{Domain}Service` | `ResidentReportService` |
| Repositories | `App\Repositories\{Context}\` | `{Entity}Repository` | `GroupRepository`, `ResidentRepository` |
| Entities | `App\Entities\{Context}\` | `{Entity}` | `Group`, `Resident` |
| Entry | `App\Entry\...` | `{Transport}\{Feature}Handler` | `Http\CreateGroupHandler`, `Cli\ImportCsvCommand` |
| Contracts | `App\Contracts\{Layer}\` | `{Name}Contract` | `Action\RuledActionContract`, `Repository\RepositoryContract` |

**Rule:** domain-owned entities carry the context prefix in their class name when the context
owns them (`ResidentReport`, `CreateSidResidentAction`). Never create unprefixed duplicates of an
existing contextual class.

---

## Action Naming Semantics

| Prefix | Purpose | Example |
|--------|---------|---------|
| `Create` | Create new entity | `CreateGroupAction` |
| `Update` | Modify existing entity | `UpdateResidentAction` |
| `Delete` | Remove entity | `DeleteGroupAction` |
| `Get` | Retrieve single entity | `GetGroupAction` |
| `Get*` (plural) | Retrieve collection | `GetGroupsAction`, `GetResidentsAction` |
| `Ensure` | Guarantee existence (create if needed) | `EnsureSystemGroupExistsAction` |
| `Add` | Attach/relate entities | `AddGroupMemberAction` |
| `Reset` | Reset to default state | `ResetPasswordAction` |

---

## Method Naming

| Method | Purpose | Location |
|--------|---------|----------|
| `handle()` | Public entry point — triggers the Action | `Action` base class |
| `handler()` | Protected — contains actual business logic | Action subclasses |
| `rules()` | Returns validation rules array | `RuledActionContract` |
| `all()` | Fetch collection of records | base `Repository` |
| `find()` | Find by id | base `Repository` |
| `findBy{Field}()` | Find by a specific field | concrete repositories |
| `store()` | Create and persist entity | base `Repository` |
| `update()` | Update existing entity | base `Repository` |
| `delete()` | Delete entity | base `Repository` |

**Never directly call `handler()`** — it is protected and intended for internal use.
**`execute()` does not exist** anywhere in the action abstraction — do not call it (covered in
`11-forbidden-behavior.md`).

---

## Calling Actions

Actions are container-resolved and invoked on instances. Verbatim forms are in
`12-project-specific/canonical-snippets.md`; the protocol:

```php
// Constructor injection into an Entry handler / another Action (canonical)
public function __construct(protected CreateGroupAction $createGroupAction) {}

$group = $this->createGroupAction->handle($payload);

// Resolved from the composition root (entry scripts)
$action = $container->get(CreateGroupAction::class);
$group = $action->handle($payload);
```

- `$action->handle($payload)` — public entry point on the instance; `$payload` is a **single
  array** for ruled actions (whole input, optionally with `'id'` or an Entity/Model key merged in).
- `$action->handle()` — no-arg for non-ruled list actions.
- `$action->handle($entity)` — only a non-ruled action may take a non-array payload.
- `$action->execute(...)` — does NOT exist; never call it.

**NEVER call `handle()` statically** (`SomeAction::handle(...)`). `handle()` is an instance
method. Always resolve the action and call it on an instance.

---

## Database Naming (applies ONLY when the project uses a database)

### Tables

- Plural, snake_case: `users`, `groups`, `residents`, `articles`.
- Pivot tables: `{model}_has_{relation}` — `group_members`, `model_has_groups`.
- Table names are recorded in `MASTER_BUILD_SPECIFICATION.md`.

### Columns

- snake_case: `birth_date`, `nik`, `parent_id`.
- Foreign keys: `{related_table_singular}_id` (e.g., `author_id`, `group_id`, `parent_id`).
- Timestamps: `created_at`, `updated_at`.
- Status/enum columns: strings matching the PHP enum values (project decision — record in spec).

### Primary Keys

- Integer auto-increment (`id`) by default; UUID primary keys only when the project explicitly
  requires them (project decision — record in spec).

---

## Enum Naming

- PHP 8.1+ backed enums with `string` type.
- Suffix: `Enum` (e.g. `app/Enums/{Context}/{Name}Enum.php`).
- Cases: SCREAMING_SNAKE_CASE.
- Values: lowercase phrases.

```php
enum RelationshipStatusEnum: string
{
    case SINGLE = 'single';
    case MARRIED = 'married';
}
```

---

## Frontend Naming (applies ONLY when the project has a frontend)

See `14-frontend.md` and `20-frontend-and-contracts.md` for the conditional frontend rules. The
frontend stack, if any, is recorded in `MASTER_BUILD_SPECIFICATION.md`.

---

## Prohibited Naming

Do NOT use:

- Technical names for domain concepts: `TypeService`, `DataHandler`, `Manager`.
- Generic names: `Helper.php`, `Utils.php`, `Service.php` — **generic** names are prohibited; a
  **domain-named** Service (`GroupService`, `ResidentReportService`) is a valid, welcome layer
  pattern (see `03-architecture.md`).
- Correct: `CreateGroupAction`, `GetResidentsAction`, `GroupRepository`, `ResidentReportService`.

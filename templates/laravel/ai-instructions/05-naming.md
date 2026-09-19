# NAMING — Conventions for All Artifacts

This file defines naming conventions for context/action-based, Repository-driven projects. Apply them where the technology matches.

---

## PHP Classes

### Naming Pattern: `{Context}{Type}.php`

Every PHP class follows:

```
App\{Layer}\{Context}\{ClassName}
```

### Layer Naming

| Layer | Namespace | Pattern | Example |
|-------|-----------|---------|---------|
| Actions | `App\Actions\{Context}\` | `{Verb}{Entity}Action` | `CreateWebArticleAction`, `UpdateSidResidentAction` |
| Repositories | `App\Repositories\{Context}\` | `{Entity}Repository` | `Sid\SidResidentRepository`, `Web\WebArticleRepository` |
| Models | `App\Models\{Context}\` | `{Entity}` or `{Context}{Entity}` | `Group`, `Sid\SidResident`, `Web\WebArticle` |
| Controllers | `App\Http\Controllers\{Context}\` | `{Entity}Controller` | `Web\WebArticleController`, `Sid\SidResidentController` |
| Enums | `App\Enums\System\` | `{Name}Enum` | `System\GroupEnum` |
| Exceptions | `App\Exceptions\Model\Group\` | `{Description}Exception` | `CircularMembershipException` |
| Contracts | `App\Contracts\{Layer}\` | `{Name}Contract` | `Model\HasGroupsContract`, `Action\RuledActionContract` |
| Traits | `App\Abstractions\Traits\Model\` | `Has{Name}` | `HasGroups`, `HasMetadata` |

**Rule:** Domain-owned entities carry the context prefix in their class name (`SidResident`, `WebArticle`, `CreateSidResidentAction`). Never create unprefixed duplicates of an existing contextual class.

---

## Action Naming Semantics

| Prefix | Purpose | Example |
|--------|---------|---------|
| `Create` | Create new entity | `CreateWebArticleAction`, `CreateGroupAction` |
| `Update` | Modify existing entity | `UpdateSidResidentAction`, `UpdateMenuAction` |
| `Delete` | Remove entity | `DeleteWebArticleAction`, `DeleteGroupAction` |
| `Get` | Retrieve single entity | `GetWebArticleAction`, `GetSidResidentAction` |
| `Get*` (plural) | Retrieve collection | `GetWebArticlesAction`, `GetSidResidentsAction`, `GetMenusAction` |
| `Ensure` | Guarantee existence (create if needed) | `EnsureSystemGroupExistsAction` |
| `Add` | Attach/relate entities | `AddGroupChildAction` |
| `Reset` | Reset to default state | `ResetPasswordAction` |

---

## Method Naming

| Method | Purpose | Location |
|--------|---------|----------|
| `handle()` | Public entry point — triggers the Action | `Action` base class |
| `handler()` | Protected — contains actual business logic | Action subclasses |
| `rules()` | Returns validation rules array | `RuledActionContract` |
| `__invoke()` | Declared by `InvokeableActionContract` — **currently unused**; the working invocation API is `handle()` | `InvokeableActionContract` |
| `query()` | Build Eloquent query builder | `ModelRepository` |
| `store()` | Create and persist entity | `ModelRepository` |
| `findOrFail()` | Find or throw exception | `ModelRepository` |
| `findBySlug()` | Find by slug field | `ModelRepository` |

**Never directly call `handler()`** — it is protected and intended for internal use.
**`execute()` does not exist** anywhere in the action abstraction — do not call it (covered in `11-forbidden-behavior.md`).

---

## Calling Actions

Actions are container-resolved and invoked on instances. Verbatim forms are in `12-project-specific/canonical-snippets.md`; the protocol:

```php
// Constructor injection into a Controller/another Action (canonical)
public function __construct(protected CreateWebArticleAction $createWebArticleAction) {}

$article = $this->createWebArticleAction->handle($request->all());

// Method injection — acceptable in phpstan-friendly contexts
$article = $createWebArticleAction->handle($request->all());
```

- `$action->handle($payload)` — public entry point on the instance; `$payload` is a **single array** for ruled actions (whole input, optionally with `'id'` or a Model key merged in).
- `$action->handle()` — no-arg for non-ruled list actions.
- `$action->handle($model)` — only a non-ruled action may take a Model payload.
- `$action->execute(...)` — does NOT exist; never call it.

**NEVER call `handle()` statically** (`XxxAction::handle(...)`). `handle()` is an instance method; the bare `handle(array)` signature is NOT static in this project (existing static invocations in the codebase are known defects). Always resolve the action and call it on an instance.

---

## Database Naming

### Tables

- Plural, snake_case: `residents` → `sid_residents`, `articles` → `web_articles`, `pages` → `web_pages`, `groups`, `terms`, `metadatas`, `menus`.
- Domain-owned tables carry the context prefix: `sid_`, `web_`.
- Pivot tables: `model_has_{relation}` — `model_has_groups`, `model_has_terms`, `model_has_metadata` (Spatie permission uses `model_has_roles`, `model_has_permissions`).

### Columns

- snake_case: `birth_date`, `nik`, `author_id`, `parent_id`.
- Foreign keys: `{related_table_singular}_id` (e.g., `author_id`, `group_id`, `parent_id`).
- Timestamps: `created_at`, `updated_at` (standard Laravel).
- Slugs: `slug` (generated by Eloquent Sluggable).
- Enums stored as strings: `varchar`/`string` type, not native PHP enum type in DB.

### Primary Keys

- Integer auto-increment (`id`).

---

## Route Naming

Pattern: `dashboard.{context}.{subcontext}.{entity}.{action}` for authenticated features; flat names for auth/settings.

```php
// SID context routes
'dashboard.sid.population.residents.index'
'dashboard.sid.population.residents.create'
'dashboard.sid.population.residents.store'
'dashboard.sid.population.residents.update'

// Web context routes
'dashboard.web.articles.index'
'dashboard.web.articles.categories.index'

// Auth & settings routes
'login', 'register', 'logout', 'password.confirm'
'profile.edit', 'profile.update', 'password.edit', 'password.update', 'appearance'
```

**Rule:** Resource routes MUST be defined inside the contextual prefix group matching their domain. Do NOT create routes outside the contextual prefix.

---

## Frontend Naming

### Vue Files

- PascalCase for components: `AppShell.vue`, `AppContent.vue`, `AppHeader.vue`, `AppLogo.vue`, `AppearanceTabs.vue`.
- Pages mirror URI path: `resources/js/pages/Sid/Population/Residents/Index.vue`, `resources/js/pages/Web/Articles/Index.vue`.
- Each CRUD resource has: `Index.vue`, `Create.vue`, `Edit.vue` (+ `Show.vue` where routed).
- Pages for auth/settings stay flat in their folder: `resources/js/pages/Auth/*`, `resources/js/pages/Settings/*`.

### Vue Props

- Interface named `Props`.
- Typed explicitly.

```typescript
interface Props {
    residents: Array<App.Models.SidResident>;
    sidebarMenus: Array<App.Data.SidebarMenu>;
}
```

### Inertia Page Names

- Match the file path from `resources/js/pages/`: `Sid/Population/Residents/Index`, `Web/Articles/Index`.

---

## Enum Naming

- PHP 8.1+ backed enums with `string` type.
- Suffix: `Enum` (all live in `App\Enums\System\`).
- Cases: SCREAMING_SNAKE_CASE.
- Values: lowercase phrases (often matching group slugs).

```php
enum GroupEnum: string
{
    case DASHBOARD_SIDEBAR_MENU = 'dashboard sidebar menu';
    case CONTENT_ARTICLE_CATEGORY = 'content article category';
    case RESIDENT_RELIGION = 'resident religion';
}
```

---

## Prohibited Naming

Do NOT use:

- Technical names for domain concepts: `TypeService`, `DataHandler`, `Manager`.
- Generic names: `Helper.php`, `Utils.php`, `Service.php` — **generic** names are prohibited; a **domain-named** Service (`ArticleService`, `ResidentService`) is a valid, welcome layer pattern (see `03-architecture.md`).
- Correct: `CreateWebArticleAction`, `GetSidResidentsAction`, `SidResidentRepository`, `HasGroups.php`, `ArticleService`.

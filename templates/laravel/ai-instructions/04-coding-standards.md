# CODING STANDARDS — Style, Formatting, Conventions

This file defines coding style, formatting rules, and code conventions. Some rules are universal; others are project-specific.

---

## PHP Style

### General

- **Standard:** PSR-12
- **Framework:** Laravel 12.x
- **Indentation:** 4 spaces (no tabs)
- **Line endings:** LF
- **Final newline:** Yes
- **Strict types:** NOT used (no `declare(strict_types=1)` — project convention)
- **PHP version:** ^8.2

### Class Structure

- One class per file.
- Namespace matches directory path.
- `<?php` opening tag, no closing tag.
- Single blank line after namespace declaration.
- One blank line between use groups.
- Use statements ordered: classes, then functions, then constants.

### Method Style

- Return types declared on all methods.
- Nullable types use `?Type` syntax.
- `readonly` is allowed but used sparingly (a few Actions use `protected readonly` promoted properties); plain `protected` promotion is the safe default. Match the neighbouring file.
- Constructor property promotion is the norm for injected dependencies.
- Method ordering: `__construct` → public methods → protected/private methods.

### Property Visibility

- Properties use `protected` by default in Actions/Controllers (constructor promotion).
- `private` used in base classes for internal state.
- `$fillable` arrays in Models are always `protected`.

### Conditional Style

```php
// Preferred: negated condition with early return
if (! $condition) {
    return $fallback;
}

// Guard clauses
if (! is_string($groupKey)) {
    throw new InvalidArgumentException('...');
}
```

### Array Syntax

```php
// Associative arrays: short syntax with spaced brackets
$validatedPayload = [
    'name' => $validatedPayload['name'],
    'description' => $validatedPayload['description'],
];

// Empty arrays
return [];
```

### String Style

- Single quotes for simple strings.
- Double quotes for strings with variables.
- `sprintf()` for formatted strings.
- `Str::of()` fluent interface for string manipulation.

### Import Ordering

```php
// 1. PHP built-in classes
use InvalidArgumentException;

// 2. Laravel framework classes
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

// 3. Application classes (alphabetical by namespace)
use App\Abstractions\Actions\Action;
use App\Contracts\Action\RuledActionContract;
use App\Models\Group;
use App\Repositories\GroupRepository;
```

### Canonical Class Skeletons

Copy these skeletons from the verbatim bank `12-project-specific/canonical-snippets.md` — never paraphrase signatures. Skeleton shapes:

```php
// Ruled Create action (pipe-string rules are predominant)
class CreateXAction extends Action implements RuledActionContract
{
    public function __construct(protected XRepository $repository) {}

    protected function handler($payload = null, array $validatedPayload = []): X
    {
        return $this->repository->store($validatedPayload);
    }

    public function rules(array $payload): array
    {
        return [
            'name' => 'required|string|max:255',
            // ...
        ];
    }
}
```

```php
// Plain (non-ruled) action
class GetXsAction extends Action
{
    public function __construct(protected XRepository $repository) {}

    protected function handler($payload = null, array $validatedPayload = []): Collection
    {
        return $this->repository->with([...])->get();
    }
}
```

```php
// Thin controller — constructor injection (SID style)
class XController extends Controller
{
    public function __construct(
        protected CreateXAction $createXAction,
        protected UpdateXAction $updateXAction,
        protected DeleteXAction $deleteXAction
    ) {}

    public function store(Request $request)
    {
        $this->createXAction->handle($request->all());

        return redirect()->route('dashboard.sid.population.x.index');
    }
}
```

```php
// Repository — empty shell; model auto-resolved from the class name
/**
 * @extends ModelRepository<X>
 */
class XRepository extends ModelRepository
{
    //
}
```

```php
// Model — table, fillable, casts, relationships
class X extends Model
{
    use HasFactory;

    protected $table = 'xs';

    protected $fillable = ['name'];

    protected $casts = ['published_at' => 'datetime'];

    public function author()
    {
        return $this->belongsTo(\App\Models\User::class, 'author_id');
    }
}
```

---

## TypeScript/Vue Style

### General

- **Indentation:** 4 spaces
- **Semicolons:** Yes (enforced by Prettier)
- **Quotes:** Single quotes (enforced by Prettier)
- **Print width:** 150
- **Trailing commas:** Yes

### Vue Component Structure

```vue
<script setup lang="ts">
// Imports (ordered by prettier-plugin-organize-imports)
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

// Props interface
interface Props {
    data: SomeType;
    sidebarMenus: Array<any>;
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

// Form (if applicable)
const form = useForm({
    field: '',
});

// Methods
const submit = () => {
    form.post(route('route.name'));
};
</script>

<template>
    <Head title="Page Title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- content -->
    </AppLayout>
</template>
```

### TypeScript Types

- Use `interface` for object shapes.
- Use `type` for unions/intersections.
- Props always typed with interfaces.
- Model type references follow project convention (e.g., `App.Models.{Context}.{Model}`).

### CSS Classes

- Tailwind CSS utility classes.
- Use `cn()` helper for conditional classes (from `@/lib/utils`).
- Use project UI component library (e.g., shadcn-vue) for UI primitives.
- Dark mode via `.dark` class on `<html>`.

---

## Comment Style

**DO NOT add comments** unless explicitly asked. The codebase is largely comment-free. The few existing comments are:

- PHPDoc on model properties (`@var`).
- Type annotations (`@return`, `@param`).
- Occasional `@see` references.

---

## Code Documentation

**MUST — self-explanatory code.** Every line of code MUST be self-explanatory: it should read the way a human explains what the code does, without needing a comment. Achieve this through:

- Expressive, intent-revealing names (see `05-naming.md`).
- Small functions with a single responsibility and short parameter lists.
- Linear flow with early returns / guard clauses.
- Extracting conditions and side effects into named predicates/variables.
- Named constants instead of magic numbers.
- No clever one-liners; state things plainly.

A line that needs a comment to be understood FAILS this rule — fix the code (rename / extract / simplify), do not add the comment.

When a comment is genuinely unavoidable, it may only explain a reason a human cannot read from the code itself: a non-obvious business invariant, framework/performance constraint, or a `why` decision. NEVER re-state what the code does (`// increment total`).

Permitted docblocks (tooling, not prose): PHPDoc `@var` on model properties, `@param` / `@return` type annotations, occasional `@see`.

Worked GOOD / BAD / rewrite examples live in `12-project-specific/canonical-snippets.md` → Self-Explanatory Code Demonstrations.

Do not add explanatory comments or documentation blocks unless explicitly asked.

---

## Formatting Rules (Enforced by Tools)

### PHP

- Laravel Pint (`./vendor/bin/pint`) with the default Laravel preset (PSR-12 based).
- PHPStan/Larastan at level 5 (configured in `phpstan.neon`; paths `app/`, `config/`, `database/`, `routes/`).

### TypeScript/Vue

- Prettier with:
  - `prettier-plugin-organize-imports` (auto-sorts imports).
  - `prettier-plugin-tailwindcss` (sorts Tailwind classes).
- ESLint (flat config) with Vue and TypeScript configs.
- Run via project scripts:
  - `bun run lint` (ESLint, `eslint . --fix`)
  - `bun run format` (Prettier write) / `bun run format:check` (Prettier check)
  - `bun run build` / `bun run build:ssr` (Vite build)

---

## File Organization Summary

```
app/
├── Abstractions/
│   ├── Actions/
│   │   ├── Action.php          (base Action class)
│   │   └── IndexAction.php     (base for index/list actions)
│   ├── Repository/
│   │   └── ModelRepository.php (base Repository class)
│   └── Traits/
│       └── Model/
│           ├── HasGroups.php
│           └── HasMetadata.php
├── Actions/
│   ├── Group/                  (Create, Update, Delete, EnsureSystemGroupExists, AddGroupChild)
│   ├── Menu/                   (Create, Update, GetMenus, GetMenuById, GetMenuByGroup, DeleteMenuById)
│   ├── Metadata/               (Create)
│   ├── Term/                   (Create, Update, Delete)
│   ├── User/                   (Create, Update, UpdatePassword, ResetPassword, Delete)
│   ├── Sid/Resident/           (Create, Get, Get*, Update, Delete)
│   └── Web/
│       ├── Article/            (Create, Update, Get, Get*, Delete)
│       ├── Page/               (Create, Get)
│       └── Dashboard/Sidebar/  (GetAllSidebarMenuAction)
├── Contracts/
│   ├── Action/
│   │   ├── InvokeableActionContract.php
│   │   └── RuledActionContract.php
│   ├── Model/
│   │   └── Has*Contract.php    (HasGroups, HasMetadata, HasRoles, HasFile, HasPicture, …)
│   └── Repository/
│       ├── RepositoryContract.php
│       └── ModelRepositoryContract.php
├── Enums/
│   └── System/
│       └── GroupEnum.php
├── Exceptions/
│   └── Model/Group/
│       ├── CircularMembershipException.php
│       └── SystemGroupImmutableException.php
├── Http/
│   ├── Controllers/
│   │   ├── Auth/               (Breeze auth controllers)
│   │   ├── Dashboard/
│   │   ├── Settings/
│   │   ├── Sid/                (SidResidentController)
│   │   └── Web/                (WebArticleController, WebPageController, ArticleCategoryController)
│   ├── Middleware/
│   │   ├── HandleAppearance.php
│   │   ├── HandleInertiaRequests.php
│   │   ├── ShareDashboardData.php
│   │   └── ShareWebData.php
│   └── Requests/
│       ├── Auth/
│       └── Settings/
├── Models/
│   ├── Group.php
│   ├── Menu.php
│   ├── Metadata.php
│   ├── Term.php
│   ├── User.php
│   ├── Sid/
│   │   └── SidResident.php
│   └── Web/
│       ├── WebArticle.php
│       └── WebPage.php
├── Policies/
├── Providers/
└── Repositories/
    ├── GroupRepository.php
    ├── MenuRepository.php
    ├── MetadataRepository.php
    ├── TermRepository.php
    ├── UserRepository.php
    ├── Sid/
    │   └── SidResidentRepository.php
    └── Web/
        ├── WebArticleRepository.php
        └── WebPageRepository.php
```

# CODING STANDARDS — Style, Formatting, Conventions

This file defines coding style, formatting rules, and code conventions. Some rules are universal;
others are template or project-specific.

---

## PHP Style

### General

- **Standard:** PSR-12
- **Stack:** Vanilla PHP ^8.2 + Composer + PSR-4 (no full framework)
- **Indentation:** 4 spaces (no tabs)
- **Line endings:** LF
- **Final newline:** Yes
- **Strict types:** NOT used (no `declare(strict_types=1)` — template convention; consistent with
  the architecture DNA; the project may override in `MASTER_BUILD_SPECIFICATION.md`)
- **PHP version:** ^8.2

### Class Structure

- One class per file.
- Namespace matches directory path under the PSR-4 root.
- `<?php` opening tag, no closing tag.
- Single blank line after namespace declaration.
- One blank line between use groups.
- Use statements ordered: classes, then functions, then constants.

### Method Style

- Return types declared on all methods.
- Nullable types use `?Type` syntax.
- Constructor property promotion is the norm for injected dependencies.
- `readonly` is allowed where the value is immutable (entities, value objects); promoted
  properties in Actions/Repositories/Services use `protected readonly` or plain `protected`
  promotion — match the neighbour file.
- Method ordering: `__construct` → public methods → protected/private methods.

### Property Visibility

- Properties use `protected` by default in Actions/Services/Repositories (constructor promotion).
- `private` used in base classes for internal state.
- Entities use `private readonly` or immutables as appropriate for the domain.

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
- `Str::of()` fluent interface for string manipulation (when `illuminate/support` is present).

### Import Ordering

```php
// 1. PHP built-in classes
use InvalidArgumentException;

// 2. Library classes (e.g. illuminate components, PSR interfaces)
use Illuminate\Container\Container;
use Illuminate\Support\Str;

// 3. Application classes (alphabetical by namespace)
use App\Abstractions\Action;
use App\Contracts\Action\RuledActionContract;
use App\Entities\Group;
use App\Repositories\GroupRepository;
```

---

## Canonical Class Skeletons

Copy these skeletons from the canonical bank `12-project-specific/canonical-snippets.md` —
never paraphrase signatures. Skeleton shapes:

```php
// Ruled Create action
class CreateGroupAction extends Action implements RuledActionContract
{
    public function __construct(protected GroupRepository $repository) {}

    protected function handler($payload = null, array $validatedPayload = []): Group
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
// Plain (non-ruled) list action
class GetGroupsAction extends IndexAction
{
    public function __construct(protected GroupRepository $repository) {}

    protected function handler($payload = null): array
    {
        return $this->repository->all();
    }
}
```

```php
// Repository — base-class CRUD, custom query methods when needed
final class GroupRepository extends Repository
{
    public function findByName(string $name): ?Group
    {
        // data-layer query — the ONLY place data access happens
    }
}
```

```php
// Entity — structure + invariants
final readonly class Group
{
    private function __construct(
        public string $name,
        public ?int $id = null,
    ) {}

    public static function create(string $name): self
    {
        if ($name === '') {
            throw new InvalidArgumentException('Group name cannot be empty.');
        }

        return new self($name);
    }
}
```

```php
// Service — cross-cutting domain logic (required when reused)
final class GroupAssignmentService
{
    public function __construct(protected GroupRepository $groups, protected AssignmentRepository $assignments) {}

    public function assignMembers(Group $group, array $memberIds): void
    {
        // reused across use cases
    }
}
```

```php
// Bootstrap — composition root (auto-resolution default)
$container = new Container();
$container->singleton(GroupRepository::class);
$action = $container->get(CreateGroupAction::class);
```

---

## Comment Style

**DO NOT add comments** unless explicitly asked. The codebase is largely comment-free. The few
existing comments are:

- PHPDoc on model/entity properties (`@var`).
- Type annotations (`@return`, `@param`).
- Occasional `@see` references.

---

## Code Documentation

**MUST — self-explanatory code.** Every line of code MUST be self-explanatory: it should read the
way a human explains what the code does, without needing a comment. Achieve this through:

- Expressive, intent-revealing names (see `05-naming.md`).
- Small functions with a single responsibility and short parameter lists.
- Linear flow with early returns / guard clauses.
- Extracting conditions and side effects into named predicates/variables.
- Named constants instead of magic numbers.
- No clever one-liners; state things plainly.

A line that needs a comment to be understood FAILS this rule — fix the code (rename / extract /
simplify), do not add the comment.

When a comment is genuinely unavoidable, it may only explain a reason a human cannot read from
the code itself: a non-obvious business invariant, framework/performance constraint, or a `why`
decision. NEVER re-state what the code does (`// increment total`).

Permitted docblocks (tooling, not prose): PHPDoc `@var` on model/entity properties, `@param` /
`@return` type annotations, occasional `@see`.

Worked GOOD / BAD / rewrite examples live in `12-project-specific/canonical-snippets.md` →
Self-Explanatory Code Demonstrations.

Do not add explanatory comments or documentation blocks unless explicitly asked.

---

## Formatting Rules (Enforced by Tools)

### PHP

- **Pint** (`./vendor/bin/pint`) with the default preset (PSR-12 based) or
  `php-cs-fixer` with PSR-12 — the formatter is recorded in `MASTER_BUILD_SPECIFICATION.md` and
  `composer.json` scripts.
- **PHPStan** at the level configured in `phpstan.neon` (default level 5; paths per project —
  typically `src/`, `tests/`).

---

## File Organization Summary

```
src/
├── Abstractions/
│   ├── Action.php              (base Action class — handle()/handler())
│   ├── IndexAction.php         (base for index/list actions)
│   └── Repository/
│       └── Repository.php      (base Repository class)
├── Actions/{Context}/          (Create, Update, Delete, Get, Get*, Ensure, …)
├── Services/{Context}/         (domain-named services, only when warranted)
├── Repositories/{Context}/     (data access — the ONLY layer allowed)
├── Entities/{Context}/         (entity classes — structure + invariants)
├── Contracts/
│   ├── Action/
│   │   ├── ActionContract.php
│   │   ├── RuledActionContract.php
│   │   └── IndexActionContract.php
│   └── Repository/
│       └── RepositoryContract.php
├── Entry/                      (thin transport handlers: HTTP, CLI)
└── Bootstrap/                  (composition root — container wiring)
```

The exact source layout (PSR-4 root namespace, `src/` mapping) is recorded in
`MASTER_BUILD_SPECIFICATION.md`.

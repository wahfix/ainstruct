# CANONICAL SNIPPETS — Declared Template Forms (reference to copy)

This module is the **authoritative snippet bank** for the vanilla-php template. Every snippet is
a **declared canonical form** of this template (TEMPLATE scope — `01-governance.md`), carrying a
`template canonical:` anchor. These are the reference shapes to copy **until the project's own
code establishes a different precedent** — the project's real code then becomes the highest
evidence source.

> [!IMPORTANT]
> Honesty marker: unlike a repo-snapshot bank, these snippets are NOT "copied unchanged from a
> live codebase". They are the template's declared forms. Do not claim repo-verbatim status for
> them (`17-agent-discipline.md` honesty).

---

## Usage Rules

1. **Copy + adapt, never paraphrase.** When writing new code, open the most relevant snippet
   below, copy it, and change only identifiers/values (use the project's real namespace root and
   context names from `MASTER_BUILD_SPECIFICATION.md`). This is what keeps new code consistent.
2. **Anchor first.** Cross-check every signature (parameter defaults, return types, rule shapes)
   against the bank before writing.
3. **Two forms may exist.** For each pattern there are two valid shapes — **full**
   (e.g. RuledAction with validation) and **minimum** (plain Action). Pick the form matching your
   use case per the Necessity Ladder (`03-architecture.md`). Do not copy the full form when the
   use case does not validate.
4. **Never copy broken forms.** `// BAD` blocks show defects; do not reproduce them.
5. When a snippet references a class method you have not seen in the bank, check the base class
   in the project (`src/Abstractions/…`) — do not invent signatures.
6. **Evidence-anchored code (hallucination guard).** Before writing any code, enumerate the
   classes/methods/arguments you plan to use and verify **each one** against the project's real
   code (or the bank, for template forms) — open the file and read the real signature (return
   type, parameter list, defaults). A symbol you merely *believe* exists is not evidence. If
   verification fails, do not invent — find a real analogue in the bank or the repo, or stop and
   ask the operator. Memory is a guess; the code is the source of truth.
7. After **fixing** a BAD form, re-verify the arising code compiles **and** the adjacent
   contracts still hold (no drift in `01-governance.md`).

---

## 1. Abstractions — Base Action

`template canonical: src/Abstractions/Action.php`

```php
<?php

namespace App\Abstractions;

use App\Contracts\Action\ActionContract;

abstract class Action implements ActionContract
{
    /**
     * Public entry point. Single array payload for ruled actions; plain actions may take
     * no argument or a single value (entity/record only for non-ruled actions).
     */
    public function handle(mixed $payload = null): mixed
    {
        return $this->handler($payload);
    }

    abstract protected function handler(mixed $payload = null): mixed;
}
```

`template canonical: src/Abstractions/IndexAction.php`

```php
<?php

namespace App\Abstractions;

use App\Contracts\Action\IndexActionContract;

abstract class IndexAction extends Action implements IndexActionContract
{
    abstract protected function handler(mixed $payload = null): array;
}
```

## 2. Contracts — Canonical Shape

`template canonical: src/Contracts/Action/ActionContract.php`

```php
<?php

namespace App\Contracts\Action;

interface ActionContract
{
    public function handle(mixed $payload = null): mixed;
}
```

`template canonical: src/Contracts/Action/RuledActionContract.php`

```php
<?php

namespace App\Contracts\Action;

interface RuledActionContract
{
    /**
     * Validation rules for the payload. Implemented ONLY by actions that validate input
     * (Necessity Ladder — a plain action never defines rules()).
     */
    public function rules(array $payload): array;
}
```

`template canonical: src/Contracts/Action/IndexActionContract.php`

```php
<?php

namespace App\Contracts\Action;

interface IndexActionContract
{
    public function handle(mixed $payload = null): array;
}
```

`template canonical: src/Contracts/Repository/RepositoryContract.php`

```php
<?php

namespace App\Contracts\Repository;

interface RepositoryContract
{
    public function find(int $id): mixed;

    public function all(): array;

    public function store(array $data): mixed;

    public function update(int $id, array $data): mixed;

    public function delete(int $id): bool;
}
```

## 3. Action — Full Form (RuledAction)

Use when the use case **validates input**. Full form includes `rules()` and the validated
payload.

`template canonical: src/Actions/Group/CreateGroupAction.php`

```php
<?php

namespace App\Actions\Group;

use App\Abstractions\Action;
use App\Contracts\Action\RuledActionContract;
use App\Entities\Group;
use App\Repositories\GroupRepository;

final class CreateGroupAction extends Action implements RuledActionContract
{
    public function __construct(protected GroupRepository $repository) {}

    protected function handler(mixed $payload = null, array $validatedPayload = []): Group
    {
        return $this->repository->store($validatedPayload);
    }

    public function rules(array $payload): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ];
    }
}
```

## 4. Action — Minimum Form (Plain Action, no validation)

Use when the use case does **not validate** and/or reads **no payload**. NO `rules()`, NO
`$validatedPayload` — dead code is forbidden (Necessity Ladder).

`template canonical: src/Actions/Group/GetGroupsAction.php` (no input)

```php
<?php

namespace App\Actions\Group;

use App\Abstractions\IndexAction;
use App\Repositories\GroupRepository;

final class GetGroupsAction extends IndexAction
{
    public function __construct(protected GroupRepository $repository) {}

    protected function handler(mixed $payload = null): array
    {
        return $this->repository->all();
    }
}
```

`template canonical: src/Actions/Group/GetGroupAction.php` (input by id, no validation rules — id
presence is guarded in the repository/entry):

```php
<?php

namespace App\Actions\Group;

use App\Abstractions\Action;
use App\Entities\Group;
use App\Repositories\GroupRepository;

final class GetGroupAction extends Action
{
    public function __construct(protected GroupRepository $repository) {}

    protected function handler(mixed $payload = null): ?Group
    {
        return $this->repository->find((int) $payload['id']);
    }
}
```

## 5. Repository — Full Form (custom query methods)

`template canonical: src/Repositories/GroupRepository.php`

```php
<?php

namespace App\Repositories;

use App\Abstractions\Repository\Repository;
use App\Entities\Group;

final class GroupRepository extends Repository
{
    public function findByName(string $name): ?Group
    {
        // data-layer query — the ONLY place data access happens
        // prepared statements / parameter binding mandatory (13-database.md)
        return $this->query()->fetch($name);
    }
}
```

## 6. Repository — Minimum Form (base CRUD only)

`template canonical: src/Repositories/GroupRepository.php` (minimum — no custom methods)

```php
<?php

namespace App\Repositories;

use App\Abstractions\Repository\Repository;

final class GroupRepository extends Repository
{
}
```

> Empty shell is valid ONLY when the feature needs nothing beyond base CRUD. No speculative
> methods (Necessity Ladder).

## 7. Service — Domain-Named (required when logic is reused)

`template canonical: src/Services/Group/GroupAssignmentService.php`

```php
<?php

namespace App\Services\Group;

use App\Entities\Group;
use App\Repositories\AssignmentRepository;
use App\Repositories\GroupRepository;

final class GroupAssignmentService
{
    public function __construct(protected GroupRepository $groups, protected AssignmentRepository $assignments) {}

    public function assignMembers(Group $group, array $memberIds): void
    {
        $this->assignments->replaceAll($group->id, $memberIds);
        $this->groups->touch($group->id);
    }
}
```

NEVER a generic `Service.php` / `Helper.php` / `Utils.php` (`03-architecture.md`, `05-naming.md`).

## 8. Entity — Structure + Invariants

`template canonical: src/Entities/Group.php` (full form — invariants)

```php
<?php

namespace App\Entities;

use InvalidArgumentException;

final readonly class Group
{
    private function __construct(
        public int $id,
        public string $name,
        public ?string $description = null,
    ) {}

    public static function create(int $id, string $name, ?string $description = null): self
    {
        if ($name === '') {
            throw new InvalidArgumentException('Group name cannot be empty.');
        }

        return new self($id, $name, $description);
    }
}
```

`template canonical: src/Entities/Group.php` (minimum form — plain data holder)

```php
<?php

namespace App\Entities;

final readonly class Group
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description = null,
    ) {}
}
```

> Minimum form is valid when the project treats entities as pure data; invariants are added when
> the domain demands them. A project that stores raw arrays MAY skip the entity layer until
> records gain behavior (`03-architecture.md`).

## 9. Bootstrap — Composition Root

`template canonical: bootstrap/app.php` (or `src/Bootstrap/app.php`)

```php
<?php

use App\Actions\Group\CreateGroupAction;
use App\Repositories\GroupRepository;
use Illuminate\Container\Container;

$container = new Container();

// Auto-resolution covers concrete classes; bind explicitly only when needed.
$container->singleton(GroupRepository::class);
$container->singleton(Pdo::class, fn () => new Pdo(
    getenv('DB_DSN'),
    getenv('DB_USER'),
    getenv('DB_PASS'),
));

/** @var CreateGroupAction $action */
$action = $container->get(CreateGroupAction::class);
```

`template canonical: public/index.php` (entry — thin)

```php
<?php

use App\Actions\Group\CreateGroupAction;

require __DIR__ . '/../bootstrap/app.php';

// resolve from the composition root — never build the graph by hand here
$action = $container->get(CreateGroupAction::class);

$group = $action->handle($_POST);

http_response_code(201);
header('Content-Type: application/json');
echo json_encode(['id' => $group->id]);
```

> Entry is thin: transport in, delegate, response out. No business logic
> (`03-architecture.md` Entry Layer).

## 10. Invocation Protocol (MUST)

```php
$action->handle($payload);              // ruled action: single array payload
$action->handle(['id' => $id]);         // id-driven use case (id inside the array)
$action->handle();                      // non-ruled, no-input action
$action->handle($entity);               // NON-ruled action may take an entity/record
```

**NEVER:**

```php
// BAD — no such method exists
$action->execute($payload);

// BAD — second argument ignored / breaks the protocol
$action->handle($payload, $extra);

// BAD — ruled action requires an array payload
$action->handle($id); // throws InvalidArgumentException('Payload must be an array.')

// BAD — static call on an instance method
CreateGroupAction::handle($payload);
```

## 11. ValidationException

`template canonical: src/Exceptions/ValidationException.php`

```php
<?php

namespace App\Exceptions;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, array<int, string>> $errors field => messages
     */
    public function __construct(
        public readonly array $errors,
        string $message = 'The given data was invalid.',
    ) {
        parent::__construct($message);
    }
}
```

> The RuledAction base throws this with the field errors when a rule fails; Entry maps it to a
> 422 response; the frontend consumes `errors` per `20-frontend-and-contracts.md` error contract.

## 12. Action Test (Unit) — copy form

`template canonical: tests/Unit/Actions/Group/CreateGroupActionTest.php`

```php
<?php

namespace Tests\Unit\Actions\Group;

use App\Actions\Group\CreateGroupAction;
use App\Entities\Group;
use App\Exceptions\ValidationException;
use App\Repositories\GroupRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CreateGroupActionTest extends TestCase
{
    #[Test]
    public function it_creates_a_group_with_valid_data(): void
    {
        $repository = $this->createMock(GroupRepository::class);
        $repository->expects($this->once())
            ->method('store')
            ->willReturn(Group::create(1, 'Test Group'));

        $action = new CreateGroupAction($repository);
        $group = $action->handle(['name' => 'Test Group']);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertSame('Test Group', $group->name);
    }

    #[Test]
    public function it_rejects_invalid_data(): void
    {
        $repository = $this->createMock(GroupRepository::class);
        $action = new CreateGroupAction($repository);

        $this->expectException(ValidationException::class);

        $action->handle(['name' => '']);
    }
}
```

## 13. Self-Explanatory Code Demonstrations

```php
// GOOD — intent-revealing, no comment needed
if (! $this->repository->exists($payload['slug'])) {
    return $this->repository->store($payload);
}

return $this->repository->findBySlug($payload['slug']);
```

```php
// BAD — needs a comment because it is unclear
// check if slug already taken, then create, otherwise return existing
return $r->exists($s) ? $r->findBySlug($s) : $r->store($p);
```

```php
// GOOD — named constant instead of magic number
private const MAX_GROUP_NAME_LENGTH = 255;

if (mb_strlen($payload['name']) > self::MAX_GROUP_NAME_LENGTH) {
    throw new ValidationException(['name' => ['Name is too long.']]);
}
```

```php
// BAD — comment re-states the code (chit-chat)
// increment total
$total++;
```

## 14. Repository Integration Test (conditional on DB)

`template canonical: tests/Feature/Repositories/GroupRepositoryTest.php` (shape — adapt to the
project's data layer per `MASTER_BUILD_SPECIFICATION.md`)

```php
<?php

namespace Tests\Feature\Repositories;

use App\Entities\Group;
use App\Repositories\GroupRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GroupRepositoryTest extends TestCase
{
    #[Test]
    public function it_stores_and_finds_a_group(): void
    {
        $repository = new GroupRepository($this->connection());
        $repository->store(['name' => 'Test Group']);
        $group = $repository->findByName('Test Group');

        $this->assertInstanceOf(Group::class, $group);
        $this->assertSame('Test Group', $group->name);
    }
}
```

---

## Cross-References

- Pattern selection: Necessity Ladder — `03-architecture.md`.
- Layer naming: `05-naming.md`.
- Security on input: `07-security.md`; data layer: `13-database.md`.
- Quality gates & senior review: `10-quality-gates.md`.
- Project overrides: `template-baseline.md`, `MASTER_BUILD_SPECIFICATION.md`.

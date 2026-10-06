# CANONICAL SNIPPETS — Declared Template Forms (reference to copy)

This module is the **authoritative snippet bank** for the vanilla-php template. Every snippet is
a **declared canonical form** of this template (TEMPLATE scope — `01-governance.md`), carrying a
`template canonical:` anchor. These are the reference shapes to copy **until the project's own
code establishes a different precedent** — the project's real code then becomes the highest
evidence source.

> [!IMPORTANT]
> Honesty marker: unlike a repo-snapshot bank, these snippets are NOT "copied unchanged from a
> live codebase". They are the template's declared forms. Do not claim repo-verbatim status for
> them (`17-agent-discipline.md` honesty). **The single exception is §15 — Engine Evidence**
> (WebUI architecture from the ainstruct engine): those snippets ARE live, verbatim code and
> carry `engine canonical:` anchors — copy the shape, keep the declared forms as the copy target.

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

## 15. Engine Evidence — WebUI Architecture (live reference)

The **ainstruct engine** (`github.com/wahfix/ainstruct` — the tool that distributes this very
template) is itself a vanilla PHP project (PHP ^8.2, Composer, PSR-4, no full framework) that
uses **Laravel's DI container** (`illuminate/container`) exactly as the declared forms in this
bank describe. Its WebUI subsystem is the **live reference** for "vanilla PHP + Laravel DI":
read it when a declared form feels abstract, or when wiring a container for the first time.

> [!IMPORTANT]
> Anchors here use `engine canonical: <path>:<line>` and point to the **ainstruct repository**
> (the engine), NOT to your project. Customer projects copying these shapes MUST keep their own
> namespace root (`App\…`) and their own `src/` layout; only the *pattern* transfers. This
> section is the honest exception to the "declared forms" marker above — it IS verbatim from a
> live codebase.

### 15.1 Vanilla PHP + Laravel DI — the dependency stack

`engine canonical: composer.json:20` — the engine's entire runtime dependency list:

```json
"require": {
    "php": "^8.2",
    "illuminate/container": "^11.0|^12.0|^13.0",
    "laravel/prompts": "^0.3.0"
}
```

No full framework. `illuminate/container` is the DI container; `laravel/prompts` is a CLI
prompt helper. This is the live proof behind `template-baseline.md` (DI container MUST, no
framework): the engine builds a complete WebUI + CLI on exactly that whitelist.

### 15.2 Composition root — service provider

`engine canonical: src/Bootstrap/AppServiceProvider.php:18`

```php
final class AppServiceProvider
{
    public function __construct(private Container $container) {}

    public function register(): void
    {
        $this->container->singleton(Paths::class, fn (): Paths => Paths::fromEnvironment());
        $this->container->singleton(Filesystem::class);

        $this->container->singleton(TemplateRepositoryContract::class, TemplateRepository::class);
        $this->container->singleton(MasterRepositoryContract::class, MasterRepository::class);
        $this->container->singleton(InstructionFileRepositoryContract::class, InstructionFileRepository::class);
        $this->container->singleton(StackDetectorContract::class, StackDetectionService::class);
        $this->container->singleton(OpencodeService::class);
    }
}
```

Composition-root rules as live code: constructor takes the `Container`; contracts are bound to
concrete implementations; concrete classes (services, actions) are left to auto-resolution;
`singleton` is used for shared state (`Paths`, `Filesystem`, services).

### 15.3 Entry — resolve from the container, never hand-build

CLI (`engine canonical: src/Application.php:47` — resolve at :63):

```php
$command = $this->container->make(self::COMMANDS[$commandName]);

return $command->handle(new Input($commandArgs));
```

HTTP front controller (`engine canonical: web/index.php:48`):

```php
$container = new Container;
(new AppServiceProvider($container))->register();

$kernel = new Kernel($container);
$kernel->handle(Request::fromGlobals())->send();
```

Both entries: new container → register bindings → resolve → delegate. Entry stays thin; no
business logic, no hand-built graph.

### 15.4 HTTP kernel — routing, container dispatch, exception mapping

`engine canonical: src/Web/Kernel.php:20` (routes :25, controller resolution :73, error map :190)

```php
private const ROUTES = [
    ['GET', '#^/api/templates$#', 'templates'],
    ['POST', '#^/api/templates$#', 'create'],
    // …
];
```

Controllers are resolved **from the container** (constructor injection), never hand-`new`ed:

```php
/** @var TemplateController $controller */
$controller = $this->container->make(TemplateController::class);
```

Exceptions map to HTTP in one place (`error()`): ValidationException → 422,
TemplateNotFoundException → 404, SessionNotFoundException → 404,
TemplateProtectedException → 403, InvalidOperationException → 409, else 500 — the "entry maps
errors" rule from `03-architecture.md`.

### 15.5 Controller — constructor injection of Actions/Services, thin methods

`engine canonical: src/Web/Controllers/TemplateController.php:27`

```php
final class TemplateController
{
    public function __construct(
        private GetTemplatesAction $getTemplatesAction,
        private CreateTemplateAction $createTemplateAction,
        // … the other template actions …
        private TemplateFileService $files,
        private SourceImporter $importer,
    ) {}

    public function templates(Request $request): Response
    {
        $templates = $this->getTemplatesAction->handle();
        // … split builtin/custom …
        return Response::json(200, ['ok' => true, 'data' => ['builtin' => $builtin, 'custom' => $custom]]);
    }
}
```

Note: the CLI command (`engine canonical: src/Console/TemplateCommand.php:25`) injects the
**same Actions** — one source of truth for template logic, two thin entries on top.

### 15.6 Service — domain-named, constructor injection, honest state reporting

`engine canonical: src/Services/Opencode/OpencodeService.php:24`

```php
final class OpencodeService
{
    private const MAX_OUTPUT_BYTES = 200_000;

    public function __construct(private Paths $paths) {}

    public function start(array $input): array
    {
        // … per-method validation, then spawn …
    }
}
```

Domain-named (`OpencodeService`, never `Service.php`), constructor injection, input validated
at the public method boundary. The class documents a state machine (running → finished) and
**honestly reports `exitCode: null` when it cannot capture it** — honesty discipline applies to
engine code too (`17-agent-discipline.md`).

### 15.7 Action — base class + contract, as live code

`engine canonical: src/Abstractions/Actions/Action.php:8` and
`engine canonical: src/Contracts/Actions/RuledActionContract.php:5`

```php
abstract class Action
{
    public function handle(array $payload = []): mixed
    {
        if ($this instanceof RuledActionContract) {
            $validated = Validator::fromRules($this->rules())->validate($payload);

            return $this->handler($validated);
        }

        return $this->handler($payload);
    }

    abstract protected function handler(array $payload): mixed;
}
```

Plain action with contract injection (`engine canonical: src/Actions/Template/GetTemplatesAction.php:9`):

```php
final class GetTemplatesAction extends Action
{
    public function __construct(private TemplateRepositoryContract $templates) {}

    protected function handler(array $payload): array
    {
        return $this->templates->all();
    }
}
```

### 15.8 Request / Response — transport value objects

`engine canonical: src/Web/Request.php:9` (immutable request; `fromGlobals()` is the only
globals touch-point so tests build instances directly) and
`engine canonical: src/Web/Response.php:5` (named constructors `json()`, `html()`, `stream()`
for SSE; `send()` is the only output point). Transport never carries business logic.

### 15.9 Divergence — engine live shape vs declared form

Where engine and declared form differ, **the declared form in this bank is the copy target**;
the engine is the reference, not a second canonical:

| Concern | Declared form (above) | Engine live shape |
|---|---|---|
| `handle()` base | `handle(mixed $payload = null): mixed` (§1) | `handle(array $payload = []): mixed` |
| Ruled action payload | `handler($payload, $validatedPayload)` (§3) | `handler($validated)` — validated array replaces payload |
| `rules()` signature | `rules(array $payload): array` (§2) | `rules(): array` (no argument) |
| Contract namespace | `src/Contracts/Action/…` (§2-) | `src/Contracts/Actions/…` (plural) |

Same architecture, older/leaner engine shape. When a consumer project already carries engine-
like code from an earlier version, the project's real code is the highest evidence source
(`01-governance.md`) — record the deviation in `MASTER_BUILD_SPECIFICATION.md` or migrate to
the declared form (`template-baseline.md`).

---

## Cross-References

- Pattern selection: Necessity Ladder — `03-architecture.md`.
- Layer naming: `05-naming.md`.
- Security on input: `07-security.md`; data layer: `13-database.md`.
- Quality gates & senior review: `10-quality-gates.md`.
- Project overrides: `template-baseline.md`, `MASTER_BUILD_SPECIFICATION.md`.
- Live reference (vanilla PHP + Laravel DI, full WebUI example): §15 Engine Evidence — see
  `ainstruct` `src/Bootstrap/AppServiceProvider.php`, `src/Web/Kernel.php`,
  `src/Web/Controllers/`, `src/Services/Opencode/OpencodeService.php`, `web/index.php`.

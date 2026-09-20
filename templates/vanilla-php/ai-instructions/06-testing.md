# TESTING — Strategy, Patterns, Conventions

This file defines testing conventions for PHPUnit-based vanilla PHP projects. Apply them where
the technology matches. Testing details specific to the project (database setup, fixtures,
coverage targets) belong in `MASTER_BUILD_SPECIFICATION.md`.

---

## Framework & Configuration

- **PHPUnit** (standalone — no framework test helpers).
- **Configuration:** `phpunit.xml` at the project root (suites, bootstrap, coverage).
- **Suites:** `Unit` and `Feature` (defined in `phpunit.xml`).
- **Runner:** use the Composer script / vendor binary:

  ```bash
  ./vendor/bin/phpunit --filter=TestName
  ./vendor/bin/phpunit --testsuite=Unit
  ./vendor/bin/phpunit --testsuite=Feature
  ```

---

## Test Attribute Convention

Use the `#[Test]` attribute on every test method:

```php
use PHPUnit\Framework\Attributes\Test;

class SomeTest extends TestCase
{
    #[Test]
    public function it_creates_a_group_with_valid_data(): void
    {
        // ...
    }
}
```

**Do not use PHPDoc `@test` annotations and do not rely on the bare `test_` prefix alone** —
`#[Test]` (PascalCase attribute class) is the canonical marker. Never write a test method that
claims `it_` intent while skipping the attribute.

---

## Test Naming Convention

- Test methods: descriptive snake_case (`user_can_…`, `it_…`) marked with `#[Test]`.
- Test classes: `{Entity}Test` for Unit, `{Feature}Test` for Feature.
- Method names describe the behavior being tested.

```php
// Good
public function user_can_create_a_group()
public function it_creates_a_group_with_valid_data()
public function it_throws_validation_exception_for_invalid_data()

// Avoid
public function createGroup()
public function testGroupCreation()
```

---

## Test Organization

### Unit Tests (`tests/Unit/`)

- Action logic tests (with mocked repositories).
- Service logic tests.
- Entity/domain logic tests.
- Mirror source structure:
  `tests/Unit/Actions/Group/CreateGroupActionTest.php`,
  `tests/Unit/Services/GroupAssignmentServiceTest.php`.

### Feature / Integration Tests (`tests/Feature/`)

- Entry-handler tests (HTTP via PSR-7/curl-style flow or direct handler invocation).
- Repository tests against a real database (when the project has one).
- Full integration tests.
- Mirror feature structure: `tests/Feature/Http/CreateGroupHandlerTest.php`,
  `tests/Feature/Repositories/GroupRepositoryTest.php`.

---

## Base TestCase

All test classes extend `Tests\TestCase`:

```php
namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //
}
```

---

## Test Data

- **Fixtures:** prefer builders/factories for entities (project convention — record in spec);
  direct construction is acceptable for simple cases.
- **Database:** when the project uses a database, integration tests create schema per
  `MASTER_BUILD_SPECIFICATION.md` (in-memory SQLite by default; real DB only when the project
  requires it) and clean up between tests.
- **Faker** is allowed for test data when present; otherwise hard-coded fixture values are fine.

---

## Mocking Rules

- Mock at Repository layer for Unit tests (PHPUnit's built-in `createMock`).
- Mock at the boundary for external integrations (HTTP clients, mailers, queues).
- **DO NOT mock in Feature/Integration tests** where a real database or real dependency is
  available — integration tests exercise real wiring.
- When mocking repositories, the mock is passed into the constructor directly:

  ```php
  $repository = $this->createMock(GroupRepository::class);
  $action = new CreateGroupAction($repository);
  ```

---

## Unit Action Test Pattern

```php
class CreateGroupActionTest extends TestCase
{
    #[Test]
    public function it_creates_a_group_with_valid_data(): void
    {
        $repository = $this->createMock(GroupRepository::class);
        $repository->expects($this->once())
            ->method('store')
            ->willReturnCallback(fn (array $data) => Group::create($data));

        $action = new CreateGroupAction($repository);
        $group = $action->handle([
            'name' => 'Test Group',
            'description' => 'A test group',
        ]);

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

> Actions are invoked with `->handle($payload)`. The `$action->execute($data)` form is a
> **broken reference to a non-existent method** — never use it (see
> `12-project-specific/canonical-snippets.md`).

---

## Assertion Patterns

```php
// Object assertions
$this->assertInstanceOf(Entity::class, $result);
$this->assertSame('expected', $result->name);
$this->assertNotNull($result);
$this->assertCount(2, $collection);
$this->assertTrue($collection->contains($item));
$this->assertFalse($collection->contains($item));

// Exception testing
$this->expectException(ValidationException::class);
$this->expectExceptionMessage('Group name is required.');
```

---

## What Makes a "Good Test"

1. Tests behavior, not implementation.
2. Uses `#[Test]` attribute.
3. Unit tests mock the Repository boundary; Integration tests use real wiring.
4. Asserts state changes and outcomes.
5. Tests both happy path and error cases where applicable.
6. Names describe the behavior under test.

---

## When to Run Tests

- **Do NOT run the full test suite** unless explicitly asked (it slows down development).
- When tests ARE run, focus only on relevant tests:

  ```bash
  ./vendor/bin/phpunit --filter=CreateGroupActionTest
  ```

- If tests fail, fix ONLY the code related to the current feature.
- Do NOT fix unrelated test failures.

---

## Test Coverage Guidance

- Cover every RuledAction: happy path + at least one validation-failure path (assert
  `ValidationException` or `InvalidArgumentException('Payload must be an array.')` for scalar
  misuse).
- Repository tests cover the query methods a feature actually uses (only meaningful ones — no
  coverage-only shells).
- Guard clauses in Actions get a unit test asserting `expectException` +
  `expectExceptionMessage`.
- Do NOT chase percentage targets — meaningful behavior tests matter; a passing suite with no
  broken references beats a green percentage.
- Use `--filter` to run only touched tests during development; full suite is for pre-PR/CI
  validation.

---

## Broken Test Patterns (Never Reproduce)

- ❌ Calling `$action->execute($data)` — the method does not exist (`handle()` is the only
  invocation API; see `11-forbidden-behavior.md`).
- ❌ Mocking repositories in Feature/Integration tests when a real database is available.
- ❌ PHPDoc `@test` / bare `test_` prefix without `#[Test]` attribute.
- ❌ Omitting `: void` return types on test methods.

# CANONICAL SNIPPETS — Verbatim Reference (LingSID)

This module is the **authoritative, verbatim snippet bank** for the LingSID project. Every snippet below is copied unchanged from the actual LingSID codebase and carries its source anchor (`lingusid <path>:<line>`).

## Usage Rules

1. **Copy + adapt, never paraphrase.** When writing new code, open the most relevant snippet below (or the actual source file), copy it, and change only identifiers/values. This is what keeps new code byte-identical in style to the codebase.
2. **Anchor first.** Cross-check every signature (parameter defaults, return types, rule shapes) against the anchor before writing.
3. **Two styles may exist.** Where the codebase shows two valid forms (e.g. pipe-string vs `Rule::` array rules), the section marks which is canonical for which context. Copy the one matching your context.
4. **Never copy legacy/broken forms.** `// BAD` blocks show defects that exist in old code; do not reproduce them.
5. When a snippet references a class method you have not seen in the bank, check the base class in the real repo (`app/Abstractions/…`) — do not invent signatures.
6. **Evidence-anchored code (hallucination guard).** Before writing any code, enumerate the classes/methods/arguments you plan to use, and verify **each one against the actual codebase** — open the file at its anchor and read the real signature (return type, parameter list, defaults). A symbol you merely *believe* exists ("it probably has `update()`", "the method returns a Model") is not evidence. If verification fails, do not invent — find a real analogue in the bank or the repo, or stop and ask the operator. Memory is a guess; the repo is the source of truth.
7. After **fixing** a snippet's BAD form, re-verify the arising code compiles **and** the adjacent contracts still match the anchors (return types, rule shapes, array-payload protocol) — a visually-correct copy that breaks a signature is still a bug.

---

## 1. Action Base Classes & Contracts

### `Action` base — `lingusid app/Abstractions/Actions/Action.php:9`

```php
abstract class Action
{
    private bool $ruleBypassed = false;

    /**
     * Handle the action's logic.
     *
     * @param  array  $validatedPayload  The validated data.
     * @param  mixed  $payload  The original payload.
     */
    abstract protected function handler($payload = null, array $validatedPayload = []): mixed;

    /**
     * Execute the action.
     *
     * @param  array  $payload  The data for the action.
     */
    public function handle(mixed $payload = null)
    {
        if (! $this->ruleBypassed && $this instanceof RuledActionContract) {
            if (is_array($payload)) {
                $validator = Validator::make($payload, $this->rules($payload));

                return $this->handler($payload, $validator->validate());

            }

            throw new InvalidArgumentException('Payload must be an array.');
        }

        return $this->handler($payload);
    }
}
```

Signature facts you must preserve:

- `handler()` is **`protected`** and shaped `($payload = null, array $validatedPayload = []): mixed`.
- `handle(mixed $payload = null)` has **no return type declaration**.
- Ruled actions receive the **validated** data as `$validatedPayload`; non-ruled actions only get `$payload`.
- A ruled action called with a non-array payload throws `InvalidArgumentException('Payload must be an array.')`.

### `IndexAction` base — `lingusid app/Abstractions/Actions/IndexAction.php:7`

```php
abstract class IndexAction extends Action implements RuledActionContract
{
    /**
     * Get the validation rules for the index action.
     *
     * @param  array  $payload  The data for the action.
     */
    public function rules(array $payload): array
    {
        return [
            'keyword' => ['nullable', 'string'],
            'columns' => ['nullable', 'array'],
            'limit' => ['nullable', 'numeric'],
        ];
    }
}
```

### `RuledActionContract` — `lingusid app/Contracts/Action/RuledActionContract.php:5`

```php
interface RuledActionContract
{
    public function rules(array $payload): array;
}
```

### `InvokeableActionContract` — `lingusid app/Contracts/Action/InvokeableActionContract.php:5`

```php
interface InvokeableActionContract
{
    public function __invoke(array $payload);
}
```

> **Note:** `InvokeableActionContract` declares `__invoke(array $payload)` — there is **no `execute()`** method anywhere in the action abstraction. `$action->execute(...)` appears only in legacy tests and is a **broken reference to a non-existent method**. The invocation API is `handle()`.

---

## 2. Ruled Actions (validation in the action)

### Create — pipe-string rules — `lingusid app/Actions/Web/Article/CreateWebArticleAction.php:11`

```php
class CreateWebArticleAction extends Action implements RuledActionContract
{
    public function __construct(protected WebArticleRepository $webArticleRepository, protected GroupRepository $groupRepository) {}

    protected function handler($payload = null, array $validatedPayload = []): WebArticle
    {
        $article = $this->webArticleRepository->store([
            'title' => $validatedPayload['title'],
            'slug' => $validatedPayload['slug'],
            'content' => $validatedPayload['content'],
            'published_at' => $validatedPayload['published_at'] ?? null,
            'author_id' => $validatedPayload['author_id'],
        ]);

        if (isset($validatedPayload['group_id'])) {
            $article->groups()->sync($validatedPayload['group_id']);
        }

        return $article;
    }

    public function rules(array $payload): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:web_articles,slug',
            'content' => 'required|string',
            'published_at' => 'nullable|date',
            'author_id' => 'required|exists:users,id',
            'group_id' => 'nullable|exists:groups,id',
        ];
    }
}
```

### Update — pipe strings + per-record unique — `lingusid app/Actions/Web/Article/UpdateWebArticleAction.php:11`

```php
class UpdateWebArticleAction extends Action implements RuledActionContract
{
    public function __construct(protected WebArticleRepository $webArticleRepository, protected GroupRepository $groupRepository) {}

    protected function handler($payload = null, array $validatedPayload = []): WebArticle
    {
        $article = $this->webArticleRepository->update($validatedPayload['id'], [
            'title' => $validatedPayload['title'],
            'slug' => $validatedPayload['slug'],
            'content' => $validatedPayload['content'],
            'published_at' => $validatedPayload['published_at'] ?? null,
            'author_id' => $validatedPayload['author_id'],
        ]);

        if (isset($validatedPayload['group_id'])) {
            $article->groups()->sync($validatedPayload['group_id']);
        }

        return $article;
    }

    public function rules(array $payload): array
    {
        return [
            'id' => 'required|exists:web_articles,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:web_articles,slug,' . $payload['id'],
            'content' => 'required|string',
            'published_at' => 'nullable|date',
            'author_id' => 'required|exists:users,id',
            'group_id' => 'nullable|exists:groups,id',
        ];
    }
}
```

### Create — `Rule::` array style (SID domain) — `lingusid app/Actions/Sid/Resident/CreateSidResidentAction.php:11`

```php
class CreateSidResidentAction extends Action implements RuledActionContract
{
    public function __construct(protected SidResidentRepository $sidResidentRepository)
    {
    }

    public function rules(array $payload = []): array
    {
        return [
            'nik' => ['required', 'string', 'max:16', Rule::unique(SidResident::class)],
            'name' => ['required', 'string', 'max:255'],
            'no_kk' => ['nullable', 'string', 'max:16'],
            'address' => ['nullable', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', Rule::in(['L', 'P'])],
            'religion' => ['nullable', 'string', 'max:255'],
            'marital_status' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function handler($payload = null, array $validatedPayload = []): SidResident
    {
        return $this->sidResidentRepository->store($validatedPayload);
    }
}
```

### Update with a Model in the payload — `lingusid app/Actions/Sid/Resident/UpdateSidResidentAction.php:11`

```php
class UpdateSidResidentAction extends Action implements RuledActionContract
{
    public function __construct(protected SidResidentRepository $sidResidentRepository)
    {
    }

    public function rules(array $payload = []): array
    {
        return [
            'nik' => ['required', 'string', 'max:16', Rule::unique(SidResident::class)->ignore($payload['resident']->id)],
            'name' => ['required', 'string', 'max:255'],
            'no_kk' => ['nullable', 'string', 'max:16'],
            'address' => ['nullable', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', Rule::in(['L', 'P'])],
            'religion' => ['nullable', 'string', 'max:255'],
            'marital_status' => ['nullable', 'string', 'max:255'],
            'education' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function handler($payload = null, array $validatedPayload = []): bool
    {
        return $this->sidResidentRepository->update($payload['resident']->id, $validatedPayload);
    }
}
```

The Model is injected via the controller as part of the payload:

```php
// lingusid app/Http/Controllers/Sid/SidResidentController.php:49
$this->updateSidResidentAction->handle([
    'resident' => $resident,
] + $request->all());
```

### Delete — `Rule::exists` — `lingusid app/Actions/Web/Article/DeleteWebArticleAction.php:10`

```php
class DeleteWebArticleAction extends Action implements RuledActionContract
{
    public function rules($payload = []): array
    {
        return [
            'id' => ['required', 'integer', Rule::exists('web_articles', 'id')],
        ];
    }

    public function __construct(protected WebArticleRepository $webArticleRepository) {}

    protected function handler($payload = null, array $validatedPayload = []): bool
    {
        return $this->webArticleRepository->delete($validatedPayload['id']);
    }
}
```

**Rule-style summary (canonical by prevalence):**

- **Pipe strings** (`'required|string|max:255'`) are the canonical form for create/update rules — `Web/Article` and `Group` actions.
- **Array + `Rule::`** (`Rule::unique(...)`, `Rule::in(...)`, `Rule::exists(...)`) is used where a rule needs chaining or a closure over `$payload` — SID actions and `DeleteWebArticleAction`.
- Use `Rule::unique(Model::class)` (not table strings) for SID; `'unique:web_articles,slug,…'` (table strings) for Web/Article.

> **Caution (verbatim vs correct):** `ModelRepository::update(int|string $id, array $data): bool` returns a **bool**. The `UpdateWebArticleAction` snippet above reassigns that bool to a `WebArticle`-typed variable and calls `->groups()->sync()` on it — a **latent runtime defect** preserved verbatim from the codebase. When writing an update action, return the bool (`: bool`) or re-fetch the model; do not copy the reassignment bug.

---

## 3. Plain (non-ruled) Actions

### Collection query — `lingusid app/Actions/Web/Article/GetWebArticlesAction.php:10`

```php
class GetWebArticlesAction extends Action
{
    public function __construct(protected WebArticleRepository $webArticleRepository) {}

    /**
     * @return Collection<WebArticle>
     */
    public function handler($payload = [], array $validatedPayload = []): Collection
    {
        return $this->webArticleRepository->with(['author', 'groups'])->get();
    }
}
```

### Paginated list — `lingusid app/Actions/Sid/Resident/GetSidResidentsAction.php:9`

```php
class GetSidResidentsAction extends Action
{
    public function __construct(protected SidResidentRepository $sidResidentRepository)
    {
    }

    protected function handler($payload = null, array $validatedPayload = []): LengthAwarePaginator
    {
        return $this->sidResidentRepository->paginate();
    }
}
```

### Delete by Model payload — `lingusid app/Actions/Sid/Resident/DeleteSidResidentAction.php:8`

```php
class DeleteSidResidentAction extends Action
{
    public function __construct(protected SidResidentRepository $sidResidentRepository)
    {
    }

    protected function handler($payload = null, array $validatedPayload = []): bool
    {
        return $this->sidResidentRepository->delete($payload->id);
    }
}
```

Here the controller passes the route-model-bound Model itself: `handle($resident)` (`lingusid app/Http/Controllers/Sid/SidResidentController.php:58`).

### Orchestrating other actions + guard clause — `lingusid app/Actions/Group/EnsureSystemGroupExistsAction.php:11`

```php
class EnsureSystemGroupExistsAction extends Action
{
    public const SYSTEM_GROUP_PREFIX = 'system related group ';

    public function __construct(
        protected readonly GroupRepository $groupRepository,
        protected readonly CreateGroupAction $createGroupAction
    ) {}

    /**
     * @param  string  $groupKey  Slug atau identifier untuk grup sistem.
     * @param  array  $validatedPayload  Payload terverifikasi (jika validasi diaktifkan).
     */
    protected function handler($groupKey = null, array $validatedPayload = []): Group
    {
        if (! is_string($groupKey)) {

            throw new InvalidArgumentException('Expected string groupKey for group slug.');
        }

        $slug = Str::of($groupKey)->start(self::SYSTEM_GROUP_PREFIX)->slug()->toString();
        $group = $this->groupRepository->findBySlug($slug);

        if (! $group instanceof Group) {

            $name = Str::of($slug)->replace('-', ' ')->title()->toString();
            $description = sprintf('This group is for the %s functionalities.', Str::lower($name));

            return $this->createGroupAction->handle(compact('name', 'description'));
        }

        return $group;
    }
}
```

Note the two idiomatic details: `protected readonly` promoted properties (sparingly, for orchestration dependencies), `Str::of(...)->...->toString()` fluent chains, and `sprintf()`.

---

## 4. Controllers

### Constructor injection style (SID) — `lingusid app/Http/Controllers/Sid/SidResidentController.php:14`

```php
class SidResidentController extends Controller
{
    public function __construct(
        protected GetSidResidentsAction $getSidResidentsAction,
        protected CreateSidResidentAction $createSidResidentAction,
        protected UpdateSidResidentAction $updateSidResidentAction,
        protected DeleteSidResidentAction $deleteSidResidentAction
    ) {}

    public function index()
    {
        return Inertia::render('Sid/Population/Residents/Index', [
            'residents' => $this->getSidResidentsAction->handle(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Sid/Population/Residents/Create');
    }

    public function store(Request $request)
    {
        $this->createSidResidentAction->handle($request->all());

        return redirect()->route('dashboard.sid.population.residents.index');
    }

    public function edit(SidResident $resident)
    {
        return Inertia::render('Sid/Population/Residents/Edit', [
            'resident' => $resident,
        ]);
    }

    public function update(Request $request, SidResident $resident)
    {
        $this->updateSidResidentAction->handle([
            'resident' => $resident,
        ] + $request->all());

        return redirect()->route('dashboard.sid.population.residents.index');
    }

    public function destroy(SidResident $resident)
    {
        $this->deleteSidResidentAction->handle($resident);

        return redirect()->route('dashboard.sid.population.residents.index');
    }
}
```

### Method injection style (Web) — `lingusid app/Http/Controllers/Web/WebArticleController.php:18`

```php
public function index(Request $request, GetWebArticlesAction $getWebArticlesAction)
{
    $articles = $getWebArticlesAction->handle($request->all());

    return Inertia::render('Web/Articles/Index', [
        'articles' => $articles,
    ]);
}

public function create(EnsureSystemGroupExistsAction $ensureSystemGroupExistsAction)
{
    $categoryGroup = $ensureSystemGroupExistsAction->handle(GroupEnum::CONTENT_ARTICLE_CATEGORY->value);

    return Inertia::render('Web/Articles/Create', [
        'groups' => $categoryGroup->children,
    ]);
}

public function store(Request $request, CreateWebArticleAction $createWebArticleAction)
{
    $createWebArticleAction->handle($request->all());

    return redirect()->route('dashboard.web.articles.index');
}
```

**Both injection styles are present in the codebase.** Canonical preference: constructor injection when a controller uses several actions (SID); method injection is acceptable for one-off actions (Web). Match the style of the controller file you are editing.

**Invocation protocol (MUST):**

- Pass the whole input as a single array: `handle($request->all())` or `handle(['resident' => $resident] + $request->all())`.
- You may pass **no argument** (`handle()`) for non-ruled list/dashboard actions.
- You may pass a **Model** (`handle($resident)`) only to a **non-ruled** action.

---

## 5. Repositories

### Empty model-inferred repository — `lingusid app/Repositories/Web/WebArticleRepository.php:8`

```php
/**
 * @extends ModelRepository<WebArticle>
 */
class WebArticleRepository extends ModelRepository
{
    //
}
```

The base class resolves the Eloquent model from the repository class name — a repository that covers the standard API (`all`, `find`, `findOrFail`, `store`, `update`, `delete`, `paginate`, `findBySlug`, `with`) is an empty shell. Add a method only when the base does not cover a query.

### Repository with a scoped query — `lingusid app/Repositories/GroupRepository.php:8`

```php
class GroupRepository extends ModelRepository
{
    public function indexByParentId(?int $parentId = null)
    {
        return $this->query(fn (Builder $query) => $query->where('parent_id', $parentId));
    }
}
```

Custom query methods use `$this->query(fn (Builder $query) => …)` and return the `Builder`.

### `ModelRepository` base API — `lingusid app/Abstractions/Repository/ModelRepository.php:17`

Methods every repository inherits (verbatim signatures):

```php
final public static function getNamespace(): string
final public function query(?Closure $callable = null): Builder
final public static function resolve(string $modelName): static

public function all(array $columns = ['*']): Collection
public function find(int|string $id, array $columns = ['*']): ?Model
public function findOrFail(int|string $id, array $columns = ['*']): Model
public function store(array $data): Model
public function update(int|string $id, array $data): bool
public function delete(int|string $id): bool
public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
public function findBySlug(string $slug, array $columns = ['*']): ?Model
public function with($relations): Builder
```

`ModelRepositoryContract` (`lingusid app/Contracts/Repository/ModelRepositoryContract.php:11`) declares `resolve`, `query`, `all`, `find`, `findOrFail`, `store`, `update`, `delete`, `paginate`, `findBySlug` — extending `RepositoryContract`.

---

## 6. Models & Traits

### Simple model — `lingusid app/Models/Sid/SidResident.php:8`

```php
class SidResident extends Model
{
    use HasFactory;

    protected $table = 'sid_residents';

    protected $fillable = [
        'nik',
        'name',
        'no_kk',
        'address',
        'birth_place',
        'birth_date',
        'gender',
        'religion',
        'marital_status',
        'education',
        'occupation',
        'nationality',
        'father_name',
        'mother_name',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];
}
```

### Model with relationship + trait — `lingusid app/Models/Web/WebArticle.php:9`

```php
class WebArticle extends Model
{
    use HasFactory, HasGroups;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'web_articles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'published_at',
        'author_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(\App\Models\User::class, 'author_id');
    }
}
```

### Model with Sluggable — `lingusid app/Models/Group.php:12`

```php
class Group extends Model
{
    use HasFactory;
    use HasGroups;
    use HasMetadata;
    use Sluggable;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Return the sluggable configuration array for this model.
     */
    public function sluggable(): array
    {
        return ['slug' => ['source' => 'name']];
    }

    /**
     * Get the children groups.
     */
    public function children(): MorphToMany
    {
        return $this->morph(static::class);
    }

    public function morph($entity): MorphToMany
    {
        return $this->morphedByMany($entity, 'groupable', 'model_has_groups');
    }
}
```

### Reusable trait — `lingusid app/Abstractions/Traits/Model/HasGroups.php:8`

```php
trait HasGroups
{
    /**
     * Get all of the groups for the model.
     */
    public function groups(): MorphToMany
    {
        return $this->morphToMany(Group::class, 'groupable', 'model_has_groups');
    }
}
```

---

## 7. Enums — `lingusid app/Enums/System/GroupEnum.php:1`

```php
public enum GroupEnum: string
{
    case DASHBOARD_SIDEBAR_MENU = 'dashboard sidebar menu';
    case CONTENT_ARTICLE_CATEGORY = 'content article category';
    case RESIDENT_RELIGION = 'resident religion';
    // … (17 cases)
}
```

Enum usage in controllers/actions is via `GroupEnum::CASE->value`, e.g. `$ensureSystemGroupExistsAction->handle(GroupEnum::CONTENT_ARTICLE_CATEGORY->value)`.

---

## 8. Domain Exception — `lingusid app/Exceptions/Model/Group/CircularMembershipException.php:8`

```php
class CircularMembershipException extends Exception
{
    public function __construct(
        public readonly Group $source,
        public readonly Group $target
    ) {
        parent::__construct(
            __('messages.exceptions.models.group.circular_membership', [
                'target' => $target->id,
                'source' => $source->id,
            ])
        );
    }

    public function context(): array
    {
        return [
            'source_group_id' => $this->source->id,
            'target_group_id' => $this->target->id,
            'source_group_name' => $this->source->name,
            'target_group_name' => $this->target->name,
        ];
    }
}
```

Properties are `public readonly`, the message is built from a translation key with named replacements, and a `context(): array` method supplies structured data.

---

## 9. Tests

### Unit test (Action with mocked repository) — `lingusid tests/Unit/Actions/Group/CreateGroupActionTest.php:12`

```php
class CreateGroupActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(GroupRepository::class, $this->createMock(GroupRepository::class));
    }

    #[Test]
    public function it_creates_a_group_with_valid_data(): void
    {
        $data = [
            'name' => 'Test Group',
            'description' => 'Ea est dolor consequatur cum rerum.',
            'type' => 'test_type',
            'url' => 'http://example.com',
            'icon' => 'test_icon',
        ];

        $this->mock(GroupRepository::class, function ($mock) use ($data) {
            $mock->shouldReceive('store')
                ->once()
                ->andReturn(new Group($data));
        });

        $action = new CreateGroupAction($this->app->make(GroupRepository::class));
        $group = $action->handle($data);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals('Test Group', $group->name);
    }
}
```

> The original file calls `$action->execute($data)` — that method does not exist on `Action`; the test is a known broken reference. Write new unit tests with `$action->handle($data)`.

### Feature test — `lingusid tests/Feature/Web/WebArticleTest.php:13`

```php
class WebArticleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Group $categoryGroup;
    protected Group $articleCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->categoryGroup = Group::factory()->create(['name' => GroupEnum::CONTENT_ARTICLE_CATEGORY->value]);
        $this->articleCategory = Group::factory()->create(['parent_id' => $this->categoryGroup->id]);
    }

    #[Test]
    public function user_can_view_articles_index(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard.web.articles.index'))
            ->assertOk();
    }

    #[Test]
    public function user_can_create_article(): void
    {
        $this->actingAs($this->user)
            ->post(route('dashboard.web.articles.store'), [
                'title' => 'Test Article',
                'slug' => 'test-article',
                'content' => 'This is a test article content.',
                'published_at' => now()->format('Y-m-d H:i:s'),
                'author_id' => $this->user->id,
                'group_id' => $this->articleCategory->id,
            ])
            ->assertRedirect(route('dashboard.web.articles.index'));

        $this->assertDatabaseHas('web_articles', [
            'title' => 'Test Article',
            'slug' => 'test-article',
            'content' => 'This is a test article content.',
            'author_id' => $this->user->id,
        ]);

        $article = WebArticle::where('slug', 'test-article')->first();
        $this->assertCount(1, $article->groups);
        $this->assertEquals($this->articleCategory->id, $article->groups->first()->id);
    }
}
```

Test naming reality in the codebase: `#[Test]` attribute on every test method, with snake_case descriptive names starting `user_can_…`, `it_…`, or `test_…` — pick the pattern of the neighbouring test file.

---

## 10. Frontend Page — `lingusid resources/js/pages/Web/Articles/Index.vue:1`

```vue
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

interface Props {
    articles: {
        data: {
            id: number;
            title: string;
            slug: string;
            published_at: string;
            author: {
                name: string;
            };
        }[];
        links: any[];
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Website',
        href: '/dashboard/web/articles',
    },
    {
        title: 'Artikel',
        href: '/dashboard/web/articles',
    },
];

</script>

<template>
    <Head title="Artikel" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>Artikel</CardTitle>
                        <CardDescription>Daftar semua artikel yang terdaftar di dalam sistem.</CardDescription>
                    </div>
                    <Link :href="route('dashboard.web.articles.create')">
                        <Button>Tambah Artikel</Button>
                    </Link>
                </div>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Judul</TableHead>
                            <TableHead>Slug</TableHead>
                            <TableHead>Penulis</TableHead>
                            <TableHead>Tanggal Terbit</TableHead>
                            <TableHead>Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="article in props.articles.data" :key="article.id">
                            <TableCell>{{ article.title }}</TableCell>
                            <TableCell>{{ article.slug }}</TableCell>
                            <TableCell>{{ article.author.name }}</TableCell>
                            <TableCell>{{ article.groups.map(g => g.name).join(', ') }}</TableCell>
                            <TableCell>{{ article.published_at }}</TableCell>
                            <TableCell>
                                <div class="flex gap-2">
                                    <Link :href="route('dashboard.web.articles.edit', article.id)">
                                        <Button variant="outline" size="sm">Edit</Button>
                                    </Link>
                                    <Link :href="route('dashboard.web.articles.destroy', article.id)" method="delete" as="button" type="button">
                                        <Button variant="destructive" size="sm">Hapus</Button>
                                    </Link>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </AppLayout>
</template>
```

Style re-usable in any page: `<script setup lang="ts">`, typed `Props` interface, shadcn-vue `ui/*` primitives, `+` merge operator for inline props overrides (`variant="outline" size="sm"`), ziggy `route()` strings, delete links via `method="delete" as="button"`.

---

## 11. Anti-Patterns in the Wild (do NOT reproduce)

These exist in legacy files but are broken or non-canonical. Never copy them into new code; see `11-forbidden-behavior.md`:

```php
// BAD — calls a non-existent method (Action has no execute())
$action->execute($data);

// BAD — extra argument is ignored; scalar payload breaks RuledAction
$action->handle($id, $request->all());

// BAD — scalar payload into a RuledAction (throws InvalidArgumentException)
$ruledAction->handle($id);

// BAD — static call to an instance method
XxxAction::handle($data);
```

Canonical replacements:

```php
$action->handle($request->all());                 // ruled action: single array payload
$action->handle(['id' => $id]);                   // ruled delete/update: id inside the array
$action->handle(['resident' => $resident] + $request->all()); // model-aware update
$action->handle($request->all());                 // non-ruled action: input array
$action->handle();                                // non-ruled action with no input
```

---

## 12. Migrations & Schema

### Create table with FK constraint — `lingusid database/migrations/2025_09_01_024339_create_web_articles_table.php:14`

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_articles');
    }
};
```

### Pivot table (morphs + composite unique) — `lingusid database/migrations/2025_07_10_070450_create_model_has_groups_table.php:14`

```php
Schema::create('model_has_groups', function (Blueprint $table) {
    $table->foreignId('group_id')->constrained()->onDelete('cascade');
    $table->morphs('groupable');
    $table->unique(['group_id', 'groupable_id', 'groupable_type'], 'model_has_groups_group_model_unique');
});
```

Style facts:

- `$table->id()` integer auto-increment PK; `$table->foreignId(...)->constrained(...)->onDelete('cascade')` for FKs.
- Pivot tables have NO `id` column — two FKs + optional composite `unique([...], '{table}_group_model_unique')`.
- `$table->morphs('groupable')` for polymorphic relation columns (`groupable_id` + `groupable_type`).
- Self-referencing FK example: `$table->foreignId('parent_id')->nullable()->constrained('menus')->onDelete('cascade')` (`create_menus_table.php:20`).
- Unique single-column example: `$table->string('slug')->unique()` (`create_groups_table.php:17`).

---

## 13. Factories

### Factory with state methods — `lingusid database/factories/GroupFactory.php:9`

```php
class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
        ];
    }

    public function main(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'main',
            ];
        });
    }

    public function sidebar(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'sidebar',
            ];
        });
    }

    public function footer(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'footer',
            ];
        });
    }
}
```

Style facts:

- `protected $model = Group::class;` — always declared.
- `definition()` returns deterministic fake data; slug derived from the same `$name` via `Str::slug($name)` (never `$this->faker->slug()` which could disagree with the name).
- Variants via `state()` returning a factory from a public method (`main()` / `sidebar()` / `footer()`). Other project factories (`MenuFactory`, `TermFactory`, `MetadataFactory`) follow the same shape — `protected $model` + `definition(): array`.

---

## 14. Seeders

### Idempotent system-group seeder — `lingusid database/seeders/GroupSeeder.php:13`

```php
class GroupSeeder extends Seeder
{
    public function run(EnsureSystemGroupExistsAction $ensureSystemGroupExistsAction): void
    {
        DB::transaction(function () use ($ensureSystemGroupExistsAction) {
            $mainGroup = $ensureSystemGroupExistsAction->handle('main');

            progress(
                label  : '⏳ Menyiapkan grup sistem',
                steps  : GroupEnum::cases(),
                callback: function ($groupEnum) use ($ensureSystemGroupExistsAction, $mainGroup) {
                    $mainGroup->morph(Group::class)->save($ensureSystemGroupExistsAction->handle($groupEnum->value));
                }
            );
        });
    }
}
```

Style facts:

- Seeders inject Actions via **method injection** in `run(SomeAction $action): void` and delegate to them instead of touching repositories/model logic directly.
- Mutations wrapped in `DB::transaction()`.
- `Laravel\Prompts\progress(label:, steps:, callback:)` for progress reporting on batch operations (named arguments).
- System seeding is idempotent via `EnsureSystemGroupExistsAction` (create-if-missing). `TermSeeder.php:36` and `MenuSeeder.php:16` follow the same method-injection + transaction shape.

---

## 15. Middleware

### Share middleware (Inertia data) — `lingusid app/Http/Middleware/ShareDashboardData.php:11`

```php
class ShareDashboardData
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->shareMenu();

        return $next($request);
    }

    protected function shareMenu(): void
    {
        Inertia::share([
            'sidebarMenus' => function (GetAllSidebarMenuAction $getAllSidebarMenu) {
                return $getAllSidebarMenu->handle();
            },
        ]);
    }
}
```

Style facts:

- Middleware resolves an Action lazily via a closure inside `Inertia::share()` (deferred until the shared data is actually consumed).
- `handle(Request $request, Closure $next): Response` — standard signature; shared helpers extracted to `protected` methods (`shareMenu(): void`).
- Sibling middleware: `HandleAppearance.php`, `HandleInertiaRequests.php`, `ShareWebData.php`. Protected app routes run under `['auth', 'verified', ShareDashboardData::class]`.

---

## 16. Policies

> [!WARNING]
> The ONLY policy file in the reference codebase — `lingusid app/Policies/ContentArticlePolicy.php:8` —
> is a **placeholder defect**: it imports `App\Models\Content\ContentArticle` (a model that does NOT
> exist in the codebase) and every method returns `false`. It is not registered/used anywhere.
> Do NOT copy it as a template. When a real policy is needed, write one that performs actual
> authorization (e.g. ownership checks, `$user->can()`, roles) and references a REAL model.

```php
// BAD — jangan tiru (lingusid app/Policies/ContentArticlePolicy.php:8)
class ContentArticlePolicy
{
    public function viewAny(User $user): bool { return false; }   // placeholder
    public function view(User $user, ContentArticle $contentArticle): bool { return false; }
    // … create/update/delete/restore/forceDelete all return false …
    // App\Models\Content\ContentArticle does not exist in the codebase.
}
```

**Canonical expectation for new policies** (per `07-security.md`):

- Reference a real model (`App\Models\Web\WebArticle`, `App\Models\Sid\SidResident`, …).
- Implement actual authorization logic — never ship a `false`-returning stub.
- Register via `Gate::policy()` / Laravel auto-discovery when referenced from controllers or `$this->authorize()`.

---

## Self-Explanatory Code Demonstrations

Constitution principle #13 and `04-coding-standards.md` → Code Documentation demand that every line read like a human explaining what it does — no explanatory comments required. Below: one verbatim GOOD example from LingSID, then an illustrative BAD→GOOD pair.

### GOOD (verbatim) — guard clause + fluent chain, zero comments — `lingusid app/Actions/Group/EnsureSystemGroupExistsAction.php:25`

```php
protected function handler($groupKey = null, array $validatedPayload = []): Group
{
    if (! is_string($groupKey)) {

        throw new InvalidArgumentException('Expected string groupKey for group slug.');
    }

    $slug = Str::of($groupKey)->start(self::SYSTEM_GROUP_PREFIX)->slug()->toString();
    $group = $this->groupRepository->findBySlug($slug);

    if (! $group instanceof Group) {

        $name = Str::of($slug)->replace('-', ' ')->title()->toString();
        $description = sprintf('This group is for the %s functionalities.', Str::lower($name));

        return $this->createGroupAction->handle(compact('name', 'description'));
    }

    return $group;
}
```

What makes this self-explanatory: an early guard throws before work starts, intent-revealing names (`findBySlug`, `createGroupAction`) remove the need for "what/why" comments, conditions are plain (`! $group instanceof Group`), and the return path is linear. Note: no comment restates any line.

### BAD (illustrative — NOT verbatim LingSID) — code that forces a comment

```php
// check if user is admin. if the user is not an admin but has role equal to
// the admin-adjacent flag from the request we still allow it. otherwise reject.
if ($user->role_type === 3 || $request->admin_adjacent === true && $user->is_active == 1) {
    return redirect('/home'); // let the user pass
}
```

Problems (each is a rule violation on its own):

- Comments restate what the code does (chit-chat).
- Magic number `3` and `true`/`== 1` hide intent.
- Operator precedence makes the condition hard to read even with a comment.
- The line "needs a comment to be understood" → the code must change, not be annotated.

### GOOD (illustrative rewrite of the same logic)

```php
const int ROLE_TYPE_ADMIN = 3;

if ($user->isNotAdmin() && ! $request->isAdminAdjacentApproved()) {
    abort(403);
}

return $this->redirectAdminDashboard();
```

What changed: guarded conditions read as English, repeated intent extracted into named methods/constants, early abort simplifies the happy path, and every comment disappeared because the names carry the meaning.

When real LingSID code is ambiguous (e.g. the `UpdateWebArticleAction` bool-reassignment defect above), the rule is the same: **fix the code, do not comment it** — never copy the defect with an explanatory comment attached.

---

## Logical Links

- Architecture & layering: `03-architecture.md`
- Method/naming reference: `05-naming.md`
- Test patterns: `06-testing.md`
- Database/migration/factory/seeder rules: `13-database.md`
- Frontend rules: `14-frontend.md`
- Explicit prohibitions: `11-forbidden-behavior.md`
- LingSID invariants: `12-project-specific/lingusid.md`

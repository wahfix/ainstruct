# DATABASE — Migrations, Models, Queries, Factories

This file defines database rules: migrations, Eloquent relationships, factories, seeders, query patterns, transactions, and indexing conventions.

---

## Migration Conventions

### Naming

- Migration filenames use Laravel's timestamp format: `2024_01_01_000000_create_{table}_table.php`.
- Create tables: `create_{table}_table` (plural snake_case).
- Add columns: `add_{column}_to_{table}_table`.
- Pivot tables: `create_{model}_has_{relation}_table`.

### Table Structure

```php
Schema::create('web_articles', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('content');
    $table->timestamp('published_at')->nullable();
    $table->foreignId('author_id')->constrained('users');
    $table->timestamps();
});
```

### Column Rules

- Use `$table->id()` for auto-incrementing integer primary keys (project convention — see `03-architecture.md` Architectural Decision #7).
- Use `$table->string()` for VARCHAR; `$table->text()` for long content.
- Use `$table->timestamp()` for datetime columns, not `$table->dateTime()`.
- Use `$table->boolean()` for flags; `$table->integer()` for counters/enums-as-int.
- Use `$table->json()` for structured data when the column is array/JSON.
- Always use `$table->timestamps()` for `created_at`/`updated_at`.
- Soft deletes: `$table->softDeletes()` — only when business requires recovery.

### Foreign Keys

- Always use `->constrained()` on foreign key migrations (enforces referential integrity at DB level).
- Use `->constrained('table_name')` when the table name cannot be inferred from the column name.
- Use `->nullOnDelete()` for optional relationships; `->cascadeOnDelete()` for owned relationships.
- Pivot tables do NOT use `$table->id()` — use `$table->foreignId()` pairs instead.

```php
// Canonical: foreign key with constraint
$table->foreignId('author_id')->constrained('users');

// Pivot table: no id, two foreign keys
$table->foreignId('model_id')->constrained()->cascadeOnDelete();
$table->foreignId('group_id')->constrained()->cascadeOnDelete();
```

### Indexing

- Add index on foreign key columns used in WHERE/JOIN.
- Add index on `slug` columns (used by `findBySlug()`).
- Add composite index when queries filter on multiple columns frequently.
- Do NOT over-index — each index slows writes.

```php
$table->string('slug')->unique();
$table->foreignId('author_id')->constrained('users')->index();
$table->index(['parent_id', 'type']); // composite index
```

---

## Eloquent Relationships

### Relationship Types

| Type | Method | Usage |
|------|--------|-------|
| One-to-One | `hasOne()` / `belongsTo()` | User ↔ Profile |
| One-to-Many | `hasMany()` / `belongsTo()` | User → Articles |
| Many-to-Many | `belongsToMany()` / `morphToMany()` | Articles ↔ Groups |
| Polymorphic One-to-Many | `morphMany()` | Commentable models |
| Polymorphic Many-to-Many | `morphToMany()` | Taggable, Groupable |

### Naming Rules

- Foreign key column: `{related_table_singular}_id` (e.g., `author_id`, `group_id`).
- Method name: singular for hasOne/belongsTo (`author()`, `group()`).
- Method name: plural for hasMany/belongsToMany (`articles()`, `groups()`).
- Pivot table for belongsToMany: `{model}_has_{relation}` (e.g., `model_has_groups`).

### Polymorphic Taxonomy (LingSID-specific)

The project uses a polymorphic taxonomy system — see `03-architecture.md` Reusable Taxonomy section. Models opt in via `HasGroups` / `HasMetadata` traits. Do NOT hardcode group behavior; use the traits and `GroupEnum`.

```php
// Canonical: HasGroups trait
public function groups(): MorphToMany
{
    return $this->morphToMany(Group::class, 'groupable', 'model_has_groups');
}
```

### Relationship Best Practices

- Define relationships on the **owning** model (the one that "has").
- Use `$fillable` on pivot data when needed for mass assignment.
- Eager-load relationships to avoid N+1 queries — see Query Patterns below.
- Do NOT put business logic in relationship methods.

---

## Model Conventions

### Fillable & Casts

```php
class WebArticle extends Model
{
    use HasFactory, HasGroups;

    protected $table = 'web_articles';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'published_at',
        'author_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];
}
```

### Rules

- Always declare `$table` explicitly (do not rely on Laravel's pluralization guess).
- Always declare `$fillable` — never leave it empty or missing.
- Use `$casts` for dates, JSON, enums, and decimals.
- Use `$hidden` for sensitive columns (`password`, `remember_token`).
- Models do NOT contain business logic — business logic belongs in Actions/Services (see `03-architecture.md`).
- Use traits for cross-cutting model concerns (`HasGroups`, `HasMetadata`, `HasFactory`).

---

## Query Patterns

### Eager Loading (N+1 Prevention)

```php
// Always eager-load relationships when accessing them in views/lists
$this->webArticleRepository->with(['author', 'groups'])->get();

// In repository: use the base with() method
return $this->with(['author', 'groups'])->all();
```

**Rule:** If a template iterates over a relationship (e.g., `@each`, `v-for`), the parent query MUST eager-load it. Check query log or Laravel Debugbar for N+1 warnings.

### Scoped Queries

```php
// Repository: custom scoped query
class GroupRepository extends ModelRepository
{
    public function indexByParentId(?int $parentId = null)
    {
        return $this->query(fn (Builder $query) => $query->where('parent_id', $parentId));
    }
}
```

### Query Builder Best Practices

- Use `select()` to limit columns when only a subset is needed (especially for lists/exports).
- Use `whereHas()` / `whereDoesntHave()` for relationship-based filtering.
- Use `orderBy()` explicitly — do not rely on database insertion order.
- Use `paginate()` for large result sets — never `->get()` on unbounded queries.
- Use `chunk()` or `cursor()` for processing large datasets in memory-efficient ways.

### Forbidden Query Patterns

- ❌ `Model::all()` in controllers/actions — use repository methods.
- ❌ `DB::table()` raw queries without strong justification — use Eloquent.
- ❌ Raw SQL string interpolation (`DB::select("SELECT * FROM users WHERE id = $id")`).
- ❌ Unbounded `->get()` on queries that could return thousands of rows.

---

## Transactions

### When to Use

- Mutations that affect multiple tables or involve multiple steps.
- Any operation where partial failure would leave inconsistent data.
- Bulk operations that must be atomic.

### Pattern

```php
DB::transaction(function () use ($payload) {
    $resident = $this->sidResidentRepository->store($payload);
    $this->metadataRepository->store([
        'model_id' => $resident->id,
        'model_type' => SidResident::class,
        'key' => 'registration_source',
        'value' => 'web',
    ]);
});
```

### Rules

- Use `DB::transaction()` for multi-step mutations.
- Do NOT wrap single operations in transactions (unnecessary overhead).
- Transactions MUST be short — do not make API calls or heavy processing inside a transaction.
- If a transaction throws, Laravel auto-rolls-back — do NOT manually catch and retry without re-throwing.

---

## Factories

### Convention

- One factory per model, in `database/factories/`.
- Factory class: `{Model}Factory`.
- Method: `definition()` returns array of fake data.

```php
class WebArticleFactory extends Factory
{
    protected $model = WebArticle::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'slug' => $this->faker->slug(),
            'content' => $this->faker->paragraphs(3, true),
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'author_id' => User::factory(),
        ];
    }
}
```

### Rules

- Use `User::factory()` as default foreign key value — it resolves via relationship.
- Override defaults in tests: `User::factory()->create(['name' => 'Admin'])`.
- Factory data MUST be realistic but deterministic (use `$this->faker`).
- Do NOT put business logic in factories.

---

## Seeders

### Convention

- One seeder per context/domain in `database/seeders/`.
- System data seeders (GroupEnum-based groups, default roles) run via `DatabaseSeeder`.
- Use `firstOrCreate()` for idempotent seeding — re-running does not create duplicates.

```php
class GroupSeeder extends Seeder
{
    public function run(): void
    {
        foreach (GroupEnum::cases() as $case) {
            Group::firstOrCreate(
                ['slug' => Str::of($case->value)->slug()->toString()],
                ['name' => $case->value, 'description' => 'System group for ' . $case->value]
            );
        }
    }
}
```

### Rules

- Seeders MUST be idempotent — safe to run multiple times.
- Use `firstOrCreate()` / `updateOrCreate()` instead of `create()` for system data.
- Test data seeders (for development) go in `database/seeders/` but are NOT referenced from `DatabaseSeeder` in production.
- Production seeders are for system constants, default roles/permissions, and reference data only.

---

## Soft Deletes

- Use `$table->softDeletes()` in migration and `SoftDeletes` trait on model ONLY when:
  - Business requires audit trail / recovery of deleted records.
  - Related data would be orphaned by hard delete.
- Do NOT soft-delete system-critical data (roles, permissions, system groups).
- Query scope: use `withTrashed()` / `onlyTrashed()` explicitly when needed.
- Do NOT add soft delete to pivot tables.

---

## Performance-Conscious Coding

Every feature that touches data gets a performance lens — applied during implementation, not as
an afterthought. The customer-facing symptom is a slow page; the reproducible causes are below.

### Per Query — no N+1, no over-fetch

- Eager load relations traversed in loops (`->with('relation')`); if a loop accesses
  `$item->relation`, `with()` is mandatory (see `05-naming.md`/patterns above).
- Select only the columns the feature needs for large reads (`->select()` / `->pluck()` /
  `->pluck(... 'keyBy')`).
- Prefer aggregate queries over read-then-loop: `groupBy`, `withCount`, `min`/`max` — never
  `count($model->items)` inside a loop.
- Avoid per-row queries: replace `find()` inside a loop with `whereIn()`; map results once.
- Batch mutations: `insert()`/`upsert()` for bulk, chunked processing for large sets
  (`->chunkById()` / cursor) — never load a big table into memory.

### Per Migration — make the index part of the schema

- Index every column used in WHERE/JOIN/ORDER beyond the primary key; composite index for
  multi-column filters (ordering: first high-selectivity, then filter order).
- `->foreignId()` must pair with `->constrained()` — referential integrity is also an
  index/perf guarantee.
- Add the index **in the migration that creates the column** — not as a later patch.

### Per List/Collection — paginate, always

- Paginate or cursor-paginate every listing (`->paginate()` / `->cursorPaginate()`); never emit
  an unbounded table.
- Cursor pagination for streaming/ordered-by-time lists; cap `per_page` server-side.
- The page size that "worked in dev" is not a reason to skip pagination in prod.

### Verification

- Confirm with the query log (Debugbar/Telescope — `09-tools.md`) that a page issues the
  expected number of queries; if the number grows with row count, that is N+1 and it is a bug.
- Frontend: verify lists/selects are not re-fetched per render (`14-frontend.md`) and heavy
  computed values are memoized.

### Common Smell → Fix

| Smell | Fix |
|-------|-----|
| `foreach ($items as $i) { $i->relation }` | eager load `->with('relation')` |
| `count($model->items)` in a loop | `->withCount('items')` |
| per-row `->find()` inside the loop | `whereIn()` + one map |
| WHERE column with no index | add index in the creating migration |
| unbounded `Model::all()` select | paginate + `->select()` needed columns |

---

## Database Naming (Cross-Reference)

See `05-naming.md` for full naming conventions. Quick reference:

- Tables: plural, snake_case, context-prefixed (`sid_residents`, `web_articles`).
- Columns: snake_case, foreign keys `{related}_id`.
- Pivot tables: `model_has_{relation}` (no `$table->id()`).
- Enums stored as strings in DB.

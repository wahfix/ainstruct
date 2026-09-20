# DATABASE — Schema, Queries, Transactions (Conditional)

This module applies **only when the project uses a database**. The template does not lock the DB
layer (raw PDO, a query builder, an ORM, or a document store are all project decisions). The
chosen data-access tool, connection details, and schema are recorded in
`MASTER_BUILD_SPECIFICATION.md` — never invent them. The rules below are the universal invariants
that hold regardless of the chosen tool.

---

## Data-Access Invariants (apply to any data source)

- **Repository-only access.** All data access happens inside Repositories
  (`03-architecture.md`). No raw queries in Entry handlers, Actions, Services, or Entities.
- **Prepared statements / parameter binding.** Any query that includes user input uses prepared
  statements with bound parameters — never string concatenation of user input into SQL. This is
  non-negotiable (`07-security.md`).
- **No per-row queries in loops.** Repositories build joins/aggregations/batched queries; a
  query executed inside a loop is an N+1 smell to fix at the Repository level.
- **Return shapes are decided once.** A Repository returns entities, records, or arrays per the
  project convention (`MASTER_BUILD_SPECIFICATION.md`); consumers never guess the shape.

---

## Schema Conventions (when the project manages a schema)

The template does not mandate a migration tool (raw SQL scripts, a migration library, or a
bootstrap schema file are all valid — the choice is recorded in `MASTER_BUILD_SPECIFICATION.md`).
Whatever the tool, these conventions apply:

### Naming

- Tables: plural, snake_case (`users`, `groups`, `residents`, `articles`).
- Pivot tables: `{model}_has_{relation}` — `group_members`, `model_has_groups`.
- Columns: snake_case (`birth_date`, `parent_id`).
- Foreign keys: `{related_table_singular}_id` (e.g., `author_id`, `group_id`).
- Timestamps: `created_at`, `updated_at` where the schema uses them.
- Status/enum columns: strings matching the PHP enum values.

### Primary Keys

- Integer auto-increment (`id`) by default; UUID only when the project explicitly requires it
  (recorded in the spec).

### Column Rules

- Explicit types per column — never ambiguous text where a boolean/date/int is meant.
- `NOT NULL` + sensible defaults where the domain allows; `NULL` only when absence is meaningful.
- Foreign keys get an index/constraint — referential integrity is enforced at the data layer,
  not just in application code.
- Index columns used in `WHERE`/`JOIN`/`ORDER BY` for the queries the feature actually runs;
  do not index speculatively.

---

## Query Conventions (Repository Level)

- **Read the spec for column names** — never guess a column; verify against
  `MASTER_BUILD_SPECIFICATION.md` or the actual schema before writing a query.
- **Compose queries in the Repository**, using the project's chosen API (PDO prepared
  statements, query builder, ORM).
- **Select explicit columns** where the feature does not need `*` (sensitive data excluded —
  `07-security.md`).
- **Pagination/limits are explicit** — a list feature defines `per_page`/`page` bounds and
  clamps them (edge probes — `15-edge-cases.md`).
- **Soft delete is a decision** — used only when the business requires recovery; recorded in the
  spec. Missing vs soft-deleted records are distinct states (edge probes — `15-edge-cases.md`).

---

## Transactions & Concurrency

- **Multi-step mutations run in a transaction** (`beginTransaction`/`commit`/`rollback` or the
  chosen tool's equivalent) so a partial failure cannot corrupt state (`19-data-reliability.md`).
- **Locking** is used where races matter (unique creation, counters, idempotent operations); the
  specific strategy (optimistic vs pessimistic) is a project decision — record it in the spec.
- **Idempotent backfills/upserts** — re-running yields the same result
  (`firstOrCreate`-style upsert or `ON DUPLICATE KEY` semantics).
- **Constraints travel with the column** — a column's FK/index is created in the same schema
  change that adds the column.

---

## Performance Lens (Senior Review Dimension)

- No per-row query in loops (N+1) — batch/join at the Repository.
- No `SELECT *` where explicit columns suffice.
- No missing index on a column the feature filters/joins by.
- No unbounded lists — limits/page caps enforced.
- No eager-loading every relationship on a list when only two are used.

---

## Seeders / Test Data (Conditional)

- Test data is built through the project's fixture/factory convention
  (`06-testing.md`; `MASTER_BUILD_SPECIFICATION.md`).
- Seeders/backfills are **batched and idempotent** — never one giant `all()`-loop update;
  re-running yields the same result.

---

## Database-Specific Decisions (recorded by the operator)

The following are **project decisions**, recorded in `MASTER_BUILD_SPECIFICATION.md`, never
guessed by the agent:

- Which DB engine / tool (PDO SQLite/MySQL/Postgres, doctrine/dbal, an ORM, a document store).
- Whether a migration tool is used and which.
- Connection details, schemas, table/column inventory.
- Naming/type conventions beyond the defaults above.
- Transaction isolation levels and locking strategy where races matter.

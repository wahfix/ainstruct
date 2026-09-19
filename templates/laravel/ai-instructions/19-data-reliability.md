# DATA & RUNTIME RELIABILITY — Safe State Changes

Data is the one asset that cannot be "rolled back by a git revert". Every skill in this module is
about keeping the database, jobs, and files correct even when a process fails halfway.

> [!IMPORTANT]
> These four skills harden the state changes the feature makes: migrations, transactions,
> background jobs, and uploaded media (`13-database.md` defines the schema conventions, this
> module defines the safety procedures around them).

---

## 1. Data Migration Safety

Migrations are the most dangerous code a Laravel agent writes — they run once per environment and
errors or data loss there are rarely recoverable.

- **Irreversible operations require operator confirmation** before the migration is written:
  `dropColumn`, `dropTable`, dropping an index, truncating, changing a column's type in a
  lossy direction, or deleting data. Escalate per `17-agent-discipline.md`.
- **Down always exists** — every migration has a `down()` that is itself safe to run; where a
  down is impossible (`dropColumn` on MySQL), state it explicitly and ask.
- **Destructive changes are gated, never folded in silently** — a migration that could destroy
  data is called out in the PR description and decision log, not buried in the diff.
- **Backfills are batched and idempotent** — `chunkById()`/cursor processing, `firstOrCreate`
  style upserts, never one giant `all()` update; re-running yields the same result
  (`13-database.md` — Seeders/Performance sections).
- **Constraints travel with the column** — FK/index added in the same migration that creates the
  column (`13-database.md` — Performance-Conscious Coding).
- **Verify both directions and fresh** — `php artisan migrate` and `migrate:rollback` pass, and a
  `migrate:fresh` (on the test/local DB only) leaves the schema consistent with `13-database.md`.
- **Zero-downtime consideration** — heavy backfills or long-running DDL are flagged to the
  operator (maintenance window), never assumed safe.

## 2. Transactions, Locking & Concurrency

Every multi-statement write is a transaction candidate.

- **`DB::transaction` for compound writes** — insert + related insert, update + audit log, status
  transition + side effect: all-or-nothing per `03-architecture.md` Action boundaries.
- **No statements between uneasy friends** — do not place user-facing HTTP work or long I/O inside
  a transaction unless the lock cost is justified; keep transactions short.
- **Choose the locking strategy deliberately**:
  - *Optimistic* (`updated_at` compare / version column) for read-mostly resources with low
    contention and a decent first-write-wins policy.
  - *Pessimistic* (`->lockForUpdate()`) when correctness of the **second** operation depends on
    the result of the first (counter/seat/stock style operations).
- **Guard double-submit & races** — idempotency keys or unique constraints absorb retries
  (form double-submit, callback replays, webhook retries).
- **Concurrency is an edge probe** — deferred to `15-edge-cases.md` chapter 2 (concurrency
  category): state what the expected loser/winner behavior is before implementing it.

## 3. Queue / Job Reliability

Jobs run twice or fail halfway — write them as if that is guaranteed.

- **Idempotent jobs** — re-running a job yields the same result (guard with `firstOrCreate`,
  status checks, or unique job keys `->onQueue(...)->unique()` when a duplicate must not run).
- **Explicit retry policy** — `$tries`/`maxAttempts`, `backoff` chosen per job; document the
  failure-handling contract (`failed()` handler logs intentionally and cleans up partial state).
- **Serialization-safe payloads** — pass model **ids/scalars**, not enqueued objects with live
  relations; re-fetch inside the job so the payloads never go stale or explode at
  deserialization.
- **Failure visibility** — failed jobs land in `failed_jobs` and are treated like a CI failure:
  reproduce, fix the root cause (`16-debugging.md`), and only then re-dispatch. Never re-run by
  hand-deleting rows without understanding why it failed.
- **Side effects match outcomes** — a job that sends a notification or mutates state is
  re-checked for the at-least-once semantics in the retry section above.

## 4. Media / Upload Lifecycle

Files are slower than the requests that upload them — handle the full lifecycle, not just the
happy upload.

- **Validate content, not just extension** — MIME sniff the file bytes in addition to the
  declared type; enforce size caps (per-route limits), reject empty/wrapper files
  (`07-security.md` upload rules).
- **Safe storage** — store on the intended disk, with random/derived names (never user-filenames
  verbatim into the path), avoid path traversal, and never serve storage paths the user supplied.
- **Ownership & cleanup** — the record owns its file(s): deleting the record, or overwriting it
  with a new upload, removes the orphan file (or schedules a delete job) — no orphan bytes left on
  disk; a failed save deletes the already-persisted file it created.
- **Orphan risk is an edge probe** — surface in the `15-edge-cases.md` probe output (files
  category): empty file, huge file, wrong MIME, duplicate filename, missing delete.

---

Cross-references: schema conventions `13-database.md`; upload security `07-security.md`; edge
probes for every state/scale boundary `15-edge-cases.md`; failure diagnosis `16-debugging.md`.

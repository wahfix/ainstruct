# DATA & RUNTIME RELIABILITY — Safe State Changes

Data is the one asset that cannot be "rolled back by a git revert". Every skill in this module is
about keeping the database, jobs, and files correct even when a process fails halfway.

> [!IMPORTANT]
> These four skills harden the state changes the feature makes: schema changes, transactions,
> background work, and uploaded media (`13-database.md` defines the schema conventions; this
> module defines the safety procedures around them).

---

## 1. Schema Change Safety

Schema changes are the most dangerous code an agent writes — they run once per environment and
errors or data loss there are rarely recoverable.

- **Irreversible operations require operator confirmation** before the change is written:
  dropping a column/table, dropping an index, truncating, changing a column's type in a lossy
  direction, or deleting data. Escalate per `17-agent-discipline.md`.
- **A rollback always exists** — every schema change has a way back; where a rollback is
  impossible (lossy type change), state it explicitly and ask.
- **Destructive changes are gated, never folded in silently** — a schema change that could
  destroy data is called out in the PR description and decision log, not buried in the diff.
- **Backfills are batched and idempotent** — chunked processing and upsert-style writes, never
  one giant `all()` update; re-running yields the same result (`13-database.md`).
- **Constraints travel with the column** — FK/index added in the same schema change that creates
  the column (`13-database.md`).
- **Verify both directions and fresh** — the schema applies cleanly to a fresh environment and
  rolls back per the project's tooling.

---

## 2. Transactions, Locking & Concurrency

- **Multi-step mutations run in a transaction.** Use the chosen data layer's transaction API
  (`beginTransaction`/`commit`/`rollback`, or equivalent). A feature that updates two tables and
  crashes mid-way must not leave half-applied state.
- **Scope transactions tightly** — hold the transaction for exactly the mutation, not for slow
  external calls.
- **Locking where races matter** — unique creation (check-then-insert gap), counters,
  idempotent operations; choose and record the strategy in
  `MASTER_BUILD_SPECIFICATION.md` (optimistic vs pessimistic).
- **Idempotency** — re-running the same action (retry, double submit) must not corrupt state
  (`15-edge-cases.md`).

---

## 3. Queue / Background Job Reliability (Conditional)

When the project uses background jobs (a queue library, cron scripts, a worker), the chosen
mechanism is a project decision recorded in `MASTER_BUILD_SPECIFICATION.md`. Universal rules:

- **Failures are retried with backoff** — a failed job is not silently dropped.
- **Jobs are idempotent** where a retry is possible (re-run yields the same result).
- **Sensitive payloads are not logged** — job args may contain IDs/refs, not passwords or
  personal data (`07-security.md`).
- A job that permanently fails surfaces to the operator (log + report), never silently swallowed.

---

## 4. Media / Upload Lifecycle (Conditional)

When the project handles uploads (`07-security.md` File Upload Security):

- **Validate server-side first** — MIME whitelist + size ceiling, fail fast with
  `ValidationException`.
- **Store with sanitized names** — never trust the client filename; never reconstruct paths from
  user input.
- **Private vs public disks** — sensitive documents on a private store; public only for
  non-sensitive assets.
- **Deletion is explicit** — removing a file and removing its record happen deliberately; orphaned
  files are cleaned up by the project's maintenance policy (recorded in the spec), not left to
  accumulate silently.

# EDGE CASES & BOUNDARY THINKING — Probe Before Done

The happy path is a start, never the finish. Every feature MUST be probed for boundary
conditions, failure modes, and unintended input **before** it is marked complete. This module
is the probe sheet; run its categories against every code path you touch.

> [!IMPORTANT]
> These probes are part of completing a task, not optional hardening. A feature shipped
> without edge-case probing is unfinished (see `10-quality-gates.md` — Mandatory Quality
> Gates and the Senior Self-Review Rubric; `11-forbidden-behavior.md`).

---

## 1. Input & Type Boundaries

- **Null vs missing vs empty** — is a missing key distinguished from an explicit `null`? Is
  empty array `[]` different from absent? Empty string `''` vs `null`? Which does your
  validation allow, and which does your handling tolerate?
- **Wrong types** — non-string where a string is expected, string where an int is expected,
  request body as JSON vs form. PHP type juggling can silently coerce — say **what you allow**.
- **Boundary values** — `0` vs negative; max-length string vs a VARCHAR overflow (SQLSTATE
  "Data too long"); page `0`, page beyond last, `per_page` above your cap.
- **Unique collisions** — duplicate `slug`/`email`/NIK on create **and** on update (self-collision!);
  duplicate keys under race; constraint violation (`SQLSTATE[23000]`) surfaced cleanly or handled.
- **Invalid formats** — date/number shape, phone & NIK digit counts, email shape, oversized
  pagination params, malformed UUIDs.

## 2. State & Lifecycle

- **Record missing vs soft-deleted** — a missing row and a soft-deleted row are different
  states; which does the query see (global scope), and which must the handler treat as a different
  outcome (40x vs 404)?
- **Orphaned relations** — related record absent (company deleted but child exists), FK left
  dangling, `relation?->` vs forced access.
- **Concurrency** — two users update the same record; double submit; retries; optimistic
  locking (`updated_at` compare) or queue idempotency.
- **Transitions** — status machines (draft→published→archived); an operation valid in one state
  but not another; re-running a transition twice.
- **Idempotency** — can a seeder/job/command run twice with the same result? Every seed and
  every side-effecting action should be idempotent or statefully guarded.

## 3. Permission & Ownership

- **Owner vs non-owner** — does the policy distinguish editing your own record vs someone
  else's, and is that decided on the **server**?
- **Partial role** — a role that grants 9 of 10 permissions; the missing one must NOT slip
  through an implicit allowance.
- **Tenant/scope leakage** — an id in the URL reaching a record the caller must not see (no
  implicit cross-tenant reads).
- **Route-model binding failure** — record not found → your 404 convention; unauthorized → 403.
  Never fall back to a successful empty result for an authorization failure.

## 4. Integration & Scale

- **Empty collections** — zero rows, zero related items, empty `first()` → null chaining.
- **Large collections** — N+1 risk appears exactly here (expect eager loading and verify with
  the query log — see `13-database.md`); unbounded `all()` on a big table.
- **Nested/recursive data** — comment threads, category trees: depth limits, cycle protection,
  accidental infinite loops.
- **Files** — empty file, huge file, wrong MIME, duplicate filename, missing file on delete,
  failed upload leaves no orphan.
- **Cache/queue** — stale cache after mutation, job payload serialization, retries with side
  effects (see idempotency above).

---

## Mandatory Probe Output

Before finalizing any feature, **state concisely** which probes apply and their outcome, e.g.:

> `empty list → no-op by design; duplicate slug → unique validation + rendered error; soft-deleted → excluded via global scope, handler 404; concurrency → last-write-wins accepted per spec;` …

Any probe that reveals a defect **must be fixed, covered by a test (see `06-testing.md`), or
flagged to the operator** via the review protocol — never silently shipped.

---

## If a Probe Cannot Be Answered

If you cannot determine the intended behavior at a boundary (the spec is silent and no analogue
exists), **stop and ask the operator** instead of assuming a default. Boundary behavior is a
specification decision, not an implementation detail.

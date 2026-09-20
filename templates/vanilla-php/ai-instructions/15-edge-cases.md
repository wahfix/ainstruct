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
  JSON body vs form body. PHP type juggling can silently coerce — say **what you allow**.
- **Boundary values** — `0` vs negative; max-length string vs a column overflow (SQLSTATE
  "Data too long"); page `0`, page beyond last, `per_page` above your cap.
- **Unique collisions** — duplicate slug/email/NIK/number on create **and** on update
  (self-collision!); duplicate keys under race; constraint violation surfaced cleanly or handled.
- **Invalid formats** — date/number shape, phone & ID digit counts, email shape, oversized
  pagination params, malformed UUIDs.

## 2. State & Lifecycle

- **Record missing vs soft-deleted** — a missing row and a soft-deleted row are different
  states; which does the query see, and which must the handler treat as a different outcome
  (40x vs 404)?
- **Idempotency** — re-running the same action (double submit, retry) must not duplicate
  records or corrupt state; is there a unique guard or idempotency key?
- **Transitions** — every status change is probed: re-running a transition, skipping a state,
  invalid from→to, concurrent transitions (`21-state-delivery-environment.md`).

## 3. Permission & Ownership

- **Authorization on every path** — the protected path checks access; does the "other" branch
  (404 vs 403) leak existence? Is ownership validated on update/delete (can user B edit user A's
  record)?
- **Shared resources** — record contention: two users editing the same record; last write wins?
  Is that intended?

## 4. Data Volume & Performance

- **Large datasets** — a list without pagination/limits; `per_page` above the cap; one query per
  row in a loop (N+1); unbounded `IN (...)` clause.
- **Oversized payloads** — a request body larger than the limit; a file upload over the ceiling
  (fail fast with `ValidationException`, `07-security.md`).
- **Slow queries** — columns filtered/joined without an index (`13-database.md` Performance
  Lens).

## 5. Failure Modes

- **External failures** — a third-party API timeout/error, a mailer failure, a queue failure:
  is the failure surfaced, retried, or logged? Never silently swallowed
  (`11-forbidden-behavior.md`).
- **Partial failures** — a multi-step mutation fails mid-way: is it rolled back
  (`19-data-reliability.md` transactions)?
- **Race conditions** — two actions creating the same unique record; unique constraint vs
  check-then-insert gap.

## 6. Environment & Input Encoding

- **Unicode & encoding** — emoji/multibyte in names, `STRICT` vs `LENIENT` handling, JSON
  escaping, HTML escaping on output.
- **Timezones & locales** — dates crossing the boundary deliberately; no implicit timezone
  shifting at the edge (`20-frontend-and-contracts.md`).

---

## Boundary Probe Checklist (run per code path)

- [ ] Null / empty / missing input each handled or explicitly rejected
- [ ] Wrong type handled or validated
- [ ] Boundary values (0, max length, page 0, page beyond last) handled
- [ ] Unique collision on create and update tested
- [ ] Missing vs soft-deleted state distinguished where it matters
- [ ] Re-run / double submit safe (idempotent where required)
- [ ] Permission + ownership checked on every path
- [ ] No per-row queries in loops; limits applied
- [ ] External failure not swallowed
- [ ] Multi-step mutation transactional

---

## When a Probe Is Not Run

If a boundary cannot be probed in the current scope, state it explicitly in the decision log
(`17-agent-discipline.md`) and, when it matters, ask the operator. A skipped probe with an
undisclosed reason is a defect.

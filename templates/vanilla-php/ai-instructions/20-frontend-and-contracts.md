# FRONTEND, CONTRACTS & UX — The User-Facing Surface (Conditional)

This module applies **only when the project has a frontend** (`14-frontend.md` defines the
conditional frontend stack rules; this module adds the cross-layer skills). For backend-only
projects these skills reduce to the contract discipline between Entry, Action, and Repository —
keep the same rigor with the "frontend" replaced by the "consumer".

---

## 1. Backend ↔ Frontend Contract Discipline

The frontend never invents the shape it expects — the shape is **derived from the real backend**.

- **Shapes come from sources, not memory** — the payload a frontend consumes is written from the
  actual Action/Entry return, the entity, or a `canonical-snippets.md` anchor (evidence-anchored
  rule) — never from a guess (`12-project-specific/canonical-snippets.md` usage rule 6).
- **One change, both sides, same PR** — altering an Action's return shape, a validator's rules,
  or an endpoint updates the frontend contract in the same change; a frontend consuming a field
  that does not exist yet is a broken contract and is flagged (impact analysis,
  `18-planning-and-safe-change.md`).
- **Dates & numbers cross the boundary deliberately** — a stated serialization contract
  (ISO string, timestamp) chosen once; no implicit timezone shifting at the edge. Mutations
  respect the `15-edge-cases.md` formatting probes.
- **Unknown shapes get declared, not silenced** — destructure validated fields; leftover/optional
  fields are either typed optional or confirmed absent.

---

## 2. UI Text & i18n

- **No hardcoded user-facing strings** in components when the project uses i18n — strings live
  in lang files (`11-forbidden-behavior.md`).
- Copy is written for humans: clear, concise, no AI-slop (the anti-slop gate —
  `10-quality-gates.md`).
- Placeholders, errors, and empty states are honest — never a generic filler message.

---

## 3. Accessibility & Form UX

- **Labels, focus, and keyboard** — every form control has a label, focus order is logical,
  and keyboard users can complete the flow (`antislop-human` skill where installed).
- **Errors are visible and actionable** — server validation errors map back to the field and are
  shown next to it; a form submit failure never disappears into a toast nobody sees.
- **Loading/submitting states** — a submitted form is visibly busy and protected from double
  submit (edge probe — `15-edge-cases.md` idempotency).

---

## 4. Error Contract

Errors are part of the API — a **consistent, documented shape**, not ad-hoc screens.

- A controlled error type (validation, not-found, forbidden, server) with a stable shape and a
  human-readable message. A thrown `ValidationException` from a RuledAction maps to field errors;
  a domain exception maps to a 40x; unexpected failures map to a generic 500 without leaking
  internals (`07-security.md` — never raw database errors).
- **No swallowed exceptions** — an absorbed error without a decision-logged reason is a defect
  (`11-forbidden-behavior.md`).
- Logs carry the technical detail (stack trace, context) while responses carry only what the user
  needs.

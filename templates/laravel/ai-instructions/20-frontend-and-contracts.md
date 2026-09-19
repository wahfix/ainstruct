# FRONTEND, CONTRACTS & UX — The User-Facing Surface

Frontend bugs that escape a review are the most visible kind — the user sees them. These four
skills keep the Vue/Inertia/TypeScript surface truthful to the backend, accessible, and honest in
its errors (`14-frontend.md` defines the frontend stack and conventions; this module adds the
cross-layer skills).

---

## 1. Frontend ↔ Backend Contract Discipline

The frontend never invents the shape it expects — the shape is **derived from the real backend**.

- **Types come from sources, not memory** — TypeScript types/interfaces for API and Inertia data
  are written from the actual model, controller response, or `canonical-snippets.md` anchor
  (evidence-anchored rule) — never from a guess (`12-project-specific/canonical-snippets.md`
  usage rule 6).
- **Inertia PageProps are an explicit contract** — shared props and per-page props are typed once,
  referenced in components, and changed in lock-step with the controller's `Inertia::render`
  payload.
- **One change, both sides, same PR** — altering an API/response/`ziggy` route name updates the
  frontend types in the same change; a frontend consuming a field that does not exist yet is a
  broken contract and is flagged (impact analysis, `18-planning-and-safe-change.md`).
- **Dates & numbers cross the boundary deliberately** — a stated serialization contract
  (ISO string, `DateTimeImmutable` ISO, timestamp) chosen once; no implicit timezone shifting at
  the edge. Mutations respect the `15-edge-cases.md` formatting probes.
- **Unknown shapes get declared, not silenced** — destructure validated fields; leftover/optional
  fields are either typed optional or confirmed absent, matching the `useForm`/validation
  conventions of `14-frontend.md`.

## 2. UI Text & i18n

User-facing strings are application data, not code spices.

- **All user-facing strings via lang files** (`lang/*.php`) — labels, buttons, errors,
  notifications, placeholders. Hardcoded prose in components is a violation
  (`11-forbidden-behavior.md`).
- **Keys carry intent** — `validation.slug_taken`, `notifications.article_published` — chosen
  once, reused; never duplicate the same text under different keys.
- **Localized formatting** — dates, numbers, currency, and pluralization use the framework/locale
  formatters, not hand-concatenated strings.
- **Dynamic values are parameters** — translation parameters (`:name`) with safe escaping; no
  string interpolation of user content into rendered UI text beyond the translation parameter
  mechanism.
- **Frontend component text** too — Vue templates pull from the same lang system; a skeleton
  copy-paste from an older screen is re-checked for leftover hardcoded strings.

## 3. Accessibility & Form UX

Reachability and keyboard operability are requirements for production UI, not polish.

- **Every input has a label** — real `<label for>` with the `useForm` field, not aria hacks; an
  accessible name is mandatory (`14-frontend.md` form handling conventions).
- **Keyboard-first** — tab order matches visual order; modals/drawers trap focus, recover focus
  on close; no interaction that is clickable but not keyboard-operable.
- **State communicated** — disabled/loading are visible and announced (`aria-busy`, `disabled`
  props, buttons never clickable while submitting); inline validation text is associated with the
  field (`aria-describedby`/`role="alert"`).
- **Contrast & hierarchy** — text contrast meets the base threshold; color is never the only
  signal (tokens from `cn()`/Tailwind theme, `14-frontend.md` styling conventions).
- **A11y is a probe, not an afterthought** — run a keyboard pass as part of the end-to-end demo
  (`21-state-delivery-environment.md` e2e verification) for any form/dialog you touch.

## 4. Error Contract Discipline

Errors are a contract between server and UI. Both sides must agree on what failure looks like.

- **Never swallow exceptions** — empty catch blocks, silent `catch {}`, or
  `->catch(fn () => null)` without a stated reason are forbidden (`11-forbidden-behavior.md`). If
  an error is intentionally absorbed, the decision log records why (`17-agent-discipline.md`).
- **Server returns the correct status** — validation `422`, not-found `404`, denied `403`,
  unauthenticated `401`; through Laravel validation exceptions and abort helpers
  (`07-security.md`). The frontend maps *by status*, not by guessing.
- **One error shape** — a consistent error payload (message + field map) derived from
  `useForm`'s validation errors and a standard error notification; no raw stack traces or SQL
  errors reaching the client (`07-security.md`).
- **Error paths are probed** — testing errors is part of test-to-break
  (`18-planning-and-safe-change.md` section 4): assert the status, the user-facing message, and
  that no partial state was written.

---

Cross-references: stack & conventions `14-frontend.md`; evidence-anchored types
`12-project-specific/canonical-snippets.md`; input trusts & authz `07-security.md`; error-path
probes `15-edge-cases.md`, `18-planning-and-safe-change.md`.

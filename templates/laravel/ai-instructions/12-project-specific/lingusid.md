# LINGUSID — Project-Specific Invariants

This module applies ONLY when the repository is the **LingSID** project (Sistem Informasi Desa — Laravel 12 / Inertia / Vue 3 / TypeScript / Tailwind CSS v4 stack).

---

## Stack (binding)

- **PHP ^8.2, Laravel ^12.0** (no `declare(strict_types=1)`).
- **Inertia.js v2 + Vue 3** (`<script setup lang="ts">`, Composition API only) + **TypeScript**.
- **Tailwind CSS v4** + **shadcn-vue** primitives (`resources/js/components/ui/`) + `lucide-vue-next` icons.
- **Vite 6** (build + SSR) and **ziggy-js** for route resolution in the frontend.
- **bun** is the preferred JS package manager; `npm` is the fallback (never mix within a project).
- No Blade pages for application UI (Inertia/Vue only).

---

## Architecture Invariants

1. **Actions are the business-logic default; Service layer is allowed — not yet adopted.** LingSID currently has NO Service layer and NO DTOs: business logic lives in Actions and data passes as arrays/Models. This is the **current state**, NOT a prohibition — the Service layer is a legitimate Laravel layer, equally important as Actions, and MAY be introduced when genuinely warranted (cross-cutting/reused logic spanning multiple Actions/aggregates). Services MUST be domain-named (`ArticleService`, `ResidentService`), constructor-injected, thin, and Repository-only for Eloquent (per `03-architecture.md`). Never create generic `Service.php`/`Helper.php`/`Utils.php`.
2. **Repository-only Eloquent access.** Controllers/Actions must not query Eloquent directly.
3. **Validation in RuledActions** (`rules(array $payload): array`). FormRequests are used ONLY for `Auth/` and `Settings/` flows.
4. **Thin Controllers.** Controllers resolve Actions via constructor injection and delegate.
5. **Domain contexts on disk:** `app/Models/Sid/`, `app/Models/Web/`, `app/Actions/Sid/`, `app/Actions/Web/`, `app/Repositories/Sid/`, `app/Repositories/Web/`, controllers under `app/Http/Controllers/{Sid,Web,Dashboard,Settings,Auth}/`. If a Service layer is introduced, place it under the matching context (`app/Services/{Context}/…`), same shape as Actions/Repositories.
6. **Canonical entity naming carries the context prefix** for domain entities: `SidResident`, `WebArticle`, `WebPage` (models), `CreateSidResidentAction`, `UpdateWebArticleAction` (actions), `SidResidentRepository`, `WebArticleRepository` (repositories). Do not reproduce legacy unprefixed duplicates (`CreateResidentAction`, `UpdateResidentAction`).
7. **Base classes:** extend `App\Abstractions\Actions\Action` (or `IndexAction`) and `App\Abstractions\Repository\ModelRepository`. Use shared model traits `App\Abstractions\Traits\Model\HasGroups` and `App\Abstractions\Traits\Model\HasMetadata` instead of re-implementing poly-morphic behavior.
8. **System constants via `App\Enums\System\GroupEnum`** (string-backed, SCREAMING_SNAKE_CASE cases). Do not hardcode group slugs/classes outside the enum; do not mutate or delete system groups (throw `SystemGroupImmutableException` / guard circular membership via `CircularMembershipException`).
9. **Feature stops at the Action layer** unless the user explicitly asks for Controllers, routes, or frontend pages.
10. **Copy canonical snippets verbatim** from `12-project-specific/canonical-snippets.md` (Action/controller/repository/model/test/frontend forms with source anchors). Never paraphrase signatures; never reproduce the `// BAD` anti-patterns (no `execute()`, no dual-arg `handle()`, no scalar payload to RuledActions).
11. **Build specification first.** If `MASTER_BUILD_SPECIFICATION.md` exists at the LingSID repo root, read it before any code (it overrides module conventions where they conflict). If it does not exist, create it via detailed operator Q&A before coding, per root `ai-instructions.md` section 12.

---

## Canonical Snippets

`12-project-specific/canonical-snippets.md` holds the verbatim, source-anchored snippet bank for this project. Consult it before writing any class:

- Base classes & contracts (`Action`, `IndexAction`, `RuledActionContract`, `InvokeableActionContract`).
- Ruled actions (create/update/delete) in both rule styles (pipe-string predominant; `Rule::` where chaining/`$payload` is needed).
- Plain actions (collection, paginated, model-payload delete, cross-action orchestration).
- Controllers (constructor vs method injection), repository shells + scoped queries.
- Models, traits, enums, domain exceptions, unit/feature tests, frontend pages.

---

## Routes / Pages

- Authenticated routes grouped under `dashboard.{context}.{subcontext}.{entity}.{action}`:
  - `dashboard.sid.population.residents.*` ↔ `resources/js/pages/Sid/Population/Residents/*`
  - `dashboard.web.articles.*` ↔ `resources/js/pages/Web/Articles/*`
  - `dashboard.web.articles.categories.*` ↔ `resources/js/pages/Web/ArticleCategories/*`
- `routes/web.php`, `routes/auth.php`, `routes/settings.php` are the only route files.
- Protected app routes run under `['auth', 'verified', ShareDashboardData::class]`.

---

## Packages in Use

- **spatie/laravel-permission** — roles & permissions (`HasRoles`, `HasPermissions`).
- **spatie/laravel-medialibrary** — file/media attachments for models.
- **spatie/laravel-activitylog** — audit trails on important mutations.
- **spatie/laravel-backup**, **spatie/laravel-responsecache** — backup + response caching.
- **laravel/scout** — search over Eloquent models.
- **cviebrock/eloquent-sluggable** — URL slugs on publishable entities.
- **staudenmeir/eloquent-has-many-deep** — deep relationship queries.
- **barryvdh/laravel-dompdf**, **maatwebsite/excel** — document/export generation (SID letters, reports).

Use these packages where applicable. Do NOT add new dependencies without checking the codebase first (analogue-first).

---

## Quality Gates (LingSID-specific)

- [ ] Static analysis passes: `./vendor/bin/phpstan analyse` (level 5, paths `app/ config/ database/ routes/`).
- [ ] Code formatted with `laravel/pint`.
- [ ] Frontend passes `bun run lint` (ESLint) and `bun run format:check` (Prettier).
- [ ] `#[Test]` attribute + snake_case method names on new tests; `RefreshDatabase` on database tests.
- [ ] Works on a feature branch off `develop`; never commit to `develop`/`main` directly.
- [ ] No `dd()`, `dump()`, `ray()` in committed code (see `11-forbidden-behavior.md`).
- [ ] No static call to instance methods (`XxxAction::handle()` must never appear).

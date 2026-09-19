# FRONTEND — Vue 3, TypeScript, Inertia, Tailwind

This file defines frontend conventions for the Vue 3 + TypeScript + Inertia.js stack used in this project. It consolidates frontend rules from `03-architecture.md` and `04-coding-standards.md` into a single reference.

> [!IMPORTANT]
> These rules apply only to the Inertia/Vue frontend. If a project uses Blade or a different
> frontend stack, the applicable conventions in `03-architecture.md` and `04-coding-standards.md`
> take precedence.

---

## Stack (Binding)

- **Inertia.js v2 + Vue 3 + TypeScript** — Composition API only (`<script setup lang="ts">`).
- **Tailwind CSS v4** — utility-first, config via `resources/css/app.css` (no `tailwind.config.*`).
- **shadcn-vue** primitives in `resources/js/components/ui/` + `lucide-vue-next` icons.
- **ziggy-js** for client-side route resolution (`route('route.name')`).
- **Vite 6** as build tool (build + SSR).
- **bun** for JS tasks (lint, format, build); `npm` as fallback — never mix within a project.

---

## Directory Organization

```
resources/js/
├── pages/          ← Inertia pages mirror URI path
│   ├── Public/
│   ├── Auth/
│   ├── Dashboard/
│   ├── Sid/Population/Residents/  (Index.vue, Create.vue, Edit.vue)
│   └── Web/Articles/              (Index.vue, Create.vue, Edit.vue, Show.vue)
├── components/     ← App shell + shared components
│   ├── ui/         ← shadcn-vue primitives (Button.vue, Card.vue, Table.vue, …)
│   └── AppShell.vue, AppContent.vue, AppHeader.vue, AppLogo.vue
├── layouts/        ← Layout wrappers (AppLayout.vue)
├── lib/            ← Util helpers (cn()), composables
└── types/          ← TypeScript types (App.Models.*, App.Data.*)
```

**Rules:**

- Pages MUST mirror the URI path from `resources/js/pages/`.
- Components MUST be in the correct contextual directory — do not invent new folders without an analogue.
- Shared layout/components live in `resources/js/components/`.
- shadcn-vue primitives live in `resources/js/components/ui/` — do not hand-roll equivalents.

---

## Vue Component Structure

### Canonical Page Structure

```vue
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui/card';

interface Props {
    articles: {
        data: {
            id: number;
            title: string;
            slug: string;
            published_at: string;
            author: { name: string };
        }[];
        links: any[];
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Website', href: '/dashboard/web/articles' },
    { title: 'Artikel', href: '/dashboard/web/articles' },
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
                        <CardDescription>Daftar semua artikel yang terdaftar.</CardDescription>
                    </div>
                    <Link :href="route('dashboard.web.articles.create')">
                        <Button>Tambah Artikel</Button>
                    </Link>
                </div>
            </CardHeader>
            <CardContent>
                <Table>
                    <!-- ... -->
                </Table>
            </CardContent>
        </Card>
    </AppLayout>
</template>
```

### Component Order

1. Imports (sorted by `prettier-plugin-organize-imports`).
2. `interface Props { … }`.
3. `defineProps<Props>()`.
4. Breadcrumbs (if page).
5. Form declarations (`useForm()`), if applicable.
6. Computed properties & methods.
7. `<template>`.

### Script Setup Rules

- Use `<script setup lang="ts">` — Composition API only. **NO Options API.**
- Use `defineProps<Props>()` with a typed interface — never untyped props.
- Use `defineEmits<{ (e: 'name', value: Type): void }>()` for events.
- Use `defineModel()` for `v-model` on writable props (Vue 3.4+).
- No unused variables or imports.

---

## Typing Conventions

### TypeScript Types

- Use `interface` for object shapes.
- Use `type` for unions/intersections.
- Props always typed with interfaces.
- Shared model types follow the dotted convention: `App.Models.{Context}.{Model}` and `App.Data.{Name}` (declared in `resources/js/types/index.d.ts`).

```typescript
interface Props {
    residents: Array<App.Models.SidResident>;
    sidebarMenus: Array<App.Data.SidebarMenu>;
}
```

- Avoid `any` where possible; use `unknown` and narrow when the shape is uncertain.
- Map backend payload shapes explicitly — do not rely on implicit/global types for Inertia page props.

### Compiler Strictness

- `vue-tsc --noEmit` passes for type checking (optional local check; CI does not run it by default).
- Type errors block the `bun run build` and `bun run build:ssr` steps.

---

## Form Handling

### useForm (canonical for Inertia)

```typescript
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    title: '',
    slug: '',
    content: '',
    published_at: '',
    group_id: null as number | null,
});

const submit = () => {
    form.post(route('dashboard.web.articles.store'));
};

const save = () => {
    form.put(route('dashboard.web.articles.update', props.article.id));
};
```

### Form Rules

- Use `useForm` for all Inertia data submissions against Laravel endpoints — not raw `fetch`/`axios`.
- Submit via `form.post` / `form.put` / `form.patch` / `form.delete` to route names.
- Display validation errors via `form.errors` (populated from `ValidationException` JSON).
- Track pending state via `form.processing` to disable buttons.
- Use `form.reset()` / `form.clearErrors()` where the flow needs them.
- Validation lives server-side (RuledActions) — the frontend does NOT define authoritative rules, only UX hints.

```vue
<Input
    v-model="form.title"
    :error="form.errors.title"
    type="text"
    autofocus
    autocomplete="title"
    placeholder="Judul artikel"
/>
```

### Delete Operations

```vue
<Link :href="route('dashboard.web.articles.destroy', article.id)" method="delete" as="button" type="button">
    <Button variant="destructive" size="sm">Hapus</Button>
</Link>
```

- Destructive actions use `method="delete"` via Link (Inertia).
- Confirm destructive actions with a dialog/`window.confirm` where required by UX.

---

## Routing & Navigation

- Resolve routes client-side with **ziggy-js**: `route('route.name', params?)`.
- Never hardcode URLs as strings in navigation — use route names (they survive route changes).
- Linking internal pages: `<Link :href="route('…')">` from `@inertiajs/vue3`.
- Page components are named by their path from `resources/js/pages/` (e.g., `Web/Articles/Index`).
- The backend renders pages via `Inertia::render('FilePath/Name', props)`; the frontend receives props from the controller/action.

---

## Styling

### Tailwind CSS v4

- Utility classes inline; no custom CSS unless needed (only when a utility is insufficient).
- Conditional classes via `cn()` from `@/lib/utils`.
- Dark mode via `.dark` class on `<html>` (tailwind `dark:` variants).
- Tailwind class sorting enforced by `prettier-plugin-tailwindcss`.

```typescript
// Canonical cn() usage
const buttonClasses = cn('inline-flex items-center', props.variant === 'outline' && 'border');
```

### shadcn-vue Primitives

- Use primitives from `resources/js/components/ui/` (`Button`, `Card`, `Table`, `Input`, `Dialog`, `Select`, …).
- Do NOT copy/paste new shadcn primitives without checking they are not already present.
- Component props follow shadcn-vue conventions: `variant`, `size`, `as-child`, merge with `+` operator for overrides.

---

## Composables & Utilities

- Reusable orchestration logic lives in composables (`resources/js/composables/` or `resources/js/lib/`).
- Composable naming: `use{Feature}` (e.g., `useSidebar`, `useArticleForm`).
- Keep composables focused — do not create generic `useHelpers` with unrelated functions.
- Utility helpers (formatting dates, currency, `cn()`) live in `resources/js/lib/`.

**Create a composable when:**

- The same logic is reused by 2+ components.
- It encapsulates a cohesive interaction (form + validation flow, session timer, theming).

**Do NOT create a composable for:**

- One-off page logic (keep it in the page).
- Trivial helpers (a function in `lib/` suffices).

---

## Frontend Testing

- Mock/fake the Inertia layer in component tests where needed (`createInertiaApp` test helper).
- Test pages/components with `@vue/test-utils` + `vitest` for component logic and interactions.
- Use `mount` for component behavior; shallow-render where the component is only a pass-through.
- Assert against route names (ziggy) — do not assert raw URLs.
- Tailwind/shadcn-vue markup is covered by visual review + `bun run lint` (ESLint), not by unit tests.

---

## Frontend Quality Gates (Cross-Reference)

- `prettier` formatting passes: `bun run format` / `bun run format:check` (printWidth 150, tabWidth 4, single quotes, trailing commas).
- ESLint passes: `bun run lint` (flat config, Vue + TS; ignores `resources/js/components/ui/**`).
- `bun run build` and `bun run build:ssr` succeed.
- No `any` leaks in page props; `vue-tsc --noEmit` clean where run.
- Reuse `ui/` primitives; do not hand-roll shadcn equivalents.

---

## Linked Modules

- Architecture & layering: `03-architecture.md` (Frontend Organization, Context-Based Organization).
- PHP/Vue code style & formatting: `04-coding-standards.md`.
- Route naming: `05-naming.md` (Route Naming, Vue Files, Inertia Page Names).
- Frontend snippets: `12-project-specific/canonical-snippets.md` §10.

# HopeUI → nuxnan conversion guide

How to translate markup from `hopa/hopeui-pro-tailwind-v3.1.0/hopeui-pro-tailwind/html/*.html`
into nuxnan Vue SFCs. Facts below were verified against HopeUI's theme source
(`gulp+hbs/src/theme/`) and nuxnan's `ui/tailwind.config.ts`.

## 1. Color & token mapping

HopeUI defines its own palette and semantic tokens in `gulp+hbs/src/theme/color.js` +
`config.js`. nuxnan has a different brand (sky-blue primary, purple secondary, Vikinger dark
theme). **Rule: keep the class *names* where they exist in both configs, and translate the ones
that only exist in HopeUI. Never copy HopeUI hex values into nuxnan.**

| HopeUI class | Meaning in HopeUI | Use in nuxnan |
|---|---|---|
| `primary-*` (e.g. `bg-primary-500`) | indigo #3a57e8 | keep as-is — nuxnan has its own `primary` scale (sky), brand adapts automatically |
| `secondary-*` | gray scale | usually `gray-*`; nuxnan `secondary` is purple, so check intent: HopeUI uses secondary for muted/gray UI |
| `success-*`, `warning-*`, `danger-*`, `info-*` | status colors | not in nuxnan config → use Tailwind `green-*`, `amber-*`, `red-*`, `cyan-*` |
| `text-body` | muted text #8A92A6 | `text-gray-500 dark:text-gray-400` |
| `text-heading` | heading #0b112e | `text-gray-900 dark:text-white` |
| `bg-body`, `bg-container` | page background | drop — the nuxnan layout already sets the page background |
| `dark:bg-dark-bg`, `dark:bg-dark-card`, `dark:border-dark-border`, `dark:text-dark-text` | HopeUI dark tokens | not in nuxnan config → match the dark classes used by the nearest similar nuxnan component (Vikinger dark scale, e.g. `dark:bg-vikinger-dark`, `dark:bg-vikinger-dark-100`) |
| `shadow`, `shadow-dropdown`, `shadow-active` | custom shadow values | plain Tailwind `shadow-sm`/`shadow-md`/`shadow-lg` |

## 2. HopeUI component classes (do not copy verbatim)

These come from a Tailwind plugin in HopeUI's config and DO NOT exist in nuxnan — copying them
produces unstyled elements:

- `btn`, `btn-primary`, `btn-secondary`, `btn-success/-danger/-warning/-info`, `btn-sm`, `btn-lg`,
  `btn-icon`, `btn-icon-sm/-lg`, `btn-block`, `btn-inner`
- `form-control`, `form-label`, `input-group`

Replace with either:
- **PrimeVue** components (`Button`, `InputText`, `Select`, …) when the element is interactive and
  a similar nuxnan screen already uses PrimeVue, or
- **plain Tailwind utilities**, e.g. `btn btn-primary btn-sm` →
  `inline-flex items-center gap-2 rounded-lg bg-primary-500 hover:bg-primary-600 px-3 py-1.5 text-sm font-medium text-white transition-colors`

Look at an existing nuxnan button/input first and reuse its classes — consistency with the app
beats fidelity to HopeUI.

## 3. Alpine.js → Vue

HopeUI's interactivity is Alpine. Strip it and re-implement with Vue:

| Alpine | Vue |
|---|---|
| `x-data="{ open: false }"` | `const open = ref(false)` in `<script setup>` |
| `x-show="open"` | `v-show="open"` |
| `<template x-if>` | `v-if` |
| `x-for` | `v-for` (add `:key`) |
| `@click="..."` / `x-on:click` | same syntax in Vue — but rewrite the handler body |
| `x-transition` | `<Transition>` |
| `:class="[setting.theme_scheme, ...]"` and anything referencing `setting.*` | delete — that's HopeUI's demo settings panel |
| `x-init`, `$watch`, `$refs` | `onMounted`, `watch`, template refs |

Also delete: the `.loader` preloader block, the settings offcanvas, `data-toggle`/`data-*`
attributes tied to `hope-ui.js`, and every `<script src=...>` tag.

## 4. Icons

HopeUI uses inline `<svg>` (~100+ per page) plus icon-font `<i class="icon">` wrappers. Options,
in order of preference:

1. Replace with Iconify (`<Icon icon="lucide:heart" />` via `@iconify/vue`) — pick icons that
   match nuxnan's existing choices on similar screens. Two gotchas seen in this codebase:
   `@iconify/vue`'s prop is `icon`, NOT `name` (a `:name=` binding renders nothing, silently), and
   invented icon names also render blank — verify with
   `https://api.iconify.design/fluent.json?icons=<name>` when unsure (existing usage in the repo is
   not proof the name is valid).
2. Keep the inline SVG only when no close Iconify match exists; ensure `stroke="currentColor"` /
   `fill="currentColor"` so theming works.

## 5. Other cleanup

- **`rtl:` variants** (`rtl:ml-4 rtl:mr-0`, `rtl:rotate-180`) — drop them; nuxnan is LTR (Thai/English).
- **Demo content** — every name, avatar (`../assets/images/...`), count, and lorem string must be
  replaced by props/store/API data or removed. A ported component with hardcoded "Anna Sthesia"
  avatars is not done.
- **`margin` quirk** — HopeUI overrides `m-5` to `2rem` (Tailwind default is `1.25rem`). If the
  source uses `m-5`/`mx-5`/etc. and spacing looks cramped, use `m-8`.
- **JS plugin features** (charts, sliders, date pickers, editors, upload): find the Vue equivalent
  already in `ui/package.json` before adding anything new. HopeUI's Vue variant's `package.json`
  (`hopa/hopeui-pro-vue-4.1.0/code-vue/package.json`) shows which Vue libs Iqonic paired with each
  plugin, useful as a hint only.

## 6. Signature pattern: the wave hero banner

HopeUI's most recognizable element is the blue wave banner behind the page header
(`html/assets/images/dashboard/top-header.png`). Recreating it with a CSS gradient does NOT look
like HopeUI — use the real image (copy it to `ui/public/images/hopeui/top-header.png`, already done
once). The structure (from `dashboard/widget/widgetcard.html`):

- Wrapper `relative isolate`; inside it an `absolute inset-x-0 top-0 -z-10 h-52 lg:h-56
  overflow-hidden rounded-2xl` div containing the `<img>` (`object-cover`, `rounded-2xl`)
- Header text block (`p-6 lg:p-8`, white `h1` + subtitle, action button right) sits on the image
- The first row of content cards renders inside the same wrapper so it **overlaps the banner's
  bottom edge** (HopeUI uses `md:-mt-14`; with the absolute-image approach the overlap happens
  naturally when the banner is taller than the header text)
- The image has a slow-zoom animation (`animScale`: scale 1 → 1.175 → 1 over 45s) — port as a
  scoped `@keyframes`

## 7. Checklist before finishing

- [ ] No `btn*`, `form-control`, `text-body`, `text-heading`, `dark:*-dark-*` classes remain
- [ ] No Alpine attributes (`x-*`) or `setting.*` bindings remain
- [ ] No references to `hopa/` paths or HopeUI asset/JS files
- [ ] Dark mode verified (classes match neighboring nuxnan components)
- [ ] Text through i18n, data through props/store/`useApi`
- [ ] Component named PascalCase in the correct domain folder

# Frontend Rules

Apply these rules to `ui/`.

## Vue/Nuxt

- Use `<script setup lang="ts">` for new Vue SFC code.
- Prefer Composition API patterns already present in nearby files.
- Keep component names PascalCase.
- Keep components in domain folders such as `components/learn/`, `components/academy/`, `components/widgets/`, or established design-system folders.
- Use file-based routing conventions for `pages/`, including `[id].vue` for dynamic params.

## Data Access

- Prefer existing services, composables, or stores.
- Do not add direct `$fetch` calls in components when a project wrapper exists.
- Keep frontend field names aligned with API resources.

## UI

- Use Tailwind utility classes and existing PrimeVue components.
- Avoid broad redesigns unless requested.
- Keep loading, empty, and error states intact.
- Preserve responsive behavior.

## i18n

- Follow existing i18n usage for user-facing strings.
- Update both `ui/i18n/locales/th.json` and `ui/i18n/locales/en.json` when adding translated copy.

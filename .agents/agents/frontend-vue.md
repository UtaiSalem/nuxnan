---
name: frontend-vue
description: Use for nuxnan frontend work in ui/: Nuxt, Vue SFCs, components, pages, composables, Pinia stores, Tailwind, PrimeVue, i18n, and browser-facing UX fixes.
tools: read, edit, shell, browser
model: default
---

# Frontend Vue Agent

You are responsible for frontend work in `ui/`.

## Start Here

1. Read `.agents/rules/project.md` and `.agents/rules/frontend.md`.
2. Inspect nearby pages, components, composables, stores, and services before editing.
3. Check `git status --short` and preserve unrelated user changes.

## Conventions

- Use Nuxt 3/4, Vue 3 Composition API, and `<script setup lang="ts">`.
- Prefer existing composables/services over direct `$fetch` in components.
- Use Pinia for shared state.
- Keep user-facing strings compatible with the existing i18n pattern.
- Match existing Tailwind and PrimeVue patterns.
- Use Iconify or the existing icon system for icons.

## Verification

- Run the narrowest meaningful check first.
- For UI changes, prefer a browser smoke test when the route is known.
- If a full build is too expensive or currently blocked, report that clearly.

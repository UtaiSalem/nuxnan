---
name: nuxnan-vue-sfc
description: Use when editing or creating Vue SFCs in nuxnan ui/: pages, cards, widgets, settings screens, filters, stores, composables, Tailwind layouts, PrimeVue controls, or i18n text.
---

# Nuxnan Vue SFC Skill

## Before Editing

1. Read `.agents/rules/frontend.md`.
2. Inspect the nearest similar component/page.
3. Identify data sources: props, store, composable, service, or API.
4. Check whether i18n keys are needed.

## Editing Rules

- Use `<script setup lang="ts">` for new SFCs.
- Keep props and emits typed.
- Keep local state minimal; move shared state to Pinia only when reused.
- Use existing service/composable wrappers for API calls.
- Preserve loading, empty, and error states.
- Keep layout responsive.

## Verification

- Run TypeScript/build checks if practical.
- For visual changes, open the affected route in the browser when available.
- Mention any route or login state that blocked manual verification.

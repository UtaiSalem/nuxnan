# Analysis: White Screen / Layout Inconsistency Fix

## Root Cause
- Mixed layout declaration patterns:
    - **Pattern A (Correct):** `definePageMeta({ layout: 'main' })` + standard template.
    - **Pattern B (Incorrect):** `definePageMeta({ layout: false })` + `<NuxtLayout name="main">` wrapper in template.
    - **Pattern C (Incorrect-Mixed):** No layout meta + `<NuxtLayout name="main">` wrapper.
- When navigating Pattern A -> Pattern B, `main.vue` is destroyed at the app-level and re-mounted inside the page component.
- `Suspense` inside `NuxtLayout` combined with `pageTransition: { mode: 'out-in' }` and multiple async boundaries causes a race condition where the page fails to re-render, leaving a blank frame (White Screen).
- Reloading works because SSR provides the full HTML, avoiding the client-side navigation deadlock.

## Targeted Files (Phase 1 & 2)
- All game pages under `ui/pages/Play/Games/`
- Earn pages with named slots (`PurchaseHistory`, `Sales`, `Marketplace`)

## Strategy
1. **Unwrap Layout:** Move layout declaration to `definePageMeta`.
2. **Teleport Slots:** Use `Teleport` for `#hero`, `#leftWidgets`, etc., instead of named slots if the layout is no longer wrapping the component.
3. **Signal Layout:** Use `useLayoutWidgets` to toggle sidebar/columns programmatically.

---

## Phase 0: Proof of Concept
- File: `ui/pages/Play/Games/cross-math-game.vue`
- Change: Remove `<NuxtLayout>`, add `layout: 'main'` to meta.
- Verification: Navigate from `/earn/wallet` to `/play/games/cross-math-game` without reload.

## Phase 1: Standardize Simple Games (Group B)
- Files: `mental-math-game`, `snake-game`, `xo-game`, `guessing-number-game`, `english-vocab-game`, `mental-match`, `typing/*`
- Recipe: Unwrap + metadata update.

## Phase 2: Handle Named Slots (Group A)
- Files: `PurchaseHistory`, `Sales`, `History`, `Advertise/create`
- Recipe: Unwrap + Teleport + `useLayoutWidgets`.

## Phase 3: Hardening
- Helper for `crypto.randomUUID()` to prevent production HTTP crashes.
- Error/Empty/Retry states for critical Earn pages.

---
name: hopeui-port
description: Port UI designs from the local HopeUI Pro template library (hopa/) into nuxnan's Nuxt 3 frontend. Use this skill WHENEVER creating or redesigning any page, component, card, widget, dashboard, form, list, or layout in ui/ — even if the user doesn't mention HopeUI — so new UI starts from HopeUI's professionally designed Tailwind markup instead of being invented from scratch. Triggers on "สร้าง component", "สร้างหน้า", "สร้างเพจ", "ปรับปรุง UI", "redesign", "new page", "new component", "hopeui", "hopa", "เอาดีไซน์จาก HopeUI", "ทำหน้าใหม่".
---

# Porting HopeUI Pro UI into nuxnan

nuxnan owns a licensed copy of **HopeUI Pro** (Iqonic Design) stored at `hopa/` in the repo root
(git-ignored). When building or redesigning frontend UI, source the visual design from HopeUI's
**Tailwind variant** instead of designing from scratch — it gives consistent, professional markup
that is already utility-class based and close to nuxnan's stack.

`hopa/` is git-ignored, so it exists only on machines where it was copied manually. If it is
missing on this machine, tell the user (they can copy it from the other machine) and fall back to
matching existing nuxnan components instead — don't fail silently.

## Source of truth

Use ONLY the Tailwind variant as the markup source:

```
hopa/hopeui-pro-tailwind-v3.1.0/hopeui-pro-tailwind/html/      ← 117 finished pages (read these)
hopa/hopeui-pro-tailwind-v3.1.0/hopeui-pro-tailwind/gulp+hbs/  ← theme config + hbs partials
```

The other variants are NOT markup sources (Bootstrap-based, they conflict with Tailwind):

- `hopa/hopeui-pro-vue-4.1.0/code-vue/src/` — reference ONLY for how to split a page into
  components (813 SFCs, Bootstrap markup — never copy its templates or styles)
- `hopa/hopeui-pro-html-4.1.0`, `hopa/hopeui-pro-laravel-4.1.0` — ignore
- `hopa/*.fig` — Figma design files (for the human designer, not for Claude)

## Workflow

1. **Pick the source page.** Read [references/page-catalog.md](references/page-catalog.md) to find
   the HopeUI page that matches the feature (e.g. building a chat UI → `chat/index.html`;
   marketplace → `e-commerce/product-grid.html`). If unsure between candidates, open both and
   compare their main content sections.

2. **Read the HTML page and extract only the section you need.** Every page embeds the full shell
   (sidebar, topbar, settings offcanvas, loader) — that shell already exists in nuxnan's layouts,
   so extract only the main-content cards/sections. Pages are large (1–3k lines); locate the
   `<main>` content area rather than reading from the top.

3. **Convert the markup** following [references/conversion-guide.md](references/conversion-guide.md).
   In short: strip Alpine.js, expand HopeUI-only classes (`btn*`, `form-control`, custom color
   tokens) into plain Tailwind utilities or nuxnan/PrimeVue equivalents, keep nuxnan's brand colors
   (do not import HopeUI's palette), drop `rtl:` variants.

4. **Integrate per nuxnan conventions** (same rules as any ui/ work):
   - `<script setup lang="ts">`, PascalCase component in the right domain folder
     (`ui/components/learn/`, `academy/`, `share/`, …)
   - Data via `useApi`/services/Pinia — replace ALL of HopeUI's hardcoded demo text, avatars, and
     numbers with real bindings or props
   - User-visible strings go through i18n (`ui/i18n/`)
   - Check the nearest existing nuxnan component for dark-mode token usage and match it

5. **Never import HopeUI's CSS or JS files.** No `hope-ui.js`, no `libs.min.js`, no Alpine, no
   copying their compiled stylesheets. Markup structure + utility classes only. If a page relies on
   a JS plugin (ApexCharts, Swiper, flatpickr, …), check `ui/package.json` first — nuxnan may
   already have an equivalent; if not, ask before adding a dependency.

6. **Assets:** if a demo image/illustration is genuinely needed (e.g. error-page artwork), copy just
   that file into `ui/public/images/hopeui/`. Never reference files inside `hopa/` from app code —
   the folder is git-ignored and won't exist on other machines.

## Quality bar

The goal is that the finished component looks like it belongs to nuxnan, not like a pasted
template: nuxnan colors, nuxnan icons (Iconify), real data, translated text, working dark mode.
HopeUI provides the layout, spacing, and structure — nuxnan provides the identity.

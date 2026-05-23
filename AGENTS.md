# AGENTS.md - nuxnan AI agent context

This is the shared entry point for AI coding agents working in this repository.
Read this file first, then follow the linked project-local rules for the task.

## Project Snapshot

- Project: `nuxnan`, a full-stack LMS with academies, courses, lessons, quizzes, assignments, social feed, gamification, wallet, marketplace, chat, and realtime features.
- Frontend: `ui/` - Nuxt 4, Vue 3, TypeScript, Pinia, Tailwind CSS, PrimeVue, i18n, TipTap.
- Backend: `api/nuxnanravel/` - Laravel 12, PHP 8.4, JWT API auth, MySQL, Laravel Reverb, PHPUnit, Laravel Pint.
- Shared agent pack: `.agents/` - Codex-style rules, skills, role definitions, and worklog.
- Claude-specific context: `CLAUDE.md` and `.claude/`.
- Human-facing agent usage guide: `AGENTS_GUIDE.md`.

## Required Reading Order

1. `AGENTS.md` - this central map.
2. `.agents/rules/project.md` - repository rules, protected paths, safety defaults.
3. `.agents/worklog.md` - cross-session handoff state, in-progress work, TODOs, recent context.
4. `.agents/latest-analysis.md` - live analysis board, active work, coordination claims, latest decisions.
5. Task-specific rule or skill:
   - Frontend work: `.agents/rules/frontend.md`, `.agents/agents/frontend-vue.md`, `.agents/skills/nuxnan-vue-sfc/SKILL.md`.
   - Backend API work: `.agents/rules/backend.md`, `.agents/agents/backend-laravel.md`, `.agents/skills/nuxnan-laravel-api/SKILL.md`.
   - Cross-stack work: `.agents/agents/fullstack-integrator.md`, `.agents/skills/nuxnan-fullstack-workflow/SKILL.md`.
   - Database work: `.agents/agents/database-migration.md`.
   - Review or handoff: `.agents/rules/review.md`, `.agents/agents/code-reviewer.md`, `.agents/skills/nuxnan-review/SKILL.md`.
6. `CLAUDE.md` when working through Claude or when deeper historical conventions are needed.

## Repository Map

```text
nuxnan/
  ui/                         Nuxt frontend
    pages/                    File-based routes
    components/               Vue components, grouped by domain and design-system folders
    composables/              use*.ts helpers
    stores/                   Pinia stores
    services/                 API wrappers
    server/                   Nuxt server routes and middleware
    i18n/                     Translations
    nuxt.config.ts

  api/nuxnanravel/            Laravel backend
    app/Http/Controllers/Api/ API controllers, grouped by domain
    app/Models/               Eloquent models
    routes/                   Main and domain route files
    database/migrations/      Schema changes
    database/seeders/         Seed data
    tests/                    PHPUnit tests

  .agents/                    Shared agent instructions and worklog
  .claude/                    Claude-specific agents and settings
```

## Operating Rules

- Run `git status --short` before meaningful edits.
- Treat existing uncommitted changes as user work.
- Search for existing implementation before creating new files.
- Keep changes scoped to the request and preserve local conventions.
- Prefer existing composables, services, stores, controllers, requests, resources, and model relationships over new abstractions.
- For cross-stack changes, keep API field names, validation rules, nullable behavior, filters, and authorization aligned between Laravel and Nuxt.
- Update `.agents/latest-analysis.md` whenever the latest analysis, active files, coordination claims, decisions, or verification changes.
- Update `.agents/worklog.md` when handing off unfinished cross-session work or important durable context.

## Protected Paths

Do not edit these without explicit user approval:

- `api/nuxnanravel/.env`
- `api/nuxnanravel/vendor/`
- `ui/node_modules/`
- `api/nuxnanravel/storage/` except targeted log inspection
- `.claude/worktrees/`

Ask before destructive or data-resetting commands, including:

- `git reset --hard`
- `git clean`
- `git push --force`
- `php artisan migrate:fresh`
- destructive SQL or file deletion outside files created in the current task

## Common Commands

Frontend, from `ui/`:

```bash
npm run dev
npm run build
npm run generate
npm run preview
```

Backend, from `api/nuxnanravel/`:

```bash
php artisan serve
php artisan route:list
php artisan test
./vendor/bin/pint
php artisan reverb:start
```

Use focused verification for the changed area. For documentation-only changes, a read-back check is usually enough.

## Frontend Conventions

- Use `<script setup lang="ts">` by default.
- Use PascalCase for Vue component names.
- Place domain components under folders such as `components/learn/`, `components/academy/`, `components/widgets/`, or existing matching folders.
- Use `use*.ts` naming for composables.
- Use Pinia stores in `ui/stores/` for shared state.
- Prefer existing `useApi` or `services/` wrappers instead of direct ad hoc API calls.
- Keep Tailwind and PrimeVue usage consistent with nearby code.

## Backend Conventions

- Controllers live under `app/Http/Controllers/Api/<Domain>/`.
- Models are flat under `app/Models/`; search before adding new ones.
- Routes are split by domain under `routes/` where possible.
- Use JWT auth with `auth:api` where endpoints require authenticated users.
- Use FormRequest classes or `$request->validate()` for validation.
- Use migrations for schema changes; do not alter schema manually in phpMyAdmin as a substitute for committed migrations.
- Run Laravel Pint for backend formatting when backend PHP files change.

## Agent Handoff Format

When another agent needs to continue work, update `.agents/worklog.md` with:

- Date and branch.
- Current task and exact files touched.
- What is done.
- What remains.
- Verification already run.
- Risks, assumptions, or blockers.

## Current Analysis Notes

- The repository already has multiple agent documents, but this root `AGENTS.md` is the central, tool-agnostic starting point.
- `AGENTS_GUIDE.md` is primarily for human usage instructions.
- `CLAUDE.md` is Claude-specific and contains a broader historical project guide.
- `.agents/rules/project.md` is the canonical safety and repository discipline file for Codex-style agents.
- `.agents/worklog.md` is the canonical cross-session handoff file.
- `.agents/latest-analysis.md` is the canonical live analysis and multi-agent coordination file.

# Project Rules

These rules apply to all nuxnan agents.

## Repository Shape

- Frontend: `ui/` - Nuxt 3/4, Vue 3, TypeScript, Pinia, Tailwind, PrimeVue.
- Backend: `api/nuxnanravel/` - Laravel 12, PHP 8.4, JWT API auth, MySQL.
- Existing Claude-specific setup: `.claude/`.
- Codex-style local setup: `.agents/`.

## Worktree Discipline

- Always check `git status --short` before meaningful edits.
- Treat existing uncommitted changes as user work.
- Do not revert user changes unless the user explicitly asks.
- Keep edits scoped to the request.
- Avoid touching generated output, dependency folders, or copied worktrees.

## Protected Paths

Do not edit these without explicit user approval:

- `api/nuxnanravel/.env`
- `api/nuxnanravel/vendor/`
- `ui/node_modules/`
- `api/nuxnanravel/storage/` except targeted log inspection
- `.claude/worktrees/`

## Dangerous Commands

Require explicit approval before running:

- `git reset --hard`
- `git clean`
- `git push --force`
- `php artisan migrate:fresh`
- destructive SQL or file deletion

## Communication

- Explain what changed and how it was verified.
- If verification could not be run, state why.
- Prefer concise Thai for project handoffs unless the user asks otherwise.

## User-Directed Planning Mode

- When the user describes a problem or a desired goal, analyze, inspect, and provide a detailed implementation plan only.
- Do not implement code changes, edit files, or run mutating commands unless the user explicitly asks Codex to do the implementation.
- The plan should include step-by-step development guidance, likely files/modules, validation points, testing steps, and risks or cautions.
- Read-only inspection commands are allowed when needed to understand the codebase and produce an accurate plan.

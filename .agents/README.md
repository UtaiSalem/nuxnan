# Nuxnan Agent Pack

This folder contains project-local agent instructions, rules, and skills for the nuxnan LMS codebase.

Use these files as the shared operating guide for Codex-style agents. The existing `.claude/` folder remains the Claude-specific setup; this `.agents/` pack is intentionally separate so both systems can coexist without overwriting each other.

## Contents

- `agents/` - role definitions for focused work.
- `rules/` - project guardrails and conventions.
- `skills/` - reusable workflows for common nuxnan tasks.

## Default Routing

- Frontend Nuxt/Vue work: use `agents/frontend-vue.md` and `skills/nuxnan-vue-sfc/`.
- Backend Laravel API work: use `agents/backend-laravel.md` and `skills/nuxnan-laravel-api/`.
- Cross-stack feature or bug work: use `agents/fullstack-integrator.md` and `skills/nuxnan-fullstack-workflow/`.
- Database schema or query work: use `agents/database-migration.md`.
- Before commit, PR, or handoff: use `agents/code-reviewer.md` and `skills/nuxnan-review/`.

## Safety Defaults

- Treat existing uncommitted changes as user work.
- Do not edit `.env`, dependency folders, generated build output, or copied worktrees unless explicitly asked.
- Do not run destructive commands such as `git reset --hard`, `migrate:fresh`, or force push without explicit approval.
- Prefer small, reversible changes with focused verification.

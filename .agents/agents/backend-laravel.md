---
name: backend-laravel
description: Use for nuxnan backend work in api/nuxnanravel/: Laravel controllers, models, resources, requests, routes, middleware, services, jobs, auth, and API behavior.
tools: read, edit, shell
model: default
---

# Backend Laravel Agent

You are responsible for Laravel API work in `api/nuxnanravel/`.

## Start Here

1. Read `.agents/rules/project.md` and `.agents/rules/backend.md`.
2. Inspect nearby controllers, requests, resources, models, routes, and tests before editing.
3. Search existing models before creating new ones; this project has many models.

## Conventions

- Use Laravel 12 and PHP 8.4 style.
- Put API controllers under `app/Http/Controllers/Api/<Domain>/`.
- Prefer FormRequest classes for reusable validation.
- Use API Resources when response shape matters.
- Keep JSON response shape consistent with nearby endpoints.
- Use `auth:api` and JWT patterns already present in the codebase.

## Verification

- Run focused PHPUnit tests when available.
- Run Pint for touched backend files when practical.
- Never run `migrate:fresh` or destructive data operations without explicit approval.

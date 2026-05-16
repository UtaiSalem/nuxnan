---
name: nuxnan-laravel-api
description: Use when changing Laravel API behavior in api/nuxnanravel/: routes, controllers, FormRequests, resources, models, migrations, auth, marketplace/course APIs, and validation.
---

# Nuxnan Laravel API Skill

## Before Editing

1. Read `.agents/rules/backend.md`.
2. Locate the route and controller first.
3. Inspect request validation, resource shape, model relationships, and tests.
4. Search for similar endpoints in the same domain.

## Implementation

- Keep controller methods focused.
- Put reusable validation in FormRequests.
- Shape public responses through resources when the UI depends on fields.
- Use policies/middleware/auth checks already established by the project.
- Add eager loading when resources access relationships in list endpoints.
- Create migrations for schema changes; do not edit schema manually.

## Verification

- Prefer focused tests.
- Run Pint for touched PHP files when practical.
- If DB migration is involved, report whether it was created, run, or left for the user to run.

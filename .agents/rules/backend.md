# Backend Rules

Apply these rules to `api/nuxnanravel/`.

## Laravel Structure

- API controllers live under `app/Http/Controllers/Api/<Domain>/`.
- Models live under `app/Models/`.
- Form requests live under `app/Http/Requests/`.
- API resources live under `app/Http/Resources/`.
- Routes are grouped by domain under `routes/` where the project already does so.

## API Behavior

- Keep JSON shapes consistent with nearby controllers.
- Use API Resources for stable public response contracts.
- Validate inputs with FormRequest classes for reusable create/update flows.
- Protect authenticated routes with existing JWT `auth:api` patterns.
- Avoid session-auth assumptions in API code.

## Data

- Define `$fillable` or `$guarded` deliberately.
- Add `$casts` for booleans, dates, JSON, arrays, and numeric values when relevant.
- Eager-load relationships when response resources would otherwise trigger N+1 queries.
- Prefer additive migrations and reversible schema changes.

## Verification

- Use `php artisan test` or focused tests when present.
- Use `./vendor/bin/pint` for backend formatting when practical.

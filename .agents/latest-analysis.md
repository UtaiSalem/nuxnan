# Latest Analysis - nuxnan shared AI context

Purpose: this file is the always-current analysis board for AI agents working on
nuxnan. Read it after `AGENTS.md`, `.agents/rules/project.md`, and
`.agents/worklog.md` before changing code.

## Update Protocol

- Update this file whenever work changes direction, a meaningful analysis is made, files are edited, verification is run, or a task is handed to another agent.
- Keep `Current Snapshot` and `Active Work` fresh.
- Append short entries to `Analysis Timeline`; do not rewrite history unless consolidating old noise.
- If multiple agents are working, claim a small scope in `Coordination Board` before editing.
- Release or update your claim when done, blocked, or handing off.
- Mention exact files, commands, assumptions, and remaining risks.
- Keep secrets out of this file. Never paste `.env` values, tokens, private keys, or user credentials.

## User Analysis Input

> Trigger: when the user asks to read the analysis, verify it against the codebase, improve or correct it, and then record the updated findings here.

_(empty — awaiting next analysis input)_

---

## Current Snapshot

- Date: 2026-06-07
- Branch: main
- Active Work: _none — ready for new task_

## Known Blockers

- _none currently recorded_ (SQLite ENUM migrations now guarded with `DB::getDriverName()` checks)

## Active Work

_(empty)_

## Coordination Board

- _empty_

## Decisions And Assumptions

- Role `STUDENT` (not `USER`) is the default general-user role.
- `username` is treated as a primary identifier field in admin flows.
- Score schema uses `*_percentage` columns; legacy `*_total_score` consumers are mapped through `CourseMemberResource` for backward compatibility.
- Avatar is an Eloquent accessor (not a DB column) — never include it in `select(...)` eager loads; select `profile_photo_path` instead.
- Notification copy must avoid 4-byte emoji while DB collation is `utf8mb3_unicode_ci`.

## Open Questions

- _none_

## Analysis Timeline

### 2026-06-07 - Cleared backlog and committed pending work
- Scope: completed all pending uncommitted work surfaced in the prior snapshot.
- Commits landed on `main`:
  - `fix(certificates): add course-level issue/issue-all routes and normalize payloads`
  - `fix(remediation): repair service parse error and disable standalone sessions`
  - `fix(gradebook-appeals): normalize paginator and drop accessor avatar select`
  - `fix(course-completion): resolve finalize 500s and schema fallout`
  - `fix(gradebook): resolve responsive table issues and score breakdown 500`
  - `chore(course): misc cleanups across controllers, model and tab bar`
- Verification: `php -l` on every modified controller/service/route passed; Pint normalized 4 style issues across 16 PHP files; basic SFC tag-balance check on every modified `.vue` passed.
- Blockers cleared: previous "SQLite/MySQL ENUM" blocker is no longer reproducible — every `MODIFY COLUMN ... ENUM` migration is guarded with `DB::getDriverName()` and `typing_sentences` generated column uses `config('database.default') !== 'sqlite'`.
- Remaining risk: no authenticated browser smoke test was run for course 21 in this turn; suggest a manual pass over `/Learn/Courses/{id}/gradebook/{index,appeals,certificates,completion,remediation}` before declaring the gradebook rebuild done.

### 2026-06-07 - Course eligibility roster filtering started
- Active Work: Course eligibility roster filtering for `/Learn/Courses/{id}/gradebook/eligibility`.
- Scope: filter course admins/TA out of the exam eligibility roster, add group filtering, and make individual student search reliable.
- Intended files: `api/nuxnanravel/app/Services/AttendanceEligibilityService.php`, `ui/pages/Learn/Courses/[id]/gradebook/eligibility.vue`.
- Finding: `AttendanceEligibilityService::getCourseEligibilitySummary()` currently reads all `course_members`, so role `3/4` course admins and TAs can appear in the table.
- Finding: eligibility UI already searches `member_code`, but summary payload does not include `member_code`; group info is also missing even though course members have `group_id`.
- Decision: filter summary and refresh calculations to learner roles `[1, 2]`, return member identity/group fields, and add client-side group filter/search reset behavior.
- Verification plan: PHP lint on touched service, focused frontend sanity where feasible, `git diff --check`.

### 2026-06-07 - Course eligibility roster filtering completed
- Changed: `AttendanceEligibilityService` now calculates summary/refresh/bulk operations against learner roles `[1, 2]`, excluding course TA/admin roles `[3, 4]`.
- Changed: eligibility summary payload now includes `member_name`, `member_code`, `role`, `group_id`, `group`, and user `email` so the UI can search and group-filter reliably.
- Changed: `ExamEligibilityController::bulkUnlock()` respects learner roles, supports `only_ineligible` without explicit member IDs, and avoids accidental all-course unlock unless that flag or a group/member list is provided.
- Changed: `ui/pages/Learn/Courses/[id]/gradebook/eligibility.vue` adds group dropdown filtering, expanded individual search, group display in desktop/mobile rows, and filtered bulk unlock IDs.
- Verification: `php -l app\Services\AttendanceEligibilityService.php` passed; `php -l app\Http\Controllers\Api\ExamEligibilityController.php` passed; Pint passed on touched PHP files; SFC parse of eligibility page passed; `git diff --check` passed.
- Blocked verification: full `vue-tsc --noEmit` still fails on pre-existing project-wide TypeScript errors and `vue-router/volar/sfc-route-blocks` export issue; localhost route returns `302 /auth` without an authenticated session, so browser-content smoke was not completed.

### 2026-06-07 - Added typing practice vocabulary migration
- Changed: added `api/nuxnanravel/database/migrations/2026_06_07_103000_add_practice_words_to_typing_words_table.php`.
- Details: migration inserts 60 active `typing_words` rows across Thai and English, all under category `practice_expansion`, using `updateOrInsert` keyed by language/difficulty/text.
- Verification: `php -l database\migrations\2026_06_07_103000_add_practice_words_to_typing_words_table.php` passed; `php artisan migrate` ran and completed; DB count for `typing_words.category = practice_expansion` is 60.
- Note: `php artisan tinker --execute` was blocked by PsySH history write permission, so count was verified with a Laravel bootstrap PHP one-liner instead.

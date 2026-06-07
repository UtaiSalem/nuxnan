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

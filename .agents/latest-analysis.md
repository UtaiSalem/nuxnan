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

> Trigger: when the user says "อ่านบทวิเคราะห์", read this section, verify it against the codebase, improve or correct it, make a clear work plan, and record that plan below.

(ว่าง - พร้อมรับบทวิเคราะห์หรืองานถัดไป)

---

## Work Plan

### 2026-05-31 - SMS Plan Refined — Detailed Implementation Tasks - COMPLETED

**Context:** User requested detailed, actionable task breakdown for School Management System plan so they or other AI agents can implement each phase.

- Ran full route audit: `php artisan route:list --path=academies` vs `useSchoolManagement.ts`
- Found **12 confirmed route drifts** (meeting-slots, fee-structures, expense-categories, method mismatches)
- Found **large missing sections** in composable: emergency alerts (entire section), meeting booking management, event registration extras, parent portal endpoints, finance/budget/payroll extras
- Confirmed student/teacher dashboards are shallow with TODO placeholders and dead links
- **Updated `.agents/school-management-system-plan.md`** with:
  - Complete route drift table (Section 2A)
  - Missing endpoint inventory (Section 2B)
  - Detailed per-Phase task lists with specific files, acceptance criteria, estimated effort
  - 10-item priority queue in Section 9
  - Updated data principles, risks, and DoD table

### 2026-05-31 - School Management System master plan - COMPLETED

**Context:** User asked to research, analyze, and plan the next School Management System layer beyond course/LMS management, while preserving the Play Learn Earn concept and recording the plan centrally for other AI agents.

- Completed read-only repo inspection of existing academy/school modules, routes, controllers, migrations, frontend pages, and `useSchoolManagement.ts`.
- Created shared planning baseline: `.agents/school-management-system-plan.md`.
- Main decision: treat SMS as a digital school campus around the academy feed/flagpole yard, with classroom, office, teacher room, finance room, parent portal, store, and director dashboard connected by shared identity/permissions and activity events.
- Key implementation caution: stabilize existing route/API contracts first because school frontend components and composable already show contract drift and placeholder logic.

### 2026-05-30 — Commit pending changes (3 logical commits) — COMPLETED

**Context:** User Analysis Input ว่าง แต่มี uncommitted changes 18 ไฟล์จาก 3 งานที่เสร็จแล้วใน session ก่อน

- ✅ Commit 1 — User Profile Fixes (Phases 1–7)
- ✅ Commit 2 — Dashboard Leaderboard NaN Fix
- ✅ Commit 3 — Sidebar Widget Timeout Fix

---

## Current Snapshot

- Date: 2026-05-31
- Branch: main
- Repository: `C:\wamp64\www\nuxnan`
- Frontend: `ui/` Nuxt/Vue/TypeScript/Pinia/Tailwind/PrimeVue
- Backend: `api/nuxnanravel/` Laravel/PHP/JWT/MySQL/Reverb
- Current SMS planning note: All phases (0-6) are complete. The "Digital Campus" is fully realized with Academic, Finance, Staff, Communication, Economy, Reports, Library, and Asset management systems.
- Current focus: Final Quality Assurance and UI/UX Polish.
- Pending commit: ไม่มี - ทุกงาน committed แล้ว

## Active Work

| Scope | Owner | Status | Files | Notes |
| --- | --- | --- | --- | --- |
| SMS Phase 6 Complete | AI | completed | `Library*.php`, `Asset*.php`, `SchoolLibraryTab.vue`, `SchoolAssetTab.vue`, `useSchoolManagement.ts`, `SchoolManagement.vue` | Library and Asset modules implemented. Phase 6 done. |
| - | - | - | - | ไม่มีงานที่กำลังทำอยู่ |

## Coordination Board

| Claim ID | Owner | Scope | Files or folders | Status | Handoff note |
| --- | --- | --- | --- | --- | --- |

## Decisions And Assumptions

- `AGENTS.md` is the tool-agnostic root entry point for all AI agents.
- `.agents/latest-analysis.md` is the live analysis and coordination board.
- `.agents/worklog.md` remains the cross-session handoff log.
- `CLAUDE.md` remains Claude-specific historical/project guidance.
- School Management System should be developed as a unified academy-level campus system, not a separate disconnected ERP.
- The academy feed is the central school courtyard/flagpole yard and should receive meaningful events from SMS modules.
- Stabilize existing SMS API contracts before adding major new modules.
- **New Decision**: Focus on Phase 0 (Route Stabilization) and Phase 1 (Dashboards) first to provide immediate user value.

## Open Questions

- Which campus modules should be implemented first after Phase 0: Student Today/Homeroom, Attendance, Finance, or Feed integration?
- Should future parent notifications target in-app only first, or include external channels such as email/SMS/Line?

(ไม่มี)

## Analysis Timeline

### 2026-05-31 - Academy child-route and classroom students API hotfix
- User pasted browser logs showing `ui/pages/academies/[name].vue` transition warnings because the page rendered a non-element/multiple-root route node, plus repeated 500s from `GET /api/academies/{academy}/classrooms/students`.
- Finding: `ClassroomController::getAllStudents()` ordered by `students.student_number`, but the number belongs to the `classroom_students` pivot. Frontend attendance also assumed the endpoint was always a plain array.
- Changed `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/ClassroomController.php` to order by active pivot student number through subselects, expose `student_number`/`classroom_id` on each item, and return array-shaped `students`/`data` plus separate pagination metadata.
- Changed `ui/pages/academies/[name]/admin/school-attendance/[id].vue` to unwrap either array or paginated response shapes and submit `user_id` for attendance records when available.
- Changed `ui/pages/academies/[name].vue` to wrap route/main content in a single root element for Nuxt route transitions.
- Verification: `php -l api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/ClassroomController.php` passed; `php artisan route:list --path=classrooms/students` confirmed `GET api/academies/{academy}/classrooms/students`; targeted Pint ran for `ClassroomController.php`.

### 2026-05-31 - SMS Plan Refined to Version 2.0
- Re-analyzed codebase and plan. Verified route drifts in `useSchoolManagement.ts` against actual Laravel routes.
- Identified that most communication, finance, and staff tables already exist in the DB, but `classroom_attendance` is a key gap.
- Refined the plan to include:
  - **Play Learn Earn Hooks**: Specific integration points (e.g., points for attendance).
  - **Database Schema**: Proposed migration for `classroom_attendances`.
  - **Agent Task Queue (T01-T07)**: Discrete, actionable tasks for implementation.
  - **Dashboard Focus**: Prioritizing student/teacher/parent dashboards as the system's "Heart".
- No app code changed yet. Plan is ready for the next agent or implementation turn.

### 2026-05-31 - School Management System master plan recorded
- User requested detailed planning for School Management System / school-life features beyond Course/LMS management, preserving Play Learn Earn.
- External research baseline: common SMS/SIS systems center on student records, attendance, timetable, gradebook/transcripts, communication, fees, staff, parent portals, and reporting; ERP-style products add payroll, transport, library/assets, inventory, alerts, analytics, and unified permissions.
- Repo finding: nuxnan already has broad academy SMS foundations: school admin page, school composable/tabs, academy feed, members/roles, students/guardians/cards/home visits, classrooms, schedules, gradebook/transcripts, finance, staff/leave/payroll, announcements/events/emergency/messages/meetings, parent portal, store/wallet/points, reports/analytics.
- Risk finding: several surfaces are incomplete or contract-drifted, especially `useSchoolManagement.ts` route mapping vs Laravel routes, `SchoolReportsTab.vue` placeholder analytics/actions, report generation/export TODOs, emergency/meeting notification TODOs.
- Plan recorded in `.agents/school-management-system-plan.md` with role journeys, core systems, Play Learn Earn hooks, phased roadmap, data principles, immediate task queue, risks, and module definition of done.
- Recommended next step: Phase 0 route/API contract audit and typed frontend service cleanup before implementing new school modules.

### 2026-05-30 - Course member identity duplication analysis
- User asked for a sustainable plan to avoid requiring students to update name, student/member code, and order number separately for every course enrollment.
- Finding: course membership identity fields currently live on `course_members` (`member_name`, `member_code`, `order_number`) and are editable through `CourseMemberController::update`, `updateOrderNumber`, `updateMemberCode`, `updateOwnProfile`, plus UI surfaces such as `MyProgressDetails.vue`, `ProgressList.vue`, member edit pages, groups, attendance, gradebook, and progress views.
- Related source-of-truth candidates already exist: `students.student_id`, `academy_members.member_code`/`student_id`, and `classroom_students.student_number`; courses also have nullable `academy_id`, so an academy/course-aware learner profile is safer than copying values into every course member record.
- Recommended direction: introduce a centralized learner profile/resolution layer, keep course-specific overrides only when genuinely needed, backfill from existing `course_members`, then update resources/UI to read effective values from the centralized source while preserving backward compatibility.
- Verification plan for implementation: migration/backfill tests, course enrollment/update feature tests, resource contract checks for members/progress/groups/attendance, and focused Nuxt smoke tests for course join, my progress, member list, and admin progress editing.

### 2026-05-30 - Course progress includes admin/owner analysis
- User reported `/Learn/Courses/5/progress` shows course owner/admin in Top Performers and the needs-help card.
- Read-only finding: `ui/components/learn/course/ProgressList.vue` renders progress from `/api/courses/{course}/progress` and top performers from `/api/courses/{course}/top-performers`; at-risk students are computed from the returned `members`.
- Backend finding: `CourseController::progress()` and `CourseController::topPerformers()` both start from `$course->courseMembers()` without filtering learner roles or excluding the course owner. `course_members.role` documents 1=student, 2=student_leader, 3=teacher, 4=admin.
- Likely fix plan: add a shared learner-member query/scope for active learner memberships, probably `whereIn(role, [1, 2])`, `where(course_member_status, 1)`, and `where(user_id, '!=', $course->user_id)`, then use it consistently in progress, stats, top performers, grade distribution, at-risk card, and export if expected to be learner-only.
- Verification plan: add/adjust focused feature coverage for a course with student, student_leader, teacher/admin, and owner membership; verify `/api/courses/{course}/progress` pagination/stats and `/top-performers` exclude non-learners; smoke-test `/Learn/Courses/5/progress`.

### 2026-05-30 - Dashboard activity login label analysis
- User asked whether `login` and `เข้าสู่ระบบ` in `/dashboard` recent activity are the same thing.
- Finding: `DashboardActivityFeed.vue` renders two separate data sources: gamification `recent_xp` and points `transactions`. Auth login fires `UsageEventType::LOGIN`; `GamificationRuleEngine` then creates an XP/rule log and points transactions for the login rule and daily-login quest.
- Interpretation: `login`, `เข้าสู่ระบบ`, and `รางวัลภารกิจ: Daily Login` are related to the same login usage event, but displayed as separate reward records from different systems. No code changes requested yet.

### 2026-05-30 - Dashboard leaderboard NaN analysis
- User reported `/dashboard` top points leaderboard showing `NaN P`.
- Finding: `DashboardLeaderboard.vue` formats `user.total_points`, while `/api/gamification/leaderboard/points` currently returns `points` from `users.pp` without a `total_points` alias.
- Plan: keep the API contract compatible by adding `total_points` to the points leaderboard response, and make the dashboard widget normalize `total_points`/`points`/`score` safely before formatting.
- Intended files: `api/nuxnanravel/app/Http/Controllers/Api/GamificationController.php`, `ui/components/dashboard/DashboardLeaderboard.vue`.
- Verification plan: run PHP syntax check for the controller and a focused frontend type/lint check if practical; browser smoke test `/dashboard` if local auth/session allows.
- Completed: API now returns both `points` and `total_points`; dashboard widget falls back across `total_points`, `points`, `score`, and `pp` and formats only finite numbers.
- Verification: `php -l app/Http/Controllers/Api/GamificationController.php` passed; Pint ran for `GamificationController.php`; `php artisan route:list --path=gamification/leaderboard/points` confirmed the route; local endpoint returned numeric `total_points`; `http://localhost:3000/dashboard` returned 200. `cmd /c npx vue-tsc --noEmit --pretty false` still fails on broad pre-existing TypeScript errors unrelated to this widget.

### 2026-05-30 - Sidebar widget API timeout fix completed
- User reported 30s frontend timeouts for `/api/friends/suggestions`, `/api/friends/pending`, `/api/donates/widget`, and `/api/advertises/widget`.
- Findings: routes existed and `/api/ping` responded, but local PHP responses were slow enough that concurrent widget calls could queue. Friend widget endpoints serialized full `UserResource` records and triggered extra count queries; advert widget serialized advertiser via full `UserResource`; donate widget selected full rows.
- Changed: compact friend widget payloads in `FriendController`, narrow donate/advert widget queries, compact `AdvertResource` advertiser payload, and fix `AdvertisesWidget.vue` click handler mismatch.
- Verification: PHP syntax checks passed; Pint ran on the touched PHP files; direct endpoint timings after the change were about 2.46-3.44s. `npx nuxi typecheck` still fails on broad pre-existing TypeScript issues across unrelated files; `npm run build` reached client bundling but timed out at 180s.

### 2026-05-30 - Cleared stale analysis board
- Removed the completed User Profile analysis and Work Plan from this file so the board is ready for the next task.
- Reset Current Snapshot and Active Work to show no active task.

### 2026-05-29 - User profile page fixes completed
- User Profile phases were implemented before this cleanup, including backend privacy/resource updates, frontend profile/sidebar/tab fixes, rich text handling, video/certificate behavior, and `UserProfilePrivacyTest.php`.
- Existing uncommitted files still include those profile changes; treat them as user/previous-session work unless explicitly asked to modify them.

### 2026-05-29 - Exam Retake Phase 2 + Course Feed Edit Bug completed
- Course Feed edit fixed by using `api.post` with `_method=PATCH` in `FormData` for multipart updates.
- Exam Retake Phase 2 added retake grant/use fields, remediation grant logic, quiz result use tracking, `retake_status` response data, and frontend panel states.
- Committed: `3caf0ffc` (feed fix), `26b04ce5` (retake phase 2).

### 2026-05-29 - Course feed admin delete/copy plan review
- Read-only inspection confirmed create/update/delete routes were distinct and backend delete behavior was valid.
- Likely bug was frontend multipart PATCH handling in `CourseEditPostModal.vue`; recommended body `_method=PATCH` convention.

### 2026-05-27 - Typing Classroom Race review and fixes
- Reviewed `race.vue`, `useClassroomRace.ts`, and `TypingRaceController.php`.
- Fixed countdown view, Echo leave usage, progress throttle cleanup, finalize logic for left participants, and rank race condition.
- Committed in `f389406e`.

# Work Log — nuxnan project

ไฟล์นี้ใช้สำหรับส่งต่อ context ระหว่างที่บ้านและที่ทำงาน
**กฎ: ก่อนออกจากแต่ละที่ → อัพเดทไฟล์นี้แล้ว `git push`**
**กฎ: มาถึงที่ใหม่ → `git pull` แล้วอ่านไฟล์นี้ก่อนเริ่มงาน**

---

## สถานะปัจจุบัน (2026-06-21)

- **Done:** Phase 2 (Service Layer Expansion):
  - **2.A Helper methods:** Extracted helper methods `closeActiveEnrollment`, `manageAcademicInfoSnapshot`, and `normalizeGradeLevel` inside `StudentEnrollmentService`.
  - **2.B-2.D Lifecycle transitions:** Added new methods `graduateStudent`, `dropStudent`, `repeatStudent`, and `promoteStudent`, and refactored `transferStudent` with strict year checks.
  - **2.E Event classes:** Added 8 plain event classes in `app/Events/Enrollment/` to broadcast enrollment lifecycle updates.
  - **2.F-2.I Rollover Service:** Implemented `AcademicYearRolloverService` with comprehensive operations: `previewRollover` (suggesting mappings + fallbacks + warnings), `planRollover` (validations), `commitRollover` (UUID generation + batch execution), `undoRollover` (reverting state and deleting temporary records with a 24h window), and `closeUndoWindow`.
  - **Verification:** Added feature tests `StudentEnrollmentServiceTest.php` and `AcademicYearRolloverServiceTest.php`. All 28 tests (101 assertions) passed successfully (100% pass). Runs pint clean.

- **In-Progress:** Phase 3 (Controller & API Surface):
  - **3.A Policy + Gates:** Created `EnrollmentPolicy` with methods `lifecycle` (admin/director/owner or homeroom teacher), `previewRollover`, `planRollover`, `commitRollover`, `undoRollover`, and `viewBatches`. Registered gates in `AppServiceProvider`. Verified with `EnrollmentPolicyTest.php` (13 tests, 24 assertions, 100% pass).
  - **3.B FormRequests + API Resources:** Created 5 FormRequests for Student lifecycle and Rollover, and 3 Resources (ClassroomStudentResource, RolloverBatchResource, StudentSummaryResource).
  - **3.C StudentLifecycleController:** Implemented 6 lifecycle endpoints for students (graduate, drop, repeat, promote, transfer) and a V2 history endpoint. Verified with 16 new tests in `StudentLifecycleControllerTest.php`. Total enrollment tests: 74 tests, 220 assertions, 100% pass.
  - **3.D RolloverController (Read endpoints):** Created 4 controller methods (`preview`, `plan`, `index`, `show`) and registered routes. Implemented user-scoped rollover plan caching and scopeBindings routing. Verified with `RolloverControllerReadTest.php` (15 tests, 60 assertions, 100% pass). Total enrollment tests: 89 tests, 280 assertions, 100% pass.
  - **3.E RolloverController (Write endpoints):** Planned for commit, undo, close-undo operations, cache retrieval, and confirm_text validation. Next up.

- **Done:** Phase M (Gamification: School Level & Classroom Leaderboard):
  - **M.0 Database Schema & Migrations:** Created migrations for `xp_events`, `school_xp_cycles`, and `classroom_point_cycles`. Migrated tables successfully.
  - **M.1 Services & Configs:** Created `XpService` and `ClassroomPointsService` sharing `HasGamificationCycles` trait. Created config files `config/xp_rates.php` and `config/gamification.php`.
  - **M.2 Model Definitions:** Defined Eloquent models `XpEvent`, `SchoolXpCycle`, and `ClassroomPointCycle`. Added safety checks for null types in `AcademyGroup`.
  - **M.3 Observers & Hooks:** Implemented observers for `AcademyPost`, `AcademyPostLike`, `AcademyPostComment`, `CourseMember` (course completion), `EventRegistration` (event attended), and `AssignmentAnswer` (assignment submission). Registered them in `AppServiceProvider`. Hooked check-in methods inside `SchoolAttendanceController`.
  - M.4 API Endpoints: Created `GamificationController` and registered endpoints `summary`, `leaderboard`, and `recent` under `/api/academies/{academy}/gamification/...`.
  - **M.5-M.7 Frontend integration:** Hooked gamification summary endpoint in Nuxt academy profile page `[name].vue`. Created and mounted `<SchoolLevelCard />` in the left sidebar and `<SchoolClassroomLeaderboard />` in the right sidebar.
  - **M.8 Cron & Scheduler:** Created `InitializeCycleRowsCommand` and scheduled it to run weekly and monthly inside `routes/console.php`.
  - **M.9 Backfill Seeder:** Wrote `BackfillGamificationSeeder` to compute historical XP and classroom leaderboard points from existing database tables.
  - **Verification:** Created feature tests in `SchoolGamificationTest.php` and got 100% pass (4 tests, 35 assertions).

### งานที่เพิ่งเสร็จสิ้น — Verified & Tested

- **Done:** Phase L (Closeout, Events mirroring & Post Variants):
  - **L.0 Notifications Bell Mount:** Mounted `<NotificationsNotificationBell />` in `ui/layouts/main.vue`.
  - **L.1 Admin Appointer Trail:** Added appointer tracking on `AcademyGroupAdmin` and displayed it in `ManageTabAdmins.vue`.
  - **L.2 Schema Integration:** Added variant columns (`post_type`, `target_audience`, `reward_points`, `embed_data`, `is_pinned`) via DB migration to `academy_posts` and configured validation rules/casts.
  - **L.3 School Events Feed Mirroring:** Created `EventToPostMirror` service. Configured idempotent check/update logic to mirror events into feed when published, and to cascade-delete (`unmirror()`) mirrored posts (cleaning up `Activity`, images, and comments) when cancelled/deleted in `SchoolEventController`.
  - **L.4-L.7 Rendering Variants:** Added computed properties (`isDirector`, `audienceLabel`, `eventData`, `isEvent`, `progressData`) and rendered director gradients, date chips, registration cards, attendance bars, audience badges, and reward chips in `FeedPost.vue`.
  - **L.8 Composer picker:** Upgraded `CreatePostModal.vue` with post type selector and conditional input parameters restricted to admins.
  - **L.9 E2E Verification:** Verified PHP syntax (100% pass) and ran targeted backend tests (`AcademyGroupPhaseGTest` 100% pass). Fixed a JavaScript catch-block syntax annotation in `FeedPost.vue` to ensure compile-readiness.

- **Done:** Single Source of Truth NotificationService & Polling (Phase K updates):
  - Created [NotificationService.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Services/NotificationService.php) with `send()` and `sendBulk()` methods to unify database notifications.
  - Refactored [AcademyGroupAdminController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupAdminController.php), [AcademyGroupController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupController.php), and [AcademyPostController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyPostController.php) to call `NotificationService`.
  - Added feature tests in [NotificationServiceTest.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/NotificationServiceTest.php) (100% pass, 2 tests, 22 assertions).
  - Updated [NotificationBell.vue](file:///C:/wamp64/www/nuxnan/ui/components/notifications/NotificationBell.vue) to poll for recent notifications every 60s, guarded by `document.hidden` check, and added `visibilitychange` listener to poll immediately when tab becomes active.
- **Done:** Invite Flow + Admin Appointment + Group Notifications (Phase K):
  - Defined group notification type constants, custom icons, and color themes in [Notification.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Models/Notification.php) model.
  - Implemented automatic database notification triggers in `addMember()` inside [AcademyGroupController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupController.php) when members are added.
  - Implemented database notification triggers in `store()` inside [AcademyGroupAdminController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupAdminController.php) when leaders are appointed.
  - Integrated notification loop inside `store()` in [AcademyPostController.php](file:///C:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyPostController.php) to notify all group members/admins (excluding the poster) when a post is created in the name of the group.
  - Verified invitation/appointment flow works seamlessly on frontend using `MemberAutocompleteInput` in [ManageTabMembers.vue](file:///C:/wamp64/www/nuxnan/ui/components/academy/groups/ManageTabMembers.vue) and [ManageTabAdmins.vue](file:///C:/wamp64/www/nuxnan/ui/components/academy/groups/ManageTabAdmins.vue).
  - Verified that group notifications display perfectly with appropriate type-mapped icons and color styling on the [notifications.vue](file:///C:/wamp64/www/nuxnan/ui/pages/notifications.vue) page.
  - Ran backend test suite and got 100% pass (14 tests, 121 assertions).
- **Done:** Post-as-Group Composer + Feed Header (Phase J):
  - Added smart backend endpoint `postableForUser` under `/api/academies/{academy}/postable-groups` that automatically filters by membership + `can_post` status.
  - Extended [useAcademyGroups.ts](file:///C:/wamp64/www/nuxnan/ui/composables/useAcademyGroups.ts) with cached helper and `invalidatePostableCache` invalidation method.
  - Implemented standalone [PostAsSelector.vue](file:///C:/wamp64/www/nuxnan/ui/components/academy/groups/PostAsSelector.vue) component with compact, full, and locked modes.
  - Integrated selector into [CreatePostBox.vue](file:///C:/wamp64/www/nuxnan/ui/components/play/feed/CreatePostBox.vue)/[CreatePostTrigger.vue](file:///C:/wamp64/www/nuxnan/ui/components/play/feed/CreatePostTrigger.vue) trigger area and [CreatePostModal.vue](file:///C:/wamp64/www/nuxnan/ui/components/play/feed/CreatePostModal.vue) header/payload submission.
  - Overrode [FeedPost.vue](file:///C:/wamp64/www/nuxnan/ui/components/play/feed/FeedPost.vue) header to render group avatar, group name, verified badge, and type label when `posted_as_group` is active, and added real author credit line ("โดย {user.name}").
  - Wired locked mode inside [GroupFeedTab.vue](file:///C:/wamp64/www/nuxnan/ui/components/academy/groups/GroupFeedTab.vue) to constrain composer to the current group on its profile page.
  - Triggered cache invalidation in [ManageTabPermissions.vue](file:///C:/wamp64/www/nuxnan/ui/components/academy/groups/ManageTabPermissions.vue) on saving new settings.
- **Done:** Academy Group Profile Page (Work Plan v2 - Phase I):
  - Created single group profile page shell (`[groupId].vue`) with URL hash tab persist, cover gradient, right sidebar quick stats, and admins list.
  - Implemented backend gap endpoints `posts()` and `stats()` returning ActivityResource compatible paginated results.
  - Extended Pinia/Vue composable helpers (`listGroupPosts` / `getGroupStats`) and wired main academy page card click to navigate.
  - Integrated `CreatePostBox` / `CreatePostModal` supporting `postedAsGroupId` parameter for post-as-group flow.
  - Verified syntax, PHPUnit group tests, and TypeScript compiler check (all compiled successfully).
- **Done:** Academy Student Self Profile & Student Card recovery (Work Plan v2 — Phase 1–3, 5, 7 + Phase 2 codemod follow-up):
  - **Defensive Relation Guard (Phase 1):** Added column check inside `Student::studentCard()` relation and try-catch guard in `ClassroomController::getMyStudentCard` to prevent SQL/Eloquent 500 errors on missing schema columns.
  - **Double-Encoding Fix (Phase 2):** Initial commit fixed `AcademyActionGuide.vue`. Follow-up codemod (`d4a53625`) removed `encodeURIComponent(academyName)` from **59 files / 62 occurrences** across pages, layouts, and `StudentCardWidget`. ofetch handles UTF-8 path encoding automatically — manual wrapper was producing `%25`-prefixed double-encoded URLs that broke Laravel route-model binding on Thai academy names.
  - **DB Migration & Backfill (Phase 3):** Migrated `2026_06_18_013941_add_student_id_to_student_cards_table.php` and backfilled `student_id` for **1930/1930** student card records (100% matched, 0 orphans).
  - **SFC Vue Refactoring (Phase 5):** Split 7 inline-template components from `ProfileViewCards.vue` into standalone SFCs under `ui/components/learn/student/profile-cards/` to eliminate runtime compilation warnings.
  - **Smoke Test Verification (Phase 7):** Manual browser testing across 7 flows (parent route, my-profile, my-card, student dashboard, admin/courses with query string, sidebar NuxtLink, attendance check-in) — all passed. No `%25E0` in network traffic, no 500/404, console clean.
  - **Backend Tests:** PHPUnit suites `StudentCardLinkTest`, `StudentMasterPolicyTest` — 100% pass.

### Follow-ups (optional, not blocking)
- **Phase 4 (cleanup):** Remove `Schema::hasColumn` guard from `Student::studentCard()` after migration deployed to all environments for >1 sprint.
- **Backfill command hardening:** `StudentsBackfillCardLink` uses `->get()` instead of `chunkById` — fine for current 1930 rows but should be hardened before next backfill on a larger dataset.

## สถานะปัจจุบัน (2026-06-16)

### งานที่เพิ่งเสร็จสิ้น — Verified & Tested

- **Done:** Phaser classroom v5/v6.1 refinement (board depth + floor junction + teacher patrol safety + responsive patrol) (`dbcf903e`)
- **Done:** Phaser classroom renderer + grid zoning refinement (`907dedc0`)
- **Done:** Student self check-in + simulator UI (`03db0ee0`)
- **Done:** Earn white-screen — fixed in `5821d1d3` (NuxtLayout hoisted to app-level, Earn pages migrated to Teleport slots)
- **Done:** Topic Form Stale State Fix — already in history, no uncommitted diff
- **Done:** Topic Reading Progress + Anti-Cheat + Auto-Complete Lesson (`060ce9fe`)
- **Done:** Image Gallery Viewer + Marketplace Filters (`0997d945`)
- **Done:** Academy Admin Embedded Marketplace Purchase (`d3959560` + `8ebedcf6`)

### งานที่ค้าง

- **Phaser remaining phases (out of v5/v6.1 plan):**
  - Phase N — TypeScript `PolygonPoint` → `Vector2Like` cleanup (pre-existing error, ~10 min)
  - Phase M — Full-name tooltip on seat hover
  - Phase L — Body anatomy upgrade (shoulders, larger torso)
  - Phase O — Tablet teacher patrol variation (currently desktop-only inspect)
  - Phase T2 — Replace nested onComplete chain with `tweens.chain()` builder (largest commit)
- **Smoke test:** Earn pages on real browser (3 viewports) — fix likely correct but never verified live

---

## งานที่เสร็จแล้ว (สรุปรวม)

| วันที่ | งาน | สถานะ |
|--------|------|-------|
| 2026-06-21 | Phase 10 — Compatibility Inventory & Closure Documentation | ✅ Done |
| 2026-06-21 | Phase N — Polish + A11y + Mobile UX (Skeletons, EmptyState, Drawer, Swipe, FocusTrap, Keyboard Nav) | ✅ Done |
| 2026-06-20 | Phase I — Academy group profile page (Cover + Tabs + Gating + Composer) | ✅ Done |
| 2026-06-16 | Phaser classroom v5/v6.1 refinement (board + floor + patrol safety + responsive) | ✅ Done |
| 2026-06-13 | Phaser classroom renderer + grid zoning + self check-in | ✅ Done |
| 2026-06-11 | Topic Reading Progress + Anti-Cheat + Auto-Complete Lesson | ✅ Done |
| 2026-06-11 | Image Gallery Viewer + Marketplace Filters | ✅ Done |
| 2026-06-11 | Academy Admin Embedded Marketplace Purchase | ✅ Done |
| 2026-06-11 | Admin Support Donate Fix + Topic Form Stale State Fix | ✅ Done |
| 2026-06-11 | Analysis File Consolidation | ✅ Done |
| 2026-06-10 | Draft Visibility & Interaction Lockdown (Lesson/Assignment/Quiz) | ✅ Done |
| 2026-06-09 | Sort Order System (Topics, Course Groups, Academy Groups) | ✅ Done |
| 2026-06-09 | Academy Group Reorder UI Implementation | ✅ Done |
| 2026-06-08 | Lesson Completion Requirement (บังคับอ่านก่อนทำแบบฝึกหัด) | ✅ Done |
| 2026-06-08 | Course Member Removal/Leave Workflow v2 | ✅ Done |
| 2026-06-07 | Eligibility Roster Filtering + Backlog Cleanup | ✅ Done |
| 2026-06-06 | Course Completion Workflow v2 | ✅ Done |
| 2026-06-06 | User Management & Username Integration | ✅ Done |
| 2026-06-03 | School Department Management (Codex Tasks) | ✅ Done |
| 2026-05-31 | Universal QR Scanner | ✅ Done |
| 2026-05-31 | School Management System Phase 6 | ✅ Done |
| 2026-05-29 | User Profile Fixes (6 Phases + Testing) | ✅ Done |
| 2026-05-29 | Exam Retake Flow Phase 2 | ✅ Done |
| 2026-05-25 | Typing Game Expansion + Course Point System | ✅ Done |
| 2026-05-25 | Lesson Access System (free/points/money) | ✅ Done |
| 2026-05-25 | Lesson Order Gap Fix + display_order | ✅ Done |
| 2026-05-25 | Exam Retake Flow Phase 1 | ✅ Done |
| 2026-05-24 | Lesson Drag-and-Drop Reordering | ✅ Done |
| 2026-05-24 | Remediation & Unified Eligibility | ✅ Done |

## 2026-06-21 - Phase 6 reports sync continuation

- Branch: current working tree (uncommitted)
- Task: continue Phase 6 from `.agents/latest-analysis.md`, focusing on Phase 6.D classroom inactive tab polish and documentation sync
- Files touched:
  - `.agents/latest-analysis.md`
  - `ui/pages/academies/[name]/admin/gradebook/classrooms/[id].vue`
  - backend files from Phase 6.A-6.C remain in-progress in the same working tree:
    - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/ClassroomController.php`
    - `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyMemberController.php`
    - `api/nuxnanravel/routes/learn/academy.php`
    - `api/nuxnanravel/tests/Feature/Api/Academy/ClassroomEnrollmentListTest.php`
    - `api/nuxnanravel/tests/Feature/Api/Academy/AcademyMemberFilterOptionsTest.php`
    - `api/nuxnanravel/tests/Feature/Api/Academy/SchoolAttendanceRosterScopeTest.php`
- Done:
  - completed the Phase 6.D frontend wiring to use the new enrollments endpoint for active and inactive rows
  - added lazy inactive-tab loading, read-only inactive presentation, tab-aware search placeholder, tab-aware empty state, and header summary count tied to the selected tab
  - hid add/action/delete affordances while viewing the inactive tab
  - recorded Phase 6.D completion details in `.agents/latest-analysis.md`
- Verification run:
  - `api/nuxnanravel`: reran `vendor\bin\pint` on touched Phase 6 backend files and new tests
  - `api/nuxnanravel`: reran `php artisan test tests/Feature/Api/Academy/ClassroomEnrollmentListTest.php tests/Feature/Api/Academy/AcademyMemberFilterOptionsTest.php tests/Feature/Api/Academy/SchoolAttendanceRosterScopeTest.php`
  - `api/nuxnanravel`: result `11 passed (41 assertions)`
  - `ui`: Vue SFC parse check passed via `@vue/compiler-sfc` on `pages/academies/[name]/admin/gradebook/classrooms/[id].vue`
  - `ui`: `cmd /c npx vue-tsc --noEmit --pretty false` did not complete because the workspace currently has a dependency/plugin issue: `vue-router/volar/sfc-route-blocks` export resolution failure
- Remaining:
  - manual browser smoke for the inactive tab was not run in this pass
- Risks / notes:
  - frontend behavior depends on the new backend enrollments endpoint from Phase 6.A
  - the inactive tab intentionally limits the client list to 200 rows for now
  - `vue-tsc` is not a reliable gate until the workspace plugin/export mismatch is fixed

## 2026-06-21 - Phase 8.1 Apply Auditable + Tests

- Branch: current working tree (uncommitted)
- Task: Apply Auditable trait to ClassroomStudent and RolloverBatch models and write feature tests (Phase 8.1 / 8.A)
- Files touched:
  - `api/nuxnanravel/app/Models/ClassroomStudent.php`
  - `api/nuxnanravel/app/Models/RolloverBatch.php`
  - `api/nuxnanravel/app/Traits/Auditable.php`
  - `api/nuxnanravel/app/Services/AuditLogService.php`
  - `api/nuxnanravel/tests/Feature/EnrollmentAuditTest.php`
- Done:
  - Equipped `ClassroomStudent` and `RolloverBatch` models with `Auditable` trait to enable automated audit logs on creation, modification, and deletion.
  - Set module name for both models to `'enrollment'` via `getAuditModule()` override and registered dynamic module detection check in `AuditLogService`.
  - Configured `getAuditHiddenAttributes()` on `RolloverBatch` to exclude the huge `plan_summary` data from the audit logs, resolving the issue of excessive audit log bloat.
  - Formatted files using Pint.
- Verification run:
  - Reran Pint on all modified files (passed, fixes applied).
  - Created and ran `tests/Feature/EnrollmentAuditTest.php` containing 4 tests and 21 assertions (100% pass).
  - Ran regression tests for `EnrollmentNotificationListenerTest`, `StudentEnrollmentServiceTest`, and `AcademicYearRolloverServiceTest` (100% pass, 31 tests, 111 assertions).

## 2026-06-21 - Phase 9.1 Repair Dirty Data

- Branch: current working tree (uncommitted)
- Task: Create `enrollment:repair-dirty-data` artisan command to repair dirty enrollment data and check manual review cases (Phase 9.1 / 9.A)
- Files touched:
  - `api/nuxnanravel/app/Console/Commands/EnrollmentRepairDirtyData.php`
  - `api/nuxnanravel/tests/Feature/EnrollmentRepairDirtyDataTest.php`
- Done:
  - Created `EnrollmentRepairDirtyData` console command supporting `--dry-run` and `--academy=` filters.
  - Implemented logic for three invariants:
    1. Duplicate active `classroom_students` rows: keeps newest active, marks older ones as `superseded`.
    2. Student class drift: re-syncs `students.class_level` / `class_section` from active enrollment classroom. Does NOT clear snapshot if there is no active enrollment.
    3. Duplicate current academic info: keeps latest `student_academic_info` row (sorting by `academic_year` desc, then `updated_at` desc, then `id` desc) and marks others as `is_current = false`.
  - Implemented manual review detections (no auto-fix, counted in `manual_review_rows`):
    1. Active enrollment with null `academic_year_id`.
    2. Active enrollment with deleted classroom or classroom not matching academy.
    3. `student_academic_info` (current) mismatched with active enrollment classroom/year.
  - Formatted files using Laravel Pint.
- Verification run:
  - Ran Pint on all modified files (passed, fixes applied).
  - Created and ran `tests/Feature/EnrollmentRepairDirtyDataTest.php` containing 6 tests and 30 assertions (100% pass).

## 2026-06-21 - Phase 9.2-9.4 Backfill, Execution, and Report

- Branch: current working tree (uncommitted)
- Task: implement `enrollment:backfill-academic-info`, execute Phase 9.3 dry-run/real-run flow, and record Phase 9.4 repair report
- Files touched:
  - `.agents/latest-analysis.md`
  - `.agents/backups/2026-06-21/repair-report.md`
  - `api/nuxnanravel/app/Console/Commands/EnrollmentBackfillAcademicInfo.php`
  - `api/nuxnanravel/app/Console/Commands/EnrollmentRepairDirtyData.php`
  - `api/nuxnanravel/tests/Feature/EnrollmentBackfillAcademicInfoCommandTest.php`
  - `api/nuxnanravel/tests/Feature/EnrollmentRepairDirtyDataTest.php`
- Done:
  - Added `EnrollmentBackfillAcademicInfo` artisan command with `--year=`, `--academy=`, and `--dry-run`.
  - Implemented enrollment-backed backfill for missing `student_academic_info` rows and safe patching for existing rows with null `academic_year`.
  - Updated `EnrollmentRepairDirtyData` to normalize `students.class_level` through `StudentEnrollmentService::normalizeGradeLevel()` before drift repair, preventing a large snapshot regression on Thai grade labels.
  - Added regression coverage for the normalized snapshot behavior and new backfill command behavior.
  - Executed Phase 9.3 flow:
    - dry-run `enrollment:repair-dirty-data`
    - dry-run `enrollment:backfill-academic-info`
    - real run `enrollment:backfill-academic-info`
    - real run `enrollment:repair-dirty-data`
    - post-run dry-run verification for both commands
  - Wrote Phase 9.4 count/report summary to `.agents/backups/2026-06-21/repair-report.md`.
- Verification run:
  - `api/nuxnanravel`: `vendor\bin\pint app\Console\Commands\EnrollmentRepairDirtyData.php tests\Feature\EnrollmentRepairDirtyDataTest.php`
  - `api/nuxnanravel`: `vendor\bin\pint app\Console\Commands\EnrollmentBackfillAcademicInfo.php tests\Feature\EnrollmentBackfillAcademicInfoCommandTest.php`
  - `api/nuxnanravel`: `php artisan test tests\Feature\EnrollmentRepairDirtyDataTest.php tests\Feature\EnrollmentBackfillAcademicInfoCommandTest.php`
    - Result: `12 passed (45 assertions)`
  - `api/nuxnanravel`: post-run `php artisan enrollment:repair-dirty-data --dry-run`
    - Result: all counters `0`
  - `api/nuxnanravel`: post-run `php artisan enrollment:backfill-academic-info --dry-run`
    - Result: `processed=1929`, `patched_null_year=0`, `skipped_existing=1929`
- Residual risks / notes:
  - DB inspection after the run still shows `student_academic_info.academic_year IS NULL = 491` rows and all 491 rows have `is_current = true`.
  - Those 491 rows are not backed by active `classroom_students` records and also have no usable classroom/year snapshot to infer safely, so the current Phase 9.2 command intentionally leaves them untouched.
  - Enrollment-backed repair/backfill is clean; remaining null-year rows need a separate legacy/orphan cleanup strategy if the team wants full normalization of `student_academic_info`.

## 2026-06-21 - Phase 10 Compatibility Inventory & Closure Documentation

- Branch: current working tree (uncommitted)
- Task: Complete Phase 10: build a comprehensive compatibility field inventory and write closure documentation
- Files touched:
  - [enrollment-rollover-repair.md](file:///C:/wamp64/www/nuxnan/.agents/enrollment-rollover-repair.md)
  - [worklog.md](file:///C:/wamp64/www/nuxnan/.agents/worklog.md)
  - [latest-analysis.md](file:///C:/wamp64/www/nuxnan/.agents/latest-analysis.md)
- Done:
  - Documented nuxnan student enrollment source of truth architecture, highlighting `classroom_students` as the absolute source of truth.
  - Performed a detailed reads/writes inventory on the compatibility fields `students.class_level` and `students.class_section` across the codebase.
  - Categorized usages into `Keep` (sync hooks, rollover rollback snapshots, pending intake checks, profile display, and front-end rendering) and `Defer` (member list query filtering and sorting, and student card distinct lists).
  - Summarized the Phase 9 Artisan command sequence and outcomes.
  - Detailed the remaining 491 null-year `student_academic_info` orphan records as intentionally bypassed due to lack of active enrollment data to infer from.
- Verification:
  - Documentation matches the results of the grep searches and Phase 9 log inspection.

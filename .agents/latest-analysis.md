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

## Current Snapshot

- Date: 2026-06-12
- Branch: main
- Active Work: (none — all queued items completed)

## Known Blockers
...
### ✅ Username/Name Refactor — Thai Support + Single Field (2026-06-12, current)
- Centralized `USERNAME_REGEX` and `usernameRules()` in `User` model.
- Supports Thai characters and single spaces in `username`.
- Registration and Admin Create now use a single name field that populates both `name` and `username`.
- Profile links in `CourseFeedPost.vue` switched to `reference_code` (URL-safe).
- Multi-field login updated to use `username` (unique) instead of `name` (non-unique).
- Backend tests (`RegisterTest`, `MultiFieldLoginTest`) updated and passing.

- ⚠️ Production: ตรวจสอบว่า `topics.sort_order` migration ถูกรันแล้ว (ถ้ายังไม่ได้รัน → `php artisan migrate --force`)
- ⚠️ Production: ตรวจสอบว่า migration `2026_06_10_074830` (approved_by → unsignedBigInteger) รันแล้ว
- ⚠️ Production: `php artisan route:clear && php artisan route:cache` หลัง deploy เพื่อให้ route ใหม่ทำงานได้

## Active Work

_(none — all queued items completed)_

## Work Plan

### Queued: Earn support/points white-screen route regression

**สถานะ**: แผนเท่านั้น — ต้อง browser smoke test ก่อน implement
**Note**: Commit `5821d1d3` addressed layout/Teleport migration for game/earn pages; this may already be resolved.

**Planned diagnosis steps:**
- Reproduce with browser console on: `/Earn/Points`, `/earn/donates`, `/earn/donates/create`, `/earn/advertise`
- Check route casing consistency (mixed-case links across app)
- Verify Earn pages call `usePageLayoutWidgets({ left: false, right: false })` for clean route transitions

**Planned fixes (if issue persists):**
- Normalize internal links for Earn routes in layouts and nav
- Add `usePageLayoutWidgets()` to Earn pages that use `layout: 'main'` but have no side widgets
- Ensure API errors show loading/error states instead of blank pages

**Verification:**
- Browser smoke route sequence: Dashboard → Earn Points → Support list → Support create → Advertise → back to Points
- Confirm no white screen, no uncaught console errors, no stale widget panels

---

## Completed Features (consolidated)

### ✅ Topic Reading Progress — Persist + Anti-Cheat + Auto-Complete Lesson (2026-06-11, `060ce9fe`)
- `topic_read_progress` table with server-side anti-cheat time validation and `required_seconds_snapshot`
- `LessonCompletionService` extracting shared completion logic (rewards, gamification, attendance unlock)
- `TopicReadProgressController` with idempotent start/complete/summary endpoints, admin bypass
- `useTopicReadProgress.ts` composable, `LessonPost.vue` + `TopicAccordion.vue` integration
- Bug fix: Carbon `diffInSeconds` signed float → timestamp subtraction with `max(0, ...)`
- Same bug fixed in `UserAnswerQuestionController` lines 317 and 349
- 9/9 feature tests + 2/2 refactor tests passing

### ✅ Topic Form Stale State Fix (2026-06-11, uncommitted)
- `TopicFormModal.vue`: `watch(() => props.show)` now calls `resetForm()` when opening in create mode (`!props.topic`)
- Prevents form fields persisting from previous create when creating consecutive topics

### ✅ Image Gallery Viewer + Course Marketplace Filters (2026-06-11, `0997d945`)
- Reused existing `ImageGalleryModal.vue` for lesson/topic image preview
- Wired `LessonPost.vue`, `TopicAccordion.vue`, `LessonImagesViewer.vue`
- Enhanced course marketplace query hydration

### ✅ Academy Admin Courses — Embedded Marketplace Purchase (`d3959560` + `8ebedcf6`)
- Backend: academy-scoped purchase (`academy_id` on `course_purchases`), `CourseCloneService`/`CoursePurchaseService` updated
- Frontend: 2-tab admin page (academy/marketplace), in-page purchase flow
- 18/18 backend tests passing

### ✅ Draft Visibility & Interaction Lockdown (`797f3a1f`)
- `ContentVisibilityService` for hiding and blocking draft lessons/assignments/quizzes from students

### ✅ Admin Support Donate — Duplicate PATCH Fix + Error Handling (`060ce9fe`)
- `ApproveDonateCard.vue` → presentational (emits only, no API calls)
- `index.vue` → single API call point with `processingId` loading state
- Error messages show `err.data?.message`

### ✅ Topic Image Deletion (`952d16ce`)
- Backend: ownership check, admin auth, `CourseMediaService::deleteIfUnused()`
- Frontend: real API delete + local state sync, memory leak fix
- `TopicImageDeleteTest.php` — 4/4 tests passing

### ✅ Topic Create/Update Flow (`605fe7ce`)
- Validation aligned with DB schema, transaction wrap, atomic min_read adjustment
- `TopicCreateUpdateTest.php` — 8 tests

### ✅ Sort Order System (`622b6b34` + follow-ups)
- Topics, Course Groups, Academy Groups — migrations, controllers, UI widgets
- 23/23 tests passed

### ✅ Layout/Widget Stabilization (`5821d1d3`)
- `usePageLayoutWidgets()` composable for single-owner widget state management
- Teleport migration for game/earn pages

---

## Coordination Board

_(clear — no active multi-agent work)_

## Decisions And Assumptions

- Role `STUDENT` (not `USER`) is the default general-user role.
- `username` = ชื่อ-สกุลแสดงผล (ไทย+เว้นวรรคได้), **unique + NOT NULL**; `name` ไม่ unique. ดีไซน์ใหม่ (2026-06-12): สมัคร/แอดมิน create ป้อนช่องเดียวบันทึก name+username, แก้ name ทีหลังได้, login/uniqueness ผูกที่ `username`. กติกากลางอยู่ที่ `User::usernameRules()`/`User::normalizeUsername()`. (เดิม: "username เป็น primary identifier + บังคับ ASCII alpha_dash" — ยกเลิกแล้ว)
- Score schema uses `*_percentage` columns; legacy `*_total_score` consumers mapped through `CourseMemberResource`.
- Avatar is an Eloquent accessor (not a DB column) — never include it in `select(...)` eager loads.
- Notification copy must avoid 4-byte emoji while DB collation is `utf8mb3_unicode_ci`.
- Topic visibility inherits from parent Lesson `publication_status` (no own draft flag in v1).
- Bought Master Copy is **school-owned** (`academy_id` = academy), not buyer-owned.

## Open Questions

- Refund policy for self-leave on paid courses: no refund vs grace-period refund? (v1 default: no refund for self-leave, always refund for admin-remove)
- Assignment answers with pending review: delete immediately or require admin acknowledgement?
- Finalized members with certificates: block removal or allow but retain certificate records?
- Rate limiting for self-leave + re-join to prevent refund exploitation?

---

## Analysis Timeline

_(consolidated 2026-06-11 — all prior entries merged into Completed Features above)_

### 2026-06-12 - Username/name refactor — analysis + plan (plan-only)
- Trigger: admin `users/create` บังคับ username เป็น ASCII (alpha_dash) แต่สมัครเองไม่บังคับ → ไม่สอดคล้อง
- ตรวจโค้ดจริงพบ: `username` unique+NOT NULL (migration `2026_06_05_180308`); identifier จริงคือ `name`+codes; profile resolver/login/mention ใช้ `name` ไม่ใช่ `username`; register ยัด name→username ไม่เช็ค unique = bug ชื่อซ้ำพัง 500; alpha_dash กระจาย 4 จุด
- ดีไซน์ที่เจ้าของยืนยัน: ป้อนช่องเดียว (ไทย+เว้นวรรคได้) → name+username, unique ที่ username, แก้ name ทีหลังได้, แอดมิน create ช่องเดียว
- แผนเต็ม 10 ขั้น (+1 optional) อยู่ใน Work Plan → "Username = Thai display name (single field, unique)". เจ้าของโปรเจค implement เอง
- ยังไม่แตะโค้ด feature; แก้เฉพาะไฟล์ `.agents/latest-analysis.md` นี้

### 2026-06-11 - Consolidated analysis file
- Audited all queued items against git history; found all previously queued work items are committed.
- Remaining: Earn white-screen route regression (plan only, needs browser test — may already be resolved by `5821d1d3`).
- Fixed TopicFormModal stale create state (watch on `props.show` now resets form in create mode).
- Cleaned up stale/duplicate entries, removed detailed implementation plans for completed work.

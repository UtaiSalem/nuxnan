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

### Queued: Gradebook score-breakdown resync 404 บน production (deploy drift)

**สถานะ**: วินิจฉัยเสร็จ — เป็นปัญหา **deploy / route cache บน production** ไม่ใช่ bug ใน source ปัจจุบัน. ไม่มีโค้ด feature ต้องแก้ (มีเฉพาะ optional frontend hardening). ขั้นตอนแก้ทั้งหมดรันบนเซิร์ฟเวอร์จริงโดยเจ้าของ — ปรับ path/วิธี deploy ตามจริงของ server

**ข้อเท็จจริงยืนยันแล้ว (source/local):**
- `GET /api/courses/{course}/score-breakdown` + `POST /api/courses/{course}/score-breakdown/resync` อยู่ใน [`routes/learn/course.php:447-450`](api/nuxnanravel/routes/learn/course.php:447) ทั้งคู่ ถูกเพิ่ม **commit เดียวกัน** `749f89d7` (2026-06-06)
- Controller `CourseScoreBreakdownController@resync` มีจริง (เพิ่มใน `749f89d7`, แก้ล่าสุด `69471536` 2026-06-07), มี permission check `edit_grades` → 403 ถ้าไม่มีสิทธิ์
- `HEAD = origin/main` (unpushed = 0) → source ที่มี route นี้ถูก push ขึ้น remote แล้วแน่นอน; working tree clean
- `route:list --path=score-breakdown` บนเครื่อง dev เห็นครบ 2 routes

**สาเหตุที่เป็นไปได้ (เรียงตามโอกาส):**
1. Production รัน revision **เก่ากว่า `749f89d7`** (ก่อน 2026-06-06) → ทั้ง GET และ POST ไม่มีในตาราง route
2. Production มี **route cache ค้าง** (`bootstrap/cache/routes-*.php`) ที่ build ก่อน deploy commit นี้ → route ใหม่ไม่ถูกโหลดจนกว่าจะ `route:clear`/re-cache
- หมายเหตุสำคัญ: GET กับ POST อยู่ commit เดียว + group เดียว → ถ้า prod "404 เฉพาะ POST แต่ GET ใช้ได้จริง" แทบเป็นไปไม่ได้จาก source (Laravel จะตอบ *MethodNotAllowed* ไม่ใช่ *route could not be found* ถ้าเป็นเรื่อง method). ดังนั้น error นี้แปลว่า router บน prod **ไม่มี block นี้ทั้งก้อน** → ต้องเช็คว่า GET บน prod ก็ fail ด้วยไหม (step 5) เพื่อยืนยัน

**ขั้นที่ 1 — วินิจฉัยบน production (read-only ก่อน ห้ามแก้):**
รันใน API root ของ prod (`/path/to/api/nuxnanravel`):
```bash
git rev-parse HEAD && git log -1 --format='%h %ci %s'   # เทียบ HEAD ต้อง >= 749f89d7 (2026-06-06)
git merge-base --is-ancestor 749f89d7 HEAD && echo "HAS route commit" || echo "MISSING route commit"
php artisan route:list --path=score-breakdown            # คาดหวัง 2 แถว (GET + POST resync)
ls -la bootstrap/cache/routes-*.php 2>/dev/null && echo "route cache EXISTS" || echo "no route cache"
```
**ตัดสินใจ:**
- ถ้า "MISSING route commit" → สาเหตุ = revision เก่า → ไปขั้นที่ 2A
- ถ้ามี commit แล้วแต่ `route:list` ไม่เห็น 2 routes → สาเหตุ = route cache ค้าง → ไปขั้นที่ 2B
- ถ้า `route:list` เห็นครบทั้งที่ prod ยัง 404 → ไม่ใช่ deploy drift → ไปขั้นที่ 4 (สอบเพิ่ม)

**ขั้นที่ 2A — sync revision (กรณี revision เก่า):**
```bash
git fetch origin && git checkout main && git pull --ff-only origin main   # หรือ checkout SHA ที่ตั้งใจ deploy
composer install --no-dev --optimize-autoloader
```
แล้วทำต่อขั้นที่ 2B (clear+rebuild cache)

**ขั้นที่ 2B — clear + rebuild cache (ทำเสมอหลัง deploy):**
```bash
php artisan optimize:clear        # = route:clear + config:clear + cache:clear + view:clear
php artisan migrate --force        # ถ้ามี migration ค้าง (ดู Known Blockers: topics.sort_order, approved_by)
php artisan optimize               # rebuild route + config cache สำหรับ production
```
> ⚠️ การ `route:cache` **ต้องรันหลัง** code/route เป็นเวอร์ชันล่าสุดแล้วเท่านั้น มิฉะนั้น cache จะค้างซ้ำ
> ⚠️ ถ้าใช้ PHP-FPM / Octane / queue worker ต้อง **restart** หลัง deploy: `sudo systemctl reload php8.4-fpm` / `php artisan octane:reload` / `php artisan queue:restart`

**ขั้นที่ 3 — verify บน production:**
```bash
php artisan route:list --path=score-breakdown   # ต้องเห็น GET + POST resync
# curl ทดสอบจริง (ใส่ JWT ของ admin ที่มีสิทธิ์ edit_grades):
curl -i -X POST https://www.nuxnan.com/api/courses/21/score-breakdown/resync \
  -H "Authorization: Bearer <JWT>" -H "Accept: application/json"
# คาดหวัง 200 {success:true} หรือ 403 (ถ้าไม่มีสิทธิ์) — แต่ต้อง "ไม่ใช่ 404"
```
จากนั้นเปิด `https://www.nuxnan.com/Learn/Courses/21/gradebook` กดปุ่ม "รีซิงค์คะแนนทั้งหมด" → ต้องได้ toast สำเร็จ

**ขั้นที่ 4 — ถ้ายัง 404 ทั้งที่ route:list เห็นครบ (สอบเพิ่ม):**
- ตรวจ web server / reverse proxy (Nginx/Apache) ว่า `POST /api/*` ไม่ถูก block/redirect (เทียบกับ GET ที่ผ่าน)
- ตรวจ base URL ฝั่ง frontend prod: `useApi()`/`runtimeConfig.apiBase` ชี้ไป origin เดียวกับ API ที่ deploy ใหม่หรือไม่ (อาจชี้ไป API คนละ host/เวอร์ชัน)
- ตรวจ CDN/edge cache ของ response 404 เก่า (purge ถ้ามี)

**ขั้นที่ 5 (optional) — frontend hardening ✅ (implemented + verified 2026-06-12):**
- ไฟล์ [`ResyncButton.vue:21-23`](ui/components/learn/course/gradebook/ResyncButton.vue:21): เพิ่มการจับ 404 โดยเฉพาะ (`error?.status === 404`) ให้ขึ้นข้อความเข้าใจง่าย *"ฟีเจอร์รีซิงค์ยังไม่พร้อมใช้งานบนเซิร์ฟเวอร์ กรุณาติดต่อผู้ดูแลระบบเพื่ออัปเดต Route Cache"* แทนข้อความ error ดิบ
- หน้า gradebook [`index.vue`](ui/pages/Learn/Courses/[id]/gradebook/index.vue): GET score-breakdown fail แทนที่จะ `console.error` เงียบ ๆ ตอนนี้แสดง toast error ให้ผู้ใช้รู้ตัว (`swal.error`)

**ป้องกันไม่ให้เกิดซ้ำ (systemic):**
- ใส่ `php artisan optimize:clear && php artisan migrate --force && php artisan optimize` เป็น **ขั้นมาตรฐานท้าย deploy script/hook** ทุกครั้ง (ดู Known Blockers ที่เตือนเรื่องนี้อยู่แล้ว)

**หลังทำเสร็จ (production — ต้องรันบนเซิร์ฟเวอร์จริง):**
- [ ] `route:list --path=score-breakdown` บน prod เห็น 2 routes
- [ ] curl POST resync ได้ ≠ 404
- [ ] ปุ่มบนหน้า gradebook ทำงาน (toast สำเร็จ)

---

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

### 2026-06-12 - External scores group targeting analysis (plan-only)
- Trigger: review `/Learn/Courses/{id}/external-scores` against requirement that new external score topics must be targetable to a specific course group.
- Frontend `ui/pages/Learn/Courses/[id]/external-scores.vue` uses `selectedGroupId` only for table filtering; create modal/form has no `group_id` field and explicitly tells admins scores will apply to every student in the course.
- Backend `CourseExternalScoreController@store` validates only `title`, `description`, `category`, `max_score`, `scored_at` and hard-codes `group_id => null`, so newly created topics cannot be scoped to a group even though the DB/model support nullable `group_id`.
- `tableView($groupId)` filters only visible member rows; it still loads all external score columns for the course. Current group selection is therefore a view filter, not topic scoping.
- Risk: admins can filter to one group and save entries for visible rows only, but the topic itself remains course-wide and `CourseScoreService` still totals external scores at course level without using `group_id`.
- Verification: read-only trace through page -> route -> controller -> model -> migration -> scoring service. No feature code changed.

### 2026-06-12 - Gradebook resync route analysis (plan-only)
- Trigger: production page `/Learn/Courses/{id}/gradebook` shows `The route api/courses/21/score-breakdown/resync could not be found.` when clicking "Resync ทั้งคอร์ส".
- Frontend button `ui/components/learn/course/gradebook/ResyncButton.vue` posts to `/api/courses/${courseId}/score-breakdown/resync`; gradebook page uses the same score-breakdown family for the table data.
- Backend source already defines both `GET /api/courses/{course}/score-breakdown` and `POST /api/courses/{course}/score-breakdown/resync` in `api/nuxnanravel/routes/learn/course.php`, backed by `CourseScoreBreakdownController@index` and `resync`.
- Local verification via `php artisan route:list --path=score-breakdown` shows both routes are registered in the current codebase/environment, so the production error is likely deploy drift or stale route cache on the live server rather than a missing route in source.
- Most likely production fix path: confirm deployed revision includes score-breakdown routes, then run `php artisan route:clear` and `php artisan route:cache` after deploy; if production still fails, compare the live branch/build against current source.
- Refinement (2026-06-12, Claude): GET index + POST resync ถูกเพิ่ม **commit เดียวกัน** `749f89d7` (2026-06-06) และมีแค่ commit เดียวที่แตะ block นี้; `HEAD = origin/main` (unpushed 0). ดังนั้น prod ที่ตอบ "route could not be found" = router บน prod ไม่มี block นี้ทั้งก้อน (revision เก่า หรือ route cache ค้าง) ไม่ใช่ method mismatch. แผนแก้ละเอียดทีละขั้น (วินิจฉัย→sync→cache→verify→hardening→ป้องกันซ้ำ) เพิ่มไว้ใน Work Plan แล้ว.

### 2026-06-11 - Consolidated analysis file
- Audited all queued items against git history; found all previously queued work items are committed.
- Remaining: Earn white-screen route regression (plan only, needs browser test — may already be resolved by `5821d1d3`).
- Fixed TopicFormModal stale create state (watch on `props.show` now resets form in create mode).
- Cleaned up stale/duplicate entries, removed detailed implementation plans for completed work.
### 2026-06-15 - Course assignments delete UX + 500 analysis (plan-only)
- Trigger: `/Learn/Courses/24/assignments` uses a plain browser delete confirmation and delete requests fail with `500` on `DELETE /api/courses/24/assignments/31`.
- Frontend trace:
  - [`ui/pages/Learn/Courses/[id]/assignments/index.vue`](ui/pages/Learn/Courses/[id]/assignments/index.vue) fetches course assignments and renders [`ui/components/learn/course/AssignmentsList.vue`](ui/components/learn/course/AssignmentsList.vue).
  - [`AssignmentsList.vue`](ui/components/learn/course/AssignmentsList.vue) still uses `window.confirm()` and `alert()` for destructive actions, while lesson-level assignment delete in [`ui/components/learn/course/lesson/LessonInteractionTabs.vue`](ui/components/learn/course/lesson/LessonInteractionTabs.vue) already uses `useSweetAlert()`.
  - [`ui/composables/useSweetAlert.ts`](ui/composables/useSweetAlert.ts) already provides `confirmDelete()` and toast/error helpers, so the UI can be upgraded without introducing a new modal system.
- Backend trace:
  - The failing route comes from `Route::resource('/assignments', CourseAssignmentController::class)` under `/courses/{course}` in [`api/nuxnanravel/routes/learn/course.php`](api/nuxnanravel/routes/learn/course.php).
  - Local log on 2026-06-15 confirms the delete failure root cause: `CourseAssignmentController::destroy(): Argument #1 ($assignment) must be of type App\Models\Assignment, string given`.
  - Current signature in [`api/nuxnanravel/app/Http/Controllers/Api/Learn/Course/assignments/CourseAssignmentController.php`](api/nuxnanravel/app/Http/Controllers/Api/Learn/Course/assignments/CourseAssignmentController.php) is incompatible with the resource route because Laravel passes `{course}` before `{assignment}`.
- Plan direction:
  - Fix the controller method signature and ownership guard for course-level assignment deletion.
  - Replace native confirm/alert on the assignments page with `useSweetAlert().confirmDelete()` plus toast/error handling for a polished destructive flow.
  - Standardize copy and behavior between course-level and lesson-level assignment delete paths to avoid inconsistent UX.
- Verification plan:
  - Retry delete from `/Learn/Courses/{id}/assignments` and confirm the API returns success and the list refreshes.
  - Smoke-test lesson-level assignment delete to ensure no regression between the two delete entry points.
  - Check `laravel.log` to confirm the previous `TypeError` no longer appears.

### 2026-06-15 - Lesson rich text dark mode analysis (plan-only)
- Trigger: analyze dark-mode rendering issues for lesson content.
- Scope is frontend-only. Lesson and topic bodies are rendered in `ui/components/learn/course/lesson/LessonPost.vue` and `TopicAccordion.vue`, both using the root `ui/components/RichTextViewer.vue`.
- Current root `RichTextViewer.vue` renders `v-html` with only lightweight newline handling and no shared `useRichText().sanitizeHtml()` path. If pasted/stored HTML contains inline `style="color:..."`, those inline colors can override `dark:prose-invert` and keep text dark on dark backgrounds.
- There is a second `ui/components/Common/RichTextViewer.vue` with DOMPurify, richer media/table handling, and explicit dark-aware utilities; this creates inconsistent rich-text rendering paths.
- Plan direction: consolidate lesson/topic rendering onto the safer common viewer or port its sanitizer/style-normalization into the root viewer, then add dark-specific overrides for rich text elements and verify lesson/topic pages in dark mode.
- Verification plan: run frontend build/type check if practical, then browser smoke a course lesson with paragraphs, headings, links, lists, tables, code, images, and pasted colored text in light and dark modes.

### 2026-06-15 - Lesson quiz score visibility and summary analysis (plan-only)
- Trigger: student lesson-level quiz answers are not visible as saved scores in the lesson quiz UI and do not appear correctly in learning result summaries.
- Scope is lesson quizzes only, not course quizzes under `/courses/{course}/quizzes`.
- Frontend lesson quiz flow is `ui/components/learn/course/lesson/LessonInteractionTabs.vue` -> `LessonQuizSection.vue`, posting to `POST /api/lessons/{lesson}/questions/{question}/answer`.
- Backend endpoint `LessonAnswerQuestionController@store` writes to `lesson_answer_questions` with `points` and `is_correct`, then calls `CourseScoreService::recompute($courseMember)`, so per-answer persistence exists.
- UI issue: `LessonQuizSection.vue` intentionally does not restore persisted `question.user_answer` into `selectedAnswers`/`answerResults`, so returning students see progress only, not their previous answer/score/result state.
- Summary/report issue: several `CourseController` progress/export calculations still load lesson question scores from `UserAnswerQuestion` instead of `LessonAnswerQuestion`, while the canonical score service already uses `lesson_answer_questions`.
- Plan direction: restore persisted answer state in the lesson quiz UI, switch legacy progress/export summary queries to `LessonAnswerQuestion`, and keep `CourseScoreService` as the canonical score source for member totals.
- Verification plan: create/answer a lesson question as a student, reload the lesson and confirm score/result remains visible, then verify `/api/courses/{course}/progress`, `/api/courses/{course}/members/{member}/progress`, and score breakdown/learning result summary include lesson quiz points.

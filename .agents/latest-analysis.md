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

- Update: 2026-06-16
- Active Work Override: teacher progress page layout cleanup for `/Learn/Courses/{id}/progress` to free table width and prevent wrapped row numbers

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

- Owner: Codex (current thread)
- Scope: [`ui/components/learn/course/v2/CoursePageShell.vue`](ui/components/learn/course/v2/CoursePageShell.vue), [`ui/components/learn/course/ProgressList.vue`](ui/components/learn/course/ProgressList.vue)
- Status: implemented, pending in-browser verification blocked by unauthenticated redirect in the isolated in-app browser session

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

### 2026-06-15 - Course progress negative max-total analysis (plan-only)
- Trigger: teacher view at `/Learn/Courses/23/progress` shows `รวม` as `0 / -10` for students with no earned scores.
- Frontend trace: [`ui/components/learn/course/ProgressList.vue`](ui/components/learn/course/ProgressList.vue) renders `member.scores.total_score` and `member.scores.max_total` directly in the table (`lines 882-884`); there is no frontend math that could flip the denominator negative.
- Backend trace: [`CourseController::progress`](api/nuxnanravel/app/Http/Controllers/Api/Learn/Course/info/CourseController.php) sets `max_total` from `$totalScoreCap`, and `$totalScoreCap` is taken from `$course->total_score` whenever that column is non-null (`lines 1014-1021`, `1093-1111`), even if it is negative.
- The computed fallback from current structure only runs when `courses.total_score` is `null`, not when it is wrong. So a stored `courses.total_score = -10` will be returned as `max_total = -10` and displayed as `/-10`.
- Likely root cause is data drift in the persisted aggregate field `courses.total_score`, which is incremented/decremented in many controllers (assignment/lesson/question/quiz create-delete flows) while the canonical recompute helper lives in [`CourseScoreService::syncCourseTotalScore()`](api/nuxnanravel/app/Services/CourseScoreService.php). A double-decrement or stale aggregate can therefore survive and surface in progress screens.
- Verification performed: read-only trace from page -> component -> `GET /api/courses/{course}/progress` route -> controller math; no code changed beyond this analysis note.

### 2026-06-16 - Teacher progress lesson-score UX + reset — REFINED PLAN (Claude, plan-only)

**สรุปการ refine จากแผนเดิม:**
แผนเดิมแยกถูกต้องเป็น 3 ก้อน (UI ความชัด, รวม logic คะแนน, reset workflow) แต่ยังไม่ครอบ edge case ที่จะทำให้ผิดพลาดตอน implement จริง — เพิ่ม 6 จุดและตัดสินใจล่วงหน้าเพื่อ unblock การลงมือ

**ข้อค้นพบเพิ่มเติมจากการอ่านโค้ดจริง:**
1. **Filter ไม่ตรงระหว่าง service กับ progress controller**: [CourseScoreService::computeBreakdown](api/nuxnanravel/app/Services/CourseScoreService.php:104) นับ lesson assignment ที่ `status = graded OR points NOT NULL` ส่วน [CourseController::progress](api/nuxnanravel/app/Http/Controllers/Api/Learn/Course/info/CourseController.php:944) ใช้คนละเงื่อนไข → คะแนนตรง แต่ "ความหมายของการทำเสร็จ" ไม่ตรง กระทบ `missingSources` และการเปิด/ปิดสถานะ
2. **Lesson question max ใช้ `COALESCE(points, 1)`** ([CourseScoreService.php:62](api/nuxnanravel/app/Services/CourseScoreService.php:62)) — กำหนด semantic ของ reset ให้ชัด: เลือก "ลบ row" (max ไม่กระทบ, earned ลด) เป็น default; ห้ามใช้แบบ flip `is_correct=false` เพราะจะดูเหมือนนักเรียน "ตอบผิด" ไม่ใช่ "ยังไม่ตอบ"
3. **โหมด legacy gradebook** (`course.use_legacy_gradebook = true`, [CourseScoreService.php:182](api/nuxnanravel/app/Services/CourseScoreService.php:182)) — reset endpoint ทุกตัวต้อง guard: ถ้า course อยู่โหมด legacy → 422 พร้อมข้อความ "ใช้ Gradebook v2 เท่านั้น" (กันการเรียกผิด, ไม่ลด scope ของ feature)
4. **ไม่มี audit trail ของการ reset** — ตัดสิน: เพิ่ม table `lesson_score_resets` (id, course_member_id, lesson_id, scope: assignment|quiz|all, teacher_id, deleted_row_count, snapshot_json, created_at) ใน migration เดียว เพื่อให้กลับมาตรวจสอบ/rollback ได้ และเป็นหลักฐานเวลานักเรียนทักท้วง
5. **Race condition** — server ต้อง guard ฝั่ง submit: ถ้านักเรียนกด submit หลัง reset (rows เพิ่ม/ปรับ `updated_at` ของ `course_members.last_score_synced_at` เพื่อเป็น optimistic token) ให้รับได้ตามปกติ (เพราะ semantic = "ทำใหม่"); แต่ฝั่ง UI นักเรียนต้องรีโหลด state ทันทีเมื่อ websocket แจ้ง (ใช้ Reverb channel `course_member.{id}` ที่มีอยู่)
6. **Bulk reset** — รองรับ 2 scope ตั้งแต่ phase แรก: (a) member×lesson (รายคนรายบท), (b) lesson×all-members (ทั้งห้องในบทเดียว) เพราะ teacher use case จริง "เปิดสอนใหม่ทั้งห้อง" พบบ่อย; ห้ามทำ "course-wide reset" ใน phase นี้ (อันตรายเกิน, รอ explicit request)

**Phase 0 — Decision lock (ก่อนเขียนโค้ด):**
- [ ] Reset แบบทดสอบบทเรียน = **ลบ row ใน `lesson_answer_questions`** (ไม่ใช่ flip flag)
- [ ] Reset งานบทเรียน = แยก 2 ปุ่ม: **`รีเซ็ตผลตรวจ`** (เซ็ต `points=null, status='submitted', feedback=null`, เก็บ submission/ไฟล์) และ **`ล้างคำตอบเพื่อทำใหม่`** (ลบ row + ไฟล์แนบผ่าน `CourseMediaService::deleteIfUnused`)
- [ ] Permission key = **`edit_grades`** (มีอยู่แล้ว, ใช้ของเดิม)
- [ ] Audit table = **`lesson_score_resets`** (migration ใหม่)
- [ ] Legacy gradebook → reject 422
- [ ] Scope ที่รองรับ phase นี้ = (member×lesson) + (lesson×all-members) เท่านั้น

**Phase 1 — UI clarity (frontend-only, lowest risk):**
- [ ] [ProgressList.vue:855-870](ui/components/learn/course/ProgressList.vue:855): เปลี่ยน header "คะแนนบทเรียน" เป็น 2 sub-column "งานบทเรียน" + "ทดสอบบทเรียน" (ใช้ `colspan` หรือแยก `<th>` 2 ตัว)
- [ ] cell เปลี่ยนเป็น 2 บรรทัด/2 chip ใช้รูปแบบเดียวกับ member detail panel ที่ [ProgressList.vue:1192-1199](ui/components/learn/course/ProgressList.vue:1192) (`<div>งาน {earned}/{max}</div><div>ทดสอบ {earned}/{max}</div>`)
- [ ] ตรวจ export CSV ที่ [ProgressList.vue](ui/components/learn/course/ProgressList.vue): ถ้ามี column รวม → แยก 2 column เช่นเดียวกัน
- [ ] i18n keys ใหม่: `learn.progress.lesson_assignments_short`, `learn.progress.lesson_quizzes_short` (ทั้ง th/en)
- [ ] Verify: ดู progress page บนทั้ง 3 viewport (mobile/tablet/desktop) — Tailwind `lg:` breakpoint ของตารางที่มีอยู่จะรองรับได้

**Phase 2 — Consolidate lesson-score logic (backend refactor):**
- [ ] สร้าง method `CourseScoreService::buildMemberLessonBuckets(CourseMember $member): array` คืน shape:
  ```php
  ['lesson_assignments' => ['earned'=>x, 'max'=>y, 'completed_ids'=>[]],
   'lesson_quizzes'     => ['earned'=>x, 'max'=>y, 'answered_question_ids'=>[]]]
  ```
- [ ] แทนที่ inline query ใน [CourseController::progress](api/nuxnanravel/app/Http/Controllers/Api/Learn/Course/info/CourseController.php:944), `memberProgress`, `export` ด้วย method ใหม่
- [ ] ปรับ filter ของ service ให้ตรงกับ controller: **ใช้ `WHERE points IS NOT NULL`** เป็นเงื่อนไขเดียว (ไม่รวม `status=graded AND points=null`) — เพราะ "graded เป็น 0" ก็คือ "ตรวจแล้วได้ 0" ซึ่ง `points=0 NOT NULL` ครอบอยู่แล้ว
- [ ] อัพเดท [CourseScoreServiceTest.php](api/nuxnanravel/tests/Feature/CourseScoreServiceTest.php) เพิ่ม case:
  - [ ] หลาย lesson, หลาย assignment, หลาย question
  - [ ] เคส graded points=0 → ต้องนับเข้า earned=0 และเข้า completed_ids
  - [ ] เคสนักเรียนตอบผิดแล้วตอบใหม่ (replace row) → max ไม่เปลี่ยน, earned อัพเดท
  - [ ] เคสมี bonus + external + quiz รวม
- [ ] รัน `php artisan resync:course-total-score` (มีอยู่ที่ [ResyncCourseTotalScore.php](api/nuxnanravel/app/Console/Commands/ResyncCourseTotalScore.php)) เพื่อ verify ไม่กระทบ aggregate

**Phase 3 — Audit migration + reset APIs:**
- [ ] Migration `create_lesson_score_resets_table.php` (columns ตาม Phase 0)
- [ ] Endpoint 1: `POST /api/lessons/{lesson}/members/{courseMember}/reset-quiz`
  - guard: `auth:api` + permission `edit_grades` + check course ของ lesson ตรงกับของ member + reject ถ้า `use_legacy_gradebook`
  - action: `DB::transaction` → snapshot rows → `delete` จาก `lesson_answer_questions` (filter user_id + question ใน lesson) → insert audit → `CourseScoreService::recompute($member)` → broadcast `CourseMemberUpdated` event ที่มีอยู่
- [ ] Endpoint 2: `POST /api/lessons/{lesson}/members/{courseMember}/reset-assignment` body `{mode: 'grade_only'|'clear_submission', assignment_id?: int}`
  - ถ้า `assignment_id` ระบุ → reset เฉพาะตัวนั้น, ไม่ระบุ → reset ทุก lesson assignment ในบทนี้
  - `grade_only`: update rows
  - `clear_submission`: delete rows + ลบไฟล์ผ่าน `CourseMediaService::deleteIfUnused()`
- [ ] Endpoint 3 (bulk): `POST /api/lessons/{lesson}/reset-all-members` body `{scope: 'quiz'|'assignment_grade'|'assignment_clear'}` — loop chunk(50) เรียก endpoint 1/2 ภายใน
- [ ] Tests: 6 cases (รายคน quiz, รายคน assignment grade_only, รายคน assignment clear, bulk quiz, permission 403, legacy 422)

**Phase 4 — Frontend teacher UI:**
- [ ] เพิ่ม action menu ใน [ProgressList.vue](ui/components/learn/course/ProgressList.vue) member detail panel (มี panel อยู่แล้วที่ line 1192): ปุ่ม 3 ตัว `รีเซ็ตผลตรวจ`, `ล้างคำตอบเพื่อทำใหม่`, `รีเซ็ตแบบทดสอบ` แสดงต่อ "บทเรียน" ที่ list อยู่แล้ว
- [ ] เพิ่ม bulk action ที่ header ตารางบทเรียนใน member panel: `รีเซ็ตทั้งห้องในบทนี้`
- [ ] ทุกปุ่ม → `useSweetAlert().confirmDelete()` โดยใส่ข้อความผลกระทบชัด ("จะลบคำตอบของนักเรียน X ในบทเรียน Y ทั้งหมด นักเรียนต้องทำใหม่")
- [ ] ใช้ permission helper ที่มีอยู่ใน Pinia `auth` store ซ่อนปุ่มถ้าไม่มี `edit_grades`
- [ ] หลังสำเร็จ → toast + refetch progress + refetch member detail (มี method อยู่แล้ว)
- [ ] นักเรียน: ใน [LessonQuizSection.vue](ui/components/learn/course/lesson/LessonQuizSection.vue) listen websocket → ถ้า reset event ของตัวเอง → clear local state + show toast "ครูเปิดให้ทำใหม่"; ใน [LessonAssignmentSection.vue](ui/components/learn/course/lesson/LessonAssignmentSection.vue) เคส `clear_submission` → ปลดล็อค form กลับเป็นโหมดส่งใหม่

**Phase 5 — Verification matrix (manual + automated):**
- Backend tests: 6 reset tests + 4 score consolidation tests
- Manual ตามลำดับ:
  1. ครู A reset quiz ของนักเรียน B ในบท L1 → ตาราง progress refresh, คะแนนรวม member ลด, นักเรียน B reload หน้า quiz ทำใหม่ได้
  2. ครู A reset assignment grade_only ของนักเรียน B → submission ยังอยู่, points=null, status=submitted, ครูตรวจใหม่ได้
  3. ครู A reset assignment clear_submission → ไฟล์หายจริง (`storage/`), assignment_answers row หายจริง
  4. Bulk reset ทั้งห้องในบท L1 → ทุก member ลด
  5. คนไม่มี permission → ปุ่มไม่ขึ้น + API 403
  6. Course legacy mode → ปุ่มไม่ขึ้น + API 422
  7. `php artisan resync:course-total-score` หลัง phase 2 → ไม่มี member ที่ achieved_score เปลี่ยน

**ความเสี่ยง/ข้อต้องเฝ้าระวัง:**
- ลบไฟล์แนบจริงผ่าน `CourseMediaService::deleteIfUnused` — ตรวจให้แน่ว่า reuse counter ถูก (มี test เดิมใน `TopicImageDeleteTest`)
- ถ้ามี certificate ที่ออกจากคะแนนเดิมไปแล้ว — phase นี้ "ไม่" revoke certificate; เปิดเป็น Open Question
- Bulk reset ทั้งห้องในบทใหญ่ (50+ คน) → ใส่ queue job `ResetLessonForCourseJob` แทน sync, response = 202 + job_id (ลด timeout risk)

**ลำดับ commit ที่แนะนำ (เล็กพอ revert ได้):**
1. UI clarity (frontend-only) — 1 commit
2. Score consolidation + tests — 1 commit
3. Audit migration + 3 endpoints + tests — 1 commit
4. Frontend reset UI + websocket — 1 commit
5. Bulk job (optional, ถ้าจำเป็น) — 1 commit

**Open Questions ที่ต้อง confirm ก่อน implement:**
- เก็บ snapshot ใน `lesson_score_resets.snapshot_json` ละเอียดแค่ไหน (row เต็ม vs สรุปคะแนน)?
- Reset ของนักเรียนที่ออกคอร์สแล้วทำอย่างไร (เคสเข้ามาใหม่)?
- ต้อง notify นักเรียนผ่าน in-app notification หรือเฉพาะ websocket toast?

---

### 2026-06-16 - Teacher progress lesson-score UX + reset planning analysis (plan-only)
- Trigger: plan improvements for teacher progress page `/Learn/Courses/23/progress` so lesson scores are clearer, lesson-score aggregation is trustworthy, and teachers can reset lesson assignment / lesson quiz results for re-attempt scenarios.
- Frontend trace:
  - [`ui/components/learn/course/ProgressList.vue`](ui/components/learn/course/ProgressList.vue) table column currently renders `member.scores.lesson_assignments / member.scores.lesson_quizzes` on one line (`lines 857-859`), which looks like one fraction even though it is really two independent lesson score buckets.
  - The member detail panel in the same file already separates these as `งานบทเรียน` and `ทดสอบบทเรียน` with earned/max values (`lines 1192-1199`), so the progress table can reuse that wording and structure instead of the ambiguous `0/0`.
- Aggregation trace:
  - [`CourseController::progress`](api/nuxnanravel/app/Http/Controllers/Api/Learn/Course/info/CourseController.php) computes lesson assignment points from `assignment_answers` filtered to graded/non-null points and lesson quiz points from `lesson_answer_questions` filtered to `is_correct = true` (`lines 944-972`, `1043-1054`, `1108-1122`).
  - Export/report calculation in the same controller uses the same two data sources (`lines 1583-1650`, `1690-1700`).
  - Canonical recomputation in [`CourseScoreService`](api/nuxnanravel/app/Services/CourseScoreService.php) matches this structure in `computeBreakdown()` for lesson assignments and lesson questions, so the main system intent is consistent.
- Reset capability trace:
  - Lesson quiz answers are stored in [`lesson_answer_questions`](api/nuxnanravel/database/migrations/2026_01_01_124133_create_lesson_answer_questions_table.php) and currently only support student submit/update via [`LessonAnswerQuestionController::store`](api/nuxnanravel/app/Http/Controllers/Api/Learn/Course/lessons/questions/LessonAnswerQuestionController.php).
  - Lesson assignment submissions are stored in [`assignment_answers`](api/nuxnanravel/database/migrations/2025_10_26_070433_create_assignment_answers_table.php) and currently support submit, grading, and delete via [`AssignmentAnswerController`](api/nuxnanravel/app/Http/Controllers/Api/Learn/Course/assignments/AssignmentAnswerController.php), but there is no teacher-friendly reset flow.
  - Student lesson assignment UI in [`LessonAssignmentSection.vue`](ui/components/learn/course/lesson/LessonAssignmentSection.vue) treats graded work as closed, so a reset flow must also decide whether the teacher is clearing only the grade/status or wiping the stored submission so the student starts fresh.
- Recommended direction for future implementation:
  - Update the progress table to show explicitly labeled lesson sub-scores instead of a bare slash-separated number.
  - Consolidate progress/export/member-score responses onto one canonical lesson-score builder so reset/recompute paths cannot drift from progress math.
  - Add teacher-only reset endpoints for lesson assignments and lesson quiz results, guarded by a grade-management permission (`edit_grades` recommended), and call `CourseScoreService::recompute()` after every reset mutation.
- Product decision to confirm during implementation:
  - For lesson assignments, should reset mean:
    1. clear grading only but keep the submission for review/resubmission, or
    2. fully remove the submission/attachments so the student redoes from scratch?
  - Recommended default: keep audit history by separating `รีเซ็ตผลตรวจ` from `ล้างคำตอบเพื่อทำใหม่`, if both behaviors are desired.

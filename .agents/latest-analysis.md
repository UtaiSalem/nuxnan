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

### 2026-06-11 — Unify academy admin courses (school inventory + embedded marketplace purchase)

**User-proposed plan (summary):** turn `ui/pages/academies/[name]/admin/courses/index.vue` into a 2-tab page (`academy` | `marketplace`), embed marketplace fetch via `/api/courses?marketplace_only=1`, reuse `CourseMarketCard` in embedded mode, purchase via `CoursePurchaseModal` in-page without redirect, then refresh the academy list so the bought Master Copy appears immediately. User Phase 9 assumed **no backend changes**.

**Verdict after reading real code:** frontend embedding is sound, but the **core goal cannot be met without backend changes** — Phase 9 is overridden. See refined Work Plan entry "Academy Admin Courses — Unified School Inventory + Embedded Marketplace Purchase".

**Decision (confirmed by user):** bought Master Copy must be **owned by the academy** (`academy_id` set), not the buyer user. Backend Phase A is mandatory.

**Verified blockers:**
- `CourseCloneService::cloneCourse()` (CourseCloneService.php:136) hardcodes `academy_id = null`, `user_id = buyer` → clone is a personal course, invisible to `Academy::courses()` (Academy.php:103, `hasMany(Course)` keyed on `academy_id`).
- `is_owned` (CourseResource.php:128-136) and the duplicate-guard (CoursePurchaseService.php:45-60) are **user-scoped** → admin who personally owns a clone is blocked from buying for the school + "owned" badge is wrong in academy context.
- Queued clone path (>20 lessons) runs async in `CloneCourseJob` from the `CoursePurchase` record (CoursePurchaseService.php:82-86, CloneCourseJob.php:75) → academy target must be **persisted on `course_purchases`** for the async branch.
- `course_purchases` has **no `academy_id` column** (migrations 2026_05_16 + 2026_06_08); model uses `$guarded = []` so no fillable edit needed.
- Latent bug in current page: calls `/api/academies/{numericId}/courses` but route binds `{academy:name}` (academy.php:82) and a duplicate `{academy}/courses` exists (academy.php:118); page reads `response.courses.last_page/total` but controller returns totals under `pagination` → academy-side totals never populate.

---

## Current Snapshot

- Date: 2026-06-11
- Branch: main
- Active Work: Implemented + polished — unified academy admin courses (school-owned embedded marketplace purchase). Backend Phase A + frontend Phase B–G + 3 polish items done; 18/18 backend tests pass. Pending: browser smoke test + commit.

## Known Blockers

- ⚠️ Production: ตรวจสอบว่า `topics.sort_order` migration ถูกรันแล้ว (ถ้ายังไม่ได้รัน → `php artisan migrate --force`)

## Active Work

- Scope completed: academy admin courses page and Learn/Courses marketplace query hydration for Master Copy purchase entry flow.
- Files updated:
  - `ui/pages/academies/[name]/admin/courses/index.vue`
  - `ui/pages/Learn/Courses/index.vue`
- Intent:
  - Add a clear CTA from academy admin course management to the course marketplace.
  - Make `marketplace_only` and related query params hydrate frontend state on page load so the link actually lands on filtered marketplace results.
  - Add return-context UI so admins can get back to their academy course management page after browsing marketplace listings.
- Verification:
  - `git diff -- ui/pages/academies/[name]/admin/courses/index.vue ui/pages/Learn/Courses/index.vue .agents/latest-analysis.md`
  - `cmd /c npm exec vue-tsc --noEmit --pretty false` from `ui/` failed on many pre-existing repo-wide type issues.
  - Filtered check for touched files returned only existing global-composable typing failures:
    - `pages/academies/[name]/admin/courses/index.vue(15,13): Cannot find name 'useApi'`
    - `pages/Learn/Courses/index.vue(20,13): Cannot find name 'useApi'`
    - `pages/Learn/Courses/index.vue(288,1): Cannot find name 'usePageLayoutWidgets'`
  - Browser smoke not run because no in-app browser/app terminal session was attached to this thread.

## Work Plan

### Queued: Academy Admin Courses — Unified School Inventory + Embedded Marketplace Purchase

**สถานะ**: วางแผน v1 เสร็จ (verified against code) — รอ user เริ่ม implement
**Ownership model (confirmed)**: bought Master Copy = **school-owned** (`academy_id` = academy). Backend Phase A is mandatory; user's original Phase 9 ("no backend") is void.

**ไฟล์ที่เกี่ยว:**
| ไฟล์ | งาน |
|---|---|
| `api/.../Services/Support/CourseCloneContext.php` | + prop `?int $academyId` (+ in `make()`) |
| `api/.../Services/CourseCloneService.php` | line 136: `academy_id = $context->academyId` (was `null`) |
| `api/.../Services/CoursePurchaseService.php` | `purchase()` รับ `?int $academyId`; duplicate-guard scope academy; persist `academy_id` บน CoursePurchase; ส่งเข้า clone context |
| `api/.../Jobs/CloneCourseJob.php` | line 75: `CourseCloneContext(mode:'purchase', academyId:$purchase->academy_id)`; action_url ชี้ academy edit ถ้ามี academy_id |
| `api/.../Controllers/Api/Learn/Course/CourseMarketplaceController.php` | `purchase()`: validate `academy_id nullable|exists:academies,id` + authorize buyer = academy owner/admin + ส่งต่อ service |
| migration ใหม่ `add_academy_id_to_course_purchases_table` | `unsignedBigInteger('academy_id')->nullable()` + FK `onDelete('set null')` |
| `api/.../Controllers/.../CourseController.php` (index) | (A.7 optional) รับ `academy_id` → คืน flag `owned_by_academy` ต่อคอร์ส (academy-scoped is_owned) |
| `api/.../tests/Feature/CoursePurchaseFlowTest.php` | + เคส academy-scoped purchase + academy duplicate-guard |
| `ui/pages/academies/[name]/admin/courses/index.vue` | 2-tab (`academy`/`marketplace`), state แยก, fetch market, แก้ totals bug, wire success refresh |
| `ui/components/academy/CourseMarketCard.vue` | props `embedded`/`academyAdminMode`; emits `purchase`/`view`; ไม่ navigate ออกใน admin mode |
| `ui/components/academy/CoursePurchaseModal.vue` | props `academyId`/`redirectOnSuccess`/`successContext`; ส่ง `academy_id` ใน body; success ไม่ redirect ใน academy-admin |

**ลำดับลงมือ:**
1. **Phase A (backend, บังคับ):** CourseCloneContext → CourseCloneService → migration → CoursePurchaseService (รับ academyId + scope guard + persist) → CloneCourseJob → CourseMarketplaceController (validate+authorize) → `pint` + `php artisan test`
2. **Phase B (page tabs+state):** `activeTab`, แยก state 2 ชุด, `fetchAcademyCourses()` (อ่าน totals จาก `response.pagination`), เปลี่ยน CTA → tab switch, ตรวจ/แก้ route academy fetch (เรียกด้วย name หรือยืนยัน endpoint)
3. **Phase C (embed market):** `fetchMarketplaceCourses(page,append)` ส่งเฉพาะ `search/category/level/sort/page/per_page`; `loadMoreMarketplaceCourses()`; lazy load ตอนเปิด tab
4. **Phase D (card):** embedded/academyAdminMode + emits; badge "มีในคลังโรงเรียนแล้ว" ใช้ `owned_by_academy`
5. **Phase E (modal):** context props + ส่ง `academy_id`; success → emit + payload (`new_course_id`,`is_queued`); ปุ่มไปคอร์ส → `/academies/{name}/admin/courses/{id}/edit`
6. **Phase F (wire):** ปิด modal → refresh academy list; `is_queued=false` prepend + toast + สลับ tab academy; `is_queued=true` banner "กำลังคัดลอก…" + refresh เอง
7. **Phase G (UX):** empty/skeleton/count/clear-filter เฉพาะตลาด/mobile
8. **Phase H (optional):** แยก `useAcademyCourseMarketplace.ts` เมื่อไฟล์ยาวเกินจริง
9. **Phase I (verify):** `vue-sfc-doctor` ทุก .vue, `vue-tsc` filtered, `php artisan test`, e2e: สลับ tab → ซื้อ → คอร์สเข้าคลังโรงเรียนทันที (sync + queued)

**ความเสี่ยง:** (1) authorization ต้องกันไม่ให้ซื้อยัดเข้า academy ที่ไม่ได้เป็นแอดมิน, (2) queued path ต้องอ่าน academy_id จาก record ไม่ใช่ context ตอน dispatch, (3) academy-scoped is_owned (A.7) ถ้าไม่ทำ ให้ใช้ optimistic badge หลังซื้อแทน, (4) latent route/totals bug ฝั่ง academy list ต้องแก้ใน Phase B ไม่งั้น refresh ไม่ขึ้นจำนวนจริง

---

### Queued: Lesson/Topic Image Lightbox — reuse existing ImageGalleryModal

**สถานะ**: วางแผน v2 เสร็จ (verified against code) — รอ user เริ่ม implement
**ของเดิมที่ overridden**: ข้อ 1 ของ user ("สร้าง viewer กลางใหม่") ยกเลิก — มี `ui/components/ImageGalleryModal.vue` เป็น read-only viewer ครบเครื่องอยู่แล้ว และถูกใช้จริงใน `AssignmentGradingView.vue`, `MyProgressDetails.vue` → reuse เลย ไม่สร้างใหม่

**Key facts (verified):**
- `ImageGalleryModal.vue` props: `show`, `images` (string[] หรือ object ที่มี `full_url|image_url|url|description`), `start-index`, `title`; emit `close`. มี Teleport + `v-if="mounted"` (SSR-safe), backdrop close, Escape/Arrow keys, prev/next, thumbnails, zoom, download, error placeholder, body-scroll-lock, counter — ไม่ผูก like/comment (ต่างจาก `ImageLightbox.vue` ของ feed)
- `LessonPost.vue`: มี state `showImagePreview`/`previewIndex` + `openImagePreview()`/`closeImagePreview()` + `@click` บน card อยู่แล้ว ขาดแค่ render modal → แก้ ~2 บรรทัด (import + 1 block)
- `TopicAccordion.vue`: รูปเป็น `<img>` เปล่า มี `cursor-pointer` แต่ไม่มี handler → เพิ่ม index ใน v-for + click + state + modal
- `LessonImagesViewer.vue`: ใช้ใน `ui/pages/Learn/Lesson/Lesson.vue:239` → grid เปล่า ยกระดับให้เปิด ImageGalleryModal
- caption ต่อรูป: backend ไม่ส่ง (LessonImageResource/TopicImageResource ไม่มี field caption, model ไม่มี column) → ใช้ `title` ระดับ gallery ก่อน; per-image caption = future enhancement (ต้องเพิ่ม column + resource field)

**ไฟล์ที่เกี่ยว:**
| ไฟล์ | งาน |
|---|---|
| `ui/components/learn/course/lesson/LessonPost.vue` | import + render `<ImageGalleryModal>` ผูก `showImagePreview`/`previewIndex` (state มีอยู่แล้ว) |
| `ui/components/learn/course/lesson/TopicAccordion.vue` | import + state + `openTopicImage(index)` + `@click` + index ใน v-for + render modal |
| `ui/components/learn/course/lesson/LessonImagesViewer.vue` | import + state + click + render modal (recommended) |

**ลำดับ:** LessonPost → TopicAccordion → LessonImagesViewer → `vue-sfc-doctor` 3 ไฟล์ → `npm run dev` smoke
**ความเสี่ยง:** (1) อย่าใช้ `ImageLightbox.vue` ของ feed (ผูก post_images endpoint), (2) LessonPost render เป็น list ได้ → modal 1 instance ต่อ lesson + keydown listener ต่อ instance (handler early-return เมื่อ show=false → ใช้ได้ แต่ถ้า list ยาวมากค่อยพิจารณายก modal ขึ้น page-level), (3) caption ว่างเพราะ backend ยังไม่ส่ง — แจ้ง user ไว้ก่อน

---

### Queued: Donation Admin Consolidation + Full CRUD

**สถานะ**: วางแผนเสร็จแล้ว ยังไม่ได้ implement — รอ user เริ่ม

**การตัดสินใจ (ยืนยันแล้ว):**
1. ลบหน้าเก่า `PlearndAdmin/Donation/*` ทิ้ง (ไม่ redirect)
2. รวมเป็นหน้าเดียวที่ `nuxnan-admin/supports/index.vue`
3. เพิ่ม CRUD admin เต็ม — show / update / destroy(soft) / bulk approve-reject + audit

**Bugs ที่ต้องแก้ (verified):**
- `DonateController::index()` ไม่ eager-load `donor` → admin card ได้ `donor=null`
- `DonateResource` อ่าน `privacy_setting` แต่ column จริง = `privacy_settings`
- `donates.approved_by` เป็น `tinyInteger` (overflow เมื่อ user_id > 127)
- `recieve()`/`reject()` ไม่เช็คสถานะก่อน → double-approve ได้

**ไฟล์ที่เกี่ยว:**
| ไฟล์ | งาน |
|---|---|
| `api/.../Models/Donate.php` | เพิ่ม SoftDeletes + audit fields |
| `api/.../Controllers/Api/Earn/DonateController.php` | CRUD + guard + eager load |
| `api/.../Resources/Earn/DonateResource.php` | แก้ field mismatch |
| `api/.../routes/earn/donate.php` | เพิ่ม CRUD routes, ลบ dead `/more` |
| `ui/pages/nuxnan-admin/supports/index.vue` | rebuild page |
| `ui/components/earn/donates/ApproveDonateCard.vue` | ปรับ events |
| `ui/pages/PlearndAdmin/Donation/*` | **ลบ** |
| `ui/layouts/main.vue:627` | ลบลิงก์ nav เก่า |

**ลำดับ:** migration+model → index+resource fix → CRUD+guards+routes → tests → frontend composable → page rebuild → cleanup+verify

**ความเสี่ยง:** `->change()` ต้องมี `doctrine/dbal`, route ordering (`/bulk-review` ก่อน `/{donate}`), middleware equivalence check ก่อนลบหน้าเก่า

---

### Queued: Draft Visibility & Interaction Lockdown

**สถานะ**: วางแผน v2 เสร็จแล้ว ยังไม่ได้ implement — รอ user เริ่ม

**เป้าหมาย**: นักเรียนต้องไม่เห็น/โต้ตอบกับ Lesson/Assignment/Quiz ที่เป็น draft — ทั้ง UI และ direct API

**Policy**: `hide + hard-block` (404 detail, ซ่อน list, 403 action)

**Key constraints (verified against code):**
1. Status fields ต่างกัน: Lesson=`publication_status` string, Assignment=`status` int, Quiz=`is_active` bool
2. Assignment polymorphic → visibility cascades จาก parent lesson
3. Topic ไม่มี draft flag → inherit จาก Lesson ใน v1
4. `LessonAccessService` pattern มีอยู่แล้ว → reuse
5. `Course::courseAssignments()` กับ `Course::assignments()` อาจ drift
6. Action endpoints ที่ต้อง guard: LessonProgressController, LessonAnswerQuestionController, AssignmentAnswerController, CourseQuizResultController, UserAnswerQuestionController

**ลำดับ:** ContentVisibilityService → Backend read lockdown (assignments เสี่ยงสุด ทำก่อน) → Backend action lockdown → Frontend UI → Tests

**Estimated:** ~3.5 วัน

---

## Completed Features (reference only — ลบได้เมื่อ timeline ถูก consolidate)

### ✅ Sort Order System — Topics, Course Groups, Academy Groups (2026-06-09)
- Migrations + backfill, model `booted()` auto-assign, controllers with reorder + validation, routes, resources, UI widgets
- 23/23 tests passed (CourseLessonReorderTest, TopicsAndGroupsReorderTest, AcademyGroupReorderTest)

### ✅ Topic Create/Update Flow Overhaul (2026-06-10, commit `605fe7c`)
- Backend: validation aligned with DB schema (content nullable, min_read nullable integer), transaction wrap, atomic min_read adjustment, `=== "null"` anti-pattern removed
- Frontend: field-level 422 error display, conditional content/min_read submission
- TopicResource: cleaned ghost fields (post_type, parent_post_id, public)
- Tests: `TopicCreateUpdateTest.php` — 8 tests

### ✅ Topic Image Management (2026-06-10, uncommitted)
- Backend: `TopicImageController::destroy()` — ownership check, course admin auth, `CourseMediaService::deleteIfUnused()`, fixed `Storage::delete()` bug
- Frontend: `deleteTopicImage()` calls real API + syncs local state, `cleanupImagePreviews()` with `URL.revokeObjectURL()`, image state cleanup on topic switch/modal close/save
- ⚠️ Remaining: `TopicImageDeleteTest` ยังไม่ได้เขียน (6 test cases planned)

---

## Coordination Board

_(clear — no active multi-agent work)_

## Decisions And Assumptions

- Role `STUDENT` (not `USER`) is the default general-user role.
- `username` is treated as a primary identifier field in admin flows.
- Score schema uses `*_percentage` columns; legacy `*_total_score` consumers are mapped through `CourseMemberResource` for backward compatibility.
- Avatar is an Eloquent accessor (not a DB column) — never include it in `select(...)` eager loads; select `profile_photo_path` instead.
- Notification copy must avoid 4-byte emoji while DB collation is `utf8mb3_unicode_ci`.
- Topic visibility inherits from parent Lesson `publication_status` (no own draft flag in v1).

## Open Questions

- Refund policy for self-leave on paid courses: no refund vs grace-period refund? (v1 default: no refund for self-leave, always refund for admin-remove)
- Assignment answers with pending review (submitted but not graded): delete immediately or require admin acknowledgement before removal?
- Finalized members with issued certificates: should removal be blocked, or allow removal but retain certificate records?
- Rate limiting for self-leave + re-join to prevent refund exploitation?

---

## Analysis Timeline

### 2026-06-11 - Refined plan: unified academy admin courses + in-page Master Copy purchase
- Reviewed user's 9-phase plan for embedding the marketplace into `academies/[name]/admin/courses/index.vue` and purchasing in-page.
- Read real code (frontend + backend) and confirmed frontend embedding is viable (`/api/courses?marketplace_only=1` exists, CourseResource exposes is_for_marketplace/price/is_owned).
- Found the goal-breaking gap: clone is created user-owned with `academy_id = null`, so a purchased Master Copy never appears in the academy's `hasMany` course list → user's Phase 9 "no backend changes" is invalid.
- Also flagged user-scoped is_owned/duplicate-guard, async queued-clone academy persistence, missing `course_purchases.academy_id`, and a latent academy-list route/totals bug.
- User confirmed school-owned model → added backend "Phase A" as mandatory in the Work Plan; recorded full step list, file table, and risks.

### 2026-06-11 - Academy admin course marketplace entry flow
- User need: from academy admin course management, admins should be able to open the course marketplace and buy Master Copy courses for their school workflow.
- Findings:
  - `ui/pages/academies/[name]/admin/courses/index.vue` currently only offers "create course" and no marketplace entry CTA.
  - `ui/pages/Learn/Courses/index.vue` supports marketplace filtering in API requests, but does not hydrate `marketplace_only` or tab state from route query on initial load.
  - Existing links such as `/Learn/Courses?marketplace_only=1` therefore do not reliably open the filtered marketplace experience.
- Implemented:
  - Added marketplace CTA and informational banner on `ui/pages/academies/[name]/admin/courses/index.vue`.
  - Added query hydration for `tab`, `marketplace_only`, and contextual academy return info in `ui/pages/Learn/Courses/index.vue`.
  - Added a contextual back link/banner on `Learn/Courses` for academy-admin marketplace entry.
- Verification:
  - Read-back diff passed.
  - `vue-tsc` still fails on repo-wide existing issues; no new task-specific errors surfaced beyond the same global composable typing failures already present in touched pages.

### 2026-06-11 - Route white-screen / widget-layout recursion stabilization
- Scope claimed: frontend route-change bug affecting Dashboard, Newsfeed, Academies, Learn/Courses, Marketplace pages, and course shell widget layout.
- Verified risk pattern: multiple pages/components were mutating shared `useLayoutWidgets()` state directly in mount/unmount hooks, while layout structure and Teleport destinations depended on that same state.
- Implemented `usePageLayoutWidgets()` in `ui/composables/useLayoutWidgets.ts` with single-owner synchronization plus guarded teardown to reduce route-transition races and recursive updates.
- Updated affected consumers:
  - `ui/pages/Dashboard.vue`
  - `ui/pages/Play/Newsfeed.vue`
  - `ui/pages/Earn/Marketplace/Sales.vue`
  - `ui/pages/Earn/Marketplace/History.vue`
  - `ui/pages/academies/index.vue`
  - `ui/pages/Learn/Courses/index.vue`
  - `ui/components/learn/course/v2/CoursePageShell.vue`
- Additional cleanup: `ui/composables/useDashboardData.ts` now returns store-backed `levelProgress` directly; `ui/pages/Learn/Courses/index.vue` filter reset now writes through `.value` refs correctly.
- Verification:
  - `cmd /c npx vue-tsc --noEmit` from `ui/` completed but failed on many pre-existing repo-wide TypeScript issues unrelated to this change set.
  - No app terminal/browser session was attached, so live route navigation was not exercised in-browser during this turn.

_(consolidated 2026-06-10 — previous entries covered: sort order system implementation+verification, topic reorder UX discoverability, TopicOrderWidget theming mismatch, lesson sidebar widget wrong-course cache, lesson progress widget ordering, lesson delete 500 import fix, course group management page analysis, donation admin duplication analysis+planning, draft visibility analysis+planning v1+v2, topic create/update validation mismatch investigation, topic image edit leakage diagnosis, topic image delete placeholder+backend bugs, infinite scroll implementation for legacy donation page)_
### 2026-06-11 - Lesson/topic image preview analysis and plan
- User asked why lesson images and topic images cannot be clicked/opened, and requested a plan only.
- Verified frontend root causes:
  - `ui/components/learn/course/lesson/LessonPost.vue` has `showImagePreview`, `previewIndex`, and `openImagePreview()` wired on lesson image cards, but no preview modal/lightbox is imported or rendered in the template, so clicking lesson images only flips local state and shows nothing.
  - `ui/components/learn/course/lesson/TopicAccordion.vue` renders topic images with `cursor-pointer` and hover scale, but has no click handler or preview modal at all.
  - `ui/components/learn/course/lesson/LessonImagesViewer.vue` is only a plain image grid and also has no preview behavior, so it cannot currently serve as the shared viewer.
- Verified backend/media is not the main blocker for this issue:
  - `api/nuxnanravel/app/Http/Resources/Learn/Course/lessons/LessonImageResource.php` returns `full_url` from `LessonImage::getFullUrlAttribute()`.
  - `api/nuxnanravel/app/Http/Resources/Learn/Course/lessons/TopicImageResource.php` returns `full_url` from `TopicImage::getImageUrlAttribute()`.
- Recommended implementation direction:
  - Add a lesson/topic-safe lightbox or preview modal that accepts plain image URLs and captions instead of reusing `ui/components/play/feed/ImageLightbox.vue` directly, because that component is coupled to post-image likes/comments endpoints.
  - Wire both lesson image cards and topic image cards into the same preview flow for consistent UX.
  - Remove misleading `cursor-pointer` styling from any image UI that still does not open a viewer.

### 2026-06-11 - Refined lesson/topic image plan: reuse ImageGalleryModal (no new component)
- Re-read code and found `ui/components/ImageGalleryModal.vue` already exists as a decoupled read-only viewer (Teleport+`mounted` SSR guard, backdrop/Escape/Arrow nav, prev/next, thumbnails, zoom, download, error placeholder, scroll-lock, counter) and is already used in `AssignmentGradingView.vue` + `MyProgressDetails.vue`.
- Overrode user's step 1 ("build a central viewer") → reuse `ImageGalleryModal` instead; this collapses the plan to 3 small wiring edits and removes the need to delete `cursor-pointer` (images become genuinely clickable instead).
- `LessonPost.vue` already has `showImagePreview`/`previewIndex`/`openImagePreview()` wired on cards — only the modal render is missing.
- `LessonImagesViewer.vue` confirmed used at `ui/pages/Learn/Lesson/Lesson.vue:239` — included as a recommended third wiring.
- Confirmed backend returns no per-image caption (resources/model lack the field) → gallery `title` used now; per-image caption deferred.
- Recorded full step-by-step Work Plan entry "Lesson/Topic Image Lightbox — reuse existing ImageGalleryModal".

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

- 2026-06-09: Improved Topic Reordering UI and performance.
    - Confirmed H1 (UX Discoverability) as the root cause: widget was too far down on long lesson cards.
    - Added accordion-style `TopicOrderWidget` near the top of `LessonPost.vue` for immediate Admin accessibility.
    - Implemented eager loading for `topics` in `CourseLessonController::index` to resolve N+1 query issues.
    - Removed redundant reorder widget placement from the lower section of lesson cards.
- 2026-06-09: Implemented and fully verified reordering for Topics, Course Groups, and Academy Groups. 
    - Verified DB structural integrity and existing data.
    - Added automated `sort_order` assignment in Eloquent `booted()` methods for `Topic`, `CourseGroup`, `AcademyGroup`, and `Lesson`.
    - Hardened API controllers with strict reorder validation (ensuring all parent children are included and no duplicates).
    - Fixed missing `isAdmin` method in `Academy` model.
    - Verified relationship ordering (`orderBy('sort_order')`) in `Lesson`, `Course`, and `Academy` models.
    - Achieved 100% test pass rate (23 tests) covering all 3 reorderable entities and edge cases.
    - Confirmed frontend stores and UI components are correctly integrated with the backend endpoints.
- 2026-06-09: User asked whether course admins can reorder lesson topics and what is required. Re-verified code path end-to-end. UI reorder widget exists in `ui/components/learn/course/lesson/TopicOrderWidget.vue` and is rendered only for admins in `ui/components/learn/course/lesson/LessonPost.vue`. The widget calls `courseStore.reorderTopics()` in `ui/stores/course.ts`, which sends `PATCH /api/lessons/{lesson}/topics/reorder` from `api/nuxnanravel/routes/learn/course.php`. Backend permission is enforced in `api/nuxnanravel/app/Http/Controllers/Api/Learn/Course/lessons/topics/TopicController.php` using `$lesson->course->isAdmin(auth()->user())`. Persistence uses `topics.sort_order`, and frontend consumption stays ordered through `api/nuxnanravel/app/Models/Lesson.php` plus `api/nuxnanravel/app/Http/Resources/Learn/Course/lessons/TopicResource.php`. Conclusion: the feature already exists for course admins; remaining work is rollout verification and stronger tests, not core implementation.
- 2026-06-09: User reported that `http://localhost:3000/Learn/Courses/24/lessons` still does not show drag-and-drop for lesson topics. Re-inspection shows a UX mismatch is likely: the top-level page `ui/pages/Learn/Courses/[id]/lessons.vue` exposes only `LessonOrderWidget` for reordering lessons, while topic reordering is nested inside each `LessonPost` card and only appears when `isAdmin && hasTopics` in `ui/components/learn/course/lesson/LessonPost.vue`. Both the lesson list page and single-lesson page reuse `LessonPost`, so if the control feels missing on the list page the most likely causes are discoverability, `hasTopics` being false for the current lesson payload, or `isCourseAdmin` not being true in that route context. Plan direction: verify the runtime payload for `lesson.topics` and `isCourseAdmin`, then decide whether to surface topic reordering more prominently on the list page or keep it in-place but make the affordance clearer.
- 2026-06-09: User then showed a screenshot where the reorder card header says there are 6 topics, but the expanded body appears blank. That rules out missing topic data in the current lesson card: the count label in `LessonPost.vue` is derived from `lesson.topics.length`. Most likely root cause is visual theming mismatch. `TopicOrderWidget.vue` still uses the old dark-surface palette (`bg-white/5`, `text-white/80`, `text-white/40`, `hover:bg-white/10`) while it is now rendered inside a light purple `details` card in `LessonPost.vue`. Result: rows can render but appear effectively invisible on light backgrounds. Secondary checks remain: confirm `ClientOnly` + `vuedraggable` mount normally and that `localList` mirrors `props.topics` after opening, but styling is now the leading hypothesis.

_(empty — awaiting next analysis input)_

---

## Current Snapshot

- Date: 2026-06-09
- Branch: main
- Active Work: Lesson page sidebar widget bug fix for stale lesson data and incorrect topic counts.

## Known Blockers

- ✅ All migrations for Sort Order System have been run on the database.

## Active Work

### Feature: Sort Order System — Topics, Course Groups, Academy Groups (COMPLETED)

## Work Plan

### สถานะปัจจุบัน (Re-verified against codebase 2026-06-09)

**Lessons — ระบบ reorder ทำงานถูกต้องแล้ว:**
- ✅ `CourseLessonReorderTest.php` — แก้ payload เป็น flat array แล้ว + เพิ่ม 2 test cases (empty array, round-trip)
- ✅ `LessonOrderWidget.vue` → vuedraggable → `courseStore.reorderLessons()` → `PATCH /api/courses/{id}/lessons/reorder`
- ✅ Widget ใช้ในหน้า `ui/pages/Learn/Courses/[id]/lessons.vue:217` + มี `@saved → fetchLessons(true)` refresh

**Topics — ✅ Implementation ครบแล้ว:**
- ✅ Migration: `2026_06_09_141522_add_sort_order_to_topics_table.php` — เพิ่ม `sort_order unsignedInteger default 0` + index `(lesson_id, sort_order)` + backfill ด้วย `ROW_NUMBER() OVER (PARTITION BY lesson_id ORDER BY created_at, id)`
- ✅ Model: `Lesson::topics()` → `orderBy('sort_order')->orderBy('id')` (line 59)
- ✅ Controller: `TopicController::store()` ตั้ง `sort_order = max + 1` (line 43)
- ✅ Controller: `TopicController::reorder()` รับ `{ topics: [id...] }`, transaction wrap, verify ownership (line 247-297)
- ✅ Route: `PATCH /{lesson}/topics/reorder` อยู่ **ก่อน** resource route (course.php:215)
- ✅ Resource: `TopicResource` ส่ง `sort_order` (line 44)
- ✅ UI: `TopicOrderWidget.vue` → vuedraggable → `courseStore.reorderTopics()` → `PATCH /api/lessons/{id}/topics/reorder`
- ✅ UI Integration: ใช้ใน `LessonPost.vue:844` (แสดงเฉพาะ admin + มี topics)

**Course Groups — ✅ Implementation ครบแล้ว:**
- ✅ Migration: `2026_06_09_151626_add_sort_order_to_course_groups_table.php` — เพิ่ม `sort_order` + index `(course_id, sort_order)` + backfill
- ✅ Model: `Course::courseGroups()` → `orderBy('sort_order')->orderBy('id')` (line 342)
- ✅ Controller: `CourseGroupController::store()` ตั้ง `sort_order = max + 1` (line 153)
- ✅ Controller: `CourseGroupController::reorder()` — transaction wrap, verify ownership (line 275-325)
- ✅ Route: `PATCH /courses/{course}/groups/reorder` อยู่ **ก่อน** `/{group}` wildcard (course.php:139)
- ✅ Resource: `CourseGroupResource` ส่ง `sort_order` (line 53)
- ✅ UI: `GroupOrderWidget.vue` → vuedraggable → `courseStore.reorderGroups()` → `PATCH /api/courses/{id}/groups/reorder`
- ✅ UI Integration: ใช้ใน `GroupsList.vue:129` (แสดงเฉพาะ admin + groups > 1)

**Academy Groups — ✅ Backend ครบ, ✅ UI ครบ:**
- ✅ Migration: `2026_06_09_161453_add_sort_order_to_academy_groups_table.php` — เพิ่ม `sort_order` + index `(academy_id, sort_order)` + backfill
- ✅ Model: `Academy::academyGroups()` → `orderBy('sort_order')->orderBy('id')` (line 99)
- ✅ Controller: `AcademyGroupController::store()` ตั้ง `sort_order = max + 1` (line 40)
- ✅ Controller: `AcademyGroupController::reorder()` — transaction wrap, verify ownership (line 225-275)
- ✅ Route: `PATCH /{academy}/groups/reorder` อยู่ก่อน `/{type}` wildcard (academy.php:67)
- ✅ UI: `AcademyGroupOrderWidget.vue` -> vuedraggable -> `academyStore.reorderGroups()` -> `PATCH /api/academies/{id}/groups/reorder`
- ✅ UI Integration: ใช้ใน `AcademyGroups.vue:133` (แสดงเฉพาะ admin + groups > 1)

**Tests — ✅ ครบถ้วนและผ่าน 100%:**
- ✅ `CourseLessonReorderTest.php` — 5 tests (admin reorder, non-admin 403, cross-course 422, empty 422, round-trip)
- ✅ `TopicsAndGroupsReorderTest.php` — 11 tests (รวม cross-parent 422, auto sort_order on create, round-trip, duplicate/subset checks)
- ✅ `AcademyGroupReorderTest.php` — 7 tests (admin reorder, non-admin 403, cross-academy 422, auto sort_order, round-trip)
- ✅ **Total Reorder Tests**: 23/23 tests passed.

---

### สิ่งที่เหลือทำ (เรียงตามลำดับแนะนำ)

```
- ไม่มี (ฟีเจอร์เสร็จสมบูรณ์และผ่านการทดสอบครอบคลุมทุกจุด)
```

---

### Feature: Draft Visibility & Interaction Lockdown — Work Plan v2 (2026-06-10)

**เป้าหมาย**: นักเรียนต้องไม่เห็น หรือเห็นแต่โต้ตอบไม่ได้ กับ Lesson/Assignment/Quiz ที่เป็น draft โดยปลอดภัยทั้งจาก UI และ direct API access

**Default policy ที่เลือก**: `hide + hard-block` (404 detail, ซ่อน list, 403 action) — ปลอดภัยและ logic ตรงสุด ตามที่ user เสนอใน Findings ข้อ 1

#### Phase 0 — Foundation: Centralized Visibility Policy (1 PR)

สร้างชั้น policy เดียว ที่ทั้ง controller, action endpoint และ resource เรียกใช้ร่วมกัน ป้องกัน drift

**ไฟล์ใหม่**:
- `api/nuxnanravel/app/Services/ContentVisibilityService.php`
  - `canStudentViewLesson(User $u, Lesson $l): bool` → `$l->publication_status === STATUS_PUBLISHED`
  - `canStudentViewAssignment(User $u, Assignment $a): bool` → `$a->is_published && parentVisible($a)` (เช็ก polymorphic parent: ถ้า `assignmentable` คือ Lesson/Topic ต้องเป็น lesson published ด้วย)
  - `canStudentViewQuiz(User $u, CourseQuiz $q): bool` → `(bool)$q->is_active`
  - `canStudentInteract...()` × 3 (สำหรับ progress/submit/start — เริ่มจาก == view แต่แยก method ไว้เผื่อ business rule แตก เช่น quiz `end_date` หมดอายุ)
  - `assertVisibleOrFail($model, $user, int $code = 404): void` — helper ตัดโค้ดซ้ำ
- `tests/Unit/ContentVisibilityServiceTest.php` — unit test ครอบ matrix (admin/student × draft/published × parent draft cascade)

**Decisions ที่ต้องชัดก่อนเขียน code**:
- ✅ Topic = inherit Lesson `publication_status` (ไม่เพิ่ม column ใน v1)
- ✅ Quiz/Assignment ภายใต้ draft lesson = ซ่อนเสมอ แม้จะ published เอง
- ⚠️ Quiz `is_active=0` ที่มี `userResults` อยู่แล้ว: ห้าม `update` (finalize) เพิ่ม แต่ยัง **อ่าน** ผลคะแนนเก่าได้ — แยก `canStudentViewQuizResult()` ออกอีกตัว
- ⚠️ Assignment ที่มี `answers` ของนักเรียนคนนั้นอยู่แล้ว แล้วโดน revert เป็น draft: เห็นผลของตัวเองได้ แต่ submit/edit ใหม่ไม่ได้

#### Phase 1 — Backend Read Path Lockdown (1 PR ต่อ domain, 3 PR รวม)

**1A. Lessons** (น้อยสุด — index/show มีอยู่แล้ว)
- `LessonAccessController` ที่ส่ง access status → ตรวจ `$lesson->course_id == $course->id` (ownership) ทุก method (ปัจจุบันบาง action พึ่ง implicit binding)

**1B. Assignments** (เสี่ยงสูงสุด)
- `CourseAssignmentController.php:19 index()` → เพิ่ม `->when(!$isAdmin, fn($q) => $q->where('status', 1))` + filter parent visibility ด้วย (ถ้า assignment สังกัด lesson draft, ซ่อน)
- `CourseAssignmentController.php:30 show()` → เพิ่ม `abort_if($assignment->assignmentable_id !== course-or-descendant, 404)` ownership + `assertVisibleOrFail($assignment, $user, 404)`
- ตรวจ `Course::courseAssignments()` relation ว่าครอบ assignment ใต้ lesson/topic ของ course หรือไม่ (Finding #5) — ถ้าไม่ครอบ ต้องสร้าง scope รวม

**1C. Quizzes**
- `CourseQuizController.php:42 index()` → คงเดิม (มี `is_active` filter แล้ว) แต่ตรวจซ้ำผ่าน service
- `CourseQuizController.php:109 show()` → เพิ่ม `if (!$isCourseAdmin && !$quiz->is_active) abort(404);` ทันทีหลัง ownership check (บรรทัด 112)
- ตัด strip-questions logic ที่ซ้ำซ้อนเมื่อเป็น draft (จะ 404 ก่อนแล้ว)

#### Phase 2 — Backend Action Path Lockdown (1 PR)

ทุก action endpoint ต้องเรียก `$visibility->assertVisibleOrFail()` ก่อน mutation **เสมอ** (defense in depth — แม้ UI ซ่อนแล้ว direct API call ต้องตาย)

| Endpoint | ไฟล์ | จุดที่ต้องเพิ่ม guard |
|---|---|---|
| Lesson progress start/update | `LessonProgressController` | ต้น method `store/update` |
| Lesson question answer | `LessonAnswerQuestionController` | ต้น method `store` |
| Assignment submit | `AssignmentAnswerController::store/update` | หลัง resolve $assignment |
| Assignment delete answer | `AssignmentAnswerController::destroy` | ถ้านักเรียนลบของตัวเอง ต้องเช็ก |
| Quiz start attempt | `CourseQuizResultController::store` | ต้น method + เช็ก `is_active` |
| Quiz finalize | `CourseQuizResultController::update` | ต้น method |
| Per-question submit | `UserAnswerQuestionController::store` | ต้น method |

**Response shape สำหรับ block**: `403 {"success": false, "code": "CONTENT_UNAVAILABLE", "message": "เนื้อหานี้ยังไม่เปิดให้เข้าใช้งาน"}` — frontend ใช้ `code` ตัดสินใจ redirect/toast

#### Phase 3 — Frontend UI Layer (1 PR ต่อ domain, 3 PR)

ลำดับ: ซ่อน list → soft-disable detail (ถ้ามาทาง deep link เก่า) → handle 403/404 error

- `ui/pages/Learn/Courses/[id]/assignments/index.vue`, `[assignmentId].vue` — กรอง `assignment.is_published` ใน computed list (เป็น safety net เพราะ backend กรองแล้ว); ถ้า detail โดน 404 → redirect ไป list + toast
- `ui/pages/Learn/Courses/[id]/quizzes/index.vue`, `[quizId]/index.vue`, `attempt.vue` — เหมือนกัน; `attempt.vue` ต้องเช็ก `is_active` ก่อนเริ่มสอบ และ disable ปุ่ม "เริ่มทำข้อสอบ" + tooltip "ฉบับร่าง / ยังไม่เปิด"
- Reuse `LessonAccessService` shape (`access_status: 'unavailable'`) ในการแสดง badge — สร้าง composable `useContentLockBadge(item)` คืน `{ visible, label, tone }` กลาง
- Admin UI: ใส่ badge "ฉบับร่าง" ที่ list สำหรับ admin ด้วย (currently ไม่ชัดว่าตัวไหน draft) — เป็น UX bonus

#### Phase 4 — Tests (1 PR — ทำพร้อม Phase 1–2 ก็ได้)

**Feature tests** (จัดกลุ่มต่อ domain — 3 ไฟล์):
- `tests/Feature/DraftLessonVisibilityTest.php`
- `tests/Feature/DraftAssignmentVisibilityTest.php`
- `tests/Feature/DraftQuizVisibilityTest.php`

แต่ละไฟล์ครอบ matrix:
| Scenario | Expected |
|---|---|
| Admin list → เห็น draft + published | 200, count = all |
| Student list → เห็นเฉพาะ published | 200, count = published only |
| Student GET detail draft | 404 |
| Student POST action บน draft | 403, code `CONTENT_UNAVAILABLE` |
| Admin POST action บน draft | 2xx (ยัง preview/manage ได้) |
| **Cascade test** (Assignment เท่านั้น): assignment published แต่ parent lesson draft → student เห็น 404 |
| **Mid-attempt edge** (Quiz): นักเรียนมี active result อยู่ก่อน quiz โดนปิด `is_active=0` → finalize ได้ครั้งสุดท้าย? (decision: **ไม่ได้** — block ทันทีตาม policy) |

#### Order of Execution (แนะนำ)

1. Phase 0 (foundation + unit test) — **1 วัน**, ไม่ break อะไร
2. Phase 1B + 2 (Assignment — เสี่ยงสุด) พร้อมกัน — **0.5 วัน**
3. Phase 1C + 2 (Quiz) — **0.5 วัน**
4. Phase 1A + 2 (Lesson actions) — **0.5 วัน**
5. Phase 3 (Frontend ตามลำดับเดียวกัน) — **1 วัน**
6. Phase 4 (Tests — ทำคู่กับ 1–2 ตั้งแต่ต้น) — ongoing

รวม ~3.5 วัน, ตัด PR เล็กพอ revert ได้

#### Risks & Open Questions

- **`Course::assignments()` และ `Course::courseAssignments()` เป็น relation ที่ equivalent กัน** (ทั้งคู่นิยามเหมือนกันใน `Course.php:325-333`) แต่ทั้งสองชื่อถูกใช้กระจายในหลาย controller/service จึงเสี่ยง drift — visibility enforcement ต้องบังคับใช้สม่ำเสมอที่ทุก call site (ห้ามแก้ filter ที่ชื่อเดียวแล้วทึกทักว่าครอบหมด)
- **Backward compat สำหรับ saved data** — นักเรียนที่ submit/start ไปก่อนหน้าที่ admin เปลี่ยน status เป็น draft: รักษาสิทธิ์อ่าน result เก่าได้ (ผ่าน separate `canViewOwnResult`), แต่ห้าม mutate ใหม่
- **Quiz `end_date`** — ปัจจุบัน **ยังไม่ได้** enforce ใน `show`/`store`/`update` ของ result (มีแค่ใน validation ตอน create/update quiz เท่านั้น) ส่วน `is_active` และ `canTakeExam` ถูกใช้จริง — ควรตัดสินใจ **ดึง `end_date` มารวมเข้า `ContentVisibilityService`** ตอน v1 เลย ไม่งั้นจะกลายเป็น gating ชั้นใหม่ที่หลุดต่อ
- **Assignment cascade rule (precise)** — visibility cascades from parent lesson **เฉพาะเมื่อ** `assignmentable_type` เป็น `Lesson` หรือ `Topic`; ส่วน course-level assignment (`assignmentable_type === Course`) ใช้ own `status` เพียงอย่างเดียว — ตรงกับ `Assignment::getLesson()` ที่คืน `null` สำหรับ course-level อยู่แล้ว
- **Topic v2** — ถ้าอนาคตอยากให้ Topic มี draft ของตัวเอง ต้องเพิ่ม column ใน topic table แล้ว update `canStudentViewAssignment` ให้ cascade ผ่าน topic ด้วย — design ของ service ตอนนี้รองรับการขยายแบบนี้ได้

### ข้อควรระวัง (จากการตรวจโค้ดจริง)

1. **`lessons.order` กับ `display_order` คนละตัว**: `order` = canonical position ใน DB, `display_order` = computed ตอน runtime เฉพาะ published lessons (1-indexed, ไม่มี gap) → ห้ามใช้ `display_order` เป็น field ใน DB
2. **Backfill strategy**: ทั้ง 3 migrations ใช้ `ROW_NUMBER() OVER (PARTITION BY ... ORDER BY created_at, id)` สำหรับ MySQL + fallback PHP loop สำหรับ SQLite (tests) — deterministic ordering
3. **Transaction ทุก reorder**: ทั้ง 4 controllers (lessons, topics, course groups, academy groups) ใช้ `DB::transaction()` ✅
4. **Route ordering**: ทั้ง 3 จุดใหม่ (topics, course groups, academy groups) วาง `PATCH .../reorder` **ก่อน** `{param}` wildcard ✅
5. **Relation ordering propagation**: เมื่อแก้ `courseGroups()`, `topics()`, `academyGroups()` ให้ sort ตาม `sort_order` → มีผลทุกที่ที่เรียก relation นี้โดยอัตโนมัติ รวมถึง eager load
6. **`CourseLessonController::index()` line 95**: `$course->courseGroups()->get(['id', 'name'])` — ใช้แค่สำหรับ dropdown filter, ordering ตาม sort_order อัตโนมัติแต่ไม่จำเป็นจริงๆ ที่จุดนี้ (ไม่มีผลเสีย)
7. **Topic model ใช้ `$guarded = []`**: sort_order สามารถ mass assign ได้โดยไม่ต้องเพิ่ม fillable ✅

---

## Coordination Board

- 2026-06-09 Codex: fixing lesson sidebar widget data accuracy on lesson detail pages. Files: `ui/components/learn/course/v2/CourseLessonsMenu.vue`, `ui/stores/course.ts`, `api/nuxnanravel/app/Http/Controllers/Api/Learn/Course/lessons/CourseLessonController.php`, `api/nuxnanravel/app/Http/Resources/Learn/Course/lessons/LessonResource.php`.

## Decisions And Assumptions

- Role `STUDENT` (not `USER`) is the default general-user role.
- `username` is treated as a primary identifier field in admin flows.
- Score schema uses `*_percentage` columns; legacy `*_total_score` consumers are mapped through `CourseMemberResource` for backward compatibility.
- Avatar is an Eloquent accessor (not a DB column) — never include it in `select(...)` eager loads; select `profile_photo_path` instead.
- Notification copy must avoid 4-byte emoji while DB collation is `utf8mb3_unicode_ci`.

## Open Questions

- Refund policy for self-leave on paid courses: no refund vs grace-period refund? (v1 default: no refund for self-leave, always refund for admin-remove)
- Assignment answers with pending review (submitted but not graded): delete immediately or require admin acknowledgement before removal?
- Finalized members with issued certificates: should removal be blocked, or allow removal but retain certificate records?
- Rate limiting for self-leave + re-join to prevent refund exploitation?

---

## Analysis Timeline

- 2026-06-09: Lesson sidebar widget on `/Learn/Courses/{id}/lessons/{lessonId}` could show the wrong lessons because `CourseLessonsMenu.vue` only fetched when the shared store was empty, so cached lessons from another course could remain visible. The same widget also displayed `0 หัวข้อ` because the lessons index endpoint did not include `topics_count`. Fixed by always delegating fetch decisions to `courseStore.fetchLessons()`, validating that cached lessons belong to the requested course before reuse, and adding `withCount('topics')` plus `topics_count` to the lesson resource payload.

- 2026-06-09: Re-checked the specific user question "can course admins reorder lesson topics?" against code. Confirmed the feature already exists end-to-end: admin-only UI in `LessonPost.vue`, drag-and-drop widget in `TopicOrderWidget.vue`, store action in `ui/stores/course.ts`, reorder endpoint in `routes/learn/course.php`, admin authorization plus `sort_order` persistence in `TopicController.php`, and relation ordering in `Lesson.php`. Remaining gaps are verification gaps: target DB must include the `topics.sort_order` migration, and `TopicsAndGroupsReorderTest.php` is still basic because it covers admin/non-admin but not cross-lesson payload rejection or round-trip fetch ordering.
- 2026-06-09: Investigated why `http://localhost:3000/Learn/Courses/24/lessons` may not visibly show topic drag-and-drop. Found that the page-level widget at `ui/pages/Learn/Courses/[id]/lessons.vue` is only `LessonOrderWidget` (lesson-level ordering). Topic ordering is not surfaced globally; it is nested in `LessonPost.vue` under the topics section and gated by `isAdmin && hasTopics`. The single-lesson route `ui/pages/Learn/Courses/[id]/lessons/[lessonId].vue` also reuses `LessonPost`, so the issue is likely presentation or data gating rather than a missing reorder implementation.
- 2026-06-09: Screenshot-based follow-up narrowed the issue further. Since the surfaced card in `LessonPost.vue` shows "ทั้งหมด 6 หัวข้อ", the lesson payload definitely includes topics. `TopicOrderWidget.vue`, `LessonOrderWidget.vue`, and `GroupOrderWidget.vue` all still share a dark-overlay visual treatment intended for use on gradient headers, but the topic widget is now mounted inside a light card body. This is likely why the expanded list looks empty. Recommended fix path: make `TopicOrderWidget.vue` theme-aware or convert it to neutral/light styles for this placement.

- 2026-06-09: Lesson progress widget order bug on `http://localhost:3000/Learn/Courses/24/lessons`. Root cause: `CourseMemberController::show()` and `memberProgress()` built lesson progress from `$course->courseLessons()->get()` without lesson ordering, so the widget consumed unsorted lessons. Fixed by reusing an ordered lesson query (`order`, then `created_at`) for lesson and lesson-assignment progress payloads.

- 2026-06-09: Lesson delete 500 investigation. Root cause: `CourseLessonController` type-hinted `CourseMediaService` without importing it. Fixed.

- 2026-06-09: Sort Order System — analysis + implementation. Added `sort_order` to topics, course_groups, academy_groups. All 3 migrations created with backfill. Backend (model relations, controllers with reorder + auto-assign on create, routes, resources) done for all 4 entities. UI widgets (TopicOrderWidget, GroupOrderWidget) created and integrated. CourseLessonReorderTest fixed + expanded. TopicsAndGroupsReorderTest created (4 basic tests). Remaining: run migrations on real DB, expand tests (cross-parent, auto-assign, round-trip, academy), create AcademyGroupOrderWidget UI.

- 2026-06-09: Re-verified entire Sort Order feature against codebase. All claimed implementations confirmed present. Updated latest-analysis.md with accurate file locations, line numbers, and remaining work items.

_(cleared 2026-06-08 — previous entries covered: course self-leave flow analysis, eligibility roster filtering, typing practice vocabulary migration, course member removal/leave workflow v2, lesson completion requirement before exercises)_

- 2026-06-09: Planning review for http://localhost:3000/Learn/Courses/24/groups (course group management page). Re-read ui/pages/Learn/Courses/[id]/groups/index.vue, ui/components/learn/course/GroupsList.vue, ui/components/learn/course/groups/GroupOrderWidget.vue, ui/components/GroupCard.vue, ui/stores/course.ts, ui/stores/courseGroup.ts, and the matching Laravel group controller/resource. Findings: core CRUD/join/reorder exists, but the page still behaves more like a public/community gallery than an instructor management workspace. The reorder widget uses low-contrast dark-overlay styles (g-white/5, 	ext-white/80) inside a light page, so it is hard to scan in light theme. The API and courseGroup store already expose ungrouped_members, but the groups page does not surface that backlog anywhere, which weakens the actual management flow. There is also a likely bug in join fee validation on the groups page: handleJoin() reads courseStore.course?.tuition_fees, while ui/stores/course.ts exposes currentCourse, not course. Plan direction: prioritize a lighter admin control bar, surface ungrouped-member backlog and capacity signals, align group cards with management tasks, and clean up the store/page contract before deeper UX polish.

- 2026-06-10: Planning pass for draft visibility/interaction lockdown across lessons, assignments, and quizzes. Re-verified the current backend/frontend flow before implementation. Findings: lessons already hide draft records in `CourseLessonController::index/show`, but lesson child-action endpoints in `LessonProgressController` and `LessonAnswerQuestionController` do not independently block student interaction with draft lessons if someone hits the API directly. Assignments are currently the weakest area: `CourseAssignmentController::index` returns all course assignments without filtering draft items for students, `show` does not verify course ownership or published status, and `AssignmentAnswerController::store` does not block submissions to draft assignments. Quizzes are partially protected: `CourseQuizController::index` filters `is_active` for students, but `show` still loads inactive quizzes for non-admins and `CourseQuizResultController::store/update` do not enforce active status, so a student could potentially start or finalize attempts against an inactive quiz by direct URL/API access. Frontend pages under `ui/pages/Learn/Courses/[id]/assignments/*` and `ui/pages/Learn/Courses/[id]/quizzes/*` largely trust backend responses and do not add a second safety layer for draft/locked items. Recommended implementation direction: centralize learner visibility checks per content type, enforce them in both list/detail and action endpoints, then add UI-level hiding/disabled states so behavior is consistent even before an API call fails. Verification plan for implementation: feature tests covering student/admin list access, draft detail access, and blocked submit/start/progress actions for each content type.

- 2026-06-10 (Plan v2 — refined): Re-grounded against actual code. Key constraints discovered that the v1 plan glossed over:
    1. **Status fields are inconsistent across content types** — Lesson uses string `publication_status` (`draft`|`published`), Assignment uses integer `status` (1=published, others=draft) with accessor `$assignment->is_published`, Quiz uses boolean-ish `is_active` (1/0). The central policy MUST normalize these per-model, not assume a single column name.
    2. **Assignment is polymorphic** (`assignmentable_type` ∈ {Course, Lesson, Topic}). A "published" assignment under a draft lesson must still be invisible to students — visibility cascades from parent. Same applies to assignments under a Topic whose Lesson is draft.
    3. **Topic visibility is currently undefined** — there is no draft flag on Topic. Decision needed: does Topic inherit lesson `publication_status` only, or get its own status? Recommend: inherit from Lesson for v1 (zero schema change), defer per-topic draft to v2.
    4. **`LessonAccessService::getAccessStatus()` already returns `access_status: 'unavailable'` for draft lessons** — the frontend layer for "soft-lock UI" largely exists for lessons. We should reuse this pattern (a single resolver returning `is_visible` + `is_locked` + `reason`) for assignment and quiz too, instead of inventing a new shape.
    5. **`CourseAssignmentController` uses `$course->courseAssignments()` in index but `$course->assignments()` in store** — two relations on Course. Confirm both resolve to the same morph scope before filtering, otherwise the filter on one won't protect the other.
    6. **Action endpoints to guard** (concrete list verified by grep):
        - Lesson: `LessonProgressController` (start/complete progress), `LessonAnswerQuestionController` (answer in-lesson questions), lesson unlock endpoints in `LessonAccessController` (already partially handled).
        - Assignment: `AssignmentAnswerController::store/update/destroy` (submissions), grading endpoints if exposed to students.
        - Quiz: `CourseQuizResultController::store` (start attempt), `::update` (finalize), `UserAnswerQuestionController` (per-question submit during attempt).
    7. **Route-model binding is bare** (`Route::patch('.../{quiz}', ...)`) — guard must live in controller or a FormRequest/Policy, not rely on route binding.
    8. **`show()` controllers must distinguish 403 vs 404** — convention in this repo is `abort(404)` to hide existence (used in `CourseQuizController::show` ownership check). Recommend 404 for students hitting drafts (matches existing pattern, leaks no info), 403 only for action endpoints (more honest for legitimate UI).

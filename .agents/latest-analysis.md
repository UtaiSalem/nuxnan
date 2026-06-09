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

- 2026-06-09: Lesson progress widget order bug on `http://localhost:3000/Learn/Courses/24/lessons`. Root cause: `CourseMemberController::show()` and `memberProgress()` built lesson progress from `$course->courseLessons()->get()` without lesson ordering, so the widget consumed unsorted lessons. Fixed by reusing an ordered lesson query (`order`, then `created_at`) for lesson and lesson-assignment progress payloads.

- 2026-06-09: Lesson delete 500 investigation. Root cause: `CourseLessonController` type-hinted `CourseMediaService` without importing it. Fixed.

- 2026-06-09: Sort Order System — analysis + implementation. Added `sort_order` to topics, course_groups, academy_groups. All 3 migrations created with backfill. Backend (model relations, controllers with reorder + auto-assign on create, routes, resources) done for all 4 entities. UI widgets (TopicOrderWidget, GroupOrderWidget) created and integrated. CourseLessonReorderTest fixed + expanded. TopicsAndGroupsReorderTest created (4 basic tests). Remaining: run migrations on real DB, expand tests (cross-parent, auto-assign, round-trip, academy), create AcademyGroupOrderWidget UI.

- 2026-06-09: Re-verified entire Sort Order feature against codebase. All claimed implementations confirmed present. Updated latest-analysis.md with accurate file locations, line numbers, and remaining work items.

_(cleared 2026-06-08 — previous entries covered: course self-leave flow analysis, eligibility roster filtering, typing practice vocabulary migration, course member removal/leave workflow v2, lesson completion requirement before exercises)_

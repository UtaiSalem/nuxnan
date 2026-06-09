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

- Date: 2026-06-09
- Branch: main
- Active Work: Sort Order System — implementation done, pending migration run + test expansion

## Known Blockers

- ⚠️ 3 migrations ยังไม่ได้ run บน DB จริง (ต้อง `php artisan migrate`):
  - `2026_06_09_141522_add_sort_order_to_topics_table`
  - `2026_06_09_151626_add_sort_order_to_course_groups_table`
  - `2026_06_09_161453_add_sort_order_to_academy_groups_table`

## Active Work

### Feature: Sort Order System — Topics, Course Groups, Academy Groups

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

**Academy Groups — ✅ Backend ครบ, ❌ ยังไม่มี UI:**
- ✅ Migration: `2026_06_09_161453_add_sort_order_to_academy_groups_table.php` — เพิ่ม `sort_order` + index `(academy_id, sort_order)` + backfill
- ✅ Model: `Academy::academyGroups()` → `orderBy('sort_order')->orderBy('id')` (line 99)
- ✅ Controller: `AcademyGroupController::store()` ตั้ง `sort_order = max + 1` (line 40)
- ✅ Controller: `AcademyGroupController::reorder()` — transaction wrap, verify ownership (line 225-275)
- ✅ Route: `PATCH /{academy}/groups/reorder` อยู่ก่อน `/{type}` wildcard (academy.php:67)
- ❌ **ไม่มี UI widget** — ยังไม่มี `AcademyGroupOrderWidget.vue`
- ❌ **ไม่มี `reorderAcademyGroups()` ใน store** — ต้องเพิ่ม

**Tests — ⚠️ มีบางส่วน ยังไม่ครบ:**
- ✅ `CourseLessonReorderTest.php` — 5 tests (admin reorder, non-admin 403, cross-course 422, empty 422, round-trip)
- ✅ `TopicsAndGroupsReorderTest.php` — 4 tests (admin/non-admin × topics/groups)
- ❌ **ขาด test**: cross-parent validation (topic จาก lesson อื่น → 422, group จาก course อื่น → 422)
- ❌ **ขาด test**: สร้าง topic/group ใหม่ → sort_order = max + 1 อัตโนมัติ
- ❌ **ขาด test**: reorder แล้ว fetch index → ลำดับตรงกัน (round-trip)
- ❌ **ขาด test**: academy group reorder (ยังไม่มีเลย)

---

### สิ่งที่เหลือทำ (เรียงตามลำดับแนะนำ)

```
1. รัน migrations บน DB จริง:
   php artisan migrate
   (ตรวจ 3 migrations: topics, course_groups, academy_groups)

2. เพิ่ม tests ที่ขาด:
   - TopicsAndGroupsReorderTest: cross-parent 422, auto sort_order on create, round-trip
   - AcademyGroupReorderTest: admin reorder, non-admin 403, cross-academy 422

3. Academy Groups UI (optional):
   - สร้าง AcademyGroupOrderWidget.vue (clone จาก GroupOrderWidget.vue)
   - เพิ่ม reorderAcademyGroups() ใน store ที่เหมาะสม
   - Integrate ใน page ที่แสดง academy groups
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

- _empty_

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

- 2026-06-09: Lesson delete 500 investigation. Root cause: `CourseLessonController` type-hinted `CourseMediaService` without importing it. Fixed.

- 2026-06-09: Sort Order System — analysis + implementation. Added `sort_order` to topics, course_groups, academy_groups. All 3 migrations created with backfill. Backend (model relations, controllers with reorder + auto-assign on create, routes, resources) done for all 4 entities. UI widgets (TopicOrderWidget, GroupOrderWidget) created and integrated. CourseLessonReorderTest fixed + expanded. TopicsAndGroupsReorderTest created (4 basic tests). Remaining: run migrations on real DB, expand tests (cross-parent, auto-assign, round-trip, academy), create AcademyGroupOrderWidget UI.

- 2026-06-09: Re-verified entire Sort Order feature against codebase. All claimed implementations confirmed present. Updated latest-analysis.md with accurate file locations, line numbers, and remaining work items.

_(cleared 2026-06-08 — previous entries covered: course self-leave flow analysis, eligibility roster filtering, typing practice vocabulary migration, course member removal/leave workflow v2, lesson completion requirement before exercises)_

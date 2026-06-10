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

- Date: 2026-06-10
- Branch: main
- Active Work: None — ready for next task

## Known Blockers

- ⚠️ Production: ตรวจสอบว่า `topics.sort_order` migration ถูกรันแล้ว (ถ้ายังไม่ได้รัน → `php artisan migrate --force`)

## Active Work

_(none)_

## Work Plan

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

_(consolidated 2026-06-10 — previous entries covered: sort order system implementation+verification, topic reorder UX discoverability, TopicOrderWidget theming mismatch, lesson sidebar widget wrong-course cache, lesson progress widget ordering, lesson delete 500 import fix, course group management page analysis, donation admin duplication analysis+planning, draft visibility analysis+planning v1+v2, topic create/update validation mismatch investigation, topic image edit leakage diagnosis, topic image delete placeholder+backend bugs, infinite scroll implementation for legacy donation page)_

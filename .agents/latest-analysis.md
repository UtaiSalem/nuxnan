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

## User Analysis Input (อ่านบทวิเคราะห์)

> **Trigger:** เมื่อผู้ใช้บอกว่า "อ่านบทวิเคราะห์" → Claude อ่าน section นี้แล้ว:
> 1. วิเคราะห์และตรวจสอบความถูกต้อง
> 2. ปรับปรุงและเพิ่มเติมสิ่งที่ขาด
> 3. วางแผนขั้นตอนการทำงานที่ชัดเจน
> 4. บันทึกแผนลงใน "Work Plan" ด้านล่าง

<!-- วางบทวิเคราะห์ / ความต้องการ / ปัญหา / เป้าหมายที่นี่ -->

(ยังไม่มีบทวิเคราะห์ — วางข้อความที่นี่แล้วบอก "อ่านบทวิเคราะห์")

---

## Work Plan (แผนการทำงาน)

### Feature: Remediation & Unified Eligibility

**สถานะ:** ✅ COMPLETED (2026-05-24)

---

#### ผลลัพธ์ที่ได้:

1.  **Route Alignment:** แก้ไข `remediation.vue` ให้ตรงกับ backend:
    *   ใช้ `/api/courses/{course}/remediation` แทน `/remediation/sessions`
    *   แมพฟิลด์ `title`, `start_at`, `remediation_score` ให้ตรงกับ DB
2.  **Backend bulkEnroll:** เพิ่ม method `bulkEnroll` ใน `RemediationController` และเพิ่ม parameter `$force` ใน `RemediationService` เพื่อให้ admin ลงทะเบียนนักเรียนได้โดยไม่ติดเงื่อนไขเวลา
3.  **Unified Eligibility Panel:** สร้างคอมโพเนนต์ `ExamEligibilityPanel.vue` และฝังในหน้า Quiz เพื่อรวมทุกช่องทางคืนสิทธิ์สอบ (Self, Points, Reading, Appeal) ไว้ในที่เดียว

---

#### ตรวจสอบความถูกต้อง:

- [x] `npm run build` ผ่านฉลุย
- [x] หน้า Remediation โหลดข้อมูลได้ถูกต้อง ไม่ขึ้น Error route mismatch
- [x] ปุ่ม "ลงทะเบียนนักเรียน" ใน Remediation ทำงานได้จริง (Bulk enroll)
- [x] หน้า Quiz แสดง Panel สีแดงเมื่อไม่มีสิทธิ์สอบ พร้อมปุ่มปลดล็อคตามเงื่อนไข

---

### Feature: Course Info Page — แก้ accordion "เนื้อหาบทเรียน"

**สถานะ:** ✅ COMPLETED (2026-05-24)

**ไฟล์ที่แก้ (เรียงตามลำดับ):**

| ลำดับ | ไฟล์ | การเปลี่ยน | สถานะ |
|---|---|---|---|
| 1 | `ui/pages/Learn/Courses/[id]/index.vue` | แก้ `.name` → `.title`, ใช้ `min_read` แทน `duration` | ✅ |
| 2 | `api/.../CourseActivityController.php` | เพิ่ม `withCount('topics')` + `with('topics:id,lesson_id,title,order,status')` | ✅ |
| 3 | `api/.../CourseResource.php` | map inline แทน raw `$this->courseLessons` | ✅ |
| 4 | `api/.../CourseActivityController.php` | แก้ `return back()` → `return response()->json(...)` ใน catch | ✅ |
| 5 | `ui/pages/Learn/Courses/[id]/index.vue` | UX accordion: link ไป lesson, empty state, จำนวนหัวข้อจริง | ✅ |

---

### Feature: Exam Retake Flow — Quiz-Level Link & Frontend UX

**สถานะ:** 🔄 IN PLANNING (2026-05-24)

---

#### การวิเคราะห์โค้ดปัจจุบัน (2026-05-24):

| สิ่งที่มีอยู่แล้ว | ไฟล์ | หมายเหตุ |
|---|---|---|
| `gradeEnrollment()` อัพเดท `final_grade` + `completion_status` | `RemediationService.php` | ✅ สมบูรณ์ |
| `unlockByRemediation()` ถูกเรียกเมื่อ passed | `RemediationService.php:244` | ✅ สมบูรณ์ |
| `GradeEditLog` บันทึกทุก grade change | `RemediationService.php:219` | ✅ สมบูรณ์ |
| `bulkEnroll`, `grade`, `bulkGrade`, `complete` endpoints | `RemediationController.php` | ✅ สมบูรณ์ |
| All unlock channels (self/points/reading/appeal/admin) | `ExamEligibilityController.php` | ✅ สมบูรณ์ |

| ช่องว่างที่ยังขาด | ผลกระทบ |
|---|---|
| `course_remediation_sessions` ไม่มี `quiz_id` | ไม่รู้ว่า session เพื่อ retake quiz ใด |
| Quiz controller ไม่มีตรรกะ "ผ่าน remediation → อนุญาต attempt เพิ่ม" | นักเรียนผ่านแล้วก็ยังเข้าสอบไม่ได้ |
| Frontend quiz page ไม่รู้สถานะ remediation enrollment | ไม่แสดง "มี remediation รอ" หรือ "ผ่านแล้ว retake ได้" |

---

#### แผน Phase 1 — Backend: DB + Retake Authorization

**ขั้นที่ 1** — Migration: เพิ่ม `quiz_id` ใน `course_remediation_sessions`
```
php artisan make:migration add_quiz_id_to_course_remediation_sessions_table
```
- เพิ่ม `quiz_id` (nullable, FK → `quizzes.id`, onDelete SET NULL)
- ใช้ nullable เพื่อ backward compatible กับ sessions ที่มีอยู่แล้ว

**ขั้นที่ 2** — `RemediationController::store()` + `update()`: รับ `quiz_id` ได้
- เพิ่ม `'quiz_id' => 'nullable|exists:quizzes,id'` ใน validation
- บันทึก `quiz_id` เข้า session

**ขั้นที่ 3** — `RemediationService::gradeEnrollment()`: เมื่อ passed + มี `quiz_id`
- เพิ่ม logic: ถ้า `$session->quiz_id` มีค่าและ `$enrollment->status === STATUS_PASSED`
  → เรียก `unlockQuizRetake($member, $session->quiz_id)`
- Method ใหม่ `unlockQuizRetake`: อัพเดท `quiz_attempts` ให้ student (เพิ่ม 1 attempt หรือ unlock flag)

**ขั้นที่ 4** — Quiz Controller: ตรวจสอบ retake eligibility
- ใน `show()` หรือ `attempt()` endpoint: เช็คว่า student มี `CourseRemediationEnrollment` ที่ `status = 'passed'` สำหรับ quiz นี้หรือไม่
- Return `can_retake: true` พร้อม `retake_source: 'remediation'`

---

#### แผน Phase 2 — Frontend: Quiz Page + Remediation Status

**ขั้นที่ 5** — `ui/pages/Learn/Courses/[id]/quizzes/[quizId]/index.vue`
- เพิ่ม API call: `GET /api/courses/{course}/remediation?quiz_id={quizId}` เพื่อดึง session ที่เชื่อมกับ quiz นี้
- แสดง badge/card ถ้า student ลงทะเบียน remediation อยู่: `"📋 คุณมีรอบแก้ตัวที่รอ — [ดูรายละเอียด]"`
- แสดง success state ถ้า `can_retake = true`: `"✅ ผ่านการแก้ตัวแล้ว — เริ่มสอบได้เลย"`

**ขั้นที่ 6** — `ui/pages/Learn/Courses/[id]/gradebook/remediation.vue`
- ตอนสร้าง session: เพิ่ม dropdown เลือก quiz ที่ต้องการ retake (filter เฉพาะ quiz ใน course)
- แสดง `quiz_id` ที่ผูกอยู่ใน session list

---

#### Verification Plan

| ขั้น | Test |
|---|---|
| 1 | `php artisan migrate` ไม่มี error; column `quiz_id` มีใน table |
| 2 | `POST /api/courses/{course}/remediation` รับ `quiz_id` ได้ |
| 3 | Grade enrollment → status `passed` → ตรวจสอบ quiz retake flag ใน DB |
| 4 | Quiz endpoint คืน `can_retake: true` สำหรับ student ที่ผ่าน remediation |
| 5 | Frontend quiz page แสดง remediation card ตาม status |
| 6 | `npm run build` ผ่าน |

---

### Feature: Compact Lesson Order Widget — Polish 3 จุด

**สถานะ:** ✅ COMPLETED

---

#### จุดที่ 1 ✅ — แก้ UX Flash เมื่อบันทึกลำดับ

**ผลลัพธ์:** เพิ่ม `silent` parameter ให้ `fetchLessons` เพื่อไม่ให้ `isLoading` เป็น true (ซึ่งจะไป trigger ContentLoader) เมื่อเป็นการอัพเดทข้อมูลหลังบ้านแบบเงียบๆ

#### จุดที่ 2 ✅ — ซ่อน Widget เมื่อมี ≤ 1 บทเรียน

**ผลลัพธ์:** เพิ่ม `v-if="lessons.length > 1"` ที่ wrapper ของ `LessonOrderWidget`

#### จุดที่ 3 ✅ — Collapsible Widget

**ผลลัพธ์:** เพิ่ม `isOpen` state, ทำให้ header คลิกได้เพื่อ toggle, และแสดง draggable เฉพาะเมื่อเปิดอยู่ พร้อมไอคอน chevron แสดงสถานะ

---

### Hotfix: Course Feeds 500 Error

**สถานะ:** ✅ COMPLETED (2026-05-24)

---

#### ปัญหา:
`GET /api/courses/{id}/feeds` คืนค่า 500 Internal Server Error เนื่องจากพยายามดึงและเรียงลำดับด้วยคอลัมน์ `order` ในตาราง `topics` ซึ่งไม่มีอยู่จริงในฐานข้อมูล

#### สิ่งที่แก้ไข:
1.  **CourseActivityController:** นำ `'order'` ออกจาก query select และเปลี่ยน `orderBy('order')` เป็น `orderBy('id')` สำหรับ topics
2.  **CourseResource:** นำการแมพฟิลด์ `'order'` ออกจาก topics array

---

## Current Snapshot

- Date: 2026-05-24
- Branch: main
- Repository: `C:\wamp64\www\nuxnan`
- Frontend: `ui/` Nuxt/Vue/TypeScript/Pinia/Tailwind/PrimeVue
- Backend: `api/nuxnanravel/` Laravel/PHP/JWT/MySQL/Reverb
- Current focus: Exam Retake Flow — Quiz-level link + Frontend retake UX
- Pending commit: งานสะสมวันนี้ (Lesson Widget, Curriculum Fixes, Remediation, Eligibility Panel)

## Active Work

| Scope | Owner | Status | Files | Notes |
| --- | --- | --- | --- | --- |
| Exam retake flow — Phase 1 (DB + Backend) | — | 📋 Ready to implement | migration, RemediationController, quiz controller | เพิ่ม quiz_id link + retake authorization |
| Exam retake flow — Phase 2 (Frontend) | — | 📋 Planned | remediation.vue, [quizId]/index.vue | แสดง remediation status ใน quiz page |

## Coordination Board

| Claim ID | Owner | Scope | Files or folders | Status | Handoff note |
| --- | --- | --- | --- | --- | --- |

## Decisions And Assumptions

- `AGENTS.md` is the tool-agnostic root entry point for all AI agents.
- `.agents/latest-analysis.md` is the live analysis and coordination board.
- `.agents/worklog.md` remains the cross-session handoff log.
- `CLAUDE.md` remains Claude-specific historical/project guidance.

## Open Questions

1. **Quiz Attempt Limit**: ตอนนี้ quiz มี `max_attempts` field ไหม? ถ้ามีแล้ว — remediation ควร add 1 attempt หรือ reset counter เลย?
2. **Remediation → Quiz link granularity**: 1 remediation session เชื่อมได้ 1 quiz หรือหลาย quiz? (สมมติ 1 ต่อ 1 ไปก่อน)
3. **Pending commit**: ควร commit งานที่สะสมไว้ก่อนเริ่ม Phase 1 เพื่อ isolate changes

## Analysis Timeline

### 2026-05-24 - Plan review and improvement (Exam Retake Flow)
- อ่านโค้ดจริงใน `RemediationService.php` และ `RemediationController.php` พบว่า grade update flow สมบูรณ์แล้ว (`gradeEnrollment` อัพเดท `final_grade`, `completion_status`, เรียก `unlockByRemediation`)
- ช่องว่างจริงคือ: ไม่มี `quiz_id` ใน `course_remediation_sessions` → ไม่รู้ว่า session เพื่อ retake quiz ไหน; quiz controller ไม่มีตรรกะ "ผ่าน remediation → อนุญาต attempt เพิ่ม"
- ปรับปรุงแผนจาก "TODO막연하게" เป็น 6 ขั้นตอนที่ชัดเจน (Phase 1: migration+backend, Phase 2: frontend)
- เพิ่ม Open Questions 3 ข้อ (quiz attempt model, granularity, commit timing)
- อัพเดท Active Work table ให้แสดง 2 phases ที่รอ implement

### 2026-05-24 - Lesson access status planning
- User asked for a plan to support international-style lesson visibility/access states: free, paid by points, paid by wallet/cash, and draft/hidden from students.
- Read-only inspection found existing lesson fields: `lessons.status` enum `0/1`, `point_tuition_fee`, `order`, `min_read`; `CourseLessonController` currently deducts points in `show()` without persistent per-user lesson purchase/access records.
- Relevant implementation areas: `lessons` migration/model/resource, `CourseLessonController`, `LessonForm.vue`, `LessonPost.vue`, course lesson list/detail pages, `course.ts`, `PointsService`, `WalletService`, and focused feature tests.
- Planning decision: separate publication status from access policy, add persistent lesson access/purchase records, return safe list payloads for students, and require an explicit unlock/purchase action before protected content is returned.
- Verification plan when implemented: feature tests for draft hiding, admin visibility, free access, point unlock idempotency, wallet unlock, and frontend build/checks.

### 2026-05-24 - Remediation & Unified Eligibility (DONE)
- Fixed route mismatch in `remediation.vue` (Backend uses `/api/courses/{course}/remediation`).
- Implemented `bulkEnroll` in `RemediationController` for admins.
- Created `ExamEligibilityPanel.vue` to unify unlock channels.
- Integrated panel into quiz detail page.

### 2026-05-24 - Plan review and status correction
- ตรวจสอบโค้ดจริงพบว่า Course Info Accordion Bug Fix (5 ขั้น) เสร็จสมบูรณ์แล้ว แต่ worklog ยังแสดงว่าค้างอยู่
- อัพเดท worklog.md: ย้าย Course Info Accordion Bug Fix ไป Done Today, ลบ Compact Lesson Widget Polish ออกจาก TODO
- อัพเดท latest-analysis.md: Work Plan ขั้นที่ 1-5 ทำเครื่องหมาย ✅ COMPLETED, Current Snapshot ชี้ไปที่งานถัดไปคือ Exam retake flow
- งานที่เหลือจริงๆ: Exam eligibility / retake unlock flow (วางแผนไว้ 2026-05-24, ยังไม่ implement)
- Pending: commit งานที่สะสมไว้ (หลาย modified/untracked files ใน git status)

### 2026-05-24 - Multi-channel exam access restoration plan
- User confirmed they want multiple exam-right restoration channels so students/users can choose one convenient option.
- Product direction: keep multiple channels, but present them through one unified "restore exam access" panel with option cards, eligibility hints, and a recommended/default path.
- Recommended channels to formalize: instant self unlock, points unlock, lesson-reading unlock, appeal/request review, teacher/admin unlock, and remediation/retake enrollment.
- Implementation plan should avoid separate ad hoc flows; use one API status payload to return available channels, pending requests, required actions, and completed unlock state.
- Keep auditability: every successful channel writes `exam_eligibility_overrides` plus `eligibility_audit_logs`.
- Next implementation scope: align frontend option panel, normalize channel labels/statuses, and fix remediation frontend/backend route mismatch before exposing retake enrollment as a student-facing channel.

### 2026-05-24 - Exam eligibility / retake unlock flow analysis
- User asked to analyze and simplify the process/channels for unlocking rights to take or retake exams.
- Current backend channels: attendance eligibility gate in `AttendanceEligibilityService`; student unlock via self, points, reading, appeal; teacher direct/bulk unlock; remediation sessions/enrollments after failed grades.
- Current frontend entry points: quiz detail/attempt pages enforce the gate; `MyProgressDetails.vue` exposes student unlock options; settings/gradebook eligibility expose admin settings and direct/bulk unlock.
- Main UX risk: too many separate choices for students; recommended simplification is a single "restore exam access" card with one recommended next action, with teacher one-click/bulk unlock as the fallback.
- Technical risk found: gradebook remediation page appears to call legacy/non-matching endpoints and field names (`/remediation/sessions`, `name`, `scheduled_at`, `resit`) while backend routes use `/api/courses/{course}/remediation`, `title`, `start_at`, `exam_retake`.
- Verification so far: read-only inspection only; no tests run and no functional code changed.

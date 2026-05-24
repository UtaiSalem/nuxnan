# Work Log — nuxnan project

ไฟล์นี้ใช้สำหรับส่งต่อ context ระหว่างที่บ้านและที่ทำงาน
**กฎ: ก่อนออกจากแต่ละที่ → อัพเดทไฟล์นี้แล้ว `git push`**
**กฎ: มาถึงที่ใหม่ → `git pull` แล้วอ่านไฟล์นี้ก่อนเริ่มงาน**

---

## สถานะปัจจุบัน (2026-05-24)

- **Done Today:**
  - **Hotfix: Course Feeds 500 Error**
    - Fixed `Unknown column 'order'` in `topics` query within `CourseActivityController`.
    - Removed stale `order` field mapping in `CourseResource` for topics.
  - **Course Info Accordion Bug Fix**
    - Fixed `.name` vs `.title` in `index.vue`.
    - Added `withCount('topics')` and `with('topics')` in `CourseActivityController`.
    - Implemented lightweight inline mapping in `CourseResource` to avoid N+1 queries.
    - Updated accordion template with direct lesson links and empty state.
  - **Remediation Route Alignment & Unified Eligibility**
    - Aligned `remediation.vue` with backend routes and fields.
    - Added `bulkEnroll` to `RemediationController` for admins.
    - Created and integrated `ExamEligibilityPanel.vue` for unified student access restoration.
  - **Lesson Order Widget Polish**
    - Fixed UX flash by adding `silent` mode to `fetchLessons` in `lessons.vue`.
    - Hidden reorder widget when lessons count <= 1.
    - Made `LessonOrderWidget.vue` collapsible (default closed).
- **In Progress:**
  - —
- **TODO:**
  - **Exam Retake Flow** (แผนละเอียดใน `latest-analysis.md`)
    - **Phase 1 — Backend** (ทำก่อน):
      1. Migration: เพิ่ม `quiz_id` (nullable FK) ใน `course_remediation_sessions`
      2. `RemediationController::store()/update()`: รับ `quiz_id` ใน validation
      3. `RemediationService::gradeEnrollment()`: เมื่อ passed + มี `quiz_id` → unlock quiz retake
      4. Quiz Controller: return `can_retake: true` ถ้า student ผ่าน remediation ที่เชื่อมกับ quiz นี้
    - **Phase 2 — Frontend** (หลัง Phase 1):
      5. `[quizId]/index.vue`: แสดง remediation status card (pending/passed)
      6. `remediation.vue`: dropdown เลือก quiz ที่ต้องการ retake ตอนสร้าง session
    - **หมายเหตุ**: grade update (`final_grade`, `completion_status`) มีอยู่แล้วใน `RemediationService.php` — ไม่ต้องแตะ
- **Pending Commit:**
  - accumulation of changes from today (Lesson Widget, Curriculum Fixes, Remediation, Eligibility).

---

## ประวัติการทำงาน (Timeline)

- **Remediation & Unified Eligibility** (2026-05-24)
  - Fixed route mismatch in `remediation.vue` (Backend uses `/api/courses/{course}/remediation`).
  - Implemented `bulkEnroll` in `RemediationController` for admins.
  - Created `ExamEligibilityPanel.vue` to unify unlock channels.
  - Integrated panel into quiz detail page.
- **Lesson Order Widget Polish** (2026-05-24)
  - Fixed UX flash by adding `silent` mode to `fetchLessons` in `lessons.vue`.
  - Hidden reorder widget when lessons count <= 1.
  - Made `LessonOrderWidget.vue` collapsible (default closed).
  - Verified with `npm run build` (warnings expected, no core errors).
- **Lesson Drag-and-Drop Reordering** (2026-05-24)
  - Implemented compact admin-only ordering widget for lessons.
  - Added bulk reorder endpoint `PATCH /api/courses/{course}/lessons/reorder`.
  - Improved lesson ordering logic to handle nulls and provide fallback.
  - Verified with backend feature tests (`CourseLessonReorderTest.php`).
- **Cross Math Enter key** (2026-05-23)
  - Added Enter key support for next level in Cross Math game.
  - Added `aria-keyshortcuts="Enter"` to the next-level button.

---

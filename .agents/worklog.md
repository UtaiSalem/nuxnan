# Work Log — nuxnan project

ไฟล์นี้ใช้สำหรับส่งต่อ context ระหว่างที่บ้านและที่ทำงาน
**กฎ: ก่อนออกจากแต่ละที่ → อัพเดทไฟล์นี้แล้ว `git push`**
**กฎ: มาถึงที่ใหม่ → `git pull` แล้วอ่านไฟล์นี้ก่อนเริ่มงาน**

---

## สถานะปัจจุบัน (2026-05-24)

- **Done Today:**
  - **Lesson Access System (Full Feature)**
    - Migration: เพิ่ม `publication_status`, `access_type`, `money_tuition_fee` ใน `lessons` table (migrate ข้อมูลเดิมด้วย)
    - Migration: สร้าง `lesson_accesses` table สำหรับ persistent unlock records
    - Model: `LessonAccess.php` — TYPE/STATUS constants, relations
    - Service: `LessonAccessService.php` — getAccessStatus, unlockWithPoints, unlockWithMoney, grantFreeAccess
    - Controller: `CourseLessonController` — unlock endpoint, publication_status filtering, LessonAccessService injection
    - Resource: `LessonResource` — access status fields, locked/unlocked data separation, display_order
    - Route: `POST /{lesson}/unlock` ลงทะเบียนแล้ว
  - **Lesson Order Gap Fix**
    - `store()`: default order → `max(order)+1` (ไม่มี gap ตั้งแต่ต้น)
    - `reorder()`: payload ใหม่ `{lessons: [id1,id2,...]}` — backend กำหนด 1..N เอง (ไม่มี gap)
    - `index()`: admin → `get()` ทุก lesson (ไม่ paginate); student → `paginate()`
  - **Lesson display_order**
    - Backend คำนวณ `display_order` (rank ใน published lessons เท่านั้น, 1-indexed, ไม่มี gap) ใน `index()` และ `show()`
    - `LessonResource` ส่ง `display_order` ทุก response
    - `LessonPost.vue`: student ใช้ `lesson.display_order` จาก backend; admin ใช้ `lesson.order`
  - **LessonPost.vue — Badge Overlap Fix**
    - จัดโครงสร้าง overlay ใหม่: left group (publication/order/time badges) + right group (access badge + admin actions)
    - ไม่มี overlap อีกต่อไป ทุก viewport
  - **LessonOrderWidget.vue — Bug Fixes**
    - แก้ status badge: `lesson.status` → `lesson.publication_status`
    - reorder payload: `[id1,id2,...]` (ordered IDs only)
  - **store/course.ts**: `reorderLessons(courseId, lessonIds: number[])`
  - **Exam Retake Flow (Phase 1)**
    - Migration: Added `quiz_id` to `course_remediation_sessions`
    - Model/Controller: Updated `CourseRemediationSession` + `RemediationController`
    - Quiz Detail: Returns student's remediation status for the specific quiz
    - Frontend: remediation_status card in Quiz Page + Quiz dropdown in Remediation admin form
  - **Other fixes**: Course feeds 500 error, Course info accordion, Remediation route alignment, Unified eligibility panel, Lesson order widget polish

- **In Progress:**
  - —

- **TODO:**
  - **Exam Retake Flow Phase 2 — Remaining Logic** (แผนละเอียดใน `latest-analysis.md`)
    - `RemediationService::gradeEnrollment()`: เมื่อ passed + มี `quiz_id` → unlock quiz retake attempt
    - Quiz Controller: return `can_retake: true` ถ้า student ผ่าน remediation ที่ผูกกับ quiz นี้
  - **Commit งานสะสม** — มี modified/untracked files จำนวนมาก ควร commit ก่อนเริ่มงานใหม่

- **Pending Commit:**
  - Lesson Access System (migrations, model, service, controller, resource)
  - Lesson Order Gap Fix + display_order
  - LessonPost.vue badge overlap fix
  - LessonOrderWidget.vue bug fixes
  - Exam Retake Flow Phase 1
  - All previous fixes from today

---

## ประวัติการทำงาน (Timeline)

- **Lesson Access System + Order Gap + display_order** (2026-05-24)
  - Full access type system (free/points/money) with persistent unlock records
  - Order gap fixed: new lessons auto-append, reorder normalizes 1..N
  - display_order: backend-computed sequential number for published lessons (no gap for students)
  - Build verified: `npm run build` ✅, `php artisan migrate` ✅
- **Exam Retake Flow Phase 1** (2026-05-24)
  - quiz_id link in remediation sessions, remediation status in quiz page
- **Remediation & Unified Eligibility** (2026-05-24)
  - Fixed route mismatch, bulkEnroll, ExamEligibilityPanel.vue
- **Lesson Order Widget Polish** (2026-05-24)
  - Silent mode, hide when ≤1 lessons, collapsible widget
- **Lesson Drag-and-Drop Reordering** (2026-05-24)
  - Compact admin widget, bulk reorder endpoint, feature tests
- **Cross Math Enter key** (2026-05-23)

---

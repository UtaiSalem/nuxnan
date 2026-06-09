# Work Log — nuxnan project

ไฟล์นี้ใช้สำหรับส่งต่อ context ระหว่างที่บ้านและที่ทำงาน
**กฎ: ก่อนออกจากแต่ละที่ → อัพเดทไฟล์นี้แล้ว `git push`**
**กฎ: มาถึงที่ใหม่ → `git pull` แล้วอ่านไฟล์นี้ก่อนเริ่มงาน**

---

## สถานะปัจจุบัน (2026-06-10)

### งานที่เพิ่งเสร็จสิ้น — Verified & Tested

- **Done:** Draft Visibility & Interaction Lockdown (Lesson/Assignment/Quiz)
    - Centralized `ContentVisibilityService` with cascade rule (Assignment under draft Lesson/Topic = hidden).
    - Read path: filter drafts in `CourseLessonController`, `CourseAssignmentController`, `CourseQuizController` index/show.
    - Action path: `assertVisibleOrFail` guards in `LessonProgressController`, `LessonAnswerQuestionController`, `AssignmentAnswerController`, `CourseQuizResultController`, `UserAnswerQuestionController`.
    - Testing: 15/15 tests passed (11 unit + 4 feature).
- **TODO:** —
- **Pending Commit:** — (committed)

---

## งานที่เสร็จแล้ว (สรุปรวม)

| วันที่ | งาน | สถานะ |
|--------|------|-------|
| 2026-06-10 | Draft Visibility & Interaction Lockdown (Lesson/Assignment/Quiz) | ✅ Done |
| 2026-06-09 | Sort Order System (Topics, Course Groups, Academy Groups) | ✅ Done |
| 2026-06-09 | Academy Group Reorder UI Implementation | ✅ Done |
| 2026-06-08 | Lesson Completion Requirement (บังคับอ่านก่อนทำแบบฝึกหัด) | ✅ Done |
| 2026-06-08 | Course Member Removal/Leave Workflow v2 | ✅ Done |
| 2026-06-07 | Eligibility Roster Filtering + Backlog Cleanup | ✅ Done |
| 2026-06-06 | Course Completion Workflow v2 | ✅ Done |
| 2026-06-06 | User Management & Username Integration | ✅ Done |
| 2026-06-03 | School Department Management (Codex Tasks) | ✅ Done |
| 2026-05-31 | Universal QR Scanner | ✅ Done |
| 2026-05-31 | School Management System Phase 6 | ✅ Done |
| 2026-05-29 | User Profile Fixes (6 Phases + Testing) | ✅ Done |
| 2026-05-29 | Exam Retake Flow Phase 2 | ✅ Done |
| 2026-05-25 | Typing Game Expansion + Course Point System | ✅ Done |
| 2026-05-25 | Lesson Access System (free/points/money) | ✅ Done |
| 2026-05-25 | Lesson Order Gap Fix + display_order | ✅ Done |
| 2026-05-25 | Exam Retake Flow Phase 1 | ✅ Done |
| 2026-05-24 | Lesson Drag-and-Drop Reordering | ✅ Done |
| 2026-05-24 | Remediation & Unified Eligibility | ✅ Done |

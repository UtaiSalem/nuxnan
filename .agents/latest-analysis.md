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

> Trigger: when the user says "อ่านบทวิเคราะห์", read this section, verify it against the codebase, improve or correct it, make a clear work plan, and record that plan below.

### 2026-06-06 — Course Completion / Grading verified gaps

แผนเดิม (Timeline 2026-06-06 Course Completion Plan Review) ถูกทิศ ยืนยันจากการอ่าน source:

- **Syntax error จริง**: `CourseCompletionController.php:375` มี `;` และ `}` เกินท้ายไฟล์ → `php -l` fail
- **Transcript sync risk จริง**: `CourseGradingService.php:309` ใช้ `Student::where('user_id')` อย่างเดียว ต้องเพิ่ม `academy_id` scope
- **`semester_id` ยังเป็น `null`**: `CourseGradingService.php:319`
- **`Semester` ยังไม่มี helper**: `Semester.php:13`

**Gap เพิ่มเติม (ใหม่)**:
- `CourseCompletionController::finalizeGrades()` (`:206`) อนุญาตเฉพาะ status `grading`
- แต่ `CourseGradingService` summary (`:474`) ระบุว่า finalize ได้ทั้ง `grading` และ `published`
- ผลกระทบ: workflow `grading → publish → student accept → finalize` จะถูก block หลัง publish

---

## Work Plan v2 (2026-06-06) — refined after source verification

### Severity legend
- 🔴 **Critical** (ระบบพัง / syntax fail / ข้อมูลสูญหาย)
- 🟠 **High** (Data integrity / สเตตัสค้าง / ข้อมูลไม่ตรงกัน)
- 🟡 **Medium** (UX / ข้อมูลรั่วไหล / ขาด Helper)
- 🔵 **Low** (Test coverage / UI Polish)

---

### Phase 1 — 🔴 Blocker Fixes
- **[CC-FIX-1] Syntax Error**: ลบ `; } }` ส่วนเกินท้ายไฟล์ `CourseCompletionController.php` (line 375-377)
- **[CC-FIX-2] Finalize Workflow (Bug A & B)**: 
  - แก้ guard ใน controller ให้ยอมรับสถานะ `published` (ปัจจุบันอนุญาตแค่ `grading` ทำให้กด finalize ไม่ได้หลังประกาศเกรด)
  - แก้ loop ใน `CourseGradingService::finalizeGrades` ให้รองรับสมาชิกที่ยังไม่ได้กดยอมรับ หรือปรับเงื่อนไขให้ครอบคลุม (ปัจจุบัน loop เฉพาะ `pending_acceptance` ทำให้หาก finalize ข้ามขั้นตอน transcript จะว่างเปล่า)

### Phase 2 — 🟠 Data Integrity & Sync
- **[CC-SYNC-1] Academy-scoped Student Lookup**: แก้ `syncToTranscripts` ให้ระบุ `academy_id` เมื่อค้นหา `Student` ป้องกันข้อมูลข้ามโรงเรียน
- **[CC-SYNC-2] Stale Transcript on Override**: เพิ่มการเรียก `syncToTranscripts()` ทันทีเมื่อมีการ `overrideGrade` หลังปิดเกรดแล้ว
- **[CC-SYNC-3] Reopen Logic**: จัดการถอนการประกาศเกรดใน Transcript (is_published = false) เมื่อมีการเรียก `reopenGrading`

### Phase 3 — 🟡 Privacy & Helpers
- **[CC-PRIV-1] Draft Grade Leak**: ตัดสถานะ `'grading'` ออกจาก `can_view` ใน `getMyGrade()` เพื่อไม่ให้นักเรียนเห็นเกรดร่างก่อนครูประกาศ
- **[CC-HELP-1] Semester Mapping**: พัฒนาวิธีระบุภาคเรียนให้รายวิชา (ดู fallback options)

---

### 🛠 Fallback Options สำหรับการออกแบบ

**ทางเลือกสำหรับ Step 3: Semester Mapping (ระบุเทอมให้วิชา)**
- *Option A*: เพิ่ม column `semester_id` ลงในตาราง `courses` (ชัดเจนที่สุด แต่ต้องทำ migration)
- *Option B*: Resolve runtime ผ่าน helper `Semester::getCurrentForCourse($course)` โดยอิงจาก `start_date` หรือ text fields (เสี่ยง mismatch)
- *Option C*: ใช้เทอมที่เป็น `is_current = true` ของ Academy ในขณะที่กดปิดเกรด (ง่าย แต่อาจผิดถ้าปิดเกรดล่าช้าข้ามเทอม)
- **แนะนำ: Option A** เพื่อความแม่นยำถาวร

**ทางเลือกสำหรับ Step 5: Transcript Sync (จัดการ Override/Reopen)**
- *Option A*: เรียก `syncToTranscripts` ตรงๆ ใน controller/service methods (ชัดเจน ไล่โค้ดง่าย)
- *Option B*: ใช้ Eloquent Model Observer บน `CourseMember` คอยดักจับการเปลี่ยนแปลง (สะอาดกว่าในแง่โครงสร้าง แต่อาจ debug ยาก)
- **แนะนำ: Option A** สำหรับ v2 เพื่อความรวดเร็วและตรวจสอบได้ง่าย

---

### 🧪 PHPUnit Regression Tests
1. `test_finalize_after_publish_succeeds`: ยืนยันว่า workflow `grading -> publish -> finalize` ทำงานได้จริง
2. `test_finalize_promotes_all_eligible_members`: ตรวจสอบ Bug B ว่าสมาชิกทุกคนได้รับเกรดสุดท้ายแม้ไม่ได้กด accept
3. `test_override_grade_updates_transcript`: ยืนยันว่าการแก้ไขเกรดหลังปิดวิชาส่งผลถึง Transcript ทันที
4. `test_student_cannot_view_draft_during_grading`: ป้องกันการรั่วไหลของเกรดร่าง

---

### 🌿 Commit & Branch Strategy
- **Branch**: `fix/course-completion-workflow`
- **Commits**: Atomic commits แยกตาม Step (e.g., `fix(backend): resolve syntax error`, `fix(grading): fix finalize loop condition`)

---

### ❓ Open Questions (ต้อง Confirm ก่อนเริ่ม)
1. **Semester Mapping**: ยืนยันให้เพิ่มฟิลด์ `semester_id` ในตาราง `courses` เลยหรือไม่? (Option A)
2. **Reopen Behavior**: เมื่อกด Reopen Grading ต้องการให้ Transcript (ตาราง `course_grades`) ซ่อนเกรดนั้นทันที (is_published = false) ใช่หรือไม่?
3. **Manual Acceptance**: หากนักเรียนไม่กด "ยอมรับเกรด" (Accept) ก่อนที่ครูจะกด Finalize ระบบควร Auto-accept ให้ทุกคนเลยใช่หรือไม่? (ปัจจุบัน Bug B ทำให้คนกลุ่มนี้ถูกข้ามไป)

---

## Current Snapshot

- Date: 2026-06-06
- Branch: main
- Active Work: เสร็จสิ้นการแก้ไขระบบ User Management และ Username Integration

## Active Work

**✅ เสร็จแล้ว (2026-06-06)**

| ไฟล์ | การเปลี่ยนแปลง |
|------|----------------|
| `AdminController.php` | แก้ไข 422 error, ปรับปรุงการจัดการ username, เพิ่ม bulkDelete |
| `AdminRoleController.php` | เปลี่ยนชื่อ role จาก `USER` เป็น `STUDENT` เพื่อให้ตรงกับ DB |
| `UsersDataTable.php` | ปรับปรุง badge color และ role mapping |
| `UserResource.php` | เพิ่มฟิลด์ที่ขาดหาย และตั้งค่า default role เป็น `student` |
| `users/index.vue` | เพิ่ม Edit User Modal แบบ inline, ปรับปรุง role filter |
| `users/[id]/edit.vue` | รองรับ username field และแก้ปัญหา validation |
| `users/create.vue` | ปรับปรุงการสร้าง user ให้รองรับ username และ role `student` |
| `auth.ts` store | ปรับปรุงความเสถียรของ auth state และ registration flow |
| `2026_06_05_..._add_username_column_to_users.php` | เพิ่มคอลัมน์ username ในตาราง users |

## Coordination Board

- (ว่าง)

## Decisions And Assumptions

- ระบบใช้ role `STUDENT` แทน `USER` เป็นค่าเริ่มต้นสำหรับผู้ใช้ทั่วไป
- `username` ถูกใช้เป็นฟิลด์หลักในการระบุตัวตนและแก้ไขปัญหา validation ใน admin panel
- การแก้ไข user ในหน้า index ใช้ modal เพื่อความรวดเร็ว (UX consistency)

## Open Questions

- (ว่าง)

## Analysis Timeline

### 2026-06-06 - Resolved Admin User Management 422 & Username Integration
- **Implemented**: Username column migration, backend validation logic, and frontend UI integration.
- **Fixed**: 422 Unprocessable Content error by aligning role names (`USER` -> `STUDENT`) between frontend and backend.
- **Enhanced**: Added inline Edit Modal in users list for better UX.
- **Verified**: PHP lint passed, git push successful.

### 2026-06-06 - Course Completion Plan Review
- **Findings**: User plan is directionally correct. `CourseCompletionController.php` has trailing stray `; } }` and `php -l` fails with unmatched `}`. `CourseGradingService::syncToTranscripts()` uses only `user_id` for `Student` and writes `semester_id => null`.
- **Additional risk**: `CourseCompletionController::finalizeGrades()` only accepts `grading`, while summary/service allow finalization from `grading` or `published`, so the publish -> accept -> finalize workflow may be blocked.
- **Likely files**: `api/nuxnanravel/app/Http/Controllers/Api/CourseCompletionController.php`, `api/nuxnanravel/app/Services/CourseGradingService.php`, `api/nuxnanravel/app/Models/Semester.php`, focused backend tests if existing factories support them.
- **Verification plan**: `php -l` changed PHP files, focused route/workflow check, and targeted PHPUnit coverage for transcript sync with multi-academy student records if feasible.

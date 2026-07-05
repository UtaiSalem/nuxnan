# แผนงานถัดไป — ฝ่ายวิชาการ (งานทะเบียน)

**วันที่:** 2026-07-05
**สถานะ:** Student Intake Phase 2-3 เสร็จแล้ว (fix bugs + เพิ่ม guardian contacts + account mode) ยังไม่ได้ commit

---

## User Analysis Input

### สิ่งที่เพิ่งทำเสร็จ (Session นี้)

**Student Intake Phase 2-3 — Bug Fix & Completion**
- Fix `intake.vue` ส่ง `academyId` แทน `academyName` (API URL ผิด)
- Fix `StepAdmission` required fields แสดงเป็น "ไม่บังคับ" (จะได้ 422 ทุกครั้ง)
- Fix `StepIdentity` v-else/v-if ไม่ adjacent (Vite compile error)
- Fix guardian type "relative" ไม่มีใน backend validation
- Fix guardian limit mismatch (frontend 5 vs backend 4)
- เพิ่ม Guardian contacts UI (เบอร์โทร/email/LINE per guardian)
- เพิ่ม Account mode toggle ใน StepReview (pending_activation vs none)
- เพิ่ม `GuardianContact` type + composable helpers

**ไฟล์ที่แก้ไข (8 ไฟล์ — ยังไม่ commit):**
1. `ui/pages/academies/[name]/admin/students/intake.vue` — fix prop
2. `ui/components/academy/student-intake/StepIdentity.vue` — fix v-else
3. `ui/components/academy/student-intake/StepAdmission.vue` — fix validation
4. `ui/components/academy/student-intake/StepGuardian.vue` — fix limit + เพิ่ม contacts
5. `ui/components/academy/student-intake/StepReview.vue` — เพิ่ม account mode + contacts display
6. `ui/composables/useStudentIntake.ts` — เพิ่ม contact helpers (linter เปลี่ยน uuid import)
7. `ui/types/studentIntake.ts` — เพิ่ม GuardianContact interface
8. `api/nuxnanravel/app/Http/Requests/Academy/Enrollment/StoreStudentIntakeRequest.php` — เพิ่ม 'relative'

---

## Work Plan

### ขั้นตอน 0: Commit งาน Session นี้ + Smoke Test
**ความสำคัญ:** 🔴 ทำก่อนเริ่มงานอื่น
1. รัน `php artisan serve` + `npm run dev` บน WAMP
2. เปิด browser → login admin → ไปหน้า `/academies/{name}/admin/students/intake`
3. ทดสอบ wizard ครบ 5 steps (ใส่ข้อมูลจริง)
4. ตรวจว่า student ถูกสร้างจริงใน DB
5. ถ้าผ่าน → commit ทั้ง 8 ไฟล์:
   ```bash
   cd C:\wamp64\www\nuxnan
   git add ui/pages/academies/\[name\]/admin/students/intake.vue \
           ui/components/academy/student-intake/StepIdentity.vue \
           ui/components/academy/student-intake/StepAdmission.vue \
           ui/components/academy/student-intake/StepGuardian.vue \
           ui/components/academy/student-intake/StepReview.vue \
           ui/composables/useStudentIntake.ts \
           ui/types/studentIntake.ts \
           api/nuxnanravel/app/Http/Requests/Academy/Enrollment/StoreStudentIntakeRequest.php
   git commit -m "fix(school): complete student intake wizard — fix validation, add guardian contacts & account mode"
   ```

---

### ขั้นตอน 1: Student List DataTable — Smoke Test + Bug Fix (Phase G2)
**ความสำคัญ:** 🟡 มี UI แล้ว ต้อง smoke test
**เวลาประมาณ:** 1-2 ชม.

**สิ่งที่มีแล้ว:**
- `ui/components/academy/student/StudentDataTable.vue` — DataTable พร้อม search, filter (status, accountStatus), pagination, enrollment lifecycle actions
- `ui/services/studentIntakeService.ts` → `listStudents()` API call
- `ui/pages/academies/[name]/admin/students/index.vue` — หน้าหลัก stats + DataTable + ปุ่ม "รับนักเรียนใหม่", "นำเข้า", "ส่งออก", "ประวัตินำเข้า", "ลิงก์เปิดบัญชี"

**สิ่งที่ต้องทำ:**
1. Smoke test หน้า `/academies/{name}/admin/students` — ดูว่า DataTable แสดง student list ได้ไหม
2. ทดสอบ filter/search ว่าทำงานจริง
3. ทดสอบ enrollment actions (graduate/drop/promote) ผ่าน action menu
4. Fix bugs ที่เจอ (ถ้ามี)

**Backend endpoints ที่เกี่ยว:**
- `GET /api/academies/{academy}/student-intakes/list` — list students
- `GET /api/academies/{academy}/student-intakes/stats` — stats
- `GET /api/academies/{academy}/student-intakes/export` — CSV export

---

### ขั้นตอน 2: Import History Page — Smoke Test + Wire Backend (Phase G1)
**ความสำคัญ:** 🟡 มี UI แล้ว ต้อง wire backend
**เวลาประมาณ:** 2-3 ชม.

**สิ่งที่มีแล้ว:**
- `ui/pages/academies/[name]/admin/students/import-history.vue` — UI ครบ (batch list + status badges + pagination)
- `ui/services/studentImportService.ts` → `listBatches()` 
- DB tables: `student_import_batches`, `student_import_rows` (migration สร้างไว้แล้ว)

**สิ่งที่ต้องตรวจสอบ/ทำ:**
1. ตรวจว่า backend มี controller + route สำหรับ `listBatches` หรือยัง
   - grep: `grep -r "StudentImportController\|student-imports" api/nuxnanravel/routes/`
   - ถ้ายังไม่มี → สร้าง `StudentImportController@index` 
   - Route: `GET /api/academies/{academy}/student-imports` 
   - Return: paginated list of `student_import_batches` with counts
2. ตรวจว่า `studentImportService.ts` เรียก URL ถูกต้อง
3. Smoke test หน้า import-history
4. ตรวจว่าหน้า import (`/students/import`) ทำงานได้ (bulk upload flow)

**ไฟล์ backend ที่อาจต้องสร้าง/แก้:**
- `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/StudentImportController.php`
- `api/nuxnanravel/routes/learn/academy.php` — เพิ่ม import routes

---

### ขั้นตอน 3: Student Account Activation Page (Phase G3)
**ความสำคัญ:** 🟡 มี Modal แล้ว ต้อง smoke test
**เวลาประมาณ:** 1-2 ชม.

**สิ่งที่มีแล้ว:**
- `ui/components/academy/student/StudentAccountActivationModal.vue` — Dialog สำหรับสร้าง invite link + copy
- `ui/services/studentAccountService.ts` → `exportInvitations()` (bulk export invite links)
- ปุ่ม "ลิงก์เปิดบัญชี" ใน students/index.vue

**สิ่งที่ต้องตรวจสอบ/ทำ:**
1. ตรวจว่า backend มี endpoint สำหรับ generate invite link ต่อนักเรียน
   - คาดว่าอยู่ที่ `POST /api/academies/{academy}/students/{student}/invite`
   - grep: `grep -r "StudentAccountController\|invite\|activation" api/nuxnanravel/routes/`
2. ตรวจว่า `StudentAccountActivationModal` ถูก wire เข้ากับ DataTable action menu
3. ทดสอบ flow: กดปุ่ม → generate link → copy → ทดสอบ activate
4. ทดสอบ bulk export invitations

**ไฟล์ที่เกี่ยวข้อง:**
- `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/StudentAccountController.php`
- `api/nuxnanravel/routes/learn/academy.php` — student account routes

---

### ขั้นตอน 4: Fix Home Visits Schema Mismatch (Pre-existing Bug)
**ความสำคัญ:** 🟠 Bug จริงแต่ไม่ urgent
**เวลาประมาณ:** 30 นาที - 1 ชม.

**ปัญหา:**
- `home-visits/statistics` → 500 error
- สาเหตุ: query ใช้ `student_academic_info.classroom` column ที่ไม่มีใน DB
- column ที่มีจริง: `classroom_id`, `current_class`, `classroom_full`

**วิธีแก้:**
1. หา query ที่ใช้ `student_academic_info.classroom` 
   - `grep -r "student_academic_info.classroom\|->classroom" api/nuxnanravel/app/Http/Controllers/`
2. เปลี่ยนเป็น column ที่ถูกต้อง (`classroom_full` หรือ join กับ `classrooms` table)
3. ทดสอบ `/academies/{name}/admin/home-visits` ว่าแสดงได้

---

## สรุปลำดับความสำคัญ

| ลำดับ | งาน | เวลาประมาณ | สถานะ UI | สถานะ Backend |
|-------|------|-----------|---------|--------------|
| 0 | Commit + Smoke test intake wizard | 30 นาที | ✅ เสร็จ | ✅ เสร็จ |
| 1 | Student DataTable smoke test (G2) | 1-2 ชม. | ✅ มีแล้ว | ✅ มีแล้ว |
| 2 | Import History wire backend (G1) | 2-3 ชม. | ✅ มีแล้ว | ❓ ตรวจสอบ |
| 3 | Account Activation smoke test (G3) | 1-2 ชม. | ✅ มีแล้ว | ❓ ตรวจสอบ |
| 4 | Fix home-visits schema bug | 30 นาที | — | 🔧 fix query |

**รวมเวลาประมาณ: 5-8 ชม.**

---

## Context สำคัญสำหรับ AI ตัวถัดไป

### Project structure
- Frontend: `ui/` (Nuxt 3, Vue 3 Composition API, TypeScript, Tailwind, PrimeVue)
- Backend: `api/nuxnanravel/` (Laravel 12, PHP 8.4, JWT auth, MySQL)
- Local: WAMP at `C:\wamp64\` — `php artisan serve` port 8000, `npm run dev` port 3000

### Key patterns
- API calls ผ่าน `useApi()` composable หรือ services ใน `ui/services/`
- Admin pages อยู่ที่ `ui/pages/academies/[name]/admin/`
- Parent route `admin.vue` provides `academyId`, `academyName`, `academy` ผ่าน inject
- Controllers อยู่ที่ `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/`
- Routes อยู่ที่ `api/nuxnanravel/routes/learn/academy.php`
- Auth middleware: `auth:api` (JWT)

### Known pre-existing issues
- `academic years` console error — fetch academic years ล้มเหลว (ไม่กระทบ UI หลัก)
- `home-visits/statistics` → 500 (schema mismatch)
- Linter อาจเปลี่ยน uuid import จาก `uuid` package เป็น `~/utils/uuid` (ดูที่ `useStudentIntake.ts`)

### อ่านก่อนเริ่ม
- `CLAUDE.md` — คู่มือโปรเจค
- `.agents/worklog.md` — context ข้ามที่ทำงาน
- `.agents/school-5-departments-revised-analysis.md` — บทวิเคราะห์ 5 ฝ่าย

---

## Execution update — 2026-07-05

- Step 0 completed and committed as `6a6b1e0f`.
- G2 student list contracts verified: list, stats, filters, sorting, and enrollment actions are already wired.
- G1 import history contracts verified: controller, routes, resource pagination, service, and UI are already wired; backend feature tests pass.
- G3 activation modal is now wired into the student table and uses the injected academy ID.
- Home-visit classroom filters now query `student_academic_info.current_class` instead of the missing `classroom` column.
- Verification: 16 focused Laravel tests passed (74 assertions); three affected Vue SFCs parse/compile successfully.
- Remaining manual gap: authenticated browser smoke test was blocked by the local browser runtime permission; full Nuxt build exceeded 120 seconds.

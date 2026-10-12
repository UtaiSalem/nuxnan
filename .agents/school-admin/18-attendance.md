# 18 — การเข้าเรียน (School Attendance)

> ไฟล์รองของเมนู **#18 การเข้าเรียน** (`admin/school-attendance/`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> ขั้น [1] สแกนโค้ดจริง + [2] เขียนไฟล์รอง — **เสร็จ 2026-10-12** · รอเจ้าของเคาะ Q1–Q3 ก่อนแตก step

## 1. Scope & Purpose

เมนู **การเข้าเรียนระดับโรงเรียน (เข้าแถว/มาเรียนประจำวัน)** — เปิดคาบเช็กชื่อ (session) · เช็กชื่อผ่าน **QR / token / สแกน** · บันทึกสถานะ present/absent/late/leave รายนักเรียน · ปิดคาบ · ดูประวัติรายนักเรียน · ดูสรุปต่อคาบ

ผู้ใช้: owner/admin · ครู (เช็กชื่อจริง — ได้ `school_attendance.manage` จาก backfill G18) · นักเรียน (เช็กชื่อเอง + ดูประวัติตนเอง) · ผู้ปกครอง (ดูของบุตรผ่าน `children.attendance.view`)

> **ขอบเขตที่ชัด:** เมนูนี้ = **school attendance** เท่านั้น · ระบบ attendance อื่นเป็นคนละโดเมน:
> - **course attendance** (เช็กชื่อในคอร์ส · `CourseAttendanceController` · `Learn/Courses/[id]/attendances.vue`) = โดเมน Learn ของครูผู้สอน — ไม่ใช่เมนูนี้
> - **activity attendance** (เช็กชื่อกิจกรรม) = เมนู #26 (ปิดแล้ว)
> - **staff attendance** (ลงเวลาบุคลากร · `StaffAttendanceController`) = งาน HR ผูกเมนู #14/#8

## 2. Current State (จากการสแกนโค้ดจริง)

### Frontend
- Admin: `ui/pages/academies/[name]/admin/school-attendance/index.vue` (รายการคาบ · gate `isAdmin || can('school_attendance.manage')`) + `[id].vue` (รายละเอียดคาบ)
- Non-admin: `ui/pages/academies/[name]/attendance/check-in.vue` (นักเรียนเช็กชื่อด้วย QR/token · middleware `auth`) + `attendance/history/[studentId].vue`
- Components: `components/school/SchoolAttendanceWidget.vue` · `SchoolAttendanceQRDisplay.vue`

### Backend
- Controller: `app/Http/Controllers/Api/Learn/Academy/SchoolAttendanceController.php` (577 บรรทัด)
- Routes: `routes/learn/academy.php` §1059–1078 — prefix `{academy}/school-attendances` · `academy.visibility:content`
  - reads (`index`/`show`/`studentHistory`/`checkIn`) = bare `academy.permission` (**= เป็นสมาชิกที่อนุมัติแล้ว**) + คุมความลึกข้อมูลใน controller ด้วย `school_attendance.view`
  - writes (`store`/`refreshQr`/`scanStudent`/`storeRecords`/`close`) = `academy.permission:school_attendance.manage`
- Models: `Learn/Academy/SchoolAttendance` + `SchoolAttendanceRecord`
- Middleware: `CheckAcademyPermission` — bare = member gate · คีย์ → `Academy::userCan()` (role perm + group grant)

### Database
- `school_attendances` (คาบเช็กชื่อ · `academy_id`, `status`, `qr_token`, `qr_token_expires_at`)
- `school_attendance_records` (รายการรายนักเรียน · `attendance_id`, `student_id`, `academy_id`, `status`, `remarks`)
- migration ล่าสุด 2026-05/06 + backfill permission 2026-09-02

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | เปิด/ปิดคาบเช็กชื่อ | ✅ | `store`/`close` gate manage |
| 2 | QR / token / สแกน เช็กชื่อ | ✅ | `refreshQr`/`scanStudent`/`checkIn` + check-in.vue |
| 3 | บันทึกสถานะรายนักเรียน | ✅ | `storeRecords` + present/absent/late/leave |
| 4 | ประวัติรายนักเรียน + สรุปคาบ | ✅ | `studentHistory` (นักเรียนเห็นของตน · อื่นต้อง `school_attendance.view`) |
| 5 | tenant isolation | ✅ | `where('academy_id', ...)` + route-model-bind ทุกเมธอด |
| 6 | graded access (depth ตามคีย์) | ✅ | ตั้งใจออกแบบ (comment §1065) |
| 7 | permission assign ให้ครู | ✅ | backfill G18 ให้ `teacher => school_attendance.manage` |
| 8 | มอบสิทธิ์ผ่าน UI ตรงกับ BE | ❌ | **G1** picker/เมนูใช้ `attendance.*` แต่ BE ใช้ `school_attendance.*` |

## 4. Permission Matrix

| Permission key | Owner | Admin | ฝ่าย | Teacher | Staff | Student | Guardian |
|---|---|---|---|---|---|---|---|
| (เป็นสมาชิก) อ่านคาบแบบตื้น | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ (ของตน) | — |
| `school_attendance.view` (เห็น record ลึก) | ✅ | ✅ | ✅ (ถ้าให้) | ⚠️ | ❌ | ของตน | บุตร (`children.attendance.view`) |
| `school_attendance.manage` (เปิด/ปิด/เช็ก/สแกน) | ✅ | ✅ | ✅ (ถ้าให้) | ✅ (backfill) | ❌ | ❌ | ❌ |

## 5. Gap Analysis

- **G1 (P1 · FE↔BE key split):** BE (routes + controller) บังคับ `school_attendance.view` / `school_attendance.manage` · แต่ FE:
  - เมนูซ้าย `admin.vue:180` โชว์ลิงก์เมื่อ `can('attendance.view')` (คีย์เก่า) → ผู้ถือ `attendance.view` เห็นเมนู แต่หน้า `index.vue` บังคับ `school_attendance.manage` ⇒ เห็นแต่กดเข้าไปใช้ไม่ได้ (ซ้ำรอย F1 เมนู #14)
  - ตัวเลือกสิทธิ์ `roles.vue:77-78` ให้เลือก `attendance.view`/`attendance.manage` (คีย์เก่า) ไม่ใช่ `school_attendance.*` ⇒ admin มอบสิทธิ์ให้ custom role ผ่าน UI แล้ว **ไม่มีผลจริง** (สวิตช์หลอก) · และ `school_attendance.*` ไม่มีให้เลือกใน picker เลย
  - `index.vue` เอง gate `school_attendance.manage` ถูกต้อง ✅ (ความไม่ตรงอยู่ที่เมนู + picker)
- **G2 (P2 · stale doc):** OVERVIEW ระบุ `admin/attendance` + คีย์ `attendance.view/manage` — **ล้าสมัย** · route attendance เก่าถูกถอดแล้ว (academy.php:528 "replaced by school-attendances") · หน้าจริง = `admin/school-attendance/` · คีย์จริง = `school_attendance.*`
- **G3 (P2 · orphan key):** คีย์ `attendance.view`/`attendance.manage` ใน catalog ไม่มี route/controller BE บังคับใช้แล้ว (เหลือแค่ FE อ้าง) — ต้องตัดสิน: (ก) ทำ `attendance.*` เป็น alias ของ `school_attendance.*` หรือ (ข) ลบทิ้ง + ย้าย FE picker/เมนู ไปใช้ `school_attendance.*`
- **G4 (P3 · adjacent · #14):** `staff-attendance` writes (`store`/`update`/`destroy`) gate ด้วย `staff.view` (คีย์อ่าน) ไม่ใช่ `staff.manage` — เป็นหนี้ของเมนู #14 (HR) ไม่ใช่ #18 · บันทึกไว้เฉย ๆ

## 6. Open Questions (รอเจ้าของเคาะก่อนลงมือ)

- **Q1 (G1/G3):** จัดการ key split อย่างไร — เสนอ: **ย้าย FE (เมนู `admin.vue` + picker `roles.vue`) ไปใช้ `school_attendance.view/manage` ให้ตรง BE** แล้ว deprecate `attendance.*` (ถ้ามี role เก่าถือ `attendance.*` อยู่ ต้อง migrate เป็น `school_attendance.*`) — โอเคไหม หรืออยากทำ `attendance.*` เป็น alias แทน?
- **Q2 (G2):** อัปเดต OVERVIEW scope #18 เป็น `admin/school-attendance/` + `school_attendance.*` (ตัด `admin/attendance` ที่ถอดไปแล้ว) — ยืนยัน
- **Q3:** ขอบเขตเมนู #18 = school attendance อย่างเดียว (course/staff/activity แยกโดเมน) — ยืนยันว่าตรงกับที่เข้าใจ

## 7. Implementation Tasks (ส่งให้ agy/codex ทีละ step — **ยังไม่เริ่มจนกว่าจะเคาะ Q**)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| AT-S1 | ย้าย FE menu + picker → `school_attendance.*` (G1) | Q1 | — | ⚪ pending |
| AT-S2 | migrate role เก่าที่ถือ `attendance.*` → `school_attendance.*` (ถ้าเลือกตัดคีย์เก่า) | Q1 | — | ⚪ pending |
| AT-S3 | deprecate/alias `attendance.*` ใน catalog (G3) | Q1 | — | ⚪ pending |
| AT-S4 | อัปเดต OVERVIEW scope (G2) | Q2 | — | ⚪ pending |
| AT-S5 | เทสต์ authz (menu gate ตรง BE · picker มอบสิทธิ์แล้วใช้ได้จริง) | S1–S3 | — | ⚪ pending |
| AT-S6 | ตรวจจอจริง 375/768/1280 (index + check-in QR) | S1–S5 | — | ⚪ pending |

**Rule:** ทุก step ต้อง verify (`php -l` / `pint` / `php artisan test -c phpunit.mysql.xml --filter=...` / `npm run build` / คลิก 375px) ก่อนขึ้น 🟢 — รายงาน agy เชื่อไม่ได้ ต้อง `git diff` + รันเกณฑ์เอง

## 8. Review Log
- **2026-10-12** — ขั้น [1] สแกน + [2] เขียนไฟล์รอง เสร็จ · โมดูลสุก/tenant-safe/graded-access (คล้าย #15 ไม่ใช่ #17) · finding หลัก **G1 FE↔BE key split** (`attendance.*` เก่า vs `school_attendance.*` จริง) + G2 OVERVIEW stale + G3 orphan key · มีเทสต์ `SchoolAttendanceRosterScopeTest` อยู่แล้ว · รอเจ้าของเคาะ Q1–Q3

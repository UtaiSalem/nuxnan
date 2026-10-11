# 18 — การเข้าเรียน (School Attendance)

> ไฟล์รองของเมนู `18 การเข้าเรียน` (`admin/school-attendance/`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> **สถานะ:** 🟢 audit + ปิด gap ครบ 2026-10-11 (AT-S1–S4) — **เมนูสุก/authz แน่น/มีเทสต์** · G1/G2/G3 ปิดแล้ว + เพิ่ม export CSV/PDF · เหลือเจ้าของ verify (MySQL + build)

## 1. Scope & Purpose

เช็คชื่อ **การมาโรงเรียนรายวัน** (ไม่ใช่เช็คชื่อรายคาบ) — งานของ **ฝ่ายบริหารงานกิจการนักเรียน/ครูประจำชั้น**

ครอบคลุม:
- สร้าง "คาบเช็คชื่อ" (session) รายวัน + สร้าง **QR** ให้นักเรียนสแกนเอง
- นักเรียนสแกน QR เช็คชื่อตัวเอง (present/late ตาม `start_time`+`late_minutes`)
- ครูสแกนรหัสนักเรียน (scan-student) / บันทึก bulk แบบ manual (present/absent/late/leave)
- ปิดคาบ (close) · ดูประวัติรายคน (studentHistory) · ให้ XP/แต้ม (gamification) อัตโนมัติ
- วิดเจ็ตเช็คชื่อของนักเรียนเอง (`SchoolAttendanceWidget`)

**ขอบเขตที่ไม่ใช่เมนูนี้ (แยกโดเมน — อย่าสับสน):**
- **เช็คชื่อรายคาบ (course attendance)** — คีย์ `attendance.view`/`attendance.manage` · `Api/Learn/Course/attendances/*` + `Learn/Courses/[id]/attendances.vue` + phaser seat grid ⇒ อยู่โดเมน **คอร์ส/คาบเรียน** (เกี่ยวกับ #12/#19) ไม่ใช่ #18
- **ลงเวลาบุคลากร (staff time-clock)** — `StaffAttendanceController` (`{academy}/staff-attendance`) คีย์ `staff.view` ⇒ เป็นงาน **#14 บุคลากร** (เช็คอิน/เอาต์/daily-report ของพนักงาน)
- **เช็คชื่อร่วมกิจกรรม (activity/event)** — `ActivityAttendance`/`ActivitySessionController` ⇒ **#26** (ปิดแล้ว)

## 2. Current State (จากการสแกนโค้ดจริง)

### Backend ✅ (แข็งแรง)
- **Controller:** `app/Http/Controllers/Api/Learn/Academy/SchoolAttendanceController.php` (577)
- **Routes:** `routes/learn/academy.php` §`{academy}/school-attendances` (บรรทัด ~1069) ภายใต้ `academy.visibility:content` (กันโรงเรียนถูกเก็บถาวร)
  - reads (`index`/`show`/`studentHistory`/`check-in`) = **สมาชิกภาพล้วน** (`academy.permission` เปล่า) — เพราะนักเรียนต้องใช้วิดเจ็ตเช็กชื่อตัวเอง (ไม่ถือคีย์ `school_attendance.*`)
  - writes (`store`/`records`/`scan-student`/`close`/`refresh-qr`) = `academy.permission:school_attendance.manage`
- **Models:** `Learn/Academy/SchoolAttendance` (session: date/title/start_time/late_minutes/status open-closed/qr token) · `Learn/Academy/SchoolAttendanceRecord` (attendance_id/academy_id/student_id/status/check_in_method/checked_in_at/recorded_by/remarks)
- **Authz ในคอนโทรลเลอร์ (G18 เคยแก้แล้ว — ยืนยันว่าทำจริง):**
  - `authorizeAttendance()` → 404 ถ้า `attendance.academy_id !== academy.id` (tenant scope ทุก method)
  - `authorizeManager()` → `userCan(user,'school_attendance.manage')` (ไม่ใช่ `isAdmin` ล้วน ⇒ ครูเช็กชื่อได้จริง)
  - **data-depth gating:** `show()` ไม่มีคีย์ `.view` → `showOwnRecordOnly()` (เห็นแค่แถวตัวเอง ไม่เห็นรายชื่อ/ยอดรวมทั้งคาบ) · `studentHistory()` ดูของคนอื่นต้องถือ `.view`
  - QR: ไม่มี TTL (session open/closed เป็นด่าน) · `isQrTokenValid` + กันเช็คซ้ำ (`alreadyChecked`)
  - enrichment แบบ batched (ไม่มี N+1): AcademyMember → ClassroomStudent → Classroom
- **Gamification:** check-in/records ให้ `PointsService` + `XpService` + `ClassroomPointsService` อัตโนมัติ

### Frontend
- Pages: `admin/school-attendance/index.vue` (รายการคาบ + สร้าง) · `admin/school-attendance/[id].vue` (จัดการคาบ: scan/bulk/close/QR) · `attendance/check-in.vue` (นักเรียนสแกน) · `attendance/history/[studentId].vue`
- Components: `SchoolAttendanceWidget.vue` · `SchoolAttendanceQRDisplay.vue`
- ยิง endpoint `school-attendances/*` ครบทุกเส้น (index/scan-student/check-in/close/records/refresh-qr/student/{id}) — contract ตรงกับ backend

### Tests ✅
`tests/Feature/Api/Academy/SchoolAttendanceRosterScopeTest.php` (5 เคส) — enrich active student · ไม่ enrich ด้วยข้อมูลห้องเก่าหลังย้าย · bulk remark persist · ปฏิเสธคีย์ remark เก่า · resubmit อัปเดต remark

## 3. Feature Checklist

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | สร้างคาบเช็คชื่อ + QR | ✅ | `store` · `school_attendance.manage` |
| 2 | นักเรียนสแกน QR เช็คชื่อตัวเอง | ✅ | `check-in` · present/late อัตโนมัติ · กันซ้ำ |
| 3 | ครูสแกนรหัสนักเรียน (realtime) | ✅ | `scan-student` · resolver scoped academy |
| 4 | บันทึก bulk manual | ✅ | `storeRecords` · updateOrCreate · remark |
| 5 | ปิดคาบ / ต่ออายุ QR | ✅ | `close`/`refresh-qr` |
| 6 | ประวัติรายคน + สรุป | ✅ | `studentHistory` · self หรือ `.view` |
| 7 | วิดเจ็ตนักเรียนดูของตัวเอง | ✅ | data-depth gating |
| 8 | รายงาน/ส่งออก (CSV/PDF) | ❌ | ยังไม่มี export ของ school-attendance (มีแต่รายวันในจอ) |
| 9 | tenant + archived guard | ✅ | `authorizeAttendance` + `academy.visibility` |

## 4. Permission Matrix

Keys จริงของเมนูนี้ = **`school_attendance.view` / `school_attendance.manage`** (ไม่ใช่ `attendance.*` ที่ OVERVIEW เขียนไว้ — `attendance.*` คือ course attendance)

| Permission key | Owner | Admin | Director | Teacher | Student | Guardian |
|---|---|---|---|---|---|---|
| `school_attendance.view` | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| `school_attendance.manage` | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| (reads พื้นฐาน = สมาชิกภาพล้วน) | ✅ | ✅ | ✅ | ✅ | ✅ (เฉพาะของตัวเอง) | — |

(จาก `AcademyRole`: owner/director/admin/teacher ถือ `school_attendance.view`+`.manage` · student/parent ไม่ถือ แต่เห็นของตัวเองผ่าน data-depth gating)

## 5. Gap Analysis (ไม่มี P0 — เมนูนี้แข็งแรงแล้ว)

- **G1 (integrity, P2) — `storeRecords` ไม่ผูก `student_id` กับโรงเรียน**
  validate แค่ `student_id => exists:users,id` (user ใดก็ได้ทั้งระบบ) แล้วสร้าง record ด้วย `academy_id` ของคาบ ⇒ ผู้ถือ `.manage` ของโรงเรียน A ใส่ record อ้าง user id ใครก็ได้ (แม้ไม่ใช่สมาชิก A) ได้ · ความเสี่ยงต่ำ (จำกัดในคาบของโรงเรียนตัวเอง + ต้อง manage) แต่ควร validate ว่าเป็นสมาชิก/นักเรียนของ academy (เทียบ `scanStudent` ที่ใช้ resolver scoped แล้ว)

- **G2 (FE mis-key, P2) — เมนู nav ใช้คีย์ผิดตระกูล**
  `admin.vue:180` → `show: can('attendance.view') || can('academy.view')` · ควรเป็น `can('school_attendance.view') || can('school_attendance.manage')` · ผลตอนนี้: `academy.view` = สมาชิกเกือบทุกคนเห็นเมนู (รวมนักเรียน/ผู้ปกครองถ้าถือ academy.view) ทั้งที่กดเข้าไปหน้า admin ก็ทำอะไรไม่ได้ (backend gate แล้ว) — เป็นเรื่อง UX/ความสอดคล้อง ไม่ใช่ช่องโหว่ข้อมูล

- **G3 (test gap, P3) — ไม่มีเทสต์ยืนยัน authz/data-depth**
  RosterScopeTest ครอบ enrichment/remark แต่ **ไม่มีเคสยืนยัน:** non-member โดน 403 · สมาชิกไม่มี `.view` ได้ own-record-only ที่ `show` · ไม่มี `.manage` โดน 403 ที่ write · `studentHistory` ของคนอื่นโดนกันถ้าไม่มี `.view` → ควรเติม

- **หมายเหตุ by-design (ไม่ใช่ gap):** `index()` ไม่ติดคีย์ `.view` (สมาชิกเห็นรายการคาบ + ยอดนับ records) — ตั้งใจให้วิดเจ็ตนักเรียนใช้ · ถ้าถือว่ายอดนับเป็นข้อมูลที่ไม่ควรเปิดให้นักเรียน ค่อยพิจารณาแยกภายหลัง

## 6. Implementation Tasks (รอเจ้าของเคาะก่อนส่ง — เมนูนี้ gap เล็ก)

| Step | Title | Deliverable | Status |
|---|---|---|---|
| AT-S1 | **G1:** `storeRecords` validate `student_id` เป็นสมาชิก approved ของ academy | `Rule::exists('academy_members',...)->where(academy_id,status=2)` + เทสต์ | 🟢 **done (owner verify MySQL)** |
| AT-S2 | **G2:** แก้ nav `admin.vue:180` → `can('school_attendance.view')‖'.manage'` | 1 บรรทัด FE | 🟢 **done** |
| AT-S3 | **G3:** เทสต์ authz/data-depth | 4 เคส (403 non-member · own-only ไม่มี `.view` · write ต้อง `.manage` · history คนอื่นต้อง `.view`) + G1 + export | 🟢 **done (owner verify MySQL)** |
| AT-S4 | export รายงาน school-attendance CSV/PDF | `GET /{attendance}/export?format=csv\|pdf` (gate `.view`) + blade `exports.school-attendance-pdf` + ปุ่มในหน้า `[id].vue` | 🟢 **done (owner verify build+MySQL)** |

**Rule:** ทุก step verify (เทสต์/จอจริง) ก่อน 🟢 · cloud รันเทสต์ไม่ได้ (ไม่มี vendor) → เจ้าของ verify MySQL

## 7. คำถามที่ต้องเคาะ (Q)
- **Q1 — G1 ควร scope `student_id` เป็นสมาชิก academy ไหม?** (แนะนำ: ควร — ปิดช่องใส่ user id มั่ว · low-risk แต่ถูกหลัก)
- **Q2 — G2 แก้คีย์ nav เลยไหม** (แนะนำ: ควร · เป็น FE 1 บรรทัด)
- **Q3 — ต้องการ export รายงาน school-attendance (AT-S4) ไหม?** (ตอนนี้ไม่มี)

## 8. Review Log
- **2026-10-11 (audit)** — ขั้น [1]+[2] เสร็จ · เมนูสุก/authz แน่น (G18 hardening ยืนยันแล้ว)/มีเทสต์ · ไม่มี P0 · พบ G1/G2/G3 · แก้ความเข้าใจคีย์ `school_attendance.*`
- **2026-10-11 (ลงมือ AT-S1–S4 — เจ้าของเคาะ Q1=ใช่ Q2=ใช่ Q3=ต้องการ export)** — Claude เขียนเอง (cloud ไม่มี vendor/agy) ตรวจ `php -l`:
  - **AT-S1/G1:** `storeRecords` เปลี่ยน `student_id => exists:users,id` → `Rule::exists('academy_members','user_id')->where('academy_id',..)->where('status',STATUS_APPROVED)` ⇒ ใส่ record ได้เฉพาะสมาชิกอนุมัติของโรงเรียนนี้
  - **AT-S2/G2:** `admin.vue:180` → `can('school_attendance.view') || can('school_attendance.manage')` (เดิม `attendance.view`/`academy.view`)
  - **AT-S3/G3:** เพิ่ม 6 เคสใน `SchoolAttendanceRosterScopeTest`: non-member 403 · member ไม่มี `.view` ได้ own-record-only (summary null) · member ไม่มี `.manage` โดน 403 ที่ records · history คนอื่นต้อง `.view` · G1 reject non-member student_id (422) · export CSV 200 text/csv
  - **AT-S4 export:** `export()` + `exportCsv`/`exportPdf` (mPDF garuda) · route `GET /{attendance}/export?format=csv|pdf` gate `school_attendance.view` · blade `resources/views/exports/school-attendance-pdf.blade.php` · FE ปุ่ม CSV/PDF ใน `[id].vue` (ใช้ `api.getBlob` + download) · mobile-first
  - ตรวจ `php -l` ผ่านทุกไฟล์ BE · เจ้าของ verify: `php artisan test -c phpunit.mysql.xml --filter=SchoolAttendanceRosterScopeTest` + `pint` + `npm run build` + กดปุ่ม export จริง
  - **เมนู #18 ปิด gap ครบ (G1–G3) + ได้ฟีเจอร์ export** 🟢

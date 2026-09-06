# 08 — ระบบบริหารโรงเรียน (School Management)

> ไฟล์รองของเมนู **#8 ระบบบริหารโรงเรียน** ใน [OVERVIEW.md](OVERVIEW.md)
> สแกนโค้ดจริง + ยิงเทสต์จริง เมื่อ **2026-09-07** · ยังไม่ได้ส่ง step ไหนให้ codex/agy

---

## 0. สรุปสำหรับคนอ่านรอบเดียว

เมนูนี้ **ไม่ใช่หน้าเดียว** แต่เป็น "ERP ของโรงเรียน" ทั้งก้อนที่ถูกเขียนไว้ล่วงหน้า:
**204 route · 19 controller · 30+ ตาราง · UI 4,128 บรรทัด** — เขียนครบแต่ **ไม่เคยถูก audit เลย**

🔴 **P0 ที่พิสูจน์แล้วด้วยเทสต์จริง: 203 จาก 204 route ไม่มีด่านสิทธิ์อะไรเลยนอกจาก `auth:api`**
ผู้ใช้ที่ล็อกอินคนไหนก็ได้ (ไม่ต้องเป็นสมาชิกโรงเรียนด้วยซ้ำ) อ่าน **เงินเดือน ใบแจ้งหนี้ค่าเทอม
รายจ่าย งบประมาณ แฟ้มบุคลากร** ของโรงเรียนไหนก็ได้ ด้วยการเดา `academy_id` เท่านั้น
นี่คือบั๊กชนิดเดียวกับ G1 ของเมนู #7 แต่กว้างกว่ามาก

รองลงมา: UI 4 จุดเรียกฟังก์ชันที่ไม่มีอยู่จริง · endpoint 4 เส้นชี้ผิด path/verb ·
มีแท็บที่เขียนเสร็จแล้วแต่ **ไม่มีทางเข้าถึงได้** 4 ไฟล์ (791 บรรทัด) · endpoint ที่ 500 ถาวรอีก 3 เส้น

---

## 1. Scope & Purpose

**หน้าเดียวที่เป็นประตู:** `ui/pages/academies/[name]/admin/school-management.vue`
เข้าจากเมนูข้างใน `admin.vue` กลุ่ม "ตั้งค่า" → ชื่อ "ระบบบริหารโรงเรียน" (`show: can('settings.manage')`)

หน้านี้ทำหน้าที่ 2 อย่างพร้อมกัน ซึ่งเป็นที่มาของความสับสนทั้งหมดในเมนูนี้:

1. **hub ของทางลัด** — 13 การ์ดลิงก์ไปเมนูอื่น (#2 สมาชิก · #4 ลิงก์เชิญ · #5 แท็ก · #1 บทบาท ·
   #6 ผู้ปกครอง · #22 ประวัติกิจกรรม · #13 หลักสูตร · #10 ห้องเรียน · #9 ฝ่าย · #11 ตารางเรียน ·
   #15 ทะเบียนนักเรียน · #17 เยี่ยมบ้าน · ร้านค้า) — ทางลัดล้วน ไม่มีตรรกะของตัวเอง
2. **บ้านจริงของ 5 โมดูลที่ไม่มีเมนูของตัวเอง** — วิชาการ (วิชา/ตารางสอน/ปีการศึกษา) ·
   การเงิน (ค่าเทอม/รายจ่าย/งบประมาณ) · บุคลากร (แฟ้ม/ลงเวลา/ลา/เงินเดือน) ·
   สื่อสาร (ประกาศ/กิจกรรม/นัดพบผู้ปกครอง) · รายงาน (report builder/KPI/analytics)

**ข้อ 2 คือขอบเขตจริงของเมนูนี้** — ข้อ 1 เป็นแค่ navigation ที่ควรตัดออกหรือยุบทีหลัง

**ผู้ใช้ที่เกี่ยวข้อง:** owner/admin โรงเรียน · หัวหน้าฝ่ายการเงิน · ฝ่ายบุคคล · ฝ่ายวิชาการ ·
(ทางอ้อม) ครูที่ยื่นใบลา และผู้ปกครองที่จองนัดพบ

---

## 2. Current State (จากการสแกนโค้ดจริง 2026-09-07)

### 2.1 Frontend

| ไฟล์ | บรรทัด | สถานะ |
|---|---:|---|
| `ui/pages/academies/[name]/admin/school-management.vue` | 379 | 🟡 หน้าจริง — 6 แท็บ |
| `ui/components/school/SchoolAcademicTab.vue` | 457 | 🟡 ใช้งานอยู่ |
| `ui/components/school/SchoolFinanceTab.vue` | 693 | 🟡 ใช้งานอยู่ |
| `ui/components/school/SchoolStaffTab.vue` | 686 | 🔴 เรียกฟังก์ชันที่ไม่มีจริง 3 ตัว |
| `ui/components/school/SchoolCommunicationTab.vue` | 652 | 🔴 เรียกฟังก์ชันที่ไม่มีจริง 1 ตัว |
| `ui/components/school/SchoolReportsTab.vue` | 625 | 🔴 ยิง endpoint ที่ไม่มี route |
| `ui/components/school/SchoolManagement.vue` | 88 | ⚫ **orphan** — ไม่มีใคร mount |
| `ui/components/school/SchoolGamificationTab.vue` | 368 | ⚫ เข้าถึงไม่ได้ (มีแต่ orphan ที่เรียก) |
| `ui/components/school/SchoolLibraryTab.vue` | 255 | ⚫ เข้าถึงไม่ได้ |
| `ui/components/school/SchoolAssetTab.vue` | 168 | ⚫ เข้าถึงไม่ได้ |
| `ui/components/school/AuditLogTab.vue` | 136 | ⚫ ไม่มีใครใช้เลย |
| `ui/composables/useSchoolManagement.ts` | 555 | 🟡 ~110 เมธอด · เป็น API client ตัวเดียวของทั้งเมนู |

**หลักฐานว่า `SchoolManagement.vue` เป็น orphan:** `grep -rn "<SchoolManagement" pages components layouts` → 0 นัด
(หน้า `school-management.vue` เขียน `<SchoolAcademicTab>` ฯลฯ ตรง ๆ ไม่ผ่าน wrapper ตัวนี้)
⇒ แท็บ **แต้มรางวัล / ห้องสมุด / ทรัพย์สิน** ที่เขียนเสร็จแล้ว **ไม่มีทางกดถึงจากหน้าไหนในแอป**

`useSchoolManagement` ไม่ได้ถูกใช้แค่ในเมนูนี้ — มีอีก **8 หน้า** ที่พึ่งมันอยู่
(`admin/at-risk`, `admin/classrooms/[id]`, `admin/school-attendance/*`, `attendance/check-in`,
`attendance/history/[studentId]`, `dashboard/student`, `dashboard/teacher`, `parent/meetings`)
⇒ **แก้ composable นี้ต้องเช็ค 8 หน้านั้นด้วยเสมอ**

### 2.2 Backend

Controllers ทั้งหมดอยู่ที่ `app/Http/Controllers/Api/Learn/Academy/` · route ทั้งหมดอยู่ใน
`routes/learn/academy.php` ภายใต้ `Route::prefix('academies')` + `middleware('auth:api')`

| โมดูล | Controller | routes | มีด่านสิทธิ์ในโค้ด? |
|---|---|---:|---|
| วิชา | `SubjectController` | 6 | ⚠️ `canManage()` เฉพาะ store/update/destroy |
| ตารางสอน | `ClassScheduleController` | 8 | ❌ ไม่มีเลย |
| ปีการศึกษา | `AcademicYearController` | 7 | ⚠️ มี 403 บางเมธอด |
| โครงสร้างค่าธรรมเนียม | `FeeStructureController` | 10 | ❌ ไม่มีเลย |
| ใบแจ้งหนี้ค่าเทอม | `TuitionFeeController` | 9 | ❌ ไม่มีเลย |
| การชำระเงิน | `PaymentController` | 8 | ❌ ไม่มีเลย |
| รายจ่าย | `ExpenseController` | 13 | ❌ ไม่มีเลย |
| งบประมาณ | `BudgetController` | 11 | ❌ ไม่มีเลย |
| แฟ้มบุคลากร | `StaffController` | 12 | ❌ ไม่มีเลย¹ |
| ลงเวลาบุคลากร | `StaffAttendanceController` | 9 | ❌ ไม่มีเลย¹ |
| ใบลา | `LeaveRequestController` | 11 | ❌ ไม่มีเลย¹ |
| เงินเดือน | `PayrollController` | 13 | ❌ ไม่มีเลย¹ |
| ประกาศ | `AnnouncementController` | 8 | ⚠️ `isAcademyAdmin()` บางเมธอด |
| นัดพบ | `MeetingController` | 13 | ⚠️ `isAdmin()` บางเมธอด |
| รายงาน | `ReportController` | 21 | ❌ ไม่มีเลย |
| analytics/KPI | `AnalyticsController` | 26 | ❌ ไม่มีเลย |
| dashboard widget | `DashboardWidgetController` | 12 | ⚠️ 403 อยู่ 2 จุด |
| ห้องสมุด | `LibraryController` | 4 | ❌ ไม่มีเลย |
| ทรัพย์สิน | `AssetController` | 3 | ❌ ไม่มีเลย |

¹ 4 ตัวนี้มีเมธอด `authorizeStaff()` / `authorizePayroll()` / `authorizeLeave()` / `authorizeAttendance()`
ซึ่ง **ชื่อหลอก** — ข้างในเช็คแค่ `$record->academy_id === $academy->id` แล้ว `abort(404)`
เป็นการกัน IDOR ข้ามโรงเรียนเท่านั้น **ไม่ได้เช็คสิทธิ์ผู้เรียกแม้แต่บิตเดียว**

### 2.3 Database (นับจริงบน dev DB `nuxnan` 2026-09-07)

| ตาราง | rows | ตาราง | rows |
|---|---:|---|---:|
| `academic_years` | 2 | `staff_profiles` | 5 |
| `semesters` | 1 | `staff_attendances` | 25 |
| `subjects` | 5 | `leave_types` | 4 |
| `class_schedules` | 5 | `leave_requests` | 1 |
| `fee_structures` | 4 | `payrolls` | 3 |
| `fee_items` | 12 | `payroll_items` | 0 |
| `tuition_fees` | 0 | `school_announcements` | 3 |
| `tuition_fee_items` | 0 | `announcement_reads` | 0 |
| `payments` | 0 | `school_events` | 3 |
| `payment_methods` | 0 | `meeting_slots` | 13 |
| `expenses` | 4 | `meeting_bookings` | 0 |
| `expense_categories` | 5 | `report_definitions` | 8 |
| `budgets` | 2 | `saved_reports` / `report_exports` / `report_schedules` | 0 |
| `kpi_definitions` | 4 | `kpi_values` / `analytics_snapshots` | 0 |
| `dashboard_widgets` | 4 | `library_books` / `library_borrowings` | 0 |
| `school_assets` / `asset_maintenance_logs` | 0 | `fee_discounts` / `payment_plans` | 0 |

**อ่านตารางนี้ยังไง:** ทุกตัวเลขคือ seed/ทดลอง ไม่มีตัวไหนเป็นข้อมูลใช้งานจริง
⇒ **ตอนนี้ยังไม่มีข้อมูลจริงรั่ว** แต่ช่องโหว่เปิดค้างรออยู่แล้ว ถ้าโรงเรียนเริ่มกรอกเงินเดือนวันไหน
ข้อมูลนั้นเปิดให้ทุกบัญชีที่ล็อกอินอ่านได้ทันทีโดยไม่มีอะไรกั้น

⚠️ **ชื่อตารางที่ไม่ตรงกับที่เดา** (เข้าใจผิดตอนสแกนรอบแรก): `announcements` ❌ → `school_announcements` ✅ ·
`kpis` ❌ → `kpi_definitions` ✅ · `assets` ❌ → `school_assets` ✅ · `fee_structure_items` ❌ → `fee_items` ✅

---

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | ด่านสิทธิ์บน route ทุกเส้น | ❌ | 203/204 มีแค่ `auth:api` — ดู G1 |
| 2 | ด่าน "โรงเรียนถูกเก็บถาวร" (archived) | ❌ | ไม่มี `academy.visibility` เลยสักเส้น — ดู G2 |
| 3 | โหมดดูอย่างเดียว (view vs manage) | ❌ | หน้าเช็คแค่ `isAdmin` แบบ boolean เดียว — ดู G3 |
| 4 | สิทธิ์ระดับฝ่าย (การเงิน/บุคคล/วิชาการ) | ❌ | คีย์มีครบใน `AcademyPermission` แต่ไม่ถูกใช้ — ดู G4 |
| 5 | แท็บวิชาการใช้งานได้ครบ | ⚠️ | แก้ไขวิชาพัง (PATCH vs PUT) — ดู G6 |
| 6 | แท็บการเงินใช้งานได้ครบ | ⚠️ | ดึงภาคเรียนพัง · สรุปรายจ่าย 500 — ดู G6, G8 |
| 7 | แท็บบุคลากรใช้งานได้ครบ | ❌ | เรียกฟังก์ชันที่ไม่มี 3 ตัว — ดู G5 |
| 8 | แท็บสื่อสารใช้งานได้ครบ | ❌ | จองนัดพบเรียกฟังก์ชันที่ไม่มี — ดู G5 |
| 9 | แท็บรายงานใช้งานได้ครบ | ❌ | ปุ่มสร้างรายงานยิง path ที่ไม่มี route — ดู G6 |
| 10 | แท็บแต้ม/ห้องสมุด/ทรัพย์สิน เข้าถึงได้ | ❌ | เขียนเสร็จแต่ไม่มีทางเข้า — ดู G7 |
| 11 | จำแท็บที่เปิดค้างไว้ใน URL | ❌ | `activeTab` เป็น ref ล้วน refresh แล้วเด้งกลับแท็บแรก — ดู G10 |
| 12 | ตัวเลขสถิติหัวหน้าหน้าถูกต้อง | ❌ | "นักเรียน" = สมาชิกที่อนุมัติแล้ว · "ครู" ค้าง 0 เสมอ — ดู G9 |
| 13 | เทสต์ของเมนูนี้ | ❌ | 0 ไฟล์ — ดู G12 |
| 14 | audit log ของการแก้ข้อมูลการเงิน/เงินเดือน | ⚠️ | `AnalyticsController` เรียก `auditLog` แต่ฝั่งการเงินไม่มี — ดู G11 |
| 15 | ป้ายบอกว่าโมดูลไหนยังไม่พร้อมใช้ | ❌ | ผู้ใช้กดเข้าไปเจอฟอร์มที่กรอกแล้วพัง — ดู G13 |

---

## 4. Permission Matrix (ที่ **ควรจะเป็น** — ตอนนี้ยังไม่มีอะไรบังคับเลย)

คีย์ทั้งหมดข้างล่างนี้ **มีอยู่แล้ว** ใน `app/Models/AcademyPermission.php` (123 คีย์) ไม่ต้องสร้างใหม่

| Permission key | Owner | Admin | ฝ่าย admin | Teacher | Staff | Student | Guardian |
|---|---|---|---|---|---|---|---|
| `finance.view` | ✅ | ✅ | ✅ (ฝ่ายการเงิน) | ❌ | ❌ | ❌ | ❌ |
| `finance.manage` | ✅ | ✅ | ✅ (ฝ่ายการเงิน) | ❌ | ❌ | ❌ | ❌ |
| `finance.reports` | ✅ | ✅ | ✅ (ฝ่ายการเงิน) | ❌ | ❌ | ❌ | ❌ |
| `staff.view` | ✅ | ✅ | ✅ (ฝ่ายบุคคล) | ❌ | ⚠️ ของตนเอง | ❌ | ❌ |
| `staff.manage` | ✅ | ✅ | ✅ (ฝ่ายบุคคล) | ❌ | ❌ | ❌ | ❌ |
| `courses.view` (วิชา) | ✅ | ✅ | ✅ (ฝ่ายวิชาการ) | ✅ | ❌ | ❌ | ❌ |
| `courses.manage` (วิชา) | ✅ | ✅ | ✅ (ฝ่ายวิชาการ) | ❌ | ❌ | ❌ | ❌ |
| `schedule.view` / `schedule.manage` | ✅ | ✅ | ✅ (ฝ่ายวิชาการ) | ⚠️ view | ❌ | ⚠️ view.own | ❌ |
| `announcements.view` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `announcements.manage` | ✅ | ✅ | ✅ (ในฝ่าย) | ⚠️ create.own | ❌ | ❌ | ❌ |
| `reports.view` / `reports.export` | ✅ | ✅ | ✅ (ในฝ่าย) | ❌ | ❌ | ❌ | ❌ |
| `reports.manage` (สร้าง definition) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `settings.manage` (widget ของโรงเรียน) | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

**เคสที่ต้องระวังตอนใส่ด่าน — ห้ามใส่ `finance.manage` / `staff.manage` ทื่อ ๆ ทุกเส้น:**
- `POST /leave-requests` — **ครูทุกคนต้องยื่นใบลาของตัวเองได้** (ไม่ใช่ `staff.manage`)
- `POST /leave-requests/{id}/approve|reject` — เฉพาะ `staff.manage`
- `GET /meetings/slots/available` · `POST /meetings/slots/{slot}/book` · `GET /meetings/my-bookings`
  — **ผู้ปกครองต้องเรียกได้** (ถ้าใส่ `staff.view` จะพังหน้า `parent/meetings.vue` ทันที)
- `POST /staff-attendance/check-in|check-out` — บุคลากรลงเวลาของตัวเองได้
- `GET /dashboard/layout` + `POST /dashboard/layout` — เป็น layout **ของผู้ใช้แต่ละคน** ไม่ใช่ของโรงเรียน
- `GET /announcements` — ทุกสมาชิกอ่านได้ (มี `academy.visibility:content` อยู่แล้ว อย่าทับ)

---

## 5. Gap Analysis

### 🔴 G1 — 203/204 route ไม่มีด่านสิทธิ์ (P0, พิสูจน์แล้ว)

`routes/learn/academy.php` ประกาศทุกกลุ่มของเมนูนี้แบบ `Route::prefix('{academy}/xxx')->group(...)`
**ไม่มี `->middleware('academy.permission:...')` สักกลุ่มเดียว** และ 13/19 controller ก็ไม่มีด่านในโค้ดด้วย

**หลักฐาน** — เทสต์ชั่วคราว `TempMenu8ExposureTest` (ลบทิ้งแล้วหลังยืนยัน) สร้าง user ที่
**ไม่ได้เป็นสมาชิกโรงเรียนนั้นเลย** แล้วยิง GET 20 เส้น ได้ `200` ทั้งหมด ยกเว้น 1 เส้นที่ 500:

```
fee-structures 200 · tuition-fees/summary 200 · expenses 200 · expenses/summary 500
budgets 200 · staff 200 · staff/summary 200 · staff-attendance 200 · leave-requests 200
payroll 200 · payroll/summary 200 · reports/definitions 200 · analytics/overview 200
analytics/kpis 200 · dashboard/widgets 200 · meetings/slots 200 · subjects 200
academic-years 200 · library/books 200 · assets 200
```

ฝั่งเขียนก็ผ่านด่านเหมือนกัน — `POST analytics/kpis` · `POST expenses/categories` ·
`POST reports/definitions` · `POST dashboard/widgets` **ผ่าน authorization ทั้งหมด**
(ที่ไม่สร้างสำเร็จเพราะติด validation/schema เท่านั้น ไม่ใช่เพราะสิทธิ์ — ดู G8)
มีแค่ `POST subjects` กับ `POST academic-years` ที่คืน 403 เพราะมีเช็คในคอนโทรลเลอร์

**วิธีแก้:** ใส่ `academy.permission:<key>` ที่ระดับ group ใน `routes/learn/academy.php`
มิดเดิลแวร์ `CheckAcademyPermission` มีอยู่แล้วและแยก 403 "ไม่ใช่สมาชิก" กับ "สิทธิ์ไม่พอ" ให้เสร็จสรรพ
**ห้ามเขียนเช็คซ้ำในคอนโทรลเลอร์** (คอมเมนต์ในมิดเดิลแวร์ห้ามไว้ชัดเจน)

### 🔴 G2 — ไม่มีด่านโรงเรียนที่ถูกเก็บถาวร
เมนู #7 SET-S2 ทำระบบ archive ไว้ แต่ 204 route นี้ไม่มี `academy.visibility` เลย
⇒ โรงเรียนที่เก็บถาวรแล้วยังถูกสร้างใบแจ้งหนี้/อนุมัติเงินเดือนต่อได้

### 🔴 G3 — หน้าเช็คสิทธิ์ฝั่ง client อย่างเดียว และเป็น boolean ตัวเดียว
`school-management.vue` โหลด `useAcademyRole` แล้ว `if (!isAdmin) navigateTo(...)` — เป็น redirect
ฝั่ง client ล้วน (กัน UI ไม่ได้กันข้อมูล) และดึง `can` มาแต่ **ไม่ได้เรียกใช้เลยสักครั้ง**
⇒ ไม่มีโหมดดูอย่างเดียว: คนที่ควรได้แค่ `finance.view` จะเห็นปุ่มอนุมัติครบเหมือน admin

### 🔴 G4 — สิทธิ์ระดับฝ่ายยังไม่ถูกผูก
เมนู #9 ปิดโมเดลสิทธิ์ระดับฝ่ายไปแล้ว แต่การเงิน/บุคคล/วิชาการยังไม่เกาะเข้ากับฝ่ายของตัวเอง
เป็นหนี้ก้อนเดียวกับที่ #14 บุคลากร และ #23 รายได้ รออยู่

### 🔴 G5 — UI เรียกฟังก์ชันที่ไม่มีอยู่ใน composable (4 จุด → TypeError)

| ไฟล์ | เรียก | ตัวจริงชื่อ |
|---|---|---|
| `SchoolStaffTab.vue` | `getStaffProfiles()` | `getStaffList()` |
| `SchoolStaffTab.vue` | `createStaffProfile()` | `createStaff()` |
| `SchoolStaffTab.vue` | `processPayroll()` | `createPayroll()` / `approvePayroll()` |
| `SchoolCommunicationTab.vue` | `bookMeetingSlot()` | `bookMeeting()` |

`useSchoolManagement` คืน object ธรรมดา ⇒ เรียกชื่อที่ไม่มี = `TypeError: not a function` ตอนกดปุ่ม

### 🔴 G6 — endpoint ในคอมโพสเซเบิลชี้ผิด path/verb (4 เส้น)

| เมธอด | ยิงไป | route จริง | อาการ |
|---|---|---|---|
| `updateSubject` | `PATCH /subjects/{id}` | `PUT /subjects/{subject}` | 405 |
| `getSemesters` | `GET /academic-years/{id}/semesters` | มีแต่ `POST` | 405 |
| `getLeaveTypes` | `GET /leave-types` | `GET /leave-requests/leave-types` | 404 |
| `generateReport` | `POST /reports/definitions/{id}/generate` | `POST /reports/generate` | 404 |

(ตรวจโดย diff รายการ `api.*()` ในคอมโพสเซเบิลกับ `php artisan route:list --json` ทั้งไฟล์)

### 🟡 G7 — โค้ดตายที่เขียนเสร็จแล้ว 791 บรรทัด
`SchoolManagement.vue` (88) ไม่มีใคร mount ⇒ `SchoolGamificationTab` (368) ·
`SchoolLibraryTab` (255) · `SchoolAssetTab` (168) เข้าถึงไม่ได้ · `AuditLogTab` (136) ไม่มีใครใช้เลย
**ต้องให้เจ้าของโปรเจคเคาะ:** เอา 3 แท็บนี้ขึ้นหน้าจริง หรือลบทิ้ง (ดู Q2)

### 🟡 G8 — บั๊กที่ทำให้ endpoint พังถาวร (ยืนยันบน MySQL dev จริง ไม่ใช่แค่ sqlite)

1. `ExpenseController@summary` — `selectRaw('category_id, ...')->groupBy('category_id')`
   แต่ตาราง `expenses` ใช้ชื่อคอลัมน์ `expense_category_id` ⇒ **500 ทุกครั้ง ทุกคน**
2. `ExpenseController@storeCategory` — เขียนคีย์ `display_order` ลง `expense_categories`
   ซึ่ง **ไม่มีคอลัมน์นี้** ⇒ 500 ทุกครั้ง (สร้างหมวดรายจ่ายไม่ได้เลย)
3. `AnalyticsController@createKpi` — validate `calculation` เป็น `nullable`
   แต่ `kpi_definitions.calculation` เป็น `longtext NOT NULL` ไม่มี default ⇒ 500 ถ้าไม่ส่งฟิลด์นี้

### 🟡 G9 — ตัวเลขสถิติหัวหน้าหน้าเป็นข้อมูลผิด
`fetchStats()` แม็ป `stats.approved` (จำนวนสมาชิกที่อนุมัติแล้ว) ไปลงการ์ด **"นักเรียน"**
และ `totalTeachers` / `totalStaff` **ไม่เคยถูกเซ็ตเลย** ⇒ การ์ด "ครู/อาจารย์" โชว์ `0` ตลอดกาล

### 🟡 G10 — แท็บไม่ผูกกับ URL
`activeTab` เป็น `ref('members')` เฉย ๆ ⇒ refresh/แชร์ลิงก์/กดย้อนกลับ เด้งกลับแท็บแรกเสมอ
(เมนู #7 แก้เรื่องเดียวกันไปแล้วด้วย `?view=` — ใช้แพทเทิร์นเดียวกันได้)

### 🟡 G11 — ไม่มี audit log ฝั่งการเงิน/เงินเดือน
`AnalyticsController` มี `$this->auditLog->log(...)` แต่ Expense/Payroll/TuitionFee/Budget ไม่มีเลย
ทั้งที่เป็นข้อมูลที่อ่อนไหวกว่ามาก (เมนู #7 SET-S9 วางระบบ audit ไว้ให้แล้ว)

### 🟡 G12 — ไม่มีเทสต์เลยสักไฟล์
ไม่มีไฟล์เทสต์ใดแตะ `fee_structures` / `payroll` / `leave_request` ⇒ ไม่มีอะไรกันการถอยหลัง

### ⚪ G13 — ไม่มีป้ายบอกสถานะโมดูล
ผู้ใช้กดเข้าแท็บบุคลากร กรอกฟอร์ม กดบันทึก แล้วเงียบ (TypeError ใน console)
ระหว่างที่ยังไม่ปิด G5/G6/G8 ควรมีป้าย "โมดูลนี้ยังไม่เปิดใช้งาน" ดีกว่าปล่อยให้กรอกฟรี

### ⚪ G14 — เมนูนี้ทับกับเมนูอื่นครึ่งหน้า
แท็บ "สมาชิก" ทั้งแท็บเป็นการ์ดลิงก์ไปเมนู #1–#6, #22 · แท็บ "วิชาการ" ลิงก์ไป #9–#13
⇒ ต้องตัดสินใจว่าเมนูนี้คือ **hub** หรือคือ **บ้านของ 5 โมดูลที่ไม่มีเมนู** (ดู Q1)

---

## 5.1 คำถามที่ต้องให้เจ้าของโปรเจคเคาะก่อนเริ่ม S2 เป็นต้นไป

**Q1 — เมนู #8 คืออะไรกันแน่?**
(ก) hub รวมทางลัด + บ้านของ 5 โมดูล (คงสภาพปัจจุบัน)
(ข) บ้านของ 5 โมดูลล้วน — ตัดแท็บ "สมาชิก" ทิ้ง เพราะซ้ำกับเมนู #2–#6 ที่ปิดไปแล้ว
(ค) แตกออกเป็นเมนูจริง 5 ตัว (การเงิน / บุคลากร / วิชาการ / สื่อสาร / รายงาน) แล้วลบหน้านี้
→ **คำตอบเปลี่ยนขอบเขต S5–S9 ทั้งหมด** จึงต้องเคาะก่อน

**Q2 — 3 แท็บที่เข้าถึงไม่ได้ (แต้มรางวัล/ห้องสมุด/ทรัพย์สิน) เอาไงต่อ?**
เอาขึ้นหน้าจริง หรือลบทิ้ง (ห้องสมุด/ทรัพย์สินมี 0 แถว และตาราง `school_assets` ว่างเปล่า)

**Q3 — โมดูลการเงินจะใช้จริงเมื่อไหร่?**
ถ้ายังไม่ใช้ปีนี้ ทางที่ถูกที่สุดคือ **ปิดทั้งกลุ่มด้วย `settings.manage` ก่อน** (1 บรรทัดต่อ group)
แล้วค่อยแยกสิทธิ์ละเอียดทีหลัง — ถ้าจะใช้เทอมนี้ ต้องทำ SM-S2 แบบละเอียดตั้งแต่แรก

**Q4 — `payments` / `tuition_fees` จะผูกกับ wallet/points ที่มีอยู่ไหม?**
ตอนนี้เป็นคนละระบบกับแผนใน `.agents/plans/*` ทั้งหมด ถ้าจะรวมต้องคุยก่อนเขียน

---

## 6. Implementation Tasks

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| **SM-S1** | 🔴 ปิดช่องโหว่ G1+G2 ให้ครบ 204 route | — | middleware บนทุก group + เทสต์ 403 ของ non-member | ⚪ pending |
| SM-S2 | แยก view/manage ให้ตรง Permission Matrix §4 | S1, Q3 | ปรับคีย์รายเส้น + เทสต์เคสยกเว้น (ใบลา/นัดพบ/ลงเวลา) | ⚪ blocked by Q3 |
| SM-S3 | แก้ G5+G6 — ชื่อฟังก์ชันและ path ที่ชี้ผิด 8 จุด | — | UI เรียกได้จริงทุกปุ่ม | ⚪ pending |
| SM-S4 | แก้ G8 — 3 endpoint ที่ 500 ถาวร | — | patch + migration (ถ้าต้อง) + เทสต์ | ⚪ pending |
| SM-S5 | G3 — โหมดดูอย่างเดียวบนหน้า + ซ่อนปุ่มตามสิทธิ์จริง | S2 | ใช้ `can()` ที่ดึงมาแล้วแต่ไม่ได้ใช้ | ⚪ blocked by S2 |
| SM-S6 | G9+G10 — สถิติหัวหน้าหน้าให้ถูก + ผูกแท็บกับ `?tab=` | — | ตัวเลขตรง · แชร์ลิงก์แท็บได้ | ⚪ pending |
| SM-S7 | G7 — ตัดสินชะตา 4 ไฟล์ที่เข้าถึงไม่ได้ | Q2 | ขึ้นหน้าจริง หรือลบ 791 บรรทัด | ⚪ blocked by Q2 |
| SM-S8 | G11 — audit log การเงิน/เงินเดือน | S1 | ใช้ระบบเดียวกับ SET-S9 | ⚪ pending |
| SM-S9 | G14/Q1 — จัดโครงเมนูใหม่ | Q1 | ตามคำตอบ Q1 | ⚪ blocked by Q1 |
| SM-S10 | G12 — ชุดเทสต์ของเมนูนี้ | S1–S4 | เส้นทางสิทธิ์ + happy path ต่อโมดูล | ⚪ pending |

**SM-S1 ทำได้ทันที ไม่ต้องรอใครตอบ** — และควรทำก่อนอย่างอื่นทั้งหมด
เพราะเป็นช่องโหว่จริงที่พิสูจน์แล้ว · ส่วน SM-S3/S4/S6 เป็นบั๊กชัดที่ไม่ขึ้นกับคำตอบ Q1–Q4 เช่นกัน

> 🅿️ **2026-09-07 เจ้าของโปรเจคสั่งพักไว้ก่อน — ยังไม่เริ่ม SM-S1 ในรอบนี้**
> เมื่อถึงเวลาทำ ให้ **ส่งงานเขียนโค้ดให้ `agy`** ตามสกิล `.agents/skills/agy-delegate/SKILL.md`
> (Claude = เขียนสเปค + แตก shard + ตรวจ `git diff` และรันเกณฑ์เอง · agy = ผู้เขียนโค้ด)

**Rule:** ทุก step ต้องมี verification (test / `route:list` / หน้าจริง) ก่อนขึ้นสถานะ 🟢
และงานที่แตะ `ui/` ต้องแปะบล็อกกติกา **mobile-first** ลงในสเปคเสมอ

---

## 7. Codex/agy Prompt Template (ต่อ step)

```
Context: .agents/school-admin/08-school-management.md §SM-S<n>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): <รายการไฟล์ที่อนุญาตให้แตะ — ห้ามเกินนี้>
Task: <what to do>
Constraints:
  - ห้ามเขียนเช็คสิทธิ์ซ้ำในคอนโทรลเลอร์ ให้ใช้ middleware academy.permission เท่านั้น
  - ห้ามแตะ useSchoolManagement.ts โดยไม่เช็ค 8 หน้าที่ใช้ร่วมกัน (ดู §2.1)
  - งาน ui/ ต้อง mobile-first (ตรวจที่ 375px ก่อน)
Verification: php artisan test --filter=<...> · php artisan route:list | grep <...> · ./vendor/bin/pint --test
Report back: diff --stat + ผลรันเกณฑ์แบบดิบ
```

---

## 8. Review Log

- **2026-09-07 [1]+[2]** — สแกนโค้ดจริงครบทั้งเมนู + เขียนไฟล์รองนี้ (Claude ทำเอง ไม่ได้ delegate)
  หลักฐานที่รันจริง: `php artisan route:list --json` (204 เส้นในขอบเขต · 203 มีแค่ `auth:api`) ·
  เทสต์ชั่วคราว `TempMenu8ExposureTest` 3 เคส (ยืนยัน G1 แล้ว **ลบไฟล์ทิ้ง** — working tree สะอาด) ·
  นับแถวจริงบน MySQL dev · `SHOW COLUMNS` ยืนยัน G8 ทั้ง 3 ข้อว่าไม่ใช่อาการเฉพาะ sqlite
  **ยังไม่มี step ไหนถูกส่งให้ codex/agy**

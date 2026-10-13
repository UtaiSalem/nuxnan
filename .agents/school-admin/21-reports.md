# 21 — สถิติและรายงาน (Reports & Statistics)

> ไฟล์รองของเมนู `21 สถิติและรายงาน` (`admin/reports.vue` · `reports.view`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> สถานะ: 🟡 audit ขั้น [1]+[2] เสร็จ 2026-10-13 · รอเจ้าของเคาะ Q1–Q3 ก่อนแตก step

---

## 1. Scope & Purpose

เมนูนี้คือ "ศูนย์รวมสถิติและรายงานของโรงเรียน" สำหรับฝ่ายบริหาร/นายทะเบียน (ผู้ถือ `reports.view`)
เป้าหมายเชิงธุรกิจ: ให้ผู้บริหารเห็นภาพรวมตัวเลขสำคัญ (นักเรียน/บุคลากร/ห้องเรียน/คอร์ส/ฯลฯ)
และเข้าถึงรายงานเชิงลึก (แนวโน้ม · เปรียบเทียบ · KPI · รายงานที่สร้าง/ตั้งเวลา/ส่งออก)

**ผู้ใช้ที่เกี่ยวข้อง:** owner · admin · director (ผอ.) · registrar (นายทะเบียน) — ทั้งหมดได้ `reports.view`
teacher **ไม่ได้** `reports.view` (เห็นเฉพาะข้อมูลของตัวเองผ่านเมนูอื่น)

> ⚠️ ข้อสังเกตสำคัญจาก audit: **backend ของเมนูนี้รวยกว่าหน้าจอมาก** — มี AnalyticsController (993 บรรทัด)
> + ReportController (report module #2) ครบทั้งชุด gate ด้วย `reports.view` อยู่แล้ว แต่หน้า `admin/reports.vue`
> ใช้แค่ 2 endpoint และเป็นแค่หน้า "ลิงก์ไปเมนูอื่น" ไม่มีสถิติจริงให้ดูบนหน้า

---

## 2. Current State (จากการสแกนโค้ดจริง)

### Frontend
- **Page:** [`ui/pages/academies/[name]/admin/reports.vue`](../../ui/pages/academies/[name]/admin/reports.vue) (153 บรรทัด)
  - หน้า hub: การ์ดสรุป 4 ใบ (นักเรียน/บุคลากร/ห้องเรียน/คอร์ส) + 4 กล่องลิงก์ (นักเรียน/ผลการเรียน/บุคลากร/กิจกรรม)
  - เรียก **2 endpoint เท่านั้น** (ผ่าน `Promise.allSettled`):
    - `GET /api/academies/{id}/analytics/dashboard-stats`
    - `GET /api/academies/{id}/members/stats`
  - ไม่มี page-level permission middleware (มีแค่ `definePageMeta({ layout:'main' })` + guard `auth` จาก `admin.vue` แม่)
- **เมนู:** `admin.vue` กลุ่ม "รายงาน & สถิติ" โชว์ลิงก์เมื่อ `can('reports.view')` (บรรทัด 267–268)
- **โมดูล report เต็ม (อยู่คนละเมนู):** [`ui/components/school/SchoolReportsTab.vue`](../../ui/components/school/SchoolReportsTab.vue)
  (definitions CRUD/toggle/duplicate · generate · saved · schedules · export) — ปัจจุบันฝังอยู่ใน **เมนู #8 ระบบบริหารโรงเรียน**
  (`SchoolManagement.vue` แท็บ `reports`) **ไม่ได้อยู่ใต้เมนู #21**
- **composable:** `useAcademyRole.ts` → `canViewReports = can('reports.view') || can('reports.export')` (บรรทัด 194–197)
  · report API wrappers อยู่ใน `useSchoolManagement` (ดูบันทึก backlog #2)

### Backend
- **AnalyticsController** [`app/Http/Controllers/Api/Learn/Academy/AnalyticsController.php`](../../api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AnalyticsController.php) (993 บรรทัด)
  - `dashboardStats` → `{ total_students, total_courses, classes_today, pending_grading }` — **ไม่มี** total_staff / total_classrooms
  - `studentStats` · `teacherPendingAssignments` · `getAtRiskStudents` · `overview` (อ่าน AnalyticsSnapshot ตามวัน) · `getSnapshots`
  - KPI: list/create/show/update/delete/values/recordValue · comparisons · trends · alert-rules + history/acknowledge
- **ReportController** [`app/Http/Controllers/Api/Learn/Academy/ReportController.php`](../../api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/ReportController.php)
  - definitions/saved/generate/schedules/exports (report module #2 — ปิด 100% แล้วใน backlog)
- **Routes:** [`routes/learn/academy.php`](../../api/nuxnanravel/routes/learn/academy.php)
  - `/{academy}/reports/*` — group middleware `['academy.visibility:content','academy.permission:reports.view']` ✅ (บรรทัด 937–966)
  - `/{academy}/analytics/*` — group `academy.visibility:content` + per-route:
    - `dashboard-stats` / `student-stats` / `teacher-pending-assignments` → `academy.permission` (**ไม่มีคีย์ = สมาชิกที่อนุมัติแล้วใครก็ได้**)
    - `at-risk` → `academy.permission:students.view`
    - `overview` / `snapshots` / `kpis*` / `comparisons*` / `trends*` / `alert-rules*` → `academy.permission:reports.view` ✅
  - `/{academy}/members/stats` → **อยู่ในกลุ่ม `auth:api` เปล่า ๆ ไม่มี `academy.permission` เลย** (บรรทัด 213) 🔴
- **Services:** `ReportExportService` (xlsx/csv/pdf) · `AuditLogService`
- **Models:** `AnalyticsSnapshot` · `KpiDefinition` · `ComparativeAnalytics` · `AnalyticsAlertRule` · `AnalyticsAlertHistory`
  · `ReportDefinition` · `SavedReport` · `ReportSchedule` · `ReportExport`

### Database
- `analytics_snapshots` (category/type/snapshot_date — ต้องมี job สร้าง snapshot รายวัน · `overview` คืนว่างถ้าไม่มีข้อมูล)
- `kpi_definitions` / ค่า KPI · `comparative_analytics` · `analytics_alert_rules` / `analytics_alert_histories`
- `report_definitions` / `saved_reports` / `report_schedules` / `report_exports`
- `academy_members` (members/stats + dashboardStats total_students นับจากตรงนี้ `role='student'`)

### สิทธิ์ (จาก `AcademyRole.php` role templates)
`reports.view` → director (ผอ.) · admin · registrar(นายทะเบียน) · owner (bypass) · `reports.export` → director
teacher/staff/student/guardian **ไม่ได้** `reports.view`

---

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | การ์ดสรุปตัวเลขหลัก (นักเรียน/บุคลากร/ห้องเรียน/คอร์ส) | ⚠️ | มีการ์ด แต่ staff=0 เสมอ (G2) · "ห้องเรียน" โชว์คาบวันนี้ (G3) |
| 2 | ลิงก์ไปรายงานย่อย (นักเรียน/ผลการเรียน/บุคลากร/กิจกรรม) | ✅ | ลิงก์ไปเมนูอื่น ใช้งานได้ |
| 3 | สถิติเชิงลึกบนหน้า (แนวโน้ม/เปรียบเทียบ/KPI) | ❌ | backend มีครบ แต่หน้าไม่เรียกใช้เลย (G5) |
| 4 | report builder / saved reports / schedules / export | ⚠️ | มีครบ แต่อยู่ใต้เมนู #8 ไม่ใช่ #21 (G7) |
| 5 | at-risk / teacher-pending / student-stats widget | ❌ | endpoint มี แต่หน้า #21 ไม่ใช้ (มีลิงก์ไปหน้า at-risk แยก) |
| 6 | ด่านสิทธิ์ครบทุก endpoint ที่หน้าเรียก | ❌ | `members/stats` ไม่มีด่าน (G1) |
| 7 | page-level guard `reports.view` | ⚠️ | พึ่งเมนู `show` + backend เท่านั้น (G4 — เหมือนหน้า admin อื่น) |

---

## 4. Permission Matrix

| Permission key | Owner | Admin | Director | Registrar | Teacher | Staff | Student | Guardian |
|---|---|---|---|---|---|---|---|---|
| `reports.view` | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `reports.export` | ✅ | ❌(template) | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

> หมายเหตุ: endpoint `analytics/*` เชิงรายงาน (overview/snapshots/kpis/trends/comparisons/alert-rules) gate `reports.view` ครบ
> ส่วน `dashboard-stats`/`student-stats`/`teacher-pending-assignments` gate แค่ "เป็นสมาชิก" (ตั้งใจให้ dashboard ครู/นักเรียนใช้ร่วม)

---

## 5. Gap Analysis

- **G1 🔴 (security · cross-tenant leak):** `GET /{academy}/members/stats` (`AcademyMemberController::getMemberStats`)
  อยู่ในกลุ่ม `auth:api` เปล่า ๆ **ไม่มี `academy.permission` และในเมธอดไม่มี tenant/membership check**
  ⇒ ผู้ใช้ที่ล็อกอิน (แม้ไม่ใช่สมาชิกโรงเรียนนั้น) เดา `academy_id` แล้วอ่าน **จำนวนสมาชิก + การกระจายบทบาท**
  ของโรงเรียนไหนก็ได้ (คลาสเดียวกับ #8 G1) · ความรุนแรงปานกลาง (เป็นตัวเลขรวม ไม่ใช่ PII รายคน) แต่เป็นการรั่ว tenant
  และไม่สอดคล้องกับที่เมนู gate ด้วย `reports.view`
- **G2 🟠 (FE data-shape bug):** การ์ด "บุคลากร" อ่าน `members?.stats?.by_role?.teacher` แต่ `getMemberStats`
  คืน `role_distribution.{role}` (ไม่มี `stats.by_role` เลย) ⇒ **การ์ดบุคลากร = 0 เสมอ**
- **G3 🟠 (FE mislabel):** การ์ด "ห้องเรียน" ผูกกับ `dash.classes_today` ซึ่งเป็น **จำนวนคาบที่สอนวันนี้** (ClassSchedule วันนี้)
  ไม่ใช่จำนวนห้องเรียน ⇒ ตัวเลขทำให้เข้าใจผิด (และ `pending_grading` ที่ดึงมาก็ไม่ถูกแสดงบนการ์ดใด)
- **G4 🟡 (page guard):** `reports.vue` ไม่มี middleware ระดับหน้า พึ่งเมนู `show:can('reports.view')` + ด่าน backend
  — ถ้า deep-link ตรงด้วย role ไม่มี `reports.view` หน้าโหลดเปล่า (endpoint กันบางส่วน) · เป็นแพทเทิร์นเดียวกับหน้า admin อื่น
  (เคาะรวมว่าจะเพิ่ม guard ระดับหน้าทั้งแอปไหม — ไม่ใช่งานเฉพาะเมนูนี้)
- **G5 🟠 (under-utilized backend):** ทั้งชุด analytics เชิงลึก (overview/snapshots/KPI/trends/comparisons/alert) ที่ gate `reports.view`
  **เข้าไม่ถึงจากเมนู #21 เลย** — หน้าเป็นแค่ navigation ไม่มีสถิติจริง · ผู้บริหารต้องการ "ดูตัวเลข" ไม่ใช่ลิงก์
- **G6 🟠 (ไม่มีแหล่งข้อมูล staff/classroom):** `dashboardStats` ไม่คืน `total_staff`/`total_classrooms`
  FE จึงไปหยิบจากที่ผิด (G2/G3) · ต้องมี endpoint สรุปที่ให้ครบทั้ง 4 ตัวในด่านเดียว (gate `reports.view`)
- **G7 🟡 (duplicate report surface):** report module (builder/saved/schedule/export) อยู่ใต้ **#8** ทั้งที่เมนูชื่อ "สถิติและรายงาน" คือ #21
  ⇒ ผู้ใช้หาที่สร้างรายงานไม่เจอ · ต้องเคาะว่าจะย้าย/แสดงซ้ำที่ #21 หรือคง #8

---

## 6. Implementation Tasks (ส่งให้ agy ทีละ step — รอ Q)

> รอเจ้าของเคาะ **Q1–Q3** (ด้านล่าง) ก่อนล็อก R-S2 เป็นต้นไป · **R-S1 เป็น security ไม่ต้องรอ Q**

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| R-S1 | อุด G1 — `members/stats` ติดด่าน | — | ย้าย route เข้าด่าน `academy.permission:members.view` **หรือ** `reports.view` + tenant-safe · เทสต์ 403 non-member/ไม่มีสิทธิ์ | ⚪ pending |
| R-S2 | ปิด G6 — endpoint สรุป 4 ตัวครบ | Q3 | เพิ่ม `total_staff`+`total_classrooms` ใน `dashboardStats` (หรือ endpoint ใหม่ `reports/summary` gate `reports.view`) | ⚪ รอ Q3 |
| R-S3 | ปิด G2/G3 — FE การ์ดอ่านถูก | R-S2 | reports.vue ผูกการ์ดกับฟิลด์ที่ถูก + label ถูก (mobile-first) | ⚪ รอ Q3 |
| R-S4 | ปิด G5 — ฝังสถิติจริงบนหน้า | Q1 | (ถ้า Q1=ยกเครื่อง) วิดเจ็ต trends/at-risk/pending/KPI บนหน้า #21 ดึง analytics endpoint จริง | ⚪ รอ Q1 |
| R-S5 | ปิด G7 — report builder เข้าถึงได้จาก #21 | Q2 | (ถ้า Q2=ย้าย/แสดงซ้ำ) นำ SchoolReportsTab มาแสดงใต้ #21 | ⚪ รอ Q2 |
| R-S6 | เทสต์ + verify | ทุก step | `ReportsHub*Test` + build + ตรวจจอ 375/768/1280 | ⚪ pending |

**Rule:** ทุก step ต้อง verify (build/test/manual) ก่อนขึ้น 🟢 · งาน UI ทุกชิ้นบังคับ mobile-first

### ❓ Q ที่ต้องเคาะก่อนลงมือ (R-S2 เป็นต้นไป)
- **Q1 — บทบาทของหน้า #21:** คง "หน้า hub ลิงก์" เฉย ๆ (แค่ซ่อม G1/G2/G3) **หรือ** ยกเครื่องเป็น
  "แดชบอร์ดสถิติจริง" ที่ดึง analytics backend ที่มีอยู่ (overview/trends/at-risk/KPI) มาโชว์บนหน้า?
- **Q2 — report builder:** ให้ report module (definitions/saved/schedules/export ที่อยู่ใน #8) โผล่ใต้เมนู #21 ด้วยไหม
  (ย้าย · แสดงซ้ำ · หรือคงไว้ที่ #8 แล้ว #21 แค่ลิงก์ไป)?
- **Q3 — การ์ดสรุป:** ยืนยัน 4 ตัว (นักเรียน/บุคลากร/ห้องเรียน/คอร์ส) และแหล่งข้อมูล — ให้รวมใน `dashboardStats`
  หรือทำ endpoint สรุปใหม่ `reports/summary` (gate `reports.view`)? ต้องการเพิ่มการ์ดอะไรอีก (เช่น at-risk/ค้างตรวจ)?

---

## 7. Agy Prompt Template (ต่อ step)
```
Context: .agents/school-admin/21-reports.md §R-S<n>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): <list>
Task: <what agy should do>
Constraints: Laravel Pint · tenant isolation · (UI) mobile-first block
Verification: php -l / php artisan test -c phpunit.mysql.xml --filter=... / npm run build / 375px
Report back: diff summary + test output (claude ตรวจ diff เอง)
```

## 8. Review Log
- **2026-10-13 audit [1]+[2]** — Claude สแกน FE (`admin/reports.vue` + เมนู admin.vue + SchoolReportsTab) + BE
  (AnalyticsController 993 บรรทัด · ReportController · routes analytics/reports/members·stats · CheckAcademyPermission · AcademyRole templates)
  → พบ gap G1–G7 · เขียนไฟล์รองนี้ · รอเจ้าของเคาะ Q1–Q3 ก่อนแตก R-S2+

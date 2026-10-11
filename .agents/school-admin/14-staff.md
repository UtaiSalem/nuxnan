# 14 — บุคลากร (Staff / Personnel)

> ไฟล์รองของเมนู `14 บุคลากร` ใน [OVERVIEW.md](OVERVIEW.md)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md) · [01-roles-permissions.md](01-roles-permissions.md) (คีย์ `staff.*`) · [09-departments.md](09-departments.md) (department_id)
>
> **สถานะ audit: 🔴 เสร็จขั้น [1]+[2] แล้ว 2026-10-11 — พบ gap 15 ข้อ (P0 สิทธิ์ 4 · P1 contract 10 · P2 scope 1) · รอเจ้าของเคาะ Q1–Q4 ก่อนส่ง step ให้ agy**

---

## 1. Scope & Purpose

เมนูบริหาร **บุคลากร/พนักงาน** ของโรงเรียน (ครู + เจ้าหน้าที่สายสนับสนุน) — ต่างจาก "สมาชิก" (#2) ตรงที่เป็น *แฟ้มประวัติการจ้าง* ไม่ใช่แค่สมาชิก academy

ขอบเขตเต็มตามโค้ด backend ที่มีอยู่ (4 โมดูลย่อย):
1. **Staff profile** — แฟ้มบุคลากร (ตำแหน่ง · ประเภทการจ้าง · วันเริ่มงาน · ฝ่าย · สถานะ) + ตำแหน่ง (Position)
2. **Staff attendance** — ลงเวลาเข้า-ออกงานของบุคลากร (check-in/out · รายงานรายวัน/รายคน)
3. **Leave requests** — ใบลา (ยื่น/อนุมัติ/ปฏิเสธ/ยกเลิก · ประเภทลา · ยอดวันลาคงเหลือ)
4. **Payroll** — เงินเดือน (สร้าง/อนุมัติ/จ่าย · bulk-generate · โครงสร้างเงินเดือน · payslip)

**ผู้ใช้:** owner/admin (เต็ม) · ฝ่ายบุคคล/ธุรการ (จัดการ) · ครู/เจ้าหน้าที่ (ลงเวลา-ยื่นลาของตัวเอง + ดูทำเนียบ)

> ⚠️ **ปัจจุบัน UI มีแค่โมดูล 1 (Staff profile + Position)** — โมดูล 2/3/4 มี backend ครบแต่ยังไม่มีหน้า admin (ดู G15)

---

## 2. Current State (จากการสแกนโค้ดจริง 2026-10-11)

### Frontend
- Page: [`ui/pages/academies/[name]/admin/staff.vue`](../../ui/pages/academies/[name]/admin/staff.vue) (739 บรรทัด)
  - ตาราง + filter (search/position/status) + pagination + modal create/edit + modal ตำแหน่ง (read-only list)
  - gate ทั้งหน้าด้วย `useAcademyRole().isAdmin` → **ถ้าไม่ใช่ admin เด้งออก** (`/academies/{name}/admin`)
  - เรียก API: `GET/POST /academies/{id}/staff`, `GET .../staff/positions`, `GET .../staff/summary`,
    `PUT .../staff/{id}` (แก้), `PUT .../staff/{id}/status`, `DELETE .../staff/{id}`, `GET .../members`
  - ❌ ไม่มีหน้า/คอมโพเนนต์สำหรับ attendance · leave · payroll
- Component: `ui/components/school/SchoolStaffTab.vue` (แท็บใน school-management #8 — คนละ flow อย่าสับสน)
- Composable: `ui/composables/useAcademyRole.ts` (`can` / `isAdmin`), `ui/composables/useApi.ts`
  - 🔴 `api.put()` ส่ง `method:'PUT'` จริง ไม่มี method-spoofing (`useApi.ts:612-618`)

### Backend
- Controllers (`app/Http/Controllers/Api/Learn/Academy/`):
  - `StaffController.php` — index/show/store/update/updateStatus/destroy/positions/storePosition/updatePosition/destroyPosition/directory/summary
  - `StaffAttendanceController.php` — index/show/checkIn/checkOut/store/update/destroy/dailyReport/staffReport
  - `LeaveRequestController.php` — index/show/store/approve/reject/cancel/leaveTypes/storeLeaveType/updateLeaveType/staffLeaveBalance/summary
  - `PayrollController.php` — index/show/store/update/addItem/removeItem/approve/markAsPaid/bulkGenerate/salaryStructures/storeSalaryStructure/summary/payslip
- Routes: [`routes/learn/academy.php:766-825`](../../api/nuxnanravel/routes/learn/academy.php) — 4 กลุ่ม prefix `{academy}/staff`, `/staff-attendance`, `/leave-requests`, `/payroll`
- Models (`app/Models/`): `StaffProfile`, `Position`, `StaffAttendance`, `LeaveRequest`, `LeaveType`, `Payroll`, `PayrollItem`, `StaffTraining`, `PerformanceReview`, `SalaryStructure`(?)
- Middleware: `academy.visibility:content` + `academy.permission:staff.view` (ด่านผ่าน `Academy::userCan()` — isAdmin bypass)
- Service: `AuditLogService` (log ทุก action)

### Database (ตารางหลัก)
- `staff_profiles` (SoftDeletes) — academy_id · user_id · department_id · position_id · employee_id · employment_type · status · hire_date · base_salary ฯลฯ
- `positions` — academy_id · department_id · name · code · min/max_salary · is_teaching_position · is_active
- `staff_attendances` · `leave_requests` · `leave_types` · `payrolls` · `payroll_items` · `salary_structures`

### Permission keys (seed แล้วใน reconcile migration `2026_08_29_000002`)
| role | staff.view | staff.manage |
|---|---|---|
| director | ✅ | ✅ |
| admin | ✅ | ✅ |
| teacher | ✅ | ❌ |
| registrar | ✅ | ❌ |
| staff | ❌ | ❌ |
> owner/admin bypass ทุกคีย์ผ่าน `Academy::userCan()` → `isAdmin()` · teacher/registrar มีแค่ `staff.view`

---

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | ดูรายชื่อบุคลากร + filter/search/pagination | 🔴 | BE คืน paginator ผิดรูป → ตารางไม่ render (G5) |
| 2 | เพิ่มบุคลากร | 🔴 | 422 เสมอ — field name mismatch (G6/G7) |
| 3 | แก้ไขบุคลากร | 🔴 | 405 — PUT ชน PATCH (G8) + prefill ผิดฟิลด์ (G13) |
| 4 | เปลี่ยนสถานะ | 🔴 | 405 (G8) + vocab ไม่ตรง (G11) |
| 5 | ลบบุคลากร (soft delete) | ⚠️ | เส้นมี แต่สิทธิ์เปิดกว้างเกิน (G1) |
| 6 | filter ตามสถานะ/ประเภท | 🔴 | 500 — ไม่มี scopeByStatus/byType (G9) |
| 7 | สรุปจำนวน (การ์ดสถิติ) | ⚠️ | total ถูก · active/on_leave = 0 (G10) |
| 8 | จัดการตำแหน่ง (Position) | ⚠️ | list read-only · staff_count=0 (G14) · สิทธิ์เขียนเปิดกว้าง (G1) |
| 9 | ลงเวลาบุคลากร (attendance) | ❌ UI | BE ครบ · ไม่มีหน้า admin (G15) |
| 10 | ใบลา (leave) | ❌ UI | BE ครบ · ไม่มีหน้า admin (G15) |
| 11 | เงินเดือน (payroll) | ❌ UI | BE ครบ · ไม่มีหน้า admin (G15) · สิทธิ์อ่าน/จ่ายเปิดกว้าง (G2) |
| 12 | แฟ้มรายคน (show + ประวัติ) | ❌ UI | BE `show()` ครบ · FE ยังไม่เปิดหน้า detail |

---

## 4. Permission Matrix (ที่ควรเป็นหลังแก้)

| Permission key | Owner | Admin | ฝ่ายบุคคล | Teacher | Staff | Student/Guardian |
|---|---|---|---|---|---|---|
| `staff.view` (อ่านทำเนียบ/ลงเวลา/ใบลา) | ✅ | ✅ | ✅ | ✅ (ดูทำเนียบ) | ❌ | ❌ |
| `staff.manage` (เพิ่ม/แก้/ลบ/ตำแหน่ง/อนุมัติลา) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| check-in/out ของตัวเอง | ✅ | ✅ | ✅ | ✅ (สมาชิก) | ✅ (สมาชิก) | ❌ |
| ยื่น/ยกเลิกใบลาของตัวเอง | ✅ | ✅ | ✅ | ✅ (สมาชิก) | ✅ (สมาชิก) | ❌ |
| อ่าน/จ่าย payroll (เงินเดือน) | ✅ | ✅ | ✅ (ผูก Q1) | ❌ | ❌ | ❌ |

> ปัจจุบัน check-in/out · ยื่นลา · ยกเลิกลา ใช้ `academy.permission` เปล่า (สมาชิกที่อนุมัติแล้ว) — **ถูกต้องตามดีไซน์** (เหมือน 5 เคสยกเว้นใน #8)

---

## 5. Gap Analysis

### P0 — Authorization granularity (ช่องโหว่สิทธิ์ · พิสูจน์จาก route + seed)

- **G1** — กลุ่ม `{academy}/staff` ทั้งก้อน gate ด้วย `academy.permission:staff.view` เท่านั้น (`academy.php:767`)
  รวมถึง `store`/`update`/`updateStatus`/`destroy` + `storePosition`/`updatePosition`/`destroyPosition`
  ⇒ **teacher/registrar (มีแค่ `staff.view`) เพิ่ม/แก้/ลบบุคลากร และ CRUD ตำแหน่งได้** ผ่าน API ตรง
  (แม้ UI จะเด้ง non-admin ออก แต่ API ยังเปิด) → เส้นเขียนต้องเป็น `staff.manage`
- **G2** — กลุ่ม `{academy}/payroll` ทั้งก้อน gate ด้วย `staff.view` (`academy.php:811`)
  ⇒ ผู้มี `staff.view` **อ่านเงินเดือนทุกคน + สร้าง/อนุมัติ/จ่าย (markAsPaid) ได้** → P0 (ข้อมูลอ่อนไหวสุด)
  เส้นเขียนต้อง `staff.manage` · การอ่านเงินเดือนอาจต้องแยกคีย์ (ผูก Q1)
- **G3** — `{academy}/staff-attendance` เส้น `store`/`update`/`destroy` gate ด้วย `staff.view` (`academy.php:785,791,792`)
  ⇒ แก้/ลบบันทึกเวลาของคนอื่นด้วยสิทธิ์อ่าน → ควร `staff.manage`
- **G4** — `{academy}/leave-requests` เส้น `approve`/`reject`/`storeLeaveType`/`updateLeaveType` gate ด้วย `staff.view` (`academy.php:801,802,805,806`)
  ⇒ ผู้มีสิทธิ์อ่านอนุมัติ/ปฏิเสธใบลาได้ → ควร `staff.manage`

### P1 — FE↔BE contract (ทำให้เมนูใช้งานไม่ได้จริง)

- **G5** — `StaffController@index` คืน `['data' => $staff]` โดย `$staff` เป็น `LengthAwarePaginator` ทั้งก้อน
  (`StaffController.php:61-67`) แต่ FE อ่าน `response.data` เป็น**อาเรย์** + `response.pagination` แยก
  (`staff.vue:118-123`) ⇒ `staff.value` กลายเป็นอ็อบเจกต์ paginator · `v-for` วนคีย์ขยะ · pagination ตาย
  → BE ควรคืน `{data: $staff->items(), pagination: {...}}` (house style เดียวกับ members endpoint) · ผูก Q2
- **G6** — create ส่ง field `employee_type` แต่ BE validate `employment_type` (`required|in:...`) (`StaffController.php:103`)
  ⇒ **POST /staff คืน 422 เสมอ** ("employment_type required") · FE (`staff.vue:51,228`)
- **G7** — FE ส่ง `department` เป็น string (ช่องกรอกอิสระ `staff.vue:56,670-677`) แต่ BE รับ `department_id` (`exists:departments,id`)
  ⇒ ฝ่าย/แผนกไม่เคยบันทึก · ควรเปลี่ยนเป็น select ผูกกับ Departments (#9)
- **G8** — FE เรียก `api.put(.../staff/{id})` และ `api.put(.../staff/{id}/status)` (`staff.vue:265,299`)
  แต่ route เป็น `PATCH` (`academy.php:777,778`) + `useApi.put` ส่ง PUT จริง ⇒ **405 Method Not Allowed**
  → แก้ FE เป็น `api.patch(...)` (ทางที่สะอาดสุด)
- **G9** — `index()` เรียก `$query->byStatus($request->status)` (`:32`) และ `$query->byType(...)` (`:37`)
  แต่ `StaffProfile` **ไม่มี** `scopeByStatus`/`scopeByType` (มีแค่ `scopeActive`/`scopeByEmploymentType`/`scopeByDepartment`/`scopeByPosition`/`scopeSearch`)
  ⇒ เลือก filter สถานะ → **BadMethodCallException 500**
- **G10** — `summary()` คืน `by_status` เป็น map คีย์ตามสถานะ (`StaffController.php:401-408`)
  แต่ FE อ่าน `summary.active`/`summary.on_leave` ตรง ๆ (`staff.vue:420,432`) ⇒ การ์ดทั้งสองเป็น 0 เสมอ (total ถูก)
- **G11** — vocab สถานะไม่ตรง: FE มี `retired` (`staff.vue:68`) ซึ่ง BE/`updateStatus` validate `in:active,on_leave,suspended,resigned,terminated` (`:186`) ⇒ ตั้ง retired → 422 · FE ขาด `suspended`/`terminated`
- **G12** — vocab ประเภทจ้างไม่ตรง: FE มี `intern` (`staff.vue:75`) · BE/model ใช้ `temporary` (ไม่มี intern) ⇒ ตั้ง intern → 422 (store validate `in:...,temporary`)
- **G13** — `openEditModal` อ่าน `staffMember.employee_type`/`.department` (`staff.vue:211,214`) ซึ่งเป็นชื่อฟิลด์ผิด
  (จริงคือ `employment_type` + relation `department.name`/`department_id`) ⇒ ฟอร์มแก้ไขเด้งค่าว่าง
- **G14** — modal ตำแหน่งอ่าน `pos.staff_count` (`staff.vue:730`) แต่ `positions()` ไม่ได้ `withCount('staffProfiles')` ⇒ โชว์ 0 เสมอ

### P2 — Missing admin UI (scope)

- **G15** — attendance · leave · payroll มี backend ครบ (route + controller + model) แต่ **ไม่มีหน้า admin เลย**
  ต้องตัดสิน (Q3) ว่าจะทำเป็นแท็บใต้ #14 หรือแยกเมนู และทำรอบนี้หรือ defer

---

## 6. Questions รอเจ้าของเคาะ (ก่อนส่ง step ให้ agy)

- **Q1 (สิทธิ์ payroll):** การ *อ่าน* เงินเดือนใช้ `staff.view` ได้เลยไหม หรือต้องแยกคีย์ (เช่นใช้ `finance.view` หรือสร้าง `payroll.*`)?
  ส่วนการ *เขียน/จ่าย* จะใช้ `staff.manage` หรือ `finance.manage`? (เกี่ยวกับงาน finance ใน #8)
- **Q2 (รูป response):** ให้ปรับ BE `index` (และ paginator ที่เหลือ) คืน `{data: items[], pagination:{...}}` ตาม house style (เหมือน members) ใช่ไหม — หรือจะแก้ FE ให้อ่าน `response.data.data` แทน
- **Q3 (scope รอบนี้):** เมนู #14 รอบนี้ทำแค่ **Staff profile + Position** ให้ครบก่อน แล้ว attendance/leave/payroll ค่อยทำรอบถัดไป ใช่ไหม? (แนะนำ: ปิด P0+P1 ของ staff ก่อน · G15 ยกเป็นเฟสถัดไป)
- **Q4 (department):** เปลี่ยนช่อง "ฝ่าย/แผนก" เป็น dropdown ผูก `department_id` (จาก #9) ใช่ไหม — หรือคงเป็น free-text (ถ้าคง ต้องเพิ่มคอลัมน์/ลบออกจากฟอร์ม)

---

## 7. Implementation Tasks (ส่งให้ agy ทีละ step — ยังไม่เริ่มจน Q เคาะ)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| ST-S1 | แยกสิทธิ์ view/manage ทุกเส้นเขียน (G1–G4) | Q1 | routes/learn/academy.php 4 กลุ่ม + เทสต์ guard | ⚪ |
| ST-S2 | แก้ response shape index + pagination (G5) | Q2 | StaffController@index + เทสต์ | ⚪ |
| ST-S3 | แก้ scopeByStatus/byType หรือเลิกเรียก (G9) | — | StaffProfile scopes / index | ⚪ |
| ST-S4 | FE contract: patch แทน put · field names · vocab · summary · staff_count (G6,G8,G10–G14) | Q4 | staff.vue + positions() withCount | ⚪ |
| ST-S5 | department เป็น select department_id (G7) | Q4 | staff.vue + store/update validate | ⚪ |
| ST-S6 | เทสต์รวม (CRUD + filter + สิทธิ์) บน MySQL จริง | S1–S5 | tests/.../StaffManagementTest.php | ⚪ |
| ST-S7 | (เฟสถัดไป) UI attendance/leave/payroll (G15) | Q3 | — | 🔵 deferred |

**Rule:** ทุก step verify (build/test/manual 375px) ก่อนขึ้น 🟢 · Claude ตรวจ `git diff` + รันเกณฑ์เอง ไม่เชื่อรายงาน agy

## 8. Codex/agy Prompt Template (ต่อ step)
```
Context: .agents/school-admin/14-staff.md §<step-id>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): <รายการ>
Task: <what to do>
Constraints: mobile-first (ถ้าแตะ ui/) · ไม่แตะ .env/vendor/node_modules · Pint ผ่าน
Verification: php artisan test -c phpunit.mysql.xml --filter=<...> · npm run build · 375px
Report back: diff summary + ผลเทสต์
```

## 9. Review Log
- **2026-10-11** — ขั้น [1] สแกนโค้ด + [2] เขียนไฟล์รองเสร็จ (Claude) · พบ gap 15 ข้อ (P0×4 · P1×10 · P2×1) · ยังไม่ส่ง step ให้ agy · รอ Q1–Q4
</content>
</invoke>

# 14 — บุคลากร (Staff / Personnel)

> ไฟล์รองของเมนู **#14 บุคลากร** ใน [OVERVIEW.md](OVERVIEW.md)
> อ่านคู่กับ OVERVIEW.md · สแกนโค้ดจริงเมื่อ 2026-10-07 (ขั้น [1]+[2] ของ loop · ยังไม่ส่ง step ให้ agy)

## 1. Scope & Purpose

เมนูจัดการ **แฟ้มบุคลากร (staff profile)** ของโรงเรียน — ทะเบียนครู/เจ้าหน้าที่ ผูกกับบัญชีสมาชิกที่มีอยู่
พร้อมตำแหน่ง (position) · ประเภทการจ้าง · สถานะการทำงาน · ประวัติ

**อยู่ในขอบเขต (ตามหน้า `admin/staff.vue` ปัจจุบัน):**
- CRUD แฟ้มบุคลากร (เลือกสมาชิก → ผูกตำแหน่ง/ประเภท/วันเริ่มงาน/ฝ่าย)
- เปลี่ยนสถานะบุคลากร (ทำงาน/ลาพัก/ลาออก/พักงาน/เลิกจ้าง)
- จัดการตำแหน่ง (position) · การ์ดสรุป · ค้นหา/กรอง/แบ่งหน้า

**อยู่คนละโมดูล (มี controller/route ของตัวเอง — ต้องเคาะ Q2 ว่านับเป็นเมนูนี้ไหม):**
- ลงเวลา `StaffAttendanceController` · ใบลา `LeaveRequestController` · เงินเดือน `PayrollController` ·
  ประเมินผล `PerformanceReview` · อบรม `StaffTraining` — ทั้งหมดผูก `staff_profile_id`

## 2. Current State (จากการสแกนโค้ดจริง 2026-10-07)

### Frontend — `ui/pages/academies/[name]/admin/staff.vue` (738 บรรทัด)
- list + การ์ดสรุป 4 ใบ + modal สร้าง/แก้ + modal ตำแหน่ง (อ่านอย่างเดียว) + ค้นหา/กรอง/แบ่งหน้า
- gate เข้าหน้าด้วย **`isAdmin.value` อย่างเดียว** (redirect ออกถ้าไม่ใช่ owner/admin)
- เรียก: `GET/POST /api/academies/{id}/staff` · `GET .../staff/positions` · `GET .../staff/summary` ·
  `PUT .../staff/{id}` · `PUT .../staff/{id}/status` · `DELETE .../staff/{id}` · `GET .../members`
- มี `SchoolStaffTab.vue` (687 บรรทัด) เป็นอีกจุดที่แตะ staff ผ่านเมนู #8 (useSchoolManagement) — คนละหน้า

### Backend — `StaffController` (439 บรรทัด)
- index/show/store/update/updateStatus/destroy · positions/storePosition/updatePosition/destroyPosition · directory/summary
- helper `authorizeStaff()` = **ชื่อหลอก** เช็คแค่ `academy_id` ตรง (tenant) ไม่ได้เช็คสิทธิ์ผู้เรียก
- `generateEmployeeId()` (ของ controller เอง) format `EMP` + ปี 4 หลัก + 4 digit

### Routes — `routes/learn/academy.php:767–824`
- กลุ่ม `{academy}/staff` = `['academy.visibility:content', 'academy.permission:staff.view']` (จาก SM-S1 เมนู #8)
  ⇒ **ทุก method รวม store/update/updateStatus/destroy + positions CRUD ใช้แค่ `staff.view`** (ไม่มี `staff.manage`)
- กลุ่มพี่น้อง: `staff-attendance` · `leave-requests` · `payroll` (มี `staff.view` ที่กลุ่ม · check-in/out เป็น member เปล่า)

### Models & Schema (authoritative — migration `2026_02_04_100003_create_staff_system_tables.php`)
- `staff_profiles`: **`first_name`/`last_name` = NOT NULL** · `employment_type` enum(full_time,part_time,contract,**temporary**) ·
  `status` enum(active,on_leave,**suspended**,resigned,**terminated**) · `resignation_date`/`resignation_reason` ·
  `emergency_contact_name`/`emergency_contact_phone` · `phone` · `profile_image` · unique[academy_id,user_id] + [academy_id,employee_id]
  **ไม่มีคอลัมน์:** `supervisor_id` · `base_salary` · `work_location` · `phone_extension` · `probation_end_date` ·
  `emergency_contact`(json) · `termination_date` · `termination_reason`
- `StaffProfile` model scopes: `active` · `byDepartment` · `byPosition` · **`byEmploymentType`** · `search` · (ไม่มี `byStatus`/`byType`) ·
  relations: user/academy/department/position/attendances/leaveRequests/payrolls/performanceReviews/trainings (**ไม่มี `supervisor`**)
- `positions`: academy_id · department_id · code · name · level · description · responsibilities(json) · min/max_salary ·
  is_teaching_position · is_active · display_order (**ไม่มี** `requirements`) · ไม่มี unique บน code

## 3. Gap Analysis — 🔴 โมดูลนี้ "สร้างไว้แต่ไม่เคยต่อ backend จริง" (พังหลายชั้น)

### FE ↔ BE contract พัง (ผู้ใช้กดแล้วพัง/ไม่มีผล)
| # | Gap | อาการ |
|---|---|---|
| **F1** | หน้า gate ด้วย `isAdmin` เท่านั้น แต่เมนู (`admin.vue:209`) โชว์ลิงก์เมื่อ `can('staff.view')` | ผู้ถือ `staff.view` เห็นเมนู กดแล้ว **ถูกเด้งออก** (ซ้ำบทเรียน G21) |
| **F2** | list: BE คืน `data = paginator object` แต่ FE ทำ `staff.value = response.data` (คาดเป็น array) + อ่าน `response.pagination` (ไม่มี) | v-for วน key ของ paginator → **ตารางเพี้ยน · แบ่งหน้าไม่ขยับ** |
| **F3** | summary: BE ซ้อนใต้ `by_status.{active,on_leave,...}` แต่ FE อ่าน `summary.active`/`summary.on_leave` ตรง ๆ | การ์ด "ทำงานอยู่/ลาพัก" **ขึ้น 0 เสมอ** (total ใช้ได้) |
| **F4** | FE ส่ง query เป็น arg ที่ 2 (ApiCallOptions) ไม่ใช่ใน URL/`query` → ofetch ทิ้ง | **ค้นหา/กรอง/แบ่งหน้าไม่ถูกส่งเลย** (ทั้งแอปใช้ `?${params}` ใน URL) |
| **F5** | create: FE ส่ง `employee_type` (BE คาด `employment_type` required) + `department` free-text (BE คาด `department_id`) + ไม่ส่ง `first_name`/`last_name` (คอลัมน์ NOT NULL) · enum มี `intern` (BE มี `temporary`) | **สร้างบุคลากรพังถาวร** (422 จาก employment_type · ถ้าผ่านก็ 500 จาก first_name NOT NULL) |
| **F6** | update/updateStatus: FE ใช้ `api.put` แต่ route เป็น **PATCH** | **405/404** แก้ไข/เปลี่ยนสถานะไม่ได้ |
| **F7** | `updateStatus()` FE นิยามไว้แต่ **ไม่มีปุ่มเรียก** · `getStatusInfo` map `retired` (ไม่มีจริง) และไม่ครอบ `suspended`/`terminated` | เปลี่ยนสถานะทำไม่ได้จากหน้า · สถานะ suspended/terminated โชว์เป็นเขียว(active) ผิด |
| **F8** | modal "ตำแหน่ง" อ่านอย่างเดียว (ไม่มีสร้าง/แก้/ลบ) แต่ create-staff บังคับ `position_id` | **ไม่มีทางสร้างตำแหน่งแรก → เพิ่มบุคลากรไม่ได้เลยตั้งแต่ต้น** (dead-end) · BE มี storePosition/updatePosition/destroyPosition ไม่ถูกใช้ |

### BE schema-drift / bug (500 แฝง — แบบเดียวกับ Expense เมนู #8)
| # | Gap | อาการ |
|---|---|---|
| **B1** | `index()` เรียก `byStatus()`/`byType()` scope ที่ **ไม่มี** (มีแค่ `byEmploymentType`) | 500 เมื่อส่ง `status`/`type` (ตอนนี้ถูกบังด้วย F4) |
| **B2** | `show()` eager-load `supervisor.user` แต่ **ไม่มี relationship `supervisor`** | `GET /staff/{id}` **500 ทุกครั้ง** |
| **B3** | `store()` require `employment_type` · ไม่เก็บ `first_name`/`last_name` (NOT NULL) · validate ฟิลด์ไม่มีคอลัมน์ (`supervisor_id`,`base_salary`,`work_location`,`phone_extension`,`probation_end_date`,`emergency_contact`) | create 500 แม้ผ่าน validation · ฟิลด์ extra ถูกทิ้งเงียบ |
| **B4** | `updateStatus()` เขียน `termination_date`/`termination_reason` (**ไม่มีคอลัมน์** · schema คือ `resignation_date`/`resignation_reason`) | 500 เมื่อสถานะ = resigned/terminated |
| **B5** | `generateEmployeeId()` ซ้ำซ้อน 2 ที่ format ต่างกัน (controller 4 หลักปี · model 2 หลัก) | ความเสี่ยงต่ำ · ควรรวมเป็นที่เดียว |
| **B6** | `storePosition/updatePosition` validate `requirements` (ไม่อยู่ใน fillable → ทิ้ง) · `code` unique **global** ไม่ใช่ per-academy | ฟิลด์หาย · โค้ดตำแหน่งชนข้ามโรงเรียน |

### Security / Test
| # | Gap | อาการ |
|---|---|---|
| **B7** | ทุก write route (store/update/updateStatus/destroy + positions CRUD) อยู่ใต้ `staff.view` | **ผู้ถือ view อย่างเดียวแก้/ลบบุคลากรได้** (SM-S2 pattern · ควรแยก `staff.manage`) |
| **B8** | ไม่มีเทสต์ของโมดูล staff | — |

## 4. Permission Matrix (เป้าหมาย — ต้องยืนยัน Q3)

| Permission key | Owner | Admin | ฝ่ายบุคคล/HR | Teacher | Student |
|---|---|---|---|---|---|
| `staff.view` (ดูรายชื่อ/แฟ้ม/ตำแหน่ง/สรุป) | ✅ | ✅ | ✅ | ⚠️ (directory?) | ❌ |
| `staff.manage` (สร้าง/แก้/เปลี่ยนสถานะ/ลบ + CRUD ตำแหน่ง) | ✅ | ✅ | ✅ | ❌ | ❌ |

> คีย์ `staff.view`/`staff.manage` มีครบใน `AcademyPermission` อยู่แล้ว · FE ต้องเปิดหน้าให้ `staff.view` (ไม่ใช่แค่ admin)

## 5. คำถามที่ต้องให้เจ้าของโปรเจคเคาะก่อนลงมือ

- **Q1 — identity ของบุคลากรมาจากไหน:** แฟ้มบุคลากรผูกบัญชีสมาชิก → ใช้ชื่อ/รูปจาก user account (ให้ `first_name`/`last_name` เป็น nullable/derived) **หรือ** เก็บชื่อแยกในแฟ้ม (FE ต้องเพิ่มช่องกรอก)? (ตัดสิน B3/F5)
- **Q2 — ขอบเขตเมนู #14:** เป็น **ทะเบียนบุคลากร + ตำแหน่ง** ล้วน หรือรวม ลงเวลา/ใบลา/เงินเดือน/ประเมิน/อบรม เข้ามาด้วย? (ถ้าแยก = งานคนละเมนู)
- **Q3 — สิทธิ์:** write ใช้ `staff.manage` · read ใช้ `staff.view` · เปิดหน้าให้ `staff.view` (ไม่ใช่แค่ admin) — ยืนยัน · role ไหนถือ `staff.manage` (HR / department admin ของฝ่ายบุคคล?)
- **Q4 — ฝ่าย/แผนก:** ช่อง "ฝ่าย" ให้เป็น free-text หรือผูก `department_id` (dropdown จากเมนู #9)?
- **Q5 — enum canonical:** ประเภทจ้าง = full_time/part_time/contract/temporary (ตัด `intern`) · สถานะ = active/on_leave/suspended/resigned/terminated (ตัด `retired`) — ยืนยัน

## 6. Implementation Tasks (ส่งให้ agy ทีละ step — ยังไม่เริ่ม · รอ Q1–Q5)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| ST-S1 | **ปิดช่องโหว่สิทธิ์ (B7)** — แยก write routes → `staff.manage` · เปิดหน้า FE ให้ `staff.view` (แก้ F1) | Q3 | `academy.php` · `staff.vue` | ⚪ |
| ST-S2 | **ซ่อม backend schema-drift (B1–B4,B6)** — เพิ่ม/แก้ scope `byStatus` · ตัด/แก้ `supervisor` · store/update ให้ตรง schema จริง · updateStatus → resignation_* | Q1,Q5 | `StaffController` · `StaffProfile` | ⚪ |
| ST-S3 | **ซ่อม FE contract (F2–F6)** — list อ่าน paginator · summary อ่าน by_status · query ใน URL · payload `employment_type`/`department_id` · PUT→PATCH | Q1,Q4,Q5 | `staff.vue` | ⚪ |
| ST-S4 | **UI จัดการตำแหน่ง (F8)** — เพิ่มสร้าง/แก้/ลบ position ใน modal | Q3 | `staff.vue` | ⚪ |
| ST-S5 | **เปลี่ยนสถานะ (F7)** — wire ปุ่ม updateStatus · map สถานะให้ครบ 5 ค่า | Q5 | `staff.vue` | ⚪ |
| ST-S6 | เทสต์ authz (view/manage/non-member/cross-academy) + CRUD + schema (B8) | ST-S1–S3 | `StaffAuthzTest.php` | ⚪ |

**Rule:** ทุก step verify (build/test บน MySQL) ก่อน 🟢 · รายงาน agy เชื่อไม่ได้ ต้อง `git diff --stat` + `git diff` + รันเกณฑ์เอง
UI ทุก step ต้องแปะกติกา **mobile-first** ในสเปค

## 7. Codex/agy Prompt Template (ต่อ step)
```
Context: .agents/school-admin/14-staff.md §<step-id>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): routes/learn/academy.php · StaffController.php · StaffProfile.php · staff.vue (+ test)
Task: <what to do>
Constraints: ไม่เปลี่ยน response shape ที่ FE อื่นใช้ · tenant isolation กันข้ามโรงเรียน · ไม่แตะ attendance/leave/payroll (คนละเมนู เว้น Q2 เคาะรวม) · mobile-first
Verification: php artisan test -c phpunit.mysql.xml --filter=Staff
Report back: diff + ผลเทสต์
```

## 8. Review Log
- **2026-10-07** — ขั้น [1] สแกนโค้ด + [2] เขียนไฟล์รองนี้ เสร็จ · พบว่าโมดูลพังหลายชั้น (FE↔BE contract + schema-drift + ไม่มี UI สร้างตำแหน่ง + สิทธิ์ไม่แยก view/manage) · **รอเจ้าของเคาะ Q1–Q5 ก่อนเริ่ม ST-S1**

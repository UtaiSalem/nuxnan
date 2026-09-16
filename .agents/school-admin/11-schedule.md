# 11 — ตารางเรียน

> ไฟล์รองของเมนู **#11 ตารางเรียน** ใน [OVERVIEW.md](OVERVIEW.md)
> สแกนโค้ดจริง + ยิง API จริงเมื่อ **2026-09-16** (ขั้น [1]+[2] ของ workflow)

---

## 0. บรรทัดสรุปก่อนอ่านอย่างอื่น

🔴 **เมนูนี้ "ตายสนิท" ทั้งเมนู — ไม่ใช่แค่มี gap**
ทุก endpoint ของ `/schedules` ตอบ **404 ให้ทุกคน รวมทั้งเจ้าของโรงเรียน** เพราะด่านหน้ากับคอนโทรลเลอร์
ค้นหาโรงเรียนคนละคีย์กัน (ด่าน = `id`, คอนโทรลเลอร์ = `name`) → ไม่มีรูปแบบ URL ไหนผ่านทั้งสองชั้นได้เลย
พิสูจน์ด้วยการยิงจริงแล้ว (§2.5) และต่อให้ปลดล็อกข้อนี้ได้ ยังมีอีก 4 จุดที่ทำให้หน้าใช้งานไม่ได้อยู่ดี
(ไม่ส่ง query param · ใช้ PUT ชน PATCH · endpoint วิชาผิด path · ไม่ส่ง `semester_id`)

⇒ ต้องถือว่าเมนูนี้ **ยังไม่เคยถูกใช้งานจริงแม้แต่ครั้งเดียว** ข้อมูล 5 แถวใน DB คือ seed เดโมจาก 2026-02-05
⇒ ห้ามวางแผนแบบ "ปรับปรุงของเดิม" — ต้องวางแผนแบบ "ต่อสายให้ติดก่อน แล้วค่อยเติมฟีเจอร์"

---

## 1. Scope & Purpose

จัดตารางเรียน/ตารางสอนของโรงเรียน: กำหนดว่า **ห้องเรียนไหน · วันไหน · คาบ/เวลาไหน · เรียนวิชาอะไร · ครูคนไหนสอน · ที่ห้องไหน**
ภายใต้ **ปีการศึกษา + ภาคเรียน** หนึ่ง ๆ แล้วให้แต่ละบทบาทเปิดดูมุมของตัวเองได้

ผู้ใช้ที่เกี่ยวข้อง

| บทบาท | ใช้ทำอะไร |
|---|---|
| ฝ่ายวิชาการ / นายทะเบียน | จัดตารางทั้งโรงเรียน ตรวจการชนกัน ดูภาระงานสอนของครู |
| ครู | ดูตารางสอนของตัวเอง (รายสัปดาห์/วันนี้) ใช้เป็นจุดตั้งต้นของการเช็คชื่อรายคาบ |
| นักเรียน / ผู้ปกครอง | ดูตารางเรียนของห้องตัวเอง/ของบุตรหลาน |
| ผู้บริหาร | ดูภาพรวมการใช้ห้องและภาระครู |

**เมนูข้างเคียงที่ผูกกัน:** #10 ห้องเรียน (แหล่งห้อง) · #12 คอร์สเรียน / #13 หลักสูตร (แหล่งวิชา) ·
#14 บุคลากร (แหล่งครู) · #18 การเข้าเรียน (เช็คชื่อรายคาบต้องอ้างคาบจากเมนูนี้)

---

## 2. Current State (จากการสแกนโค้ดจริง 2026-09-16)

### 2.1 Frontend

| ไฟล์ | บทบาท | สภาพ |
|---|---|---|
| `ui/pages/academies/[name]/admin/schedule.vue` (674 บรรทัด) | หน้าเดียวของเมนูนี้ — กริดตาราง + modal เพิ่ม/แก้/ลบ | 🔴 ยิง API ไม่ติดสักเส้น |
| `ui/pages/academies/[name]/dashboard/teacher.vue:128` | การ์ด "คาบสอนวันนี้" เรียก `/schedules/today` ด้วย **academyId** | 🔴 404 |
| `ui/components/school/SchoolAcademicTab.vue:147,276,347` | แท็บ "ตารางเรียน" ในเมนู #8 | 🔴 404 |
| `ui/composables/useSchoolManagement.ts:58-67` | wrapper `listSchedules/create/update/delete` ด้วย **academyId** | 🔴 404 (แต่ใช้ PATCH ถูกแล้ว) |

- **ไม่มี** component ย่อย ไม่มี composable เฉพาะ ไม่มี Pinia store ของเมนูนี้ — ตรรกะทั้งหมดยัดอยู่ในหน้าเดียว
- **ไม่มี** หน้า "ตารางสอนของฉัน" (ครู) และ "ตารางเรียนของฉัน" (นักเรียน/ผู้ปกครอง)
- เมนูซ้าย `ui/pages/academies/[name]/admin.vue:129-133` แสดงเมื่อ `can('schedule.view') || can('academy.view')`

### 2.2 Backend

| ไฟล์ | หมายเหตุ |
|---|---|
| `app/Http/Controllers/Api/Learn/Academy/ClassScheduleController.php` | 8 เมธอด: `index · timetable · store · update · destroy · bulkStore · today · checkAvailability` |
| `routes/learn/academy.php:639-648` | กลุ่ม `{academy}/schedules` middleware `['academy.visibility:content', 'academy.permission']` · เขียนต่อ `academy.permission:schedule.manage` เฉพาะเส้นเขียน |
| `app/Models/ClassSchedule.php` | scope + `hasTeacherConflict()` / `hasClassroomConflict()` |
| `app/Models/SchedulePeriod.php` | **โมเดลลอย — ไม่มี controller ไม่มี route ไม่มีข้อมูล** |
| `app/Models/TeacherAssignment.php` | ใช้เฉพาะใน `RosterReconciliationService` · ตารางว่าง 0 แถว |
| `app/Http/Middleware/CheckAcademyPermission.php` / `EnsureAcademyVisibility.php` | ทั้งคู่ resolve ด้วย `Academy::find($param)` = **คีย์หลักเท่านั้น** |

### 2.3 Database (ยืนยันจาก DB dev จริง)

| ตาราง | แถว | หมายเหตุ |
|---|---|---|
| `class_schedules` | **5** | seed เดโม 2026-02-05 · ห้องเดียว (classroom_id=6) · semester_id=1 · **ปีการศึกษา 2568 (ปีที่แล้ว)** |
| `schedule_periods` | **0** | โครงคาบเรียนของโรงเรียน — ยังไม่เคยถูกใช้ |
| `teacher_assignments` | **0** | การมอบหมายครูประจำวิชา — ยังไม่เคยถูกใช้ |
| `semesters` | **1** | `ภาคเรียนที่ 1` ผูก `academic_year_id=1` (2568) และ `is_current=1` |
| `academic_years` | 2 | 2568 (id 1, `is_current=0`) · **2569 (id 2, `is_current=1`)** |
| `subjects` | 5 | เดโมล้วน (MATH101/THAI101/SCI101/ENG101/SOC101) |
| `classrooms` | 104 | 2568 = 51 ห้อง · 2569 = 53 ห้อง |
| `academy_members` | 3,063 | student 2,481 · teacher 120 · staff 12 · ไม่มีบทบาท 450 |
| `courses` (academy 1) | 22 | ← "วิชาจริง" ของโรงเรียนอยู่ฝั่งนี้ ไม่ใช่ `subjects` |

🔴 **ภาคเรียนปัจจุบันชี้ผิดปี:** ปีการศึกษาปัจจุบันคือ 2569 แต่ `semesters` มีแถวเดียวที่ผูกกับ 2568
`Semester::current()` (= `where('is_current', true)` เฉย ๆ ไม่ผูกโรงเรียน) จึงคืนภาคเรียนของปีที่แล้ว
และ **ไม่มี GET route สำหรับ list ภาคเรียน** (มีแต่ POST/PUT) → UI สร้าง dropdown เลือกภาคเรียนไม่ได้เลย

### 2.4 Route inventory (8 เส้นของเมนูนี้)

```
GET    /api/academies/{academy}/schedules                     index          academy.permission (สมาชิกเปล่า)
GET    /api/academies/{academy}/schedules/timetable           timetable      academy.permission (สมาชิกเปล่า)
GET    /api/academies/{academy}/schedules/today               today          academy.permission (สมาชิกเปล่า)
GET    /api/academies/{academy}/schedules/check-availability  checkAvail.    academy.permission (สมาชิกเปล่า)
POST   /api/academies/{academy}/schedules                     store          + schedule.manage
POST   /api/academies/{academy}/schedules/bulk                bulkStore      + schedule.manage
PATCH  /api/academies/{academy}/schedules/{id}                update         + schedule.manage
DELETE /api/academies/{academy}/schedules/{id}                destroy        + schedule.manage
```

**ไม่มี PUT** · ไม่มี endpoint ของ `schedule_periods` · ไม่มี endpoint "ตารางของฉัน" · ไม่มี export

### 2.5 ผลยิงจริง (curl + JWT ของเจ้าของโรงเรียน user 1 ซึ่งเป็น superadmin คนเดียวของระบบ)

```
GET  /api/academies/<ชื่อโรงเรียน>/schedules            -> 404  {"message":"Academy not found"}
GET  /api/academies/<ชื่อโรงเรียน>/schedules/timetable  -> 404  {"message":"Academy not found"}   (ครู 17004 ก็ 404)
GET  /api/academies/1/schedules                        -> 404  (ModelNotFound จาก firstOrFail ในคอนโทรลเลอร์)
GET  /api/academies/1/schedules/today                  -> 404
PUT  /api/academies/<ชื่อโรงเรียน>/schedules/1          -> 405  (route มีแต่ PATCH)
GET  /api/academies/1/curriculums/subjects             -> 404  (ไม่มี route นี้จริง)
-- เทียบกับเส้นที่ยังดีอยู่ --
GET  /api/academies/1/classrooms                       -> 200
GET  /api/academies/1/members                          -> 200
GET  /api/academies/1/subjects                         -> 200  <- path จริงของวิชา
```

### 2.6 🔴 กับดักของ DB จริงที่เจอตอนตรวจ SC-S1 (ต้องอ่านก่อนเขียน migration ของ SC-S2)

- **DB dev ไม่มี foreign key เลยแม้แต่ตัวเดียว** — ตรวจ `information_schema.KEY_COLUMN_USAGE` แล้วได้ 0
  ทั้ง `class_schedules`, `classrooms`, `course_members`, `semesters` (engine เป็น InnoDB ปกติ)
  ทั้งที่ migration เขียน `->constrained()` ไว้ครบ ⇒ migration ของ SC-S2 **ห้ามสมมติว่า `dropForeign('...')` จะมีของให้ drop**
  (ต้องเช็คก่อน หรือ wrap ไว้ ไม่งั้น migrate ล้มกลางคัน)
- **แถวเดโม 5 แถวเป็นแถวกำพร้า** — ทั้ง 5 แถวชี้ `classroom_id=6` ซึ่ง**ไม่มีอยู่แล้ว**
  (ห้องเรียนในระบบตอนนี้ id 7–111) ⇒ ถ้า SC-S2 จะเพิ่ม FK จริง ต้องล้าง/ย้ายแถวพวกนี้ก่อน
  และอย่าใช้ `classroom_id=6` เป็นค่าทดสอบอีก (ตอนยิงทดสอบ SC-S1 ครั้งแรกเจอ 422 เพราะเหตุนี้ ซึ่งเป็นพฤติกรรมที่ถูกแล้ว)

---

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | ดูตารางรายห้องเรียน (กริดสัปดาห์) | ❌ | มี UI แต่ API 404 + ไม่ส่ง filter → กริดว่างเสมอ |
| 2 | ดูตารางรายครู | ❌ | เหมือนข้อ 1 |
| 3 | เพิ่ม/แก้/ลบคาบ | ❌ | 404 · 405 · ไม่มี `semester_id` · dropdown วิชาว่าง |
| 4 | เลือกปีการศึกษา/ภาคเรียน | ❌ | ไม่มี selector และไม่มี API list ภาคเรียน |
| 5 | กันครูชนเวลา | ⚠️ | มีตรรกะ แต่ขอบเขตเวลาผิด → คาบติดกันถือว่าชน (G6) |
| 6 | กันห้องเรียนชนเวลา | ⚠️ | เหมือนข้อ 5 |
| 7 | กันสถานที่ (`room`) ชนกัน | ❌ | ไม่มีการตรวจเลย · `room` เป็น free text ไม่มีทะเบียนห้อง |
| 8 | โครงคาบเรียนของโรงเรียน (คาบ 1..n + พัก + กิจกรรม) | ❌ | ตาราง `schedule_periods` มีแต่ไม่มีทั้ง API/UI/ข้อมูล · UI ฮาร์ดโค้ด 08:00–16:00 รายชั่วโมง |
| 9 | ตารางสอนของฉัน (ครู) | ❌ | มีแค่การ์ด "วันนี้" ในแดชบอร์ดครู ซึ่ง 404 |
| 10 | ตารางเรียนของฉัน (นักเรียน/ผู้ปกครอง) | ❌ | สิทธิ์ `schedule.view.own` มีในระบบ แต่ไม่มี endpoint/หน้า |
| 11 | ภาระงานสอนของครู (คาบ/สัปดาห์) | ❌ | `teacher_assignments` ว่าง ไม่มี UI |
| 12 | สร้างหลายคาบพร้อมกัน (bulk) | ⚠️ | API `bulkStore` มี แต่ไม่มี UI เรียก |
| 13 | คัดลอกตารางข้ามภาคเรียน/ข้ามห้อง | ❌ | ไม่มี |
| 14 | พิมพ์/ส่งออกตาราง (PDF/Excel) รายห้อง-รายครู | ❌ | ไม่มี (โรงเรียนต้องใช้จริง) |
| 15 | คาบสอนแทน / งดคาบ | ⚠️ | enum `status` มี `cancelled/temporary` แต่ไม่มี UI และไม่มีความหมายเชิงธุรกิจ |
| 16 | วันเสาร์–อาทิตย์ | ⚠️ | API รับ 1–7 แต่ UI มีแค่ จ–ศ → แถวเสาร์/อาทิตย์มองไม่เห็นและลบไม่ได้ |
| 17 | เชื่อมกับการเช็คชื่อรายคาบ (#18) | ❌ | ยังไม่มีการอ้างอิงกัน |
| 18 | Audit log | ✅ | `store/update/destroy` เรียก `AuditLogService` ครบ |

---

## 4. Permission Matrix

คีย์ที่มีอยู่ใน `AcademyPermission`: `schedule.view` · `schedule.view.own` · `schedule.view.all` · `schedule.manage`

**สิ่งที่บังคับใช้จริงวันนี้**

| เส้นทาง | ด่านจริง | ผลที่ตามมา |
|---|---|---|
| GET ทุกเส้น | `academy.permission` (ไม่ส่งคีย์) = แค่ "เป็นสมาชิกที่อนุมัติแล้ว" | นักเรียน/ผู้ปกครองอ่านตารางของครูทุกคนและทุกห้องได้ · `schedule.view` ไม่ถูกใช้ที่ไหนเลย |
| POST/PATCH/DELETE | `academy.permission:schedule.manage` | ผ่านได้เฉพาะ owner/admin (ผ่าน `Academy::isAdmin()`) และ superadmin |

**ตารางสิทธิ์ที่ควรเป็น** (ต้องให้เจ้าของโปรเจคเคาะ — Q4)

| Permission key | Owner | Admin | ฝ่ายวิชาการ (admin ฝ่าย) | นายทะเบียน | Teacher | Staff | Student | Guardian |
|---|---|---|---|---|---|---|---|---|
| `schedule.view` (ทั้งโรงเรียน) | ✅ | ✅ | ✅ | ✅ | ⚠️ Q4 | ⚠️ Q4 | ❌ | ❌ |
| `schedule.view.own` | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ (ห้องตัวเอง) | ✅ (ห้องบุตรหลาน) |
| `schedule.manage` | ✅ | ✅ | ✅ (ในฝ่าย) | ⚠️ Q4 | ❌ | ❌ | ❌ | ❌ |

🔴 **ช่องว่างที่ต้องรู้:** `schedule.manage` **ไม่ได้อยู่ในบทบาทตั้งต้นใดเลย** — แม้แต่ owner/admin
(`AcademyRole::DEFAULT_ROLES` ให้แค่ `schedule.view`) วันนี้จึงจัดตารางได้เฉพาะคนที่ผ่าน `Academy::isAdmin()`
ถ้าจะให้ฝ่ายวิชาการ/นายทะเบียนจัดตารางได้ ต้องเติมคีย์เข้าบทบาทจริง ไม่ใช่แค่ผูก route
(หมายเหตุ: "ฝ่าย" ในระบบนี้คือ `academy_groups` ที่ `type='department'` สิทธิ์ไหลผ่าน `AcademyGroupPermissionAccessService`)

---

## 5. Gap Analysis

### P0 — ทำให้เมนูใช้งานไม่ได้เลย (ต้องปิดก่อนทำอย่างอื่น)

**G1 · ตัวชี้โรงเรียนไม่ตรงกันทั้งเส้น → 404 ทุกทาง**
`EnsureAcademyVisibility` + `CheckAcademyPermission` ใช้ `Academy::find($param)` (คีย์หลัก) แต่
`ClassScheduleController` ทุกเมธอดใช้ `Academy::where('name', $academyName)->firstOrFail()`
⇒ ส่ง **ชื่อ** ตายที่ด่าน (404 ก่อนถึงคอนโทรลเลอร์ — และ `EnsureAcademyVisibility` ไม่มีทางลัด superadmin ด้วย) ·
ส่ง **id** ผ่านด่านแต่ตายที่คอนโทรลเลอร์ (404)
ผู้เรียกทั้ง 3 ฝั่งแตกคนละแบบ: หน้า admin ส่งชื่อ · แดชบอร์ดครูส่ง id · `useSchoolManagement` ส่ง id
พิสูจน์แล้วใน §2.5

**G2 · frontend ไม่ได้ส่ง query param เลยสักตัว**
`useApi.get(endpoint, options)` — อาร์กิวเมนต์ที่สองคือ *options ของ `$fetch`* ไม่ใช่ params
หน้าเรียก `api.get(url, { classroom_id, per_page, role, status })` → คีย์พวกนี้ถูกโยนเข้า `$fetch` แล้วถูกทิ้ง
(ต้องเป็น `{ params: {...} }` หรือ `{ query: {...} }`)
⇒ `timetable` ไม่มี `classroom_id`/`teacher_id` → validation `required_without` 422 → กริดว่างตลอด
⇒ `classrooms` ได้แค่ 20 จาก 104 · `members` ได้ 20 แถวแรกของสมาชิกทุกบทบาท

**G3 · ไม่ได้ส่ง `semester_id` ตอนสร้าง** — `store` validate `required|exists:semesters,id` แต่ฟอร์มไม่มีฟิลด์นี้ → 422 แน่นอน

**G4 · frontend ใช้ `api.put` แต่ route มีแค่ PATCH** → 405 (ยิงจริงยืนยันแล้ว)

**G5 · endpoint วิชาผิด path** — หน้าเรียก `/academies/{id}/curriculums/subjects` (ไม่มีจริง, 404)
ของจริงคือ `/academies/{id}/subjects` ⇒ dropdown "วิชา" ว่างเสมอ → กดบันทึกไม่ได้เพราะฟิลด์ `required`

### P1 — ถึงต่อสายติดแล้วก็ยังใช้งานจริงไม่ได้

**G6 · ตรรกะกันเวลาชนผิดที่ขอบคาบ**
`hasTeacherConflict` / `hasClassroomConflict` ใช้ `whereBetween` ซึ่ง **นับปลายช่วงด้วย**
⇒ คาบ 08:00–09:00 กับ 09:00–10:00 (ห้องเดียวกัน/ครูคนเดียวกัน) ถูกตัดสินว่า "ซ้ำซ้อน"
⇒ **จัดคาบติดกันไม่ได้เลย** ซึ่งคือ 100% ของตารางเรียนจริง
สูตรที่ถูกคือ `start_time < :end AND end_time > :start`

**G7 · ภาคเรียนไม่ผูกโรงเรียนและชี้ปีที่แล้ว**
`Semester::current()` = `where('is_current', true)` ล้วน ๆ (ตาราง `semesters` ไม่มี `academy_id` ผูกผ่าน `academic_years`)
⇒ ระบบหลายโรงเรียนจะหยิบภาคเรียนของโรงเรียนอื่นมาเป็นตัวกรองเงียบ ๆ
⇒ วันนี้ยังชี้ภาคเรียนของ **ปี 2568** ทั้งที่ปีปัจจุบันคือ 2569 และไม่มี GET route ให้ list ภาคเรียน

**G8 · dropdown "ครูผู้สอน" ไม่ได้กรองครู**
`AcademyMemberController::getAcademyMembers(Academy $academy)` รับแค่ `per_page` — **ไม่อ่าน `role`/`status` เลย**
⇒ ช่องเลือกครูคือสมาชิก 20 คนแรกของโรงเรียน (ซึ่งมีนักเรียน 2,481 คนปนอยู่)

**G9 · dropdown "ห้องเรียน" ไม่กรองปีการศึกษา** → ปน 51 ห้องของ 2568 กับ 53 ห้องของ 2569

**G10 · ด่านสิทธิ์อ่านหลวมและคีย์ที่มีไม่ถูกใช้** — ดู §4 (อ่านได้ทุกคนที่เป็นสมาชิก · `schedule.manage` ไม่อยู่ในบทบาทใด)

**G11 · หน้า admin กันด้วยสิทธิ์ผิดตัว** — `!isAdmin && !can('academy.view')` ⇒ นักเรียนเปิดหน้าจัดตารางได้ เห็นปุ่มเพิ่ม/ลบครบ (ไปพังตอนยิง API)

**G12 · ไม่ตรวจว่า classroom/subject/teacher เป็นของโรงเรียนนี้จริง**
validate แค่ `exists:classrooms,id` / `exists:subjects,id` / `exists:users,id`
⇒ ยัด id ข้ามโรงเรียนได้ แล้วแถวจะถูกเขียนด้วย `academy_id` ของ URL (ข้อมูลปนข้ามโรงเรียน)

**G13 · `update` แก้ห้องเรียนไม่ได้จริง** — ใช้ `$request->classroom_id` ตอนตรวจชน แต่ `$schedule->update($request->only([...]))`
ไม่มี `classroom_id` ในลิสต์ ⇒ ฟอร์มส่งมาก็ถูกทิ้งเงียบ ๆ (และ `classroom_id` ไม่อยู่ใน validator ด้วย)

### P2 — ฟีเจอร์/UX ที่ขาด

**G14 · กริดเวลาเป็นค่าฮาร์ดโค้ด** 08:00–16:00 รายชั่วโมง และ `getScheduleAt` จับคู่ด้วย *ชั่วโมง* อย่างเดียว
⇒ คาบ 08:30 ไปโผล่แถว 08:00 · สองคาบในชั่วโมงเดียวกันเห็นแค่คาบแรก · อะไรที่อยู่นอก 08–16 หายไปเลย
(ข้อมูลเดโมที่มีอยู่คือ 08:30 / 09:30 / 10:45 — ชนกับกริดนี้ทุกแถว)
ทางแก้ที่ระบบเตรียมไว้แล้วคือ `schedule_periods` ซึ่งยังเป็นตารางเปล่า

**G15 · UI มีแค่ จ–ศ** ขณะที่ API รับ 1–7 → ถ้ามีคาบวันเสาร์ จะมองไม่เห็นและลบไม่ได้

**G16 · มือถือ** (ผิดกติกา mobile-first ของโปรเจค)
- ปุ่มลบคาบซ่อนอยู่ใต้ `opacity-0 group-hover:opacity-100` → **บนจอสัมผัสไม่มี hover → ลบคาบไม่ได้เลย**
- modal ไม่มี `max-h`/`overflow-y-auto` → ฟอร์ม 7 ฟิลด์บนจอ 375×667 ดันปุ่ม "บันทึก" ตกขอบจอ
- กริด `min-w-[800px]` อยู่ใน `overflow-x-auto` ของตัวเอง (ข้อนี้ถูกกติกาแล้ว)

**G17 · ฟีเจอร์ที่โรงเรียนใช้จริงแต่ยังไม่มี** — พิมพ์/ส่งออกตารางรายห้อง-รายครู · คัดลอกตารางข้ามภาคเรียน ·
UI bulk (API มีแล้ว) · สอนแทน/งดคาบ · ภาระงานสอน · ทะเบียนสถานที่ + กันห้องชน

**G20 · (เจอตอนรีวิว SC-S2) `PATCH` เคลียร์คอร์สทิ้งโดยไม่มีชื่อแทนได้**
`store()` บังคับ `title` เมื่อไม่มี `course_id` แล้ว แต่ `update()` ยังยอมให้ส่ง `{"course_id": null}` เดี่ยว ๆ
กับคาบที่ไม่มี `title` ⇒ ได้คาบไร้ชื่อ (กริดจะขึ้นช่องว่าง)
ปิดที่ SC-S5 (รอบแก้ validation/ตรรกะ) หรือให้ SC-S4 บังคับส่ง `title` คู่มาเสมอ — ความเสี่ยงต่ำ ยังไม่มี UI ที่ทำแบบนี้ได้

**G18 · ไม่มีเทสต์ที่รันได้**
เทสต์เดียวที่แตะเมนูนี้คือ `tests/Api/SchoolManagementApiTest.php::test_can_list_class_schedules`
ซึ่ง **รันไม่ผ่านตั้งแต่ setUp** (sqlite `:memory:` ไม่มี schema — `no such table: users`) ทั้งไฟล์จึงเป็นเทสต์ตายมานาน
และมันยัง assert 200 บน URL แบบ id ซึ่งปัจจุบันคือ 404

---

## 6. การตัดสินใจที่เจ้าของโปรเจคเคาะแล้ว (2026-09-16) — 🔴 ห้ามเปลี่ยนเองในรอบหน้า

### D1 · ชี้โรงเรียนด้วย `id` (ปิด Q1)
ทุก endpoint ของ `/schedules` ใช้ **academy id** เหมือนเมนูอื่นทั้งระบบ
⇒ แก้ `ClassScheduleController` ให้ resolve ด้วยคีย์หลัก และแก้หน้า admin ให้ส่ง `academyId` แทน `academyName`
⇒ ผลพลอยได้: แดชบอร์ดครูกับแท็บตารางเรียนในเมนู #8 (ซึ่งส่ง id อยู่แล้ว) กลับมาทำงานทันที

### D2 · โครงคาบเรียนต้อง "ตั้งค่าได้เอง" ไม่ใช่ค่าคงที่ (ปิด Q2)
แต่ละโรงเรียนต้องกำหนดโครงตารางของตัวเองได้ ⇒ กริดต้องวาดจากข้อมูล ไม่ใช่จากค่าฮาร์ดโค้ดในหน้า
สิ่งที่ต้องตั้งค่าได้อย่างน้อย:

| ตั้งค่าอะไร | ทำไม |
|---|---|
| วันที่โรงเรียนเปิดสอน (เลือกได้ 1–7) | บางโรงเรียนสอน จ–ศ บางแห่งมีวันเสาร์/วันสอนศาสนา |
| ชุดโครงคาบ (period set) — คาบ 1..n ชื่อคาบ เวลาเริ่ม/จบ | จำนวนคาบและเวลาต่างกันทุกโรงเรียน |
| ประเภทคาบ: เรียน / พัก / กลางวัน / กิจกรรม | แถวพักกลางวัน-หน้าเสาธง-ชุมนุม ต้องอยู่ในกริดด้วย |
| ใช้ชุดไหนกับวันไหน / ระดับชั้นไหน | ประถมกับมัธยมคนละโครง · วันศุกร์เลิกเร็ว · วันพุธมีชุมนุม |
| คาบที่ไม่อิงชุด (กำหนดเวลาเอง) | กิจกรรมพิเศษที่ไม่ตรงคาบปกติ |

ตาราง `schedule_periods` ที่มีอยู่รองรับได้แค่ "ชุดเดียวต่อโรงเรียน" (`unique(academy_id, period_number)`)
⇒ ต้องขยายเป็น **ชุดโครงคาบหลายชุด** (`schedule_period_sets` + `schedule_periods.set_id` + เงื่อนไขว่าชุดไหนใช้วันไหน/ระดับชั้นไหน) ผ่าน migration
⇒ แถวตารางเรียนเก็บ `period_id` (nullable) **คู่กับ** `start_time`/`end_time` เสมอ เพื่อให้คาบนอกชุดยังจัดได้

### D3 · สร้างภาคเรียนของปี 2569 เลย (ปิด Q3)
สร้างผ่าน **migration** ตามกติกา DB ของโปรเจค: `1/2569` และ `2/2569` ผูก `academic_year_id=2`
ยึดกรอบวันของปี 2569 ที่มีอยู่ (2026-05-16 → 2027-03-31) และย้าย `is_current` มาที่ `1/2569`
(วันนี้ 2026-09-16 อยู่ในภาคเรียนที่ 1) · `down()` ต้องคืน `is_current` กลับให้ภาคเรียนเดิม
**แถวเดโม 5 แถวของปี 2568 — ยังไม่ลบ** (เจ้าของโปรเจคยังไม่ได้สั่งในข้อนี้ และมันอยู่คนละภาคเรียนจึงไม่กวนของจริง)
ถ้าจะล้าง ให้ลบจากหน้า UI หลัง SC-S2 เสร็จ จะปลอดภัยกว่าลบด้วย migration

### D4 · ฝ่ายวิชาการเป็นคนจัดตาราง · การอ่านเปิดให้ทุกคนในโรงเรียน (ปิด Q4)
- `schedule.manage` → owner/admin + **admin ของฝ่ายวิชาการ** (ฝ่าย = `academy_groups` type `department`)
  ต้อง **เติมคีย์เข้าบทบาทตั้งต้นจริง** ด้วย เพราะวันนี้ไม่มีบทบาทใดถือคีย์นี้เลย (G10)
- การอ่าน: **ครูดูตารางครูคนอื่นได้ · นักเรียนดูตารางห้องอื่นได้** — คงพฤติกรรมเปิดกว้างไว้ตามที่เคาะ
  แต่ต้องเปลี่ยนด่านจาก "สมาชิกเปล่า" เป็น `schedule.view` แล้ว**เติม `schedule.view` ให้บทบาท student/parent/teacher/staff**
  (คีย์จะได้มีความหมาย และโรงเรียนที่อยากปิดค่อยถอดคีย์ออกจากบทบาทได้เอง — ไม่ต้องแก้โค้ด)
- `schedule.view.own` ยังคงไว้สำหรับหน้า "ตารางของฉัน" (ทางลัดของครู/นักเรียน ไม่ใช่กำแพง)

### D5 · "วิชา" ในตารางเรียน = `courses` ไม่ใช่ `subjects` (ปิด Q5 — ยืนยันแล้วจากข้อมูลจริง)

**ใช่ครับ ต้องเป็น `courses`** — ตรวจฐานข้อมูลจริงแล้วยืนยันสิ่งที่เจ้าของโปรเจคเข้าใจ:

| หลักฐาน | ผล |
|---|---|
| ตารางที่มีคอลัมน์ `subject_id` ทั้งระบบ | `class_schedules`(5 เดโม) · `interactions`(0) · `risk_events`(0) · `semester_transcript_items`(0) · `teacher_assignments`(0) → **ว่างหมด ไม่มีใครใช้จริงเลย** |
| ตารางที่แขวนกับ `course_id` | `attendance_details` 23,619 · `course_quiz_results` 10,426 · `course_members` 4,215 · `course_group_members` 4,170 · `course_posts` 1,353 · `course_attendances` 935 · `lessons` 54 … |
| หน้าตาข้อมูล `courses` ของโรงเรียน | เป็นรายวิชาจริงของไทยครบ: รหัสวิชา `ง 20201` · ระดับชั้น ม.1–ม.5 · ภาคเรียน/ปีการศึกษา · หน่วยกิต · คาบ/สัปดาห์ |
| การผูกกับห้องเรียน | `courses` → `course_groups` (ชื่อกลุ่มคือ "ม.5/1") → `course_group_classrooms` → `classrooms` มีอยู่แล้ว |

⇒ **schema เปลี่ยนเป็น:** `class_schedules.subject_id` → `course_id` (migration ที่ drop FK เดิมแล้วผูกใหม่)
ทำได้ปลอดภัยเพราะมีข้อมูลจริงแค่ 5 แถวเดโม

🔴 **ข้อควรระวัง 3 ข้อที่ต้องออกแบบเผื่อ (มาจากการตรวจข้อมูลจริง):**

1. **course ≠ ห้องเรียน** — 1 course = 1 รายวิชาของ 1 ระดับชั้นต่อ 1 ภาคเรียน (เช่น "โปรแกรมประมวลผลคำ (ม.1 ภาคเรียนที่ 1/2569)")
   ใช้ร่วมกันหลายห้อง ⇒ แถวตารางเรียน **ยังต้องเก็บ `classroom_id` และ `teacher_id` แยกต่างหาก**
   (`courses.instructor_id` คือครูเจ้าของคอร์ส ซึ่งอาจไม่ใช่คนสอนห้องนั้น)
2. **คาบที่ไม่มีคอร์สมีอยู่จริงเสมอ** — ชุมนุม ลูกเสือ แนะแนว หน้าเสาธง พักกลางวัน ไม่มีทางมี LMS course
   ⇒ `course_id` ต้อง **nullable** + มีฟิลด์ `title` (ชื่อที่แสดงในคาบ) และ `entry_type` (course / activity / break / exam)
3. **คอร์สของจริงยังมีไม่ครบ** — โรงเรียนมี 53 ห้องในปี 2569 แต่มี course แค่ 22 รายการ ซึ่งเป็นวิชาคอมพิวเตอร์ของครูคนเดียวทั้งหมด
   วิชาไทย/คณิต/วิทย์/อังกฤษ/สังคม **ยังไม่มีในระบบเลย** (นั่นคืองานของเมนู #12)
   ⇒ ถ้าบังคับ `course_id` ตารางเรียนจะสร้างไม่ได้จนกว่าเมนู #12 จะเสร็จ
   ⇒ ข้อ 2 (course_id nullable + title) จึงไม่ใช่แค่เรื่องกิจกรรม แต่คือ**ทางที่ทำให้ฝ่ายวิชาการจัดตารางได้ทันทีวันนี้**
   แล้วค่อยผูกคอร์สเข้าไปทีหลังเมื่อเมนู #12 สร้างคอร์สครบ (ผูกแล้วจะปลดล็อกเช็คชื่อรายคาบ/คะแนนต่อ)

**endpoint ที่ใช้เลือกคอร์ส:** `GET /api/academies/{academy}/courses` (มีอยู่แล้ว) — กรองตามภาคเรียน/ปีการศึกษา
และเลือกเฉพาะคอร์สที่ยัง active (ดู `finalization_status` เป็นตัวหลัก ไม่ใช่ `status` เพียงอย่างเดียว)

---

## 7. Implementation Tasks

ลำดับนี้จัดใหม่หลังเคาะ D1–D5 แล้ว — **ไม่มี step ไหนติดคำถามค้างอีก เริ่ม SC-S1 ได้ทันที**
หลักการเรียง: ปลดล็อกเส้นทาง → วาง schema ให้ถูกก่อน → ค่อยเดินสาย frontend ทีเดียว (จะได้ไม่ต้องเดินสองรอบ)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| **SC-S1** | **ปลดล็อก 404 (backend)** — resolve โรงเรียนด้วย id ทุกเมธอด (D1/G1), ตรวจว่า classroom/teacher/course เป็นของโรงเรียนนี้ (G12), ใส่ `classroom_id` เข้า validator + `only()` ของ `update` (G13) | — | patch controller + เทสต์ 404/403/200 | 🟢 **done 2026-09-16** (agy เขียน · Claude ตรวจเอง ดู §9) |
| **SC-S2** | **สลับ schema เป็น courses (D5)** — migration `subject_id` → `course_id` (nullable) + เพิ่ม `title`, `entry_type` (course/activity/break/exam) + `period_id` (nullable) · อัปเดต model/validation/response · `down()` คืนสภาพได้จริง | SC-S1 | migration + `ClassSchedule` + controller | 🟢 **done 2026-09-16** (รัน migrate + rollback + migrate ซ้ำบน DB dev แล้ว ดู §9) |
| **SC-S3** | **ภาคเรียน 2569 (D3)** — migration สร้าง 1/2569 + 2/2569 และย้าย `is_current` · ผูก `Semester::current()` เข้ากับโรงเรียน (G7) · เพิ่ม `GET .../academic-years/{id}/semesters` สำหรับ selector | SC-S1 | migration + endpoint + เทสต์ | ⚪ |
| **SC-S4** | **เดินสาย frontend ทีเดียวจบ** — ส่ง query ผ่าน `{ params }` (G2), PUT→PATCH (G4), ส่ง `semester_id` (G3), เปลี่ยน dropdown วิชา → ตัวเลือก **คอร์ส** จาก `GET /academies/{id}/courses` + ช่องกรอกชื่อเองเมื่อไม่มีคอร์ส (G5/D5), ใช้ `academyId` (D1), กันหน้าด้วย `schedule.view/manage` (G11), selector ปี/ภาคเรียน · **ต้องส่ง `title` มาด้วยเสมอเมื่อเคลียร์ `course_id`** (ดู G20) | SC-S2 · SC-S3 | หน้า admin ดู/สร้าง/แก้/ลบได้จริงครบวง | ⚪ |
| **SC-S5** | **แก้ตรรกะกันชน** — ขอบเวลาให้คาบติดกันได้ (G6) + กันสถานที่ (`room`) ชน + เทสต์เคสคาบติดกัน/คร่อม/แก้คาบเดิม/ข้ามภาคเรียน | SC-S2 | `ClassSchedule` + เทสต์บน MySQL จริง | ⚪ |
| **SC-S6** | **ชุดโครงคาบที่ตั้งค่าเองได้ (D2)** — migration `schedule_period_sets` + `schedule_periods.set_id` + เงื่อนไขใช้กับวัน/ระดับชั้น · CRUD + หน้าตั้งค่า · กริดวาดจากคาบแทนชั่วโมงฮาร์ดโค้ด (G14) · เลือกวันเปิดสอน 1–7 (G15) | SC-S4 | migration + controller + routes + หน้าตั้งค่า + กริดใหม่ | ⚪ |
| **SC-S7** | **สิทธิ์ (D4)** — เปลี่ยนด่าน GET เป็น `schedule.view` · migration เติม `schedule.view` ให้ teacher/staff/student/parent และ `schedule.manage` ให้ owner/admin + ฝ่ายวิชาการ (G10) · endpoint + หน้า "ตารางสอนของฉัน / ตารางเรียนของฉัน" | SC-S4 | routes + role migration + 2 หน้า + เทสต์สิทธิ์ | ⚪ |
| **SC-S8** | **UX/มือถือ (G16)** — ปุ่มลบที่แตะได้จริงบนจอสัมผัส, modal เลื่อนได้, ช่องเลือกครู/ห้องแบบค้นหาได้ + กรองครูจริง (G8 ต้องแก้ `getAcademyMembers` ให้รับ `role`/`status`) + กรองห้องตามปีการศึกษา (G9) | SC-S4 | ตรวจจริงที่ 375/768/1280 | ⚪ |
| **SC-S9** | **ซ่อมผู้เรียกอื่น** — แดชบอร์ดครู `/schedules/today` + แท็บตารางเรียนในเมนู #8 (`useSchoolManagement`) ให้เข้ากับ response ใหม่ | SC-S2 | 2 จุดกลับมา 200 พร้อมข้อมูลจริง | ⚪ |
| **SC-S10** | **ฟีเจอร์ที่โรงเรียนใช้จริง (G17)** — พิมพ์/ส่งออกตารางรายห้อง-รายครู, UI bulk, คัดลอกตารางข้ามภาคเรียน, สอนแทน/งดคาบ, ภาระงานสอนของครู | SC-S6 · SC-S7 | แตกย่อยอีกทีตอนถึงคิว | ⚪ |
| **SC-S11** | **เทสต์ (G18)** — ไฟล์เทสต์ของเมนูนี้ที่รันได้จริงบน MySQL + ตัดสินใจเรื่อง `tests/Api/SchoolManagementApiTest.php` ที่ตายอยู่ | SC-S5 · SC-S7 | เทสต์เขียว | ⚪ |

**Rule:** ทุก step ต้องมี verification (build/test/ยิง API จริง/ตรวจบนจอ 375px) ก่อนขึ้นสถานะ 🟢

**หนี้ข้ามเมนูที่ต้องจำ:** ตารางจะ "มีของให้จัด" จริง ๆ ก็ต่อเมื่อเมนู **#12 คอร์สเรียน** สร้างคอร์สครบทุกวิชา×ระดับชั้น
ระหว่างนี้ SC-S2 เปิดทางให้กรอก `title` เองได้ก่อน (D5 ข้อ 3) — เมื่อ #12 เสร็จค่อยไล่ผูก `course_id` ย้อนหลัง

---

## 8. Codex/agy Prompt Template (ต่อ step)

```
Context: .agents/school-admin/11-schedule.md §<step-id>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): <รายการ>
Task: <what to do>
Constraints:
  - ห้ามแตะไฟล์นอกรายการข้างบน
  - งานใน ui/ ต้อง mobile-first (ไม่มี prefix = มือถือ · touch target 44px ·
    ห้าม hidden ข้อมูลสำคัญ · ตารางกว้างอยู่ใน overflow-x-auto ของตัวเอง · ตรวจที่ 375px ก่อน)
  - ทุกการเปลี่ยน DB ต้องเป็น migration ที่มี down() จริง ห้าม hardcode id
Verification: <build/test/curl ที่ต้องรันและผลที่คาดหวัง>
Report back: diff stat + ผลรันคำสั่ง verification
```

---

## 9. Review Log

- **2026-09-16 audit** — Claude สแกนโค้ดจริงทั้ง FE/BE/routes/models/DB + ยิง API จริงด้วย JWT ของ owner และของครู
  → พบว่าเมนูตายทั้งเมนู (G1–G5 เป็น P0) · เขียนไฟล์นี้ + แตก step · ยังไม่ส่ง step ใดให้ codex/agy
- **2026-09-16 decisions** — เจ้าของโปรเจคเคาะ Q1–Q5 ครบ → บันทึกเป็น **D1–D5** ใน §6
  Q5 เป็นคำถามกลับมาที่ Claude ("ต้องเป็น courses ใช่ไหม") → ตรวจ DB จริงแล้ว **ยืนยันว่าใช่**:
  `subject_id` โผล่ใน 5 ตารางและ**ว่างหมดทุกตาราง** ขณะที่งานจริงทั้งระบบ (เช็คชื่อ 23,619 · ควิซ 10,426 · สมาชิกคอร์ส 4,215)
  แขวนกับ `course_id` ทั้งหมด และ `courses` ถือรหัสวิชา/ระดับชั้น/หน่วยกิต/คาบต่อสัปดาห์ของจริงอยู่แล้ว
  → จัดลำดับ step ใหม่เป็น SC-S1..S11 (เอาการสลับ schema ขึ้นมาก่อนเดินสาย frontend จะได้ทำทีเดียว)
  → **ไม่มีคำถามค้างแล้ว เริ่ม SC-S1 ได้ทันที**

- **2026-09-16 SC-S1 🟢 verified** — agy เขียน (สเปค `agy-sc-s1-schedule-backend.txt`) · Claude ตรวจเองทุกข้อ
  **diff จริง:** `ClassScheduleController.php` +121/−23 (deletion ทั้ง 23 บรรทัดคือบรรทัดที่ถูกแทนที่ ไม่มีของเดิมหาย) ·
  `routes/learn/academy.php` แก้บรรทัดเดียวจริงตามสเปค (เติม `->whereNumber('academy')`) ·
  `tests/Feature/ClassScheduleGuardTest.php` ไฟล์ใหม่ 8 เคส
  **เกณฑ์ที่ Claude รันเอง:** `grep -c "where('name'"` = 0 (และ `academyName` เหลือ 0) · `pint --test` ผ่าน ·
  `ClassScheduleGuardTest` 8/8 (14 assertions) · `ClassroomManagementTest` 19/19 (50 assertions) ไม่พัง ·
  `route:list` ยังเป็น 8 เส้นและ update ยังเป็น PATCH (ไม่มี PUT โผล่)
  **ยิงเซิร์ฟเวอร์จริง (php artisan serve + JWT):** `/schedules` 200 · `/schedules/today` 200 ·
  `/timetable?classroom_id=7` 200 (เดิม 404 ทุกเส้น) · เรียกด้วยชื่อโรงเรียน 404 ตามดีไซน์ ·
  ครูที่เป็นสมาชิกอ่านได้ 200 แต่ POST โดน 403 · owner POST 201 → PATCH ย้ายห้อง 7→8 สำเร็จ (พิสูจน์ G13 บนของจริง)
  → ลบแถวทดสอบออกแล้ว DB กลับมา 5 แถวเท่าเดิม
  **2 จุดที่ agy รายงานไม่ตรง (Claude แก้เอง):** (ก) บอกว่า pint ผ่าน แต่จริง ๆ ไฟล์เทสต์ตก `line_ending`
  → Claude รัน `pint` ซ้ำให้ผ่าน · (ข) ทิ้งไฟล์ `api/nuxnanravel/fix_test.py` ไว้นอกสเปค → Claude ลบทิ้ง

- **2026-09-16 SC-S2 🟢 verified** — agy เขียน (สเปค `agy-sc-s2-schedule-course.txt`) · Claude ตรวจเองทุกข้อ
  **diff จริง:** migration ใหม่ 1 ไฟล์ · `ClassScheduleController` +84/−36 · `ClassSchedule` +24/−? ·
  `SchoolManagementSeeder` (เฉพาะบล็อกตารางเรียน) · เทสต์ใหม่ `ClassScheduleCourseEntryTest` 6 เคส ·
  ไม่มีไฟล์นอกสเปคหลุดมา · สูตรตรวจชนเวลายังไม่ถูกแตะ (สงวนไว้ให้ SC-S5)
  **schema ใหม่:** `subject_id` หายไป · เพิ่ม `course_id` (null ได้) `title` `entry_type` (default `course`) `period_id` ·
  index `subject_id+semester_id` → `course_id+semester_id` · **ไม่ใส่ FK ตามที่ตัดสินใจไว้** (DB ทั้งก้อนไม่มี FK)
  **เกณฑ์ที่ Claude รันเอง:** `grep -c subject` = 0 ทั้ง controller และ model · `pint --test` ผ่าน ·
  `ClassScheduleGuardTest` + `ClassScheduleCourseEntryTest` = 14/14 (32 assertions)
  **รัน migration จริงบน DB dev เอง (agy ไม่ได้รัน ตรวจ `migrate:status` = Pending ก่อนรัน):**
  สำรอง 5 แถวเป็น JSON ก่อน → `migrate` ✅ (คอลัมน์ครบ · **backfill `title` จากชื่อวิชาถูกต้องทั้ง 5 แถว**
  เทียบกับ subject_id เดิมทีละแถวแล้ว) → `migrate:rollback` ✅ (คืน `subject_id` + index เดิมเป๊ะ ข้อมูล 5 แถวอยู่ครบ)
  → คืนค่า `subject_id` จากไฟล์สำรอง → `migrate` ซ้ำ ✅ ได้ผลเหมือนเดิมทุกแถว ⇒ **`down()` ใช้ได้จริง ไม่ใช่แค่มีไว้**
  **ยิงเซิร์ฟเวอร์จริง:** `GET /schedules` คืน `entry_type`/`title`/`course` และ**ไม่มีคีย์ `subject`** แล้ว ·
  POST ผูกคอร์สจริง `ง 20201` 201 (timetable แสดงชื่อคอร์สและรหัสวิชาถูกต้อง) ·
  POST คาบ "ชุมนุม" แบบไม่มีคอร์ส 201 (`course: null`, `entry_type: activity`) ·
  POST ที่ไม่มีทั้งคอร์สและชื่อ 422 · POST คอร์สนอกโรงเรียน 422 · ลบแถวทดสอบออกแล้ว DB กลับมา 5 แถว
  **agy พลาดซ้ำเรื่องเดิม:** รายงานว่า pint ผ่านทั้งที่ตกจริง 3 ไฟล์ (line_ending + class_definition + single_quote)
  → Claude รัน `pint` เองให้ผ่าน · ส่วนข้อห้ามรัน migrate ครั้งนี้ agy ทำตามถูกต้อง

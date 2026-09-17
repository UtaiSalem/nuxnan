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
| 5 | กันครูชนเวลา | ✅ | แก้ขอบเวลาแล้ว (G6 ปิด 2026-09-17) — คาบติดกันจัดได้ คาบซ้อนยังกันอยู่ |
| 6 | กันห้องเรียนชนเวลา | ✅ | เหมือนข้อ 5 (ใช้สูตรเดียวกันที่ `overlappingQuery`) |
| 7 | กันสถานที่ (`room`) ชนกัน | ⚠️ | มีแล้ว 2026-09-17 (`hasRoomConflict` ครบทั้ง store/update/bulk/check-availability) แต่ยังเทียบด้วย **ชื่อที่พิมพ์เอง** (ตัดช่องว่างซ้ำให้แล้ว) — ยังไม่มีทะเบียนห้องจริง ดู G17 |
| 8 | โครงคาบเรียนของโรงเรียน (คาบ 1..n + พัก + กิจกรรม) | ✅ | SC-S6: ตั้งได้**หลายชุด** (`schedule_period_sets`) ผูกเงื่อนไขวัน/ระดับชั้น · มีหน้าตั้งค่า `/admin/schedule-periods` · กริดวาดจากคาบจริง |
| 9 | ตารางสอนของฉัน (ครู) | ❌ | มีแค่การ์ด "วันนี้" ในแดชบอร์ดครู ซึ่ง 404 |
| 10 | ตารางเรียนของฉัน (นักเรียน/ผู้ปกครอง) | ❌ | สิทธิ์ `schedule.view.own` มีในระบบ แต่ไม่มี endpoint/หน้า |
| 11 | ภาระงานสอนของครู (คาบ/สัปดาห์) | ❌ | `teacher_assignments` ว่าง ไม่มี UI |
| 12 | สร้างหลายคาบพร้อมกัน (bulk) | ⚠️ | API `bulkStore` มี แต่ไม่มี UI เรียก |
| 13 | คัดลอกตารางข้ามภาคเรียน/ข้ามห้อง | ❌ | ไม่มี |
| 14 | พิมพ์/ส่งออกตาราง (PDF/Excel) รายห้อง-รายครู | ❌ | ไม่มี (โรงเรียนต้องใช้จริง) |
| 15 | คาบสอนแทน / งดคาบ | ⚠️ | enum `status` มี `cancelled/temporary` แต่ไม่มี UI และไม่มีความหมายเชิงธุรกิจ · ตรรกะกันชนไม่นับคาบที่ยกเลิกแล้ว แต่ **unique index ระดับ DB ยังนับอยู่** (G22) |
| 16 | วันเสาร์–อาทิตย์ | ✅ | SC-S6: คอลัมน์วันมาจากชุดโครงคาบ + วันที่มีคาบจริง · ฟอร์มเลือกได้ครบ 1–7 |
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
⇒ `members` ได้ 20 แถวแรกของสมาชิกทุกบทบาท (ส่วน `classrooms` ไม่กระทบ ดู G9 ที่แก้แล้ว)

**G3 · ไม่ได้ส่ง `semester_id` ตอนสร้าง** — `store` validate `required|exists:semesters,id` แต่ฟอร์มไม่มีฟิลด์นี้ → 422 แน่นอน

**G4 · frontend ใช้ `api.put` แต่ route มีแค่ PATCH** → 405 (ยิงจริงยืนยันแล้ว)

**G5 · endpoint วิชาผิด path** — หน้าเรียก `/academies/{id}/curriculums/subjects` (ไม่มีจริง, 404)
ของจริงคือ `/academies/{id}/subjects` ⇒ dropdown "วิชา" ว่างเสมอ → กดบันทึกไม่ได้เพราะฟิลด์ `required`

### P1 — ถึงต่อสายติดแล้วก็ยังใช้งานจริงไม่ได้

**G6 · ~~ตรรกะกันเวลาชนผิดที่ขอบคาบ~~ — 🟢 ปิดแล้ว 2026-09-17 (SC-S5)**
~~`hasTeacherConflict` / `hasClassroomConflict` ใช้ `whereBetween` ซึ่ง **นับปลายช่วงด้วย**~~
~~⇒ คาบ 08:00–09:00 กับ 09:00–10:00 (ห้องเดียวกัน/ครูคนเดียวกัน) ถูกตัดสินว่า "ซ้ำซ้อน"~~
แก้เป็นช่วงแบบ half-open `start_time < :end AND end_time > :start` ที่ `ClassSchedule::overlappingQuery()`
(จุดเดียวที่ทั้ง teacher/classroom/room ใช้ร่วมกัน) · ยิงจริงบน MySQL แล้ว: 08:00–09:00 ต่อ 09:00–10:00 = 201

**G7 · ภาคเรียนไม่ผูกโรงเรียนและชี้ปีที่แล้ว**
`Semester::current()` = `where('is_current', true)` ล้วน ๆ (ตาราง `semesters` ไม่มี `academy_id` ผูกผ่าน `academic_years`)
⇒ ระบบหลายโรงเรียนจะหยิบภาคเรียนของโรงเรียนอื่นมาเป็นตัวกรองเงียบ ๆ
⇒ วันนี้ยังชี้ภาคเรียนของ **ปี 2568** ทั้งที่ปีปัจจุบันคือ 2569 และไม่มี GET route ให้ list ภาคเรียน

**G8 · dropdown "ครูผู้สอน" ไม่ได้กรองครู**
`AcademyMemberController::getAcademyMembers(Academy $academy)` รับแค่ `per_page` — **ไม่อ่าน `role`/`status` เลย**
⇒ ช่องเลือกครูคือสมาชิก 20 คนแรกของโรงเรียน (ซึ่งมีนักเรียน 2,481 คนปนอยู่)

**G9 · ~~dropdown "ห้องเรียน" ไม่กรองปีการศึกษา~~ — ❌ ข้อนี้ผมเคลมผิด (แก้ 2026-09-16)**
ยิง `GET /academies/1/classrooms` จริงแล้วพบว่า `ClassroomController::index` **ตั้งค่าเริ่มต้นเป็นปีการศึกษาปัจจุบันให้เองอยู่แล้ว**
(และไม่แบ่งหน้า) ⇒ คืน 53 ห้องของ 2569 ครบ ไม่ได้ปนปีเก่าและไม่ได้ถูกตัดเหลือ 20 ตามที่เขียนไว้ตอน audit
สิ่งที่ยังต้องทำจริงคือ **ส่ง `academic_year_id` เมื่อผู้ใช้เลือกปีอื่น** ซึ่งทำใน SC-S4 แล้ว

**G10 · ด่านสิทธิ์อ่านหลวมและคีย์ที่มีไม่ถูกใช้** — ดู §4 (อ่านได้ทุกคนที่เป็นสมาชิก · `schedule.manage` ไม่อยู่ในบทบาทใด)

**G11 · หน้า admin กันด้วยสิทธิ์ผิดตัว** — `!isAdmin && !can('academy.view')` ⇒ นักเรียนเปิดหน้าจัดตารางได้ เห็นปุ่มเพิ่ม/ลบครบ (ไปพังตอนยิง API)

**G12 · ไม่ตรวจว่า classroom/subject/teacher เป็นของโรงเรียนนี้จริง**
validate แค่ `exists:classrooms,id` / `exists:subjects,id` / `exists:users,id`
⇒ ยัด id ข้ามโรงเรียนได้ แล้วแถวจะถูกเขียนด้วย `academy_id` ของ URL (ข้อมูลปนข้ามโรงเรียน)

**G13 · `update` แก้ห้องเรียนไม่ได้จริง** — ใช้ `$request->classroom_id` ตอนตรวจชน แต่ `$schedule->update($request->only([...]))`
ไม่มี `classroom_id` ในลิสต์ ⇒ ฟอร์มส่งมาก็ถูกทิ้งเงียบ ๆ (และ `classroom_id` ไม่อยู่ใน validator ด้วย)

### P2 — ฟีเจอร์/UX ที่ขาด

**G14 · ~~กริดเวลาเป็นค่าฮาร์ดโค้ด~~ — 🟢 ปิดแล้ว 2026-09-17 (SC-S6)**
~~08:00–16:00 รายชั่วโมง และ `getScheduleAt` จับคู่ด้วย *ชั่วโมง* อย่างเดียว~~
กริดวาดจาก **ชุดโครงคาบที่โรงเรียนตั้งเอง** แล้ว: แถว = ช่วงเวลาจริงของคาบ (เรียงตามเวลา) ·
จับคาบสอนเข้าแถวด้วย "ซ้อนทับกันมากที่สุด" และหนึ่งคาบอยู่ได้แถวเดียว ·
คาบที่ไม่ตรงช่วงไหนเลยได้แถว **"นอกโครงคาบ"** ของตัวเอง ⇒ ไม่มีคาบไหนหายจากจออีก
(พิสูจน์บนจอจริง: คาบ 06:30 และ 17:00 ที่กริดเดิมซ่อนทิ้ง ตอนนี้ขึ้นครบ)

**G15 · ~~UI มีแค่ จ–ศ~~ — 🟢 ปิดแล้ว 2026-09-17 (SC-S6)**
คอลัมน์วันมาจาก `days` ของชุดโครงคาบ + **วันไหนมีคาบอยู่จริงต้องขึ้นเสมอ** (ตาข่ายกันคาบหาย) ·
dropdown วันในฟอร์มให้เลือกครบ 1–7 · พิสูจน์แล้ว: สร้างคาบวันเสาร์ → คอลัมน์ "เสาร์" โผล่เองและกดแก้/ลบได้

**G16 · มือถือ** (ผิดกติกา mobile-first ของโปรเจค)
- ปุ่มลบคาบซ่อนอยู่ใต้ `opacity-0 group-hover:opacity-100` → **บนจอสัมผัสไม่มี hover → ลบคาบไม่ได้เลย**
- modal ไม่มี `max-h`/`overflow-y-auto` → ฟอร์ม 7 ฟิลด์บนจอ 375×667 ดันปุ่ม "บันทึก" ตกขอบจอ
- กริด `min-w-[800px]` อยู่ใน `overflow-x-auto` ของตัวเอง (ข้อนี้ถูกกติกาแล้ว)

**G17 · ฟีเจอร์ที่โรงเรียนใช้จริงแต่ยังไม่มี** — พิมพ์/ส่งออกตารางรายห้อง-รายครู · คัดลอกตารางข้ามภาคเรียน ·
UI bulk (API มีแล้ว) · สอนแทน/งดคาบ · ภาระงานสอน · ทะเบียนสถานที่ + กันห้องชน

**G20 · ~~(เจอตอนรีวิว SC-S2) `PATCH` เคลียร์คอร์สทิ้งโดยไม่มีชื่อแทนได้~~ — 🟢 ปิดแล้ว 2026-09-17 (SC-S5)**
~~`store()` บังคับ `title` เมื่อไม่มี `course_id` แล้ว แต่ `update()` ยังยอมให้ส่ง `{"course_id": null}` เดี่ยว ๆ~~
`update()` คำนวณ "ค่าหลังรวมร่าง" (ค่าที่ส่งมา ถ้าไม่ส่งใช้ค่าเดิม) แล้วตอบ 422 `errors.title` เมื่อไม่เหลือทั้ง
`course_id` และ `title` — ครอบทั้งการเคลียร์คอร์สและการล้าง `title` ของคาบที่ไม่มีคอร์ส (`"   "` ก็ไม่ผ่าน)

**G21 · (เจอตอนออกแบบ SC-S5) `PATCH` ทำให้ช่วงเวลากลับหัวได้** — 🟢 ปิดแล้ว 2026-09-17 (SC-S5)
validator ของ `update()` มีแค่ `sometimes|date_format:H:i` (ไม่มี `after:start_time` แบบ `store()`)
⇒ ส่ง `end_time` ที่ย้อนก่อน `start_time` เดิมได้ · **ช่วงกลับหัวจะไม่มีวันชนกับใครตามสูตร half-open**
(คาบผีที่จองเวลาไว้แต่ระบบมองไม่เห็น) ⇒ `update()` เทียบค่าหลังรวมร่างแล้วตอบ 422 `errors.end_time`

**G22 · 🔴 unique index ระดับ DB ยังค้างอยู่และไม่รู้จัก `status` — ยังไม่ปิด (ต้องมี migration)**
`class_schedules` มี `unique_teacher_schedule` = (`teacher_id`,`semester_id`,`day_of_week`,`start_time`)
และ `unique_classroom_schedule` = (`classroom_id`,`semester_id`,`day_of_week`,`start_time`) — ยืนยันบน MySQL จริงแล้ว
ปัญหา: (ก) มันกันแค่ "เวลาเริ่มตรงกันเป๊ะ" ซึ่งเป็นเซตย่อยของการชนจริง ⇒ ไม่ได้ช่วยอะไรที่ตรรกะแอปไม่ได้ทำ
(ข) มัน **ไม่สนใจ `status`** ⇒ คาบที่ถูก "ยกเลิก" ยังจองเวลาเริ่มนั้นไว้ ⇒ สร้างคาบใหม่ทับเวลาเริ่มเดิม
จะได้ **500 (SQLSTATE 23000)** แทนที่จะเป็น 422 ที่อ่านรู้เรื่อง (เทสต์ `test_cancelled_period_does_not_block`
จึงต้องเลี่ยงเวลาเริ่มที่ตรงกันเป๊ะ) · ทางแก้ต้องเป็น migration (ตามกติกาโปรเจค) ⇒ ยกไปเคาะที่ SC-S11

**G23 · (เจอตอนตรวจ SC-S6 บนจอจริง) โมดัลของหน้า admin เป็น `z-50` เท่ากับแถบเมนูล่างของมือถือ**
แถบเมนูล่าง (`fixed bottom-0 z-50`, สูง 64px) ถูกวาดทับปุ่มท้ายโมดัล ⇒ บนจอ 375px กดปุ่ม "บันทึก" ไม่โดน
(กดแล้วไปโดนเมนู "รายได้" แทน — ยืนยันด้วย `document.elementFromPoint` บนหน้าจริง)
หน้าใหม่ของ SC-S6 แก้แล้วด้วย `z-[60]` แต่ **โมดัล `z-50` อื่น ๆ ทั้งเมนู admin ยังเป็นแบบเดิม 13 จุด**
⇒ รวมไว้ใน **SC-S8** (งาน UX/มือถือ) พร้อม G16

**G24 · (เจอตอนตรวจ SC-S6) หน้าเว็บอ่าน error จาก API ผิดที่ ⇒ ข้อความ 422 ไม่เคยถึงผู้ใช้**
`useApi` โยน `ApiError` ที่เก็บ body ไว้ที่ **`.data`** (สร้างใหม่ใน `createApiError()` — ไม่มีคีย์ `response`)
แต่หลายหน้าอ่าน `err.response?.data?.errors` (แบบ axios) หรือ `error?.response?._data` (แบบ ofetch ดิบ)
⇒ ตกไปที่ข้อความสำรองเสมอ เช่น "ไม่สามารถอัปเดตตารางเรียนได้" ทั้งที่ backend ส่ง
"ครูผู้สอนมีตารางสอนซ้ำซ้อนในเวลานี้" มาให้แล้ว (ทำให้ผลงานของ SC-S5 ไม่ถึงผู้ใช้)
`schedule.vue` + หน้าใหม่ของ SC-S6 แก้แล้วเป็น `err.data` (พิสูจน์บนจอจริง: ข้อความไทยขึ้นจริงแล้ว) ·
ที่เหลือทั้งเรพ ~30 ไฟล์ **ยังผิดอยู่** → แยกเป็นงานต่างหาก (ไม่ใช่ของเมนูนี้อย่างเดียว)

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
| **SC-S3** | **ภาคเรียน 2569 (D3)** — migration สร้าง 1/2569 + 2/2569 และย้าย `is_current` · `Semester::currentForAcademy()` ผูกโรงเรียน + แก้ `setAsCurrent()` ที่เป็นต้นตอ (G7) · ~~เพิ่ม endpoint list ภาคเรียน~~ **ไม่ต้องทำ — `GET /academies/{id}/academic-years` คืน `semesters[]` มาให้อยู่แล้ว** | SC-S1 | migration + model + เทสต์ | 🟢 **done 2026-09-16** (รัน migrate + rollback + migrate ซ้ำบน DB dev แล้ว ดู §9) |
| **SC-S4** | **เดินสาย frontend ทีเดียวจบ** — ส่ง query ผ่าน `{ params }` (G2), PUT→PATCH (G4), ส่ง `semester_id` (G3), เปลี่ยน dropdown วิชา → ตัวเลือก **คอร์ส** จาก `GET /academies/{id}/courses` + ช่องกรอกชื่อเองเมื่อไม่มีคอร์ส (G5/D5), ใช้ `academyId` (D1), กันหน้าด้วย `schedule.view/manage` (G11), selector ปี/ภาคเรียน · **ต้องส่ง `title` มาด้วยเสมอเมื่อเคลียร์ `course_id`** (ดู G20) | SC-S2 · SC-S3 | หน้า admin ดู/สร้าง/แก้/ลบได้จริงครบวง | 🟢 **done 2026-09-16** (ตรวจบนจอจริง 375/768/1280 + สร้าง/แก้/ลบผ่าน UI จริง ดู §9) |
| **SC-S5** | **แก้ตรรกะกันชน** — ขอบเวลาให้คาบติดกันได้ (G6) + กันสถานที่ (`room`) ชน + ปิด G20/G21 + เทสต์เคสคาบติดกัน/คร่อม/แก้คาบเดิม/ข้ามภาคเรียน | SC-S2 | `ClassSchedule` + เทสต์บน MySQL จริง | 🟢 **done 2026-09-17** (เทสต์ใหม่ 27 เคส + ยิงจริงบน MySQL 16 เคส ดู §9) |
| **SC-S6** | **ชุดโครงคาบที่ตั้งค่าเองได้ (D2)** — migration `schedule_period_sets` + `schedule_periods.set_id` + เงื่อนไขใช้กับวัน/ระดับชั้น · CRUD + หน้าตั้งค่า · กริดวาดจากคาบแทนชั่วโมงฮาร์ดโค้ด (G14) · เลือกวันเปิดสอน 1–7 (G15) | SC-S4 | migration + controller + routes + หน้าตั้งค่า + กริดใหม่ | 🟢 **done 2026-09-17** (แตกเป็น S6a/S6b/S6c · เทสต์ใหม่ 20 เคส · migrate→rollback→migrate บน MySQL จริง · ตรวจบนจอจริง 375/768/1280 ดู §9) |
| **SC-S7** | **สิทธิ์ (D4)** — เปลี่ยนด่าน GET เป็น `schedule.view` · migration เติม `schedule.view` ให้ teacher/staff/student/parent และ `schedule.manage` ให้ owner/admin + ฝ่ายวิชาการ (G10) · endpoint + หน้า "ตารางสอนของฉัน / ตารางเรียนของฉัน" | SC-S4 | routes + role migration + 2 หน้า + เทสต์สิทธิ์ | ⚪ |
| **SC-S8** | **UX/มือถือ (G16)** — ปุ่มลบที่แตะได้จริงบนจอสัมผัส, modal เลื่อนได้, ช่องเลือกครู/ห้องแบบค้นหาได้ + กรองครูจริง (G8 ต้องแก้ `getAcademyMembers` ให้รับ `role`/`status`) + กรองห้องตามปีการศึกษา (G9) | SC-S4 | ตรวจจริงที่ 375/768/1280 | ⚪ |
| **SC-S9** | **ซ่อมผู้เรียกอื่น** — แดชบอร์ดครู `/schedules/today` + แท็บตารางเรียนในเมนู #8 (`useSchoolManagement`) ให้เข้ากับ response ใหม่ | SC-S2 | 2 จุดกลับมา 200 พร้อมข้อมูลจริง | ⚪ |
| **SC-S10** | **ฟีเจอร์ที่โรงเรียนใช้จริง (G17)** — พิมพ์/ส่งออกตารางรายห้อง-รายครู, UI bulk, คัดลอกตารางข้ามภาคเรียน, สอนแทน/งดคาบ, ภาระงานสอนของครู | SC-S6 · SC-S7 | แตกย่อยอีกทีตอนถึงคิว | ⚪ |
| **SC-S11** | **เทสต์ (G18)** — ไฟล์เทสต์ของเมนูนี้ที่รันได้จริงบน MySQL + ตัดสินใจเรื่อง `tests/Api/SchoolManagementApiTest.php` ที่ตายอยู่ · **+ migration จัดการ unique index ที่ไม่รู้จัก `status` (G22)** | SC-S5 · SC-S7 | เทสต์เขียว | ⚪ |

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

- **2026-09-16 SC-S4 🟢 verified** — agy เขียน (สเปค `agy-sc-s4-schedule-frontend.txt`) · Claude ตรวจเองทุกข้อ
  **ตัดสินใจก่อนส่งงาน 2 เรื่อง:** (ก) **ดึง G8 จาก SC-S8 ขึ้นมาทำในรอบนี้** เพราะไม่มี endpoint ไหน
  ในระบบ list ครูได้เลย และ `getAcademyMembers` อ่านแค่ `per_page` ⇒ ช่องเลือกครูจะเป็นสมาชิก 20 คนแรก
  ที่เกือบทั้งหมดเป็นนักเรียน (จาก 3,063 คน เป็นครู 120) · (ข) **ดึง 2 ข้อของ G16 มาทำ**
  (modal ล้นจอ + ปุ่มลบที่ซ่อนใต้ `group-hover` กดไม่ได้บนจอสัมผัส) เพราะต้องแก้ markup ตรงนั้นอยู่แล้ว
  **diff จริง:** `schedule.vue` +436/−237 (เขียนใหม่ทั้ง script และครึ่งหนึ่งของ template) ·
  `AcademyMemberController::getAcademyMembers` +29/−6 (เพิ่ม `role`/`status`/`search` แบบไม่เปลี่ยน
  พฤติกรรมเดิมเมื่อไม่ส่งพารามิเตอร์ + ครอบ `per_page` ไม่เกิน 200) · เทสต์ใหม่ `AcademyMemberFilterTest`
  (1 เมธอด 12 assertion ครอบ 5 เคสตามสเปค) · ไม่มีไฟล์นอกสเปคหลุดมา
  **3 จุดที่ Claude แก้เองหลังรีวิว (บั๊กจริง ถ้าปล่อยไว้หน้าจะพังตอนแก้ไขคาบ):**
  payload ของ timetable ไม่มี `day` ในตัวรายการ (วันอยู่ที่กลุ่มแม่) และส่ง `classroom` มาเฉพาะมุมมองครู /
  ส่ง `teacher` มาเฉพาะมุมมองห้องเรียน ⇒ `openEditModal` เดิมจะได้ `day_of_week=1` เสมอ (แก้คาบวันพฤหัสแล้ว
  เด้งไปวันจันทร์) และส่ง `classroom_id`/`teacher_id` เป็น null ⇒ **PATCH 422 ทุกครั้ง**
  → รับ `day` จากช่องที่กดมา + เติมอีกฝั่งจากตัวเลือกที่กำลังดูอยู่
  **1 จุดที่ Claude แก้ที่ backend:** `index`/`timetable` เคยส่ง `title` เป็นค่าที่คำนวณแล้ว (display_title)
  ⇒ ฟอร์มแก้ไขจะคัดลอกชื่อคอร์สลงคอลัมน์ `title` ทุกครั้งที่กดบันทึก → เปลี่ยนเป็นส่ง `title` ดิบ
  และเพิ่มคีย์ `display_title` แยกต่างหาก (กริดมี fallback `title || course.name` อยู่แล้ว)
  **เกณฑ์ที่ Claude รันเอง:** SFC compile ผ่าน · `grep` = 0 ทั้ง `api.put` / `subject` /
  `curriculums/subjects` / `group-hover:opacity-100` · `pint --test` ผ่าน · เทสต์ 20/20 (54 assertions)
  **ตรวจบนเบราว์เซอร์จริง (dev server ของ session นี้ + JWT ของเจ้าของโรงเรียน):**
  · **375px** — ไม่มีการเลื่อนแนวนอนทั้งหน้า (`scrollWidth == clientWidth == 375`) · ตัวเลือกปี/ภาค/มุมมอง/ห้อง
    เรียงลงล่างเต็มความกว้าง · modal เลื่อนในตัวเองและปุ่มบันทึกกดได้ · ปุ่ม "ลบคาบนี้" อยู่ในโมดัลเต็มความกว้าง
  · **network ที่ยิงจริง** (หลักฐานว่า G2/G3/D1 ปิดแล้ว): `/academic-years` 200 ·
    `/classrooms?academic_year_id=2` 200 · `/members?role=teacher&status=2&per_page=200` 200 ·
    `/courses?per_page=100` 200 · `/schedules/timetable?semester_id=4&classroom_id=58` 200
  · **ช่องเลือกคอร์ส** มีคอร์สจริง 22 รายการพร้อมรหัสวิชา (ง 20201 ฯลฯ) — เดิมว่างเปล่า
  · **ช่องเลือกครู** เป็นรายชื่อครูจริง (นายข๊ดดะรี บินดุเหล็ม ฯลฯ) — เดิมเป็นนักเรียนปน
  · **วงจรเต็ม CRUD ผ่าน UI ที่ 375px**: สร้างคาบผูกคอร์ส ง 20201 → 201 · กริดแสดงชื่อ+รหัสวิชา+ครู ·
    เปิดแก้ไข → PATCH 200 · ย้ายวันจันทร์→พฤหัสบดี แล้วเปิดใหม่ modal แสดง "พฤหัสบดี" ถูกต้อง ·
    ลบผ่านปุ่มในโมดัล → "ลบสำเร็จ" · DB กลับมา 5 แถวเท่าเดิม (ไม่มีขยะค้าง)
  · **768px / 1280px** — ไม่มีการเลื่อนแนวนอนทั้งหน้าทั้งสองขนาด
  **agy ทำเกินคำสั่งเล็กน้อย:** รัน `git add` ให้ (สเปคห้ามแค่ commit/push) — ไม่กระทบอะไร แต่รอบหน้าจะสั่งห้ามด้วย

- **2026-09-16 SC-S3 🟢 verified** — agy เขียน (สเปค `agy-sc-s3-semester-2569.txt`) · Claude ตรวจเองทุกข้อ
  **ตัดงานออกไป 1 ข้อก่อนส่ง:** สเปคเดิมจะให้เพิ่ม endpoint list ภาคเรียน — ยิงของจริงก่อนแล้วพบว่า
  `GET /api/academies/{id}/academic-years` **eager load `semesters[]` มาให้ครบทุกปีอยู่แล้ว**
  จึงสั่งห้ามแตะ `AcademicYearController` และห้ามแตะ routes เลย (SC-S4 ใช้ endpoint เดิมทำ selector ได้)
  **เจอต้นตอของอาการ "ภาคเรียนค้างปีเก่า":** `Semester::setAsCurrent()` ล้าง `is_current` เฉพาะภาคเรียน
  ในปีเดียวกัน ⇒ พอขึ้นปีใหม่ ภาคของปีเก่าจึงค้างสถานะปัจจุบันตลอด — แก้ให้ล้างทั้งโรงเรียน (ไม่ข้ามโรงเรียน)
  **diff จริง:** `Semester.php` +30/−7 (เพิ่ม `scopeForAcademy` + `currentForAcademy` + แก้ `setAsCurrent`
  · `scopeCurrent` เดิมไม่ถูกแตะเพราะมีที่อื่นใช้) · `ClassScheduleController` แก้ 3 บรรทัดตามสเปคเป๊ะ ·
  migration ใหม่ 1 ไฟล์ · เทสต์ใหม่ `SemesterCurrentScopeTest` 5 เคส · ไม่มีไฟล์นอกสเปค
  **2 จุดที่ Claude แก้เองหลังรีวิว** (อธิบายไว้เพราะเป็นทางที่ลบข้อมูลได้): (ก) migration เรียก `DB::table()`
  โดยไม่ได้ `use Illuminate\Support\Facades\DB` (รอดเพราะ global alias — เปราะ) → เพิ่ม import ·
  (ข) `down()` เดิมจะลบภาคเรียน **ทุกแถว** ของปีปัจจุบันที่ไม่มีใครอ้างถึง ซึ่งจะกินภาคเรียนที่โรงเรียน
  สร้างเองไปด้วย → เพิ่มตัวเทียบ `expectedSemesters()` (ชื่อ+ช่วงวันต้องตรงกับที่ `up()` สร้างเท่านั้น)
  และให้ `up()`/`down()` ใช้สูตรเดียวกันจะได้ไม่หลุดกัน
  **เกณฑ์ที่ Claude รันเอง:** `grep -c "Semester::current()"` = 0 · `pint --test` ผ่าน ·
  เทสต์ 19/19 (42 assertions) ทั้ง 3 ไฟล์ของเมนูนี้
  **รัน migration จริงบน DB dev เอง** (สำรอง `semesters` เป็น JSON ก่อน · agy ไม่ได้รัน ตรวจ status = Pending):
  `migrate` ✅ ได้ 1/2569 = 2026-05-16..2026-10-15 (`is_current`) และ 2/2569 = 2026-10-16..2027-03-31
  · `is_current` ของ 1/2568 ถูกปิด · `migrate:rollback` ✅ **คืนสภาพเดิมเป๊ะ** (เหลือภาคเรียนเดียวของ 2568
  ที่ `is_current=1` · `class_schedules` 5 แถวไม่ถูกแตะ) · `migrate` ซ้ำ ✅ ได้ผลเดิม
  **ยิงเซิร์ฟเวอร์จริง (หลักฐานว่า G7 ปิดแล้ว):** `GET /schedules` แบบไม่ส่ง `semester_id` คืน **0 แถว**
  (เดโมปี 2568 ไม่ถูกนับเป็น "ปัจจุบัน" อีกแล้ว) · ส่ง `semester_id=1` ยังได้ 5 แถวเดิม ·
  POST คาบใหม่ในภาคเรียนปัจจุบัน 201 แล้ว `GET /schedules` เห็นทันทีโดยไม่ต้องระบุภาคเรียน · ลบแถวทดสอบแล้ว

- **2026-09-17 SC-S5 🟢 verified** — agy เขียน (สเปค `agy-sc-s5-schedule-conflict.txt`) · Claude เขียนเทสต์เอง + ตรวจเองทุกข้อ
  **ขอบเขต:** G6 (ขอบคาบ) + กันสถานที่ (`room`) + G20 (เคลียร์คอร์สจนไร้ชื่อ) + G21 (ช่วงเวลากลับหัว) — ไม่แตะ `ui/` เลย
  **diff จริง (นับจาก `git diff --numstat`):** `ClassSchedule.php` **+82/−29** (deletion ทั้งหมดคือบล็อก
  `whereBetween` เดิมที่ถูกแทนที่ · `$fillable`/`$casts`/scopes/accessors ไม่ถูกแตะ) ·
  `ClassScheduleController.php` **+97/−4** (บรรทัด `-` ทั้ง 4 คือ `'room' => $request->room,` ·
  `'room' => $scheduleData['room'] ?? null,` · และคู่ `$schedule->update($request->only([` / `]));`
  ที่กลายเป็น `$payload` — คีย์ครบเท่าเดิมทั้ง 13 ตัว) ·
  เทสต์ใหม่ `tests/Feature/ClassScheduleConflictTest.php` 27 เคส · **ไม่มีไฟล์นอกสเปคหลุดมา** · ลายเซ็น
  `hasTeacherConflict`/`hasClassroomConflict` เหมือนเดิม ⇒ ผู้เรียก 6 จุดไม่ต้องแก้
  **🔴 1 จุดที่ Claude แก้เอง เพราะสเปคของ Claude เองผิด (ไม่ใช่ความผิด agy):** สเปคสั่งให้ normalize ค่าขอบ
  เป็น `'H:i:s'` แล้วเทียบกับคอลัมน์ตรง ๆ — พอรันเทสต์จริงถึงเห็นว่า **ค่าที่เก็บสองฐานไม่เหมือนกัน**:
  MySQL เก็บเป็นชนิด `TIME` (`'09:00:00'`) แต่ SQLite ที่ใช้รันเทสต์เก็บเป็นข้อความตามรูปแบบ cast คือ `'09:00'`
  ⇒ เทียบแบบสตริง `'09:00' < '09:00:00'` เป็นจริง ⇒ คาบที่ **จบพอดีตอนคาบเดิมเริ่ม** ถูกนับว่าชนบน SQLite
  (เคส `test_period_ending_exactly_when_existing_starts_is_allowed` แดง) → เปลี่ยนเป็นหุ้ม `TIME()` ทั้งสองฝั่ง
  (`TIME(start_time) < TIME(?)`) ซึ่งมีทั้ง MySQL และ SQLite และคืน `'H:i:s'` เหมือนกัน — ยืนยันค่าเทียบบน MySQL จริงแล้ว
  **เกณฑ์ที่ Claude รันเอง:** `pint --test` ผ่าน (3 ไฟล์) · `php -l` clean · `grep -c whereBetween` = 0 ·
  เทสต์ใหม่ **27/27 (76 assertions)** · สวีทข้างเคียง `ClassSchedule|ClassroomManagement` **60/60 (158 assertions)**
  **revert-check (พิสูจน์ว่าเทสต์กัดจริง):** เอาโมเดลเวอร์ชันก่อนหน้ากลับมา → แดง **16/27** ทันที
  โดยเคสที่แดงเพราะสูตรขอบเวลาล้วน ๆ คือ "คาบติดกัน" และ "คาบที่จบตอนอีกคาบเริ่ม"
  **ยิงจริงบน MySQL (ภาคเรียนปัจจุบัน 4/2569 · ห้อง 58,67 · ครู 17004,17005 · วันพุธ):**
  · 08:00–09:00 แล้วต่อ 09:00–10:00 ครู+ห้องเรียน+สถานที่เดียวกัน = **201 ทั้งคู่** (เดิมคือ 422 — นี่คือ G6)
  · สถานที่เดียวกันเวลาซ้อน (คนละครู คนละห้องเรียน) = 422 `สถานที่นี้ถูกใช้ในเวลานี้แล้ว`
  · สถานที่เดียวกันติดกันพอดี = 201 · ชื่อสถานที่ที่มีช่องว่างเกิน `"  ห้องทดสอบ   SC-S5 "` = 422 (ยุบช่องว่างแล้วตรงกัน)
  · ครูซ้อน = 422 ครู · ห้องเรียนซ้อน = 422 ห้องเรียน (ข้อความเดิมไม่เปลี่ยน)
  · PATCH `{"course_id":null}` เดี่ยว ๆ = 422 `errors.title` (G20) · แนบ `title` มาด้วย = 200
  · PATCH `end_time` ย้อนก่อน `start_time` = 422 `errors.end_time` · PATCH `start_time` เลยหลัง `end_time` = 422 (G21)
  · PATCH เวลาเดิมของตัวเอง = 200 (ไม่ชนกับตัวเอง) · PATCH ย้ายสถานที่ไปทับของคาบอื่นที่เวลาซ้อน = 422
  · `check-availability` คืน `room_available:false` + `"สถานที่ไม่ว่างในเวลานี้"` เมื่อทับ · ช่วงติดกันพอดี = ว่างทั้งหมด
  · **ลบแถวทดสอบทั้ง 3 แถวแล้ว DB กลับมา 5 แถวเท่าเดิม (max id = 5)**
  **ของแถมที่เจอระหว่างทาง → G22:** เทสต์ "คาบที่ยกเลิกไม่ควรกันเวลา" ล้มด้วย **SQLSTATE 23000** ไม่ใช่ 422
  ⇒ ตามรอยเจอ unique index ระดับ DB 2 ตัวที่ไม่รู้จัก `status` (ยืนยันบน MySQL จริง) — บันทึกเป็น G22 ยกไป SC-S11
  เพราะแก้ต้องมี migration

- **2026-09-17 SC-S6 🟢 verified** — แตกเป็น 3 shard (agy เขียนทั้งสามตามสเปคของ Claude · Claude เขียนเทสต์ + ตรวจเองทุกข้อ)
  **S6a backend:** `schedule_period_sets` (name · `days` json · `grade_levels` json · `is_default` · `is_active` ·
  `display_order`) + `schedule_periods.set_id` และย้าย unique จาก `(academy_id, period_number)` ไป `(set_id, period_number)`
  ⇒ **โรงเรียนมีโครงคาบได้หลายชุด** · `SchedulePeriodSet::resolveFor(academy, day, gradeLevel)` เลือกชุดที่เจาะจงกว่าก่อน
  แล้วค่อยตกไปที่ชุดเริ่มต้น · CRUD 4 เส้น (`/academies/{id}/schedule-period-sets`) อ่านด้วย `academy.permission`
  เขียนด้วย `schedule.manage` · แก้ไขแบบส่งรายการคาบมาทั้งชุดแต่**จับคู่ของเดิมด้วย `period_number` เพื่อคง id**
  (มี `class_schedules.period_id` อ้างอยู่) · คาบที่ถูกลบ → เคลียร์ `period_id` ก่อนเสมอ ไม่ทิ้ง id ค้าง
  **S6b หน้าตั้งค่า** `ui/pages/academies/[name]/admin/schedule-periods.vue` + เมนูซ้าย "โครงคาบเรียน"
  **S6c กริดใหม่** — ดู G14/G15 ข้างบน
  **diff จริง:** ไฟล์ใหม่ 4 (migration · model · controller · หน้า Vue) + เทสต์ใหม่ 1 ·
  ไฟล์เดิมที่แตะ 4 (`SchedulePeriod` +11/−0 · `routes/learn/academy.php` +12/−0 · `admin.vue` +6/−0 ·
  `schedule.vue` +214/−55 ซึ่ง deletion ทั้งหมดคือ `timeSlots`/`days`/`getScheduleAt`/`getEndTime` ที่ถูกแทนที่)
  **โค้ดที่ agy ส่งมาตรงสเปคเป๊ะ** (เทียบทีละบรรทัดกับสเปคของทั้ง 3 ไฟล์ใหม่ = identical)
  **เกณฑ์ที่ Claude รันเอง:** `pint --test` ผ่าน · SFC คอมไพล์ผ่านทั้ง 3 ไฟล์ Vue ·
  เทสต์ใหม่ `SchedulePeriodSetTest` **20/20** · สวีทข้างเคียง (`SchedulePeriod|ClassSchedule|ClassroomManagement|Semester`) **86/86**
  **migration บน MySQL จริง — ครบวง up → rollback → up:**
  `migrate` ✅ (unique ย้ายสำเร็จ) → ยิง API สร้าง 2 ชุดที่ **มีคาบเลข 1 พร้อมกันทั้งคู่** (สคีมาเดิมทำไม่ได้) →
  ผูก `period_id` ไว้ 2 แถว → `rollback` ✅ คาบของชุดเริ่มต้นรอด · คาบของชุดอื่นถูกลบ · แถวที่ชี้คาบที่ถูกลบถูกเคลียร์
  เป็น null · `class_schedules` ครบ 5 แถว · unique เดิมกลับมา → `migrate` ซ้ำ ✅ **ได้ทดสอบเส้น backfill ด้วยข้อมูลจริง**
  (คาบกำพร้าถูกยกให้ชุด "โครงคาบหลัก" ที่สร้างให้อัตโนมัติ)
  **ตรวจบนจอจริง (เจ้าของโปรเจคล็อกอินเอง · 375 → 768 → 1280):**
  · สร้างชุด "โครงคาบเรียนมัธยม" 9 คาบ (รวมพักกลางวัน) ผ่านปุ่ม "ใช้ตัวอย่างโครงคาบมัธยม" ที่ 375px → บันทึกจริงลง DB
  · สร้างชุด "วันศุกร์เลิกเร็ว" (`days=[5]`, 2 คาบ) — ปุ่ม "เพิ่มคาบ" ต่อเวลาให้เองถูกต้อง (08:30–09:20 → 09:20–10:10)
  · กริด: 13 แถวจากคาบจริง · คอลัมน์ **จ–ศ + เสาร์** (เพราะมีคาบวันเสาร์) · แถวพักกลางวันพื้นหลังต่างและ**ไม่มีปุ่มเพิ่มคาบ**
  · คาบ 06:30 และ 17:00 ขึ้นแถว "นอกโครงคาบ" (กริดเดิมซ่อนทั้งคู่)
  · สร้างคาบ **ติดกันพอดี** 09:20–10:10 ต่อจาก 08:30–09:20 ครูคนเดิมผ่าน UI → 201 (ผลของ SC-S5 บนจอจริง)
    และแถวนั้นได้ `period_id` ผูกกับคาบในชุดอัตโนมัติ
  · ยิงคาบชนครู → ขึ้น Swal **"ครูผู้สอนมีตารางสอนซ้ำซ้อนในเวลานี้"** (ก่อนแก้ G24 จะขึ้นข้อความสำรองเท่านั้น)
  · ไม่มีการเลื่อนแนวนอนทั้งหน้าในทั้ง 3 ขนาด (375/768/1272) — กริดเลื่อนในกล่องตัวเอง
  · **ลบข้อมูลทดสอบออกหมดแล้ว** DB กลับสู่สภาพเดิม (class_schedules 5 แถว · ไม่มีชุด/คาบ · ไม่มี `period_id` ค้าง)
  **3 จุดที่ Claude แก้เองหลังตรวจ (อธิบายไว้เพราะไม่ได้อยู่ในสเปคเดิม):**
  (ก) หน้าใหม่ + `schedule.vue` อ่าน error ผิดที่ → เปลี่ยนเป็น `err.data` (G24)
  (ข) โมดัลหน้าใหม่เป็น `z-50` เท่าแถบเมนูล่าง กดปุ่มบันทึกบนมือถือไม่ได้ → `z-[60]` (G23)
  (ค) คาบวันเสาร์ไปเกาะแถวของชุด "วันศุกร์" เพราะเวลาซ้อนเท่ากันพอดี → ให้แถวของชุดที่ใช้กับ "วันนั้น" ชนะเสมอ

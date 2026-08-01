# 26 — เช็คชื่อเข้าร่วมกิจกรรม (Activity Attendance)

> ไฟล์รองของเมนู **#26** — เมนูใหม่ ไม่อยู่ในสารบัญ 24 เมนูเดิมของ [OVERVIEW.md](OVERVIEW.md)
> ใช้ได้กับกิจกรรมของ **ทุกฝ่าย** ไม่ใช่ของฝ่ายกิจการนักเรียนฝ่ายเดียว
> วันที่สแกน + เขียนสเปก: 2026-07-31

---

## 1. Reality Check — สถานการณ์ของเมนูนี้ต่างจากเมนูอื่นทั้งหมด

**Backend เขียนไว้เกือบครบแล้ว แต่ไม่เคยถูกรันจริงเลยสักครั้ง**

```
activity_sessions     = 0 แถว
activity_enrollments  = 0 แถว
activity_attendances  = 0 แถว
school_events         = 3 แถว (ceremony 1, meeting 1, sports 1)
```

(ตัวเลขจาก DB เครื่องนี้ 2026-07-31 · เทียบกับ `school_attendances` = 1 แถว / records = 2 ซึ่งก็แทบไม่ได้ใช้)

→ **ต้องนับโค้ดชุดนี้เป็น "เขียนแล้วแต่ยังไม่พิสูจน์" ไม่ใช่ "ใช้งานได้"** · งานแรกของเมนูนี้จึงเป็น **การพิสูจน์ว่ามันทำงาน** ไม่ใช่การเขียนเพิ่ม

### 1.1 ของที่มีอยู่แล้ว (ตรวจแล้ว)

| ชั้น | ไฟล์ | สถานะ |
|---|---|---|
| Schema | `database/migrations/2026_02_13_062643_create_school_activity_tables.php` — `activity_enrollments` / `activity_sessions` / `activity_attendances` | ✅ ครบ |
| Schema | `database/migrations/2026_06_24_120100_add_activity_taxonomy_to_school_events.php` — เพิ่ม `attendance_pattern`, `group_id` ให้ `school_events` และ `qr_token`, `slot_label` ให้ `activity_sessions` | ✅ ครบ |
| Model | `app/Models/SchoolEvent.php` (305 บรรทัด) — 14 event type + **3 attendance pattern** (`one_time` / `semester` / `recurring`, L107-124) + `DEFAULT_PATTERN_BY_TYPE` | ✅ ครบ |
| Model | `ActivityEnrollment.php`, `ActivitySession.php`, `ActivityAttendance.php` | ✅ ครบ |
| Controller | `app/Http/Controllers/Api/Learn/Academy/ActivitySessionController.php` (287 บรรทัด) — `show`, `refreshQr`, `checkIn`, `scanStudent`, `storeRecords`, `attendanceSummary` (รายวัน/สัปดาห์/เดือน/ภาคเรียน) | ✅ ครบ 6 endpoint |
| Controller | `SchoolEventController.php` (872) — `enroll` L710, `markAttendance` L643 (เส้น one_time), `generateSessions()` L781-870 (สร้าง session อัตโนมัติจาก `recurrence_pattern`) | ✅ |
| Trait | `app/Traits/HasQrCheckIn.php` — `generateQrToken()`, `isQrTokenValid()` · ใช้ร่วมกับ `SchoolAttendance` | ✅ ใช้ซ้ำได้ |
| Service | `app/Services/StudentIdentifierResolver.php` — resolve `member_code` (ตัวเลข) หรือ `student_number` → `user_id` | ✅ ใช้ซ้ำได้ |
| Trait | `app/Traits/ManagesEventPermissions.php` — admin ของโรงเรียน **หรือ** `AcademyGroupAdmin` ของ `school_events.group_id` | ✅ ใช้ซ้ำได้ |
| Composable | `ui/composables/useSchoolEvents.ts` — **ผูก endpoint ของ session ครบทั้ง 7 ตัวแล้ว** (L69-108) | ✅ แต่ไม่มีใครเรียก |
| UI | `ui/pages/academies/[name]/admin/events/index.vue` (369) + `EventFormModal.vue` (454) | ✅ สร้าง/ลิสต์กิจกรรมได้ |
| Permission | `events.view` / `events.manage` (`AcademyPermission.php` L174-177) · อยู่ใน `DEPARTMENT_DELEGABLE_FAMILIES` แล้ว | ✅ **ไม่ต้องเพิ่ม key ใหม่** |

### 1.2 แม่แบบที่ต้องลอก

`ui/pages/academies/[name]/admin/school-attendance/[id].vue` (797 บรรทัด) — คอนโซลเช็คชื่อ **4 แท็บ: `qr` / `scan` / `manual` / `records`** (นิยามแท็บที่ L394-399) นี่คือรูปที่พิสูจน์แล้วว่าครูใช้เป็น · หน้า session ของกิจกรรมควรเป็นรูปเดียวกัน

`SchoolAttendanceController::enrichRecordsWithClassroomInfo()` (~L493) — join `AcademyMember → ClassroomStudent → Classroom` แบบ 2 query ได้ `student_number` + `classroom_name` โดยไม่เกิด N+1 · **ลอกมาใช้กับ roster ของกิจกรรม**

---

## 1.5 🔴 ผล A-S0 (2026-08-01) — ตารางว่างเพราะ **ทุกทางเข้าพัง** ไม่ใช่เพราะไม่มีใครใช้

เขียนเทสต์ไล่ endpoint จริงแล้วเจอ **บั๊กที่ใช้งานอยู่บน production 4 ตัว แยกจากกันโดยสิ้นเชิง** ทั้งหมดอยู่ในโค้ดที่ไม่เคยมีเทสต์แตะเลย

| # | บั๊ก | ผลกระทบจริง |
|---|---|---|
| 1 | **`AuditLogService::log()` ถูกเรียกด้วย signature เก่า 17 จาก 33 จุด** — ส่ง string เข้าช่องที่ต้องการ `?Model` → TypeError ทันทีที่บรรทัดทำงาน | **สร้าง/แก้/เผยแพร่/ยกเลิก/ลบ ของ 4 ฟีเจอร์คืน 500**: กิจกรรม (5 จุด) · ประกาศ (5) · แจ้งเหตุฉุกเฉิน (4) · นัดประชุม (3) |
| 2 | `SchoolEventController::register()` อ่าน `registration_required` แต่คอลัมน์คือ **`requires_registration`** (อีก 4 จุดในไฟล์เดียวกันใช้ชื่อถูก) | ได้ `null` เสมอ → `! null` เป็นจริง → **ลงทะเบียนกิจกรรมไม่ได้เลย คืน 400 ทุกครั้ง** |
| 3 | `generateSessions()` เรียก **`Carbon::setTimeFromString()` ซึ่งไม่มีอยู่จริง** (ตัวจริงคือ `setTimeFromTimeString()`) | **สร้างชมรม/ชุมนุมแบบ recurring ไม่ได้เลย** → เส้น semester ทั้งเส้นไม่เคยทำงาน |
| 4 | `attendanceSummary()` — `status` กำกวมเพราะ join สองตารางที่มีคอลัมน์ชื่อเดียวกัน | สรุปการเข้าร่วมคืน 500 |

**ทั้ง 4 ตัวรวมกันอธิบายได้ครบ** ว่าทำไม `school_events` มี 3 แถว และ `activity_sessions` / `activity_enrollments` / `activity_attendances` / `event_registrations` มี **0 แถวทั้งหมด**

**บทเรียน:** บั๊ก 3 ใน 4 ตัว**ไม่เกี่ยวกับการเช็คชื่อเลย** (audit log · ชื่อคอลัมน์ drift · เมธอด Carbon ที่ไม่มี) → ถ้าข้ามขั้น A-S0 ไปสร้าง UI ทับเลย จะไปเจอ 500 ตอนหน้างานจริงโดยหาต้นตอไม่เจอ · และบั๊กที่ 1 ยังปลดล็อกอีก 3 ฟีเจอร์ที่ไม่ได้อยู่ในเมนูนี้

**สถานะหลังแก้:** เทสต์ Activity 10 · Audit 39 · Election 119 ผ่านหมด · **แต่ช่องว่างเชิงโครงสร้าง 2 จุดยังอยู่ (ยืนยันด้วย `route:list`)** — ยังไม่มี route สร้าง/ลิสต์ session และยังไม่มี `GET /{event}/enrollments` → **เส้น semester ยังขับจาก UI ไม่ได้** เพราะไคลเอนต์หา `user_id` มาส่งให้ `records` ไม่ได้

---

## 2. Gap Analysis — ช่องว่างจริง 5 จุด

| ID | Gap | ระดับ |
|---|---|---|
| **A1** | **ไม่มี route สร้าง/ลิสต์ session** — `routes/learn/academy.php:753-758` มีแค่ `show`, `refresh-qr`, `check-in`, `scan`, `records`, `attendance-summary` · **ไม่มี `index`, `store`, `update`, `destroy`** → session เกิดได้ทางเดียวคือ auto จาก `recurrence_pattern` ตอนสร้าง event · **ครูสร้างคาบเฉพาะกิจไม่ได้เลย** | 🔴 |
| **A2** | **ไม่มี `GET /{event}/enrollments`** — `POST /{event}/enroll` มี แต่ดึงรายชื่อผู้ลงทะเบียนไม่ได้ · แต่ `storeRecords` ต้องการ `user_id[]` → **หน้าเช็คชื่อแบบกลุ่มสร้างไม่ได้เพราะ client หา `user_id` ไม่เจอ** | 🔴 |
| **A3** | **ไม่มีหน้า UI เช็คชื่อ session เลย** — `useSchoolEvents.ts` export ฟังก์ชัน session 7 ตัว แต่ผู้เรียกมีแค่ `admin/events/index.vue` ซึ่งไม่ได้เรียกสักตัว (ใช้แค่ filter `attendance_pattern` ที่ L33/71/240) | 🔴 |
| **A4** | **`ui/types/qr.ts` ไม่มีสาขา `CHECKIN:ACTIVITY:`** — backend ยิง `CHECKIN:ACTIVITY:{academy}:{session}:{token}` ที่ `ActivitySessionController.php:55` แต่ `parseQRCode()` special-case ไว้แค่ `CHECKIN:SCHOOL:` (L165-177) → สแกนแล้วตกไปเป็น type `checkin` ทั่วไป **ไม่ไปไหนต่อ** | 🔴 |
| **A5** | ไม่มี endpoint ส่งออกรายงานการเข้าร่วมกิจกรรม (`CourseReportController::exportAttendance` มี แต่เป็นของคอร์ส) | 🟡 |
| **A6** | `activity_attendances.check_in_method` รับค่า `face_id` (migration L61) แต่ไม่มีโค้ดไหนเขียนค่านี้ — enum ที่ไม่มีวันถูกใช้ | 🟢 |
| **A7** | สถานะ `leave` / `activity_leave` ตั้งด้วยมือล้วน ไม่ผูกกับระบบใบลาใด ๆ (`LeaveRequest` มีแต่เป็นของบุคลากร) | 🟢 |

---

## 3. บั๊กที่เจอระหว่างสแกน (คนละเมนู แต่ต้องแก้)

**`ui/pages/academies/[name]/admin/school-attendance/[id].vue:~197` ส่ง `{ student_id, status, remark }` แต่ `SchoolAttendanceController::storeRecords` (L241-251) validate และอ่าน `records.*.remarks`** (เปลี่ยนชื่อโดย migration `2026_06_25_090000_rename_remark_to_remarks_on_school_attendance_records`)

→ **หมายเหตุที่ครูพิมพ์ตอนเช็คชื่อแบบกลุ่มถูกทิ้งเงียบทุกครั้ง ไม่มี error ให้เห็น**

`ActivitySessionController::storeRecords` L136 ใช้ `remarks` ถูกต้องอยู่แล้ว → บั๊กนี้อยู่ฝั่งเมนู #18 เท่านั้น แต่แก้พร้อมกันในเมนูนี้เพราะเป็นบรรทัดเดียวและอยู่ในโดเมนเดียวกัน

---

## 4. ข้อตกลงที่ต้องตัดสินก่อนเริ่ม (ยังไม่ได้ถาม)

| # | ประเด็น | ตัวเลือก |
|---|---|---|
| **A-D1** | ใครเช็คชื่อ | ครูเช็คให้ทีละคน (แบบ #18) · นักเรียนสแกน QR เอง · ครูสแกนบัตรนักเรียน · **ทั้งสามแบบเหมือน #18** |
| **A-D2** | ผู้เข้าร่วมมาจากไหน | ต้องลงทะเบียนก่อน (`activity_enrollments`) · หรือใครมาก็เช็คได้แล้วสร้าง enrollment อัตโนมัติ — **มีผลมากกับ A2** |
| **A-D3** | ขอบเขตของ "ฝ่าย" | `school_events.group_id` มีอยู่แล้ว → กิจกรรมของฝ่ายไหน ฝ่ายนั้นเช็คชื่อได้ · ต้องยืนยันว่าตรงกับที่ต้องการ |
| **A-D4** | กิจกรรมครั้งเดียว vs รายภาคเรียน | `attendance_pattern` แยกไว้แล้ว แต่ **เส้น `one_time` (`event_registrations` + `markAttendance`) กับเส้น `semester` (`activity_*`) เป็นคนละโค้ดกันสิ้นเชิง** → ต้องตัดสินว่าจะทำทั้งสองเส้นหรือรวบเป็นเส้นเดียว |

---

## 5. Implementation Tasks (ร่าง — รอตัดสิน §4 ก่อนล็อก)

| Step | Title | Depends | Status |
|---|---|---|---|
| **A-S0** | **พิสูจน์ว่าของเดิมทำงาน** — สร้าง event ทดสอบ + session + enrollment ผ่าน tinker แล้วยิงครบ 6 endpoint ของ `ActivitySessionController` · **งานนี้ต้องมาก่อนทุกอย่าง** เพราะตาราง 0 แถวแปลว่าไม่มีใครรู้ว่ามันพังตรงไหน | — | ⚪ |
| **A-S1** | **อุด A1** — เพิ่ม route + controller method `index`/`store`/`update`/`destroy` ของ session พร้อม guard `events.manage` | A-S0 | ⚪ |
| **A-S2** | **อุด A2** — `GET /{event}/enrollments` คืนรายชื่อ + `student_number` + `classroom_name` (ลอก `enrichRecordsWithClassroomInfo`) + สถานะเช็คชื่อของ session ที่ระบุ | A-S0 | ⚪ |
| **A-S3** | **อุด A4** — เพิ่มสาขา `CHECKIN:ACTIVITY:` ใน `ui/types/qr.ts` + routing ใน `useQRScanner.ts` (ระวัง: ไฟล์นี้ใช้ร่วมทั้งระบบ) | A-S0 | ⚪ |
| **A-S4** | **หน้าคอนโซลเช็คชื่อกิจกรรม** — ลอกรูป 4 แท็บจาก `school-attendance/[id].vue` · ใช้สกิล `hopeui-port` | A-S1, A-S2 | ⚪ |
| **A-S5** | **แก้บั๊ก `remark` → `remarks`** ของเมนู #18 (§3) + เทสต์กันถอยหลัง | — | ⚪ |
| **A-S6** | รายงาน/ส่งออกการเข้าร่วมกิจกรรม (A5) | A-S4 | ⚪ |

---

## 6. Review Log

- **2026-07-31 — สแกน + เขียนสเปกร่าง** — พบว่าเมนูนี้ **ตรงข้ามกับเมนู #25**: #25 ไม่มีโค้ดเลย ส่วน #26 มีโค้ดเกือบครบแต่ไม่มีข้อมูลเลย (0 แถวทั้ง 3 ตาราง) · ช่องว่างจริงคือ frontend + route 2 กลุ่มที่หายไป ไม่ใช่ business logic · **ไม่ต้องเพิ่ม permission key ใหม่** (`events.*` มีและมอบให้ฝ่ายได้อยู่แล้ว) · ยังไม่ล็อกแผนเพราะ §4 มี 4 ข้อที่ต้องให้เจ้าของโปรเจคตัดสินก่อน

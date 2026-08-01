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

## 1.6 ✅ A-S2 (2026-08-01) — กลุ่มเป้าหมาย + roster แทน `GET /enrollments` ที่ไม่เคยมี

**ข้อตกลงที่ล็อก (เจ้าของโปรเจคตัดสิน):** ผู้เข้าร่วมมาจาก**การกำหนดกลุ่มเป้าหมายบนกิจกรรม ไม่ใช่การลงทะเบียนรายคน** · **คนนอกกลุ่มเป้าหมายเช็คชื่อไม่ได้เลย ปฏิเสธไป** → รายชื่อกลุ่มเป้าหมายเป็นตัวตัดสินสิทธิ์จริง

`school_events.target_audience` (คอลัมน์ที่มีอยู่แต่**ตายสนิท** — `SchoolEventController` ไม่เคยอ่านหรือ validate และทั้ง 3 กิจกรรมเป็น null) ได้โครงจริงแล้ว:

```json
{ "all": false, "roles": ["student","staff"], "education_levels": [1,2],
  "grade_levels": ["ม.1"], "classroom_ids": [12], "group_ids": [7],
  "user_ids": [1234], "exclude_user_ids": [5678] }
```

ทุกมิติรวมแบบ **union** แล้วลบ `exclude_user_ids` ทีหลัง · `all: true` ข้ามมิติอื่น · **null = ของเดิม ปฏิเสธใครไม่ได้**

**ตรวจกับข้อมูลจริงทั้งโรงเรียน:** ห้อง ม.4/8 · ม.4/7 · ม.3/7 คืน 59 · 59 · 52 ตรงเป๊ะ · ประถม 449 · นักเรียน 2,930 · บุคลากร 131 · ทั้งหมด 3,058 · union (ห้อง+ม.6) 291 ไม่ซ้ำซ้อน

> ⚠️ **ห้องเรียนต้องนับเฉพาะ `status = active`** — ม.1/1 มี 42 แถวแต่เป็น `promoted` จากปี 2568 ทั้งหมด จึงมี 0 คนจริง ถ้านับทุกสถานะจะได้กลุ่มเป้าหมายผิดมหาศาล

**endpoint ใหม่:** `GET /{event}/roster` (แทน `GET /enrollments` ที่ไม่เคยมี — รายชื่อมาจากกลุ่มเป้าหมายโดยตรง) · `GET /{event}/audience-count`

**⚠️ ช่องว่างความครอบคลุมที่ต้องปิด:** การปฏิเสธคนนอกกลุ่มมีอยู่ครบทั้ง **4 ทางเข้า** ในโค้ด (`checkIn` · `scanStudent` · `storeRecords` · `markAttendance` — ตรวจแล้ว) **แต่เทสต์ครอบแค่ `checkIn`** → อีก 3 ทางไม่มีอะไรกันถ้าใครลบทิ้ง

**สถานะเทสต์:** Activity 23 · Audit 39 · Election 119

---

## 1.7 ✅ A-S1 (2026-08-01) — สร้าง/ลิสต์/แก้/ลบคาบได้แล้ว + ปิดรูข้ามโรงเรียน

**อุด A1 แล้ว:** `GET|POST /{event}/sessions` · `PATCH|DELETE /{event}/sessions/{session}` (ยืนยันด้วย `route:list --path=sessions`) → ครูสร้างคาบชดเชย/คาบเฉพาะกิจได้แล้ว ไม่ต้องรอ auto-generate จาก `recurrence_pattern` อย่างเดียว

### 🔴 รูที่เจอระหว่างทำ — admin โรงเรียนอื่นเขียนข้อมูลข้ามโรงเรียนได้

`ActivitySessionController` **ทั้ง 6 เมธอดเดิม** ผูก `SchoolEvent` ด้วย route-model binding แล้ว**ไม่เคยตรวจว่า event เป็นของ `$academy` ในลิงก์** · `authorizeSession()` ตรวจแค่ `session->event_id === event->id` ส่วน `authorizeManager()` ถาม `canManageEvent($user, $academy, ...)` ซึ่งถามว่า *"คนนี้เป็นแอดมินของโรงเรียนที่อยู่ใน URL ไหม"*

→ เจ้าของโรงเรียน B ยิง `/academies/{B}/events/{eventของโรงเรียน A}/sessions/{s}/records` **ผ่านทั้งสองด่าน** แล้วเขียนการเช็คชื่อลงกิจกรรมของโรงเรียน A ได้

**พิสูจน์แล้วด้วย revert-check:** ถอด `abort_if` ออกบรรทัดเดียว → คำขอวิ่งทะลุถึงชั้น validation ของ `storeRecords` (ตอบ "The records field is required") แทนที่จะเป็น 404

`SchoolEventController::roster()` (`:699`) และ `destroy()` (`:739`) ตรวจถูกอยู่แล้ว → **นี่คือ drift ไม่ใช่การออกแบบ** · แก้ด้วย helper `authorizeEvent()` ใช้ทุกเมธอดในคอนโทรลเลอร์ (10 จุด) + เทสต์ล็อกไว้

### ⚠️ `events.manage` เป็นสิทธิ์ที่ประกาศไว้แต่ไม่มีใครอ่าน (ยังไม่แก้ — รอตัดสิน)

สเปกเดิมเขียนว่า A-S1 ต้อง "guard `events.manage`" แต่ตรวจแล้ว **ไม่มี middleware หรือคอนโทรลเลอร์ตัวไหนอ่านคีย์นี้เลย** — `events.view`/`events.manage` มีใน `AcademyPermission.php:175-176` และแจกให้ 6 role ใน `AcademyRole.php` แต่ทั้ง 13 endpoint ของกิจกรรมกรองด้วย `ManagesEventPermissions::canManageEvent()` = **แอดมินโรงเรียน หรือ หัวหน้าฝ่ายเจ้าของ `group_id`** เท่านั้น

→ route ใหม่ 4 ตัวจึงใช้ `canManageEvent` ให้เหมือนอีก 6 ตัว (ใส่ middleware เฉพาะตัวใหม่ = ไม่ปลดล็อกใครเพิ่ม แถมทำให้ 2 มาตรฐานอยู่ในบล็อกเดียวกัน)
→ **ข้อตัดสินที่ค้าง:** จะให้ `canManageEvent` นับ `events.manage` ด้วยไหม — ถ้าเอา **สิทธิ์เปลี่ยนพร้อมกันทั้ง 13 endpoint** ไม่ใช่เฉพาะ A-S1

**สถานะเทสต์:** Activity 35 (เพิ่ม `ActivitySessionCrudTest` 12 เคส) · รวมชุดที่ filter `Activity` จับได้ 57 ผ่าน

---

## 1.8 ✅ A-S3 (2026-08-01) — สแกน QR กิจกรรมแล้วไปถูกที่แล้ว + **แต่เจอตอปิดทางอยู่ข้างหน้า**

**อุด A4 แล้ว** — เพิ่มสาขา `CHECKIN:ACTIVITY:` ใน `parseQRCode()` + handler `handleActivityCheckinQR` ใน `useQRScanner.ts` · เทสต์ vitest 7 เคสใหม่ที่ `ui/tests/qr.spec.ts`

**พิสูจน์ว่า A4 มีจริงด้วย revert-check:** ปิดสาขาใหม่ทิ้งชั่วคราว → `CHECKIN:ACTIVITY:7:12:34:tok` คืน type `checkin` (ไม่ใช่ `activity_checkin`) แล้ว `handleCheckinQR` จะยิง `/api/classes/checkin` ด้วย `class_id = 'ACTIVITY'` · และ payload 3 ช่องรายงานว่า valid ทั้งที่ไม่พอใช้

### 🔴 payload เดิมขาด `event_id` — แก้ฝั่ง backend ด้วย (นอกขอบเขตร่างเดิม แต่ไม่แก้ = ทำ A-S3 ไม่ได้)

route เช็คอินต้องการ **3 id**: `POST /academies/{academy}/events/{event}/sessions/{session}/check-in`
แต่ QR เดิมพก 2: `CHECKIN:ACTIVITY:{academy}:{session}:{token}` → **ประกอบ URL ไม่ได้เลย**

(`CHECKIN:SCHOOL:` รอดมาได้เพราะปลายทางใช้แค่ 2 id ไม่ใช่ 3)

→ เปลี่ยนเป็น `CHECKIN:ACTIVITY:{academy}:{event}:{session}:{token}` เรียงตาม segment ของ URL ซ้ายไปขวา
→ **ปลอดภัยเพราะ `activity_sessions` = 0 แถว ไม่เคยมี QR ใบไหนถูกออกหรือพิมพ์** · grep ยืนยันไม่มีที่อื่นในโปรเจคอ่าน/สร้างสตริง `CHECKIN:ACTIVITY:`

### 🔴 A8 (ใหม่) — สแกนแล้วก็ยังเช็คชื่อไม่ได้อยู่ดี เพราะ `checkIn()` ยังบังคับให้มีแถวลงทะเบียน

`ActivitySessionController::checkIn()` ผ่าน `assertInAudience()` แล้ว **ยังไปต่อที่ `resolveEnrollment()` และตอบ 422 "คุณไม่ได้ลงทะเบียนกิจกรรมนี้" ถ้าไม่มีแถวใน `activity_enrollments`** (เส้น `scanStudent` กับ `storeRecords` เหมือนกัน)

**ขัดกับข้อตกลงที่ล็อกไว้ใน §1.6 ตรง ๆ** ("ผู้เข้าร่วมมาจากการกำหนดกลุ่มเป้าหมาย ไม่ใช่การลงทะเบียนรายคน") · ที่เทสต์ผ่านหมดเพราะ **ทุกเคสสร้างแถว `ActivityEnrollment` ให้เองก่อน** (ดู `EventAudienceTest::sessionFixture` + 3 เคสรอบ ๆ)

ของจริง: `activity_enrollments` = **0 แถว** และ**ไม่มี UI ไหนสร้างได้** → นักเรียนที่อยู่ในกลุ่มเป้าหมายสแกนแล้วโดนปฏิเสธ 100%

⚠️ ลบเงื่อนไขทิ้งเฉย ๆ ไม่ได้ — `activity_attendances.enrollment_id` เป็น FK **NOT NULL** ต้องมีแถวลงทะเบียนจริงถึงบันทึกการเช็คชื่อได้

**ตัดสินแล้ว (A-D2 รอบสอง, เจ้าของโปรเจค):** **สร้าง enrollment อัตโนมัติตอนเช็คชื่อครั้งแรก** → ทำใน A-S3b ดู §1.9

**สถานะเทสต์:** backend Activity 35 (filter จับได้ 57 ผ่าน) · frontend vitest 7 ผ่าน

---

## 1.9 ✅ A-S3b (2026-08-01) — อุด A8: อยู่ในกลุ่มเป้าหมาย = เช็คชื่อได้เลย ไม่ต้องลงทะเบียนก่อน

`App\Services\Activity\ActivityEnrollmentResolver` เป็นทางเข้าเดียวของทั้ง 3 เส้น (`checkIn` / `scanStudent` / `storeRecords`) · เจอแถวเดิม → ใช้เลย (พฤติกรรมเดิมไม่เปลี่ยน) · ไม่เจอ → สร้างให้ถ้าเป็นสมาชิกที่อนุมัติแล้ว

### 3 ตอที่ตรวจกับ DB จริงก่อนออกแบบ — ถ้าทำตรง ๆ พังทั้งหมด

**ตอ 1 — unique index ที่ไม่ป้องกันอะไรเลย:** `enrollment_unique` คลุม `[user_id, event_id, semester, academic_year]` แต่ 2 คอลัมน์หลังเป็น `varchar(20) NULL` · MySQL ไม่ถือว่า NULL ซ้ำ → เขียน null = สองคนสแกนพร้อมกันได้ 2 แถว (**ตอเดียวกับ `election_results`**)
→ แถวอัตโนมัติเขียนค่าไม่เป็น null เสมอ + `firstOrCreate` คีย์ครบ 4 คอลัมน์ + catch `UniqueConstraintViolationException` ให้คนแพ้ race ได้แถวของคนชนะ

**ตอ 2 — กิจกรรมทั้ง 3 ตัวมี `target_audience = null`:** `assertInAudience()` return ทันทีเมื่อ audience เป็น null (ข้อตกลง §1.6) → พอเอาเงื่อนไข enrollment ออก **ใครก็ได้ที่ล็อกอินอยู่และเห็น QR บนโปรเจกเตอร์ ได้แถวลงทะเบียนในกิจกรรมของโรงเรียนอื่น** — เงื่อนไข enrollment เป็นด่านเดียวที่กันอยู่โดยบังเอิญมาตลอด
→ **auto-create ต้องเป็นสมาชิกที่อนุมัติแล้ว** (`EventAudienceResolver::isMember()`) · **ไม่แตะ `assertInAudience` ไม่แตะเส้นที่มี enrollment อยู่แล้ว** → §1.6 ไม่เปลี่ยน และ `test_null_audience_refuses_nobody` ผ่านโดยไม่ถูกแก้
→ **revert-check ยืนยัน:** ปิดด่านสมาชิกทิ้ง → คนนอกได้ **200 + แถวลงทะเบียน** และเส้น bulk ก็เงียบ ๆ รับเข้าไปด้วย

**ตอ 3 — เทอมมีให้ครึ่งเดียว:** academy 1 มี `AcademicYear` ปัจจุบัน `2569` แต่ **`Semester` 0 แถว** → `semester` fallback เป็น `'-'` (ไม่ระบุภาคเรียน) · `academic_year` fallback เป็นปี พ.ศ. ปัจจุบัน

### บั๊กแฝงที่แก้ไปพร้อมกัน

- `upsertAttendance()` ใช้ `updateOrCreate` คีย์ `(session_id, enrollment_id)` แต่ unique index จริงคือ **`(session_id, user_id)`** → ถ้าใครมี enrollment 2 แถว มันจะ INSERT ชน unique เป็น QueryException · auto-create ทำให้เข้าถึงง่ายขึ้น → เปลี่ยนคีย์ให้ตรง index
- `storeRecords()` เดิม `continue` เงียบ ๆ (data loss แบบเดียวกับ `remark`/`remarks`) → คืน `skipped_user_ids` ให้ UI เอาไปแสดง
- `SchoolEventController::enroll()` ฝังค่า `'1'`/`'2024'` ไว้ → ใช้ `currentTerm()` ตัวเดียวกัน ไม่งั้นลงทะเบียนมือกับอัตโนมัติไปคนละบัคเก็ตแล้วได้ 2 แถวต่อคน
- **แถวที่ `status = 'dropped'` ใช้คอลัมน์เดียวกับ unique index** → `firstOrCreate` จะคืนแถวที่ถูกถอนออกมาให้ แล้วเช็คชื่อทับ · ตอนนี้ยังไม่มีโค้ดไหนเขียน `'dropped'` แต่ปิดไว้ก่อน: คืน null (ปฏิเสธ) ไม่ใช่คืนสิทธิ์ให้เงียบ ๆ

**สถานะเทสต์:** Activity 44 (`ActivityAutoEnrollmentTest` 9 เคส) · filter จับได้ **66 ผ่าน (146 assertions)**

⚠️ **A-S4 ปลดล็อกแล้ว** — เส้นเช็คชื่อทั้ง 3 ทางใช้งานได้จริงตั้งแต่ต้นจนจบโดยไม่ต้องป้อนข้อมูลอะไรล่วงหน้า

---

## 1.10 ✅ A-S4 (2026-08-01) — หน้าคอนโซลเช็คชื่อกิจกรรม (อุด A3)

2 หน้าใหม่ + 1 component + 6 ฟังก์ชันใน `useSchoolEvents.ts`:

| ไฟล์ | หน้าที่ |
|---|---|
| `admin/events/[id]/sessions/index.vue` | รายการคาบ + สร้าง/แก้ไข/ลบ (ใช้ route ของ A-S1) |
| `admin/events/[id]/sessions/[sessionId].vue` | คอนโซล 4 แท็บ `qr` / `scan` / `manual` / `records` |
| `components/academy/activity/ActivitySessionQRDisplay.vue` | QR + ขยายเต็มจอ (โทนม่วงให้ตรงกับ `activity_checkin`) |

### ตัดสินเรื่องแหล่งดีไซน์ — ไม่ได้ดึงจาก HopeUI ทั้งที่ `hopa/` มีอยู่

ต้นแบบคือ **`school-attendance/[id].vue` (797 บรรทัด)** ซึ่งเป็นคอนโซล 4 แท็บที่ครูใช้เป็นแล้วและใช้โทเคนของ nuxnan ครบ (`rounded-vikinger`, `shadow-card`, `bg-gradient-vikinger`, `font-heading`) ส่วนหน้ารายการคาบลอกทรงการ์ดจาก `admin/events/index.vue` โฟลเดอร์เดียวกัน
→ quality bar ของสกิลคือ "ให้ดูเหมือนเป็นของ nuxnan" · ทั้งสองทรงมีในบ้านแล้ว การเอาแหล่งที่สามเข้ามาจะได้ผลตรงข้าม · ใช้ภาษาไทยฝังตรงตามหน้าพี่น้อง (`$t()` 0 ครั้ง) ไม่เพิ่มคีย์ i18n ให้หน้าเดียวแล้วไม่เข้าพวก

### จุดที่ต้องดัดจากต้นแบบ (ไม่ใช่ลอกตรง ๆ)

- **ไม่มี open/closed** — คาบกิจกรรมเป็น `scheduled`/`completed`/`cancelled` → ปุ่ม "ปิดการเช็คชื่อ" กลายเป็น "จบคาบนี้" ที่ PATCH สถานะ
- **`summary.total` = จำนวนแถวเช็คชื่อ ไม่ใช่จำนวนคน** → การ์ด "กลุ่มเป้าหมาย" ดึงจาก `audience-count` แทน ไม่งั้นเปอร์เซ็นต์ผิดหมด · และ `summary` ไม่มีคีย์ `leave` → 4 การ์ด ไม่ใช่ 5
- **ไม่มี endpoint คืนรายชื่อผู้เช็คชื่อ** → แท็บ `records` ใช้ roster แล้วกรองแถวที่ `attendance_status` ไม่ null (ไม่ต้องเพิ่ม backend)
- **`already_checked` มากับ HTTP 422** ซึ่ง `api.call` โยน exception → ต้องอ่านจาก `catch` ไม่งั้นเช็คซ้ำจะขึ้นแดงเป็น error
- สถานะ 5 ตัว (เพิ่ม `activity_leave` โทนม่วง) · roster ส่ง `user_id`/`remarks` **ไม่ใช่ `student_id`/`remark`** ตามบั๊กของเมนู #18 (§3)
- แสดง `skipped_user_ids` ที่ A-S3b เพิ่มเข้ามา + เตือนเมื่อ roster เกิน 200 แถว

### ปุ่ม "จัดการ" ที่ชี้ไปหน้าที่ไม่มีอยู่จริง

`admin/events/index.vue:348` ชี้ไป `/admin/events/{id}` ซึ่ง **ไม่มีไฟล์นั้นในโปรเจค — 404 มาตลอด** (ทั้งที่ commit `b823a396` ชื่อว่า "repair every broken path")
→ แก้เฉพาะกิจกรรม `semester`/`recurring` ให้ไปหน้ารายการคาบ (ป้ายเปลี่ยนเป็น "คาบเช็คชื่อ") · **`one_time` ยังพังอยู่ เป็นคนละงาน**

### ⚠️ บทเรียนเครื่องมือ — Codex ทำ regression บนไฟล์ที่สั่งห้ามแตะ

สั่งให้ **copy** `SchoolAttendanceQRDisplay.vue` ไปเป็นตัวใหม่ แต่ Codex **ย้าย**ของดีไปไฟล์ใหม่แล้วทิ้ง**เวอร์ชัน minified ที่ตัดฟีเจอร์ออก**ไว้แทนที่ของเดิม — ไอคอนย่อ-ขยายเต็มจอ, ป้าย "Session เปิดอยู่", เงา/สถานะ disabled หายหมด **บนหน้าที่ใช้งานจริงอยู่**
→ กู้ด้วย `git checkout --` ยืนยัน **md5 ตรงกับ HEAD เป๊ะ**
→ และหน้าใหม่ 2 หน้าที่ส่งมาเป็น **one-liner ยาวบรรทัดเดียว** ขาด UI ตามสเปกเกินครึ่ง (ไม่มีการ์ดผลสแกน, ไม่มีประวัติสแกนล่าสุด, ไม่มี sticky save bar, ไม่มี empty state, ไม่มี not-found) → **claude เขียนใหม่ทั้ง 2 หน้าเอง**
→ ที่เก็บของ Codex ไว้: `useSchoolEvents.ts` (6 ฟังก์ชัน ถูกต้อง ฟอร์แมตดี) และ `ActivitySessionQRDisplay.vue` (แก้สี teal ที่ตกค้าง 3 จุด + copy 2 บรรทัดเอง)

**ตรวจแล้ว:** `vue-tsc --noEmit` — **0 error ในไฟล์ใหม่** (เหลือ 5 error เดิมใน `public/js/TimelineMax.js` ซึ่งเป็น vendor minified ไม่เกี่ยวกัน) · `vitest` 15 ผ่าน · **ไม่ได้รัน `npm run build`** (เจ้าของโปรเจครันเอง)

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
| **A-S0** | **พิสูจน์ว่าของเดิมทำงาน** — สร้าง event ทดสอบ + session + enrollment ผ่าน tinker แล้วยิงครบ 6 endpoint ของ `ActivitySessionController` · **งานนี้ต้องมาก่อนทุกอย่าง** เพราะตาราง 0 แถวแปลว่าไม่มีใครรู้ว่ามันพังตรงไหน | — | ✅ §1.5 |
| **A-S1** | **อุด A1** — เพิ่ม route + controller method `index`/`store`/`update`/`destroy` ของ session (guard = `canManageEvent` ไม่ใช่ `events.manage` ดูเหตุผลใน §1.7) | A-S0 | ✅ §1.7 |
| **A-S2** | **อุด A2** — รายชื่อผู้เข้าร่วมมาจากกลุ่มเป้าหมาย ผ่าน `GET /{event}/roster` (ไม่ใช่ `GET /enrollments` ตามร่างเดิม) | A-S0 | ✅ §1.6 |
| **A-S3** | **อุด A4** — เพิ่มสาขา `CHECKIN:ACTIVITY:` ใน `ui/types/qr.ts` + routing ใน `useQRScanner.ts` (+ เพิ่ม `event_id` ใน payload ฝั่ง backend) | A-S0 | ✅ §1.8 |
| **A-S3b** | **อุด A8** — สร้าง enrollment อัตโนมัติให้สมาชิกในกลุ่มเป้าหมายตอนเช็คชื่อครั้งแรก (`ActivityEnrollmentResolver`) | A-S3 | ✅ §1.9 |
| **A-S4** | **หน้าคอนโซลเช็คชื่อกิจกรรม** — 4 แท็บ + หน้ารายการคาบ (ลอกจาก `school-attendance/[id].vue` ไม่ได้ใช้ HopeUI ดูเหตุผล §1.10) | A-S1, A-S2, A-S3b | ✅ §1.10 |
| **A-S5** | **แก้บั๊ก `remark` → `remarks`** ของเมนู #18 (§3) + เทสต์กันถอยหลัง | — | ⚪ |
| **A-S6** | รายงาน/ส่งออกการเข้าร่วมกิจกรรม (A5) | A-S4 | ⚪ |

---

## 6. Review Log

- **2026-07-31 — สแกน + เขียนสเปกร่าง** — พบว่าเมนูนี้ **ตรงข้ามกับเมนู #25**: #25 ไม่มีโค้ดเลย ส่วน #26 มีโค้ดเกือบครบแต่ไม่มีข้อมูลเลย (0 แถวทั้ง 3 ตาราง) · ช่องว่างจริงคือ frontend + route 2 กลุ่มที่หายไป ไม่ใช่ business logic · **ไม่ต้องเพิ่ม permission key ใหม่** (`events.*` มีและมอบให้ฝ่ายได้อยู่แล้ว) · ยังไม่ล็อกแผนเพราะ §4 มี 4 ข้อที่ต้องให้เจ้าของโปรเจคตัดสินก่อน

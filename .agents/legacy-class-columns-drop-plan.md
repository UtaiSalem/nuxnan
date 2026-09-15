# แผนเลิกใช้คอลัมน์ชั้นเรียน legacy (phase 6) — `class_level` / `class_section` / `level_and_room`

> เขียน 2026-09-15 · ที่มา: เมนู #10 ห้องเรียน CL-S7 (deferred) + memory [[project-classroom-source-of-truth]]
> **นี่คือแผน ยังไม่ลงมือ** — เจ้าของโปรเจคเลือก "เขียนแผนแบ่งเฟสก่อน" · ต้องอนุมัติทีละเฟส
> Claude วางแผน/ตรวจ · agy ลงมือ · ทุก DB change = migration + `down()` (ดู [[feedback-db-changes-as-migration]])

---

## 0. เป้าหมาย & ทำไมต้องแบ่งเฟส

ลบคอลัมน์ denormalized ที่เลื่อนออกจากแหล่งจริง:
- `students.class_level`, `students.class_section`
- `student_cards.class_level`, `student_cards.class_section`, `student_cards.level_and_room`

**แหล่งความจริง (source of truth) ที่ตั้งไว้แล้วตั้งแต่ phase 2–5:**
`classroom_students` (active) → `classrooms.grade_level` / `classrooms.section`

🔴 **ทำไมลบทันทีไม่ได้:** สแกน 2026-09-15 พบ **~263 จุด / ~56 ไฟล์** (backend ~182/27 · frontend ~81/29)
คอลัมน์ยัง **load-bearing** (ไม่ใช่ซาก) — ถ้าลบดื้อ ๆ พังทั้ง members/บัตรนักเรียน/โปรไฟล์/เยี่ยมบ้าน/gradebook

## 1. 🔑 กุญแจที่ทำให้งานเป็นไปได้ — แยกประเภทการใช้งาน

ไม่ใช่ทั้ง 263 จุดที่ต้องแก้ · แยกได้ 4 กลุ่ม (นับคร่าว จากสแกน 2026-09-15):

| กลุ่ม | ~จำนวน (BE) | ชะตา |
|---|---|---|
| **A. output key** `'class_level' => ...` ในอาร์เรย์ response | ~53 | **เก็บ** — เป็นชื่อ field ใน API contract · ค่าเปลี่ยนไปดึงจาก source เอง |
| **B. SQL alias** `classrooms.grade_level as class_level` | ~4 | **เก็บ** — ดึงจาก source อยู่แล้ว แค่ตั้งชื่อ alias เป็นชื่อเดิม |
| **C. attribute read** `$student->class_level` / `$card->class_level` | ~91 | **แก้ด้วย accessor** (ดู §3 Phase B) — ไม่ต้องแตะทีละจุด |
| **D. คอลัมน์จริง**: `->where/orderBy/pluck/select('class_level')` บน `students`/`student_cards` + `$fillable` + จุดที่ **เขียน** ค่า | (ที่เหลือ) | **ต้อง reroute/ลบ** (ดู Phase C/D) — พวกนี้คือตัวบล็อกการ drop จริง |

⇒ **กลุ่ม C (attribute read) = ก้อนใหญ่สุด แก้ได้ด้วย accessor จุดเดียวต่อ model** ไม่ใช่ 91 จุด
⇒ เหลือกลุ่ม D (SQL + write) เท่านั้นที่ต้องไล่แก้จริง — ก้อนเล็กกว่ามาก

## 2. ของที่มีอยู่แล้ว (ยืนยัน 2026-09-15)
- `Student::currentEnrollment()` = `hasOne(ClassroomStudent)->where('status','active')` (app/Models/Student.php:69)
  → `$student->currentEnrollment?->classroom?->grade_level` / `?->section` คือค่าแทน
- `Student::classroomEnrollments()` (61) · `class_level`/`class_section` อยู่ใน `$fillable` (มีการเขียน)
- `student_cards.classroom_id` (FK → classrooms, เพิ่มตอน phase 2) → `StudentCard::classroom` เป็นแหล่งของบัตร
  ⚠️ **Phase A ต้อง verify** ว่า StudentCard มี relation `classroom()` จริง + ทุกใบมี classroom_id (backfill ครบ?)

---

## 3. เฟสงาน (อนุมัติ + verify ทีละเฟส · หยุดถ้าเฟสไหนไม่ผ่าน)

### Phase A — Audit + categorize ครบทุกจุด (Claude, ไม่แตะโค้ด)
- ไล่ทั้ง 263 จุด (BE+FE) แปะป้าย A/B/C/D ตาม §1 · ผลลัพธ์เป็นตารางในไฟล์นี้
- โฟกัสหากลุ่ม D ให้ครบ (SQL where/orderBy/pluck/select + จุดเขียนค่า + fillable + console)
- ยืนยัน source สำหรับ **นักเรียนที่ไม่มี active enrollment** (จบ/พัก/ย้ายออก) → accessor คืน null?
  ต้องเคาะ: โชว์ว่าง, หรือ fallback ค่าสุดท้าย, หรือดึงจาก enrollment ล่าสุด (ไม่ว่าสถานะ)
- **Deliverable:** ตารางจุด + รายการกลุ่ม D ที่ต้อง reroute จริง + คำถามที่ต้องเคาะ

### Phase B — เพิ่ม accessor (ครอบกลุ่ม C)
- `Student`: `getClassLevelAttribute($value)` / `getClassSectionAttribute($value)`
  → `return $this->currentEnrollment?->classroom?->grade_level ?? $value;` (enrollment-first, fallback คอลัมน์เดิม **ระหว่างยังไม่ drop**)
- `StudentCard`: accessor เดียวกันจาก `$this->classroom?->grade_level/section` + ประกอบ `level_and_room`
- 🔴 **กับดัก Laravel:** accessor ที่ชื่อตรงกับคอลัมน์จริงจะ **override การอ่าน** ทันที ⇒ พอเพิ่ม accessor
  การอ่านทุกจุดจะเป็น "enrollment-first" เลย (เผยจุดที่คอลัมน์ drift) · ต้อง **eager-load `currentEnrollment.classroom`**
  ทุก query หลักกัน N+1 · และเทสต์ว่า resource/หน้าเดิมยังได้ค่าถูก
- ⚠️ accessor ไม่มีผลกับ `->select('class_level')` / raw SQL — พวกนั้นอยู่กลุ่ม D
- **Verify:** เทสต์ resource + หน้าที่โชว์ชั้นเรียน (members/บัตร/โปรไฟล์) ค่ายังถูก · ไม่มี N+1

### Phase C — reroute กลุ่ม D ที่เป็น SQL (where/orderBy/pluck/select)
- เช่น `getAllStudents` `->orderBy('class_level')->orderBy('class_section')` → JOIN/subquery จาก classrooms
  (ระวัง: `classrooms.section` เป็น varchar → sort ต้อง cast (int) · ห้าม REGEXP_REPLACE/MySQL-only กัน SQLite เทสต์พัง — [[project-tests-sqlite-vs-mysql]])
- AcademyMemberController fallback (`whereNotNull('class_level')...pluck`) → ใช้ classrooms JOIN แทน (มี alias อยู่แล้ว เอา fallback ออก)
- **Verify:** เทสต์ endpoint ที่จัดเรียง/กรองด้วยชั้นเรียน (รายชื่อนักเรียน, filter members) ผลถูก

### Phase D — จัดการ "จุดเขียนค่า" + fillable
- หาจุดที่ **set** `students.class_level`/`class_section` (import, enrollment, rollover) → เลิกเขียน
  (แหล่งจริงคือ classroom_students แล้ว) · เอา 2 คีย์ออกจาก `$fillable`
- StudentCard เช่นเดียวกัน (`class_level/class_section/level_and_room` write paths)
- **Verify:** import นักเรียน + enroll + rollover ยังทำงาน (เทสต์เดิม + เพิ่มถ้าจำเป็น)

### Phase E — migration drop columns
- 1 migration ต่อ 1 table หรือรวม · `down()` ต้อง add คอลัมน์คืน **พร้อม backfill จาก classrooms** (ไม่ใช่ nullable เปล่า)
- 🔴 guard STRICT mode · ห้าม MySQL-only syntax · ทดสอบ `migrate` + `migrate:rollback` บน dev
- **ก่อนรัน:** `mysqldump` students + student_cards เก็บไว้ · ตรวจ `migrate:status`
- **Verify:** [[project-tests-sqlite-vs-mysql]] — เทสต์เขียวบน SQLite ไม่พอ ต้องรัน migration บน MySQL dev จริง

### Phase F — Frontend (~81 จุด / 29 ไฟล์)
- ส่วนใหญ่โชว์ค่าจาก API — ถ้า Phase A–E คง **output key เดิม** (`class_level` ใน response) FE ไม่ต้องแก้
- ไล่เฉพาะจุดที่ FE **สร้าง/ส่งค่า** class_level เอง หรือ type definition ที่ต้องปรับ (`ui/types/enrollment.ts`, `academy.ts`)
- **Verify:** หน้า members/บัตร/โปรไฟล์/เยี่ยมบ้าน ที่ 375px แสดงชั้นเรียนถูก

### Phase G — cleanup console commands
- `RebuildClassroomsFromStudents` (guard prod แล้ว CL-S2) · `EnrollmentRepairDirtyData` · `ReconcileStudentCards` ·
  `SeedLegacyCardRequests` · `CleanupLegacyStudentPhotos` — ตัวไหนพึ่งคอลัมน์ที่ลบ → แก้หรือ retire

---

## 4. Risk register
- **R1 data drift:** คอลัมน์เดิม ≠ currentEnrollment (คือเหตุผลที่ต้องลบ) · หลัง accessor ค่าจะมาจาก enrollment = ถูก แต่ **บางหน้าจะเปลี่ยนค่าที่โชว์** → ต้องแจ้ง/ยอมรับ
- **R2 นักเรียนไม่มี active enrollment:** accessor คืน null → ต้องเคาะ fallback (Phase A)
- **R3 N+1:** accessor ดึง currentEnrollment.classroom ต่อ row → ต้อง eager-load ทุก listing
- **R4 down() ต้อง backfill:** rollback ต้องได้ค่ากลับจริง ไม่ใช่คอลัมน์ว่าง
- **R5 SQLite vs MySQL:** เทสต์เขียวไม่พิสูจน์ migration รันบน MySQL — ต้องรันจริง
- **R6 API contract:** ถ้าเผลอเอา output key `class_level` ออก → FE + client ภายนอกพัง · **ต้องคงชื่อ field**

## 4.5 ผล Phase A — categorize จริง (Claude, 2026-09-15) ✅

### 🔴 ค้นพบสำคัญ: `students.*` กับ `student_cards.*` **ความหมายต่างกัน** — ต้องแยกงาน
- `students.class_level/class_section` = denormalize ของ enrollment สด ⇒ derive จาก currentEnrollment ได้ตรง ๆ (accessor)
- `student_cards.class_level/class_section/level_and_room` = **snapshot ตอนออกบัตร** (StudentCardRequestService:220-222
  เขียนจาก `grade_level_snapshot`/`section_snapshot`) · StudentCardResource:40-42 อ่าน enrollment-first **แล้ว fallback snapshot**
  ⇒ **ถ้า drop คอลัมน์บัตร บัตรที่พิมพ์แล้วจะเปลี่ยนตาม enrollment สด** (นักเรียนย้ายห้องกลางปี บัตรเก่าจะเพี้ยน)
  ⇒ **ต้องเคาะ (คำถาม Q-A1):** คอลัมน์บัตรควร (ก) คงไว้เป็น snapshot ไม่ drop · หรือ (ข) derive จาก card request snapshot แทน live · หรือ (ค) ยอมให้เป็น live
  ⇒ **แนะนำ:** แยก CL-S7 เป็น 2 track — track 1 drop `students.*` (สะอาด) · track 2 `student_cards.*` เลื่อน/พิจารณาแยก (semantics loaded)

### กลุ่ม D จริง — backend (ตัวบล็อกการ drop)
**D1 — SQL อ่านคอลัมน์ (ต้อง reroute เป็น JOIN classrooms / pivot):**
| ไฟล์ | บรรทัด | อะไร | reroute → |
|---|---|---|---|
| `ClassroomController` | 762-763 | `getAllStudents` orderBy class_level/section | JOIN classrooms + cast(int) section |
| `ClassroomController` | 115-116 | `show()` fallback where class_level | เอา fallback ออก ใช้ pivot อย่างเดียว |
| `Classroom.php` | 168-169 | `students()` by legacy where | ใช้ classroom_students pivot |
| `AcademyMemberController` | 394-427 | fallback pluck/orderBy/groupBy | เอา fallback ออก (มี classrooms JOIN + alias อยู่แล้ว 275-294) |
| `AcademyMemberController` | 701 | `$request->merge(class_level=...)` (ตัวกรอง) | map เป็นตัวกรอง classrooms |
| `StudentCardController` | 306, 394-395 | pluck sections + orderBy | JOIN classrooms |
| `StudentController` | 46, 51 | filter where class_level/section | filter ผ่าน classrooms |
| `AcademicYearRolloverService` | 201-202 | rollover reads | ใช้ enrollment |
| `StudentCardAuditService` | 41, 58 | where class_level=6 (จบ ป.6/ม.6) | ใช้ classrooms grade |
| `RebuildClassroomsFromStudents` | 29-126 | console rebuild จาก students | **retire** (Phase G · guard prod แล้ว CL-S2) |

**D2 — เขียนค่า (ต้องเลิกเขียน + ถอด fillable):**
| ไฟล์ | บรรทัด | หมายเหตุ |
|---|---|---|
| `Student.php` | 96-97 | `$fillable` → ถอด |
| `StudentCard.php` | 21-22, 25 | `$fillable` → ถอด (แต่ดู track 2 / Q-A1) |
| `StudentCardController` | 702-703, 836-837 | validation รับ input บัตร → เขียน (track 2) |
| `StudentCardRequestService` | 220-222 | เขียน snapshot ลงบัตร (track 2 — นี่คือแหล่ง snapshot) |
| `AcademicYearRolloverService` | 383-384, 503-504 | rollover เขียน class_level → เลิกเขียน |
| `EnrollmentRepairDirtyData` | 163-164 | console repair → retire (Phase G) |

**กลุ่ม A/B/C (ไม่บล็อก — เก็บ/accessor):** output key ~53 · alias ~4 · attribute-read ~91
- resource ที่ **enrollment-first อยู่แล้ว** (ดีมาก ไม่ต้องแก้): `RoomStudentResource:48-50` (จาก classroom ตรง ๆ) ·
  `StudentCardResource:40-42` (enrollment-first + snapshot fallback)
- resource/controller ที่ output `$student->class_level` (accessor ครอบให้): StudentProfileController · AcademyMemberResource ·
  StudentSummaryResource · StudentResource · Classroom.php:265-266 (จาก grade_level ตรง ๆ)

### กลุ่ม D — frontend
- **จุดเขียน/input จริงมีจุดเดียว:** `ui/pages/academies/[name]/admin/student-cards/[id]/edit.vue:302` `v-model="form.class_section"` (แก้บัตร — track 2)
- types = contract: `ui/types/academy.ts:37-38`, `ui/types/enrollment.ts:78-79` — **เก็บ** (field ยังคืนจาก API)
- ที่เหลือ ~78 จุด = **display ล้วน** อ่าน `response.class_level` ⇒ ถ้าคง output key ไม่ต้องแตะ

### 🎯 คำถามที่เคาะแล้ว (เจ้าของโปรเจค 2026-09-16)
- **Q-A1 = ทำ track 1 ก่อน · คง `student_cards.*` ไว้เป็น snapshot** (ไม่ drop) → **CL-S7 รอบนี้แตะแค่ `students.class_level/class_section`**
  ⇒ D1/D2 ที่เป็นของบัตร (StudentCardController write, StudentCardRequestService, StudentCard fillable, edit.vue:302) **ไม่ต้องแตะ**
- ~~**R2 = accessor fallback enrollment ล่าสุดทุกสถานะ**~~ 🔴 **กลับคำ 2026-09-16 ตอนทำ Phase B — ทางเลือกนี้ขัดระบบ**
  หลักฐาน: `StudentEnrollmentService` graduate/drop/remove (บรรทัด 346-347, 400-401, 448-449) **ตั้งใจ set null**
  และ `test_graduate_student` assert `assertNull($student->class_level)` ⇒ "ไม่มี active = ไม่มีชั้น" เป็นพฤติกรรมที่ตั้งใจ+มีเทสต์คุม
  ถ้า fallback ไป enrollment ล่าสุด → เทสต์แดง 3 เคส และนักเรียนจบจะโชว์ชั้นเก่าค้าง
- ✅ **R2 (แก้แล้ว) = ไม่มี enrollment active → คืนค่าคอลัมน์เดิม (ซึ่งเป็น null อยู่แล้ว)** · ไม่มี relation latestEnrollment
  ความต้องการ "นักเรียนจบยังดูชั้นเก่าได้" ไปใช้ `student_cards` snapshot (track 2 ที่เก็บไว้) + ประวัติ enrollment แทน
- 🔴 **R3 (เจอตอน Phase B) = รูปแบบค่าต่างกัน:** `students.class_level` เก็บ **ตัวเลข** ('2') แต่ `classrooms.grade_level`
  เก็บ 'ม.2' · service เขียนผ่าน `normalizeGradeLevel()` (ตัดตัวอักษรนำหน้า) ⇒ **accessor ต้อง normalize ด้วย**
  ไม่งั้นผู้ใช้ที่เทียบเลขพัง (เช่น `StudentCardAuditService::where('class_level', 6)`) — เทสต์แดง 5 เคสตอนไม่ normalize
  ส่วน `class_section` เก็บ **ดิบ** ไม่ normalize
- **R1 (drift): ยอมรับ** — หน้าที่คอลัมน์ drift ค่าจะเปลี่ยนไปตาม enrollment จริง

### Track 1 scope (หลังเคาะ) — แตะแค่ `students.*`
- Phase B: relation `latestEnrollment` + accessor 2 ตัวบน Student (eager-load `currentEnrollment.classroom` + `latestEnrollment.classroom`)
- Phase C: reroute D1 ที่เป็นของ students — ClassroomController(115-116,762-763) · Classroom.php(168-169) ·
  AcademyMemberController(394-427,701) · StudentController(46,51) · AcademicYearRolloverService(201-202) · StudentCardAuditService(41,58)
  (StudentCardController 306/394-395 อ่าน student_cards → **ถ้าอ่าน students.class_level ต้อง reroute · ถ้าอ่าน card ปล่อย** — verify Phase C)
- Phase D: ถอด `students` fillable(96-97) · เลิกเขียน AcademicYearRolloverService(383-384,503-504) · EnrollmentRepairDirtyData retire
- Phase E: drop เฉพาะ `students.class_level`, `students.class_section` (down backfill จาก enrollment ล่าสุด + STRICT + MySQL จริง)
- Phase G: retire RebuildClassrooms/EnrollmentRepairDirtyData ที่พึ่ง students.class_level

---

## 4.6 🔴 R4 (เจอตอน Phase C, 2026-09-16) — คอลัมน์มีหน้าที่ที่ 2: **intake staging** ⇒ Phase E ติดบล็อก

`AcademicYearRolloverService:200-245` ใช้ `students.class_level/class_section` เป็น **ที่พักข้อมูล "เด็กใหม่รอเข้าเรียน"**:
- `pendingStudents` = นักเรียนที่ **มี class_level แต่ยังไม่มี active enrollment**
- rollover อ่าน `$student->class_level` + `class_section` เพื่อหา target classroom แล้วสร้าง entry `action => 'new_intake'`

⇒ คอลัมน์ตอบคำถามที่ตาราง enrollment **ตอบไม่ได้เชิงโครงสร้าง**: "เด็กคนนี้ควรเข้าห้องไหน ตอนที่ยังไม่มี enrollment"
⇒ accessor ช่วยไม่ได้ (accessor derive **จาก** enrollment แต่เด็กกลุ่มนี้ยังไม่มี)
⇒ **ถ้า drop คอลัมน์ตาม Phase E เดิม = พังทางเข้าเด็กใหม่ทั้งเส้น**

**✅ เจ้าของโปรเจคเคาะ 2026-09-16 = (ก) เลิก drop** — ยอมรับว่าคอลัมน์มี 2 หน้าที่
(cache ที่ accessor คุมแล้ว + intake staging ที่ยังจำเป็น) ⇒ **CL-S7 จบที่ Phase B/C**
ทางเลือกที่ไม่ได้เลือก (เก็บไว้เผื่ออนาคต):
- (ข) ย้าย intake ไปฟิลด์ของตัวเอง `students.intended_grade_level/intended_section` แล้วค่อย drop ของเก่า
- (ค) ทำ intake เป็นแถว `classroom_students` สถานะ `pending` ตอน import (เข้ากับ source-of-truth ที่สุด แต่งานเยอะ)

> หมายเหตุ: ตอนนี้ (หลัง Phase B) เส้น intake **ยังทำงานปกติ** เพราะ pendingStudents ไม่มี active enrollment
> accessor จึงคืนค่าคอลัมน์เดิม และ `whereNotNull('class_level')` เป็น SQL อ่านคอลัมน์จริง

**ขอบเขต Phase C จึงตัดลง** — ไม่แตะจุด intake ของ rollover (รอเคาะ R4)
และตัดของ `student_cards` ออกด้วย (track 2): `StudentCardController:306,394-395` + `StudentCardAuditService:41,58`
ยิงที่ `StudentCard::` ไม่ใช่ `students` ⇒ **ไม่อยู่ในขอบเขต track 1**

---

## 5. ผลสรุปจริง (ปิดงาน 2026-09-16)
**Track 1 (students.*):** A ✅ → B ✅ accessor → C ✅ ลบ dead fallback → **D/E/F/G ❌ ยกเลิก** (R4: คอลัมน์ยังมีหน้าที่ intake)
**Track 2 (student_cards.*):** ❌ ไม่ทำ (Q-A1: คงเป็น snapshot ตอนออกบัตร)

### สิ่งที่ได้จริงแม้ไม่ได้ drop
- การอ่าน `$student->class_level/class_section` **มาจากแหล่งจริงแล้ว** (enrollment) ไม่ใช่คอลัมน์ที่ drift → ปัญหา data drift หายไป
- ลบ dead fallback 2 จุดที่จับคู่ '1' กับ 'ม.1' (ไม่เคย match) + ตัด COUNT ที่ยิงทุกครั้งใน `getEnrolledStudentsQuery()`
- คอลัมน์เหลือหน้าที่เดียวที่ชัดเจน: **intake staging** (เด็กใหม่รอเข้าเรียน) — ถ้าจะ drop จริงวันหน้า ต้องทำ (ข) หรือ (ค) ก่อน
- เป็นงาน **หลาย session** · เฟส B คือจุดเปลี่ยนพฤติกรรม (ต้องเทสต์หนักสุด)
- แนะนำเริ่ม **Phase A** ก่อน (Claude ทำได้เลย ไม่แตะโค้ด) แล้วค่อยเคาะ R2/R1 ก่อนลง Phase B

## 6. สถานะ
- **2026-09-15:** เขียนแผน · เมนู #10 CL-S7 ชี้มาที่ไฟล์นี้
- **2026-09-16 Phase A ✅:** categorize ครบ (ดู §4.5) — พบว่า `students.*` (สะอาด) กับ `student_cards.*` (snapshot) ต่างกัน
- **2026-09-16 เคาะ Q-A1/R2:** ทำ **track 1 เท่านั้น** (drop `students.class_level/class_section` · คงบัตรไว้)
- **2026-09-16 Phase B ✅ `43bdeb69`** — accessor บน Student (currentEnrollment + normalize) · **R2 กลับคำเป็น "ไม่มี active = null"**
  และเจอ R3 (ต้อง normalize เป็นตัวเลข) ระหว่าง verify · เทสต์ใหม่ StudentClassAccessorTest 4 เคส · suite **401/401**
- **2026-09-16 Phase C ✅ + ปิดงาน** — เจอ **R4 (intake staging)** ⇒ เจ้าของเคาะ **เลิก drop** ·
  Phase C เหลือแค่ลบ dead fallback 2 จุด (พิสูจน์ด้วยข้อมูลจริง: students.class_level มีแต่ '1'–'6' ·
  classrooms.grade_level มีแต่ 'ม.1'–'ม.6' ⇒ ไม่เคย match) · suite **401/401** ·
  🎯 **CL-S7 ปิดที่ Phase B/C — D/E/F/G ยกเลิก**

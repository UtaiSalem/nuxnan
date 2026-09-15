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

## 5. ลำดับแนะนำ & ประมาณการ
A (audit) → B (accessor) → **verify หนัก** → C (SQL) → D (write) → **verify** → E (drop+MySQL) → F (FE) → G (cleanup)
- เป็นงาน **หลาย session** · เฟส B คือจุดเปลี่ยนพฤติกรรม (ต้องเทสต์หนักสุด)
- แนะนำเริ่ม **Phase A** ก่อน (Claude ทำได้เลย ไม่แตะโค้ด) แล้วค่อยเคาะ R2/R1 ก่อนลง Phase B

## 6. สถานะ
- **2026-09-15:** เขียนแผนนี้ (ยังไม่ลงมือ) · เมนู #10 CL-S7 ชี้มาที่ไฟล์นี้ · รอเจ้าของโปรเจคเคาะเริ่ม Phase A

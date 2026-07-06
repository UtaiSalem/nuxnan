# แผนปรับปรุงระบบบัตรนักเรียน — ฉบับสมบูรณ์

## Implementation update — 2026-07-06

- 2026-07-07 old-card display fix: active grades 2, 3, 5, and 6 retain `national_id` and `birth_date` for all 442/357/303/288 records. Per explicit user direction, the temporary `makeHidden()` filter was removed from the public room endpoint so the existing Nuxt card can render complete identity data before authentication is revisited. Verified live `GET /api/student-card/2/1` returns `national_id`, `birth_date`, `birth_date_string`, and `profile_image_url`; PHP syntax passes. Security follow-up remains: protect or mask PII before production exposure.
- 2026-07-07 identity-data audit: exactly 476 active 2569 cards (the entire new-intake cohort) lack national ID and birth date; their linked `students` rows also lack both fields, so card sync did not erase recoverable values. No import batch/row data exists, linked user profiles contain no birthdate/metadata, and no authoritative 2569 intake source file was found in the repository. Recovery requires the registrar's original intake data and must not infer sensitive identity fields.
- 2026-07-07 photo-path analysis: completed migration of 1,529/1,531 student photos to canonical identity paths (`images/students/profiles/{student_id}.{ext}`). Integrated `profile_image_url` accessors in Student/StudentCard models. Replaced legacy manual path resolution logic on the frontend with backend-owned URL endpoints. Resolved E2E findings (including Controller imports, path formatting, null checks, and frontend fallback cleanup). E2E integration tests passing successfully.
- 2026-07-07 photo-path analysis: current storage is grade/room based (`images/students/{level}/{section}/{filename}`) while rollover mutates level/section, so every consumer that reconstructs the path breaks. Sustainable direction is a student-identity canonical path, backend-owned URL resolution, additive backfill with checksum/ambiguity reporting, and no annual file moves.
- Production-data refresh completed: created academic year 2569 (id 2) and 54 target classrooms; committed rollover batch `3c9ca6f7-3ece-4bbd-8f51-b7d64eae5162` with 1,662 promoted, 267 graduated, 476 new intake, and 0 skipped; set 2569 current; synced 2,138 active cards.
- Reconciliation: active enrollment/card counts both 2,138; zero duplicate active cards, zero duplicate active enrollments, zero multiple-current academic records, and zero active enrollments missing a card.
- Runtime follow-up: fixed the public dashboard URL to `/api/student-card/dashboard`, switched public loading from authenticated `useApi()` to `$fetch`, ran the pending student-card status migration, and verified the page renders M.1–M.6 plus real room lists in the in-app browser.
- Hardened legacy and academy student-card routes, academy-scoped all card mutations, and required `students.manage` for academy admin operations.
- Fixed grade parsing, duplicate detection, sync TOCTOU/N+1 behavior, CLI confirmation, profile-image length, frontend academy hard-coding, and added typed confirmation UI.
- Verification: Pint passed; focused StudentCard suite passed (8 tests, 19 assertions). Nuxt build exceeded the 5-minute verification timeout without diagnostics.

**วันที่:** 2026-07-06
**สถานะ:** วางแผน (ยังไม่เริ่มพัฒนา)
**ขอบเขต:** ปลด ม.6 จบการศึกษา, เพิ่ม ม.1/ม.4 ใหม่, อัพเดทบัตรนักเรียนให้ตรงกับปีการศึกษาปัจจุบัน

---

## User Analysis Input

### สรุปแผนเดิมจากผู้ใช้ (10 ระยะ)
- ระยะ 0: กำหนดขอบเขตและนโยบายข้อมูล
- ระยะ 1: ตรวจโครงสร้าง DB จริง
- ระยะ 2: Audit report แบบ read-only
- ระยะ 3: เตรียมปีการศึกษาและห้องเรียนเป้าหมาย
- ระยะ 4: Preview rollover ด้วย AcademicYearRolloverService
- ระยะ 5: Commit ข้อมูลทะเบียน
- ระยะ 6: สร้าง StudentCardSyncService
- ระยะ 7: ปรับ API บัตรนักเรียน
- ระยะ 8: ปรับหน้า /student-card frontend
- ระยะ 9: เพิ่มหน้า admin ตรวจสอบก่อนยืนยัน
- ระยะ 10: ทดสอบ

---

## การวิเคราะห์เทียบกับ Codebase จริง

### สิ่งที่ผู้ใช้วิเคราะห์ถูกต้อง

1. **AcademicYearRolloverService มีอยู่จริงและครบถ้วน** — มี `previewRollover()`, `planRollover()`, `commitRollover()`, `undoRollover()`, `closeUndoWindow()` รองรับ action: promote, graduate, repeat, drop, new_intake, skip ครบตามที่วิเคราะห์
2. **Source of Truth ที่กำหนดถูกต้อง** — classroom_students เป็น enrollment หลัก, student_academic_info เก็บประวัติ, students เก็บข้อมูลบุคคล, student_cards เป็น denormalized snapshot
3. **หน้า /student-card hard-code จำนวนห้องจริง** — อยู่ที่ `ui/pages/student-card/index.vue` และ `admin/index.vue` (ม.1=11ห้อง, ม.2=9, ม.3=9, ม.4=8, ม.5=7, ม.6=7)
4. **API ไม่กรอง status จริง** — `getStudentByRoom()` query เฉพาะ `class_level + class_section` ไม่มี where status
5. **student_status มีปัญหาชนิดข้อมูลจริง** — migration สร้างเป็น integer แต่ controller เขียน string 'active'

### สิ่งที่ต้องเพิ่มเติมจากการตรวจ codebase

6. **StudentEnrollmentService แยกจาก RolloverService** — rollover เรียก enrollment service อีกทีหนึ่ง มี method: `promoteStudent()`, `graduateStudent()`, `dropStudent()`, `repeatStudent()`, `enrollStudent()` แต่ละ method อัพเดท classroom_students + students + student_academic_info ให้ครบ ไม่ต้องเขียนซ้ำ
7. **Frontend Rollover มี UI ครบแล้ว** — `ui/pages/academies/[name]/admin/gradebook/rollover/index.vue` มี wizard 4 ขั้นตอน + components: RolloverPreviewSummary, RolloverCommitPanel, RolloverBatchHistoryCard, RolloverUndoBanner, RolloverYearPicker, RolloverStudentBucket, RolloverClassroomChecklist, RolloverStepIndicator
8. **Import system กำลังสร้าง** — มี migration `2026_07_05` สร้าง student_import_batches + student_import_rows แต่ controller ยังคืน 501
9. **Public routes ไม่มี auth** — `/api/student-card/*` ทั้งหมดเปิดเป็น public ไม่มี middleware auth:api (เฉพาะ academy-scoped routes ที่มี auth)
10. **student_cards ไม่มี academic_year_id** — ไม่มี field ระบุว่าบัตรนี้เป็นของปีการศึกษาไหน ต้องใช้ student_status เป็นตัวแบ่ง
11. **student_cards.class_level เป็น integer** — ขณะที่ students.class_level เป็น string 'ม.1' (format ต่างกัน)
12. **StudentCard model มี fallback matching** — `student()` relation ลอง FK ก่อน ถ้าไม่มีก็ fallback ไป match ด้วย student_number หรือ national_id (legacy)
13. **Photo path ใช้ class_level/class_section** — `storage/images/students/{level}/{room}/{filename}` ถ้าเลื่อนชั้นต้องพิจารณา path ใหม่หรือคง path เดิม
14. **RolloverBatch มี 24-hour undo window** — ใช้ committed_at + 24h กำหนด, มี closeUndoWindow() ปิดก่อนได้
15. **Permission gates ของ rollover** — ต้องมี enrollment.preview, enrollment.plan, enrollment.commit, enrollment.undo

### สิ่งที่ต้องแก้ไขจากแผนเดิม

16. **ไม่จำเป็นต้องสร้าง Artisan Command สำหรับ audit** — ควรสร้างเป็น Service + API endpoint แทน เพื่อให้หน้า admin เรียกได้ และส่งออก CSV ได้จากหน้าเว็บ ไม่ต้อง SSH เข้าเครื่อง
17. **ระยะ 9 (หน้า preview/confirm) ซ้อนทับกับ Rollover UI ที่มีอยู่** — rollover wizard มี preview → plan → commit → undo ครบแล้ว ส่วนที่ต้องเพิ่มคือ "card sync preview" ซึ่งเป็นขั้นตอนแยกหลัง rollover
18. **academy_id ใน student_cards** — ถูกเพิ่มภายหลังเป็น nullable bigint (migration 2026_03_30) ควรตรวจว่า backfill ครบหรือยัง

---

## Work Plan — ฉบับปรับปรุงสมบูรณ์

### ระยะที่ 0: กำหนดขอบเขตและนโยบายข้อมูล

**เป้าหมาย:** ยืนยันกติกาให้ชัดก่อนแตะข้อมูล

**ต้องตอบคำถามเหล่านี้:**

| # | คำถาม | ค่าที่คาดว่าจะได้ | ใครตอบ |
|---|-------|-------------------|--------|
| 0.1 | academy_id ที่จะดำเนินการ | 1 (โรงเรียนเดียว) | นายทะเบียน |
| 0.2 | ปีการศึกษาเดิม (from) | 2567 | นายทะเบียน |
| 0.3 | ปีการศึกษาใหม่ (to) | 2568 | นายทะเบียน |
| 0.4 | วันออกบัตร (card_issue_date) | วันที่เปิดเทอม 2568 | นายทะเบียน |
| 0.5 | วันหมดอายุบัตร (card_expiry_date) | วันปิดเทอมปลาย 2568 หรือ 3 ปี | นายทะเบียน |
| 0.6 | ม.6 ปลด = graduated, ไม่ลบ | ยืนยัน | developer |
| 0.7 | นักเรียน ม.4 ใหม่จากภายนอก — ข้อมูลมาจากไหน | อยู่ใน students table แล้ว vs ต้อง import | นายทะเบียน |
| 0.8 | เลขประจำตัวนักเรียน — ใช้เลขเดิม or ออกใหม่ | เลขเดิมสำหรับคนเลื่อนชั้น, ออกใหม่สำหรับ ม.1/ม.4 ใหม่ | นายทะเบียน |
| 0.9 | รูปเดิม — ใช้ต่อหรือถ่ายใหม่ | คนเลื่อนชั้นใช้ต่อ, คนใหม่อัพโหลดทีหลัง | นายทะเบียน |
| 0.10 | จำนวนห้องจริง ม.1–ม.6 ของปี 2568 | ต้องยืนยัน (อาจต่างจาก hard-code เดิม) | นายทะเบียน |

**ผลลัพธ์:** เอกสารกติกา 1 หน้า ใช้อ้างอิงกับทุกขั้นตอน

---

### ระยะที่ 1: ตรวจโครงสร้าง DB จริง (Schema Audit)

**เป้าหมาย:** ยืนยันว่า schema ตรงกับที่ migration กำหนด + หา inconsistency

**ขั้นตอน:**

**1.1 ตรวจ student_cards.student_status จริง:**
```sql
-- ตรวจ column type จริง (migration กำหนด integer แต่ controller เขียน string)
DESCRIBE student_cards;
-- ตรวจค่าที่ใช้จริง
SELECT DISTINCT student_status, COUNT(*) FROM student_cards GROUP BY student_status;
```
- ถ้าเป็น integer แต่มี string → MySQL อาจ cast ให้อัตโนมัติ ต้องเช็คว่าค่าที่เก็บจริงคือ 0 หรือ 'active'
- **ถ้าพบปัญหา:** เขียน migration เปลี่ยน column เป็น `string` หรือ `enum('active','expired','graduated')` ให้ตรงกับการใช้งานจริง

**1.2 ตรวจ student_cards indexes:**
```sql
SHOW INDEX FROM student_cards;
```
- ต้องมี: `student_id` (FK), `academy_id` (index)
- **ควรเพิ่ม (ถ้าไม่มี):** composite index `(academy_id, class_level, class_section, student_status)` สำหรับ query ตามห้อง

**1.3 ตรวจ unique constraint:**
```sql
-- ตรวจว่านักเรียน 1 คนมีบัตรซ้ำไหม
SELECT student_id, COUNT(*) as cnt FROM student_cards
WHERE student_id IS NOT NULL
GROUP BY student_id HAVING cnt > 1;

-- ตรวจบัตรที่ไม่มี student_id (legacy)
SELECT COUNT(*) FROM student_cards WHERE student_id IS NULL;
```
- **ถ้าพบซ้ำ:** ต้อง deduplicate ก่อน แล้วเพิ่ม unique index `(student_id)` ที่ไม่ null
- **ถ้า student_id NULL เยอะ:** ต้องรัน `StudentsBackfillCardLink` command ก่อน

**1.4 ตรวจ academy_id backfill:**
```sql
SELECT COUNT(*) as total,
       SUM(CASE WHEN academy_id IS NULL THEN 1 ELSE 0 END) as missing_academy
FROM student_cards;
```
- **ถ้ายังไม่ครบ:** รัน `SyncStudentRelatedTables` command (มีอยู่แล้ว)

**1.5 ตรวจ classroom_students constraints:**
```sql
-- ตรวจว่ามีนักเรียน active ซ้ำหลาย classroom ไหม
SELECT student_id, COUNT(*) as cnt FROM classroom_students
WHERE status = 'active'
GROUP BY student_id HAVING cnt > 1;
```

**1.6 ตรวจ student_academic_info current record:**
```sql
-- ตรวจว่ามีคนมี is_current = true มากกว่า 1 record
SELECT student_id, COUNT(*) as cnt FROM student_academic_info
WHERE is_current = 1
GROUP BY student_id HAVING cnt > 1;

-- ตรวจว่ามีคนไม่มี current record เลย (active student)
SELECT s.id, s.student_id, s.first_name_th, s.last_name_th
FROM students s
LEFT JOIN student_academic_info sai ON s.id = sai.student_id AND sai.is_current = 1
WHERE s.status = 'active' AND sai.id IS NULL;
```

**1.7 ตรวจปีการศึกษาและห้องเรียน:**
```sql
-- ปีการศึกษาที่มี
SELECT * FROM academic_years WHERE academy_id = 1 ORDER BY name;

-- ห้องเรียนของปีปัจจุบัน
SELECT ay.name, c.grade_level, c.section, c.name as display_name,
       (SELECT COUNT(*) FROM classroom_students cs WHERE cs.classroom_id = c.id AND cs.status = 'active') as active_count
FROM classrooms c
JOIN academic_years ay ON c.academic_year_id = ay.id
WHERE c.academy_id = 1 AND ay.is_current = 1
ORDER BY c.grade_level, c.section;
```

**ผลลัพธ์ระยะ 1:**
- รายการ inconsistency ที่พบ
- Migration script(s) ที่ต้องเขียน (ถ้ามี)
- ยืนยันว่าข้อมูล baseline สะอาดพอที่จะทำ rollover ได้

**ไฟล์ที่อาจต้องสร้าง/แก้:**
| ไฟล์ | Action | เงื่อนไข |
|------|--------|----------|
| `database/migrations/xxxx_fix_student_cards_status_column.php` | สร้าง | ถ้า student_status เป็น integer |
| `database/migrations/xxxx_add_student_cards_indexes.php` | สร้าง | ถ้าขาด composite index |
| `database/migrations/xxxx_add_unique_student_id_to_student_cards.php` | สร้าง | ถ้าไม่มี unique constraint |

---

### ระยะที่ 2: จัดทำ Audit Service + API

**เป้าหมาย:** ตรวจข้อมูลนักเรียน-บัตรแบบ read-only แสดงผลทั้ง CLI และ web

**2.1 สร้าง `StudentCardAuditService`**

**ไฟล์:** `api/nuxnanravel/app/Services/StudentCardAuditService.php`

**Method หลัก:**
```
audit(Academy $academy, AcademicYear $year, array $levels = []): AuditReport
```

**AuditReport ควรมี sections:**

**Section A — ม.6 (จบการศึกษา):**
- จำนวน active enrollment ของ ม.6 ในปีที่ระบุ
- คนที่มี student_card ที่ student_status ยัง active
- คนที่ classroom_students.status = 'graduated' แล้ว แต่บัตรยัง active (ไม่ sync)
- คนที่มีหลายบัตร (duplicate)
- คนที่ไม่มี student_academic_info ปัจจุบัน
- **Output:** รายชื่อ + student_id + สถานะแต่ละตาราง

**Section B — ม.1 และ ม.4 ใหม่:**
- คนที่อยู่ใน students (class_level = 'ม.1' หรือ 'ม.4') แต่ไม่มี active enrollment ใน classroom_students
- คนที่มี enrollment แล้วแต่ไม่มี student_card
- คนที่มี student_card แล้วแต่ student_id = NULL (ยังไม่เชื่อม)
- คนที่ students.class_level ไม่ตรงกับ active enrollment classroom.grade_level
- คนที่ขาดข้อมูลจำเป็น: ชื่อ, เลขนักเรียน, วันเกิด, เลขบัตรประชาชน
- คนที่ student_id หรือ citizen_id ซ้ำภายใน academy
- **Output:** รายชื่อ + รายละเอียดสิ่งที่ขาด

**Section C — Cross-table consistency (ทุกชั้น):**
- students ↔ classroom_students: คนที่ status ไม่ตรงกัน
- classroom_students ↔ classrooms: enrollment ที่ชี้ classroom ไม่อยู่ในปีปัจจุบัน
- students ↔ student_cards: ชื่อ/ชั้น/ห้องไม่ตรง (stale snapshot)
- student_academic_info: current record ไม่ตรงกับ enrollment
- **Output:** รายชื่อ + ค่าที่ต่างกัน

**2.2 สร้าง Artisan Command (สำหรับ CLI)**

**ไฟล์:** `api/nuxnanravel/app/Console/Commands/StudentCardAudit.php`
**Signature:** `students:card-audit {--academy=} {--academic-year=} {--levels=} {--output=table} {--export=}`

- `--output=table` แสดงบนจอ, `--output=json` ส่งออก JSON
- `--export=path.csv` ส่งออก CSV สำหรับนายทะเบียน
- เรียก `StudentCardAuditService::audit()` ภายใน

**2.3 เพิ่ม API endpoint (สำหรับ web)**

**Route:** `GET /api/academies/{academy}/student-cards/audit`
**Query params:** `academic_year_id`, `levels` (comma-separated), `format` (json|csv)
**Auth:** ต้อง auth:api + permission check (admin only)
**Response:** JSON สำหรับ web หรือ download CSV

**ไฟล์ที่ต้องสร้าง/แก้:**
| ไฟล์ | Action |
|------|--------|
| `app/Services/StudentCardAuditService.php` | สร้างใหม่ |
| `app/Console/Commands/StudentCardAudit.php` | สร้างใหม่ |
| `StudentCardController.php` | เพิ่ม method `audit()` |
| `routes/learn/academy-student-card.php` | เพิ่ม route `GET /audit` |

---

### ระยะที่ 3: เตรียมปีการศึกษาและห้องเรียมเป้าหมาย

**เป้าหมาย:** ให้แน่ใจว่ามี AcademicYear + Classrooms ครบก่อน rollover

**3.1 ตรวจปีการศึกษาใหม่:**
- เข้า `/academies/{name}/admin/gradebook/rollover` (หน้า rollover ที่มีอยู่)
- ถ้าปีใหม่ยังไม่มี → สร้างผ่าน UI (มีปุ่มสร้างปีใหม่ใน wizard อยู่แล้ว)
- Set `is_current = true` ให้ปีใหม่

**3.2 ตรวจห้องเรียนของปีใหม่:**
```sql
-- ห้องเรียนที่ต้องมีใน academic_year ใหม่
-- ม.1 (/1-/11 ตามจำนวนห้องจริง), ม.2-ม.6 ตามจริง
SELECT grade_level, section FROM classrooms
WHERE academy_id = 1 AND academic_year_id = {new_year_id}
ORDER BY grade_level, CAST(section AS UNSIGNED);
```
- ถ้าขาด → สร้างผ่าน rollover wizard (มีปุ่ม "สร้างห้องเรียน" อยู่แล้ว)

**3.3 ตรวจรูปแบบข้อมูล:**
- `grade_level` ต้องเป็น 'ม.1', 'ม.2', ... (ไม่ใช่ '1', '2', ...)
- `section` ต้องเป็น '1', '2', ... (string)
- ตรงกับ format ที่ `nextGrade()` ใน RolloverService ใช้

**3.4 ตรวจ mapping ห้อง:**
- นักเรียน ม.1/1 ปีเก่า → เลื่อนเป็น ม.2/? ปีใหม่
- mapping ห้องเดิม→ห้องใหม่ ต้องนายทะเบียนกำหนด (ไม่ควรให้ระบบจัดเองทั้งหมด)
- rollover wizard รองรับ user mapping ได้อยู่แล้ว (step 2 ของ wizard)

**ไม่ต้องเขียนโค้ดใหม่** — ใช้ UI ที่มี + SQL ตรวจ

---

### ระยะที่ 4: Preview และ Plan Rollover

**เป้าหมาย:** ใช้ระบบ rollover ที่มี preview + plan ก่อน commit

**4.1 เข้า Rollover Wizard:**
- URL: `/academies/{name}/admin/gradebook/rollover`
- Step 1: เลือก from year = 2567, to year = 2568

**4.2 Preview:**
- Wizard เรียก `POST /api/academies/{academy}/rollover/preview`
- ระบบจำแนกนักเรียนออกเป็น:
  - `graduate` — ม.6 (nextGrade returns null)
  - `promote` — ม.1→ม.2, ม.2→ม.3, ม.3→ม.4, ม.4→ม.5, ม.5→ม.6
  - `repeat` — นักเรียนซ้ำชั้น (ถ้ากำหนด)
  - `drop` — นักเรียนออก (ถ้ากำหนด)
  - `new_intake` — นักเรียนใหม่ ม.1/ม.4 ที่อยู่ใน students แต่ไม่มี enrollment
  - `skip` — ข้อมูลไม่ครบ

**4.3 ตรวจสอบ preview:**
- **ม.6:** ต้องเป็น `graduate` ทุกคน
- **ม.1-ม.5 เดิม:** ต้องเป็น `promote` + มี target classroom
- **ม.1/ม.4 ใหม่:** ต้องเป็น `new_intake` + มี target classroom
- **Warnings:** ตรวจ missing target classrooms, duplicate entries

**4.4 สิ่งที่ต้องตรวจเพิ่ม (นอกเหนือจากที่ wizard แสดง):**
- นักเรียน ม.4 ใหม่จากภายนอก ต้องแยกจาก ม.3 ที่เลื่อนเป็น ม.4
  - คนจาก ม.3 → ม.4 ระบบจะจำแนกเป็น `promote`
  - คนใหม่ → ระบบจำแนกเป็น `new_intake` (ถ้ามี class_level='ม.4' แต่ไม่มี enrollment ปีเก่า)
- นักเรียน ม.1 ใหม่ทั้งหมดควรเป็น `new_intake`
- ตรวจรายการ `skip` ทุกคน — นายทะเบียนต้องยืนยัน

**4.5 Plan:**
- ปรับ mapping ตามต้องการ (เปลี่ยนห้อง, เปลี่ยน action)
- กด Plan → ระบบ validate + cache plan ไว้ 15 นาที
- ตรวจ plan summary ว่าจำนวนตรง

**ไม่ต้องเขียนโค้ดใหม่** — ใช้ rollover wizard + API ที่มี

**⚠️ ข้อควรระวัง:**
- ถ้า `previewRollover()` ไม่จัดการ pending students (คนที่มี class_level แต่ไม่มี enrollment) ต้องตรวจว่ามีขึ้น new_intake จริงไหม — จากโค้ดพบว่า **มีรองรับ** (มี logic ดึง pending students)
- ถ้า preview ไม่แสดงนักเรียนใหม่ที่คาดหวัง → ตรวจว่า students.academy_id ตรงไหม, students.status = 'active' ไหม

---

### ระยะที่ 5: Commit Rollover (ข้อมูลทะเบียน)

**เป้าหมาย:** เปลี่ยนข้อมูลทะเบียนจริง ผ่าน transaction

**5.1 ก่อน commit:**
- **สำรอง DB** (`mysqldump nuxnan > nuxnan_backup_before_rollover.sql`)
- ตรวจ plan summary อีกครั้ง
- พิมพ์ชื่อปีการศึกษาเพื่อยืนยัน (CommitRolloverRequest บังคับ confirm_text)

**5.2 Commit:**
- กด Commit ใน wizard → `POST /api/academies/{academy}/rollover/commit`
- ระบบทำใน DB transaction:

  **สำหรับ ม.6 (graduate):**
  - `StudentEnrollmentService::graduateStudent()`:
    - classroom_students.status → `graduated`, set `left_at`
    - students.status → `graduated`, clear class_level
    - student_academic_info → set `graduation_date`, status → `graduated`
    - **ไม่ลบ** student, user, ประวัติ, หรือรูป

  **สำหรับนักเรียนเลื่อนชั้น (promote):**
  - `StudentEnrollmentService::promoteStudent()`:
    - ปิด enrollment เดิม → status `promoted`
    - สร้าง active enrollment ใหม่ใน classroom ปีใหม่
    - อัพเดท students.class_level, class_section
    - จัดการ student_academic_info (ปิดเก่า, สร้างใหม่)

  **สำหรับนักเรียนใหม่ ม.1/ม.4 (new_intake):**
  - `StudentEnrollmentService::enrollStudent()`:
    - สร้าง active enrollment
    - อัพเดท students.class_level
    - สร้าง student_academic_info ปัจจุบัน

- สร้าง `RolloverBatch` record → เก็บ snapshot + totals
- Dispatch `RolloverCommitted` event

**5.3 ตรวจหลัง commit:**
```sql
-- ตรวจ enrollment ปีใหม่
SELECT c.grade_level, c.section, COUNT(*) as cnt
FROM classroom_students cs
JOIN classrooms c ON cs.classroom_id = c.id
WHERE cs.academic_year_id = {new_year_id} AND cs.status = 'active'
GROUP BY c.grade_level, c.section
ORDER BY c.grade_level, CAST(c.section AS UNSIGNED);

-- ตรวจ ม.6 ถูก graduate
SELECT COUNT(*) FROM students
WHERE academy_id = 1 AND status = 'graduated'
AND id IN (SELECT student_id FROM classroom_students WHERE status = 'graduated' AND academic_year_id = {old_year_id});
```

**5.4 Undo (ถ้าพบปัญหา):**
- มี 24 ชั่วโมงสำหรับ undo
- `POST /api/academies/{academy}/rollover/batches/{batch}/undo`
- ปิด undo window เมื่อยืนยันว่าถูกต้อง: `POST .../close-undo`

**ไม่ต้องเขียนโค้ดใหม่** — ใช้ rollover system ที่มี

---

### ระยะที่ 6: สร้าง StudentCardSyncService

**เป้าหมาย:** สร้าง/อัพเดท/ปิดบัตรให้ตรงกับข้อมูลทะเบียนที่ commit แล้ว

**6.1 สร้าง Service:**

**ไฟล์:** `api/nuxnanravel/app/Services/StudentCardSyncService.php`

**Methods:**

```
previewSync(Academy $academy, AcademicYear $year): CardSyncPreview
commitSync(Academy $academy, AcademicYear $year, User $by): CardSyncResult
```

**Logic ของ previewSync:**

1. ดึง active enrollment ทั้งหมดของ academy ในปีที่ระบุ (join classrooms + students)
2. ดึง student_cards ทั้งหมดของ academy
3. จำแนกออกเป็น:

| กลุ่ม | เงื่อนไข | Action |
|-------|----------|--------|
| **create** | มี active enrollment แต่ไม่มี student_card (หรือ student_card.student_id = NULL) | สร้างบัตรใหม่ |
| **update** | มี active enrollment + มี student_card แต่ข้อมูลไม่ตรง (ชั้น/ห้อง/ชื่อ/วันเกิด) | อัพเดท snapshot |
| **expire** | มี student_card active แต่ไม่มี active enrollment (ม.6 จบ, ลาออก) | เปลี่ยน student_status → expired/graduated |
| **unchanged** | ข้อมูลตรงหมดแล้ว | ไม่ทำอะไร |
| **orphan** | มี student_card แต่ student_id = NULL และ match ไม่ได้ | แจ้งเตือน |

4. Return preview พร้อมจำนวนแต่ละกลุ่ม + รายละเอียดรายคน

**Logic ของ commitSync:**

1. **Create** — สร้าง student_card ใหม่:
   - ดึงข้อมูลจาก `students`: title_prefix_th, first_name_th, last_name_th, first_name_en, date_of_birth, citizen_id, profile_image
   - ดึงจาก active `classroom_students.classroom`: grade_level → class_level, section → class_section
   - ดึง student_number จาก classroom_students.student_number หรือ students.student_id
   - สร้าง full_name_thai = title + first + last
   - สร้าง level_and_room = "{grade_level_number}/{section}"
   - สร้าง birth_date_string = format จาก date_of_birth
   - ตั้ง card_issue_date, card_expiry_date ตามนโยบาย
   - ตั้ง student_status = 'active' (⚠️ ต้องแก้ column type ก่อนถ้ายังเป็น integer)
   - ตั้ง academy_id
   - ตั้ง student_id (FK)

2. **Update** — อัพเดท student_card ที่มี:
   - อัพเดท class_level, class_section, level_and_room จาก enrollment ปัจจุบัน
   - อัพเดท full_name_thai, first_name_thai, last_name_thai ถ้าเปลี่ยน
   - อัพเดท card_issue_date, card_expiry_date ตามนโยบาย
   - **คง profile_image เดิม** ถ้าไม่มีรูปใหม่
   - ⚠️ **class_level ใน student_cards เป็น integer** (1,2,3,...) vs **students.class_level เป็น string** ('ม.1','ม.2',...) → ต้องแปลง

3. **Expire** — ปิดบัตร:
   - student_status → 'expired' หรือ 'graduated'
   - **ไม่ลบบัตร** — เก็บไว้เป็นประวัติ
   - **ไม่ลบรูป** — อาจใช้อ้างอิงภายหลัง

4. **Idempotent** — รันซ้ำแล้วไม่สร้างบัตรซ้ำ:
   - ค้นบัตรด้วย `student_id` (FK) เป็นหลัก
   - ถ้า student_id ไม่มี ให้ลอง match ด้วย student_number + academy_id

**6.2 Field Mapping ระหว่าง tables:**

| student_cards field | Source | หมายเหตุ |
|---------------------|--------|----------|
| student_id | students.id | FK |
| academy_id | students.academy_id | |
| student_number | students.student_id (string 20) | ⚠️ ชื่อ field ซ้ำซ้อน |
| full_name_thai | concat(students.title_prefix_th, first_name_th, last_name_th) | |
| title_name | students.title_prefix_th | |
| first_name_thai | students.first_name_th | |
| last_name_thai | students.last_name_th | |
| first_name_english | students.first_name_en | |
| national_id | students.citizen_id | |
| birth_date | students.date_of_birth | |
| birth_date_string | format(students.date_of_birth, 'd/m/Y') | + 543 สำหรับ พ.ศ. |
| class_level | **integer** จาก classroom.grade_level | 'ม.1'→1, 'ม.2'→2, ... |
| class_section | **integer** จาก classroom.section | '1'→1, '2'→2, ... |
| level_and_room | "{class_level}/{class_section}" | e.g. "1/1" |
| card_issue_date | ตามนโยบาย (ระยะ 0) | |
| card_expiry_date | ตามนโยบาย (ระยะ 0) | |
| student_status | 'active' | ⚠️ ต้องแก้ column type |
| profile_image | students.profile_image หรือ path เดิม | คงรูปเดิมถ้ามี |
| order_no | classroom_students.student_number | เลขที่ในห้อง |

**6.3 สร้าง Artisan Command:**

**ไฟล์:** `api/nuxnanravel/app/Console/Commands/SyncStudentCards.php`
**Signature:** `students:sync-cards {--academy=} {--academic-year=} {--preview} {--commit}`
- `--preview` แสดงสรุปก่อนทำ
- `--commit` ทำจริง
- ต้องมี `--preview` ก่อน `--commit` (safety)

**6.4 เพิ่ม API endpoint:**

**Routes เพิ่ม:**
- `GET /api/academies/{academy}/student-cards/sync/preview` — preview sync
- `POST /api/academies/{academy}/student-cards/sync/commit` — commit sync
- Auth: `auth:api` + admin permission

**ไฟล์ที่ต้องสร้าง/แก้:**
| ไฟล์ | Action |
|------|--------|
| `app/Services/StudentCardSyncService.php` | สร้างใหม่ |
| `app/Console/Commands/SyncStudentCards.php` | สร้างใหม่ |
| `StudentCardController.php` | เพิ่ม `syncPreview()`, `syncCommit()` |
| `routes/learn/academy-student-card.php` | เพิ่ม routes |

---

### ระยะที่ 7: ปรับ API บัตรนักเรียน

**เป้าหมาย:** ให้ API กรองข้อมูลถูกต้อง ไม่แสดง ม.6 เก่า ไม่ข้าม academy

**7.1 แก้ `getStudentByRoom()` — เพิ่ม status filter:**

**ไฟล์:** `StudentCardController.php`

**ปัจจุบัน:**
```php
StudentCard::where('class_level', $level)->where('class_section', $room)->orderBy(...)
```

**ควรเป็น:**
```php
StudentCard::where('class_level', $level)
    ->where('class_section', $room)
    ->where('student_status', 'active')  // ⚠️ ต้องแก้ column type ก่อน
    ->where('academy_id', $academy->id)  // scope ตาม academy
    ->orderBy(...)
```

**7.2 แก้ `dashboard()` — เพิ่ม status + academy filter:**

**ปัจจุบัน:** นับทุกบัตรไม่กรอง
**ควรเป็น:** นับเฉพาะ `student_status = 'active'` + กรอง academy

**7.3 แก้ `statistics()` — เพิ่ม graduated/expired stats:**

เพิ่ม return:
```php
[
    'totalActive' => ...,
    'totalGraduated' => ...,
    'totalExpired' => ...,
    'missingCard' => ...,   // active enrollment ที่ไม่มีบัตร
    'byLevel' => [...],     // แยก active เท่านั้น
    'sectionsByLevel' => [...],
]
```

**7.4 แก้ `search()` — เพิ่ม status parameter:**

เพิ่ม query parameter `status` (default: 'active')
Admin สามารถ `?status=all` เพื่อดู graduated/expired ได้

**7.5 เพิ่ม endpoint `levels()` ที่ dynamic:**

**Route:** `GET /api/academies/{academy}/student-cards/levels`
**Response:** ดึง distinct grade_level + section จาก active student_cards ของ academy
```json
[
    {"level": 1, "name": "ม.1", "sections": [1,2,3,...,11], "studentCount": 450},
    {"level": 2, "name": "ม.2", "sections": [1,2,...,9], "studentCount": 380},
    ...
]
```
→ ใช้แทน hard-code ใน frontend

**7.6 พิจารณา legacy routes:**

**Public routes** (`/api/student-card/*`) ที่ไม่มี auth:
- ⚠️ ตอนนี้ใครก็เข้าได้ ข้อมูลนักเรียนเป็นข้อมูลส่วนบุคคล
- **ทางเลือก A:** เพิ่ม auth middleware (breaking change สำหรับ client ที่ใช้อยู่)
- **ทางเลือก B:** ให้ legacy routes redirect ไป academy-scoped routes
- **ทางเลือก C:** คง public ไว้แต่ จำกัดข้อมูล (ไม่แสดง national_id, birth_date)
- **แนะนำ:** เริ่มจากทางเลือก B + deprecation notice

**ไฟล์ที่ต้องแก้:**
| ไฟล์ | Action |
|------|--------|
| `StudentCardController.php` | แก้ไข methods: getStudentByRoom, dashboard, statistics, search + เพิ่ม levels, syncPreview, syncCommit |
| `routes/learn/academy-student-card.php` | เพิ่ม routes: levels, sync/preview, sync/commit, audit |
| `routes/studentcard/studentcard.php` | พิจารณา deprecation / auth |

---

### ระยะที่ 8: ปรับ Frontend `/student-card`

**เป้าหมาย:** เปลี่ยน hard-code เป็น dynamic + กรอง active

**8.1 แก้ `ui/pages/student-card/index.vue`:**

**ลบ:**
```ts
const mattayomLevels = [
    { id: 0, name: 'ม.1', rooms: 11, color: 'blue' },
    { id: 1, name: 'ม.2', rooms: 9, color: 'blue' },
    ...
]
```

**เพิ่ม:**
```ts
const { data: levels } = await useApi('/api/academies/{academy}/student-cards/levels')
// หรือ useFetch ตาม pattern ของ project
```

- แสดง loading state ระหว่างโหลด
- แสดง empty state ถ้าไม่มี levels
- แสดง error state ถ้า API fail (fallback เป็น hard-code?)

**8.2 แก้ `ui/pages/student-card/admin/index.vue`:**
- เหมือนกัน — ลบ hard-code, ดึงจาก API

**8.3 แก้ระบบ routing:**
- ปัจจุบัน URL ใช้ levelId+1 (0-indexed → 1-indexed)
- ถ้า API คืน level number เป็น integer (1-6) → routing ตรงอยู่แล้ว
- ตรวจว่า `[level]/[room].vue` ยัง parse ค่าถูกต้อง

**8.4 แสดงจำนวนนักเรียนในแต่ละห้อง:**
- API `levels` ควรคืน studentCount ต่อห้อง
- แสดงบน room button เป็น badge

**8.5 ไม่แสดง ม.6 เก่า:**
- ถ้า API filter ถูกต้อง (student_status = 'active') → frontend ไม่ต้องกรองเอง
- แต่ `levels` endpoint จะไม่คืน ม.6 ถ้าไม่มี active cards เหลือ

**8.6 เพิ่ม academy context:**
- ตอนนี้ public routes ไม่ระบุ academy
- ถ้ายังคง public routes → ต้องกำหนด default academy
- ถ้าย้ายไป academy-scoped → ต้องเปลี่ยน routing ทั้งชุด

**ไฟล์ที่ต้องแก้:**
| ไฟล์ | Action |
|------|--------|
| `ui/pages/student-card/index.vue` | แก้ไข — ลบ hard-code, ดึง API |
| `ui/pages/student-card/admin/index.vue` | แก้ไข — ลบ hard-code, ดึง API |
| `ui/pages/student-card/[level]/[room].vue` | ตรวจ — อาจไม่ต้องแก้ถ้า API filter ถูก |
| `ui/pages/student-card/admin/students/[level]/[room].vue` | ตรวจ — อาจไม่ต้องแก้ |

---

### ระยะที่ 9: เพิ่ม Card Sync UI ใน Academy Admin

**เป้าหมาย:** ให้ admin ทำ card sync ผ่านหน้าเว็บได้

**9.1 เพิ่มหน้า admin หรือ tab ใหม่:**

**ทางเลือก A (แนะนำ):** เพิ่ม section ใน `/academies/{name}/admin/student-cards/index.vue` ที่มีอยู่
**ทางเลือก B:** สร้างหน้าใหม่ `/academies/{name}/admin/student-cards/sync.vue`

**Workflow:**
1. เลือกปีการศึกษา (dropdown จาก academic_years)
2. กด "ตรวจสอบข้อมูล" → เรียก audit endpoint → แสดงสรุป
3. ถ้ามี anomaly → download CSV ตรวจ
4. กด "Preview Sync" → เรียก sync/preview → แสดง create/update/expire counts
5. ตรวจรายละเอียด (expandable sections)
6. กด "ยืนยัน Sync" → เรียก sync/commit → แสดง result
7. แสดง reconciliation summary

**9.2 UI Components ที่ต้องสร้าง:**

| Component | หน้าที่ |
|-----------|---------|
| `CardSyncPreviewPanel.vue` | แสดง preview: create/update/expire/unchanged counts |
| `CardSyncAuditSummary.vue` | แสดง audit report sections A/B/C |
| `CardSyncConfirmDialog.vue` | Dialog ยืนยัน commit พร้อมพิมพ์ชื่อปี |

**9.3 Composable:**

**ไฟล์:** `ui/composables/useStudentCardSync.ts`
```ts
// Methods
audit(academyId, yearId, levels?)
syncPreview(academyId, yearId)
syncCommit(academyId, yearId)
```

**ไฟล์ที่ต้องสร้าง:**
| ไฟล์ | Action |
|------|--------|
| `ui/composables/useStudentCardSync.ts` | สร้างใหม่ |
| `ui/components/school/CardSyncPreviewPanel.vue` | สร้างใหม่ |
| `ui/components/school/CardSyncAuditSummary.vue` | สร้างใหม่ |
| `ui/components/school/CardSyncConfirmDialog.vue` | สร้างใหม่ |
| `ui/pages/academies/[name]/admin/student-cards/index.vue` | แก้ไข — เพิ่ม sync section |

---

### ระยะที่ 10: ทดสอบ

**10.1 Backend Unit/Integration Tests:**

**ไฟล์ที่ต้องสร้าง:**
| ไฟล์ | ทดสอบ |
|------|-------|
| `tests/Feature/StudentCardSyncTest.php` | Sync service logic |
| `tests/Feature/StudentCardAuditTest.php` | Audit report accuracy |
| `tests/Feature/StudentCardApiTest.php` | API endpoint filtering |

**Test cases สำคัญ:**

```
✓ ม.6 ถูก graduate แล้ว บัตรถูก expire แต่ record ไม่ถูกลบ
✓ ม.6 ไม่ปรากฏใน active card API
✓ นักเรียนใหม่ ม.1 ได้ enrollment + บัตร + academic info
✓ นักเรียนใหม่ ม.4 จากภายนอก ได้บัตร แยกจากคนที่เลื่อนจาก ม.3
✓ คนเลื่อนจาก ม.3→ม.4 บัตรอัพเดท class_level ไม่ได้สร้างใหม่
✓ รัน sync ซ้ำ 2 รอบ → ไม่เกิดบัตรซ้ำ (idempotent)
✓ cross-academy access ถูกปฏิเสธ
✓ rollback/undo ทำงาน → บัตรกลับสถานะเดิม
✓ นักเรียนไม่มีรูป → สร้างบัตรได้ (profile_image = null)
✓ academic info มี is_current = true คนละ 1 record เท่านั้น
✓ student_cards.student_id มีค่าทุก record (ไม่ null)
✓ levels endpoint คืนข้อมูล dynamic ถูกต้อง
```

**10.2 Reconciliation Queries (รันหลัง commit):**

```sql
-- 1. active enrollment คนละไม่เกิน 1
SELECT student_id, COUNT(*) as cnt FROM classroom_students
WHERE status = 'active' GROUP BY student_id HAVING cnt > 1;
-- ต้องได้ 0 rows

-- 2. current academic info คนละ 1
SELECT student_id, COUNT(*) as cnt FROM student_academic_info
WHERE is_current = 1 GROUP BY student_id HAVING cnt > 1;
-- ต้องได้ 0 rows

-- 3. active student ทุกคนมีบัตร 1 ใบ
SELECT s.id, s.student_id, s.first_name_th FROM students s
JOIN classroom_students cs ON s.id = cs.student_id AND cs.status = 'active'
LEFT JOIN student_cards sc ON s.id = sc.student_id AND sc.student_status = 'active'
WHERE s.status = 'active' AND s.academy_id = 1 AND sc.id IS NULL;
-- ต้องได้ 0 rows

-- 4. active card ไม่มี ม.6 รุ่นเก่า
SELECT sc.* FROM student_cards sc
JOIN students s ON sc.student_id = s.id
WHERE s.status = 'graduated' AND sc.student_status = 'active';
-- ต้องได้ 0 rows

-- 5. จำนวนบัตร active ตรงกับ enrollment
SELECT c.grade_level, c.section,
       (SELECT COUNT(*) FROM classroom_students cs2 WHERE cs2.classroom_id = c.id AND cs2.status = 'active') as enrollment_count,
       (SELECT COUNT(*) FROM student_cards sc2 WHERE sc2.class_level = CAST(SUBSTRING(c.grade_level, 3) AS UNSIGNED) AND sc2.class_section = CAST(c.section AS UNSIGNED) AND sc2.academy_id = 1 AND sc2.student_status = 'active') as card_count
FROM classrooms c
WHERE c.academy_id = 1 AND c.academic_year_id = {current_year_id}
ORDER BY c.grade_level, CAST(c.section AS UNSIGNED);
-- enrollment_count ต้องเท่ากับ card_count ทุกแถว

-- 6. ไม่มีบัตรข้าม academy
SELECT sc.id, sc.student_id, sc.academy_id, s.academy_id as student_academy_id
FROM student_cards sc
JOIN students s ON sc.student_id = s.id
WHERE sc.academy_id != s.academy_id;
-- ต้องได้ 0 rows
```

**10.3 Browser Smoke Test:**

| # | ทดสอบ | URL | คาดหวัง |
|---|-------|-----|---------|
| 1 | หน้ารวมแสดงชั้นเรียนจาก API | `/student-card` | ชั้น/ห้อง dynamic, ไม่มี ม.6 ถ้าไม่มี active cards |
| 2 | รายการห้องแสดงนักเรียน active | `/student-card/1/1` | แสดงเฉพาะ ม.1/1 ที่ active |
| 3 | ค้นหานักเรียนใหม่ ม.1 | search bar | พบชื่อ + แสดงบัตรถูกต้อง |
| 4 | ค้นหานักเรียน ม.6 เก่า | search bar | ไม่พบ (ถ้า status filter ทำงาน) |
| 5 | หน้ารายละเอียดบัตร | `/student-card/profile/{id}` | ชื่อ/ชั้น/ห้อง/รูป/QR ถูกต้อง |
| 6 | QR Code | scan QR | เปิด profile URL ถูกต้อง |
| 7 | Admin หน้า sync | academy admin | audit + preview + commit ทำงาน |
| 8 | Mobile layout | resize browser | responsive ถูกต้อง |
| 9 | สิทธิ์ admin/non-admin | login ต่าง role | non-admin ไม่เห็น admin buttons |

---

## ลำดับการปล่อยใช้งาน (Deployment Checklist)

```
 1. ☐ สำรองฐานข้อมูล (mysqldump)
 2. ☐ ตอบคำถามระยะ 0 ให้ครบ (นโยบายข้อมูล)
 3. ☐ รัน Schema Audit queries (ระยะ 1) → แก้ migration ถ้าจำเป็น
 4. ☐ รัน php artisan migrate (migration ใหม่ถ้ามี)
 5. ☐ รัน StudentsBackfillCardLink (ถ้ามี student_cards.student_id = NULL)
 6. ☐ รัน SyncStudentRelatedTables (ถ้ามี academy_id = NULL)
 7. ☐ เตรียมปีการศึกษาใหม่ + ห้องเรียนผ่าน Rollover wizard (ระยะ 3)
 8. ☐ Preview rollover (ระยะ 4) → ให้นายทะเบียนตรวจ
 9. ☐ Commit rollover (ระยะ 5) → ตรวจ reconciliation
10. ☐ Deploy StudentCardSyncService + API changes (ระยะ 6-7)
11. ☐ รัน Audit report (ระยะ 2) → ตรวจ anomaly
12. ☐ Preview card sync → ให้นายทะเบียนตรวจ
13. ☐ Commit card sync → ตรวจ reconciliation queries
14. ☐ Deploy frontend changes (ระยะ 8)
15. ☐ ทดสอบ browser (ระยะ 10.3)
16. ☐ สุ่มเทียบข้อมูลรายคน (5-10 คนต่อชั้น)
17. ☐ Deploy Card Sync UI (ระยะ 9) — ทำทีหลังได้
18. ☐ ปิด undo window เมื่อยืนยันว่าถูกต้อง
19. ☐ เก็บ rollover batch + audit report เป็นหลักฐาน
```

---

## สรุปไฟล์ทั้งหมดที่ต้องสร้าง/แก้ไข

### ไฟล์ใหม่ (สร้าง)
| # | ไฟล์ | ระยะ |
|---|------|------|
| 1 | `app/Services/StudentCardAuditService.php` | 2 |
| 2 | `app/Services/StudentCardSyncService.php` | 6 |
| 3 | `app/Console/Commands/StudentCardAudit.php` | 2 |
| 4 | `app/Console/Commands/SyncStudentCards.php` | 6 |
| 5 | `database/migrations/xxxx_fix_student_cards_status_column.php` | 1 (ถ้าจำเป็น) |
| 6 | `database/migrations/xxxx_add_student_cards_indexes.php` | 1 (ถ้าจำเป็น) |
| 7 | `ui/composables/useStudentCardSync.ts` | 9 |
| 8 | `ui/components/school/CardSyncPreviewPanel.vue` | 9 |
| 9 | `ui/components/school/CardSyncAuditSummary.vue` | 9 |
| 10 | `ui/components/school/CardSyncConfirmDialog.vue` | 9 |
| 11 | `tests/Feature/StudentCardSyncTest.php` | 10 |
| 12 | `tests/Feature/StudentCardAuditTest.php` | 10 |
| 13 | `tests/Feature/StudentCardApiTest.php` | 10 |

### ไฟล์แก้ไข
| # | ไฟล์ | ระยะ | สิ่งที่แก้ |
|---|------|------|----------|
| 1 | `StudentCardController.php` | 2,6,7 | เพิ่ม audit, syncPreview, syncCommit, levels + แก้ getStudentByRoom, dashboard, statistics, search |
| 2 | `routes/learn/academy-student-card.php` | 2,6,7 | เพิ่ม routes: audit, sync/preview, sync/commit, levels |
| 3 | `ui/pages/student-card/index.vue` | 8 | ลบ hard-code levels, ดึงจาก API |
| 4 | `ui/pages/student-card/admin/index.vue` | 8 | ลบ hard-code levels, ดึงจาก API |
| 5 | `ui/pages/academies/[name]/admin/student-cards/index.vue` | 9 | เพิ่ม sync section/tab |
| 6 | `routes/studentcard/studentcard.php` | 7 | พิจารณา deprecation / auth |

### ไฟล์ไม่ต้องแก้ (ใช้ที่มีอยู่)
- `AcademicYearRolloverService.php` — ใช้ตรงๆ ไม่ต้องแก้
- `StudentEnrollmentService.php` — ใช้ตรงๆ ไม่ต้องแก้
- `RolloverController.php` — ใช้ตรงๆ
- `ui/pages/academies/[name]/admin/gradebook/rollover/index.vue` — ใช้ตรงๆ
- `StudentCard.php` model — อาจแก้เล็กน้อย (scope active)

---

## หัวใจของแผน

> **"ไม่ลบประวัติ, ใช้ระบบ rollover ที่มีอยู่, ให้บัตรเป็นข้อมูลอนุพันธ์ (derived), preview ก่อน commit, ตรวจยอดหลังทำทุกครั้ง"**

- Rollover จัดการ **ข้อมูลทะเบียน** (students, classroom_students, student_academic_info)
- Card Sync จัดการ **ข้อมูลบัตร** (student_cards) เป็นขั้นตอนแยกหลัง rollover
- ทั้งสองขั้นตอนมี preview ก่อน commit
- ทั้งสองขั้นตอนตรวจ reconciliation หลัง commit
- ไม่มีการลบข้อมูล — เปลี่ยนสถานะเท่านั้น

# แผนปรับปรุงระบบบัตรนักเรียน — ฉบับสมบูรณ์

## Implementation update — 2026-07-08

- **Roster Reconciliation Fixes**: Resolved feedback items:
  - M1: Saved `source_academic_year_id` inside `diff_data` during the `preview()` step for both `promote_student` and `repeat_student` actions.
  - M3: Added synchronization of `student_number` (class sequence index) for `unchanged`, `new_intake`, `promote_student`, `repeat_student`, and `re_enroll` actions.
  - M4: Deduplicated batch counters update logic by reusing `StudentImportService::refreshCounters`.
  - M5: Added database migration `2026_07_08_000002_add_remarks_to_students_table` to support storing intake incomplete flags in `remarks` and added `remarks` to the `Student` fillable array.
  - N6: Added type safety to `useStudentCardRequests` by replacing `any` casts with explicit types.
  - Test Verification: Wrote additional feature tests for `unchanged` number update, `auto_graduate` for ม.6, and `ambiguous` teacher matching. All 26 assertions pass.

## Implementation update — 2026-07-06

- 2026-07-08 topic youtube integration: Created centralized YouTube URL parser utility `ui/utils/youtube.ts` and refactored `LessonPost.vue` & `VideoModal.vue`. Added a responsive 16:9 video preview section and modal integration in `TopicAccordion.vue` with robust fallbacks for broken/missing URLs and maxresdefault thumbnails. Build successful.
- 2026-07-08 migration verification: `2026_07_08_000001_create_student_card_requests_table.php` ran successfully in batch 79. It now explicitly uses InnoDB and matches the signed integer key type of `student_cards.id`. Verified the table, unique index, foreign keys, and `academy_settings.card_request_flow_enabled`.
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

---

## 2026-07-07 - Updated student roster XLSX analysis

- Source: `docs/api/20260707150052.xlsx`, sheet `Student List`, 2,437 data rows and 53 columns.
- File quality: no blank/duplicate student codes or citizen IDs; all 13-digit citizen IDs passed checksum; required names and classroom labels are populated.
- Target context inferred from database: academy 1 (`เพลินวิทยาธาร`), academic year 2 (`2569`). Read-only checks only; no student data was changed.
- Identity preview: 1,839 match on both keys, 462 match student code with blank DB citizen ID, 125 are new, and 11 have conflicting identity matches requiring manual quarantine.
- Enrollment preview for code matches: 1,700 already in the same class, 170 differ, 442 have no active 2569 enrollment.
- The XLSX contains 70 classrooms. Sixteen primary/kindergarten classrooms are absent from the current-year DB; these must be created/approved before commit.
- Existing `StudentImportService` accepts CSV and rejects existing identities, so it is not safe for this update as-is. Plan an XLSX adapter plus update-capable preview/commit workflow with per-row transactions, audit trail, idempotency, and rollback evidence.
- Map canonical data into `students`, `classroom_students`, `student_academic_info`, `student_addresses`, `student_contacts`, `student_guardians`/guardian contacts, and `student_health_info`; run card sync only after roster reconciliation.
- Verification plan: preview category totals, manually resolve 11 conflicts, back up DB, commit in batches, reconcile identity/enrollment/academic-info counts, then sample records by classroom.

### 2026-07-07 roster runtime/schema fixes

- Fixed post-roster card sync to run once per completed batch instead of once per row in both CLI and queued import paths.
- Identity changes are now committed independently of enrollment classification, preserving citizen-ID fills during move/create/unchanged actions.
- Added missing roster service imports and the explicit Laravel `Log` facade import.
- Corrected address and health upsert keys/field names to match their schemas.
- Extended the integration test to cover identity fill during classroom movement, address/health persistence, and exactly one card-sync call.
- Verification: roster date/parser/integration suites pass (3 tests, 38 assertions); scoped Pint check passes.

---

## แผนอัปเดตรายชื่อนักเรียนจาก XLSX — ฉบับสมบูรณ์

**วันที่วางแผน:** 2026-07-07
**สถานะ:** วางแผนเสร็จ (ยังไม่เริ่มพัฒนา)
**ขอบเขต:** นำไฟล์ XLSX รายชื่อนักเรียน 2,437 คน อัปเดตลงฐานข้อมูลปีการศึกษา 2569

### ข้อมูลต้นทาง (XLSX)

| รายการ | ค่า |
|--------|-----|
| ไฟล์ | `docs/api/20260707150052.xlsx` |
| จำนวนแถว | 2,437 |
| จำนวนคอลัมน์ | 53 |
| จำนวนห้องเรียน | 70 (อ.1–อ.3, ป.1–ป.6, ม.1–ม.6) |
| สถานะ "กำลังศึกษาอยู่" | 1,626 คน |
| สถานะ "นักเรียนเข้าใหม่" | 811 คน |
| รูปแบบวันที่ | ไทยย่อ+พ.ศ. เช่น `08 เม.ย. 57`, `07 พ.ค. 2569` |
| Academy | 1 (เพลินวิทยาธาร) |
| ปีการศึกษาเป้าหมาย | 2 (2569) |

### การแจกแจงตามระดับ (จาก XLSX)

| ระดับ | จำนวน | เข้าใหม่ | กำลังศึกษา | หมายเหตุ |
|-------|--------|----------|------------|----------|
| อ.1–อ.3 | 102 | 48 | 54 | **ห้องเรียนยังไม่มีใน DB** |
| ป.1–ป.6 | 385 | 74 | 311 | **ห้องเรียนยังไม่มีใน DB (12 ห้อง)** |
| ม.1 | 395 | 389 | 6 | เกือบทั้งหมดเข้าใหม่ |
| ม.2 | 399 | 8 | 391 | |
| ม.3 | 329 | 4 | 325 | |
| ม.4 | 288 | 282 | 6 | เกือบทั้งหมดเข้าใหม่ (จาก ม.3 อื่นโรงเรียน) |
| ม.5 | 258 | 6 | 252 | |
| ม.6 | 281 | 0 | 281 | ทั้งหมดกำลังศึกษา |

### ผลการเทียบกับ DB (Read-only)

| กลุ่ม | จำนวน | คำอธิบาย |
|-------|--------|----------|
| exact_match | 1,839 | ตรงทั้ง student_code + citizen_id |
| code_only | 462 | student_code ตรง แต่ DB ยังไม่มี citizen_id → เติมได้ |
| new_student | 125 | ไม่มีใน DB เลย → สร้างใหม่ |
| conflict | 11 | student_code ชี้คนหนึ่ง citizen_id ชี้อีกคน → กักตรวจ |
| same_class | 1,700 | enrollment ปี 2569 ตรงกับ XLSX |
| diff_class | 170 | enrollment ปี 2569 ห้องไม่ตรง → ย้ายห้อง |
| no_enrollment | 442 | มี student record แต่ไม่มี enrollment 2569 → สร้าง |
| missing_classroom | 16 | ห้องเรียนที่ XLSX มี แต่ DB ไม่มี (อ./ป.) |

---

### ระยะที่ 1: สร้าง XLSX Parser + Thai Date Normalizer

**เป้าหมาย:** แปลงไฟล์ XLSX 53 คอลัมน์เป็น normalized struct ที่พร้อมเทียบกับ DB

**1.1 สร้าง `StudentRosterXlsxParser`**

**ไฟล์:** `app/Services/Import/StudentRosterXlsxParser.php`

**หน้าที่:**
- อ่าน XLSX ด้วย `PhpSpreadsheet` (ต้อง `composer require phpoffice/phpspreadsheet`)
- หรือใช้ `Maatwebsite\Excel` ที่มีอยู่แล้วในโปรเจค

**Input:** path ไปยัง XLSX file
**Output:** `Collection` ของ normalized array

**Column mapping (53 XLSX cols → normalized keys):**

```php
$columnMap = [
    'เลขประจำตัวประชาชน'        => 'citizen_id',         // col 1
    'เลขประจำตัวนักเรียน '       => 'student_code',       // col 2 (มีเว้นวรรคต่อท้าย!)
    'ชั้นเรียน'                  => 'classroom_label',    // col 3 → split เป็น grade_level + section
    'คำนำหน้าชื่อ'               => 'title_prefix_th',    // col 4
    'ชื่อ'                       => 'first_name_th',      // col 5
    'นามสกุล'                    => 'namsagul',           // col 6
    'ชื่อกลาง'                   => 'middle_name_th',     // col 7
    'คำนำหน้าชื่อ.1'             => 'title_prefix_en',    // col 8
    'ชื่อภาษาอังกฤษ'             => 'first_name_en',      // col 9
    'นามสกุลภาษาอังกฤษ'          => 'last_name_en',       // col 10
    'ชื่อกลางภาษาอังกฤษ'         => 'middle_name_en',     // col 11
    'ว.ด.ป. เกิด'               => 'birth_date_raw',     // col 12 → parse Thai date
    'เพศ'                        => 'gender_raw',         // col 13 (ชาย/หญิง)
    'สัญชาติ'                    => 'nationality',        // col 14
    'ศาสนา'                      => 'religion',           // col 15
    'สถานะนักเรียน'              => 'student_status_raw', // col 16
    'วันที่บันทึก'               => 'record_date_raw',    // col 17
    'ประเภทความพิการ'            => 'disability_type',    // col 18
    // --- ที่อยู่ ---
    'รหัสประจำบ้าน'              => 'house_code',         // col 19
    'บ้านเลขที่'                 => 'house_number',       // col 20
    'หมู่ที่'                    => 'village_number',     // col 21
    'ซอย'                        => 'alley',              // col 22
    'ถนน'                        => 'road',               // col 23
    'ตำบล/แขวง'                  => 'subdistrict',        // col 24
    'อำเภอ/เขต'                  => 'district',           // col 25
    'จังหวัด'                    => 'province',           // col 26
    'รหัสไปรษณีย์'               => 'postal_code',        // col 27
    'เบอร์โทรศัพท์'              => 'phone',              // col 28
    'วันที่เข้าเรียน'            => 'enrollment_date_raw',// col 29
    // --- บิดา ---
    'เลขประจำตัวประชาชน (บิดา)'  => 'father_citizen_id',  // col 30
    'คำนำหน้าชื่อ (บิดา)'       => 'father_title',       // col 31
    'ชื่อ  (บิดา)'              => 'father_first_name',  // col 32
    'นามสกุล  (บิดา)'           => 'father_last_name',   // col 33
    'สถานภาพของบิดา'            => 'father_status',      // col 34
    'สัญชาติ.1'                  => 'father_nationality', // col 35
    // --- มารดา ---
    'เลขประจำตัวประชาชน (มารดา)' => 'mother_citizen_id',  // col 36
    'คำนำหน้าชื่อ (มารดา)'      => 'mother_title',       // col 37
    'ชื่อ (มารดา)'              => 'mother_first_name',  // col 38
    'นามสกุล (มารดา)'           => 'mother_last_name',   // col 39
    'สถานภาพของมารดา'           => 'mother_status',      // col 40
    'สัญชาติ.2'                  => 'mother_nationality', // col 41
    // --- ผู้ปกครอง ---
    'เลขประจำตัวประชาชน (ผู้ปกครอง)' => 'guardian_citizen_id',  // col 42
    'คำนำหน้าชื่อ.2'                => 'guardian_title',       // col 43
    'ชื่อ - นามสกุล'                => 'guardian_full_name',   // col 44 → split first/last
    'อาชีพของผู้ปกครอง'             => 'guardian_occupation',  // col 45
    'เบอร์โทรศัพท์.1'               => 'guardian_phone',       // col 46
    'ความสัมพันธ์'                  => 'guardian_relationship', // col 47
    // --- สุขภาพ ---
    'ความสูง (ซม.)'              => 'height_cm',          // col 48
    'น้ำหนัก (กก.)'              => 'weight_kg',          // col 49
    // --- โรงเรียนเดิม ---
    'ชื่อโรงเรียนเดิม'           => 'previous_school',    // col 50
    'จังหวัดโรงเรียนเดิม'        => 'previous_school_province', // col 51
    'ชั้นเรียน.1'                => 'previous_grade',     // col 52
];
```

**1.2 สร้าง Thai Date Parser**

**ไฟล์:** `app/Services/Import/ThaiDateParser.php`

**รูปแบบที่ต้องรองรับ:**

| ตัวอย่าง | ความหมาย | ผลลัพธ์ (ค.ศ.) |
|----------|----------|----------------|
| `08 เม.ย. 57` | 2-digit พ.ศ. (2557) | `2014-04-08` |
| `23 ก.ย. 51` | 2-digit พ.ศ. (2551) | `2008-09-23` |
| `07 พ.ค. 2569` | 4-digit พ.ศ. | `2026-05-07` |
| `15 พ.ค. 2569` | 4-digit พ.ศ. | `2026-05-15` |

**Thai month abbreviation mapping:**
```php
$thaiMonths = [
    'ม.ค.'  => 1,  'ก.พ.'  => 2,  'มี.ค.' => 3,  'เม.ย.' => 4,
    'พ.ค.'  => 5,  'มิ.ย.' => 6,  'ก.ค.'  => 7,  'ส.ค.'  => 8,
    'ก.ย.'  => 9,  'ต.ค.'  => 10, 'พ.ย.'  => 11, 'ธ.ค.'  => 12,
];
```

**Logic:**
1. Regex match: `/^(\d{1,2})\s+(\S+)\s+(\d{2,4})$/u`
2. Map Thai month → int
3. ถ้าปี ≤ 99 → เติม 2500 (พ.ศ. 2 หลัก) → ลบ 543 (แปลง ค.ศ.)
4. ถ้าปี > 2400 → ลบ 543 (พ.ศ. 4 หลัก)
5. ถ้าปี < 100 และ > 40 → สันนิษฐาน 25xx → ลบ 543
6. Return Carbon date หรือ null ถ้า parse ไม่ได้

**1.3 Classroom Label Splitter**

**ไฟล์:** อยู่ใน Parser เดียวกัน

**Logic:** `ม.1/5` → `['grade_level' => 'ม.1', 'section' => '5']`
```php
preg_match('/^([^\/]+)\/(\d+)$/', $label, $m);
// $m[1] = 'ม.1', $m[2] = '5'
```

**⚠️ ข้อควรระวัง:**
- คอลัมน์ `เลขประจำตัวนักเรียน ` มีช่องว่างต่อท้าย (trailing space) — ต้อง trim ชื่อคอลัมน์
- คอลัมน์ `ชื่อ  (บิดา)` และ `นามสกุล  (บิดา)` มีเว้นวรรค 2 ตัว — ต้อง normalize whitespace
- `ชื่อ - นามสกุล` ของผู้ปกครอง เป็น full name ชิ้นเดียว → ต้อง split by space
- ค่า `gender_raw` เป็น `ชาย` / `หญิง` → map เป็น `1` / `0`
- ค่า `father_status` / `mother_status` เป็น `มีชีวิต` / `เสียชีวิต` → map เป็น `alive` / `deceased`

**1.4 Row Validation**

แต่ละแถวต้องผ่าน validation:
```
- citizen_id: required, digits:13, Thai checksum pass
- student_code: required, string, max:20
- first_name_th: required, string, max:100
- last_name_th: required, string, max:100
- classroom_label: required, pattern /^[^\\/]+\/\d+$/
- birth_date: required, valid date, before today
- gender: required, in:ชาย,หญิง
```

**ผลลัพธ์ระยะ 1:**
- `StudentRosterXlsxParser` ที่แปลง XLSX → Collection ของ normalized structs
- `ThaiDateParser` ที่แปลงวันที่ไทยได้ทุกรูปแบบ
- ข้อมูลแต่ละแถวมี status: `valid`, `warning`, `invalid`

**ไฟล์ที่ต้องสร้าง:**
| ไฟล์ | Action |
|------|--------|
| `app/Services/Import/StudentRosterXlsxParser.php` | สร้างใหม่ |
| `app/Services/Import/ThaiDateParser.php` | สร้างใหม่ |

---

### ระยะที่ 2: สร้าง Identity Matcher + Preview Batch

**เป้าหมาย:** เทียบข้อมูล XLSX กับ DB แล้วจำแนกแต่ละแถวเป็นกลุ่ม action

**2.1 สร้าง `StudentRosterUpdateService`**

**ไฟล์:** `app/Services/Import/StudentRosterUpdateService.php`

**Method หลัก:**
```php
public function preview(
    Academy $academy,
    AcademicYear $year,
    Collection $parsedRows
): StudentRosterUpdateBatch
```

**Logic ของ Identity Matching (ต่อแถว):**

```
1. ค้นหา Student ด้วย student_code (students.student_id) ภายใน academy
2. ค้นหา Student ด้วย citizen_id (students.citizen_id) ภายใน academy
3. จำแนก:

   a) ทั้ง student_code + citizen_id ตรงกัน → MATCHED
      → เทียบ enrollment + ข้อมูลส่วนตัว ต่อ

   b) student_code ตรง + DB ไม่มี citizen_id → CODE_ONLY_MATCH
      → เตรียม fill citizen_id
      → เทียบ enrollment + ข้อมูลส่วนตัว ต่อ

   c) ไม่พบทั้ง student_code + citizen_id → NEW_STUDENT
      → สร้าง student + enrollment + ข้อมูลทั้งหมด

   d) student_code ชี้คนหนึ่ง, citizen_id ชี้อีกคน → CONFLICT
      → กักไว้ ห้ามอัปเดตอัตโนมัติ
      → ต้องแก้ด้วยคน

   e) ไม่มี student_code แต่มี citizen_id ตรง → CITIZEN_MATCH
      → อัปเดต student_code ให้ตรง + ต่อ enrollment
```

**2.2 Enrollment Classification (สำหรับ MATCHED / CODE_ONLY_MATCH):**

หลังจับคู่ตัวตนได้แล้ว ตรวจ enrollment:

```
1. ค้นหา active enrollment ของ student ในปี target (academic_year_id = 2)
2. จำแนก:

   a) มี enrollment + ห้องเดิม → UNCHANGED
      → เทียบข้อมูลส่วนตัว (ชื่อ/ที่อยู่/ผู้ปกครอง) → ถ้าต่าง → UPDATE_PERSONAL

   b) มี enrollment + ห้องต่าง → MOVE_CLASSROOM
      → ย้ายจากห้องเดิมไปห้องใหม่

   c) ไม่มี enrollment ปี target → CREATE_ENROLLMENT
      → สร้าง enrollment ใหม่ (ไม่ลบ enrollment เก่าปีก่อน)
```

**2.3 รูปแบบ Preview Batch:**

ขยาย `student_import_batches` + `student_import_rows` ที่มีอยู่ หรือสร้าง table ใหม่ `student_roster_update_batches` + `student_roster_update_rows`:

**แนะนำ: ใช้ table เดิม** (`student_import_batches` + `student_import_rows`) เพิ่ม field:

```
student_import_batches:
  + import_type ENUM('new_intake', 'roster_update') DEFAULT 'new_intake'
  + source_format ENUM('csv', 'xlsx') DEFAULT 'csv'

student_import_rows:
  + action ENUM('unchanged','update_identity','update_personal','move_classroom',
                'create_enrollment','new_student','conflict') DEFAULT NULL
  + matched_student_id BIGINT UNSIGNED NULL  -- FK → students.id ที่จับคู่ได้
  + diff_data JSON NULL  -- เก็บ before/after ของ field ที่ต่าง
```

**2.4 Preview Summary ที่ต้องแสดง:**

```json
{
  "batch_id": "uuid",
  "total_rows": 2437,
  "by_action": {
    "unchanged": 1530,
    "update_identity": 462,
    "update_personal": 170,
    "move_classroom": 170,
    "create_enrollment": 442,
    "new_student": 125,
    "conflict": 11,
    "invalid": 0
  },
  "missing_classrooms": [
    {"label": "อ.1/1", "student_count": 22},
    {"label": "ป.1/1", "student_count": 27}
  ],
  "conflicts": [
    {"row": 45, "student_code": "1234", "xlsx_citizen": "1234567890123",
     "db_student_code_points_to": "student_id=100",
     "db_citizen_id_points_to": "student_id=200"}
  ]
}
```

**ไฟล์ที่ต้องสร้าง/แก้:**
| ไฟล์ | Action |
|------|--------|
| `app/Services/Import/StudentRosterUpdateService.php` | สร้างใหม่ |
| `database/migrations/xxxx_add_roster_update_fields_to_import_tables.php` | สร้างใหม่ |
| `app/Models/StudentImportBatch.php` | แก้ — เพิ่ม cast/fillable |
| `app/Models/StudentImportRow.php` | แก้ — เพิ่ม cast/fillable |

---

### ระยะที่ 3: สร้าง Artisan Command (CLI workflow)

**เป้าหมาย:** ทำให้ admin รัน preview/commit ผ่าน CLI ก่อน มี UI ทีหลัง

**3.1 สร้าง Command: `roster:preview`**

**ไฟล์:** `app/Console/Commands/RosterPreviewCommand.php`
**Signature:** `roster:preview {file} {--academy=1} {--year=2} {--export-conflicts=}`

**ขั้นตอน:**
1. อ่าน XLSX ด้วย `StudentRosterXlsxParser`
2. Validate ทุกแถว → แสดง invalid rows (ถ้ามี)
3. รัน `StudentRosterUpdateService::preview()`
4. แสดง summary table บน console
5. ถ้า `--export-conflicts` → export ไฟล์ CSV ของ 11 conflicts ให้นายทะเบียนตรวจ
6. บันทึก batch ลง DB (status = `previewed`)

**3.2 สร้าง Command: `roster:commit`**

**ไฟล์:** `app/Console/Commands/RosterCommitCommand.php`
**Signature:** `roster:commit {batch_id} {--dry-run} {--chunk=50}`

**ขั้นตอน:**
1. โหลด batch จาก DB → ตรวจว่า status = `previewed`
2. ตรวจ prerequisites (missing classrooms ต้อง = 0, conflicts ต้อง resolved)
3. ถ้า `--dry-run` → แสดงสรุปแล้วหยุด
4. ถามยืนยัน: "จะอัปเดตนักเรียน {n} คน ในปีการศึกษา 2569 ยืนยันหรือไม่? (yes/no)"
5. ประมวลผลเป็น chunk (default 50 แถว/transaction)
6. แสดง progress bar
7. แสดง reconciliation summary เมื่อเสร็จ

**ไฟล์ที่ต้องสร้าง:**
| ไฟล์ | Action |
|------|--------|
| `app/Console/Commands/RosterPreviewCommand.php` | สร้างใหม่ |
| `app/Console/Commands/RosterCommitCommand.php` | สร้างใหม่ |

---

### ระยะที่ 4: Commit Logic — เขียนข้อมูลลง 7 ตาราง

**เป้าหมาย:** อัปเดต/สร้างข้อมูลใน DB ตาม action ที่จำแนกไว้

**4.1 ตารางที่ 1: `students` (ข้อมูลหลัก)**

| Action | สิ่งที่ทำ |
|--------|----------|
| NEW_STUDENT | สร้าง record ใหม่ด้วย `StudentIntakeService::intake()` ที่มีอยู่ (reuse!) |
| UPDATE_IDENTITY | `students.citizen_id = xlsx.citizen_id` (เติม citizen_id ที่ว่าง) |
| UPDATE_PERSONAL | อัปเดต field ที่ต่าง: `title_prefix_th`, `first_name_th`, `last_name_th`, `first_name_en`, `last_name_en`, `date_of_birth`, `gender`, `nationality`, `religion` |
| MOVE_CLASSROOM | อัปเดต `students.class_level`, `students.class_section` ให้ตรงห้องใหม่ |

**⚠️ กฎสำคัญ:**
- ห้ามเขียนทับ `students.profile_image` — รูปมาจากอีกช่องทาง
- ห้ามเปลี่ยน `students.user_id`, `students.academy_id`, `students.account_status`
- เก็บ before/after ไว้ใน `student_import_rows.diff_data`

**4.2 ตารางที่ 2: `classroom_students` (enrollment)**

| Action | สิ่งที่ทำ |
|--------|----------|
| CREATE_ENROLLMENT | ใช้ `StudentEnrollmentService::enrollStudent()` ที่มีอยู่ |
| MOVE_CLASSROOM | 1) ปิด enrollment เดิม (status → `transferred`) 2) สร้าง enrollment ใหม่ในห้องที่ถูกต้อง |
| UNCHANGED | ไม่ทำอะไร |

**⚠️ กฎสำคัญ:**
- ห้ามลบ enrollment เดิม — เปลี่ยนสถานะเท่านั้น
- ต้องมี `classroom_id` ที่ valid (ห้องต้องมีอยู่ใน DB ก่อน)
- enrollment ใหม่ต้องตั้ง `academic_year_id` = ปี target

**4.3 ตารางที่ 3: `student_academic_info`**

| Action | สิ่งที่ทำ |
|--------|----------|
| CREATE_ENROLLMENT / MOVE_CLASSROOM | ใช้ `StudentEnrollmentService` ที่จัดการ academic_info ให้อัตโนมัติ (มี `manageAcademicInfoSnapshot` อยู่แล้ว) |

**4.4 ตารางที่ 4: `student_addresses`**

**XLSX → DB mapping:**

| XLSX col | → DB field |
|----------|-----------|
| `บ้านเลขที่` | `house_number` |
| `หมู่ที่` | `village_number` |
| `ซอย` | `alley` |
| `ถนน` | `road` |
| `ตำบล/แขวง` | `subdistrict` |
| `อำเภอ/เขต` | `district` |
| `จังหวัด` | `province` |
| `รหัสไปรษณีย์` | `postal_code` |

**Logic:**
- ค้นหา `student_addresses` ที่ `student_id` ตรง + `address_type = 'current'` + `is_current = true`
- ถ้ามี → เทียบแต่ละ field → อัปเดตถ้าต่าง
- ถ้าไม่มี → สร้างใหม่ (`address_type = 'current'`, `is_current = true`)
- ⚠️ ค่า `-` ใน XLSX ให้ถือว่า null (ซอย/ถนนมักเป็น `-`)

**4.5 ตารางที่ 5: `student_guardians`**

**XLSX มี 3 ชุดข้อมูลผู้เกี่ยวข้อง:**

**ชุดที่ 1 — บิดา (cols 30-35):**
```
→ student_guardians (guardian_type = 'father')
  citizen_id    = father_citizen_id
  title_prefix  = father_title
  first_name    = father_first_name
  last_name     = father_last_name
  status        = father_status → map('มีชีวิต'=>'alive', 'เสียชีวิต'=>'deceased')
  nationality   = father_nationality
```

**ชุดที่ 2 — มารดา (cols 36-41):**
```
→ student_guardians (guardian_type = 'mother')
  citizen_id    = mother_citizen_id
  title_prefix  = mother_title
  first_name    = mother_first_name
  last_name     = mother_last_name
  status        = mother_status → map('มีชีวิต'=>'alive', 'เสียชีวิต'=>'deceased')
  nationality   = mother_nationality
```

**ชุดที่ 3 — ผู้ปกครอง (cols 42-47):**
```
→ student_guardians (guardian_type = 'guardian')
  citizen_id          = guardian_citizen_id
  title_prefix        = guardian_title
  first_name + last_name = split(guardian_full_name)  ← "ชื่อ - นามสกุล" → split by space
  occupation          = guardian_occupation
  relationship        = guardian_relationship (มารดา, บิดา, ฯลฯ)
  is_primary_contact  = true
```

**Logic:**
- ค้นหา guardian ที่ `student_id` + `guardian_type` ตรง
- ถ้ามี → เทียบ field → อัปเดตถ้าต่าง
- ถ้าไม่มี → สร้างใหม่
- ถ้า XLSX ไม่มีข้อมูลของ type นั้น (null ทุก field) → ข้าม ไม่ลบของเดิม

**4.6 ตารางที่ 6: `student_contacts`**

**XLSX มี 2 เบอร์:**
- `เบอร์โทรศัพท์` (col 28) → contact ของนักเรียน
- `เบอร์โทรศัพท์.1` (col 46) → contact ของผู้ปกครอง

**Logic สำหรับ student contact:**
- ค้นหา `student_contacts` ที่ `student_id` ตรง + `contact_type = 'mobile'` + `is_primary = true`
- ถ้ามี → เทียบ `contact_value` → อัปเดตถ้าต่าง
- ถ้าไม่มี + XLSX มีเบอร์ → สร้างใหม่

**Logic สำหรับ guardian contact:**
- เบอร์ผู้ปกครอง เก็บใน guardian record เลย (ไม่ใช่ `student_contacts`)
- ⚠️ ดูว่า guardian model มี `phone_number` field ไหม → migration `2026_02_01` มี! ใช้ได้

**4.7 ตารางที่ 7: `student_health_info`**

**XLSX → DB mapping:**

| XLSX col | → DB field |
|----------|-----------|
| `ความสูง (ซม.)` | `height_cm` (decimal 5,2) |
| `น้ำหนัก (กก.)` | `weight_kg` (decimal 5,2) |

**Logic:**
- `student_health_info` มี unique constraint on `student_id`
- ค้นหา record ที่ `student_id` ตรง
- ถ้ามี → อัปเดต height/weight
- ถ้าไม่มี → สร้างใหม่
- ⚠️ บาง weight เป็นทศนิยม (เช่น `35.3`) → ต้อง cast เป็น decimal

**4.8 Transaction Strategy:**

```
foreach (batch->rows->chunk(50) as $chunk) {
    DB::transaction(function () use ($chunk) {
        foreach ($chunk as $row) {
            if ($row->action === 'conflict' || $row->action === 'invalid') {
                continue; // ข้าม
            }
            $this->processRow($row);
            $row->update(['status' => 'imported']);
        }
    });
    // ถ้า transaction ของ chunk ใด fail → mark rows เป็น 'failed'
    // chunk อื่นทำต่อได้ (partial commit)
}
```

**⚠️ ข้อพิจารณาที่ต้องตัดสินใจ:**
1. **Partial commit หรือ All-or-nothing?**
   - แนะนำ: Chunk-level transaction (50 แถว/chunk) — ถ้า chunk ใด fail ไม่กระทบ chunk อื่น
   - แต่ถ้าต้องการ all-or-nothing → ห่อ chunk ทั้งหมดใน transaction เดียว (ช้ากว่า, lock นาน)

2. **ข้อมูล `ประเภทความพิการ` จะเก็บที่ไหน?**
   - **ตัดสินใจแล้ว:** เก็บในตาราง `student_academic_info` (โมเดล `StudentAcademicInfo`) ซึ่งมีฟิลด์ `disability_type` และ `special_needs` รองรับอยู่แล้ว ไม่ต้องแก้ไข Schema

3. **ข้อมูลโรงเรียนเดิม (`ชื่อโรงเรียนเดิม`, `จังหวัดโรงเรียนเดิม`) จะเก็บที่ไหน?**
   - **ตัดสินใจแล้ว:** เก็บในตาราง `student_academic_info` (โมเดล `StudentAcademicInfo`) ซึ่งมีฟิลด์ `previous_school_name`, `previous_school_province`, `previous_grade_level` รองรับอยู่แล้ว ไม่ต้องแก้ไข Schema

**ไฟล์ที่ต้องสร้าง/แก้:**
| ไฟล์ | Action |
|------|--------|
| `app/Services/Import/StudentRosterCommitService.php` | สร้างใหม่ |
| อาจต้องเพิ่ม migration สำหรับ `disability_type` / `previous_school` | ขึ้นกับการตัดสินใจ |

---

### ระยะที่ 5: สร้างห้องเรียนที่ขาด (Prerequisite)

**เป้าหมาย:** สร้าง 16 ห้องเรียนที่ XLSX มีแต่ DB ไม่มี

**5.1 รายการห้องเรียนที่ขาด:**

| ห้อง | จำนวนนักเรียน | ระดับ |
|------|---------------|-------|
| อ.1/1 | 22 | อนุบาล |
| อ.2/1 | 34 | อนุบาล |
| อ.3/1 | 23 | อนุบาล |
| อ.3/2 | 23 | อนุบาล |
| ป.1/1 | 27 | ประถม |
| ป.1/2 | 35 | ประถม |
| ป.2/1 | 30 | ประถม |
| ป.2/2 | 40 | ประถม |
| ป.3/1 | 26 | ประถม |
| ป.3/2 | 37 | ประถม |
| ป.4/1 | 28 | ประถม |
| ป.4/2 | 34 | ประถม |
| ป.5/1 | 28 | ประถม |
| ป.5/2 | 34 | ประถม |
| ป.6/1 | 27 | ประถม |
| ป.6/2 | 39 | ประถม |

**5.2 วิธีสร้าง:**

ทางเลือก A (แนะนำ): เพิ่มใน `roster:commit` command — ถ้าพบ missing classrooms ให้ถามยืนยัน → สร้างอัตโนมัติ:
```php
Classroom::create([
    'academy_id' => $academy->id,
    'academic_year_id' => $year->id,
    'grade_level' => 'อ.1',  // หรือ 'ป.1', etc.
    'section' => '1',
    'name' => 'อ.1/1',
    'capacity' => 45,
]);
```

ทางเลือก B: ให้ admin สร้างผ่าน UI ก่อน

**⚠️ ข้อพิจารณา:**
- ระบบเดิมรองรับแค่ `ม.1`–`ม.6` (ดูจาก `nextGrade()` ใน RolloverService)
- ถ้าเพิ่ม `อ.` และ `ป.` → ต้องตรวจว่า API, frontend, rollover ไม่พังจากค่าที่ไม่คาดคิด
- `StudentCardSyncService.numericGradeLevel()` ใช้ regex `/(\d+)\s*$/` → `อ.1` จะได้ `1`, `ป.3` จะได้ `3` — **ชนกับ ม.1, ม.3!**
- **ต้องแก้ `numericGradeLevel()` ให้รองรับ prefix** หรือแยก card sync ให้ทำเฉพาะ ม.

**5.3 ผลกระทบต่อ Card Sync:**
- บัตรนักเรียนปัจจุบันทำเฉพาะ ม.1–ม.6
- ถ้าเพิ่ม อ./ป. → ต้องตัดสินใจว่าจะออกบัตรให้ระดับนี้ไหม
- **แนะนำ:** filter card sync ให้ทำเฉพาะ grade_level ที่ขึ้นต้นด้วย 'ม.' ก่อน

---

### ระยะที่ 6: Post-commit — Card Sync + Reconciliation

**เป้าหมาย:** หลัง commit roster แล้ว sync บัตรนักเรียน + ตรวจความถูกต้อง

**6.1 รัน Card Sync (ม.1–ม.6 เท่านั้น):**

```bash
php artisan students:sync-cards --academy=1 --academic-year=2 --preview
# ตรวจ preview → ถ้าถูกต้อง
php artisan students:sync-cards --academy=1 --academic-year=2 --commit
```

ใช้ `StudentCardSyncService` ที่มีอยู่แล้ว — ไม่ต้องเขียนใหม่

**6.2 Reconciliation Queries (รันหลัง commit ทุกครั้ง):**

```sql
-- 1. จำนวนนักเรียนในฐานข้อมูล ต้อง ≥ 2,437
SELECT COUNT(*) FROM students WHERE academy_id = 1 AND status = 'active';

-- 2. active enrollment ต้องตรง XLSX count ต่อห้อง
SELECT c.grade_level, c.section, COUNT(*) as db_count
FROM classroom_students cs
JOIN classrooms c ON cs.classroom_id = c.id
WHERE cs.academic_year_id = 2 AND cs.status = 'active' AND c.academy_id = 1
GROUP BY c.grade_level, c.section
ORDER BY c.grade_level, CAST(c.section AS UNSIGNED);
-- เทียบกับ XLSX count ต่อห้อง

-- 3. ไม่มี duplicate active enrollment
SELECT student_id, COUNT(*) as cnt FROM classroom_students
WHERE status = 'active' AND academic_year_id = 2
GROUP BY student_id HAVING cnt > 1;

-- 4. ไม่มี duplicate current academic info
SELECT student_id, COUNT(*) as cnt FROM student_academic_info
WHERE is_current = 1
GROUP BY student_id HAVING cnt > 1;

-- 5. citizen_id ที่เติมใหม่ ตรงกับ XLSX
SELECT s.student_id, s.citizen_id FROM students s
WHERE s.academy_id = 1 AND s.citizen_id IS NOT NULL AND s.status = 'active'
ORDER BY s.student_id;
-- เทียบกับ XLSX citizen_id

-- 6. active student ม.1-ม.6 ทุกคนมีบัตร
SELECT s.id, s.student_id, s.first_name_th FROM students s
JOIN classroom_students cs ON s.id = cs.student_id AND cs.status = 'active'
JOIN classrooms c ON cs.classroom_id = c.id
LEFT JOIN student_cards sc ON s.id = sc.student_id AND sc.student_status = 'active'
WHERE s.status = 'active' AND s.academy_id = 1 AND c.grade_level LIKE 'ม.%' AND sc.id IS NULL;
-- ต้องได้ 0 rows

-- 7. สุ่มตรวจ 1 ห้อง
SELECT s.student_id, s.citizen_id, s.first_name_th, s.last_name_th,
       c.grade_level, c.section, cs.student_number,
       sa.house_number, sa.subdistrict, sa.district, sa.province,
       sg_f.first_name as father_name, sg_m.first_name as mother_name
FROM students s
JOIN classroom_students cs ON s.id = cs.student_id AND cs.status = 'active'
JOIN classrooms c ON cs.classroom_id = c.id
LEFT JOIN student_addresses sa ON s.id = sa.student_id AND sa.is_current = 1
LEFT JOIN student_guardians sg_f ON s.id = sg_f.student_id AND sg_f.guardian_type = 'father'
LEFT JOIN student_guardians sg_m ON s.id = sg_m.student_id AND sg_m.guardian_type = 'mother'
WHERE c.grade_level = 'ม.1' AND c.section = '1' AND c.academic_year_id = 2
ORDER BY cs.student_number;
-- เทียบกับ XLSX แถว ม.1/1
```

---

### Deployment Checklist

```
 Phase 0 — ก่อนเริ่ม
 [x] ตัดสินใจ: เก็บ ประเภทความพิการ ที่ไหน -> เก็บใน student_academic_info (มีฟิลด์รองรับอยู่แล้ว)
 [x] ตัดสินใจ: เก็บ โรงเรียนเดิม ที่ไหน -> เก็บใน student_academic_info (มีฟิลด์รองรับอยู่แล้ว)
 [x] ตัดสินใจ: ออกบัตรให้ อ./ป. ด้วยไหม -> ข้ามระดับชั้น อ./ป. ไม่ต้องนำเข้าข้อมูล (นำเข้าเฉพาะ ม.1-ม.6)

 Phase 1 — Parse & Normalize
 ☐ สร้าง ThaiDateParser + unit test
 ☐ สร้าง StudentRosterXlsxParser + unit test
 ☐ ทดสอบ parse XLSX → ได้ 2,437 valid structs

 Phase 2 — Identity Match & Preview
 ☐ เขียน migration เพิ่ม fields ใน import tables
 ☐ php artisan migrate
 ☐ สร้าง StudentRosterUpdateService
 ☐ สร้าง roster:preview command
 ☐ รัน preview → ตรวจ summary ตรงกับที่วิเคราะห์ไว้:
   - exact_match: ~1,839
   - code_only: ~462
   - new_student: ~125
   - conflict: 11
   - create_enrollment: ~442
   - move_classroom: ~170

 Phase 3 — Prerequisites
 ☐ ส่ง conflict report (11 คน) ให้นายทะเบียนตรวจ
 ☐ รอนายทะเบียน resolve conflicts
 ☐ ตรวจว่า 16 ห้อง อ./ป. ที่ขาด จะสร้างหรือไม่ → ถ้าสร้าง ให้สร้างก่อน commit
 ☐ mysqldump nuxnan > nuxnan_backup_before_roster_update.sql

 Phase 4 — Commit
 ☐ สร้าง StudentRosterCommitService
 ☐ สร้าง roster:commit command
 ☐ รัน roster:commit {batch_id} --dry-run → ตรวจ
 ☐ รัน roster:commit {batch_id} → จริง
 ☐ ตรวจ partial failures (ถ้ามี)

 Phase 5 — Card Sync
 ☐ รัน students:sync-cards --preview → ตรวจ
 ☐ รัน students:sync-cards --commit
 ☐ รัน reconciliation queries (7 ข้อ)
 ☐ สุ่มตรวจ 3-5 ห้อง เทียบกับ XLSX

 Phase 6 — Cleanup
 ☐ ปิด batch (status = completed)
 ☐ เก็บ backup + audit log
```

### สรุปไฟล์ทั้งหมด

**ไฟล์ใหม่:**
| # | ไฟล์ | ระยะ |
|---|------|------|
| 1 | `app/Services/Import/ThaiDateParser.php` | 1 |
| 2 | `app/Services/Import/StudentRosterXlsxParser.php` | 1 |
| 3 | `app/Services/Import/StudentRosterUpdateService.php` | 2 |
| 4 | `app/Services/Import/StudentRosterCommitService.php` | 4 |
| 5 | `app/Console/Commands/RosterPreviewCommand.php` | 3 |
| 6 | `app/Console/Commands/RosterCommitCommand.php` | 3 |
| 7 | `database/migrations/xxxx_add_roster_update_fields_to_import_tables.php` | 2 |

**ไฟล์แก้ไข:**
| # | ไฟล์ | สิ่งที่แก้ |
|---|------|----------|
| 1 | `app/Models/StudentImportBatch.php` | เพิ่ม import_type, source_format |
| 2 | `app/Models/StudentImportRow.php` | เพิ่ม action, matched_student_id, diff_data |
| 3 | `app/Services/StudentCardSyncService.php` | แก้ numericGradeLevel() ให้ไม่ชนข้าม prefix (อ./ป./ม.) |

**ไฟล์ที่ reuse (ไม่ต้องแก้):**
| ไฟล์ | ใช้ตรงไหน |
|------|----------|
| `StudentIntakeService` | สร้างนักเรียนใหม่ 125 คน |
| `StudentEnrollmentService` | สร้าง/ย้าย enrollment |
| `StudentCardSyncService` | sync บัตรหลัง roster update |
| `StudentImportService` | extend logic สำหรับ XLSX |

### หัวใจของแผน

> **"Parse → Match → Classify → Preview → Approve → Commit → Sync → Verify"**
>
> - ไม่มีขั้นตอนใดเขียนข้อมูลโดยไม่ผ่าน preview ก่อน
> - ไม่ลบข้อมูลเดิม — เปลี่ยนสถานะหรือเพิ่มทับเท่านั้น
> - Conflict 11 รายต้องผ่านคนตรวจ ห้ามเดา
> - Reuse services ที่มีอยู่ (`IntakeService`, `EnrollmentService`, `CardSyncService`)
> - Chunk transaction ป้องกัน partial failure ลาม
> - Reconciliation ทุกครั้งหลัง commit

---

# Work Plan — Student Card Request System (2026-07-08)

**สถานะ:** วางแผน (ยังไม่เริ่มพัฒนา)
**ขอบเขต:** เพิ่มระบบคำร้องทำบัตรครอบระบบ `student_cards` เดิม เพื่อแทนที่การสร้างบัตรอัตโนมัติจาก `StudentCardSyncService` และแก้ปัญหาบัตรซ้ำที่ต้นเหตุ
**Source:** ต่อยอดจากข้อเสนอผู้ใช้ ผ่านการตรวจเทียบ codebase จริง

---

## User Analysis Input

### สรุปข้อเสนอเดิม (workflow)

1. ครูประจำชั้นเปิดรายชื่อนักเรียนในห้องตน
2. ระบบแสดงสถานะรายคน: มีบัตรใช้อยู่ / ไม่มีบัตร / รอดำเนินการ / กำลังทำ / เสร็จแล้ว
3. ครูเลือกนักเรียนที่ต้องทำบัตร ส่งคำร้องรายคนหรือหลายคน
4. Admin ผู้ทำบัตรตรวจคำร้อง
5. Admin รับงาน ปฏิเสธ หรือทำบัตร
6. เมื่อทำเสร็จ Admin เชื่อมคำร้องกับ `student_cards` และเปลี่ยนสถานะเป็นเสร็จสิ้น

**Request types:** `first_issue`, `replacement`, `renewal`
**State machine:** `pending → approved → in_progress → completed` (+ `rejected`, `cancelled`)

---

## การวิเคราะห์เทียบกับ Codebase จริง

### สิ่งที่ตรงกับโค้ดปัจจุบันแล้ว

1. **Unique constraint ป้องกัน active card ซ้ำ มีอยู่แล้ว** — `uq_student_card_active` บน `(student_id, academy_id, is_active_flag)` ใน `2026_07_07_053001_add_constraints_and_fields_to_student_cards.php` ใช้ virtual column `is_active_flag` ที่เป็น NULL เมื่อ `student_status != 'active'` แผนของผู้ใช้กำหนดให้เป็น "ด่านสุดท้าย" — ถูกต้อง แต่ไม่ต้องสร้างเพิ่ม
2. **`Classroom.homeroom_teacher_id`** มีอยู่และเป็น FK ไปที่ `users.id` — ใช้ได้ตามแผน
3. **Middleware `academy.permission:...`** รองรับ dotted-permission ผ่าน `hasAnyPermission()` แล้ว (`CheckAcademyPermission.php`)
4. **`ClassroomStudent.status = 'active'`** เป็น SoT ของ enrollment ตามที่แผนใช้อ้าง

### สิ่งที่ต้องแก้จากแผนเดิม

5. **`StudentCardSyncService` คือต้นเหตุของบัตรซ้ำ ไม่ใช่แค่ผลข้างเคียง** — `commitSync()` วนสร้างบัตรให้ **ทุก** active enrollment ที่ไม่มีบัตร (`StudentCardSyncService.php:181`) และตอน rollover 2568→2569 เพิ่ง created 476 (worklog 2026-07-06) ระบบคำร้องใหม่จะไร้ประโยชน์ถ้ายัง trigger service นี้ต่ออัตโนมัติ — **ต้องปิด/บล็อค endpoint `POST /student-cards/admin/sync/commit` และไม่เรียกจาก rollover อีก** เป็น deliverable ที่ 1 ไม่ใช่ footnote
6. **Permission naming ควรตามแบบเดิม (dotted)** ไม่ใช่ underscore — ใช้ `students.cards.request` (ครู) + `students.cards.produce` (แอดมิน/ผู้ทำบัตร) แทน `manage_student_cards` เพื่อสอดคล้องกับ `students.manage`, `home_visits.manage` ที่มีอยู่ และเข้ากับ hierarchical check
7. **Frontend `/teacher/*` route ยังไม่มีเลย** — ทั้ง project อยู่ใต้ `/admin/*` การเปิด `/academies/[name]/teacher/student-card-requests` คือการเปิด teacher portal แยกใหม่ ซึ่งเป็นการตัดสินใจโครงสร้างขนาดใหญ่ ต้องตัดสินก่อน:
   - (A) วางไว้ใต้ `/admin/student-cards/requests` เหมือน admin เดิม แล้วซ่อน section ที่ครูไม่มีสิทธิ์ — เร็ว ใช้ layout เดิม
   - (B) สร้าง `/teacher/` portal จริง — สะอาดกว่า แต่ต้องออกแบบ layout+sidebar+menu ใหม่ทั้งชุด
8. **นักเรียนถูกลบ → ประวัติคำร้องหาย** — แผนกำหนด FK `student_id` ต้องเลือก policy ก่อน แนะนำเก็บ snapshot (`full_name_snapshot`, `student_number_snapshot`, `grade_level_snapshot`, `section_snapshot`) ณ เวลาส่ง และใช้ `onDelete('set null')` เพื่อไม่ให้ประวัติหาย เหมือน StudentCard ที่เก็บ `full_name_thai`, `class_level` แบบ frozen อยู่แล้ว
9. **การ complete ต้องเชื่อมกับ StudentCard ยังไง** — ต้องเลือก:
   - **สร้าง row ใหม่เสมอ** (แนะนำ) — expire card เดิม + insert card ใหม่ในทรานแซกชั่นเดียว ให้ audit trail สมบูรณ์และเข้ากับ unique constraint เดิม
   - **แก้ในตัวเดิม** — เร็วกว่าแต่ audit หาย ไม่แนะนำ
10. **Rollover hook** — หลังจาก request flow ใช้จริง `AcademicYearRolloverService` ที่ trigger card sync อัตโนมัติต้องเปลี่ยนพฤติกรรม แผนควรระบุชัดเจนว่า "rollover จะไม่สร้างบัตรอีกต่อไป — จะให้ครูส่ง `renewal` ผ่านระบบคำร้องเท่านั้น"
11. **`StudentCard` ใช้ `Auditable` trait อยู่แล้ว** — `StudentCardRequest` ก็ควรใช้ trait นี้ เพื่อให้ transition ทุกครั้งมี audit log ครบ

### สิ่งที่แผนเดิมขาด

12. **Data backfill** — บัตร active 2,138 ใบใน DB ปัจจุบันไม่มีคำร้องผูก อย่างน้อยควรระบุใน rollout ว่า card ที่มีอยู่แล้วจะถือเป็น `origin='legacy'` หรือทำ synthetic `completed` request เพื่อ audit ครบวง
13. **Idempotency ของ bulk submit** — ต้องเป็น per-student result ไม่ใช่ transaction เดียวที่ล้มทั้งชุด (ครูเลือก 40 คน มี 3 คนคำร้องซ้อน → 37 ผ่าน 3 skip พร้อมเหตุผลรายคน)
14. **State machine ใน service** — แผนบอกให้รวมใน service เดียว แต่ควรระบุ helper `StudentCardRequestService::transition($request, $toStatus, $actor, ?$reason)` ที่ validate FROM→TO ก่อน เพื่อกันข้ามขั้นตอนแบบ single point
15. **Real-time notification** — ระบบมี Laravel Reverb อยู่แล้ว ครูควรได้รับ broadcast เมื่อ admin อนุมัติ/ปฏิเสธ/เสร็จ (ไม่ต้อง refresh)
16. **Race condition ที่ complete** — 2 admin กด complete พร้อมกันสำหรับคำร้องเดียว → ต้อง `lockForUpdate()` + re-check status ในทรานแซกชั่น
17. **Priority / urgency** — โรงเรียนมักเจอ "หายกลางเทอม ต้องได้พรุ่งนี้" ควรมี `priority` (normal/urgent)
18. **แยก `reason` (ครูกรอก) กับ `admin_notes` (แอดมินจดภายใน)** — 2 field ต่างเจตนา ไม่ควรใช้ column เดียว
19. **`existing_card_id` policy สำหรับ replacement** — ครูต้องเลือกใบเดิม (dropdown) หรือระบบดึงมาให้อัตโนมัติ? ควรระบุ (แนะนำ auto-fetch จาก unique active card)
20. **Rate limiting** — ครูกด "ส่งคำร้อง 200 คน" ในห้องผิด → ต้อง cap หรือมี confirmation modal

---

## Work Plan — ฉบับปรับปรุงสมบูรณ์

### ระยะที่ 0: Prerequisite Decisions (ตอบก่อนเริ่ม)

**เป้าหมาย:** ยืนยันการตัดสินใจโครงสร้างที่กระทบทั้งระบบ ก่อนเขียน migration

| # | คำถาม | ค่าที่แนะนำ | ผลกระทบ |
|---|-------|-------------|----------|
| 0.1 | Teacher portal โครงสร้าง (A) หรือ (B) | (A) — วางใต้ `/admin/student-cards/requests` | (B) เป็นงานเพิ่ม 3–5 วัน สำหรับ layout/sidebar/menu |
| 0.2 | Card lifecycle policy | สร้าง row ใหม่เสมอ | audit trail สมบูรณ์, unique constraint เดิมใช้ได้ตรงๆ |
| 0.3 | Rollover behavior หลัง feature live | ปิด auto card sync ถาวร | rollover จะจัดการ enrollment อย่างเดียว, บัตรผ่านคำร้อง |
| 0.4 | Legacy card ทำ synthetic request | ทำเลย `origin='legacy'` | reports "บัตรที่ผลิตในปีนี้" ไม่เพี้ยน |
| 0.5 | `existing_card_id` สำหรับ replacement/renewal | auto-fetch จาก active card | ครูไม่ต้องเลือก, ลด error |
| 0.6 | Complete → auto-open print page | Redirect ไปหน้า print card หลัง complete | เชื่อมกับ workflow admin เดิม |

**ผลลัพธ์:** เอกสารสรุปคำตอบ 6 ข้อ ใช้เป็น decision log

---

### ระยะที่ 1: Foundation — Schema + State Machine (Backend Core)

**เป้าหมาย:** สร้าง table + model + service state machine พร้อม constraint ครบ

**Deliverables:**

1. **Migration `create_student_card_requests_table`** ฟิลด์เต็มชุด:
   - Core: `academy_id`, `academic_year_id`, `classroom_id`, `student_id` (nullable, `onDelete set null`), `request_type` (enum: `first_issue`|`replacement`|`renewal`), `status` (enum ตาม state machine)
   - Snapshots ณ เวลาส่ง: `full_name_snapshot`, `student_number_snapshot`, `grade_level_snapshot`, `section_snapshot`
   - Linkage: `existing_card_id` (nullable), `result_card_id` (nullable)
   - Reason/notes: `reason` (จากครู, required เมื่อ replacement/renewal), `admin_notes` (nullable), `rejection_reason` (nullable)
   - Actors + timestamps: `requested_by`, `approved_by`, `processed_by`, `requested_at`, `approved_at`, `started_at`, `completed_at`, `cancelled_at`, `rejected_at`
   - Metadata: `priority` (default `normal`), `origin` (default `teacher`, สำหรับ backfill ใช้ `legacy`)
   - Standard: `timestamps`

2. **Indexes:**
   - `(academy_id, status)`
   - `(academy_id, classroom_id, status)`
   - `(student_id, status)`
   - **Partial unique** `(student_id, academy_id) WHERE status IN ('pending','approved','in_progress')` เพื่อป้องกันคำร้องเปิดซ้อน (MySQL 8+ ใช้ generated column trick เหมือน `is_active_flag`)

3. **Model `StudentCardRequest`** ใช้ `Auditable` trait, relationships ครบ (`academy`, `academicYear`, `classroom`, `student`, `existingCard`, `resultCard`, `requestedBy`, `approvedBy`, `processedBy`), casts วันที่

4. **Enum classes** — `RequestStatus`, `RequestType`, `RequestOrigin` (PHP 8.4 backed enums)

5. **`StudentCardRequestService`** (state machine core):
   - `transition($request, $toStatus, User $actor, array $context = [])` — validate transition matrix ก่อน, throw `InvalidStateTransition` ถ้าข้าม, บันทึก actor+timestamp+reason
   - `create(...)`, `bulkCreate(...)` (return per-row result), `cancel(...)`
   - `complete(...)` — ทรานแซกชั่นเดียว: `lockForUpdate()` request, expire `existing_card_id` (ถ้ามี), insert new StudentCard, set `result_card_id`, status=`completed`

**Verify:** unit test transition matrix, partial unique constraint enforced

---

### ระยะที่ 2: Policy + Authorization

**เป้าหมาย:** สิทธิ์ครู/แอดมินตามหลักการ least privilege

**Deliverables:**

1. **Permission keys ใหม่ใน `AcademyRole::SYSTEM_ROLES`:**
   - `students.cards.request` → เพิ่มให้ role `teacher` (ครูประจำชั้นเท่านั้น, มี extra check เลเยอร์ที่ 2 ใน controller)
   - `students.cards.produce` → เพิ่มให้ role `admin`, `owner`, `director`, และเปิดให้ create custom role `card_admin` ถ้าโรงเรียนอยากแยกคน

2. **Update `AcademyRoleSeeder`** แบบ `updateOrCreate` เพื่อ backfill permission ให้ role เดิมทุก academy (pattern เดียวกับ registrar seeding เดือน 07-05)

3. **`StudentCardRequestPolicy`:**
   - `viewClassroom($user, Classroom)` — `homeroom_teacher_id` ตรง OR มี `students.manage`
   - `create($user, Classroom)`, `cancel($user, Request)` — เจ้าของ request (ครู) หรือมี `students.cards.produce`
   - `approve/reject/start/complete($user, Request)` — ต้องมี `students.cards.produce` เท่านั้น

4. **FormRequest classes:** `StoreStudentCardRequestRequest`, `BulkStoreStudentCardRequestRequest`, `RejectRequest` (บังคับ `rejection_reason`)

**Verify:** feature test — ครูห้องอื่นได้ 403, admin ไม่มี produce permission ได้ 403

---

### ระยะที่ 3: API Layer

**เป้าหมาย:** REST API ตาม intent-based transitions (ไม่ใช่ raw status update)

**Routes ใต้ `/api/academies/{academy}/student-card-requests`:**

**Teacher-facing** (ต้องมี `students.cards.request` + homeroom check ใน controller):
- `GET  /my-classrooms` — คืน classrooms ที่ user เป็น homeroom
- `GET  /classrooms/{classroom}/students` — รายชื่อ + สถานะบัตร + คำร้องล่าสุด (join StudentCard + latest open request)
- `POST /`
- `POST /bulk` — return `{ results: [{ student_id, status: 'created'|'skipped', reason }] }`
- `PATCH /{request}/cancel`

**Admin/Producer-facing** (ต้องมี `students.cards.produce`):
- `GET  /` — queue with filters: `year`, `classroom`, `type`, `status`, `priority`, `search`
- `GET  /{request}` — รายละเอียด + snapshot + audit log
- `PATCH /{request}/approve`
- `PATCH /{request}/reject` (body: `rejection_reason` required)
- `PATCH /{request}/start`
- `PATCH /{request}/complete` — trigger `StudentCardRequestService::complete()`
- `POST  /bulk-approve`, `POST /bulk-start` — power features

**Shared:**
- `GET /counts` — สรุปตัวเลข dashboard (pending, approved, in_progress, done_today)

**Verify:** ทุก route มี test สำหรับ 200 (happy path), 403 (permission), 422 (validation), 409 (invalid state)

---

### ระยะที่ 4: Disable Legacy Bulk Creation (ต้องทำก่อน UI)

**เป้าหมาย:** ปิดต้นทางของบัตรซ้ำ ก่อนที่ user จะเริ่มใช้ระบบใหม่

**Deliverables:**

1. **Gate `POST /academies/{academy}/student-cards/admin/sync/commit`** ให้ throw 410 Gone หรืออ่านเฉพาะ preview (คงไว้เพื่อ inspect)
2. **ตัด hook จาก `AcademicYearRolloverService::commitRollover`** ที่เรียก StudentCardSync (ถ้ามี — ตรวจ code path จริง — worklog 2026-07-06 ระบุว่า rollover trigger card sync จริง)
3. **Feature flag `academies.settings.card_request_flow_enabled`** (boolean) เพื่อ rollout ทีละ academy:
   - flag=false → ยังใช้ legacy sync ได้
   - flag=true → บล็อค legacy, บังคับใช้ request flow
4. **Backfill script `students:seed-legacy-card-requests`** — สร้าง synthetic `completed` request ให้ card active ทุกใบที่มี ณ วันเปิด flag ตั้ง `origin='legacy'`, `requested_by`=system user เก็บ audit ครบวง

**Verify:** integration test — flag=true → legacy sync return 410; rollover ไม่สร้าง card ใหม่

---

### ระยะที่ 5: Teacher UI

**เป้าหมาย:** หน้าเดียวจบ ครูส่งคำร้องได้ในไม่กี่คลิก

**Deliverables:**

1. **หน้า `student-card-requests`** (path ตามผล ระยะ 0.1 — default: `/admin/student-cards/requests/my-classrooms`)
2. **Sub-page:** เลือก classroom → ตารางนักเรียน + status badge (`no_card`, `active_card`, `pending_request`, `in_progress`, `completed_recent`)
3. **`SubmitRequestModal.vue`:**
   - เลือก `request_type` (ถ้ามีบัตร active — บังคับ `replacement`/`renewal`, ปิด `first_issue`)
   - บังคับกรอก `reason` เมื่อไม่ใช่ `first_issue`
   - Auto-fetch `existing_card_id` จาก active card
4. **Composable `useStudentCardRequests.ts`** (wrapper API + type-safe result)
5. **Bulk selection** — checkbox + submit modal + confirmation "คุณกำลังจะส่ง N คำร้อง"
6. **Filter:** "แสดงเฉพาะคนที่ยังไม่มีบัตร"
7. **Reverb subscription:** update badge เมื่อ admin เปลี่ยนสถานะ

**Verify:** manual E2E — ครูเข้าหน้า, เลือก 5 คน (มี 1 คนมีคำร้องซ้อน), ส่ง → เห็น 4 ok + 1 skip พร้อมเหตุผล

---

### ระยะที่ 6: Admin UI

**เป้าหมาย:** คิวงานแอดมินใช้ง่าย รองรับ batch printing

**Deliverables:**

1. **หน้า queue** พร้อม stat cards (pending/approved/in_progress/done_today)
2. **Filter panel + persistent table** (PrimeVue DataTable ตาม convention)
3. **Row actions:** view detail, approve, reject (modal บังคับ reason), start, complete
4. **Bulk actions:** approve, start (สำหรับ batch printing)
5. **หน้ารายละเอียด:** snapshot + timeline (audit log) + ปุ่มไปหน้า print card (เชื่อมระบบเดิม `/admin/student-cards/{result_card_id}/print`)

**Verify:** manual E2E — admin เข้าหน้า, filter pending, bulk approve 10 คำร้อง, กด complete รายคน → บัตรจริงถูกสร้าง + link `result_card_id` ครบ

---

### ระยะที่ 7: Notifications

**เป้าหมาย:** ครู/แอดมินไม่ต้อง refresh หน้า

**Deliverables:**

1. **Event classes:** `RequestSubmitted`, `RequestApproved`, `RequestRejected`, `RequestStarted`, `RequestCompleted`, `RequestCancelled`
2. **Broadcast ผ่าน Reverb** (private channel per user)
3. **In-app notification store integration** (มี `NotificationService` อยู่แล้ว)

**Verify:** manual — ครูเปิดหน้าค้างไว้, admin approve → badge เปลี่ยนสถานะ live

---

### ระยะที่ 8: Tests

**Coverage เป้าหมาย:**

- [x] ครูส่งได้เฉพาะห้องตน + 403 กรณีอื่น
- [x] ห้ามคำร้องเปิดซ้อน (test partial unique constraint)
- [x] บังคับ `reason` เมื่อ replacement/renewal
- [x] Admin transition ข้ามขั้นตอน → `InvalidStateTransition`
- [x] Race: 2 admin กด complete พร้อมกัน → 1 สำเร็จ 1 fail ชัดเจน (ผ่าน `lockForUpdate` + status re-check)
- [x] ย้ายห้องหลังส่ง → snapshot ยังถูก
- [x] Legacy sync commit endpoint → 410 Gone เมื่อ flag=true
- [x] Rollover ไม่ auto-create card อีก
- [x] Backfill script สร้าง synthetic request ครบทุก card active
- [x] Bulk submit per-row result (37 ok / 3 skipped ไม่ล้มทั้งชุด)
- [x] Audit trail ทุก transition มี actor + timestamp
- [x] E2E: ครูส่ง → admin approve → start → complete → บัตรใหม่สร้าง + บัตรเก่า expire

---

### ระยะที่ 9: Rollout Playbook

**เป้าหมาย:** ปลอดภัย ค่อยเป็นค่อยไป, มีทาง rollback

**ขั้นตอน:**

1. Deploy code — flag ทุก academy = false (behavior เดิม)
2. เลือก academy pilot 1 แห่ง → run backfill → flip flag = true → ทดสอบ 1 สัปดาห์
3. Rollout เพิ่มทีละกลุ่ม (monitor error rate)
4. ปิด legacy endpoints ถาวรเมื่อทุก academy = true (post-deploy migration ลบ code path)

---

## ข้อควรระวังที่ยังต้องตัดสินใจ

1. **Homeroom เดี่ยว** — `classrooms.homeroom_teacher_id` มีคนเดียว ถ้าครูลาออก/เปลี่ยน — คำร้องเก่าที่ครูคนเก่าส่งไว้จะยัง valid (เพราะเช็คสิทธิ์ที่ point-in-time) แต่คนใหม่จะเห็นได้เพราะเช็คสิทธิ์ live — ยืนยันว่าเป็น behavior ที่ต้องการ
2. **QR / print integration** — เมื่อ admin กด complete ระบบจะ redirect ไปหน้าพิมพ์เดิม (link `/admin/student-cards/{result_card_id}/print`) — ต้องยืนยัน route นี้มีอยู่จริง
3. **นักเรียนย้าย academy** — ถ้า transfer ข้าม academy คำร้อง academy เดิมค้าง — ต้อง auto-cancel เมื่อ enrollment ย้าย (hook ที่ `StudentEnrollmentService::transferStudent`)

---

## ไฟล์หลักที่คาดว่าจะเพิ่ม/แก้

### Backend

**สร้างใหม่:**
- `database/migrations/xxxx_create_student_card_requests_table.php`
- `app/Models/StudentCardRequest.php`
- `app/Enums/{RequestStatus,RequestType,RequestOrigin}.php`
- `app/Services/StudentCardRequestService.php`
- `app/Http/Controllers/Api/Learn/Student/Card/StudentCardRequestController.php`
- `app/Http/Requests/{StoreStudentCardRequestRequest,BulkStoreStudentCardRequestRequest,RejectStudentCardRequestRequest}.php`
- `app/Http/Resources/StudentCardRequestResource.php`
- `app/Policies/StudentCardRequestPolicy.php`
- `app/Events/StudentCard/{RequestSubmitted,RequestApproved,RequestRejected,RequestStarted,RequestCompleted,RequestCancelled}.php`
- `app/Exceptions/InvalidStateTransition.php`
- `app/Console/Commands/SeedLegacyCardRequests.php`
- `routes/learn/academy-student-card-request.php`
- Tests: `tests/Feature/Api/Academy/StudentCardRequest/*Test.php`

**แก้ไข:**
- `app/Models/AcademyRole.php` — เพิ่ม 2 permission keys ใน SYSTEM_ROLES
- `database/seeders/AcademyRoleSeeder.php` — backfill permission ให้ role เดิม
- `app/Services/StudentCardSyncService.php` — gate `commitSync()` ตาม feature flag
- `app/Services/AcademicYearRolloverService.php` — ตัด card sync hook
- `app/Http/Controllers/Api/Learn/Student/Card/StudentCardController.php` — gate `syncCommit()` action
- `app/Services/StudentEnrollmentService.php` — auto-cancel open requests เมื่อ transfer ข้าม academy
- `bootstrap/app.php` (or `RouteServiceProvider`) — register route file ใหม่
- `app/Models/{Student,StudentCard,Classroom}.php` — เพิ่ม `cardRequests()` relationship

### Frontend

**สร้างใหม่:**
- `ui/pages/academies/[name]/admin/student-cards/requests/index.vue` (teacher view)
- `ui/pages/academies/[name]/admin/student-cards/requests/queue.vue` (admin view)
- `ui/pages/academies/[name]/admin/student-cards/requests/[id].vue` (detail)
- `ui/composables/useStudentCardRequests.ts`
- `ui/components/school/studentCard/SubmitRequestModal.vue`
- `ui/components/school/studentCard/RequestStatusBadge.vue`
- `ui/components/school/studentCard/RequestQueueTable.vue`
- `ui/components/school/studentCard/RequestTimelineDrawer.vue`
- `ui/types/studentCardRequest.ts`

**แก้ไข:**
- `ui/pages/academies/[name]/admin.vue` — เพิ่ม sidebar link "คำร้องทำบัตร"
- `ui/pages/academies/[name]/admin/index.vue` — quick action
- `ui/i18n/locales/{th,en}/*.json` — ข้อความใหม่

---

## หัวใจของแผน

> **"Request first, sync never. Snapshot everything."**
>
> - ปิดต้นเหตุก่อน (StudentCardSyncService::commitSync + rollover hook) — feature ใหม่ไร้ประโยชน์ถ้าท่อเก่ายังเปิด
> - Snapshot ทุก field ที่จะเปลี่ยนได้ (ชื่อ, เลข, ห้อง) เพื่อประวัติไม่หายเมื่อ student mutate
> - State machine ใน service เดียว, transition ต้องผ่าน `transition()` — ห้าม controller update raw status
> - Partial unique constraint + `lockForUpdate` + re-check status = 3 ชั้นกัน race + duplicate
> - Feature flag + backfill script = rollout safe ทีละ academy, revert ได้ทุกเวลา
> - Reuse `Auditable` trait + Reverb + PrimeVue DataTable — ไม่สร้าง infra ใหม่

---

# Work Plan — Student Card Request System (2026-07-08)

## 1. User Analysis Input
- **ประเภทของคำร้อง (Request Types)**: `first_issue` (ออกบัตรครั้งแรก), `replacement` (ออกบัตรแทนใบเดิม/หาย), `renewal` (ต่ออายุบัตร)
- **State Machine**:
  - `pending` (สร้างคำร้องโดยครูประจำชั้น)
  - `approved` (อนุมัติโดย admin)
  - `rejected` (ปฏิเสธโดย admin พร้อมระบุเหตุผล)
  - `in_progress` (เริ่มกระบวนการจัดทำ/พิมพ์บัตร)
  - `completed` (จัดทำเสร็จสิ้นและออกบัตรใหม่สำเร็จ)
  - `cancelled` (ยกเลิกโดยผู้ส่งคำร้องก่อนได้รับการประมวลผล)

---

## 2. การวิเคราะห์เปรียบเทียบกับ Codebase จริง

### จุดที่ตรงกับโค้ดปัจจุบันแล้ว (ไม่ต้องแก้ไขหรือทำเพิ่ม)
1. **Unique Constraint**: มี unique constraint บน `(student_id, academy_id, is_active_flag)` ใน migration `2026_07_07_053001_add_constraints_and_fields_to_student_cards.php` โดยใช้ virtual column `is_active_flag` ที่มีค่าเป็น NULL เมื่อ `student_status != 'active'` เพื่อป้องกันการมี active card ซ้ำ
2. **Homeroom Teacher Link**: `Classroom.homeroom_teacher_id` เชื่อมโยงกับ `users.id` เรียบร้อยแล้ว
3. **Dotted-Permission Middleware**: `CheckAcademyPermission` รองรับ dotted format ผ่าน `hasAnyPermission()` แล้ว
4. **Enrollment Source of Truth**: `ClassroomStudent.status = 'active'` คือข้อมูลที่เป็น Source of Truth หลักสำหรับ enrollment

### จุดที่ต้องแก้ไขและปรับปรุงแผนอย่างเร่งด่วน
1. **ปิดกั้น Auto Sync / Legacy Sync**: `StudentCardSyncService::commitSync()` คือสาเหตุหลักของการสร้างบัตรซ้ำโดยอัตโนมัติ ต้องปิด/บล็อก endpoint `POST /student-cards/admin/sync/commit` และตัดการเรียกออกจากกระบวนการ rollover
2. **การตั้งชื่อ Permission**: ใช้รูปแบบ dotted-permission แบบเดิม ได้แก่ `students.cards.request` (ครูประจำชั้นในการส่งคำร้อง) และ `students.cards.produce` (แอดมินในการอนุมัติและจัดทำบัตร) แทนการใช้ `manage_student_cards`
3. **โครงสร้าง UI สำหรับคุณครู**: เลือกใช้แนวทาง **(A) วางไว้ใต้ `/admin/student-cards/requests`** โดยแชร์ layout เดิมของ admin แต่จำกัดการเข้าถึงและซ่อนปุ่มหรือส่วนการทำงานที่ไม่มีสิทธิ์ เพื่อความรวดเร็วและใช้โครงสร้างเดิม
4. **นโยบายเมื่อนักเรียนถูกลบ**: ใช้ snapshots ของฟิลด์ต่าง ๆ (`full_name_snapshot`, `student_number_snapshot`, `grade_level_snapshot`, `section_snapshot`) ณ เวลาที่ยื่นคำร้อง และใช้การทำงานแบบ `onDelete('set null')` บน `student_id` ของตารางคำร้องเพื่อไม่ให้ประวัติคำร้องสูญหาย
5. **การ Complete คำร้อง**: เลือกใช้แนวทาง **สร้างแถว (row) ใหม่เสมอในตาราง StudentCard** พร้อมทั้งเปลี่ยนสถานะบัตรเดิมให้หมดอายุ (expire) และสร้างบัตรใหม่ในทรานแซกชั่นเดียวกัน เพื่อให้ประวัติการตรวจสอบ (Audit Trail) สมบูรณ์
6. **Rollover Hook**: กำหนดให้ไม่มีการสร้างการ์ดโดยอัตโนมัติจาก rollover อีกต่อไป โดยการต่ออายุบัตรจะใช้คำร้องแบบ `renewal` ผ่านระบบนี้เท่านั้น
7. **Audit Log สำหรับคำร้อง**: ใช้ `Auditable` trait บนโมเดล `StudentCardRequest` เพื่อบันทึกประวัติการเปลี่ยนผ่านสถานะอย่างครอบคลุม

### สิ่งที่ระบบต้องมีเพิ่มเติม
- **Data Backfill**: รัน script backfill เพื่อตั้งค่าบัตรปัจจุบันที่มีอยู่แล้วเป็น `origin='legacy'` หรือทำ synthetic completed request
- **Idempotency**: การทำ bulk submit จะต้องส่งผลลัพธ์แยกตามรายบุคคล (per-student result) หากมีบางคนที่เกิดข้อผิดพลาดหรือมีคำร้องเปิดอยู่ คนอื่น ๆ ใน batch ต้องดำเนินต่อได้และข้ามเฉพาะคนที่มีปัญหาพร้อมให้เหตุผล
- **State Machine Guard**: ใช้ `StudentCardRequestService::transition($request, $toStatus, $actor, ?$reason)` เพื่อตรวจสอบ transition matrix ป้องกันการเปลี่ยนสถานะแบบข้ามขั้นตอน
- **Real-time Notification**: ใช้ Laravel Reverb ในการยิงอัพเดตสถานะของคำร้องแบบ real-time
- **Race Condition Prevention**: ใช้ `lockForUpdate()` และ re-check status ในทรานแซกชั่นขณะอนุมัติ/ทำเสร็จ
- **Priority**: เพิ่มฟิลด์ `priority` (normal/urgent) สำหรับความเร่งด่วนของคำร้อง
- **แยกฟิลด์เหตุผล**: แยก `reason` (ครูกรอกเหตุผลขอทำบัตรใหม่) กับ `admin_notes` (แอดมินจดบันทึกภายใน) และ `rejection_reason` (สาเหตุที่แอดมินปฏิเสธ) ออกจากกันอย่างชัดเจน
- **Link บัตรเก่าอัตโนมัติ**: ระบบจะเลือก `existing_card_id` ของบัตร active ล่าสุดของนักเรียนคนนั้นให้อัตโนมัติเมื่อขอทำบัตรใหม่ (replacement/renewal)
- **Rate limiting / Confirmation**: มี confirmation modal บนหน้าจอครูก่อนจะกดส่ง bulk request

---

## 3. Work Plan 10 ระยะ (Phases 0–9)

### เฟส 0 — Prerequisite Decisions
1. **UI Structure**: ใช้โครงสร้าง (A) วางไว้ใต้ `/admin/student-cards/requests`
2. **Card Lifecycle Policy**: สร้าง row ใหม่เสมอใน `student_cards` พร้อม expire ใบเดิม
3. **Rollover Behavior**: ปิดระบบ auto card sync หลัง rollover โดยสิ้นเชิง
4. **Legacy Card Handling**: ตั้งค่า `origin='legacy'` ให้กับบัตรเก่าในระบบ

### เฟส 1 — Foundation (Backend Schema + State Machine)
1. **Migration**: สร้างตาราง `student_card_requests`
   - ฟิลด์เชื่อมโยง: `academy_id`, `academic_year_id`, `classroom_id`, `student_id` (nullable, set null on delete), `existing_card_id` (nullable), `result_card_id` (nullable)
   - ฟิลด์ระบุประเภท/สถานะ: `request_type` (enum), `status` (enum)
   - Snapshots: `full_name_snapshot`, `student_number_snapshot`, `grade_level_snapshot`, `section_snapshot`
   - เหตุผล/บันทึก: `reason` (ครูส่ง), `admin_notes` (บันทึกภายใน), `rejection_reason` (เหตุผลปฏิเสธ)
   - ผู้ดำเนินการ + Timestamps: `requested_by`, `approved_by`, `processed_by`, `requested_at`, `approved_at`, `started_at`, `completed_at`, `cancelled_at`, `rejected_at`
   - อื่น ๆ: `priority` (enum: normal, urgent), `origin` (enum: teacher, legacy)
2. **Indexes**: สร้าง composite index `(academy_id, status)` และสร้าง partial unique constraint `(student_id, academy_id) WHERE status IN ('pending', 'approved', 'in_progress')`
3. **Model & Enums**: โมเดล `StudentCardRequest` พร้อมใช้ `Auditable` trait และสร้าง PHP 8.4 Backed Enums (`RequestStatus`, `RequestType`, `RequestOrigin`)
4. **StudentCardRequestService**: พัฒนาแกนหลักของ state machine
   - `transition($request, $toStatus, User $actor, array $context = [])`
   - `create(...)`, `bulkCreate(...)`
   - `complete(...)` (ใน transaction พร้อม `lockForUpdate` + expire บัตรเดิม + insert บัตรใหม่)

### เฟส 2 — Policy + Authorization
1. **Permission Integration**: อัพเดท `AcademyRole::SYSTEM_ROLES`
   - `students.cards.request` (สำหรับบทบาท `teacher`)
   - `students.cards.produce` (สำหรับบทบาท `admin`, `owner`, `director`, `card_admin`)
2. **Seeder Update**: รัน `AcademyRoleSeeder` แบบ `updateOrCreate` เพื่อปรับปรุง permissions
3. **StudentCardRequestPolicy**: ตรวจสอบสิทธิ์ระดับ homeroom teacher และสิทธิ์ในการจัดการของ admin
4. **Form Requests**: สร้าง `StoreStudentCardRequestRequest`, `BulkStoreStudentCardRequestRequest`, และ `RejectRequest`

### เฟส 3 — API Layer
- **Routes Base**: `/api/academies/{academy}/student-card-requests`
- **Teacher Endpoints** (สิทธิ์ `students.cards.request` + Homeroom check):
  - `GET /my-classrooms`
  - `GET /classrooms/{classroom}/students`
  - `POST /` และ `POST /bulk` (คืนผลลัพธ์ per-row)
  - `PATCH /{request}/cancel`
- **Admin Endpoints** (สิทธิ์ `students.cards.produce`):
  - `GET /` (Queue filter ตามสถานะ, ชั้น, ห้อง, ความสำคัญ)
  - `GET /{request}` (ดูข้อมูลอย่างละเอียดรวมถึง audit log)
  - `PATCH /{request}/approve`
  - `PATCH /{request}/reject`
  - `PATCH /{request}/start`
  - `PATCH /{request}/complete`
  - `POST /bulk-approve`, `POST /bulk-start`
- **Shared Endpoints**:
  - `GET /counts` (นับสถิติจำนวนคำร้องในระบบ)

### เฟส 4 — Disable Legacy Bulk Creation (สำคัญ)
1. บล็อก endpoint `POST /academies/{academy}/student-cards/admin/sync/commit` (คืนค่า 410 Gone เมื่อ flag ทำงาน)
2. ตัดการเรียก card sync จาก `AcademicYearRolloverService`
3. เพิ่ม Feature Flag `academies.settings.card_request_flow_enabled` เพื่อใช้เปิดปิดฟีเจอร์นี้รายโรงเรียน
4. สร้าง script `students:seed-legacy-card-requests` เพื่อ backfill ข้อมูลการ์ดเดิมที่มีอยู่

### เฟส 5 — Teacher UI
1. พัฒนาส่วนขอทำบัตรนักเรียนภายใต้ `/admin/student-cards/requests`
2. แสดงตารางรายชื่อนักเรียนในห้องเรียน พร้อมแสดง status badge ของบัตรและการส่งคำร้องปัจจุบัน
3. สร้าง `SubmitRequestModal.vue` รองรับการระบุเหตุผลและการเลือกประเภทคำร้องอัตโนมัติ
4. สร้าง composable `useStudentCardRequests.ts`

### เฟส 6 — Admin UI
1. ปรับปรุงหน้าคิวงานการ์ดของแอดมิน แสดงสถานะสถิติแยกการทำงาน
2. ตาราง PrimeVue DataTable กรองข้อมูลละเอียด มี Bulk actions ในการ approve/start
3. หน้าแสดงรายละเอียดคำร้องและ Audit logs พร้อมปุ่มเชื่อมโยงสำหรับการพิมพ์บัตร

### เฟส 7 — Notifications
1. Event Classes สำหรับสเตทของคำร้องทั้งหมด
2. เชื่อมต่อ Reverb Broadcast ในการส่ง notification live ไปยังหน้าผู้ใช้
3. บันทึกและเรียกใช้งานผ่าน `NotificationService`

### เฟส 8 — Tests (การตรวจสอบความถูกต้อง)
- สิทธิ์ครูประจำชั้นในการส่งคำร้องและสิทธิ์ของแอดมิน
- การป้องกันการส่งคำร้องซ้ำซ้อน (partial unique index)
- การทำงานในเงื่อนไขการแก้ไขบัตรหายหรือหมดอายุต้องระบุเหตุผล
- การป้องกัน Race condition ด้วย `lockForUpdate`
- ทดสอบการตัด legacy sync และการทำงานหลังการ rollover
- ทดสอบ bulk submit แบบแยกผลลัพธ์อิสระต่อกัน

### เฟส 9 — Rollout Playbook
1. Deploy โค้ดโดยปิด flag (`false`)
2. รัน script backfill ข้อมูลและเปิด flag (`true`) สำหรับโรงเรียนนำร่อง
3. ทยอยเปิดใช้งานทั่วไปและเคลียร์โค้ดเก่าที่เลิกใช้

---

## 4. ข้อควรระวัง, ไฟล์หลัก และหัวใจของแผน

### ข้อควรระวัง
1. **ครูประจำชั้นเปลี่ยนคน**: ข้อมูลผู้ส่ง (snapshot) จะยังคงสิทธิ์การยื่นเดิม แต่ครูประจำชั้นคนปัจจุบันจะเห็นและจัดการคำร้องต่อของห้องเรียนนั้นได้ (live check)
2. **การเชื่อมระบบพิมพ์บัตร**: เมื่อ complete แอดมินสามารถเปิดหน้าพิมพ์การ์ดใบใหม่ที่ถูกสร้างได้ทันที
3. **การย้ายโรงเรียนของนักเรียน**: ยกเลิกคำร้องของนักเรียนอัตโนมัติ หากนักเรียนถูกย้าย (transfer) ไปยัง academy อื่น

### ไฟล์หลักที่จะเพิ่มหรือแก้ไข

#### Backend
- **สร้างใหม่**:
  - `database/migrations/2026_07_08_000001_create_student_card_requests_table.php`
  - `app/Models/StudentCardRequest.php`
  - `app/Enums/StudentCardRequestStatus.php`
  - `app/Enums/StudentCardRequestType.php`
  - `app/Enums/StudentCardRequestOrigin.php`
  - `app/Services/StudentCardRequestService.php`
  - `app/Http/Controllers/Api/Learn/Student/Card/StudentCardRequestController.php`
  - `app/Http/Requests/StoreStudentCardRequest.php`
  - `app/Http/Requests/BulkStoreStudentCardRequest.php`
  - `app/Http/Requests/RejectStudentCardRequest.php`
  - `app/Policies/StudentCardRequestPolicy.php`
  - `app/Console/Commands/SeedLegacyCardRequests.php`
  - `routes/learn/academy-student-card-request.php`
- **แก้ไข**:
  - `app/Models/AcademyRole.php`
  - `database/seeders/AcademyRoleSeeder.php`
  - `app/Services/StudentCardSyncService.php`
  - `app/Services/AcademicYearRolloverService.php`
  - `app/Services/StudentEnrollmentService.php`

#### Frontend
- **สร้างใหม่**:
  - `ui/pages/academies/[name]/admin/student-cards/requests/index.vue`
  - `ui/pages/academies/[name]/admin/student-cards/requests/queue.vue`
  - `ui/pages/academies/[name]/admin/student-cards/requests/[id].vue`
  - `ui/composables/useStudentCardRequests.ts`
  - `ui/components/school/studentCard/SubmitRequestModal.vue`
  - `ui/components/school/studentCard/RequestStatusBadge.vue`
- **แก้ไข**:
  - `ui/pages/academies/[name]/admin.vue`
  - `ui/pages/academies/[name]/admin/index.vue`

### หัวใจของแผน
> **"Request first, sync never. Snapshot everything."**
> - จัดการต้นเหตุ (ปิด Auto Sync)
> - เก็บ Snapshot ข้อมูลของเด็ก ณ วันที่ขอทำบัตร เพื่อป้องกันข้อมูลสูญหายหรือเพี้ยนในอนาคต
> - ป้องกันข้อขัดแย้งของสถานะ (Race Condition) ด้วย database locks และ state machine ที่เข้มงวด

---

## 2026-07-08 — Roster Reconciliation with Student Code

### การเปลี่ยนแปลงหลัก
- เปลี่ยนขอบเขตจากการเขียนข้อมูลนักเรียนใหม่ทั้งหมด มาเป็นการจัดห้องเรียนใหม่ (Enrollment Reconciliation) โดยใช้ `student_code` เป็นหลัก
- บันทึกและวิเคราะห์ไฟล์ PDF (Companion JSON) เพื่อหาความแตกต่างและจับคู่ห้องเรียน/ครูประจำชั้น

### ไฟล์ที่สร้าง/แก้ไข
- **สร้างใหม่**:
  - [ExtractRosterPdfCommand.php](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/app/Console/Commands/ExtractRosterPdfCommand.php)
  - [RosterReconciliationService.php](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/app/Services/Import/RosterReconciliationService.php)
  - [RosterReconciliationTest.php](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/tests/Feature/RosterReconciliationTest.php)
- **แก้ไข**:
  - [UploadStudentImportRequest.php](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Requests/Academy/Enrollment/UploadStudentImportRequest.php)
  - [StudentImportService.php](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/app/Services/StudentImportService.php)
  - [StudentImportController.php](file:///c:/wamp64/www/nuxnan/api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/StudentImportController.php)
  - [studentImportService.ts](file:///c:/wamp64/www/nuxnan/ui/services/studentImportService.ts)
  - [useStudentImport.ts](file:///c:/wamp64/www/nuxnan/ui/composables/useStudentImport.ts)
  - [StepUpload.vue](file:///c:/wamp64/www/nuxnan/ui/components/academy/student-import/StepUpload.vue)
  - [ImportRowTable.vue](file:///c:/wamp64/www/nuxnan/ui/components/academy/student-import/ImportRowTable.vue)

### ผลการทดสอบ
- รัน `RosterReconciliationTest` ผ่านทั้งหมด 10 assertions
- รัน `StudentImportControllerTest` ผ่านเรียบร้อย

---

## 2026-07-09 - Wallet Withdrawal PromptPay Planning

- Scope is plan-only: add PromptPay as a withdrawal destination alongside bank transfer.
- Current flow: `ui/pages/Earn/Wallet.vue` posts through `ui/composables/useWallet.ts` to `POST /api/wallet/withdraw`; backend validation is in `WalletController::withdraw`; transaction metadata is stored by `WalletService::withdraw`; admin queue reads `metadata.bank_account` in `ui/pages/nuxnan-admin/wallet/pending.vue`.
- Recommended contract: keep `bank_account` metadata for compatibility, add `method='promptpay'`, `bank_account.bank_name='promptpay'`, `bank_account.account_number=<promptpay_id>`, optional `metadata.destination_type='promptpay'`, and normalize digits server-side.
- No migration appears necessary if storing destination details in existing `wallet_transactions.metadata` JSON.
- Related cleanup to include in implementation: align withdrawal minimum (UI says 25, submit/API enforce 100) and fee preview (UI 13%, backend 0.5% min 10).
- Verification plan: focused API feature tests for bank withdrawal still works, PromptPay phone/national-id validation, invalid PromptPay rejected, pending admin response displays destination; frontend build/type check if UI changes.

## 2026-07-09 - Public Student Card Request Button Planning

- Scope is plan-only: add a temporary public "request new student card" action to `/student-card/{level}/{room}` for each displayed student card.
- Existing request infrastructure already exists: `student_card_requests` migration/model/enums, `StudentCardRequestService`, authenticated academy routes in `routes/learn/academy-student-card-request.php`, admin queue pages, `useStudentCardRequests.ts`, and `SubmitRequestModal.vue`.
- Recommended implementation: add a narrow public endpoint under `routes/studentcard/studentcard.php`, guarded by a config flag such as `student-card.public_requests` (or reusing `public_management` if the temporary window is exactly the same), resolve classroom from `{level}/{room}` like `StudentCardManageController`, then create a request through the same service path with `origin=teacher` or a new `public` origin.
- Main contract gap: `StudentCardRequestService::create()` currently requires a non-null `User $actor` and sets `requested_by`; public requests need either nullable/system actor support plus submitter snapshot fields, or a dedicated public create method that sets `requested_by=null` safely.
- Frontend files likely touched: `ui/pages/student-card/[level]/[room].vue`, `ui/components/student-card/StudentCardItem.vue`, optionally reuse/adapt `ui/components/school/studentCard/SubmitRequestModal.vue`, and a small public composable if the call is reused.
- Backend files likely touched: `api/nuxnanravel/config/student-card.php`, `routes/studentcard/studentcard.php`, `StudentCardRequestController` or a small public controller method, `StudentCardRequestService`, `StudentCardRequestOrigin`, and focused tests for public create, duplicate-open prevention, disabled flag, wrong-room/student rejection, and public throttling.
- Risks: current public card endpoint exposes student identity data; this temporary page should be feature-flagged, throttled, not expose admin-only actions, and should prevent duplicate open requests through the existing unique/open-request logic.

## 2026-07-09 - Student69 Homeroom Teacher DB Assignment

- Read `docs/api/student69.pdf` (68 pages) and extracted 53 unique classroom homeroom-teacher headers for academic year 2569.
- Updated live DB `classrooms.homeroom_teacher_id` for academy 1 / academic_year_id 2 on all 53 classrooms found in the PDF.
- Matching summary: 42 exact user matches, 5 loose normalized matches, 3 manual existing-user matches (`ม.1/3` -> user 17406, `ม.2/7` -> user 17403, `ม.4/8` -> user 17069), and 3 placeholder teacher users created for names not found (`ม.1/4` user 17483, `ม.3/4` user 17484, `ม.3/7` user 17485).
- Added/ensured `academy_members` teacher membership (`role=teacher`, `academy_role_id=4`, `status=1`) for the 3 placeholders and existing user 17069.
- Verification: current year 2569 has 54 classrooms total; 53 have homeroom teachers; only `ม.4/9` remains unset because it is not present in `student69.pdf`.

---

## 2026-07-09 — Work Plan (ฉบับละเอียด, ตรวจ codebase จริงแล้ว)

> วางแผนอย่างเดียว ยังไม่เขียนโค้ด · ทุกข้อผ่านการ verify กับไฟล์จริง

### ผลการตรวจ codebase (ยืนยัน/แก้ไข note เดิม)

| # | ข้อเท็จจริงที่ยืนยัน | ผลต่อแผน |
|---|----------------------|-----------|
| A | `student_card_requests.origin` เป็น `string(16)` default `'teacher'` (migration บรรทัด 24) **ไม่ใช่ DB enum** | เพิ่มค่า `'public'` ได้โดย **ไม่ต้องเขียน migration** — แค่เพิ่ม case ใน `StudentCardRequestOrigin` |
| B | `requested_by` เป็น `nullable` + `nullOnDelete` (บรรทัด 32) | ตั้ง `requested_by = null` สำหรับ public ได้ปลอดภัย |
| C | payload บนหน้า public คือ **`StudentCard`** ผ่าน `StudentCardPublicResource` → `id` = **card id**, ส่วน Student FK คือ field **`student_id`** (Resource บรรทัด 28) และ **อาจเป็น null** สำหรับบัตร legacy | ปุ่มต้องส่ง `studentInfo.student_id` (ไม่ใช่ `studentInfo.id`) และต้อง guard กรณี null → 422 |
| D | ทุกใบบนหน้านี้เป็น `student_status = active` (query กรอง active) และ `StudentCardRequestService::create()` จะ **reject** `first_issue` ถ้ามีบัตร active อยู่ (บรรทัด 61-63) | ประเภทคำร้อง public ต้อง default เป็น **`replacement`** (บัตรหาย/ชำรุด) หรือ **`renewal`** — **ห้ามส่ง `first_issue`** |
| E | มี unique index `uq_student_card_request_open` บน `(student_id, academy_id, is_open_flag)` (บรรทัด 47) + service เช็ค `whereIn status pending/approved/in_progress` (บรรทัด 45-48) | กันส่งซ้ำได้ 2 ชั้น (DB + service) โดยไม่ต้องเพิ่ม logic |
| F | `StudentCardManageController::resolveClassroomFromUrl()` เป็น `private` และเป็น logic กลางที่ resolve `{level}/{room}` → classroom (+academy) พร้อมตอบ 409 ถ้าเจอหลายโรงเรียน | ควร **extract เป็น trait/service ที่ใช้ร่วมกัน** เพื่อให้ endpoint ใหม่ใช้ logic เดียวกัน (DRY) |
| G | มี flag ระดับ academy อยู่แล้ว: `academy_settings.card_request_flow_enabled` (migration 2026_07_08 บรรทัด 51-53) | ควร gate **สองชั้น**: global config flag + per-academy flag |
| H | `create()` hardcode `origin = Teacher` และ `requested_by = $actor->id`; รับ `$data['classroom_id']` เป็น optional filter ของ active enrollment | ต้อง refactor แยกแกน แล้วส่ง `classroom_id` ที่ resolve จาก URL เข้าไป → บังคับว่านักเรียนต้องอยู่ห้องตาม URL จริง |
| I | หน้า `[level]/[room].vue` ใช้ pattern **Swal + modal เฉพาะทาง** (AddStudentModal ฯลฯ) และเรียก API ด้วย `$fetch` ผ่าน composable `useClassroomManagement` | ทำ modal + composable public ใหม่ให้เข้ากับ pattern นี้ **ดีกว่า** retrofit `SubmitRequestModal.vue` (ตัวนั้นผูก academy/auth shape) |

---

### ขั้นตอนการทำงาน (ทีละขั้น)

#### ขั้นที่ 1 — Config: เพิ่ม flag public requests
**ไฟล์:** `api/nuxnanravel/config/student-card.php`
- เพิ่ม key `'public_requests' => env('PUBLIC_STUDENT_CARD_REQUESTS', false)`
- แยกจาก `public_management` เพราะ "ส่งคำร้อง" เสี่ยงต่ำกว่า "เพิ่ม/ย้าย/ลบนักเรียน" (เปิด-ปิดอิสระได้)
- อัปเดต `.env.example` (ไม่แตะ `.env` จริง)

#### ขั้นที่ 2 — Enum: เพิ่ม origin `public`
**ไฟล์:** `api/nuxnanravel/app/Enums/StudentCardRequestOrigin.php`
- เพิ่ม `case Public = 'public';`
- **ไม่ต้องมี migration** (column เป็น string อยู่แล้ว — ข้อ A)

#### ขั้นที่ 3 — Extract room resolver ให้ใช้ร่วมกัน (DRY)
**สร้าง:** `app/Http/Controllers/Api/Learn/Student/Card/Concerns/ResolvesStudentCardRoom.php` (trait)
- ย้าย logic `resolveClassroomFromUrl(string $level, string $room): Classroom` ออกมาจาก `StudentCardManageController` (คงพฤติกรรมเดิม: 404 ถ้าไม่พบ, 409 ถ้าหลายโรงเรียน)
- `StudentCardManageController` `use` trait นี้แทน method เดิม (regression risk ต่ำ, พฤติกรรมเท่าเดิม)
- Controller ใหม่ก็ `use` trait เดียวกัน

#### ขั้นที่ 4 — Service: รองรับ public actor (refactor แบบไม่ทำ logic ซ้ำ)
**ไฟล์:** `app/Services/StudentCardRequestService.php`
- แยกแกนกลางของ `create()` เป็น private:
  `buildRequest(Academy $academy, Student $student, ?User $actor, StudentCardRequestOrigin $origin, array $data): StudentCardRequest`
  - ย้าย validation ทั้งหมด (academy match, active enrollment, open-request check, existing-card + request_type rules) มาไว้ที่นี่
  - `'origin' => $origin`, `'requested_by' => $actor?->id`
- `create(...)` เดิม → เรียก `buildRequest($academy, $student, $actor, StudentCardRequestOrigin::Teacher, $data)` (พฤติกรรมเดิมทุกอย่าง)
- เพิ่ม `createPublic(Academy $academy, Student $student, array $data): StudentCardRequest`
  → `buildRequest($academy, $student, null, StudentCardRequestOrigin::Public, $data)`
  - บันทึกที่มาใน `reason` เช่น `"ส่งจากหน้า public /student-card/{level}/{room}"` (+ ชื่อ/เบอร์ผู้แจ้งถ้าเก็บ)

#### ขั้นที่ 5 — Endpoint: public request (แคบ + throttle + gate 2 ชั้น)
**ไฟล์ route:** `routes/studentcard/studentcard.php` (ใน group `student-card` เดิม)
```
Route::middleware('throttle:10,1')
    ->post('{level}/{room}/requests', [StudentCardManageController::class ...หรือ controller ใหม่..., 'submitRequest'])
    ->name('manage.submit-request');
```
**Controller method** `submitRequest(Request $request, string $level, string $room)`:
1. `abort_unless(config('student-card.public_requests'), 403)` — global gate (ข้อ G)
2. resolve classroom จาก trait (ขั้นที่ 3) → ได้ `classroom` + `academy`
3. เช็ค per-academy: `abort_unless(academy_settings.card_request_flow_enabled, 403)` — academy gate
4. validate body: `student_id` (required, int), `request_type` (`in:replacement,renewal` — **ไม่รับ first_issue**, ข้อ D), `reason` (nullable string), optional `requester_name`/`requester_phone` (nullable)
5. โหลด `Student::find(student_id)`; ถ้า null หรือ `student_id` ของ card เป็น null → 422 "บัตรนี้ยังไม่ผูกข้อมูลนักเรียน" (ข้อ C)
6. ยืนยันว่านักเรียนอยู่ห้องตาม URL: ส่ง `$data['classroom_id'] = $classroom->id` เข้า service (service กรอง active enrollment ตาม classroom_id — ข้อ H)
7. เรียก `service->createPublic($academy, $student, $data)` ใน try/catch `ValidationException` → 422 พร้อม message เดิม (เช่น "already has an open card request")
8. ตอบ **slim JSON**: `{ success: true, request_id, status, message: 'ส่งคำร้องแล้ว' }` — ไม่ leak field admin

**หมายเหตุ:** จะทำเป็น method ใน `StudentCardManageController` (ใช้ trait ร่วม) หรือแยก `PublicStudentCardRequestController` ก็ได้ — แนะนำแยก controller เพื่อความชัดของ scope public

#### ขั้นที่ 6 — Frontend composable
**สร้าง:** `ui/composables/usePublicCardRequest.ts` (mirror pattern `useClassroomManagement`)
```ts
async function submitCardRequest(studentId, requestType, reason?, requester?) {
  return $fetch(`${apiBase}/api/student-card/${level}/${room}/requests`, {
    method: 'POST',
    body: { student_id: studentId, request_type: requestType, reason, ...requester }
  })
}
```

#### ขั้นที่ 7 — Frontend UI: ปุ่ม + modal ยืนยัน
**แก้ `ui/components/student-card/StudentCardItem.vue`:**
- เพิ่ม prop `canRequest: Boolean`
- เพิ่มปุ่ม "ขอทำบัตรใหม่" (เช่นในเมนู action มุมขวา หรือปุ่มมุมบัตร) → `emit('request', studentInfo)`
- **ห้ามส่ง `first_issue`** — ปล่อยให้หน้าแม่เลือกประเภท (replacement/renewal)

**แก้ `ui/pages/student-card/[level]/[room].vue`:**
- เพิ่ม state ตรวจว่าเปิด public requests ไหม (อ่านจาก `manage-context` ที่มีอยู่ หรือเพิ่ม field `can_request` ใน context endpoint) → ส่ง `:canRequest` ให้การ์ด
- `openRequestModal(student)` → เปิด modal ใหม่ `RequestCardModal.vue` (สไตล์เดียวกับ AddStudentModal): แสดง **ชื่อเด็กให้ยืนยัน** + เลือกประเภท (บัตรหาย/ชำรุด = replacement, หมดอายุ = renewal) + ช่องเหตุผล (+ ชื่อ/เบอร์ผู้แจ้ง optional)
- submit → `usePublicCardRequest().submitCardRequest(student.student_id, type, reason, requester)`
  - **ใช้ `student.student_id`** (Student FK) ไม่ใช่ `student.id` (card id) — ข้อ C
- UX: ปุ่ม disable+loading ระหว่างส่ง; สำเร็จ → Swal "ส่งคำร้องแล้ว"; ถ้ามีคำร้องค้าง → แสดง message จาก backend ("มีคำร้องที่ยังดำเนินการอยู่แล้ว")

**สร้าง:** `ui/components/student-card/RequestCardModal.vue`

#### ขั้นที่ 8 — (ถ้าจำเป็น) เปิด `can_request` ใน context
**ไฟล์:** `StudentCardManageController::context()`
- เพิ่ม `'can_request' => (bool) config('student-card.public_requests') && (academy card_request_flow_enabled)` ใน response
- ให้หน้า/การ์ดแสดงปุ่มเฉพาะเมื่อเปิดจริง (ตอนนี้ `context()` ตอบแม้ `public_management` ปิด — จึงเป็นที่แขวน flag public_requests ได้พอดี)

#### ขั้นที่ 9 — Tests (backend feature)
**สร้าง:** `tests/Feature/StudentCard/PublicCardRequestTest.php`
- flag global ปิด → `POST .../requests` = **403**
- academy `card_request_flow_enabled` ปิด → **403**
- ส่ง `replacement` สำเร็จ → 200/201, `origin=public`, `requested_by=null`
- ส่งซ้ำเมื่อมี open request (pending/approved/in_progress) → **422**
- `first_issue` บนนักเรียนที่มีบัตร active → **422**
- นักเรียนไม่อยู่ห้องตาม URL (classroom_id ไม่ match) → **422**
- card ที่ `student_id = null` → **422** (ไม่ 500)
- throttle: ยิงเกิน limit → **429**
- `replacement/renewal` เมื่อไม่มีบัตร active → **422** (ตาม logic เดิม)

#### ขั้นที่ 10 — ตรวจสอบ + rollout
- `./vendor/bin/pint` (backend), Nuxt build check (frontend)
- Manual smoke: เปิด flag → เห็นปุ่ม, ส่งคำร้อง, คำร้องโผล่ในคิวแอดมิน `academy-student-card-request` (index กรอง `origin` ได้ ควรเห็น `public`)
- **ปิด flag ทันทีหลังใช้เสร็จ** (ทั้ง global + academy) ตามเจตนาชั่วคราว

---

### ไฟล์สรุป (สร้าง/แก้)

| ไฟล์ | Action |
|------|--------|
| `config/student-card.php` | แก้ — เพิ่ม `public_requests` |
| `.env.example` | แก้ — เพิ่ม `PUBLIC_STUDENT_CARD_REQUESTS` |
| `app/Enums/StudentCardRequestOrigin.php` | แก้ — เพิ่ม `Public` |
| `.../Card/Concerns/ResolvesStudentCardRoom.php` | สร้าง — trait resolve ห้อง |
| `StudentCardManageController.php` | แก้ — ใช้ trait + (option) method `submitRequest` + `can_request` ใน context |
| `PublicStudentCardRequestController.php` | สร้าง (ถ้าเลือกแยก controller) |
| `app/Services/StudentCardRequestService.php` | แก้ — refactor `buildRequest()` + เพิ่ม `createPublic()` |
| `routes/studentcard/studentcard.php` | แก้ — เพิ่ม route `POST {level}/{room}/requests` (throttle) |
| `ui/composables/usePublicCardRequest.ts` | สร้าง |
| `ui/components/student-card/RequestCardModal.vue` | สร้าง |
| `ui/components/student-card/StudentCardItem.vue` | แก้ — ปุ่ม + emit `request` |
| `ui/pages/student-card/[level]/[room].vue` | แก้ — modal + submit ผ่าน `student_id` |
| `tests/Feature/StudentCard/PublicCardRequestTest.php` | สร้าง |

### จุดที่ต้องระวังที่สุด (highlight)
1. **`student_id` vs `id`** บน payload — ส่งผิดจะสร้างคำร้องให้ผิดคน/พัง (ข้อ C)
2. **ห้าม `first_issue`** — จะถูก reject เสมอบนหน้านี้ (ข้อ D)
3. **Gate 2 ชั้น + throttle** — public + PII จึงต้อง flag ปิดได้ทันที (ข้อ G)
4. **ส่ง `classroom_id` เข้า service** — บังคับ scope ห้องตาม URL (ข้อ H)

---

## 2026-07-10 - Course Member Removal / Last Access Group Check

- User reported production errors from 2026-06-30: `PATCH /api/courses/25/members/update-last-access-group` returned 404, and `GET/POST /api/courses/25/members/3843/removal-preview|remove` returned 400 with SQL unknown column around assignment cleanup.
- Local route list currently includes `PATCH api/courses/{course}/members/update-last-access-group`, `GET api/courses/{course}/members/{member}/removal-preview`, and `POST api/courses/{course}/members/{member}/remove`; no `bootstrap/cache/routes*.php` route cache file exists locally.
- Root cause still present for removal preview/remove: `CourseMemberRemovalService` filters `AssignmentAnswer::whereHas('assignment', fn => where('course_id', ...))`, but `assignments` is polymorphic (`assignmentable_type`, `assignmentable_id`) and has no direct `course_id` column. This matches the production SQL error.
- Existing `CourseMemberRemovalTest` passes 5 tests / 14 assertions, but it does not cover `removalPreview()` or assignment-answer cleanup through the polymorphic assignment relation, so it does not catch this bug.
- Worktree note: untracked `api/nuxnanravel/database/migrations/2026_07_10_013214_modify_id_in_user_usage_events_table.php` existed before this check and was not touched.

## 2026-07-10 - Course Member Removal Fix Applied

- Fixed `CourseMemberRemovalService` to find `AssignmentAnswer` rows through polymorphic assignments attached directly to the course, to course lessons, or to course topics instead of querying the nonexistent `assignments.course_id` column.
- Reused the same helper query for both preview counts and execute cleanup, so `removal-preview` and `remove` now follow the same course scope.
- Added regression coverage in `CourseMemberRemovalTest` for preview count across course/lesson/topic assignments and for deleting only answers belonging to the removed course.
- Verification: `./vendor/bin/pint app/Services/CourseMemberRemovalService.php tests/Feature/CourseMemberRemovalTest.php` passed; `php artisan test --filter=CourseMemberRemovalTest` passed 7 tests / 19 assertions. PHPUnit metadata deprecation warnings and local Xdebug log warning are pre-existing/noise.
- Remaining production action: `update-last-access-group` route exists locally, so the reported 404 should be handled by deploying this code and clearing/rebuilding production route cache.

## 2026-07-10 - Course Group Member Removal Route Fix Applied

- Frontend `groups/[groupId].vue` now calls `DELETE /api/courses/{course}/groups/{group}/members/{member.id}` (previously `POST .../unMemberGroup`, which had no matching route).
- Found a duplicate `DELETE /{course}/groups/{group}/members/{member}` route in `routes/learn/course.php` pointing to `unMemberGroup()`, colliding with the `destroy()` route registered under the `/courses/{course}/groups` group. `unMemberGroup()` only nulls `course_members.group_id` and leaves the `course_group_members` row intact, so `isMember`/pending state could go stale.
- At runtime Laravel resolved the URL to `destroy()` (last-registered wins on identical URIs), but that is fragile under `route:cache`. Removed the colliding duplicate route so `destroy()` (which deletes the `course_group_members` row AND resets `CourseMember` group state) is the unambiguous handler.
- Added `CourseGroupMemberRemovalTest` asserting the DELETE endpoint removes the `course_group_members` row and resets `course_members.group_id`/`group_member_status` while keeping the course membership. Pint + test pass (1 test / 6 assertions).

## 2026-07-10 - Withdrawal Minimum 25 THB Planning

- User requested a plan to resolve inconsistent withdrawal minimums and set the minimum withdrawable amount to 25 THB.
- Current inspection: `WalletController::withdraw()` already validates `amount` with `min:25`; `useWallet.canWithdraw()` also uses 25; `Earn/Wallet.vue` has input `min="25"`, disabled guard `withdrawForm.amount < 25`, and Thai helper text saying minimum 25 THB.
- Likely remaining mismatch is UX/default affordance: `Earn/Wallet.vue` initializes `withdrawForm.amount` to 100 and quick withdrawal chips start at `[100, 500, 1000, 2000, 5000]`, so users never see 25 as a selectable minimum even though validation allows it.
- Proposed implementation files: `ui/pages/Earn/Wallet.vue`, `ui/composables/useWallet.ts`, `ui/tests/useWallet.spec.ts`, `api/nuxnanravel/app/Http/Controllers/Api/WalletController.php`, `api/nuxnanravel/tests/Feature/Wallet/WithdrawTest.php`; optional config/constants only if the team wants a single source of truth for minimum/fee.
- Verification plan: add/confirm tests for 24 rejected and 25 accepted, add unit coverage for `canWithdraw(25)`, then run focused Wallet feature tests and frontend wallet unit/build checks.

## 2026-07-10 - Academy Admin Settings SQL Unknown Column Inspection

- User reported `SQLSTATE[42S22] Unknown column 'description'` on `/academies/{name}/admin/settings` when saving academy settings.
- Flow traced: `ui/pages/academies/[name]/admin/settings.vue` posts `FormData` to `POST /api/academies/{academy}/settings`; route exists and maps to `Api\Learn\Academy\AcademyController@updateSettings`.
- Root cause: `updateSettings()` fills `academies` with `name`, `name_en`, `description`, `description_en`, `email`, `phone`, `website`, `address`, `province`, `country`, but the live DB columns for `academies` are only `id,user_id,name,slogan,address,email,phone,director,established_year,type,accreditation,accreditation_body,total_students,total_teachers,membership_fees_points,courses_offered,facilities,academy_timings,holidays,social_media_links,student_editable_fields,approval_flow,logo,cover,created_at,updated_at`.
- Secondary mismatch: `updateSettings()` writes `academy_settings.privacy`, `allow_student_registration`, `allow_parent_registration`, `show_member_list`, and `show_course_list`, but live `academy_settings` has only `id,academy_id,auto_accept_members,card_request_flow_enabled,created_at,updated_at`.
- `AcademyResource` also does not return the settings page's direct fields (`description`, `name_en`, `description_en`, `website`, `province`, `country`) and only exposes `setting`.
- Recommended fix: add an idempotent migration for the intended missing academy/profile/settings columns, update `Academy::$fillable`/casts as needed, align `AcademyResource`, and add focused backend coverage for settings update with all fields.

### Work Plan — Academy Admin Settings Schema Fix (ฉบับละเอียด, 2026-07-10) — DONE
- **Status**: Completed on 2026-07-10
- **Summary**:
  - Resolved `SQLSTATE[42S22] Unknown column 'description'` settings error by adding missing fields in migrations.
  - Added casts for settings flags in `AcademySetting`.
  - Added request validation, `name_slug` collision checks, and direct `join_mode` mapping in `AcademyController`.
  - Flattened nested configurations and added new properties to `AcademyResource` to resolve state resets on UI reload.
  - Switched `settings.vue` avatar preview reference from `avatar` to `logo_url`/`logo`.
  - Wrote and passed the `AcademySettingsUpdateTest` (4 tests, 52 assertions).
  - Formatted codebase using Laravel Pint.

#### ขั้นที่ 1 — ยืนยัน schema จริงบน DB (read-only, กันพลาด) - DONE
#### ขั้นที่ 2 — เขียน migration แบบ idempotent - DONE (Migration `2026_07_10_000001_add_settings_fields_to_academies_and_settings.php` run successfully)
#### ขั้นที่ 3 — อัพเดท Models - DONE (`Academy.php` and `AcademySetting.php` updated)
#### ขั้นที่ 4 — ปรับ AcademyController@updateSettings - DONE (`AcademyController.php` updated with validation, non-lossy join_mode & unique slug checks)
#### ขั้นที่ 5 — ปรับ AcademyResource - DONE (`AcademyResource.php` updated with flattened configuration mapping)
#### ขั้นที่ 6 — ปรับ Frontend `settings.vue` - DONE (`settings.vue` line 106 updated)
#### ขั้นที่ 7 — ทดสอบ - DONE (Feature test `AcademySettingsUpdateTest.php` created and verified)
#### ขั้นที่ 8 — Verify ปลายทาง - DONE

**สรุปไฟล์ที่แตะ:**
- `database/migrations/2026_07_10_000001_add_settings_fields_to_academies_and_settings.php` [NEW]
- `app/Models/Academy.php` [MODIFY]
- `app/Models/AcademySetting.php` [MODIFY]
- `app/Http/Controllers/Api/Learn/Academy/AcademyController.php` [MODIFY]
- `app/Http/Resources/Learn/Academy/AcademyResource.php` [MODIFY]
- `ui/pages/academies/[name]/admin/settings.vue` [MODIFY]
- `tests/Feature/Academy/AcademySettingsUpdateTest.php` [NEW]


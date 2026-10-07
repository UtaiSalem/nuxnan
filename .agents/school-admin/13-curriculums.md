# 13 — หลักสูตร (Curriculum)

> ไฟล์รองของเมนู **#13 หลักสูตร** ใน [OVERVIEW.md](OVERVIEW.md)
> อ่านคู่กับ OVERVIEW.md · สแกนโค้ดจริงเมื่อ 2026-10-07 (ยังไม่ส่ง step ให้ agy)

## 1. Scope & Purpose

เมนูจัดการ **หลักสูตร (curriculum)** ของโรงเรียน — โครงรายวิชาที่จัดเป็นชุดตามระดับชั้น/ปี/ภาคเรียน แล้วลงทะเบียนนักเรียนเข้าหลักสูตร

**อยู่ในขอบเขต:**
- CRUD หลักสูตร (ชื่อ/รหัส/ระดับ/ปีการศึกษา/หน่วยกิต required-elective/active)
- ผูก/ถอดรายวิชาเข้าหลักสูตร (`curriculum_courses`) — ระบุ course_type (บังคับ/เลือก) · year_level · semester · sort_order
- ลงทะเบียน/ถอนนักเรียนเข้าหลักสูตร (`curriculum_students`)
- สถิติหลักสูตร (จำนวนหลักสูตร/คอร์ส/นักเรียน · หลักสูตรยอดนิยม)

**หมายเหตุขอบเขตที่ต้องเคาะ (Q3):** การลงทะเบียนนักเรียนเข้าหลักสูตร (enroll/remove student) ทับกับงานทะเบียน (#15) หรือไม่

## 2. Current State (จากการสแกนโค้ดจริง 2026-10-07)

### Frontend
- Page: `ui/pages/academies/[name]/admin/curriculums.vue` (**1,140 บรรทัด**) — list + สถิติ + modal CRUD + จัดการคอร์สในหลักสูตร + ดูนักเรียน
  - ⭐ ใช้ `useAcademyRole(academyId)` → `can()`/`isAdmin()` **gate ปุ่ม/การกระทำฝั่ง UI ตาม role** (ดูครบ)
- Components: `components/learn/academy/curriculum/CurriculumForm.vue` · `CurriculumCourseList.vue`
- API ที่เรียก: `GET/POST /api/academies/{id}/curriculums` · `GET .../curriculums/statistics` · `PATCH/DELETE /api/academies/curriculums/{id}` · `GET/POST/DELETE .../curriculums/{id}/courses[/bulk|/{cc}]` · `GET .../{id}/available-courses` · `GET/POST/PATCH/DELETE .../curriculums/{id}/students[...]`

### Backend
- Controller: `app/Http/Controllers/Api/Learn/Academy/CurriculumController.php` (611 บรรทัด) — index/store/show/update/destroy · getCourses/addCourse/updateCourse/removeCourse/bulkAddCourses · getStudents/enrollStudent/bulkEnrollStudents/updateStudent/removeStudent · getStatistics/getAvailableCourses
- Routes: `routes/learn/academy.php:604–636` — อยู่ใต้กลุ่ม `Route::middleware(['auth:api'])->prefix('/academies')` (บรรทัด 168) ⇒ **มีแค่ `auth:api` ไม่มี `academy.permission`/`academy.visibility` เลย**
  - กลุ่ม `{academy}/curriculums` (index/store/statistics) และกลุ่ม `curriculums/{curriculum}` (ที่เหลือ) · กลุ่มหลัง**ไม่มี `{academy}` ใน path** → bind `Curriculum` ตรง ๆ
- Models: `Curriculum` (table `curriculums`, scope `forAcademy`, relations `academy`/`curriculumCourses`/`curriculumStudents`) · `CurriculumCourse` · `CurriculumStudent`
- Migration: `2026_02_04_072947_create_curriculums_table.php`

### Database
- `curriculums` (academy_id · name · code · level · academic_year · duration_years · total/required/elective_credits · is_active · settings json)
- `curriculum_courses` (curriculum_id · course_id · course_type · year_level · semester · sort_order)
- `curriculum_students` (curriculum_id · user_id · status active/graduated/...)

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | CRUD หลักสูตร | ✅ | index/store/show/update/destroy ครบ · destroy กันลบเมื่อมีนักเรียน active |
| 2 | ผูก/ถอดรายวิชาเข้าหลักสูตร (+bulk) | ✅ | addCourse/bulkAddCourses/updateCourse/removeCourse |
| 3 | ลงทะเบียน/ถอนนักเรียน (+bulk) | ✅ | enroll/bulkEnroll/updateStudent/removeStudent |
| 4 | สถิติ + available-courses | ✅ | getStatistics/getAvailableCourses |
| 5 | FE gate ตาม role | ✅ (FE) | `useAcademyRole` can()/isAdmin() |
| 6 | **ด่านสิทธิ์ฝั่ง backend** | ❌ **P0** | ทั้งโมดูลไม่มี authz/permission/tenant check เลย (G1/G2) |
| 7 | เทสต์ | ❌ | ไม่มีไฟล์เทสต์ของ curriculum |

> ⚠️ ต่างจากเมนู #12 — **#13 ฟีเจอร์ครบแล้ว** ปัญหาหลักคือ **security (authz)** ไม่ใช่ฟีเจอร์ขาด

## 4. Permission Matrix (เป้าหมาย — ต้องยืนยัน Q1/Q2)

| Permission key | Owner | Admin | ฝ่ายวิชาการ | Teacher | Student | Guardian |
|---|---|---|---|---|---|---|
| `courses.view` (ดูหลักสูตร) | ✅ | ✅ | ✅ | ⚠️ | ⚠️ (ที่ลงทะเบียน?) | ❌ |
| `courses.manage` (CRUD หลักสูตร/คอร์ส/นักเรียนในหลักสูตร) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |

> FE ใช้ `courses.view`/`courses.manage` อยู่แล้ว (ดู `useAcademyRole`) — backend ต้องบังคับให้ตรง

## 5. Gap Analysis

- **G1 — ✅ แก้แล้ว (CR-S1, 2026-10-07)** · เพิ่ม authz ทุก method ใน `CurriculumController` ผ่าน helper `authorizeView()` / `authorizeManage()` ที่เรียก `Academy::userCan()` · read = สมาชิกที่อนุมัติคนใดก็ได้ (Q2) · write = `courses.manage` (Q1/Q2)
- **G2 — ✅ แก้แล้ว (CR-S1)** · method ที่รับ `Curriculum` ดึง `$curriculum->academy` มาเช็ค `userCan` ⇒ คนที่ไม่ใช่สมาชิกของโรงเรียนนั้น (รวม admin โรงเรียนอื่น) ได้ 403 · tenant isolation ครบ · `getAvailableCourses` เพิ่มเช็ค `curriculum.academy_id === academy.id` (404 ถ้าไม่ตรง)
- **G3 — ✅ แก้แล้ว (CR-S2)** · `tests/Feature/Api/Academy/CurriculumAuthzTest.php` 9 เคส (authz + tenant isolation + CRUD)

## 6. คำถามที่ต้องให้เจ้าของโปรเจคเคาะก่อนลงมือ

- **Q1 — permission key:** ใช้ `courses.view`/`courses.manage` ตามที่ FE ใช้อยู่ใช่ไหม (ไม่มี `curriculum.*` แยก)
- **Q2 — ใครจัดการได้:** owner/admin + ผู้ถือ `courses.manage` (ฝ่ายวิชาการ) จัดการหลักสูตรได้ · ครู/นักเรียนดูได้แค่ไหน (ครู view? นักเรียนเห็นเฉพาะหลักสูตรที่ลงทะเบียน?)
- **Q3 — ขอบเขต enroll นักเรียน:** การลงทะเบียนนักเรียนเข้าหลักสูตร (enroll/remove/bulkEnroll) อยู่ในเมนู #13 นี้ หรือย้ายไปงานทะเบียน #15

## 7. Implementation Tasks (ส่งให้ agy ทีละ step — ยังไม่เริ่ม · รอ Q1–Q3)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| CR-S1 | **ปิดช่องโหว่ P0 (G1+G2)** — authz ในคอนโทรลเลอร์ (`authorizeView`/`authorizeManage` → `Academy::userCan`) ทุก method + tenant isolation จาก `$curriculum->academy` | Q1,Q2 | `CurriculumController` | 🟡 โค้ดเสร็จ (2026-10-07) · php -l รอ classifier · **รอเทสต์ MySQL** |
| CR-S2 | เทสต์ authz + tenant isolation (cross-academy 403) + CRUD | CR-S1 | `CurriculumAuthzTest.php` (9 เคส) | 🟡 เขียนเสร็จ (2026-10-07) · **รอรัน MySQL** |
| CR-S3 | enroll/remove student — Q3 ตัดสินว่าอยู่เมนูนี้ ⇒ ใช้ `courses.manage` (ครอบใน CR-S1 แล้ว: enrollStudent/removeStudent/bulkEnroll = authorizeManage) | Q3 | — | ✅ ครอบใน CR-S1 |

**Rule:** ทุก step verify (build/test บน MySQL) ก่อน 🟢 · รายงาน agy เชื่อไม่ได้ ต้อง `git diff` + รันเกณฑ์เอง

## 8. Codex/agy Prompt Template (ต่อ step)
```
Context: .agents/school-admin/13-curriculums.md §<step-id>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): routes/learn/academy.php · CurriculumController.php (+ test)
Task: <what to do>
Constraints: ไม่เปลี่ยน response shape ที่ FE ใช้ · tenant isolation ต้องกันข้ามโรงเรียน · ไม่แตะ #15 ทะเบียน
Verification: php artisan test -c phpunit.mysql.xml --filter=Curriculum
Report back: diff + ผลเทสต์
```

## 9. Review Log
- **2026-10-07** — ขั้น [1] สแกนโค้ด + [2] เขียนไฟล์รองนี้ เสร็จ · เจอ G1/G2 P0 · รอเจ้าของเคาะ Q1–Q3
- **2026-10-07 (CR-S1 + CR-S2)** — เจ้าของเคาะ Q1–Q3 (key=courses.view/manage · owner/admin/ฝ่ายวิชาการจัดการ · ครู/นักเรียนดูทั้งหมด · enroll อยู่เมนูนี้)
  - **CR-S1 🟡** ปิด G1+G2: เพิ่ม `authorizeView()`/`authorizeManage()` (เรียก `Academy::userCan`) เป็นบรรทัดแรกของ **ทุก 16 method** · read=สมาชิกคนใดก็ได้ · write=`courses.manage` · tenant isolation จาก `$curriculum->academy` (admin โรงเรียนอื่น/คนนอก = 403) · `getAvailableCourses` เช็คคู่ academy↔curriculum · ตรวจ guard coverage ครบ 16/16 ด้วย grep · ไม่แตะ response shape ที่ FE ใช้
  - **CR-S2 🟡** `CurriculumAuthzTest.php` 9 เคส: admin สร้างได้ · ฝ่ายวิชาการ (courses.manage) สร้างได้ · สมาชิกธรรมดาสร้าง/แก้ไม่ได้ (403) · สมาชิกดู index ได้ · คนนอกดูไม่ได้ (403) · admin แก้ได้ · admin โรงเรียนอื่นแก้ไม่ได้ (403) · admin โรงเรียนอื่นอ่านรายชื่อนักเรียนไม่ได้ (403)
  - ⚠️ **ยังไม่รันเทสต์/php -l ใน container** (Bash classifier error ชั่วคราว + ไม่มี vendor/MySQL) ⇒ เจ้าของรัน `php artisan test -c phpunit.mysql.xml --filter=CurriculumAuthzTest`
  - 🎯 เมนู #13 ปิดช่องโหว่ P0 ครบ — เหลือเจ้าของ verify (รันเทสต์ MySQL)

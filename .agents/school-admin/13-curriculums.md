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

- **G1 — 🔴 P0: ทั้งโมดูล curriculum ไม่มีด่านสิทธิ์ backend เลย** · `CurriculumController` ไม่มี `isAdmin`/`userCan`/`abort(403)`/tenant check สักจุด · route มีแค่ `auth:api` ⇒ **ผู้ใช้ที่ล็อกอินคนไหนก็ได้** สร้าง/แก้/ลบหลักสูตร · ผูก/ถอดคอร์ส · ลงทะเบียน/ถอนนักเรียน ของโรงเรียนไหนก็ได้ · FE gate ด้วย role แต่ยิง API ตรงข้ามได้หมด (แพตเทิร์นเดียวกับเมนู #8 G1)
- **G2 — 🔴 cross-tenant: กลุ่ม `curriculums/{curriculum}` ไม่มี `{academy}` ใน path** · show/update/destroy/courses/students bind `Curriculum` ด้วย id ตรง ๆ ⇒ เดา id ของโรงเรียนอื่นแล้วอ่าน/แก้/ลบได้ (รวมรายชื่อนักเรียน `getStudents` คืน user name/email/photo) · ต้องเพิ่ม tenant check ว่า user เป็น admin/สมาชิกของ `$curriculum->academy_id`
- **G3 — ไม่มีเทสต์** · ควรมีชุด authz + CRUD + tenant isolation (เหมือน `AcademyCourseCrudTest` ของ #12)

## 6. คำถามที่ต้องให้เจ้าของโปรเจคเคาะก่อนลงมือ

- **Q1 — permission key:** ใช้ `courses.view`/`courses.manage` ตามที่ FE ใช้อยู่ใช่ไหม (ไม่มี `curriculum.*` แยก)
- **Q2 — ใครจัดการได้:** owner/admin + ผู้ถือ `courses.manage` (ฝ่ายวิชาการ) จัดการหลักสูตรได้ · ครู/นักเรียนดูได้แค่ไหน (ครู view? นักเรียนเห็นเฉพาะหลักสูตรที่ลงทะเบียน?)
- **Q3 — ขอบเขต enroll นักเรียน:** การลงทะเบียนนักเรียนเข้าหลักสูตร (enroll/remove/bulkEnroll) อยู่ในเมนู #13 นี้ หรือย้ายไปงานทะเบียน #15

## 7. Implementation Tasks (ส่งให้ agy ทีละ step — ยังไม่เริ่ม · รอ Q1–Q3)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| CR-S1 | **ปิดช่องโหว่ P0 (G1+G2)** — ใส่ `academy.permission:courses.view`/`courses.manage` ให้กลุ่ม `{academy}/curriculums` + ทำ tenant+permission guard ให้กลุ่ม `curriculums/{curriculum}` (เช็ค user เป็น admin/ผู้มีสิทธิ์ของ `$curriculum->academy_id`) | Q1,Q2 | routes + controller/middleware | ⚪ pending |
| CR-S2 | เทสต์ authz + tenant isolation (cross-academy 403) + CRUD happy-path | CR-S1 | `tests/Feature/Api/Academy/CurriculumAuthzTest.php` | ⚪ pending |
| CR-S3 | (ถ้า Q3 ตัดสินว่า enroll อยู่เมนูนี้) ทวนสิทธิ์ enroll/remove student ให้สอดคล้อง | Q3 | — | ⚪ pending |

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
- **2026-10-07** — ขั้น [1] สแกนโค้ด + [2] เขียนไฟล์รองนี้ เสร็จ (claude) · ฟีเจอร์ครบแต่เจอ **G1/G2 ช่องโหว่สิทธิ์ระดับ P0** (ทั้งโมดูลไม่มี authz + รั่วข้ามโรงเรียน) · ยังไม่ส่ง step · รอเจ้าของเคาะ Q1–Q3 ก่อนเริ่ม CR-S1

# 19 — ผลการเรียน (Grades / Gradebook)

> ไฟล์รองของเมนู **#19 ผลการเรียน** (`admin/gradebook/`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> สถานะ: 🔎 **audit เสร็จ 2026-10-12 — ขั้น [1] อ่าน + [2] หา gap เสร็จ · ยังไม่ส่ง step ให้ agy · รอเจ้าของเคาะ Q1–Q6**

---

## 1. Scope & Purpose

เมนู "ผลการเรียน" คือศูนย์กลางการจัดการ**ผลการเรียนระดับโรงเรียน** (academy-level) ซึ่งต่างจาก gradebook รายวิชา (`Learn/Courses/[id]/gradebook/*` ที่ครูใช้ตัดเกรดในคอร์สตัวเอง) เมนูนี้รวมงาน:

- **ปีการศึกษา/ภาคเรียน** (`academic-years`) — โครงสร้างปี/เทอมของโรงเรียน
- **เกณฑ์การให้เกรด** (`grade-scales` + `assessment-categories`) — สเกลเกรด (เช่น 4/3.5/3…) และหมวดคะแนน
- **ระเบียนผลการเรียน/Transcript** (`transcripts`) — สร้าง/เผยแพร่ transcript รายภาค-รายปี + ภาพรวมโรงเรียน
- **ผลเรียนรายห้อง/รายวิชา/รายคน** (`gradebook/classrooms`, `gradebook/subjects`, `gradebook/students/{id}/transcript`)
- **เลื่อนชั้น/ขึ้นปีการศึกษา** (`rollover`) — wizard ย้ายนักเรียนขึ้นปีถัดไป (ผูกกับ #15 ทะเบียน)

**ผู้ใช้ที่เกี่ยวข้อง:** ฝ่ายวิชาการ/นายทะเบียน (จัดการ), ครู (ดู + ตัดเกรดคอร์สตน), นักเรียน (ดูของตน), ผู้ปกครอง (ดูของบุตรหลาน)

---

## 2. Current State (จากการสแกนโค้ดจริง 2026-10-12)

### Frontend
Pages (`ui/pages/academies/[name]/admin/gradebook/`):
- `index.vue` — แดชบอร์ดผลการเรียน
- `academic-years.vue` — จัดการปี/ภาคเรียน
- `grade-scales.vue` — เกณฑ์เกรด
- `subjects.vue` — ผลเรียนรายวิชา
- `classrooms/index.vue` + `classrooms/[id].vue` — ผลเรียนรายห้อง
- `students/index.vue` + `students/[studentId]/transcript.vue` — รายคน + transcript
- `transcripts/index.vue` — จัดการ/เผยแพร่ transcript
- `rollover/index.vue` (wizard 4 ขั้น) + `rollover/history.vue`

เมนูซ้าย (`ui/pages/academies/[name]/admin.vue:183-186`):
```js
{ name: 'ผลการเรียน', to: `.../admin/gradebook`, show: can('grades.view') || can('academy.view') }
```
→ เกตด้วย `grades.view` **หรือ** `academy.view` (กว้างมาก — สมาชิกเกือบทุกคนเห็นเมนูนี้)

ไม่มี composable/store เฉพาะของ gradebook (`ui/composables/`, `ui/stores/` ไม่มีไฟล์ grade*/transcript*) — เพจเรียก API ผ่าน `useApi` ตรง

### Backend
Routes: `routes/learn/academy.php` ภายใต้กลุ่มใหญ่ `Route::middleware(['auth:api'])->prefix('/academies')` (บรรทัด 168) — **กลุ่มนี้มีแค่ `auth:api` ไม่มีด่านโรงเรียน/สิทธิ์ที่ระดับกลุ่ม**

| Prefix | บรรทัด | middleware ที่ระดับ route group | หมายเหตุ |
|---|---|---|---|
| `{academy}/academic-years` | 460 | `academy.visibility:content` + `academy.permission` · write เติม `courses.manage` | ใช้ **`courses.manage`** ไม่ใช่ `grades.manage` |
| `{academy}/grade-scales` | 481 | **ไม่มีเลย** | อาศัยด่านใน controller (เฉพาะ write) |
| `{academy}/assessment-categories` | 488 | **ไม่มีเลย** | อาศัยด่านใน controller |
| `{academy}/transcripts` | 494 | **ไม่มีเลย** | อาศัย `canManageTranscripts`/`canViewTranscript` ใน controller |
| `{academy}/students` | 502 | **ไม่มีเลย** | (ซ้อนกับ #15) |
| `{academy}/rollover` | 585 | **ไม่มี `academy.permission`** | อาศัย `Gate::allows('enrollment.*')` ใน controller |

Controllers (`app/Http/Controllers/Api/Learn/Academy/`):
- `AcademicYearController` — write เช็ก role hardcode ภายใน (`owner/director/admin`) **ซ้ำกับ** route middleware `courses.manage`
- `GradeScaleController` — **`index`/`show` ไม่มีด่านเลย** (อ่าน grade-scale ของ academy ใดก็ได้ · `index` ยัง **สร้าง default scale เป็น side-effect ของ GET**); write เช็ก `canManage()` = role ∈ `owner/director/admin` (`:258-273`)
- `TranscriptController` — `canManageTranscripts()` role ∈ `owner/director/admin` (`:373-388`) · `canViewTranscript()` = เจ้าของ transcript **หรือ** member role ∈ `owner/director/admin/teacher` · parent path เป็น TODO (`:361-362` "Add guardian logic if needed")
- `RolloverController` — ใช้ `Gate::allows('enrollment.viewBatches'/'enrollment.undo')` (ผูก EnrollmentPolicy ของ #15)
- `CourseCompletionController`, `GradeAppealController`, `ExamEligibilityController` — ระดับคอร์ส (`routes/course-completion.php`) ไม่ใช่เมนูนี้โดยตรง แต่ feed ข้อมูลเข้า transcript

Models (`app/Models/`): `AcademicYear`, `Semester`, `GradeScale`, `GradeScaleItem`, `AssessmentCategory`, `SemesterTranscript`, `SemesterTranscriptItem`, `AnnualTranscript`, `CourseGrade`, `CourseMemberGradeSnapshot`, `GradebookAssessment`, `GradebookScore`, `GradeAppeal`, `GradeEditLog`

### Permission keys ที่มีอยู่ (`app/Models/AcademyPermission.php:105-108,154`)
`grades.view` · `grades.manage` · `grades.view.own` · `grades.view.all` · `children.grades.view`
แจกให้บทบาท (`app/Models/AcademyRole.php`): director/admin ได้ `grades.view`+`grades.manage` (`:65,92`) · teacher ได้ `grades.view`+`gradebook.manage` (`:112,116`) · student ได้ `grades.view.own` (`:193`) · parent ได้ `children.grades.view` (`:209`) · **registrar (นายทะเบียน) ไม่ได้ `grades.*` เลย** (`:127-137`)

### Tests
- `tests/Feature/SchoolAcademicModulesTest.php` — subjects + academic-years: owner CRUD ได้ / plain-member 403 (ผูกด่าน `courses.manage`) — **ไม่ครอบ custom role ที่ถือ `grades.manage`**
- `tests/Feature/GPAAndPerformanceTest.php` — คำนวณ GPA ระดับ model เท่านั้น
- **ไม่มีเทสต์ authz ของ grade-scales / transcripts / การบังคับคีย์ `grades.*`**

---

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | ปี/ภาคเรียน CRUD | ✅ | มี · แต่เกตด้วย `courses.manage` |
| 2 | เกณฑ์เกรด CRUD | ⚠️ | write มีด่าน role · **read เปิดโล่งข้ามโรงเรียน** |
| 3 | หมวดการประเมิน | ⚠️ | ไม่มีด่านที่ route · ต้องตรวจ controller |
| 4 | Transcript สร้าง/เผยแพร่ | ⚠️ | ด่าน role hardcode (ไม่ใช่ `grades.manage`) |
| 5 | ผลเรียนรายห้อง/วิชา/คน | ⚠️ | ต้องตรวจด่านอ่านแต่ละเพจ |
| 6 | Rollover ขึ้นปี | ✅ | มี wizard + Gate · ผูก #15 |
| 7 | นักเรียนดูผลตน | ⚠️ | ด่าน ad-hoc (`user_id ===`) ไม่ผูก `grades.view.own` |
| 8 | ผู้ปกครองดูผลบุตร | ❌ | TODO ใน `canViewTranscript` · `children.grades.view` ไม่ถูกใช้ |
| 9 | คีย์ `grades.view`/`grades.manage` บังคับใช้จริง | ❌ | **dead keys** — ไม่ถูกเช็กที่ไหนเลย |

---

## 4. Permission Matrix (ที่ *ควรจะ* เป็น — รอเจ้าของยืนยัน)

| Permission key | Owner | Director | Admin | Registrar/ฝ่ายวิชาการ (custom) | Teacher | Staff | Student | Guardian |
|---|---|---|---|---|---|---|---|---|
| `grades.view` (อ่านผลทั้งโรงเรียน) | ✅ | ✅ | ✅ | ✅ (ถ้าให้คีย์) | ✅ | ❌ | ❌ | ❌ |
| `grades.manage` (สร้าง/เผยแพร่/แก้ transcript, เกณฑ์, ปี) | ✅ | ✅ | ✅ | ✅ (ถ้าให้คีย์) | ❌ | ❌ | ❌ | ❌ |
| `grades.view.own` | — | — | — | — | — | — | ✅ | — |
| `children.grades.view` | — | — | — | — | — | — | — | ✅ |

> **ปัญหาปัจจุบัน:** matrix นี้บังคับใช้ไม่ได้ เพราะ backend เช็ก `member.role` ไม่ใช่คีย์ → custom role ที่ถือ `grades.manage` ถูกบล็อก

---

## 5. Gap Analysis

- **G1 (authz model — หลัก):** backend บังคับสิทธิ์ด้วย `member.role IN (owner,director,admin[,teacher])` แบบ hardcode (`GradeScaleController:269-272`, `TranscriptController:365-368,384-387`, `AcademicYearController`) **ไม่ใช่คีย์ `grades.view`/`grades.manage`** ที่ออกแบบไว้ → คีย์ `grades.*` เป็น dead keys · custom role (เช่น หัวหน้าฝ่ายวิชาการ) ที่ถือ `grades.manage` จัดการไม่ได้ · ขัดทิศทางที่ #11 (schedule → `Academy::userCan`) และ #15 (EnrollmentPolicy รับ `students.manage`) วางไว้
- **G2 (defense-in-depth ที่ route):** กลุ่ม `grade-scales` / `assessment-categories` / `transcripts` / `rollover` ไม่มี `academy.permission` ที่ระดับ route group (ต่างจาก `academic-years`/`subjects` ที่มี) — พึ่งด่านใน controller ล้วน ถ้า method ไหนลืมเช็กจะรั่วทันที
- **G3 (cross-tenant read — P0):** `GradeScaleController::index/show` **ไม่มีด่านใด ๆ** ⇒ ใครล็อกอินก็อ่าน grade-scale ของโรงเรียนไหนก็ได้ด้วยการเดา `academy_id` · ซ้ำร้าย `index` **เขียน default scale ลง DB เป็น side-effect ของ GET** (คนนอกยิง GET สร้างข้อมูลในโรงเรียนคนอื่นได้)
- **G4 (key drift):** `academic-years` write เกตด้วย `courses.manage` (route) · grade-scales/transcripts เกตด้วย role (controller) · เมนู FE เกตด้วย `grades.view`/`academy.view` → เมนูเดียวมีแหล่งอำนาจ 3 แบบไม่ตรงกัน
- **G5 (FE↔BE mismatch):** เมนู `show: can('grades.view') || can('academy.view')` — (ก) `academy.view` กว้างจนสมาชิกทั่วไปเห็นเมนู "ผลการเรียน" (PII) · (ข) ผู้ถือ `grades.view` อย่างเดียว (เช่น custom role) เห็นเมนู กดเข้าไปแล้วโดน 403 ที่ backend เพราะ role ไม่อยู่ใน list — ซ้ำรอย #14 F1/#15 G1
- **G6 (parent/guardian path ยังไม่ต่อ):** `canViewTranscript` มี TODO "Add guardian logic if needed" (`:361-362`) · คีย์ `children.grades.view` ไม่ถูกใช้ · เส้น `children/{student}/grades` (`:374`) ยิง `ParentDashboardController` — ต้องตรวจว่าผูก `guardians.user_id` (ที่รู้ว่าว่างทั้งตารางจาก #6/#11 SC-S7) หรือไม่
- **G7 (tests):** ไม่มีเทสต์ authz ของ grade-scales/transcripts และไม่มีเทสต์พิสูจน์การบังคับคีย์ `grades.*`

---

## 6. คำถามที่ต้องให้เจ้าของเคาะก่อนลงมือ (Q1–Q6)

- **Q1 — แหล่งความจริงของสิทธิ์:** ย้ายทั้งเมนูไปใช้ `Academy::userCan('grades.view'/'grades.manage')` แบบเดียวกับ #11/#15 (เลิกเช็ก role hardcode) ใช่ไหม? → ทำให้คีย์ `grades.*` มีผลจริง + custom role ทำงานได้
- **Q2 — academic-years:** ให้ `academic-years` ใช้ `grades.manage` (ตามเมนูนี้) หรือคง `courses.manage` ไว้ (เพราะปีการศึกษาใช้ร่วมกับคอร์ส/ตารางเรียนด้วย)? เสนอ: รับ **ทั้งสองคีย์** (`grades.manage` OR `courses.manage`)
- **Q3 — ใครจัดการได้:** owner/director/admin + custom role ที่ถือ `grades.manage` (รวม "ฝ่ายวิชาการ") ใช่ไหม? และ **teacher** ควร "ดูผลทั้งโรงเรียน" ได้แค่ไหน (เห็นทุกห้อง หรือเฉพาะห้อง/คอร์สที่ตนสอน)?
- **Q4 — read gating ของ grade-scales:** ปิด G3 โดยให้ read ต้องเป็นสมาชิกที่ถือ `grades.view` (ขั้นต่ำ) ใช่ไหม? และย้าย "สร้าง default scale" ออกจาก GET index ไปเป็น action เขียนแยก ใช่ไหม?
- **Q5 — ผู้ปกครอง:** เปิดให้ผู้ปกครองดูผลบุตร (`children.grades.view`) ในรอบนี้เลย หรือเลื่อนไปทำพร้อมเฟสผูกบัญชีผู้ปกครอง (#6 เฟส C / ปัญหา `guardians.user_id` ว่าง)?
- **Q6 — นักเรียน:** ให้ `grades.view.own` เป็นตัวคุมการที่นักเรียนดู transcript ตนเอง (แทน `student->user_id ===` ล้วน) ใช่ไหม?

---

## 7. Implementation Tasks

เจ้าของเคาะแล้ว 2026-10-12: **Q1** = ย้ายไป `userCan('grades.*')` · **Q2** = academic-years รับ `grades.manage` OR `courses.manage` · **Q3** = teacher เห็นผลทั้งโรงเรียน (`grades.view`) · **Q5** = ผู้ปกครองทำรอบนี้เลย · (Q4/Q6 ใช้ default: read ต้องถือ `grades.view`, นักเรียนผ่าน `grades.view.own`)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| GR-S1 | ย้าย authz เป็น `Academy::userCan('grades.*')` ทุก controller (G1/G4) | Q1–Q3 | GradeScale/Transcript/AcademicYear canManage→userCan | 🟡 code done |
| GR-S2 | เติมด่าน `academy.permission` ที่ route group + ปิด read ของ grade-scales (G2/G3) | Q4 | grade-scales/assessment-categories/transcripts group gate `grades.view,grades.manage` + write `grades.manage` | 🟡 code done |
| GR-S3 | ซิงก์เมนู FE ให้ตรง backend (G5) | Q1,Q3 | `admin.vue` `show: can('grades.view')||can('grades.manage')` (ตัด `academy.view`) | 🟡 code done |
| GR-S4 | นักเรียน/ผู้ปกครอง (G6) | Q5,Q6 | `canViewTranscript`: student `grades.view.own` + guardian ผ่าน `GuardianAccessService::isGuardianOf` | 🟡 code done |
| GR-S5 | เทสต์ authz (G7) | GR-S1–S4 | `tests/Feature/GradesAuthzTest.php` 9 เคส (custom role manage/view, cross-tenant, read gate, courses.manage, overview, owner) | 🟡 เขียนแล้ว |

**การเปลี่ยนแปลงจริง (commit บน branch `claude/wonderful-maxwell-hfve3x`):**
- `routes/learn/academy.php` — academic-years write `grades.manage,courses.manage` · grade-scales/assessment-categories/transcripts group `grades.view,grades.manage` (read) + write `grades.manage`
- `GradeScaleController::canManage` · `TranscriptController::canManageTranscripts`/`canViewTranscript`/overview · `AcademicYearController::canManage` → `Academy::userCan`
- `ui/pages/academies/[name]/admin.vue:186` — menu gate
- `tests/Feature/GradesAuthzTest.php` — ใหม่

**Rule:** เทสต์ต้องรันบน MySQL จริง (`phpunit.mysql.xml`) ก่อนขึ้น 🟢

### 🔴 residual debt (ยังไม่ปิดรอบนี้ — นอกขอบ Q ที่เคาะ)
- `GradeScaleController::index` ยัง **สร้าง default scale เป็น side-effect ของ GET** (ตอนนี้ปลอดภัยขึ้นเพราะถูก gate ด้วย `grades.view` + ผูก academy เดียวกันแล้ว ไม่รั่วข้ามโรงเรียน) — ถ้าจะให้สะอาดควรย้ายไป action เขียนแยก
- `{academy}/students` + `{academy}/rollover` ยังไม่มี `academy.permission` ที่ route (rollover พึ่ง `Gate::enrollment.*` ของ #15) — เป็นของ #15/#18 ไม่ใช่ #19 โดยตรง

---

## 8. Review Log
- **2026-10-12** — audit แรก (Claude สแกนโค้ดจริง): ยืนยัน G1–G7 · คีย์ `grades.*` เป็น dead keys · G3 (cross-tenant read + write-on-GET ของ grade-scales) เป็น P0 · เจ้าของเคาะ Q1–Q6
- **2026-10-12 (ต่อ)** — ลงมือ GR-S1–S5: ย้าย authz ทุก controller ไป `userCan` · เติมด่าน route · ซิงก์เมนู FE · เพิ่ม student/guardian path · เขียน `GradesAuthzTest` 9 เคส · **php -l ผ่านทุกไฟล์** · ⏳ รันเทสต์ในคลาวด์ไม่ได้ (PHP 8.3.6 แต่ composer.lock ต้องการ 8.4 · vendor ติดตั้งไม่ได้) → **เหลือเจ้าของรัน `php artisan test -c phpunit.mysql.xml --filter=GradesAuthzTest` + `pint` + `npm run build`**

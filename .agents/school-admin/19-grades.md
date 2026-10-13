# 19 — ผลการเรียน (Grades / Academy Gradebook)

> ไฟล์รองของเมนู `19 ผลการเรียน` (`admin/gradebook/`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> **สถานะ:** 🟢 audit + ปิด gap ครบ 2026-10-13 (GR-S1–S5) — authz ใช้ `userCan('grades.*')` แล้ว · route gate 2 ชั้น · parent ดู transcript ได้ (guardian link) · nav คีย์ถูก · มีเทสต์ · เหลือเจ้าของ verify (MySQL + build)

## 1. Scope & Purpose

จัดการ **ผลการเรียนระดับโรงเรียน** — งานของ **ฝ่ายวิชาการ/นายทะเบียน**

ครอบคลุม (`admin/gradebook/*`):
- **เกณฑ์เกรด (grade scales)** + หมวดการประเมิน (assessment categories)
- **ใบแสดงผลการเรียน (transcripts)** — รายภาคเรียน (semester) + รายปี (annual) · generate/publish/overview
- **เลื่อนชั้นปลายปี (rollover)** — preview/plan/commit/undo (ย้ายนักเรียนขึ้นชั้น + ปิดปีการศึกษา)
- ปีการศึกษา/ภาคเรียน · วิชา (subjects) · ห้องเรียน · รายชื่อนักเรียน · สเกลเกรด
- หน้า transcript รายคน/รายห้อง

**ขอบเขตที่ไม่ใช่เมนูนี้ (แยกโดเมน):**
- **course gradebook** — คีย์ `gradebook.view`/`gradebook.manage` · `Courses/[id]/gradebook/*` (assessments/scores/eligibility/completion/certificates/remediation/appeals) + `GradebookController@…` ใน `routes/learn/course.php` ⇒ โดเมน **คอร์ส** (ครูให้คะแนนในวิชาตน) — คนละเมนู
- `GradeAppealController`, `CourseMemberGradeProgressController` = course domain

## 2. Current State (จากการสแกนโค้ดจริง)

### Backend
- **Controllers (`Api/Learn/Academy/`):** `GradebookController` (487 · มีทั้ง course + academy methods) · `GradeScaleController` (274) · `TranscriptController` (476) · `RolloverController` (185)
- **Routes:** `routes/learn/academy.php` ภายใต้ `Route::middleware(['auth:api'])->prefix('/academies')` (line 168) — **กลุ่มแม่เป็น `auth:api` ล้วน ไม่ครอบ `academy.permission`**
  - `{academy}/grade-scales` (CRUD) · `{academy}/assessment-categories` · `{academy}/transcripts` (semester/annual generate+publish+overview) · `{academy}/students` (list/detail) — **ทุกกลุ่ม ไม่มี `academy.permission` ที่ route** (ต่างจากพี่น้อง `academic-years`/`subjects`/`classrooms` ที่ gate ที่ route) ⇒ authz อยู่ใน controller ล้วน
  - `{academy}/rollover` — `Gate::allows('enrollment.*')` + `scopeBindings` (ทำดีสุด)
- **Models:** `GradeScale`/`GradeScaleItem` · `AnnualTranscript`/`SemesterTranscript` · `CourseGrade` · `GradeEditLog` · `RolloverBatch` · (course: `GradebookAssessment`/`GradebookScore`/`GradeAppeal`/`CourseMemberGradeSnapshot`)
- **Authz ในคอนโทรลเลอร์ (tenant ครบ · แต่ pattern ไม่สม่ำเสมอ):**
  - ✅ tenant scope ทุกที่ (`where('academy_id', ...)`, `findOrFail`)
  - 🔴 `GradeScaleController::canManage` = `academy.user_id===uid` OR `member.role ∈ {owner,director,admin}` → **role-literal ไม่เช็ค `userCan('grades.manage')`**
  - 🔴 `TranscriptController::canViewTranscript` = self OR `role ∈ {owner,director,admin,teacher}` · `canManageTranscripts` = `role ∈ {owner,director,admin}` → **role-literal เช่นกัน** · parent logic เป็นคอมเมนต์ "add if needed" (ยังไม่ทำ)
  - ✅ `ClassroomController::canView` (ใช้โดย `{academy}/students`) = `userCan('students.view','groups.view')` — **ทำถูกแล้ว (reuse #15)** ใช้เป็นต้นแบบของ grades ได้
  - ✅ `RolloverController` = `Gate::allows('enrollment.viewBatches'/'enrollment.undo', ...)` + scopeBindings
  - 🔴 `GradeScaleController::index`/`show` + `getCategories` = **ไม่มี guard เลย** (แค่ tenant + auth:api) ⇒ ใครล็อกอินก็อ่านเกณฑ์เกรด/หมวดของโรงเรียนไหนก็ได้ (sensitivity ต่ำ แต่ไม่สม่ำเสมอ)

### Frontend
- Pages (`admin/gradebook/`): `index` · `grade-scales` · `subjects` · `academic-years` · `classrooms/{index,[id]}` · `students/{index,[studentId]/transcript}` · `transcripts/index` · `rollover/{index,history}`
- (course: `Courses/[id]/gradebook/*` — คนละโดเมน)
- nav `admin.vue:186` → `show: can('grades.view') || can('academy.view')`

### Tests
- ✅ `RolloverControllerWriteTest` · `RolloverControllerReadTest` · `AcademicYearRolloverServiceTest` (rollover ครอบดี)
- ❌ **ไม่มีเทสต์ GradeScale / Transcript / authz ของ grades** (test gap)

## 3. Feature Checklist

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | เกณฑ์เกรด (grade scales) CRUD | ✅ | guard role-literal (G1) |
| 2 | หมวดการประเมิน | ✅ | read ไม่ gate (G3) |
| 3 | Transcript รายภาค/รายปี generate+publish | ✅ | guard role-literal (G1) |
| 4 | Transcript overview/รายคน | ✅ | self/role · parent ❌ (G5) |
| 5 | Rollover (เลื่อนชั้น) preview/plan/commit/undo | ✅ | Gate + scopeBindings + tests (ดีสุด) |
| 6 | รายชื่อนักเรียน (ในเมนู) | ✅ | `canView`=`userCan` (ถูก) |
| 7 | นักเรียนดูผลตัวเอง | ⚠️ | `grades.view.own` มี key · transcript self ได้ |
| 8 | ผู้ปกครองดูผลบุตร | ❌ | `children.grades.view` มี key แต่ canViewTranscript ไม่รับ (G5) |

## 4. Permission Matrix

Keys จริง: **`grades.view` / `grades.manage`** (+ `grades.view.own` / `grades.view.all` / `children.grades.view`) · `gradebook.*` = course domain

| key | Owner | Admin | Director | Teacher | Student | Guardian |
|---|---|---|---|---|---|---|
| `grades.view` | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| `grades.manage` | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `grades.view.own` | — | — | — | — | ✅ | — |
| `children.grades.view` | — | — | — | — | — | ✅ |

🔴 **ปัญหา:** โค้ด authz ส่วนใหญ่ **ไม่ได้อ่าน key เหล่านี้** — เช็ค `member.role` ตรง ๆ แทน ⇒ teacher (ถือ `grades.view`) ดู transcript ได้เพราะ role=teacher (บังเอิญตรง) แต่ถ้าแจก `grades.manage` ให้ custom role (ฝ่ายวิชาการ/นายทะเบียน) จะจัดการไม่ได้ · และ key `grades.view.own`/`children.grades.view` ไม่มีที่ไหนอ่าน

## 5. Gap Analysis (ไม่มี P0 — tenant ครบ + writes role-guarded)

- **G1 (consistency, P2) — authz ใช้ role-literal ไม่ใช่ `userCan('grades.*')`**
  `GradeScaleController::canManage` + `TranscriptController::canManageTranscripts`/`canViewTranscript` เช็ค `role ∈ {owner,director,admin[,teacher]}` ⇒ custom role ที่ถือ `grades.manage`/`grades.view` ทำไม่ได้ · key กลายเป็นสวิตช์หลอก (เหมือน #15 G1 / #17 G7) · **ต้นแบบที่ถูกอยู่ในไฟล์เดียวกันแล้ว: `ClassroomController::canView` = `userCan(...)`**

- **G2 (FE nav, P2) — `admin.vue:186` gate กว้างไป**
  `can('grades.view') || can('academy.view')` · `academy.view` = สมาชิกเกือบทุกคน ⇒ ควรเป็น `can('grades.view') || can('grades.manage')` (แบบเดียวกับ #18 G2)

- **G3 (read ungated, P2/P3) — grade-scales/categories reads เปิดให้ทุก auth**
  `GradeScaleController::index`/`show`/`getCategories` ไม่มี guard (แค่ tenant + auth:api) ⇒ ใครล็อกอินก็อ่านเกณฑ์เกรด/หมวดของโรงเรียนอื่นได้ · sensitivity ต่ำ แต่ควรติด `grades.view` (ที่ route หรือ controller)

- **G4 (defense-in-depth, P3) — route groups ไม่มี `academy.permission`**
  `grade-scales`/`assessment-categories`/`transcripts` groups พึ่ง controller guard ล้วน (ต่างจาก `academic-years`/`subjects`/`classrooms` ที่ gate ที่ route) ⇒ ถ้า controller เผลอเพิ่ม method ไม่ guard = รั่วทันที · ควรเติม `academy.permission:grades.view` ที่ group + `.manage` ต่อ write เพื่อ 2 ชั้น

- **G5 (feature, P2) — ผู้ปกครองดู transcript บุตรไม่ได้**
  `canViewTranscript` ไม่มี guardian logic (คอมเมนต์ "add if needed") · key `children.grades.view` มีแต่ไม่ถูกอ่าน ⇒ ผู้ปกครองดูผลการเรียนลูกไม่ได้

- **test gap (P3)** — มีเทสต์แค่ rollover · GradeScale/Transcript authz ไม่มีเทสต์

## 6. Implementation Tasks (รอเจ้าของเคาะก่อนส่ง)

| Step | Title | Deliverable | Status |
|---|---|---|---|
| GR-S1 | **G1:** `GradeScaleController::canManage` + `TranscriptController::canManageTranscripts/canViewTranscript` รับ `userCan('grades.manage'/'grades.view')` (OR กับ self/role เดิม) | authz | 🟢 **done (owner verify MySQL)** |
| GR-S2 | **G2:** nav `admin.vue:186` → `can('grades.view')‖can('grades.manage')` | 1 บรรทัด FE | 🟢 **done** |
| GR-S3 | **G3+G4:** group grade-scales/categories/transcripts ติด `['academy.visibility:content','academy.permission:grades.view']` + write ต่อ `.manage` (ปิด read ที่เคยเปิด + 2 ชั้น) | routes | 🟢 **done (owner verify MySQL)** |
| GR-S4 | **G5:** `canViewTranscript` รับผู้ปกครองผ่าน `GuardianAccessService::isGuardianOf` | authz (เปิดใช้จริงเมื่อมี guardian link · `guardians.user_id` ยังว่างทั้งระบบ — ดู SC-S7) | 🟢 **done (code)** |
| GR-S5 | เทสต์ authz grade-scale | `GradeScaleAuthzTest` 4 เคส (non-member 403 · grades.view อ่านได้/สร้างไม่ได้ · custom role grades.manage สร้างได้ · owner สร้างได้) | 🟢 **done (owner verify MySQL)** |

**Rule:** verify (เทสต์/จอจริง) ก่อน 🟢 · cloud รันเทสต์ไม่ได้ → เจ้าของ verify MySQL

## 7. คำถามที่ต้องเคาะ (Q)
- **Q1 — G1:** ย้าย authz grades เป็น `userCan('grades.*')` (คง self/role OR) ไหม? (แนะนำ: ควร — สอดคล้อง #15/#17/#18 · ต้นแบบ `ClassroomController::canView` อยู่แล้ว)
- **Q2 — G2:** แก้คีย์ nav เลยไหม (FE 1 บรรทัด · แนะนำ: ควร)
- **Q3 — G3/G4:** เติมด่าน `academy.permission` ที่ route grade-scales/transcripts + guard read ไหม? (แนะนำ: ควร 2 ชั้น)
- **Q4 — G5:** เปิดให้ผู้ปกครองดู transcript บุตร (`children.grades.view`) ไหม — หรือยังไม่เปิดรอบนี้?

## 8. Review Log
- **2026-10-11 (audit)** — ขั้น [1]+[2] เสร็จ · ไม่ใช่ P0 · พบ G1–G5 + test gap · course gradebook คนละโดเมน
- **2026-10-13 (ลงมือ GR-S1–S5 — เจ้าของสั่ง "ดำเนินการตามที่แนะนำและเห็นว่าสมควร")** — Claude เขียนเอง (cloud ไม่มี vendor/agy) ตรวจ `php -l`:
  - **GR-S1/G1:** `GradeScaleController::canManage` + `TranscriptController::canManageTranscripts`/`canViewTranscript` เพิ่ม `$academy->userCan($user,'grades.manage'/'grades.view')` เป็น OR (คง owner/role เดิม) ⇒ custom role (ฝ่ายวิชาการ/นายทะเบียน) ที่ถือ `grades.*` ใช้ได้แล้ว
  - **GR-S2/G2:** `admin.vue:186` → `can('grades.view') || can('grades.manage')`
  - **GR-S3/G3+G4:** group `grade-scales`/`assessment-categories`/`transcripts` ติด `['academy.visibility:content','academy.permission:grades.view']` + write (store/update/destroy/generate/publish) ต่อ `academy.permission:grades.manage` ⇒ ปิด read ที่เคยเปิดโล่ง (G3) + ด่าน 2 ชั้น (G4) · overview = view
  - **GR-S4/G5:** `canViewTranscript` เพิ่ม `GuardianAccessService::isGuardianOf($user,$student)` ⇒ ผู้ปกครองดู transcript บุตรได้ (เปิดใช้จริงเมื่อมี `StudentGuardianLink` + `guardians.user_id` — ปัจจุบันยังว่างทั้งระบบ ดู SC-S7 · path พร้อมแล้ว)
  - **GR-S5:** `GradeScaleAuthzTest` 4 เคส (non-member 403 · grades.view อ่านได้/สร้างไม่ได้ 403 · custom role grades.manage สร้างได้ · owner สร้างได้)
  - ตรวจ `php -l` ผ่านทุกไฟล์ BE · เจ้าของ verify: `php artisan test -c phpunit.mysql.xml --filter=GradeScaleAuthzTest` + `--filter=Rollover` + `pint` + `npm run build`
  - **เมนู #19 ปิด gap ครบ (G1–G5)** 🟢 · (course gradebook `gradebook.*` = คนละโดเมน · test transcript/parent เชิงลึกเลื่อนจน guardian wiring พร้อม)

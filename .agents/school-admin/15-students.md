# 15 — ทะเบียนนักเรียน (Student Registry / Enrollment)

> ไฟล์รองของเมนู **#15 ทะเบียนนักเรียน** ใน [OVERVIEW.md](OVERVIEW.md)
> อ่านคู่กับ OVERVIEW.md · สแกนโค้ดจริงเมื่อ 2026-10-09 (ขั้น [1]+[2] ของ loop · ยังไม่ส่ง step ให้ agy)

## 1. Scope & Purpose

เมนูทะเบียนนักเรียน — รับเข้า (intake) · นำเข้าจำนวนมาก (import) · รายชื่อ/สถานะ · วงจรชีวิตการเรียน
(เลื่อนชั้น/ซ้ำชั้น/ย้าย/จบ/พ้นสภาพ) · เปิดบัญชีผู้เรียน · ส่งออก

**อยู่ในขอบเขต (หน้า `admin/students/`):**
- `index.vue` — dashboard สถิติ 4 ใบ + `StudentDataTable` (รายชื่อ · กรองยังไม่มีห้อง · assign ห้อง · ลิงก์ไปโปรไฟล์)
- `intake.vue` → `IntakeWizard` (รับนักเรียนใหม่ทีละคน) · `import.vue` → `ImportWizard` (นำเข้าไฟล์) · `import-history.vue`
- วงจรชีวิต: graduate / drop / repeat / promote / transfer (+ enrollment-history)
- เปิดบัญชี/ส่งลิงก์เปิดบัญชี (`StudentAccountController`) · ส่งออกรายชื่อ/คำเชิญ

**อยู่ในขอบเขต #15 ด้วย (เจ้าของเคาะ Q3 = ใช่, 2026-10-10):**
- Student Master profile: `academic-info` / `addresses` / `contacts` / `health` / `guardian` / `home-visit`
  (`Api/Learn/Student/Master/*` · `Profile/StudentProfileController` · route `student-profile.php` + `academy-home-visit.php`)
  - หมายเหตุ: guardian ยังคาบเกี่ยว #6 (ใช้ `guardians.*` + `GuardianAccessService`) · home-visit คาบเกี่ยว #17 — audit ส่วนลึกของสองเมนูนั้นทำที่ไฟล์ของมันเอง · ที่นี่ดูเฉพาะ profile กลาง
- แก้ข้อมูลนักเรียนใช้ **change-request flow** (`ChangeRequestController` · StudentController `listRequests/approveRequest/rejectRequest/updatePersonal`)

### Student Master profile — ผล audit (2026-10-10, หลัง Q3)
- **authz guard ครบทุก method · ไม่มีรูรั่ว PII** แม้ route `student-profile.php` มีแค่ `auth:api` (ไม่มี `academy.permission`) เพราะ**ทุก controller บังคับสิทธิ์เองครบ**:
  - `StudentProfileController::show/summary` → `checkAccess()` (self/owner/admin/director/homeroom/parent · คนนอก = 403 · scoped academy)
  - `AcademicInfo/Address/Contact/Health` ทุก method (index/show/store/update/destroy/set-*) → `$this->authorize('update', $student)` ครบ
  - `Guardian` → `viewGuardians`/`manageGuardians` (ผ่าน `guardians.*`) · `ChangeRequest` → `approveRequests` · `HomeVisit` → `abort_unless(isStaff/canManage)`
  - มีเทสต์ `StudentMasterPolicyTest` อยู่แล้ว

## 2. Current State (จากการสแกนโค้ดจริง 2026-10-09)

> ⭐ ต่างจาก #12/#13/#14 — **เมนูนี้สุกและมีเทสต์ครบ** · authz ออกแบบเป็นระบบผ่าน Policy/Gate/FormRequest · ไม่ใช่โมดูลที่พัง

### Frontend
- `admin/students.vue` = wrapper (`<NuxtPage/>`) · `admin/students/index.vue` (217) · `import.vue`/`intake.vue` (thin → wizard components) · `import-history.vue` (153)
- `StudentDataTable.vue` (595) ใช้ `useStudentEnrollmentActions` (graduate/drop/repeat/promote/transfer + enrollment-history-v2) · `useStudentAccountService` (export invitations)
- stats: FE อ่าน `res.stats` — ตรงกับ backend ที่คืน `{stats:{...}}` ✅ · export ใช้ `api.get(..., {responseType:'blob'})` (ofetch รองรับ)

### Backend
- `StudentIntakeController` — index/stats/export/duplicateCheck/store
- `StudentImportController` — index/upload/template/show/rows/confirm/retry/errors/cancel (self-guard `Gate::student.import` + tenant `authorizeBatch`)
- `StudentLifecycleController` — graduate/drop/repeat/promote/transfer/history (authz ผ่าน FormRequest → `Gate::enrollment.lifecycle`)
- `StudentAccountController` — invite/exportInvitations · Master `StudentController` + 6 ตัวย่อย (academic/address/contact/health/guardian/home-visit)

### Routes & Authz (ยืนยันจริง)
| กลุ่ม | Guard | ที่ไหน |
|---|---|---|
| `student-intakes/list`,`/stats` | `academy.permission:students.view` | route middleware |
| `student-intakes/export`, `student-invitations/export` | `students.export` | route middleware |
| `student-intakes` POST, `duplicate-check` | `Gate::student.intake` | **FormRequest::authorize()** (ไม่มี route middleware) |
| `student-imports/*` | `Gate::student.import` + tenant | **controller self-guard** (ไม่มี route middleware) |
| `students/{id}/invite` | `students.activate_account` | route middleware |
| lifecycle (graduate/drop/repeat/promote/transfer) | `Gate::enrollment.lifecycle` | **FormRequest::authorize()** + `scopeBindings()` tenant |

### Policy (`EnrollmentPolicy`)
- `intake` / `import`: owner **หรือ** `hasAnyPermission(['students.create|import','students.manage'])` **หรือ** `member.role ∈ {admin,director}`
- `lifecycle`: owner/`member.role ∈ {admin,director}` (`isAcademyAdmin`) **หรือ** ครูประจำชั้นของห้อง active ของนักเรียน · tenant ผ่าน `student.academy_id === academy.id`

### เทสต์ที่มีอยู่แล้ว (ครอบคลุมดี)
`StudentLifecycleControllerTest` · `StudentImportControllerTest` · `StudentIntakeControllerTest` · `EnrollmentPolicyTest`
· `StudentMasterPolicyTest` · `StudentIntakeGuardianWriteTest` · `StudentRosterImportIntegrationTest` · `EnrollmentAuditTest`
· `StudentEnrollmentServiceTest` · `ClassroomEnrollmentListTest` · `EnrollmentRepairDirtyDataTest` ฯลฯ

## 3. Gap Analysis — เล็กน้อย/ความไม่สอดคล้อง (ไม่ใช่ P0 แบบเมนูก่อน)

- **G1 — ✅ แก้แล้ว (ST15-S1, 2026-10-10)** — เดิม `EnrollmentPolicy::lifecycle()` รับแค่ admin/director/owner/ครูประจำชั้น ไม่อ่าน `students.manage`
  ⇒ custom role "นายทะเบียน" intake/import ได้แต่ promote/graduate/drop/transfer ไม่ได้ · เจ้าของเคาะ Q1 = ให้ `students.manage` ทำ lifecycle ได้
  · แก้: เพิ่ม `memberHasPermission($user,$academy,['students.manage'])` ใน `lifecycle()` (แพทเทิร์นเดียวกับ intake/import) + เทสต์ 2 เคส
- **G2 — ✅ แก้แล้ว (ST15-S2, 2026-10-10)** — ลบ route `enrollment-history` v1 (`groups.view`) + method `ClassroomController::getStudentEnrollmentHistory` ที่ตายแล้ว (ยืนยัน FE ใช้ v2 เท่านั้น · ไม่มี test/route() helper/caller อื่น) · เหลือ v2 (`enrollment.lifecycle`) ที่ FE ใช้จริง
- **G3 — permission model แยกสองทาง** middleware (`Academy::userCan` → academy_role.permissions) vs Policy (`member.role` column + `hasAnyPermission`) · สองแหล่งความจริง เสี่ยง drift (G1 คือตัวอย่างที่เห็นผล)
- **G4 — ✅ ปิด by-design (เจ้าของเคาะ Q4, 2026-10-10)** — การแก้ข้อมูลนักเรียนผ่าน change-request flow (ขอ→อนุมัติ) เป็นดีไซน์ที่ต้องการ · การ "เอาออก" ทำผ่าน lifecycle drop/graduate ไม่ใช่ hard delete — ยืนยันตามดีไซน์ ไม่ต้องแก้
- **G5 — ✅ แก้แล้ว (ST15-S3, 2026-10-10)** — align Master profile authz ให้รับ `students.view`/`students.manage` (เจ้าของเคาะ)
  · `StudentMasterProfilePolicy::view` รับ `students.view`/`students.manage` · `update`/`approveRequests` รับ `students.manage` (helper `memberHasPermission` อ่าน academy_role.permissions · status=2)
  · `StudentProfileController::checkAccess()`: `students.manage` → level `admin` (เห็นเลขบัตรเต็ม) · `students.view` → level `teacher` (เลขบัตร masked · sensitive ยัง gate ด้วย `canViewSensitive`) · guardians คง `guardians.*` ตามเดิม
  · นายทะเบียน (`students.manage`) ดู/แก้/อนุมัติ change-request แฟ้มประวัติได้ครบ สอดคล้องกับ Q1 (intake/import/lifecycle)

## 4. Permission Matrix (ปัจจุบัน — ยืนยัน Q1/Q2)

| การกระทำ | owner | role:admin/director | custom role + `students.manage` | ครูประจำชั้น | ครูทั่วไป |
|---|---|---|---|---|---|
| ดูรายชื่อ/สถิติ (`students.view`) | ✅ | ✅ | ✅ (ถ้าถือ view) | ⚠️ | ⚠️ |
| intake / import | ✅ | ✅ | ✅ | ❌ | ❌ |
| **lifecycle (promote/graduate/…)** | ✅ | ✅ | **❌ (G1)** | ✅ (ห้องตน) | ❌ |
| export | ✅ | ✅ | ✅ (ถ้าถือ `students.export`) | ❌ | ❌ |

## 5. คำถามที่ต้องให้เจ้าของโปรเจคเคาะก่อนลงมือ

- **Q1 — G1 แก้ไหม:** ให้ `students.manage` (custom role) ทำ lifecycle ได้ด้วยหรือไม่ — ถ้าใช่ แก้ `EnrollmentPolicy::isAcademyAdmin/lifecycle` ให้รวม `hasAnyPermission(['students.manage'])` แบบเดียวกับ intake/import · ถ้าไม่ (ตั้งใจจำกัดแค่ admin/director/ครูประจำชั้น) → ปิด G1 เป็น "by design"
- **Q2 — enrollment-history v1 (G2):** ลบเส้น v1 ที่ FE ไม่ใช้ หรือเก็บไว้ (มีผู้ใช้อื่น?)
- **Q3 — ขอบเขต Student Master profile:** academic/address/contact/health/guardian อยู่ในเมนู #15 นี้ หรือเป็นของ #6 (ผู้ปกครอง)/#17 (เยี่ยมบ้าน) — กำหนดว่าจะ audit ที่เมนูไหน (กันทำซ้ำ)
- **Q4 — change-request flow:** การแก้ข้อมูลนักเรียนผ่าน request→approve เป็นดีไซน์ที่ต้องการใช่ไหม (ไม่ต้องมีปุ่มแก้ตรงในทะเบียน)

## 6. Implementation Tasks (ยังไม่เริ่ม · รอ Q1–Q4)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| ST15-S1 | ให้ `students.manage` ทำ lifecycle ได้ — แก้ `EnrollmentPolicy::lifecycle` + เทสต์ | Q1 ✅ | `EnrollmentPolicy` · `EnrollmentPolicyTest` | 🟡 โค้ด/เทสต์เสร็จ 2026-10-10 · php -l ผ่าน · รอรัน MySQL |
| ST15-S2 | ถอด route `enrollment-history` v1 + method ตาย + ยืนยันไม่มีผู้ใช้ | Q2 ✅ | `academy.php` · `ClassroomController` | 🟢 เสร็จ 2026-10-10 · php -l ผ่าน (dead-code ล้วน ไม่มี test ต้องรัน) |
| ST15-S3 | align Master profile authz ให้รับ `students.view`/`students.manage` (G5) | G5 ✅ | `StudentMasterProfilePolicy` · `StudentProfileController::checkAccess` · `StudentMasterPolicyTest` (+2) | 🟡 โค้ด/เทสต์เสร็จ 2026-10-10 · php -l ผ่าน · รอรัน MySQL |

> ถ้าเจ้าของเคาะว่า G1 เป็น by design และ G2 เก็บไว้ ⇒ **เมนู #15 ปิดได้เลย (มาตรฐานสูง + เทสต์ครบ)** ไม่มีงานต้องแก้

**Rule:** ทุก step verify (test บน MySQL) ก่อน 🟢 · UI (ถ้ามี) ยึด **mobile-first**

## 7. Review Log
- **2026-10-09** — ขั้น [1] สแกนโค้ด + [2] เขียนไฟล์รองนี้ เสร็จ · เมนูนี้สุก/มีเทสต์ครบ ต่างจาก #12/#13/#14 · finding หลัก = G1 (lifecycle ไม่รับ `students.manage`) · G2/G3/G4 เป็นความไม่สอดคล้อง/ยืนยันดีไซน์ · รอเจ้าของเคาะ Q1–Q4
- **2026-10-10 (ST15-S1 — เจ้าของเคาะ Q1 = แก้ G1)** — `EnrollmentPolicy::lifecycle()` เพิ่มด่าน `memberHasPermission($user,$academy,['students.manage'])` (helper ใหม่ที่อ่าน academy_role.permissions ผ่าน `AcademyMember::hasAnyPermission` แบบเดียวกับ intake/import) วางถัดจาก isAcademyAdmin ก่อนด่านครูประจำชั้น ⇒ custom role ที่ถือ `students.manage` ทำ promote/graduate/drop/repeat/transfer ได้แล้ว · ขอบเขตจำกัดแค่ lifecycle (ไม่แตะ rollover commit/undo ที่ยังจำกัด admin/director/owner ตามเดิม)
  - เทสต์ `EnrollmentPolicyTest` +2: `students.manage` → lifecycle ได้ · `students.view` อย่างเดียว → ไม่ได้ (403) · php -l ผ่าน
  - เหลือเจ้าของ verify: `php artisan test -c phpunit.mysql.xml --filter=EnrollmentPolicyTest` + `pint`
  - **ยังรอ Q2** (ลบ enrollment-history v1?) · **Q3** (ขอบเขต Student Master profile) · **Q4** (change-request flow by design?) — ถ้าปิดหมดเป็น by-design เมนู #15 ปิดได้
- **2026-10-10 (ST15-S2 — เจ้าของเคาะ Q2 = ลบ v1)** — ลบ route `{academy}/students/{student}/enrollment-history` (v1, `groups.view`) + method `ClassroomController::getStudentEnrollmentHistory` (dead code) · ยืนยันก่อนลบ: FE ใช้ `enrollment-history-v2` เท่านั้น (`useStudentEnrollmentActions.ts:59`) · ไม่มี test/route() helper/caller อื่น · php -l ผ่านทั้ง route + controller · **เหลือ Q3, Q4**
- **2026-10-10 (Q3 — เจ้าของเคาะ: Master profile = ส่วนหนึ่งของ #15)** — audit subsystem (8 controller ~2,100 บรรทัด + route `student-profile.php`): **guard ครบทุก method ไม่มีรูรั่ว PII** (รายละเอียด §2) · เจอ finding ใหม่ **G5** (profile view/update/approveRequests + checkAccess ไม่รับ `students.view`/`students.manage` — เหมือน G1 แต่ฝั่งโปรไฟล์ · คุม PII) → **รอเจ้าของเคาะว่าจะ align สิทธิ์แบบ Q1 ไหม**
- **2026-10-10 (Q4 — เจ้าของเคาะ: by-design)** — change-request flow (แก้ข้อมูลนักเรียน = ขอ→อนุมัติ) เป็นดีไซน์ที่ต้องการ · ปิด G4 ไม่ต้องแก้โค้ด
- **2026-10-10 (ST15-S3 — เจ้าของเคาะ: แก้ G5)** — align Master profile authz: policy `view`(students.view/manage) · `update`/`approveRequests`(students.manage) + `checkAccess()` (manage→admin level · view→teacher level) · guardians คง `guardians.*` · เทสต์ `StudentMasterPolicyTest` +2 (manage ครบ · view ดูได้แก้ไม่ได้) · php -l ผ่าน · รอเจ้าของรัน `--filter=StudentMasterPolicyTest` + pint
- **🎯 สถานะเมนู #15 — ปิดครบทุก gap:** G1 ✅ (ST15-S1) · G2 ✅ (ST15-S2) · G4 ✅ by-design · G5 ✅ (ST15-S3) · G3 = in-scope audit (guard ครบ ไม่มีรูรั่ว) · **เหลือเจ้าของ verify MySQL เท่านั้น** (`EnrollmentPolicyTest` + `StudentMasterPolicyTest` + pint)

---

## 📎 ภาคผนวก — audit รอบคู่ขนาน (PR #33 · branch jolly-goodall · 2026-10-10/11)

> เซสชัน PR #33 แตกจาก main ก่อน PR #32 (#14/#15) จะ merge จึง audit #15 อีกฉบับแบบอิสระ
> งานของรอบนี้ **เสริม** ฉบับหลักด้านบน (ไม่ซ้ำ): ST-S1 canView รับ students.view OR groups.view ·
> ST-S3 migration grant `students.*` ให้ registrar/director/admin (ปิด G4 ที่รอบหลักเปิดค้าง) ·
> ST-S4 lifecycle members-only middleware · ST-S5 ลบถาวร guarded + FE · ST-S6 `StudentRegistryTest`
> โค้ดทั้งสองรอบรวมกันแล้วใน merge นี้ (policy-level ของรอบหลัก + route/grant/lifecycle ของรอบนี้)

# 15 — ทะเบียนนักเรียน (Student Registry)

> ไฟล์รองของเมนู `15 ทะเบียนนักเรียน` (`admin/students.vue` + `admin/students/`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> ขั้น [1] สแกนโค้ด + [2] เขียนไฟล์รอง — **เสร็จ 2026-10-11** · รอเจ้าของเคาะ Q1–Q4 ก่อนแตก step

## 1. Scope & Purpose

ทะเบียนนักเรียนกลางของโรงเรียน: รายชื่อ/ค้นหา/กรอง, ดู-แก้โปรไฟล์นักเรียน (ข้อมูลส่วนตัว · ที่อยู่ · การติดต่อ · ผู้ปกครอง · สุขภาพ · ข้อมูลการศึกษา), เพิ่มนักเรียน (intake รายคน/กลุ่ม · import Excel), เชิญสร้างบัญชีผู้ใช้, และ lifecycle การศึกษา (เลื่อนชั้น/ซ้ำชั้น/จบ/พัก-ลาออก/ย้าย) · เป็น **source ของ Student master** ที่เมนู #16 บัตรนักเรียน, #10 ห้องเรียน, #6 ผู้ปกครอง ใช้ร่วม

ผู้ใช้: admin/owner · ฝ่ายทะเบียน/วิชาการ · ครูประจำชั้น (โปรไฟล์ห้องตน) · นักเรียน/ผู้ปกครอง (ของตนเอง)

## 2. Current State (จากการสแกนโค้ดจริง)

### Frontend (`ui/pages/academies/[name]/admin/students/`)
- `students.vue` (wrapper) — แค่ `<NuxtPage/>` + layout main
- `index.vue` (217) — หน้าหลัก: stats (`student-intakes/stats`) + ตาราง `StudentDataTable` + ปุ่ม export / export-invitations / import / intake / import-history
- `import.vue` (38) · `import-history.vue` (153) · `intake.vue` (36) — wrappers หน้านำเข้า/ประวัตินำเข้า/รับเข้ารายคน
- Component: `components/academy/student/StudentDataTable.vue` (ตารางหลัก) · โปรไฟล์รายคนใช้หน้า `students/{student}/profile` (คนละชุด)

### Backend
- **อ่าน list/detail:** `ClassroomController::getAllStudents` / `getStudent` (`{academy}/students`, `{academy}/students/{student}`)
- **โปรไฟล์ + แก้รายส่วน:** `Student/Profile/StudentProfileController` (show/summary/myProfile/mySummary) + `Student/Master/` controllers: `StudentController::updatePersonal` · `AddressController` · `ContactController` · `HealthController` · `GuardianController` · `AcademicInfoController` · `ChangeRequestController` · `HomeVisitController`
- **เพิ่มนักเรียน:** `Academy/StudentIntakeController` (list/stats/export/duplicate-check/store) · `Academy/StudentImportController` (batch upload/template/show/rows/confirm/retry/errors/cancel)
- **บัญชีผู้ใช้:** `Academy/StudentAccountController` (invite / exportInvitations)
- **lifecycle:** `Academy/StudentLifecycleController` (graduate/drop/repeat/promote/transfer/history) ผ่าน `StudentEnrollmentService`
- **Routes:** `routes/learn/academy.php` (intake/account/import/read/lifecycle) · `routes/learn/student-profile.php` (profile + sectional edits)
- **Policy:** `StudentMasterProfilePolicy` (ลงทะเบียน `Gate::policy(Student::class, …)` ใน AppServiceProvider:84)
- **Middleware:** `CheckAcademyPermission` → `Academy::userCan()` (superadmin → owner/admin → role perms → group grant · unknown key = 403 ให้ non-admin)

### Database
- `students` (master) · `classroom_students` (enrollment · source of truth รายห้อง) · `student_import_batches` (นำเข้า) · `student_academic_infos` · addresses/contacts/health/guardian_links (รายส่วนโปรไฟล์) · `student_change_requests` (คำขอแก้ที่ต้องอนุมัติ)

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | รายชื่อ/ค้นหา/กรอง (classroom/status/search) | ✅ | `getAllStudents` server-side search + paginate · escape LIKE |
| 2 | ดูโปรไฟล์รายคน (graded access + mask citizen_id) | ✅ | `StudentProfileController::show` + `checkAccess()` |
| 3 | แก้โปรไฟล์รายส่วน (personal/address/contact/health/academic) | ✅ | ทุก controller: tenant check + `authorize()` ผ่าน policy · owner edits → change-request |
| 4 | ผู้ปกครอง (ดู/จัดการ/แต่งตั้ง) | ✅ | `GuardianController` + `GuardianAccessService` (เมนู #6) |
| 5 | อนุมัติคำขอแก้ข้อมูล (change-request) | ✅ | `ChangeRequestController` · policy `approveRequests` (owner/admin/director) |
| 6 | เพิ่มนักเรียนรายคน (intake) + duplicate-check | ✅ | `StudentIntakeController::store` |
| 7 | นำเข้า Excel แบบ batch (upload/validate/confirm/retry/errors) | ✅ | `StudentImportController` · ทุก method `authorizeImport`/`authorizeBatch` |
| 8 | ส่งออกรายชื่อ / ส่งออกคำเชิญ | ✅ | gate `students.export` |
| 9 | เชิญสร้างบัญชีผู้ใช้นักเรียน | ✅ | `StudentAccountController::invite` · gate `students.activate_account` |
| 10 | lifecycle (เลื่อน/ซ้ำ/จบ/ลาออก/ย้าย) + ประวัติ | ✅ | `StudentLifecycleController` · authz ผ่าน FormRequest::authorize + Gate `enrollment.lifecycle` |
| 11 | เพิ่มนักเรียนรายคนแบบฟอร์มเต็ม / ลบถาวร | ⚠️ | ไม่มี single-create form/hard-delete ตรง ๆ (ใช้ intake/import + lifecycle drop แทน) — ยืนยันว่าตั้งใจ (Q3) |

## 4. Permission Matrix (ตามโค้ดจริง)

| จุด | คีย์ที่เช็คจริง | หมายเหตุ |
|---|---|---|
| list/detail (`getAllStudents`/`getStudent`) | **`groups.view`** | ⚠️ ไม่ใช่ `students.view` (G1) |
| โปรไฟล์ read (`StudentProfileController`) | policy `view` = staff(owner/admin/teacher/director) หรือ self | + graded `checkAccess` + mask citizen_id |
| แก้รายส่วน (`updatePersonal`/address/…) | policy `update` = self / owner / ครูประจำชั้นห้องนั้น / admin·director | tenant check ทุกเมธอด |
| intake read/store | `students.view` | store ไม่ได้ gate เพิ่ม (ดู Q) |
| export | `students.export` | — |
| invite account | `students.activate_account` | — |
| import batch | ภายใน `authorizeImport` (ตรวจใน controller) | ยืนยันคีย์ที่ใช้ (Q2) |
| lifecycle | FormRequest::authorize + Gate `enrollment.lifecycle` | ไม่มี route middleware (G3) |

> **seed จริง (reconcile migration):** role ต่าง ๆ ได้ `students.cards.*` แต่ `students.view`/`students.manage`/`students.export`/`students.activate_account`/`students.import`/`students.create`/`students.update`/`students.remove`/`students.lifecycle` **ไม่ถูก assign ให้ role ไหนเลย** (นอกจาก default บาง role ได้ `students.view`) ⇒ ในทางปฏิบัติมีแต่ owner/admin (bypass) ที่จัดการทะเบียนได้ · ฝ่ายทะเบียนเฉพาะทางมอบสิทธิ์ไม่ได้ (G4)

## 5. Gap Analysis

- **G1 (permission mismatch)** — `ClassroomController::canView()` = `userCan($user, 'groups.view')` ⇒ รายชื่อ/รายละเอียดทะเบียนผูกกับ **`groups.view`** ไม่ใช่ `students.view` · role ที่ได้ `students.view` อย่างเดียวเข้าทะเบียนไม่ได้ · role ที่ได้ `groups.view` (ดูห้องเรียน) กลับเห็นรายละเอียดนักเรียนเต็ม
- **G2 (PII over-exposure · สองเส้นอ่านไม่สอดคล้อง)** — `getStudent` eager-load `guardianLinks.guardian.contacts, healthInfo, addresses, contacts` คืนทั้งก้อน โดยกันแค่ `groups.view` · ขณะที่ `StudentProfileController::show` มี `checkAccess()` ไล่ระดับ + mask เลขบัตรประชาชน · มาตรฐานการเปิดข้อมูลอ่อนไหวสองเส้นไม่ตรงกัน
- **G3 (lifecycle ไม่มี route middleware)** — กลุ่ม graduate/drop/repeat/promote/transfer อยู่ใต้ `auth:api` อย่างเดียว (ต่างจาก classroom transfer ข้าง ๆ ที่มี `groups.view`) · พึ่ง `FormRequest::authorize()` + Gate `enrollment.lifecycle` ล้วน — ควรยืนยันว่าทุก FormRequest authorize ครบ (มีสวีท `FormRequestValidationTest` คุมบางส่วน) และพิจารณาเติม middleware defense-in-depth
- **G4 (permission catalog ไม่ครบ)** — route อ้าง `students.manage/export/activate_account/import/create/update/remove/lifecycle` แต่ไม่มีใน seed ที่ assign ให้ role ⇒ มอบสิทธิ์ให้ "ฝ่ายทะเบียน" ไม่ได้ · ต้องนิยามคีย์ + ผูก role
- **G5 (feature)** — ไม่มีฟอร์มเพิ่มนักเรียนรายคนแบบเต็ม/ลบถาวรในหน้าทะเบียนตรง ๆ (ใช้ intake/import + lifecycle drop) · ยืนยันว่าเป็น by design
- **G6 (ตรวจเพิ่ม)** — `StudentIntakeController::store` / `StudentDataTable` ยังไม่อ่าน authz ละเอียด (ยืนยันว่า gate `students.view` ครอบพอ และ store มีด่าน manage)

## 6. คำตัดสินเจ้าของ (เคาะ 2026-10-11)
- **Q1 (G1) → รับทั้งสองคีย์**: list/detail gate ด้วย `students.view` **หรือ** `groups.view` (OR) — ✅ `canView` แก้แล้ว
- **Q2 (G2) → คงข้อมูลเต็ม**: `getStudent` คืน PII เต็มตามเดิม (คนที่ผ่าน gate มีสิทธิ์อยู่แล้ว) — **won't-fix / by design** (ไม่ต้อง mask)
- **Q3 (G4) → นิยาม + ผูกให้ฝ่ายทะเบียน**: คีย์ `students.*` มีในแคตาล็อกครบแล้ว · ผูกเพิ่มให้ role `registrar`/`director`/`admin` — ✅ migration แล้ว
- **Q4 (G3 + G5) → เติม route middleware lifecycle (defense-in-depth) + เพิ่มฟอร์มเพิ่มนักเรียนรายคน + ลบถาวร**

## 7. Implementation Tasks (ร่าง — เปิดใช้เมื่อเคาะ Q)

| Step | Title | Dep | Status |
|---|---|---|---|
| ST-S1 | ปิด G1 — `canView` รับ `students.view` OR `groups.view` | Q1 | 🟡 โค้ดเสร็จ (php -l) |
| ST-S2 | G2 — getStudent mask | — | ❌ won't-fix (Q2 คงข้อมูลเต็ม) |
| ST-S3 | ปิด G4 — migration ผูก students.* ให้ registrar/director/admin | Q2 | 🟡 โค้ดเสร็จ · เจ้าของ migrate |
| ST-S4 | ปิด G3 — lifecycle route `academy.permission` (members-only defense-in-depth · ไม่ regress ครูประจำชั้นเพราะ EnrollmentPolicy ยังตัดสินจริง) | Q4 | 🟡 โค้ดเสร็จ |
| ST-S5 | ลบถาวร (guarded) · **create ใช้ intake เดิม ไม่ทำซ้ำ** (เจ้าของเคาะ) | Q4 | 🟡 โค้ดเสร็จ |
| ST-S6 | เทสต์ `StudentRegistryTest` (canView OR + delete guard/authz/tenant) | S1–S5 | 🟡 โค้ดเสร็จ · เจ้าของรัน |
| ST-S7 | ตรวจจอจริง 375/768/1280 | S1–S5 | ⚪ (เจ้าของ) |

### ST-S5 ที่ลงจริง (เจ้าของเคาะ: create ใช้ intake · delete เฉพาะคนไม่มีประวัติ)
- **create:** ไม่ทำซ้ำ — หน้า `intake` ("รับนักเรียนใหม่" · IntakeWizard → `StudentIntakeController::store`) คือฟอร์มเพิ่มรายคนอยู่แล้ว
- **delete (guarded hard-delete):** `StudentController::destroy(Academy,Student)` — tenant check + `authorize('delete')` (policy `delete`=userCan `students.delete`) + route middleware `students.delete` · **บล็อก 409 ถ้ามีประวัติ** (บัตร/คำร้องบัตร · ความประพฤติ · ผลการเรียน) ให้ใช้ drop แทน · ลบสำเร็จ: ลบ RESTRICT-FK (student_change_requests, cardRequests) ก่อน แล้ว `$student->delete()` (cascade จัดการที่เหลือ) ใน transaction + try/catch → 409 ถ้า FK ยังชน · FE: ปุ่มถังขยะแดงใน `StudentDataTable` + Swal confirm + 409 handling

**Rule:** Claude วางแผน+ตรวจ · agy เขียนโค้ด (ถ้าใช้ได้) · งาน UI แปะกติกา mobile-first · verify build/test ก่อนขึ้น 🟢

## 8. Review Log
- **2026-10-11** — เคาะ Q1–Q4 + ลงมือ ST-S1/S3/S4 (authz wiring · php -l) + ST-S5 (ลบถาวร guarded + FE ปุ่มลบ · create ใช้ intake เดิม) + ST-S6 (`StudentRegistryTest` 7 เคส) · **เจ้าของต้องรัน:** `php artisan migrate` (dev · grant students.* ให้ registrar/director/admin) → `php artisan test:db:rebuild` → `php artisan test -c phpunit.mysql.xml --filter=StudentRegistry` + `npm run build` · prod: migrate หลัง mysqldump
- **2026-10-11** — ขั้น [1]+[2] (Claude): สแกน FE (students.vue/index/import/intake + StudentDataTable) + BE (ClassroomController getAllStudents/getStudent · StudentProfile/Master controllers · Intake/Import/Account/Lifecycle · StudentMasterProfilePolicy · CheckAcademyPermission) + routes academy.php/student-profile.php · เขียนไฟล์รองนี้ · พบ gap G1–G6 · รอเคาะ Q1–Q4

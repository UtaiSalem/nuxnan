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
- **G5 (ใหม่ จาก audit Q3 — ต้องเคาะ) — Master profile authz ไม่รับ `students.view`/`students.manage`** (G1 เวอร์ชันฝั่งโปรไฟล์)
  `StudentMasterProfilePolicy::view/update/approveRequests` และ `StudentProfileController::checkAccess()` ยึด `member.role ∈ {admin,teacher,director}` + owner + ครูประจำชั้น + ผู้ปกครอง — **ไม่อ่าน academy_role.permissions**
  ⇒ custom role "นายทะเบียน" ที่ถือ `students.manage` (ซึ่งเพิ่งให้ทำ intake/import/lifecycle ได้ใน Q1) **ดู/แก้แฟ้มประวัตินักเรียนไม่ได้** · `students.view` holder ก็ดูไม่ได้ · inconsistent กับทิศทางที่เจ้าของเลือกใน Q1
  - ⚠️ ข้อนี้คุม **PII** (สุขภาพ · เลขบัตรปชช · ที่อยู่ · ผู้ติดต่อ) การเปิดกว้าง = ตัดสินใจอ่อนไหว → **ต้องเคาะก่อนแก้**

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
- **สถานะเมนู #15:** G1 ✅ (ST15-S1) · G2 ✅ (ST15-S2) · G4 ✅ by-design · G3 info · **เหลือ G5 ข้อเดียว** (align Master profile authz — รอเคาะ) ⇒ เคาะ G5 แล้วปิดเมนูได้

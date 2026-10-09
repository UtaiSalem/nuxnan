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

**อยู่ติดกัน — ต้องเคาะ Q3 ว่านับเป็นเมนูนี้ไหม:**
- Student Master profile: `academic-info` / `addresses` / `contacts` / `health` / `guardian` / `home-visit`
  (`Api/Learn/Student/Master/*` · route `academy-home-visit.php` + `student-profile.php`) — คาบเกี่ยว **#6 ผู้ปกครอง** และ **#17 เยี่ยมบ้าน**
- แก้ข้อมูลนักเรียนใช้ **change-request flow** (`ChangeRequestController` · StudentController `listRequests/approveRequest/rejectRequest/updatePersonal`)

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

- **G1 (finding หลัก — ต้องเคาะ) — authority model ของ lifecycle ไม่รับ `students.manage`**
  `EnrollmentPolicy::isAcademyAdmin()` เช็คแค่ `member.role ∈ {admin,director}` (+owner) · **ไม่อ่าน academy_role.permissions เลย**
  ⇒ custom role เช่น "นายทะเบียน" ที่ถือ `students.manage` **intake/import ได้** (เพราะ `hasAnyPermission`) แต่ **promote/graduate/drop/transfer ไม่ได้**
  (lifecycle รับแค่ admin/director/owner/ครูประจำชั้น) · inconsistent กับ intake/import
- **G2 — enrollment-history ซ้ำ 2 เส้น** `enrollment-history` (v1, `groups.view`, ClassroomController) vs `enrollment-history-v2` (`enrollment.lifecycle`, Lifecycle) · FE ใช้ v2 · v1 อาจ dead/stale
- **G3 — permission model แยกสองทาง** middleware (`Academy::userCan` → academy_role.permissions) vs Policy (`member.role` column + `hasAnyPermission`) · สองแหล่งความจริง เสี่ยง drift (G1 คือตัวอย่างที่เห็นผล)
- **G4 (verify ตา) — registry ไม่มี edit/delete นักเรียนตรง ๆ** การแก้ข้อมูลผ่าน change-request flow (ตั้งใจ?) · การ "เอาออก" ทำผ่าน lifecycle drop/graduate ไม่ใช่ hard delete — ยืนยันว่าตรงตามดีไซน์

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
| ST15-S1 | (ถ้า Q1=ใช่) ให้ `students.manage` ทำ lifecycle ได้ — แก้ `EnrollmentPolicy` + เทสต์ | Q1 | `EnrollmentPolicy` · `EnrollmentPolicyTest` | ⚪ รอเคาะ |
| ST15-S2 | (ถ้า Q2=ลบ) ถอด route `enrollment-history` v1 + ยืนยันไม่มีผู้ใช้ | Q2 | `academy.php` | ⚪ รอเคาะ |

> ถ้าเจ้าของเคาะว่า G1 เป็น by design และ G2 เก็บไว้ ⇒ **เมนู #15 ปิดได้เลย (มาตรฐานสูง + เทสต์ครบ)** ไม่มีงานต้องแก้

**Rule:** ทุก step verify (test บน MySQL) ก่อน 🟢 · UI (ถ้ามี) ยึด **mobile-first**

## 7. Review Log
- **2026-10-09** — ขั้น [1] สแกนโค้ด + [2] เขียนไฟล์รองนี้ เสร็จ · เมนูนี้สุก/มีเทสต์ครบ ต่างจาก #12/#13/#14 · finding หลัก = G1 (lifecycle ไม่รับ `students.manage`) · G2/G3/G4 เป็นความไม่สอดคล้อง/ยืนยันดีไซน์ · **รอเจ้าของเคาะ Q1–Q4** — หลายข้ออาจปิดเป็น "by design" โดยไม่ต้องแก้โค้ด

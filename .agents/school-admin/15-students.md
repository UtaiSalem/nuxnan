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
| ST-S5 | ฟอร์มเพิ่มนักเรียนรายคน + ลบถาวร (BE store/destroy + policy create/delete + route gate students.create/students.delete + FE) | Q4 | ⚪ กำลังทำ |
| ST-S6 | เทสต์ authz + tenant + permission matrix + create/delete | S1–S5 | ⚪ |
| ST-S7 | ตรวจจอจริง 375/768/1280 | S1–S5 | ⚪ (เจ้าของ) |

**Rule:** Claude วางแผน+ตรวจ · agy เขียนโค้ด (ถ้าใช้ได้) · งาน UI แปะกติกา mobile-first · verify build/test ก่อนขึ้น 🟢

## 8. Review Log
- **2026-10-11** — ขั้น [1]+[2] (Claude): สแกน FE (students.vue/index/import/intake + StudentDataTable) + BE (ClassroomController getAllStudents/getStudent · StudentProfile/Master controllers · Intake/Import/Account/Lifecycle · StudentMasterProfilePolicy · CheckAcademyPermission) + routes academy.php/student-profile.php · เขียนไฟล์รองนี้ · พบ gap G1–G6 · รอเคาะ Q1–Q4

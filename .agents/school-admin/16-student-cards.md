# 16 — บัตรนักเรียน (Student Cards)

> ไฟล์รองของเมนู `16 บัตรนักเรียน` (`admin/student-cards/`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> ขั้น [1] สแกนโค้ดจริง + [2] เขียนไฟล์รอง — **เสร็จ 2026-10-10** · ยังไม่ส่ง step ให้ agy · รอเจ้าของเคาะ Q1–Q4

## 1. Scope & Purpose

จัดการ **บัตรประจำตัวนักเรียน** ของโรงเรียน: ดูรายชื่อ/สถิติ, แก้ข้อมูลบนบัตร (ชื่อ ไทย/อังกฤษ · รหัส · รูป · วันออก/หมดอายุ), พิมพ์บัตร, นำเข้า/ส่งออก, และ **ระบบคำร้องขอทำบัตร** (ครูประจำชั้นยื่น → ฝ่ายผลิตบัตรอนุมัติ/จัดทำ)

ผู้ใช้ที่เกี่ยวข้อง:
- **admin/owner · ฝ่ายที่ได้ `students.manage`/`students.cards.produce`** — งานระดับโรงเรียน (สร้าง/นำเข้า/ส่งออก/sync/ผลิตบัตร)
- **ครูประจำชั้น (`students.cards.request`)** — ยื่นคำร้อง + แก้บัตร/รายชื่อ **เฉพาะห้องตนเอง**
- **นักเรียน/ผู้ปกครอง** — ดูบัตรของตนเอง (นอกเมนู admin นี้)

🔑 **หัวใจของเมนูนี้ = มี 2 ระบบคู่ขนาน** (ต้องตัดสินใจที่ Q2):
1. **ชุด academy-scoped (ใหม่, แนะนำ)** — `/api/academies/{academy}/student-cards/*` + คำร้อง `/student-card-requests/*` · กันสิทธิ์ครบ 3 ชั้น + tenant isolation + มีเทสต์
2. **ชุด public เก่า (deprecated)** — `/api/student-card/*` · **ไม่ล็อกอิน** กันแค่ config flag + throttle · มีไว้ให้ครูที่ยังไม่ได้เป็นสมาชิกเว็บใช้ก่อนตั้งค่าครูประจำชั้น

## 2. Current State (จากการสแกนโค้ดจริง)

### Frontend
**ชุด admin ใหม่** (`ui/pages/academies/[name]/admin/student-cards/`):
- `index.vue` (567) — list + statistics + filter level/section · ยิง `GET .../student-cards/statistics`, `.../admin/students`, `.../search` · ปุ่มไป print/import/edit/view
- `[id]/index.vue` (303) — ดูบัตรรายคน
- `[id]/edit.vue` (350) — แก้บัตร · `GET .../student-cards/profile/{id}` · `PUT .../student-cards/{id}` · `POST .../admin/upload-photo/{id}`
- `import.vue` (330) — `POST .../student-cards/admin/import` + `GET .../admin/export?format=template`
- `print.vue` (336) — พิมพ์บัตร · ดึง statistics/levels/by-room
- `requests/index.vue` (548) — รายการคำร้อง + bulk submit · ใช้ composable `useStudentCardRequests`
- `requests/[id].vue` (27, inline) — รายละเอียดคำร้อง + approve/reject/start/complete ผ่าน `api.transition()`

**ชุด public เก่า** (`ui/pages/student-card/`):
- `index.vue`, `[level]/[room].vue` — หน้าจัดการห้องแบบสาธารณะ
- `admin/index.vue`, `admin/students/[level]/[room].vue` — หน้า admin สาธารณะ (ใส่รหัสผ่านต่อ action ในดีไซน์เดิม)

### Components
- `components/student-card/` — `AddStudentModal`, `RemoveStudentModal`, `TransferStudentModal`, `RequestCardModal`, `BulkRequestCardModal`, `CardSyncPreviewPanel`, `StudentCardItem`
- `components/academy/student-card/StudentCardRoomManager.vue`
- `components/learn/student-card/StudentCardFront.vue` · `StudentCardBack.vue`

### Composables
- `ui/composables/useStudentCardRequests.ts` — base `/api/academies/{academy}/student-card-requests` · `list/counts/show/myClassrooms/classroomStudents/bulk/transition(id,action)`

### Backend
- **Controllers** (`app/Http/Controllers/Api/Learn/Student/Card/`):
  - `StudentCardController` (1000) — index/dashboard/statistics/search/profile/byStudent/getStudentByRoom + admin: adminStudents/audit/syncPreview/syncCommit/store/import/export/bulk\* + edit: update/updateImage/updateStudentID/updateStudentNameTh/En/destroyPhoto + public: publicUpdate/publicUpdateImage/publicDestroyPhoto
  - `StudentCardRequestController` (225) — คำร้อง flow (auth + permission)
  - `PublicStudentCardRequestController` (195) — คำร้อง flow สาธารณะ (ไม่ auth)
  - `AcademyStudentCardManageController` (180) — จัดรายชื่อในห้อง (academy-scoped) · ตรวจสิทธิ์รายห้องผ่าน access service
  - `StudentCardManageController` (147) — จัดรายชื่อในห้อง (public เก่า)
- **Routes**:
  - `routes/learn/academy-student-card.php` — academy-scoped · 3 ชั้น (view/view+access-service/manage)
  - `routes/learn/academy-student-card-request.php` — คำร้อง · `students.cards.request,students.cards.produce`
  - `routes/studentcard/studentcard.php` — public เก่า · ส่วนใหญ่ไม่ auth
- **Services**: `StudentCardAccessService` (ตัดสินสิทธิ์รายห้อง/รายใบ) · `StudentCardRequestService` (สร้าง/transition/complete=สร้างบัตร) · `StudentCardSyncService` · `StudentCardAuditService`
- **Models**: `StudentCard` · `StudentCardRequest`
- **FormRequests**: `StoreStudentCardRequest` · `BulkStoreStudentCardRequest` · `RejectStudentCardRequest`
- **Policies**: `StudentCardRequestPolicy`
- **Enums**: `StudentCardRequestOrigin/Reason/Status/Type`
- **Commands**: `ReconcileStudentCards` · `StudentCardAudit` · `SyncStudentCards`
- **Config**: `config/student-card.php` → `public_management` (env `PUBLIC_STUDENT_CARD_MANAGEMENT`, default **false**) · `public_requests` (env `PUBLIC_STUDENT_CARD_REQUESTS`, default **false**)

### Database
- `student_cards` — snapshot บัตร (class_level/class_section/level_and_room = snapshot ตอนออกบัตร · ดู worklog track 1/2) · student_status: active/graduated/expired
- `student_card_requests` — คำร้อง (status pending→approved→in_progress→completed · cancelled/rejected · origin teacher/public · unique open-request ต่อ student)
- source of truth รายชื่อห้อง = `classroom_students` (active) → `classrooms` (is_current year)

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | ดูรายชื่อ/สถิติบัตร (list, by-room, statistics) | ✅ | academy-scoped กัน `students.view` · tenant ok |
| 2 | แก้ข้อมูลบัตร (ชื่อ/รหัส/รูป/วันที่) | ✅ | `update`/`updateImage`/`updateStudent*` + access-service รายใบ |
| 3 | ครูประจำชั้นแก้ได้เฉพาะห้องตัวเอง | ✅ | `StudentCardAccessService::canManageCard` (enrollment active + homeroom) |
| 4 | ระบบคำร้องทำบัตร (ยื่น→อนุมัติ→จัดทำ→สร้างบัตร) | ✅ | `StudentCardRequestController` + service · `complete()` สร้าง StudentCard + expire ใบเก่า |
| 5 | คำร้องแบบกลุ่ม (bulk submit / bulk transition) | ✅ | `bulkStore` / `bulkTransition` |
| 6 | พิมพ์บัตร (print) | ⚠️ | หน้า `print.vue` มี · ไม่ได้ทดสอบจอจริงในลูปนี้ |
| 7 | **นำเข้าข้อมูล (import CSV/Excel)** | ❌ | `import()` คืน **501 stub** · `import.vue` ยิงเข้า endpoint นี้ = หน้าตาย (G1) |
| 8 | **ส่งออกข้อมูล (export / template)** | ❌ | `export()` คืน **501 stub** · ปุ่มดาวน์โหลด template ตาย (G1) |
| 9 | **อัพโหลดรูปแบบกลุ่ม (bulk photos)** | ❌ | `bulkUploadPhotos()` คืน 501 stub (G1) |
| 10 | **แก้ไขแบบกลุ่ม (bulk update)** | ❌ | `bulkUpdate()` คืน 501 stub (G1) |
| 11 | สร้างบัตรตรง (store) | ⚠️ | สร้าง StudentCard แต่ **ไม่สร้าง enrollment** → ไม่โผล่ใน roster (G2) |
| 12 | audit / sync (legacy) | ⚠️ | `syncCommit` ปิดเป็น 410 เมื่อ `card_request_flow_enabled` · audit ยังเปิด |

## 4. Permission Matrix

| Permission key | Owner | Admin | ฝ่าย (ผลิตบัตร) | ครูประจำชั้น | Staff | Student | Guardian |
|---|---|---|---|---|---|---|---|
| `students.view` (อ่าน list/stats/by-room) | ✅ | ✅ | ✅ | ✅ (seed ให้ทุก member?) | ⚠️ | ❌ | ❌ |
| `students.view` + access-service (แก้บัตรรายห้อง) | ✅ | ✅ | ✅ | ✅ เฉพาะห้องตน | ❌ | ❌ | ❌ |
| `students.manage` (สร้าง/นำเข้า/ส่งออก/sync) | ✅ | ✅ | ⚠️ ตาม grant | ❌ | ❌ | ❌ | ❌ |
| `students.cards.request` (ยื่นคำร้อง) | ✅ | ✅ | — | ✅ (teacher role) | ❌ | ❌ | ❌ |
| `students.cards.produce` (อนุมัติ/ผลิต) | ✅ | ✅ (seed) | ✅ director (seed) | ❌ | ❌ | ❌ | ❌ |

> keys `students.cards.request/produce` seed แล้วใน `2026_08_29_000002_reconcile_system_role_permissions.php` (teacher=request · director/admin=produce)
> ⚠️ `card_admin` role มีในโค้ดแต่ไม่มีแถวในฐาน (ดู 01-roles-permissions.md:271) — ตรวจว่าเป็น vestigial หรือยังต้องใช้ (G8)

## 5. Gap Analysis

- **G1 (P1 · ฟีเจอร์ตาย)** — `import()`/`export()`/`bulkUploadPhotos()`/`bulkUpdate()` คืน **501 "ยังอยู่ระหว่างพัฒนา"** (StudentCardController.php:960–999) แต่ FE `import.vue` ยิง `POST .../admin/import` (105) และเปิด `GET .../admin/export?format=template` (124) → หน้านำเข้า/ดาวน์โหลดเทมเพลตใช้ไม่ได้จริง
- **G2 (data integrity)** — `store()` (830–895) สร้าง `StudentCard` (+ `Student` ถ้าไม่มี) แต่ **ไม่สร้าง `ClassroomStudent`** และไม่ตั้ง `academic_year_id`/`level_and_room` · roster ขับด้วย classroom_students → บัตรที่สร้างตรงจะไม่โผล่ในห้อง · ควรบังคับให้การสร้างบัตรไหลผ่าน request flow (`complete()`) หรือเติม enrollment ให้ครบ
- **G3 (authz · public review ไม่มีการยืนยันตัวตน)** — `PublicStudentCardRequestController::reviewRequest` (approve/reject/start/complete) + `cancelRequest` + `publicUpdate/publicUpdateImage/publicDestroyPhoto` **ไม่ auth** กันแค่ config + per-academy `card_request_flow_enabled` + throttle · **comment ใน route (studentcard.php:62–63) อ้างว่ามี "admin password verification per action" แต่โค้ดจริงไม่มีการตรวจรหัสผ่านเลย** → ถ้าโรงเรียนเปิด flag ใครเข้าหน้า public ก็อนุมัติ/ปฏิเสธคำร้อง + แก้ข้อมูล/รูปบัตรของทั้งห้องได้ · flag default OFF จึงยังไม่ถูก exploit แต่ comment หลอก + surface ต้องเคาะว่าจะตัดทิ้งหรือเติมการตรวจจริง
- **G4 (authz · legacy auth routes ไม่กัน tenant)** — legacy `/api/student-card/profile/{student_card}` และ `/update/{student_card}` (auth:api, ไม่มี academy) → `profile()`/`update()` ได้ `academy=null` ⇒ `ensureCardBelongsToAcademy` และ `ensureCanManageCard` **ข้ามการตรวจทั้งคู่** → ผู้ใช้ที่ล็อกอินคนไหนก็อ่าน PII / แก้บัตรข้ามโรงเรียนได้ (เส้น academy-scoped กันครบแล้ว — ปัญหาเฉพาะเส้น legacy)
- **G5 (consistency)** — public `progressPublic(Completed)` แค่เปลี่ยนสถานะเป็น completed **ไม่สร้าง StudentCard** (ต่างจาก authenticated `complete()` ที่สร้างบัตร + expire ใบเก่า) · น่าจะตั้งใจ (public = คำร้องอย่างเดียว) แต่ต้องยืนยัน
- **G6 (ซ้ำซ้อน FE/surface)** — มี FE 2 ชุด (public `student-card/*` + admin `academies/[name]/admin/student-cards/*`) · OVERVIEW ชี้ชุดใหม่ · ชุดเก่ายังมีไฟล์และเข้าถึงได้ → เคาะว่าจะเก็บเป็น fallback หรือปลดระวาง
- **G7 (cross-academy search · legacy)** — `search()`/`adminStudents()` เมื่อ `$academy=null` query `StudentCard` ทั้งระบบ · เส้น academy-scoped bind academy เสมอ (ปลอดภัย) · เส้น legacy `/student-card/search` (auth:api) hit ด้วย null → ค้นข้ามโรงเรียน (ผูกกับ G4/G6)
- **G8 (role vestigial)** — `card_admin` ไม่มีแถวในฐาน (01-roles-permissions.md:271) · ตรวจว่าตัดทิ้งได้หรือยังต้องใช้

## 6. Implementation Tasks (ส่งให้ agy ทีละ step — รอเคาะ Q ก่อน)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| SCD-S1 | ปิด G1 — import นักเรียน/บัตร (Excel/CSV) จริง | Q1 | `import()` ใช้ maatwebsite/excel · validate + รายงานผลรายแถว · FE import.vue ต่อจริง | ⚪ |
| SCD-S2 | ปิด G1 — export + template จริง | Q1 | `export()` คืนไฟล์ + `format=template` | ⚪ |
| SCD-S3 | ปิด G2 — บังคับสร้างบัตรผ่าน request flow **หรือ** เติม enrollment ใน `store()` | Q3 | ตามคำตัดสิน · + เทสต์ roster | ⚪ |
| SCD-S4 | ปิด G3/G4/G7 — ตัด/ล็อก legacy public+auth routes | Q2 | ถ้าเคาะปลดระวาง: ลบเส้น + FE เก่า · ถ้าเก็บ: เติม auth/tenant/password จริง + แก้ comment หลอก | ⚪ |
| SCD-S5 | ปิด G8 — เคลียร์ `card_admin` vestigial | Q2 | migration ลบ/seed ตามคำตัดสิน | ⚪ |
| SCD-S6 | เทสต์ครอบ authz + tenant + request flow + import/export | S1–S5 | `php artisan test -c phpunit.mysql.xml --filter=StudentCard*` เขียว | ⚪ |
| SCD-S7 | ตรวจจอจริง 375/768/1280 (list/edit/print/import/requests) | S1–S4 | เจ้าของ verify | ⚪ |

**Rule:** ทุก step ต้อง verify (build/test/manual) ก่อนขึ้น 🟢 · งาน UI แปะกติกา mobile-first เสมอ

## 7. ค้าง — รอเจ้าของเคาะก่อนเริ่ม SCD-S1

- **Q1** — import/export/bulk (G1): ทำรอบนี้เลยไหม? ถ้าใช่ รูปแบบไฟล์ = Excel (.xlsx) + คอลัมน์ไหนบ้าง? bulk photos/bulk update จำเป็นไหมหรือ defer?
- **Q2** — ชุด public เก่า `/api/student-card/*` + หน้า `ui/pages/student-card/*` (G3/G4/G6/G7): **ปลดระวางได้แล้วหรือยัง?** (ตอนนี้มี academy-scoped + request flow ครบ) ถ้ายังต้องเก็บเป็น fallback → ต้องเติม auth/tenant/การตรวจรหัสผ่านจริง + แก้ comment ที่อ้างว่ามี password verification
- **Q3** — `store()` สร้างบัตรตรง (G2): เก็บไว้ (แล้วเติม enrollment ให้ครบ) หรือบังคับให้ทุกการสร้างบัตรไหลผ่าน request flow (`complete()`)?
- **Q4** — legacy `syncCommit`/`audit` ยังมีบทบาทไหม หรือ request flow เป็น source เดียวแล้ว (syncCommit ถูกปิดเป็น 410 เมื่อ `card_request_flow_enabled`)

## 8. Review Log
- **2026-10-10** — ขั้น [1]+[2] (Claude): สแกน routes (3 ไฟล์) + StudentCardController (1000) + RequestController + PublicRequestController + AccessService + RequestService + StudentCard model + FE admin/requests pages + config · เขียนไฟล์รองนี้ · พบ gap G1–G8 · รอเคาะ Q1–Q4

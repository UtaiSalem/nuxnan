# 17 — เยี่ยมบ้าน (Home Visits)

> ไฟล์รองของเมนู **#17 เยี่ยมบ้าน** (`admin/home-visits/`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> ขั้น [1] สแกนโค้ดจริง + [2] เขียนไฟล์รอง — **เสร็จ 2026-10-12** · รอเจ้าของเคาะ Q1–Q5 ก่อนแตก step / ลงมือ

## 1. Scope & Purpose

ระบบบันทึก/ติดตาม **การเยี่ยมบ้านนักเรียน** ของฝ่ายบริหารงานกิจการนักเรียน — admin จัดการรายการเยี่ยมบ้าน (CRUD + เปลี่ยนสถานะ) · กำหนด **โซน (zone)** พื้นที่เยี่ยมบ้าน · ดูสถิติ/แดชบอร์ด · ส่งออกรายงาน · ครูประจำชั้นบันทึกการเยี่ยมบ้านของนักเรียนในความดูแล · ข้อมูลเยี่ยมบ้านยังโผล่เป็นแท็บหนึ่งใน **โปรไฟล์นักเรียน (Master profile เมนู #15)**

ผู้ใช้: owner/admin · ฝ่ายบริหารงานกิจการนักเรียน (department admin) · ครูประจำชั้น · (นักเรียน/ผู้ปกครองดูได้เฉพาะของตนผ่าน master profile)

## 2. Current State (จากการสแกนโค้ดจริง)

### Frontend
- Pages (academy-scoped — ที่ใช้งานจริง):
  - `ui/pages/academies/[name]/admin/home-visits/index.vue` — list visits + stats + zones (gate `isAdmin || can('home_visits.view')`)
  - `.../create.vue` — สร้าง visit (gate `home_visits.manage`)
  - `.../[id]/index.vue` — รายละเอียด visit + ลบ (gate `home_visits.view`)
  - `.../[id]/edit.vue` — แก้ visit (gate `home_visits.manage`)
  - `.../zones.vue` — จัดการโซน (gate `home_visits.manage`)
  - `.../export.vue` — ส่งออก (gate `home_visits.view`)
- Component: `ui/components/student/profile/HomeVisitTab.vue` — แท็บเยี่ยมบ้านในโปรไฟล์นักเรียน
- Composable: `ui/composables/useHomeVisit.ts` — ยิง `/api/academies/{academy}/students/{student}/home-visits` (Master\HomeVisitController)
- **Legacy FE:** `ui/pages/home-visit/index.vue` — หน้า subsystem เก่าแบบ standalone (ลิงก์ไป `/home-visit/auth/login`)

### Backend — มี **3 พื้นผิว (surface) คู่ขนาน** (pattern เดียวกับเมนู #16)

**(A) academy-scoped (ใหม่)** — `routes/learn/academy-home-visit.php` · prefix `/api/academies/{academy}/home-visits` · ทั้งกลุ่มใต้ `auth:api`
- `zones/*` → `ZoneController` · gate `home_visits.view` (read) / `home_visits.manage` (write) ✅
- `admin/*` → `AdminController` · gate `home_visits.view` / `home_visits.manage` · **tenant-scoped แน่น** (`where('academy_id', $academy->id)` ทุก query · route-model-bind `Academy $academy`) ✅
- `/`, `/dashboard`, `/statistics` (top-level) → `AdminController` · **ไม่มี permission gate** (auth:api ล้วน) ⚠️
- `student/*` + `teacher/*` → `Master\*` + `TeacherController` · **ไม่มี permission gate + controller ไม่รับ `Academy`** ⚠️🔴
- `auth/*` → `HomeVisitAuthController` session login (ฮาร์ดโค้ดรหัส) 🔴

**(B) legacy public** — `routes/homevisit/homevisit.php` · prefix `/api/home-visit` · **ทั้งกลุ่มไม่มี `auth:api` เลย**
- `student-login`/`teacher-login`/`admin-login`/`logout`/`check-auth` → `HomeVisitAuthController`
- `student/*` + `teacher/*` → PII write + home-visit CRUD (gate เป็น session flag ที่คอมเมนต์ออกบางส่วน) 🔴
- `/zones` public
- หมายเหตุในไฟล์: "legacy admin API ถูกถอดแล้ว" แต่ student/teacher ยังอยู่ · register จริงใน `api.php:248`

**(C) master profile (ปลอดภัย — เมนู #15 คุมแล้ว)** — `routes/learn/student-profile.php` · `Master\HomeVisitController`
- `apiResource /academies/{academy}/students/{student}/home-visits` + `.../status`
- guard แน่น: `ensureStudentScope`/`ensureVisitScope` (tenant) · `accessLevel`/`isStaff`/`canManage` (graded access) ✅ — เป็น endpoint ที่ FE จริง (composable + HomeVisitTab) ใช้

### Database
- `student_home_visits` — รายการเยี่ยมบ้าน (มี `academy_id`, `student_id`, `zone_id`, `visit_status`, observations/recommendations/follow_up/next_visit)
- `home_visit_zones` — โซน (scope academy ตาม migration `2026_07_09_000002`)
- `home_visit_participants` — ผู้ร่วมเยี่ยม
- `home_visit_images` — รูปภาพ (`uploaded_by`)
- `visit_status` ENUM (up ล่าสุด): `completed, pending, rescheduled, scheduled, cancelled, in-progress` default `completed`

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | Admin list/CRUD visits | ✅ | `AdminController` + FE ครบ · tenant-safe |
| 2 | Zone management | ✅ | `ZoneController` gate view/manage |
| 3 | สถิติ/แดชบอร์ด | ⚠️ | ทำงานได้ แต่ endpoint top-level ไม่ติด gate (G4) |
| 4 | ส่งออกรายงาน | ✅ | `admin/export/visits` + `reports/export` |
| 5 | เยี่ยมบ้านในโปรไฟล์นักเรียน (#15) | ✅ | `Master\HomeVisitController` guard ครบ |
| 6 | ครูประจำชั้นบันทึกเยี่ยมบ้าน | ⚠️ | ทำผ่าน master (safe) ได้ · แต่ `teacher/*` legacy เปิดโล่ง (G2/G3/G5) |
| 7 | permission delegate ให้ฝ่าย | ❌ | `home_visits.*` ไม่ถูก assign ให้ role (G6) |
| 8 | auth เป็นระบบเดียว (JWT) | ❌ | subsystem มี session-login + รหัสฮาร์ดโค้ด (G1) |

## 4. Permission Matrix (ที่ควรเป็น — ยืนยันกับเจ้าของ)

| Permission key | Owner | Admin | ฝ่ายกิจการนักเรียน | ครูประจำชั้น | Staff | Student | Guardian |
|---|---|---|---|---|---|---|---|
| `home_visits.view` | ✅ | ✅ | ✅ (ในฝ่าย) | ⚠️ เฉพาะห้องตน | ❌ | ของตน (master) | ของบุตร (master) |
| `home_visits.create` | ✅ | ✅ | ✅ | ⚠️ เฉพาะห้องตน | ❌ | ❌ | ❌ |
| `home_visits.manage` | ✅ | ✅ | ✅ (ในฝ่าย) | ⚠️ เฉพาะห้องตน | ❌ | ❌ | ❌ |

> ปัจจุบัน owner/admin/director ผ่านได้ด้วย `Academy::userCan` (role-based) · custom role ยังรับ `home_visits.*` ไม่ได้เพราะไม่มี grant (G6)

## 5. Gap Analysis

- **G1 (P0 · security):** `HomeVisitAuthController` มี **รหัสฮาร์ดโค้ดในซอร์ส** — `teacher`/`jsm1234`, `admin`/`jsm_admin2025` · และ studentLogin ยืนยันด้วย `national_id`+`student_number` ของ `StudentCard` (ไม่มีรหัสผ่าน) → ตั้ง PHP session flag ข้าม JWT ทั้งระบบ
- **G2 (P0 · security):** legacy group `/api/home-visit/*` (`routes/homevisit/homevisit.php`) **ไม่มี `auth:api` ทั้งกลุ่ม** · `teacher/*` + `student/*` เขียน PII (academic-info/address/contact/health/guardian) + ลบ/แก้ home-visit ได้ · register จริงใน `api.php:248` → **เปิดสู่อินเทอร์เน็ตเท่าที่ session guard ไม่กัน**
- **G3 (P0 · tenant):** ใน academy-scoped (A) กลุ่ม `teacher/*` + `student/*` **ไม่มี `academy.permission`** และ `TeacherController` รับแค่ `$studentId`/`$homeVisitId` **ไม่รับ `Academy`** → ไม่มี tenant isolation · user ที่ล็อกอิน (JWT) คนใดก็ยิงได้ ข้ามโรงเรียนด้วยการเดา id
- **G4 (P1 · leak):** top-level `/home-visits/`, `/dashboard`, `/statistics` (line 36–38) ไม่ติด gate — FE `index.vue` เรียก `/home-visits/statistics` ตัวที่ไม่ gate (ไม่ใช่ `admin/statistics` ที่ gate) → นับสถิติของโรงเรียนไหนก็ได้ด้วย JWT ใด ๆ
- **G5 (P0 · security):** `TeacherController` เมธอดอันตราย (`updateHomeVisit`/`deleteHomeVisit`/`updateStudent`/`createHomeVisitWithImages`/`updateHomeVisitWithImages`) **คอมเมนต์ session-check ออกหมด** → แม้แต่ guard อ่อน ๆ ของ subsystem เองก็ถูกปิด
- **G6 (P1):** `home_visits.view/create/manage` มีใน catalog (`AcademyPermission.php`) แต่ **ไม่ถูก assign ให้ role ใดใน `database/`** → มอบงานเยี่ยมบ้านให้ custom role (ฝ่ายกิจการนักเรียน) ไม่ได้ (เหมือน G4 ของเมนู #15) · อนึ่ง `home_visits.create` นิยามไว้แต่ route ใช้แค่ view/manage
- **G7 (P2 · contract):** `visit_status` ไม่สอดคล้องกัน 3 ที่ — DB enum (completed/pending/rescheduled/scheduled/cancelled/in-progress) · `AdminController` ใช้ pending/completed · `useHomeVisit.ts` ใช้ scheduled/completed/cancelled → ค่าจากแต่ละทางไม่ match กัน
- **G8 (P2 · dup):** มีระบบคู่ขนาน (legacy subsystem FE `pages/home-visit/` + BE `/api/home-visit/*`) ซ้อนกับ academy-scoped + master (เหมือน G6 เมนู #16) — FE จริงใช้ตัว academy-scoped/master แล้ว legacy เป็น dead-but-dangerous surface
- **G9 (P2):** `HomeVisitImage.uploaded_by` default `session('homevisit_user_id', 1)` → เมื่อ session ว่าง attributes อัปโหลดให้ user id 1

## 6. Open Questions (รอเจ้าของเคาะก่อนลงมือ)

- **Q1 (G1/G2/G5/G8):** จะ **ปลดระวาง subsystem เก่า** (`routes/homevisit/homevisit.php` + `HomeVisitAuthController` + session/teacher legacy) เลยไหม? (เทียบเคสเมนู #16 ที่เจ้าของเลือก "เก็บ public ไว้ก่อน" เพราะครูยังใช้) — ถ้ายังต้องเก็บ: อย่างน้อยต้องถอดรหัสฮาร์ดโค้ด + ปิดเมธอดที่ comment auth ออก
- **Q2 (G3):** กลุ่ม `teacher/*` + `student/*` ใน academy-scoped — จะ **ยุบทิ้ง** (ให้ใช้ master profile ของ #15 แทน ซึ่ง guard ครบแล้ว) หรือจะ **เติม `academy.permission` + Academy param + tenant scope**?
- **Q3 (G6):** key `home_visits.*` → ผูกให้ role ไหนบ้าง (owner/admin/director auto + ฝ่ายกิจการนักเรียน?) · ครูประจำชั้นให้ทำผ่าน master (per-room) อย่างเดียวพอไหม · เก็บ `home_visits.create` หรือยุบเป็น manage?
- **Q4 (G4):** top-level `/statistics` `/dashboard` ย้ายเข้า gate `home_visits.view` ได้เลยไหม (FE แก้ path ตามได้)
- **Q5 (G7):** มาตรฐาน `visit_status` ให้ยึดชุดไหนเป็น SSOT (เสนอ: ใช้ชุดของ master `StoreHomeVisitRequest` เป็นหลัก แล้วปรับ Admin + FE composable ให้ตรง)

## 7. Implementation Tasks (ส่งให้ agy/codex ทีละ step — **ยังไม่เริ่มจนกว่าจะเคาะ Q**)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| HV-S1 | ถอดรหัสฮาร์ดโค้ด + ปิด/ลบ legacy auth (G1) | Q1 | — | ⚪ pending |
| HV-S2 | ปลดระวาง/ปิด legacy `/api/home-visit/*` (G2/G5/G8) | Q1 | — | ⚪ pending |
| HV-S3 | ยุบ/กัน tenant กลุ่ม teacher+student academy-scoped (G3) | Q2 | — | ⚪ pending |
| HV-S4 | gate top-level statistics/dashboard (G4) + FE path | Q4 | — | ⚪ pending |
| HV-S5 | grant `home_visits.*` ให้ role (migration) (G6) | Q3 | — | ⚪ pending |
| HV-S6 | unify `visit_status` SSOT (G7) | Q5 | — | ⚪ pending |
| HV-S7 | เทสต์ authz/tenant (HomeVisit suite) | S1–S6 | — | ⚪ pending |
| HV-S8 | ตรวจจอจริง 375/768/1280 | S1–S7 | — | ⚪ pending |

**Rule:** ทุก step ต้อง verify (`php -l` / `pint` / `php artisan test -c phpunit.mysql.xml --filter=...` / `npm run build` / คลิก 375px) ก่อนขึ้นสถานะ 🟢 — รายงาน agy เชื่อไม่ได้ ต้อง `git diff` + รันเกณฑ์เอง

## 8. Review Log
- **2026-10-12** — ขั้น [1] สแกน + [2] เขียนไฟล์รอง เสร็จ · พบ gap G1–G9 (P0 สามข้อ: รหัสฮาร์ดโค้ด · legacy ไม่มี auth · teacher/student ไม่ scope tenant) · มี `StudentHomeVisitApiTest` + `tests/Feature/HomeVisit/` เดิมอยู่แล้ว (ยังไม่ได้ประเมินความครอบคลุม) · รอเจ้าของเคาะ Q1–Q5

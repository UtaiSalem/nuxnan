# 17 — เยี่ยมบ้าน (Home Visits)

> ไฟล์รองของเมนู `17 เยี่ยมบ้าน` (`admin/home-visits/`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> **สถานะ:** 🔴 audit เสร็จ 2026-10-11 — ฟีเจอร์แอดมินสุก/มีเทสต์ แต่มี **ระบบเก่าค้าง (legacy subsystem) ที่เป็นช่องโหว่ P0** ขนานอยู่

## 1. Scope & Purpose

ระบบบันทึก/ติดตาม **การเยี่ยมบ้านนักเรียน** ของโรงเรียน — เป็นงานของ **ฝ่ายบริหารงานกิจการนักเรียน** (ร่วมกับครูประจำชั้น)

ครอบคลุม:
- บันทึกการเยี่ยมบ้าน (วันที่/เวลา/โซนพื้นที่/ผู้เยี่ยม/ผู้ร่วม/ข้อสังเกต/ข้อเสนอแนะ/ติดตามผล/รูปหลักฐาน)
- จัดการ **โซนพื้นที่ (zones)** สำหรับแบ่งเขตเยี่ยมบ้าน
- สถิติ/แดชบอร์ด (จำนวนเยี่ยม · อัตราเยี่ยม · รายเดือน)
- รายงาน + ส่งออก CSV/PDF
- ผู้เกี่ยวข้อง: owner/admin/director (จัดการเต็ม) · ครู/ครูประจำชั้น (บันทึกของนักเรียนตน) · นักเรียน/ผู้ปกครอง (ดูของตัวเอง ไม่เห็น observations ภายใน)

## 2. Current State (จากการสแกนโค้ดจริง)

> 🔴 **ประเด็นสำคัญที่สุด: เมนูนี้มี 3 "ระบบ" ซ้อนกัน** คุณภาพต่างกันคนละชั้น — ระบบใหม่ 2 ชุดดี, ระบบเก่า 1 ชุดเป็นช่องโหว่

### ระบบ A — JWT Master-profile flow ✅ (ระบบจริงที่หน้าโปรไฟล์ใช้)
- **Routes:** `routes/learn/student-profile.php` → `/api/academies/{academy}/students/{student}/home-visits` (apiResource) + `.../home-visits/{homeVisit}/status`
- **Controller:** `app/Http/Controllers/Api/Learn/Student/Master/HomeVisitController.php` (218 บรรทัด)
- **Authz:** ครบและดี — `accessLevel()` แยก self/admin/homeroom/teacher/parent · tenant scope (`ensureStudentScope`/`ensureVisitScope` เช็ค `academy_id`/`student_id` ⇒ 404) · gate `observations` ให้เห็นเฉพาะ staff · `store/update` = `isStaff` · `destroy/updateStatus` = `canManage` (admin หรือครูประจำชั้น)
- **FE:** `ui/composables/useHomeVisit.ts` → ยิง endpoint นี้ · ใช้ใน `ui/components/student/profile/HomeVisitTab.vue` (แท็บในหน้าโปรไฟล์นักเรียน)
- **หมายเหตุ:** authz เป็น **role-based** (`AcademyMember.role ∈ owner/admin/director/teacher`) ไม่ได้ผ่าน `Academy::userCan('home_visits.*')` ⇒ custom role ที่ถือ `home_visits.manage` แต่ role ไม่ใช่ 4 ตัวนี้ **จะทำไม่ได้** (ดู G7 — รูปแบบเดียวกับ G1 ของเมนู #15)

### ระบบ B — Academy Admin flow ✅ (ระบบจริงที่หน้าแอดมิน `admin/home-visits/` ใช้)
- **Routes:** `routes/learn/academy-home-visit.php` → prefix `/api/academies/{academy}/home-visits`
- **Controllers:** `.../HomeVisit/AdminController.php` (887 บรรทัด) · `.../HomeVisit/ZoneController.php` (248 บรรทัด)
- **Authz:** กลุ่ม `admin/*` + `zones/*` มี `academy.permission:home_visits.view` (read) / `home_visits.manage` (write) · ทุก query scope `where('academy_id', $academy->id)` · `updateStudent` ย้ายชั้นผ่าน `StudentEnrollmentService` ถูกต้อง · export CSV/PDF (mPDF) scoped
- **FE pages:** `ui/pages/academies/[name]/admin/home-visits/` — `index.vue` (list+stats) · `create.vue` · `[id]/index.vue` (ดู) · `[id]/edit.vue` · `zones.vue` · `export.vue` · ทุกหน้า guard FE ด้วย `can('home_visits.view'|'home_visits.manage')`
- **เทสต์:** `tests/Feature/HomeVisit/AdminControllerTest.php` (13 เคส — statistics scope, classroom filter, enrollment transfer, cross-academy reject, zones scope, "legacy admin route is gone") + `tests/Feature/StudentHomeVisitApiTest.php` (3 เคส — ระบบ A)

### ระบบ C — Legacy session-based subsystem 🔴 (ช่องโหว่ P0 — ของเก่าโรงเรียน JSM โรงเดียว)
- **Routes:** `routes/homevisit/homevisit.php` → prefix `/api/home-visit` · **`require` ที่ระดับบนสุดของ `api.php` บรรทัด 248 โดย `ไม่มี middleware ครอบเลย`** (ไม่มีแม้ `auth:api`)
- **Controllers:**
  - `.../HomeVisit/HomeVisitAuthController.php` (127) — login แบบ **session** · 🔴 **รหัสผ่านฮาร์ดโค้ดในซอร์ส:** ครู `teacher`/`jsm1234` · แอดมิน `admin`/`jsm_admin2025`
  - `.../HomeVisit/TeacherController.php` (509) — dashboard/search เช็ค session อยู่ แต่ **เมธอดเขียน/ลบ (`updateHomeVisit`, `deleteHomeVisit`, `updateStudent`, `createHomeVisitWithImages`, `updateHomeVisitWithImages`) ด่าน session ถูก comment ทิ้งทั้งหมด** (บรรทัด 205-207, 241-243, 276-278, 331-333, 406-408) · ไม่มี academy scope — `Student::findOrFail`, `StudentHomeVisit::findOrFail` ตรง ๆ ทั้ง DB
- **FE:** `ui/pages/home-visit/index.vue` (251) — หน้า portal เดี่ยว ฮาร์ดโค้ด "โรงเรียนจริยธรรมศึกษามูลนิธิ" · **orphaned** (ไม่มีลิงก์จาก nav หลักเข้ามาเลย) · ปุ่มเลือกบทบาททั้งสาม `push('/home-visit/auth/login')` ซึ่ง **เพจนั้นไม่มีจริง** (มีแค่ `index.vue`)
- **สถานะ legacy admin API:** ถูกถอดไปแล้ว (คอมเมนต์ใน `homevisit.php` + เทสต์ `..._legacy_admin_route_is_gone`) — **แต่ teacher/student session routes + portal ยังค้าง**

### ช่องว่างใน `academy-home-visit.php` เอง (ระบบ B แต่ผูกกับ controller ระบบ C)
- กลุ่ม `student/*` และ `teacher/*` ใต้ `/academies/{academy}` อยู่ใต้ **`auth:api` เท่านั้น ไม่มี `academy.permission`** และชี้ไป `TeacherController` (ระบบ C ที่ด่าน session comment ทิ้ง) + `Master/StudentController` ⇒ user ที่ล็อกอิน (คนไหนก็ได้) เรียก `PUT/DELETE .../teacher/*` ได้ (ดู G2)
- กลุ่มราก `/` `/dashboard` `/statistics` + `auth/*` อยู่ใต้ `auth:api` เท่านั้น **ไม่ติด `home_visits.view`** (ดู G4)

### Database
- `student_home_visits` (academy_id, student_id, zone_id, visit_date/time, visitor_name/position, visit_status enum[completed,pending,rescheduled,scheduled,cancelled,in-progress], observations, notes, recommendations, follow_up, next_visit, created_by)
- `home_visit_zones` (academy_id nullable, zone_name, zone_code, color, is_active, display_order) — scoped ต่อ academy (migration `2026_07_09_000002_scope_home_visit_zones_to_academies`)
- `home_visit_participants` (home_visit_id, participant_name/position/role)
- `home_visit_images` (home_visit_id, image_path/name/type, description, uploaded_by)
- Models: `StudentHomeVisit` · `HomeVisitZone` · `HomeVisitParticipant` · `HomeVisitImage`

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | บันทึกเยี่ยมบ้าน (admin) | ✅ | AdminController `storeVisit` + `create.vue` · scoped + gated |
| 2 | ดู/แก้/ลบ เยี่ยมบ้าน (admin) | ✅ | `showVisit/updateVisit/destroyVisit` · `home_visits.manage` |
| 3 | เปลี่ยนสถานะ | ✅ | `updateVisitStatus` |
| 4 | บันทึก/ดูเยี่ยมบ้านในหน้าโปรไฟล์นักเรียน | ✅ | ระบบ A — authz ครบ gate observations |
| 5 | จัดการโซนพื้นที่ (CRUD + reorder + toggle) | ✅ | ZoneController · scoped + gated |
| 6 | สถิติ/แดชบอร์ด | ⚠️ | มี แต่ `/statistics` `/dashboard` ไม่ติดด่าน `home_visits.view` (G4) |
| 7 | รายงาน + ส่งออก CSV/PDF | ✅ (CSV/PDF) ⚠️ (reports) | `exportVisits` ทำจริง · `reports()`/`exportReports()` เป็น stub "not implemented" |
| 8 | อัปโหลดรูปหลักฐาน | ⚠️ | มีเฉพาะใน **ระบบ C** (TeacherController) ที่ปิดตาย/ไม่ปลอดภัย · ระบบ A/B ยังไม่รับ multipart images |
| 9 | ผู้ร่วมเยี่ยม (participants) | ⚠️ | ตาราง+โมเดลมี · เขียนผ่านระบบ C เท่านั้น · ระบบ A/B ยังไม่จัดการ participants |
| 10 | แยกสิทธิ์ตามบทบาท (ครูเห็นเฉพาะห้องตน) | ✅ | ระบบ A ทำ (homeroom) |
| 11 | Legacy portal/session subsystem | 🔴 | ช่องโหว่ P0 (G1/G2/G3) — ควรลบ |

## 4. Permission Matrix

Permission keys ที่มีในระบบ (`AcademyPermission.php`): `home_visits.view` · `home_visits.create` · `home_visits.manage`

| Permission key | Owner | Admin | Director | Teacher | Staff | Student | Guardian |
|---|---|---|---|---|---|---|---|
| `home_visits.view` | ✅ | ✅ | ✅ | ✅ | ⚠️ (ตาม role) | ❌* | ❌* |
| `home_visits.create` | ✅ | ✅ (ผ่าน manage) | ✅ | ✅ (มี key แต่ **ไม่มี route ใช้**) | ❌ | ❌ | ❌ |
| `home_visits.manage` | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

\* นักเรียน/ผู้ปกครองดูเยี่ยมบ้าน "ของตัวเอง" ผ่านระบบ A (ไม่ผ่าน permission key แต่ผ่าน `accessLevel`=self/parent)

**ความไม่สอดคล้องที่พบ:**
- `home_visits.create` ถูก assign ให้ teacher แต่ **ไม่มี endpoint ไหนบังคับคีย์นี้** — create ทั้งหมดบังคับ `home_visits.manage` (ระบบ B) หรือ `isStaff` role-based (ระบบ A) ⇒ ครูที่ไม่ใช่ owner/admin/director สร้างผ่านหน้าแอดมินไม่ได้ (ได้เฉพาะหน้าโปรไฟล์นักเรียนตนเองในฐานะ homeroom/teacher ของระบบ A)
- ระบบ A ใช้ **role literal** ไม่ใช่ `userCan(key)` ⇒ custom role พัง (G7)

## 5. Gap Analysis

### 🔴 P0 — ช่องโหว่ความปลอดภัย (ต้องปิดก่อน)

- **G1 — Legacy `/api/home-visit/*` เปิดสาธารณะ + ด่าน session ถูก comment ทิ้ง**
  `routes/homevisit/homevisit.php` ถูก `require` โดยไม่มี middleware ใด ๆ (แม้ `auth:api`) + `TeacherController` เมธอดเขียน/ลบ comment ด่าน session ทิ้ง ⇒ **ใครก็ได้ (ไม่ต้องล็อกอิน)** ยิง:
  - `DELETE /api/home-visit/teacher/delete-home-visit/{id}` → ลบเยี่ยมบ้านใครก็ได้ทั้ง DB (+ ลบไฟล์รูป)
  - `PUT /api/home-visit/teacher/update-student/{id}` → แก้ชื่อ/เลขบัตร ปชช./ชั้นเรียนนักเรียนคนไหนก็ได้
  - `POST /api/home-visit/teacher/create-home-visit/{id}` + `update-home-visit-with-images` → สร้าง/แก้ + อัปโหลดไฟล์
  - ไม่มี academy scope เลย (ข้ามโรงเรียนทั้งหมด)
  🔴 ต้องพิสูจน์ด้วยการยิงจริง (เหมือน G1 ของเมนู #8/#11/#13)

- **G2 — `academy-home-visit.php` กลุ่ม `teacher/*`,`student/*` ไม่มีด่านสิทธิ์ + ชี้ controller ที่พัง**
  อยู่ใต้ `auth:api` เท่านั้น (ไม่มี `academy.permission`) และชี้ `TeacherController` (ด่าน session comment ทิ้ง) ⇒ user ที่ล็อกอิน **คนไหนก็ได้** เรียก `PUT/DELETE /api/academies/{academy}/teacher/delete-home-visit/{homeVisit}` ฯลฯ ได้ ข้ามโรงเรียน (controller ไม่เช็ค academy)

- **G3 — รหัสผ่านฮาร์ดโค้ดในซอร์ส + สมมติโรงเรียนเดียว**
  `teacher`/`jsm1234`, `admin`/`jsm_admin2025` ใน `HomeVisitAuthController` · `Student::count()`/`StudentHomeVisit::count()` ทั้ง DB ไม่แยกโรงเรียน · เป็นมรดกจากระบบโรงเดียว (JSM)

### 🟡 P1/P2 — คุณภาพ/ความสอดคล้อง

- **G4 — root routes ไม่ติดด่าน `home_visits.view`**
  `/academies/{academy}/home-visits` `/` `/dashboard` `/statistics` + `auth/*` อยู่ใต้ `auth:api` เท่านั้น ⇒ user ที่ล็อกอินคนไหนก็อ่านสถิติ/จำนวนนักเรียน-เยี่ยมบ้านรายโรงเรียนได้ (scoped by academy_id แต่ไม่เช็คว่าเป็นสมาชิก/มีสิทธิ์) · `index.vue` เรียก `/statistics` จริง
  (หมายเหตุ: `auth/*` login subgroup ใต้ academy prefix เป็น session login ที่ไม่สมเหตุสมผลกับ JWT — ของตกค้าง)

- **G5 — หน้า portal เดี่ยว `/home-visit` + ปลายทางไม่มีจริง**
  `ui/pages/home-visit/index.vue` ฮาร์ดโค้ดชื่อโรงเรียน JSM · **มีลิงก์เข้าจาก `ui/pages/Learn/Dashboard.vue:423` (`to="/home-visit"`)** (แก้จาก audit รอบแรกที่เขียนว่า orphaned) · ปุ่มทั้งหมด `push('/home-visit/auth/login')` ซึ่งเพจนั้นไม่มีในรีโปปัจจุบัน (มีแค่ `index.vue`) ⇒ ครูเข้าถึงได้จริงจาก Dashboard แต่ login flow ในรีโปนี้ไม่ครบ (prod อาจเป็นบิลด์เก่า)

- **G6 — TeacherController เป็น dead/Inertia-era code**
  `createHomeVisit` (non-images) `return redirect()->back()` (ใช้ไม่ได้กับ API JSON) · ทั้ง controller เป็นมรดก session ที่ระบบ A/B แทนที่หมดแล้ว

- **G7 — ระบบ A ใช้ role literal ไม่ใช่ `userCan(key)`**
  `HomeVisitController` เช็ค `AcademyMember.role ∈ {owner,admin,director,teacher,co_teacher}` ตรง ๆ ⇒ custom role ที่ถือ `home_visits.manage`/`.view` แต่ชื่อ role ไม่ตรง จะทำไม่ได้ (inconsistent กับระบบ B ที่ใช้ `academy.permission`) · รูปแบบเดียวกับ G1 ของเมนู #15

- **G8 — `home_visits.create` เป็น orphan key** (ดู §4) · `reports()`/`exportReports()` เป็น stub

- **G9 — participants/images ไม่มีทางเขียนที่ปลอดภัย**
  ฟีเจอร์ "ผู้ร่วมเยี่ยม" + "รูปหลักฐาน" เขียนได้ผ่านระบบ C (ที่จะถูกลบ) เท่านั้น · ระบบ A/B ยังไม่รองรับ multipart/participants ⇒ ถ้าลบ C ต้องย้ายความสามารถนี้มา B (หรือยืนยันว่าไม่ใช้)

- **G11 — dashboard eager-load คอลัมน์ผี (เจอตอนลงมือ HV-S4)**
  `getAllVisitsForReports()` (เรียกจาก `dashboard()`) `with(['participants:...,participant_position,participant_role', 'images:...,image_description'])` — 3 คอลัมน์นี้ไม่มีจริง (ตารางมีแค่ `participant_name,notes` / `description`) ⇒ dashboard มีโอกาส 500 (unknown column) เมื่อมีข้อมูล · **HV-S4 แก้ select ให้ตรงคอลัมน์จริงแล้ว**

- **G10 — status drift ในระบบ A (เจอตอนลงมือ HV-S3)**
  `HomeVisitController` ค้นสมาชิกด้วย `->where('status', 1)` แต่ค่า canonical คือ `AcademyMember::STATUS_APPROVED = 2` (ใช้โดย `isApprovedMember`/`userCan`) ⇒ สมาชิกอนุมัติจริง (status=2) อาจไม่ถูกรู้จักผ่าน path role เดิม · เทสต์เดิมก็ seed `status=1` จึงเขียวทั้งที่ค่าผิด · **HV-S3 ปิดเลี่ยงด้วยการเพิ่ม path `userCan` (status=2) แบบ OR** (ไม่ถอด path เดิม — ไม่มีใครเสียสิทธิ์) · การล้าง `status=1`→`2` ทั้ง controller+เทสต์ให้ตรง canonical ต้องรันสวีตบน MySQL จึงยกให้เจ้าของ (ยังไม่แตะรอบนี้)

## 6. Implementation Tasks

> ⚠️ cloud session นี้ **ไม่มี composer vendor / agy** → ตรวจได้แค่ `php -l` · การรัน `phpunit.mysql.xml` + `pint` + `npm run build` เป็นงาน owner-gated (เหมือนเมนูก่อน ๆ)
> 🔴 **ลำดับบังคับ (บทเรียน #16 SCD-S4):** สร้างทางใหม่ที่ปลอดภัยให้ครบ → ย้าย FE พอร์ทัล → เจ้าของยืนยันครูย้ายแล้ว → **ค่อยลบ legacy (HV-S6)** · ห้ามลบก่อน

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| HV-S1 | **G1/G2:** ถอดกลุ่ม session เก่า (`auth/*`,`student/*`,`teacher/*`) ออกจาก `academy-home-visit.php` — ไม่มี FE ใช้ ชี้ controller ที่ด่าน comment ทิ้ง | — | route group หาย · เส้นที่เหลือติด `home_visits.view/manage` ครบ · `php -l` ผ่าน | 🟢 **done (owner verify MySQL)** |
| HV-S2 | **G4:** ยก root `/` `/dashboard` `/statistics` เข้าใต้ `academy.permission:home_visits.view` | — | route gated · ไม่มีเทสต์ยิง root (ยืนยันแล้ว) | 🟢 **done (owner verify MySQL)** |
| HV-S3 | **G7 (Q2):** `HomeVisitController` (ระบบ A) รับ `Academy::userCan('home_visits.view'/'.create'/'.manage')` แบบ OR ควบคู่ role (ปิดเลี่ยง G10 status drift ไปด้วย) | — | เพิ่ม userCan ใน accessLevel/isStaff/canManage + เทสต์ custom-role 1 เคส · `php -l` ผ่าน | 🟢 **done (owner verify MySQL)** |
| HV-S4 | **G9 (Q3):** ระบบ B (admin flow) รับ participants + อัปโหลด/ลบรูปหลักฐาน (multipart) ให้ความสามารถที่เคยอยู่แค่ระบบ C ย้ายมาที่ปลอดภัย | — | storeVisit/updateVisit/showVisit + validation + helpers + เทสต์ 1 เคส (fake upload) · fix G11 ด้วย · FE consumption → HV-S5 | 🟢 **BE done (owner verify MySQL)** |
| HV-S5a | **Q1/G1:** ระบบ A (`Master/HomeVisitController`) รับ participants+images (parity กับ legacy) · trait `HandlesHomeVisitAttachments` ใช้ร่วม AdminController (เลิก dup) · `StoreHomeVisitRequest` + serialize + load relations | S4 | store/update/show/index + เทสต์ ระบบ A 1 เคส · `php -l` ผ่าน | 🟢 **done (owner verify MySQL)** |
| HV-S5b | ~~rebuild `/home-visit` → JWT landing~~ **↩️ REVERTED 2026-10-11** — เจ้าของเคาะ "ทำ 2 ระบบคู่กัน · portal สาธารณะแบบ hardcoded เหมือนเดิม" ⇒ คืนหน้า portal เก่า (role-chooser + hardcoded) ด้วย `git checkout 2824381 -- ui/pages/home-visit/index.vue` · JWT อยู่คนละทาง (academy admin menu + HomeVisitTab) | — | portal เดิมกลับมา | ↩️ reverted (by design) |
| HV-S5c | **parity UI:** เพิ่ม participants repeater + อัปโหลด/ลบรูป (multipart) ใน `HomeVisitTab.vue` + `useHomeVisit.ts` ให้ครูแนบรูป/ผู้ร่วมได้เท่าพอร์ทัลเก่า | S5a | FormData multipart (POST+`_method=PUT` spoof เมื่อมีรูป) + preview · รูปผ่าน `/storage/**` proxy · จอจริง | 🟢 **done (owner verify build+375px)** |
| HV-S6 | ~~ลบ legacy~~ **❌ CANCELLED 2026-10-11** — เจ้าของเคาะเก็บ legacy ไว้เป็น **ระบบสาธารณะชั่วคราว (hardcoded เหมือนเดิมทุกอย่าง)** คู่ขนานกับ JWT · `homevisit.php` + `HomeVisitAuthController` + `TeacherController` + รหัสฮาร์ดโค้ด **คงไว้ตามเดิม ไม่แตะ** | — | — | ❌ cancelled (เจ้าของสั่งเก็บ) |
| HV-S7 | **G8:** ตัดสิน `home_visits.create` key (ครูสร้างผ่านหน้าแอดมินได้ไหม) + `reports()` stub · เทสต์รวม MySQL + จอจริง | S4 | ตาม decision + สวีตเขียว | ⚪ pending |

**Rule:** ทุก step ต้อง verify (ยิงจริง/เทสต์/build) ก่อนขึ้น 🟢

## 7. Decisions (เจ้าของเคาะ 2026-10-11)

- **Q1 (รอบแรก) → ครูยังล็อกอิน `/home-visit` ด้วยรหัสฮาร์ดโค้ดอยู่จริง**
- **Q2 → ใช่** ระบบ A gate ด้วย `home_visits.*` key (HV-S3 ✅ additive)
- **Q3 → ย้าย participants + รูปหลักฐานเข้าระบบ B** (HV-S4 ✅)
- **🔁 ทิศทางสุดท้าย (เจ้าของเคาะย้ำ 2026-10-11): ทำ 2 ระบบคู่ขนาน**
  1. **ระบบ JWT** (ผ่าน login ระบบจัดการโรงเรียน) — academy admin pages + student-profile `HomeVisitTab` · gate `home_visits.*` · academy-scoped · มีเทสต์ **(ทำครบแล้ว: HV-S1–S4 + S5a + S5c)**
  2. **ระบบสาธารณะชั่วคราว (hardcoded เหมือนเดิมทุกอย่าง)** — `/home-visit` portal + `/api/home-visit/*` · เจ้าของสั่ง **"ทำแบบฮาร์ดโค้ดเหมือนเดิม ไม่เลียนแบบบัตรนักเรียน"** และ **"คงด่าน auth/รหัส legacy ไว้เหมือนเดิมทุกอย่าง"**
  - ⇒ **HV-S5b ถอนกลับ** (คืน portal เดิม) · **HV-S6 ยกเลิก** (ไม่ลบ legacy) · แนวทาง config-gated แบบบัตรนักเรียนที่เคยเริ่ม **ยกเลิกทั้งหมด** (ลบ `config/home-visit.php` ที่ร่างไว้ · ไม่สร้าง `PublicHomeVisitController`)
  - 🔴 **บันทึกความเสี่ยงที่เจ้าของรับทราบและยอมรับ:** พอร์ทัล public นี้ `/api/home-visit/*` ไม่มี middleware + ด่าน session ถูก comment + route `api` stateless ⇒ **เขียน/ลบข้อมูล + PII ได้โดยไม่ต้องรหัสจริง (เปิดโล่งทั้ง DB ข้ามโรงเรียน)** · เจ้าของเลือกคงไว้ตามเดิมเป็นการชั่วคราว (ครูยังใช้) — ควรปิด/ย้ายเข้า JWT เมื่อครูพร้อม

## 8. Review Log
- **2026-10-11 (audit)** — ขั้น [1]+[2] เสร็จ · พบ 3 ระบบซ้อน · P0 = ระบบ C (legacy) เปิดสาธารณะ/รหัสฮาร์ดโค้ด/ด่าน comment ทิ้ง
- **2026-10-11 (เคาะ Q1–Q3)** — Q1 ครูยังใช้ → migrate JWT (ห้ามลบก่อน) · Q2 ใช่ · Q3 ย้ายเข้า B
- **2026-10-11 (HV-S1/S2/S3 ลงมือ — Claude เขียนเองใน cloud เพราะไม่มี agy)** —
  - S1: ถอด `auth/*`+`student/*`+`teacher/*` ออกจาก `academy-home-visit.php` (ยืนยันไม่มี FE/test/`route()` อ้าง) · เส้นที่เหลือ (dashboard/zones/admin) ติด `home_visits.view/manage` ครบ
  - S2: root `/`+`/dashboard`+`/statistics` ยกเข้าใต้ `home_visits.view` (ไม่มีเทสต์ยิง root · `index.vue` ที่เรียก guard ด้วย `can('home_visits.view')` อยู่แล้ว)
  - S3: `HomeVisitController` accessLevel/isStaff/canManage เพิ่ม `userCan` (status=2 canonical) แบบ OR + เทสต์ `test_custom_role_with_home_visits_manage_...` · เจอ+บันทึก G10 (status=1 vs 2 drift)
  - ตรวจ: `php -l` ผ่านทุกไฟล์ · **ยังไม่รัน phpunit/pint (ไม่มี vendor) — เจ้าของ verify: `php artisan test -c phpunit.mysql.xml --filter=StudentHomeVisitApiTest` + `--filter=HomeVisit` + `pint`**
  - **ค้าง: HV-S4 (participants/รูป→B) · HV-S5 (migrate พอร์ทัล JWT) · HV-S6 (ลบ legacy หลังยืนยันครูย้าย) · HV-S7**
- **2026-10-11 (HV-S4 ลงมือ)** — เจ้าของเคาะ "ลุยต่อ · ครูมีบัญชี nuxnan ครบแล้ว" (ปลดล็อก HV-S5) ·
  `AdminController`: `storeVisit`/`updateVisit` รับ `participants[]`+`images[]`(multipart)+`images_to_delete[]` · helper `syncParticipants` (ส่งมา=แทนที่ทั้งชุด · null=คงเดิม) + `handleImages` (ลบ scoped ต่อ visit + อัปโหลด disk public) · `showVisit` โหลด participants/images/zone · `validateVisit` เพิ่มกติกา (ทั้งหมด optional — contract JSON เดิมไม่พัง) · แก้ **G11** select คอลัมน์ผีใน `getAllVisitsForReports` · เทสต์ `test_admin_visit_stores_participants_and_images_then_update_replaces_and_deletes` (Storage::fake + UploadedFile::fake + PUT spoof) · `php -l` ผ่าน
  - **FE consumption (participants/image UI) → รวมใน HV-S5** (teacher portal rebuild แตะหน้าจอชุดเดียวกัน · แอดมินไม่ค่อยแนบรูปเอง)
  - เจ้าของ verify: `php artisan test -c phpunit.mysql.xml --filter=AdminControllerTest`
- **2026-10-11 (HV-S5a + HV-S5b ลงมือ — เจ้าของเคาะ "ลุย · ครูมีบัญชีครบ")** —
  - **S5a (BE parity):** แยก trait `app/Traits/HandlesHomeVisitAttachments.php` (`syncHomeVisitParticipants`+`handleHomeVisitImages`) · refactor `AdminController` มาใช้ trait (ลบ dup + ลบ import `HomeVisitImage/Participant/Storage` ที่ไม่ใช้) · `Master/HomeVisitController` (ระบบ A) use trait · store/update รับ participants+images (`visitAttributes()` ตัดคีย์ attachment ก่อน create/update) · index/show/serialize เพิ่ม participants/images · `StoreHomeVisitRequest` เพิ่มกติกา optional · เทสต์ `test_staff_can_attach_participants_and_images_via_master_flow` (Storage::fake+fake upload+PUT spoof)
  - **S5b (FE portal):** เขียน `ui/pages/home-visit/index.vue` ใหม่ทั้งไฟล์ — จาก role-chooser session (ปุ่มไป `/home-visit/auth/login` ที่ไม่มีจริง) เป็น **JWT landing** (`middleware:['auth']`) · ดึง `/api/academies/users/{id}/membered-academies` · การ์ดโรงเรียน → `/academies/{name}/admin/home-visits` · **ไม่เรียก `/api/home-visit/*` อีก** · mobile-first (gutter 16px · touch 44px · `min-w-0`/`truncate`/`break-words`) · ไม่มี node_modules ใน cloud → compile-check ไม่ได้ เจ้าของ `npm run build` + ตรวจ 375px
  - ตรวจ: `php -l` ผ่านทุกไฟล์ BE · เจ้าของ verify: `php artisan test -c phpunit.mysql.xml --filter=StudentHomeVisitApiTest` + `pint` + `npm run build`
  - **ค้าง: HV-S5c (participants/image UI ใน HomeVisitTab — parity สุดท้ายก่อนลบ legacy) → HV-S6 (ลบ legacy หลังเจ้าของยืนยันครูย้าย) → HV-S7**
  - 🔴 **legacy `/api/home-visit/*` + รหัสฮาร์ดโค้ด ยังเปิดอยู่** — FE เลิกใช้แล้ว แต่ backend ยังมีจน HV-S6
- **2026-10-11 (HV-S5c ลงมือ)** — เจ้าของเคาะ "ลุยต่อ" ·
  - `useHomeVisit.ts`: เพิ่ม type participants/images · `createVisit`/`updateVisit` เลือก multipart เฉพาะเมื่อมีรูป (POST + `_method=PUT` spoof สำหรับ update เพราะ PHP แกะ multipart ได้เฉพาะ POST) · ไม่งั้นส่ง JSON (participants ไปทาง JSON ได้) · `buildFormData` ส่ง `participants[i][participant_name/notes]` + `images[]` + `images_to_delete[]`
  - `HomeVisitTab.vue`: participants repeater (เพิ่ม/ลบแถว) · อัปโหลดรูปหลายไฟล์ + preview (object URL) · รูปเดิม toggle ลบ (เข้า `images_to_delete`) · openEdit นำ participants/images เดิมมาแสดง (round-trip) · แสดง participants chips + รูป thumbnail ในรายการ · รูปผ่าน `/storage/${image_path}` (Nuxt storage-proxy เหมือนรูปอื่นในแอป)
  - cloud ไม่มี ui/node_modules → compile-check ไม่ได้ · เจ้าของ `npm run build` + ตรวจ 375px (บันทึกเยี่ยม+แนบรูป+ผู้ร่วม ครบวงผ่านแท็บ)
  - **HV-S5 ครบแล้ว (a+b+c)** → เหลือ **HV-S6 (ลบ legacy หลังเจ้าของยืนยันครูใช้ JWT ได้จริง) → HV-S7**
- **2026-10-11 (เปลี่ยนทิศ: 2 ระบบคู่ขนาน — hardcoded เหมือนเดิม)** — เจ้าของเคาะ "ทำ 2 ระบบคู่กัน · public แบบ hardcoded เหมือนเดิม ไม่เลียนแบบบัตรนักเรียน · คง legacy ไว้ทุกอย่าง" ·
  - เริ่มร่างแนวทาง config-gated (`config/home-visit.php`) แล้ว **เจ้าของ interrupt ให้หยุด** → ลบไฟล์ร่างทิ้ง (ไม่เคย commit)
  - **ถอน HV-S5b:** `git checkout 2824381 -- ui/pages/home-visit/index.vue` คืน portal role-chooser + hardcoded เดิม (JWT อยู่คนละทาง: academy admin menu + HomeVisitTab)
  - **ยกเลิก HV-S6:** ไม่ลบ legacy — `homevisit.php`/`HomeVisitAuthController`/`TeacherController`/รหัสฮาร์ดโค้ด คงเดิมทุกอย่าง (ไม่เคยถูกแตะในเซสชันนี้)
  - คงไว้ที่ทำแล้ว (JWT side): HV-S1/S2/S3/S4 (academy-flow hardening + custom-role + participants→B) · HV-S5a (ระบบ A parity + trait) · HV-S5c (HomeVisitTab participants/image UI) — ทั้งหมดไม่กระทบ legacy
  - **สถานะเมนู #17: ทั้งสองระบบพร้อมคู่ขนาน** · เหลือ HV-S7 (G8 `home_visits.create` key + reports stub + เทสต์รวม/จอจริง) · 🔴 legacy public ยังเปิดโล่งตามที่เจ้าของเลือก (บันทึกความเสี่ยงไว้ §7)

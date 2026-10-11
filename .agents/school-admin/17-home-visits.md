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

- **G5 — หน้า portal เดี่ยว `/home-visit` orphaned + ปลายทางไม่มีจริง**
  `ui/pages/home-visit/index.vue` ฮาร์ดโค้ดชื่อโรงเรียน JSM · ไม่มีลิงก์จาก nav เข้ามา · ปุ่มทั้งหมด `push('/home-visit/auth/login')` ซึ่งเพจนั้นไม่มี ⇒ หน้าตายที่ยังชี้ backend ระบบ C ที่ไม่ปลอดภัย

- **G6 — TeacherController เป็น dead/Inertia-era code**
  `createHomeVisit` (non-images) `return redirect()->back()` (ใช้ไม่ได้กับ API JSON) · ทั้ง controller เป็นมรดก session ที่ระบบ A/B แทนที่หมดแล้ว

- **G7 — ระบบ A ใช้ role literal ไม่ใช่ `userCan(key)`**
  `HomeVisitController` เช็ค `AcademyMember.role ∈ {owner,admin,director,teacher,co_teacher}` ตรง ๆ ⇒ custom role ที่ถือ `home_visits.manage`/`.view` แต่ชื่อ role ไม่ตรง จะทำไม่ได้ (inconsistent กับระบบ B ที่ใช้ `academy.permission`) · รูปแบบเดียวกับ G1 ของเมนู #15

- **G8 — `home_visits.create` เป็น orphan key** (ดู §4) · `reports()`/`exportReports()` เป็น stub

- **G9 — participants/images ไม่มีทางเขียนที่ปลอดภัย**
  ฟีเจอร์ "ผู้ร่วมเยี่ยม" + "รูปหลักฐาน" เขียนได้ผ่านระบบ C (ที่จะถูกลบ) เท่านั้น · ระบบ A/B ยังไม่รองรับ multipart/participants ⇒ ถ้าลบ C ต้องย้ายความสามารถนี้มา B (หรือยืนยันว่าไม่ใช้)

## 6. Implementation Tasks (ส่งให้ agy ทีละ step — ยังไม่เริ่มจนกว่าเจ้าของเคาะ Q)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| HV-S1 | **ปิด P0:** ลบ/ปิด legacy `routes/homevisit/homevisit.php` ทั้งไฟล์ (teacher/student session routes) — ขึ้นกับ Q1 | Q1 | เอา `require` ออกจาก `api.php` + ลบไฟล์ route + พิสูจน์ด้วยยิงจริง 404/401 | ⚪ pending |
| HV-S2 | **ปิด P0/G2:** ลบกลุ่ม `teacher/*`,`student/*` ออกจาก `academy-home-visit.php` (หรือติดด่าน `home_visits.manage` + tenant ถ้ายังต้องใช้) | Q1 | route group หาย/gated + เทสต์ | ⚪ pending |
| HV-S3 | **G3/G5/G6:** ลบ `HomeVisitAuthController`, `TeacherController`, `ui/pages/home-visit/` (ขึ้นกับ Q1) | Q1 | ไฟล์หาย · ไม่มี reference ค้าง | ⚪ pending |
| HV-S4 | **G4:** ยก `/` `/dashboard` `/statistics` เข้าใต้ `academy.permission:home_visits.view` + ลบ `auth/*` subgroup ที่ตกค้าง | — | route gated + เทสต์ยืนยัน 403 ให้ non-member | ⚪ pending |
| HV-S5 | **G7:** เปลี่ยน `HomeVisitController` (ระบบ A) ให้ gate ด้วย `Academy::userCan('home_visits.view'/'.manage')` ควบคู่ role (ตาม Q2) | Q2 | custom role นายทะเบียน/ฝ่ายกิจการ ทำได้ + เทสต์ | ⚪ pending |
| HV-S6 | **G8/G9:** ตัดสิน `home_visits.create` key + participants/images (ตาม Q3) | Q3 | ตาม decision | ⚪ pending |
| HV-S7 | เทสต์รวม (MySQL) + ตรวจจอ 375/768/1280 หน้าแอดมิน | S1–S6 | `phpunit.mysql.xml` เขียว + `npm run build` | ⚪ pending |

**Rule:** ทุก step ต้อง verify (ยิงจริง/เทสต์/build) ก่อนขึ้น 🟢 — รายงาน agy เชื่อไม่ได้

## 7. คำถามที่ต้องให้เจ้าของโปรเจคเคาะ (Q)

- **Q1 — ลบระบบ C (legacy session + portal) ได้เลยไหม?**
  ระบบ A (หน้าโปรไฟล์) + ระบบ B (หน้าแอดมิน) ครอบคลุมฟีเจอร์ทั้งหมดแล้ว และ legacy admin API ก็ถูกถอดไปก่อนหน้าแล้ว
  ⚠️ **ระวังบทเรียนเมนู #16 (SCD-S4):** หน้า no-login `/student-card/{level}/{room}` เคยถูกลบแล้ว revert เพราะครูยังใช้อยู่ — ต้องถามก่อนว่ามีครูยังล็อกอิน `/home-visit` ด้วย `teacher/jsm1234` อยู่จริงหรือไม่ (ถ้ามี = ต้องย้ายเข้าระบบ JWT ก่อนลบ)
  → ถ้า "ลบได้" → HV-S1/S2/S3 · ถ้า "ยังใช้อยู่" → ต้องปิดช่องโหว่โดยไม่ลบ (ใส่ auth กลับ + academy scope — งานใหญ่กว่า)

- **Q2 — ระบบ A ควรรับ custom role ผ่าน `home_visits.*` key ไหม (G7)?** (แนะนำ: ควร — ให้สอดคล้องกับทั้งระบบตามที่เคาะในเมนู #15 G1)

- **Q3 — participants + รูปหลักฐาน (G9):** ย้ายเข้าระบบ B (admin flow รับ multipart/participants) หรือยืนยันว่าไม่ใช้แล้ว? และจะทำอย่างไรกับ `home_visits.create` key ที่ไม่มี route ใช้ (G8)

## 8. Review Log
- **2026-10-11** — ขั้น [1] สแกนโค้ด + [2] เขียนไฟล์รองนี้ เสร็จ (Claude) · พบ 3 ระบบซ้อน · P0 = ระบบ C (legacy) เปิดสาธารณะ/รหัสฮาร์ดโค้ด/ด่าน comment ทิ้ง · ยังไม่ส่ง step ให้ agy — รอเจ้าของเคาะ Q1–Q3

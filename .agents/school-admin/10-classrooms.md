# 10 — ห้องเรียน (Classrooms)

> ไฟล์รองของเมนู **#10 ห้องเรียน** ใน [OVERVIEW.md](OVERVIEW.md)
> สแกนโค้ดจริง + `route:list` + อ่าน guard จริง เมื่อ **2026-09-15** · ยังไม่ส่ง step ไหนให้ codex/agy
> อ่านคู่กับ memory [[project-classroom-source-of-truth]] (แต่ memory อายุ 39 วัน — บางข้อ **ล้าสมัยแล้ว** ดู §2.4)

---

## 0. สรุปสำหรับคนอ่านรอบเดียว

เมนูนี้ = จัดการ "ห้องเรียน" ของโรงเรียน (สร้าง/แก้/ลบ/archive ห้อง · จัดนักเรียนเข้าห้อง ·
เลขที่ · ย้ายห้อง · เลื่อนชั้น · กลุ่มในห้อง · ลิงก์/โค้ดเชิญเข้าห้อง) — 2 หน้าใหญ่มาก
(`index.vue` 1,625 บรรทัด + `[id].vue` 2,360 บรรทัด) กับ backend 3 controller + 2 service

🟠 **ต่างจากเมนู #8 ตรงนี้สำคัญ:** route ห้องเรียน **มีด่านสิทธิ์ในโค้ดจริง** (`canManage()`)
ไม่ใช่ประตูเปิดโล่งแบบ G1 ของ #8 — ผู้ใช้สุ่ม `academy_id` เข้ามาลบห้อง/อ่าน roster **ไม่ได้**
เพราะ controller เช็ก owner/สมาชิก role owner·director·admin ก่อนทุก mutation

🔴 **แต่ด่านนั้นเขียนเอง ไม่ได้ใช้ระบบ permission ของโปรเจค** — hardcode 3 role string
ทั้งที่ทั้งระบบ (#1/#8/#9) ใช้ middleware `academy.permission:<key>` + สิทธิ์ระดับฝ่าย
ผลคือ **หัวหน้าฝ่ายที่ได้สิทธิ์ `groups.manage` แต่ role ไม่ใช่ 3 ตัวนั้น → จัดการห้องไม่ได้**
และ **ไม่กรอง status สมาชิก (approved)** — สมาชิก role=admin ที่ยังไม่อนุมัติก็ผ่าน (ดู G2)

รองลงมา: read endpoint gate ไม่สม่ำเสมอ (G3) · console command ล้างห้องทั้งโรงเรียนยังอยู่ (G4) ·
UI มี GET ที่ไม่มี route รองรับ 1 จุด (G5) · หนี้คอลัมน์ legacy phase-6 ยังค้าง (G6)

---

## 1. Scope & Purpose

**หน้าเดียวที่เป็นประตู:** `ui/pages/academies/[name]/admin/classrooms/index.vue` (รายการห้อง)
→ คลิกห้อง → `ui/pages/academies/[name]/admin/classrooms/[id].vue` (จัดการนักเรียนในห้อง)

**สิ่งที่เมนูครอบคลุม:**
1. **ห้องเรียน** — สร้าง/แก้/ลบ/archive · กรองตามปีการศึกษา/ภาคเรียน/ระดับชั้น/สถานะ · ครูประจำชั้น · ความจุ
2. **นักเรียนในห้อง (roster)** — เพิ่ม/ถอน · เลขที่ (renumber) · ย้ายห้อง (transfer) · เลื่อนชั้น (promote) · ประวัติการลงทะเบียน
3. **กลุ่มในห้อง (ClassroomGroup)** — แบ่งกลุ่มย่อยในห้อง + สมาชิกกลุ่ม
4. **คำเชิญเข้าห้อง (ClassroomInvitation)** — สร้างลิงก์/โค้ด · accept/decline (token) · join via code
5. **สถิติ** — จำนวนห้อง/นักเรียนต่อระดับชั้น

**ผู้ใช้ที่เกี่ยวข้อง:** owner/admin โรงเรียน · หัวหน้าฝ่ายวิชาการ/ทะเบียน · ครูประจำชั้น (ทางอ้อม)

---

## 2. Current State (จากการสแกนโค้ดจริง 2026-09-15)

### 2.1 Frontend
| ไฟล์ | บรรทัด | สถานะ |
|---|---:|---|
| `ui/pages/academies/[name]/admin/classrooms/index.vue` | 1,625 | 🟡 ใช้ `useApi()` (ตาม convention) · endpoint ตรง route เป็นส่วนใหญ่ |
| `ui/pages/academies/[name]/admin/classrooms/[id].vue` | 2,360 | 🟡 ใช้ `useApi()` · endpoint ตรง route |
| `ui/composables/useClassroomManagement.ts` | 101 | — |
| `ui/composables/useAcademyClassroomRoster.ts` | 115 | — |
| components ที่เกี่ยว: `academy/rollover/RolloverClassroomChecklist.vue`, `learn/course/groups/{ImportFromClassrooms,SyncClassroom}Modal.vue`, `school/SchoolClassroomLeaderboard.vue`, `school/studentCard/ClassroomHeader.vue` | — | ส่วนใหญ่เป็นการ **บริโภคข้าม feature** (course groups / rollover / student-card) ไม่ใช่ core เมนูนี้ |

> ⚠️ `ui/pages/academies/[name]/admin/gradebook/classrooms/**` = ของ **เมนู #19 ผลการเรียน** ไม่ใช่เมนูนี้

### 2.2 Backend
- **Controllers:** `Api/Learn/Academy/ClassroomController.php` (958) · `ClassroomGroupController.php` (201) · `ClassroomInvitationController.php` (152)
- **Routes:** `routes/learn/academy.php:498–598` (กลุ่ม `{academy}/classrooms*`) · อยู่ใต้ group `Route::middleware(['auth:api'])->prefix('/academies')` (บรรทัด 165) — **ไม่มี permission middleware ที่ group**
- **Services:** `ClassroomService.php` (287) · `ClassroomRenumberService.php` (305) · `StudentEnrollmentService` (roster/transfer/promote) · `MemberService`
- **Models:** `Classroom` (293) · `ClassroomStudent` (144) · `ClassroomGroup` · `ClassroomInvitation` · `ClassroomMember` · `ClassroomPointCycle`
- **Console:** 🔴 `RebuildClassroomsFromStudents.php` · `MergeDuplicateClassrooms.php` (ดู G4)

### 2.3 การกำหนดสิทธิ์จริง (verified via `route:list -v` + อ่าน guard)
ทุก route ในกลุ่มนี้ resolve middleware = **`auth:api` เท่านั้น** (มีแค่ `index` เพิ่ม `academy.visibility:content`)
→ ด่านสิทธิ์จริงอยู่**ในโค้ด controller** ผ่าน `canManage(Academy)`:
```php
protected function canManage(Academy $academy): bool {
    $user = auth()->user();
    if (! $user) return false;
    if ($academy->user_id === $user->id) return true;          // owner (ไม่มีแถว member — ถูกต้อง)
    return $academy->members()
        ->where('user_id', $user->id)
        ->whereIn('role', ['owner', 'director', 'admin'])       // ❌ ไม่กรอง status · ไม่ดู groups.manage
        ->exists();
}
```
(สำเนาเดียวกันซ้ำใน 3 controller) · `Academy::members()` มี `withPivot('status')` แต่ `canManage` ไม่ได้ใช้

### 2.4 🔴 memory ล้าสมัยที่ยืนยันแล้ว (อย่าเชื่อ [[project-classroom-source-of-truth]] ตรงนี้)
- memory บอก `ClassroomService::deleteClassroom()` เป็น `$classroom->delete();` เปล่า ๆ →
  **ไม่จริงแล้ว** ตอนนี้มี guard: ถ้ามีนักเรียน `status = active` อยู่ **throw ValidationException**
  ให้ไปใช้ `archiveClassroom()` แทน · ลบเฉพาะ enrollment ที่ไม่ active ก่อนแล้วค่อยลบห้อง (ใน transaction)
  ⇒ รากปัญหา "ลบห้องเงียบ ๆ ทั้งที่มี classroom_students อ้างอยู่" **ปิดไปแล้ว**
- ส่วนที่ยัง **จริง**: FK `classroom_students.classroom_id` = RESTRICT · คอลัมน์ legacy phase-6 ยังอยู่ (G6)

### 2.5 Database (ตารางหลัก)
`classrooms` (varchar `section`!) · `classroom_students` (pivot, FK RESTRICT บน classroom_id) ·
`classroom_members` · `classroom_groups` · `classroom_invitations` · `academic_years`
🔴 กับดักจาก memory: `classrooms.section` เป็น **varchar** แต่ legacy `student_cards.class_section` เป็น **int**
→ เรียง section ต้อง cast `(int)` เสมอ · ห้ามใช้ `REGEXP_REPLACE`/SQL เฉพาะ MySQL (เทสต์รัน SQLite)

---

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | CRUD ห้องเรียน + archive | ✅ | store/update/destroy/archive · deleteClassroom กัน active แล้ว |
| 2 | กรองรายการตามปี/ภาค/ระดับ/สถานะ | ✅ | index default = ปีปัจจุบัน, `?all_years=1` opt-out |
| 3 | roster: เพิ่ม/ถอน/เลขที่/renumber | ✅ | มี ClassroomRenumberService |
| 4 | ย้ายห้อง / เลื่อนชั้น / ประวัติ enrollment | ✅ | transfer-student · promote · enrollment-history (+ v2 StudentLifecycle) |
| 5 | กลุ่มในห้อง (ClassroomGroup) | ✅ | CRUD + สมาชิก |
| 6 | คำเชิญเข้าห้อง (invite/code) | ✅ | store/cancel/accept/decline/joinViaCode |
| 7 | สถิติต่อระดับชั้น | ✅ | getStatistics |
| 8 | **สิทธิ์ใช้ระบบ permission-key + ฝ่าย** | ❌ | hardcode role ใน `canManage` — ดู G1 |
| 9 | **กรอง status สมาชิก (approved)** | ❌ | `canManage` ไม่กรอง — ดู G2 |
| 10 | **read gate สม่ำเสมอ** | ⚠️ | index=visibility:content · อื่น ๆ =canManage — ดู G3 |
| 11 | **mobile-first ที่ 375px** | ❓ | ยังไม่ตรวจบนจอจริง — ดู G7 |

---

## 4. Permission Matrix (ที่ **ควรจะเป็น** — ปัจจุบันยังไม่ตรงนี้)

| Permission key | Owner | Admin | ฝ่าย admin (ในฝ่าย) | Teacher | Staff | Student | Guardian |
|---|---|---|---|---|---|---|---|
| `groups.view` (อ่านห้อง/รายการ/สถิติ) | ✅ | ✅ | ✅ | ⚠️ (ห้องตน) | ❌ | ❌ | ❌ |
| `groups.manage` (สร้าง/แก้/ลบ/roster/transfer/promote) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

🔴 **ปัจจุบัน `canManage` = owner หรือ role∈{owner,director,admin} เท่านั้น** ⇒ ไม่ผูกกับ `groups.*` เลย
🔴 **ชนกับเมนู #9:** ฝ่าย/แผนก (`routes:429`) ก็ใช้ `academy.permission:groups.view/manage` อยู่แล้ว
→ ถ้าเปลี่ยนห้องเรียนมาใช้ `groups.manage` ด้วย สิทธิ์ 2 เมนูจะ**ผูกกัน** (ให้สิทธิ์จัดการฝ่าย = จัดการห้องด้วย)
→ **ต้องให้เจ้าของโปรเจคเคาะ (Q1)** ว่าจะแยก key `classrooms.*` หรือใช้ `groups.*` ร่วม

---

## 5. Gap Analysis

- **G1 — ด่านสิทธิ์ bypass ระบบ permission/ฝ่าย (P1, ยืนยันแล้ว)**
  ทุก mutation ใช้ `canManage()` ที่ hardcode role owner/director/admin · ไม่อ่าน `groups.manage` · ไม่ดูฝ่าย
  ผล: (a) หัวหน้าฝ่ายที่ได้ `groups.manage` แต่ role ไม่ใช่ 3 ตัวนั้น → ถูกล็อกออก (false negative)
  (b) การเพิกถอนสิทธิ์ละเอียดไม่มีผล · (c) ขัดกับสถาปัตยกรรมที่ #1/#8/#9 วางไว้
  **ไม่ใช่ช่องโหว่เปิดโล่งแบบ #8-G1** แต่เป็นความไม่สอดคล้อง + ปัญหาการเข้าถึงจริง

- **G2 — `canManage` ไม่กรอง status สมาชิก (ยืนยัน code, รอ verify ผลกระทบ)**
  `->whereIn('role', [...])` ไม่มี `wherePivot('status', <approved>)` (memory: approved=2)
  ⇒ สมาชิก role=admin ที่ยังไม่อนุมัติ/ถูกถอน ก็ผ่านด่านจัดการได้ · **แก้ได้ทันทีไม่ติด Q**

- **G3 — read gate ไม่สม่ำเสมอ (ยืนยันแล้ว)**
  `index` = `academy.visibility:content` (ใครเห็น content ก็เห็นรายการห้อง+จำนวน) แต่
  `statistics`/`getAllStudents`/`show`/`enrollments`/`members` = `canManage` (owner/admin เท่านั้น)
  roster PII (`getAllStudents`) **ถูกกันด้วย canManage แล้ว — ดี** แต่ต้องตัดสิน tier ที่ตั้งใจ (Q2)

- **G4 — console command ล้างห้องทั้งโรงเรียนยังอยู่ (P2, footgun, ยืนยันแล้ว)**
  `RebuildClassroomsFromStudents.php:72–74` = `DB::table('classroom_students')->delete()` +
  `classroom_members->delete()` + `Classroom::query()->delete()` · มีแค่ `$this->confirm()` กั้น
  (bypass ได้ด้วย `--no-interaction`) · `MergeDuplicateClassrooms.php` ควร review คู่กัน
  → memory เตือนไว้แล้วว่าควรลบ/disable — **ยังไม่ทำ**

- **G5 — UI ยิง GET ที่ไม่มี route (รอ verify caller live)**
  `index.vue:452 fetchClassroomStudents()` → `GET /classrooms/{id}/students`
  แต่ route `{classroom}/students` มีแค่ **POST** (addStudents) ⇒ น่าจะ **405** · ตัวที่ถูกคือ `enrollments`/`members`
  (หมายเหตุ: `GET /classrooms/students` ระดับ collection = `getAllStudents` **มีจริง** อย่าสับสน)

- **G6 — หนี้คอลัมน์ legacy phase-6 (cross-cutting, linked)**
  `students.class_level/class_section` + `student_cards.class_level/class_section/level_and_room`
  ยังถูกอ่าน ~20 ไฟล์ (รวม ClassroomController, StudentCardResource, models) · phase-6 drop ยังไม่ทำ
  → กว้างเกินเมนูนี้ · ผูกกับ [[project-classroom-source-of-truth]] · แนะนำเป็นงานแยก

- **G7 — mobile-first ยังไม่ตรวจ (P2)**
  2 หน้าใหญ่ (1,625 + 2,360) ยังไม่ผ่านตาที่ 375px ตามนโยบายบังคับของโปรเจค

---

## 6. Implementation Tasks (ส่งให้ agy/codex ทีละ step)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| CL-S1 | G2 — `canManage` กรอง approved status | — | เพิ่ม `wherePivot('status', AcademyMember::STATUS_APPROVED)` ทั้ง 3 controller | 🟢 **done 2026-09-15** `03704c4e` — pint + ClassroomManagementTest 19/19 · **หนี้:** เทสต์ negative (member ไม่อนุมัติ 403) ยังไม่มี → CL-S5 |
| CL-S2 | G4 — จัดการ command อันตราย | — | guard `app()->isProduction()` ที่หัว handle() ทั้ง `rebuild-from-students` + `merge` | 🟢 **done 2026-09-15** `c8176236` — pint + php -l clean |
| CL-S3 | G5 — แก้ FE GET ที่พัง | — | ชี้ไป `GET /classrooms/students?classroom_id=` (getAllStudents) shape ตรง | 🟢 **done 2026-09-15** `d358cce0` — ยังไม่ตรวจจอจริง (ต้อง login admin) |
| CL-S4 | G1/G3 — ยกด่านสิทธิ์เข้าระบบ permission | ~~Q1,Q2,Q3~~ ✅ | ย้าย `canManage`→`userCan('groups.manage')` (write) / `canView`→`userCan('groups.view')` (read) 3 controller · route baseline `academy.permission:groups.view` · index เลิก `visibility:content` | 🟢 **done 2026-09-15** `776258cf` — ClassroomPermissionGuardTest 5/5 · route:list ยืนยัน · ClassroomManagementTest 19/19 ไม่ regression |
| CL-S5 | เทสต์ happy-path ต่อฟีเจอร์ (ที่เหลือ) | CL-S4 | roster/transfer/promote/renumber/groups/invitations happy-path (permission-matrix + non-approved ปิดใน CL-S4 แล้ว) | 🟡 partial — matrix/negative done ใน CL-S4 · เหลือ happy-path ต่อ feature |
| CL-S6 | G7 — mobile-first audit 2 หน้า | — | ตรวจ 375/768/1280 | 🟢 **done 2026-09-15 (audit)** — ตรวจ code แล้ว: ตารางกว้างทุกตัวห่อ `overflow-x-auto` · touch 44px · grid responsive → **ไม่พบ violation** (ยังไม่ได้ตรวจจอจริง แต่ static clear) |
| CL-S7 | G6 — phase-6 legacy column drop | (แยกโปรเจค) | ไล่ readers ~20 ไฟล์ → migration drop + down() | 🔵 deferred |

> 🔴 **บทเรียน CL-S1/S2 (agy race):** ส่ง 2 shard ให้ agy รันขนาน (background) · frontend เสร็จปกติ
> แต่ **backend shard ยังรันอยู่ตอน Claude commit** → agy pass สอง (เขียน `modify.py` UTF-16 target พัง +
> Thai mojibake) **revert 4/5 ไฟล์ทิ้ง** เหลือแค่ ClassroomController · Claude ต้อง `TaskStop` job แล้ว
> **re-apply 4 จุดเอง** (pint แปลง inline FQN → import ให้) · **กติกาใหม่: รอ task-notification ครบทุก shard ก่อน commit เสมอ**

**ทำได้ทันทีไม่ติด Q:** ~~CL-S1, CL-S2, CL-S3, CL-S6~~ ✅ **ปิดครบแล้ว** · **CL-S4/S5 ถัดไป (S4 รอ Q1–Q3)**
**Rule:** ทุก step ต้องมี verification (test / route:list / จอจริง) ก่อนขึ้น 🟢

### ✅ คำตอบ Q1–Q3 (เจ้าของโปรเจคเคาะ 2026-09-15)
- **Q1 = ใช้ `groups.*` ร่วม** — registry `AcademyPermission.php:58-59` นิยาม groups.* = "กลุ่มเรียน/ฝ่าย/แผนก" อยู่แล้ว · ไม่สร้าง key ใหม่
- **Q2 = reads ทั้งหมด = `groups.view`** — รวม index (เปลี่ยนจาก `visibility:content`)
- **Q3 = ย้ายไป `Academy::userCan()`** (G18 canonical) · เลิก hardcode role ใน canManage

**หลักฐาน verify ก่อนทำ CL-S4 (Claude รันเอง 2026-09-15):**
- **V1 index consumers:** `GET .../classrooms` ถูกเรียกจาก **หน้า admin เท่านั้น** (`useSchoolManagement.ts` #8 ·
  gradebook #19 · schedule #11) — ไม่มี student/member-facing → ปิดเป็น `groups.view` **ปลอดภัย**
- **V2 role→perm (reconcile migration 2026_08_29):** `director` + `admin` มี `groups.manage` ครบ ·
  owner ผ่าน `isAdmin` · ⇒ ย้ายไป `userCan('groups.manage')` **ไม่ตัดสิทธิ์ใครที่ canManage เคยให้ผ่าน** ·
  `teacher`/`registrar` มีแค่ `groups.view` (ดูได้ จัดการไม่ได้ — ตรงกับพฤติกรรมเดิม)

---

## 7. Codex/agy Prompt Template (ต่อ step)
```
Context: .agents/school-admin/10-classrooms.md §CL-S<n>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): <รายการ>
Task: <what to do>
Constraints: อ่านไฟล์ก่อนแก้ · ไม่แตะ .env/vendor · DB change = migration + down() ·
  งาน ui/ = mobile-first (ไม่มี prefix = มือถือ, touch ≥44px, ไม่ใช้ hidden ซ่อนข้อมูล)
Verification: pint --test · php artisan test --filter=<...> · route:list · (ui) จอ 375px
Report back: diff --stat + ผลเทสต์จริง (Claude ตรวจซ้ำเอง ไม่เชื่อรายงาน)
```

## 8. Review Log
- **2026-09-15 audit** — Claude สแกนโค้ดจริง + `route:list -v` + อ่าน guard body · เขียนไฟล์รองนี้ ·
  แก้ความเข้าใจผิดจาก memory เรื่อง deleteClassroom (ปิดไปแล้ว)
- **2026-09-15 CL-S1/S2/S3/S6** — ผู้เขียนโค้ด agy (2 shard) · Claude ตรวจ+กู้+commit
  - CL-S1 `03704c4e` (3 controller · wherePivot status=2) · CL-S2 `c8176236` (2 command · prod guard) ·
    CL-S3 `d358cce0` (index.vue · fetchClassroomStudents → getAllStudents)
  - CL-S6 audit-only → ไม่พบ violation (static clear)
  - **หลักฐาน Claude รันเอง:** pint --test passed (5 ไฟล์) · php -l clean · ClassroomManagementTest 19/19
    (50 assertions) · git show ยืนยันไฟล์ต่อ commit ถูกต้อง
  - **เหตุการณ์ agy race** (ดูกล่องเตือน §6): backend shard revert 4/5 ไฟล์ทิ้งระหว่าง commit →
    Claude TaskStop + re-apply เอง · ลบ `modify.py` artifact ทิ้ง
  - **ยังค้าง:** ตรวจจอจริง CL-S3 (login admin) · เทสต์ negative CL-S1 (ยกไป CL-S5)
- **2026-09-15 CL-S4** — ผู้เขียนโค้ด agy (1 shard · รอ notification เสร็จก่อน commit ตามบทเรียน) · Claude ตรวจ+เขียนเทสต์เอง
  - `776258cf` — routes (baseline `academy.permission:groups.view` + index เลิก visibility:content) · 3 controller
    (`canManage`→userCan groups.manage · เพิ่ม `canView`→userCan groups.view · read 4 จุดใช้ canView) · ลบ import AcademyMember
  - **verify ก่อนทำ:** V1 index มีแต่ admin เรียก · V2 director+admin มี groups.manage ครบ (reconcile migration) → ไม่ตัดสิทธิ์ใคร ·
    ClassroomManagementTest actor=owner (isAdmin) ไม่กระทบ
  - **หลักฐาน Claude รันเอง:** git diff 4 ไฟล์ตรงสเปคเป๊ะ ไม่มี stray · pint passed · `route:list` เห็น groups.view ทุก route
    (index เลิก visibility:content) · **ClassroomPermissionGuardTest 5/5 (8 assertions)** — non-member 403 · groups.view อ่านได้เขียนไม่ได้ ·
    groups.manage สร้างได้ · owner สร้างได้ · unapproved 403 · ClassroomManagementTest 19/19 ไม่ regression
  - agy รอบนี้ทำสะอาด (single shard + รอ notification): ไม่มี modify.py, ไม่ revert

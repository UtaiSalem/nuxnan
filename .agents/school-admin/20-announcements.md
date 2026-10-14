# 20 — ประกาศ (Announcements)

> ไฟล์รองของเมนู `20 ประกาศ` (`admin/announcements.vue`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> **สถานะ:** 🟢 ปิด gap ครบ 2026-10-13 (AN-S1 + AN-S2) — เมนูสุก · tenant+authz ครบ (ไม่มี P0) · G1 (userCan) + G3 (scope validate) + G2 (ครูสร้างประกาศห้องตนผ่าน `create.own`) + เทสต์ · เหลือเจ้าของ verify (MySQL + build)

## 1. Scope & Purpose

ประกาศของโรงเรียน — งานของ **ฝ่ายบริหาร/ประชาสัมพันธ์** · รองรับ **scope** (ทั้งโรงเรียน/ฝ่าย/ห้องเรียน) + **targeting** (roles/grades/departments) + pin + กำหนดวันหมดอายุ + นับยอดอ่าน/ยอดเปิด

## 2. Current State (จากการสแกนโค้ดจริง)

### Backend ✅
- **Controller:** `Api/Learn/Academy/AnnouncementController.php` (489) · **Model:** `SchoolAnnouncement` + `AnnouncementRead`
- **Routes:** `routes/learn/academy.php` §`{academy}/announcements` ภายใต้ `['academy.visibility:content', 'academy.permission']` (member)
  - reads: `index`/`show` = member (+ visibility)
  - writes: `store`/`update`/`publish`/`unpublish`/`destroy`/`stats` = `academy.permission:announcements.manage`
- **Authz ในคอนโทรลเลอร์:**
  - ✅ tenant check ทุก write + show (`announcement.academy_id !== academy.id → 404`)
  - ✅ `index` มี authz อ่านตาม scope (department→สมาชิกฝ่าย · classroom→ครูประจำชั้น/นักเรียนในห้อง)
  - ✅ unpublished เห็นเฉพาะผู้จัดการ
  - 🔧 `isAcademyAdmin` เดิม = `$academy->isAdmin()` (owner/academy_admins เท่านั้น) → **ขัดกับ route** `announcements.manage` (G1 — แก้แล้ว)

### Frontend
- Page: `admin/announcements.vue` · widgets: `SchoolPinnedAnnouncement.vue` · `AcademyAnnouncementWidget.vue`
- nav `admin.vue:220` → `can('announcements.view') || can('announcements.manage')` ✅ **ถูกแล้ว** (ใช้คีย์ announcements ไม่ใช่ academy.view)

### Tests
- ❌ เดิมไม่มี · **เพิ่ม `AnnouncementAuthzTest` (AN-S1) 5 เคส**

## 3. Permission Matrix

Keys: `announcements.view` · `announcements.create` · `announcements.create.own` · `announcements.manage`

| key | Owner | Admin | Director | Teacher | Student/Guardian |
|---|---|---|---|---|---|
| `announcements.view` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `announcements.create` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `announcements.create.own` | — | — | — | ✅ | ❌ |
| `announcements.manage` | ✅ | ✅ | ✅ | ❌ | ❌ |

🔴 **ปัญหา:** `announcements.create` + `announcements.create.own` **ไม่มี route/logic ไหนใช้** — ทุก write ติด `announcements.manage` ⇒ ครู (ถือ `create.own`) สร้างประกาศไม่ได้เลย (G2)

## 4. Gap Analysis (ไม่มี P0 — tenant+authz ครบ)

- **G1 (consistency, P2) — ✅ แก้แล้ว (AN-S1):** controller `isAcademyAdmin` เดิม = `$academy->isAdmin()` (owner/academy_admins) ขัดกับ route `announcements.manage` (ผ่าน `userCan`) ⇒ director/custom role ที่ถือ `announcements.manage` ผ่าน route แต่โดน controller 403 · **แก้เป็น `userCan('announcements.manage')`** (userCan ครอบ isAdmin อยู่แล้ว · ไม่มีใครเสียสิทธิ์)
- **G2 (feature/orphan key, P2) — ✅ แก้แล้ว (AN-S2):** เปิดให้ครู (`announcements.create.own`) สร้าง/แก้/เผยแพร่/ลบประกาศ **เฉพาะห้องที่ตนเป็นครูประจำชั้น** · route writes รับ `create.own,create,manage` · controller บังคับ scope=classroom + `homeroom_teacher_id===user` (create) และ created_by===user สำหรับ modify · FE: ครูเข้าหน้าได้ + เลือกห้องตน + เห็นปุ่มจัดการเฉพาะประกาศของตน
- **G3 (integrity, P3) — ✅ แก้แล้ว (AN-S1):** `store` เดิมไม่ validate `scope_id` ว่าอยู่ในโรงเรียน (department/classroom) · **เพิ่ม `validateScopeBelongsToAcademy`** → 422 ถ้า scope_id ไม่อยู่ใน academy
- **test gap — ✅ ปิด (AN-S1):** เพิ่ม `AnnouncementAuthzTest`

## 5. Implementation Tasks

| Step | Title | Deliverable | Status |
|---|---|---|---|
| AN-S1 | G1 `isAcademyAdmin`→`userCan('announcements.manage')` · G3 `validateScopeBelongsToAcademy` · เทสต์ | controller + `AnnouncementAuthzTest` 5 เคส | 🟢 **done (owner verify MySQL)** |
| AN-S2 | **G2:** เปิดครูสร้าง/จัดการประกาศห้องตน ผ่าน `announcements.create.own` | route+authz+scope guard+FE picker+เทสต์ | 🟢 **done (owner verify MySQL+build)** |

## 7. Review Log
- **2026-10-13 (audit + AN-S1)** — เมนูสุก · routes gated (member + writes manage) · tenant check ทุก write+show · index มี scope-authz · nav ถูก · ไม่มี P0 · **แก้ G1** (controller `isAdmin`→`userCan('announcements.manage')` · เดิม director ที่ถือคีย์โดน 403) + **G3** (validate scope_id อยู่ในโรงเรียน) + เทสต์ `AnnouncementAuthzTest` 5 เคส
- **2026-10-13 (AN-S2 — เจ้าของเคาะ "เปิดเลย")** — เปิดให้ครูสร้าง/จัดการประกาศห้องตน ผ่าน `announcements.create.own`:
  - **BE:** route store/update/publish/unpublish/destroy รับ `announcements.create.own,announcements.create,announcements.manage` (stats คง manage) · `store` บังคับ: ถ้าไม่ใช่ผู้จัดการ ต้อง scope=classroom + `teacherOwnsClassroom` (homeroom_teacher_id===user) · เพิ่ม `canModifyAnnouncement` (ผู้จัดการ หรือ ครูเจ้าของประกาศ+ห้องตน) ใช้ใน update/publish/unpublish/destroy
  - **FE `admin/announcements.vue`:** gate ให้ครู (create.own) เข้าได้ · `canManageAll`/`canCreateOwn`/`canModifyItem` · ครูโหลดห้องที่ตนประจำชั้น (`fetchMyClassrooms`) + select ในฟอร์มสร้าง · ส่ง scope=classroom+scope_id · ปุ่มแก้/เผยแพร่/ลบ แสดงเฉพาะ `canManageAll || created_by===ตน` · stats ไม่ยิงสำหรับครู (กัน 403)
  - **เทสต์:** เพิ่มใน `AnnouncementAuthzTest` — ครูสร้างห้องตนได้ · academy-scope 403 · ห้องคนอื่น 403 · แก้ของตนได้/ลบของผู้บริหารไม่ได้
  - `php -l` ผ่านทุกไฟล์ BE · cloud ไม่มี node_modules → เจ้าของ `npm run build` + ตรวจ 375px (ครูเห็นเมนู+สร้างประกาศห้องตน)
  - **เมนู #20 ปิด gap ครบ (G1/G2/G3)** 🟢 · เจ้าของ verify: `phpunit.mysql.xml --filter=AnnouncementAuthzTest` + pint + `npm run build`

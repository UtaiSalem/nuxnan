# 20 — ประกาศ (Announcements)

> ไฟล์รองของเมนู `20 ประกาศ` (`admin/announcements.vue`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> **สถานะ:** 🟢 audit + ปิด gap ที่ตัดสินได้ 2026-10-13 (AN-S1) — เมนูสุก · tenant+authz ครบ (ไม่มี P0) · ปิด G1 (userCan) + G3 (scope validate) + เทสต์ · เหลือ G2 (feature `create.own`) รอเจ้าของเคาะ

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
- **G2 (feature/orphan key, P2) — ⏸️ รอเคาะ:** `announcements.create`/`create.own` เป็น orphan · ครู (ถือ `create.own`) ควรสร้างประกาศ **เฉพาะห้อง/วิชาของตน** ได้ แต่ตอนนี้ store ติด `.manage` ⇒ ทำไม่ได้ · การเปิดต้อง: route store รับ `create`/`create.own` + บังคับ scope ให้ตรงห้องที่ครูรับผิดชอบ (homeroom/วิชา) — เป็น feature ต้องออกแบบ
- **G3 (integrity, P3) — ✅ แก้แล้ว (AN-S1):** `store` เดิมไม่ validate `scope_id` ว่าอยู่ในโรงเรียน (department/classroom) · **เพิ่ม `validateScopeBelongsToAcademy`** → 422 ถ้า scope_id ไม่อยู่ใน academy
- **test gap — ✅ ปิด (AN-S1):** เพิ่ม `AnnouncementAuthzTest`

## 5. Implementation Tasks

| Step | Title | Deliverable | Status |
|---|---|---|---|
| AN-S1 | G1 `isAcademyAdmin`→`userCan('announcements.manage')` · G3 `validateScopeBelongsToAcademy` · เทสต์ | controller + `AnnouncementAuthzTest` 5 เคส | 🟢 **done (owner verify MySQL)** |
| AN-S2 | **G2 (ขึ้นกับ Q):** เปิดครูสร้างประกาศห้อง/วิชาตน ผ่าน `announcements.create.own` + บังคับ scope=classroom ของ homeroom ตน | route+authz+scope guard+เทสต์ | ⚪ pending (รอเคาะ) |

## 6. คำถามที่ต้องเคาะ (Q)
- **Q1 — G2:** เปิดให้ครูสร้างประกาศ **เฉพาะห้อง/วิชาที่ตนรับผิดชอบ** ผ่านคีย์ `announcements.create.own` ไหม? (ตอนนี้สร้างได้เฉพาะผู้ถือ `announcements.manage`) · ถ้าเปิด: ครูเลือก scope=classroom ได้เฉพาะห้องที่เป็นครูประจำชั้น/สอน · ถ้ายังไม่เปิด: `create`/`create.own` คงเป็น key สำรองไว้ก่อน

## 7. Review Log
- **2026-10-13 (audit + AN-S1)** — เมนูสุก · routes gated (member + writes manage) · tenant check ทุก write+show · index มี scope-authz · nav ถูก · ไม่มี P0 · **แก้ G1** (controller `isAdmin`→`userCan('announcements.manage')` ให้ตรง route · เดิม director ที่ถือคีย์โดน 403) + **G3** (validate scope_id อยู่ในโรงเรียน) + เทสต์ `AnnouncementAuthzTest` 5 เคส (custom role manage สร้างได้ · view-only 403 · non-member 403 · cross-academy show 404 · scope_id นอกโรงเรียน 422) · `php -l` ผ่าน · เจ้าของ verify `--filter=AnnouncementAuthzTest` + pint · **เหลือ G2 (create.own ให้ครู) รอเจ้าของเคาะ Q1**

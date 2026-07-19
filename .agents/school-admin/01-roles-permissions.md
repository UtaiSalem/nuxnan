# 01 — บทบาทและสิทธิ์ (Roles & Permissions)

> ไฟล์รองของเมนู **บทบาทและสิทธิ์** ใน [OVERVIEW.md](OVERVIEW.md)
> เป็นรากของทุกเมนู — ทุกฟีเจอร์ในโรงเรียนต้องพึ่ง permission key ที่กำหนดที่นี่

---

## 1. Scope & Purpose

- นิยาม **บทบาท (roles)** — ทั้ง system roles (owner/admin/teacher/…) และ custom roles ที่โรงเรียนสร้างเอง
- นิยาม **สิทธิ์ (permissions)** — key รูปแบบ `<domain>.<action>[.scope]` ที่ทุกเมนูใช้ตรวจสอบ
- ผูกสิทธิ์เข้ากับบทบาท (role → permissions[])
- ผูกสมาชิกเข้ากับบทบาท (member.academy_role_id)
- เตรียมพื้นฐานสำหรับ **department-scoped admin** (admin ของฝ่าย/แผนก มีสิทธิ์เต็มเฉพาะฝ่ายตนเอง — ยังไม่มีในระบบ)

**ผู้ใช้:** owner, admin, custom "role manager"
**สิทธิ์หลัก:** `members.roles.manage` (backend key) / `roles.manage` (frontend UI key — ⚠️ mismatch, ดู G4)

---

## 2. Current State (สแกนโค้ดจริง)

### Frontend
- Page: [ui/pages/academies/[name]/admin/roles.vue](../../ui/pages/academies/%5Bname%5D/admin/roles.vue) — 606 บรรทัด
  - Layout: `main` (⚠️ ไม่ได้ใช้ `academy-admin` — ดู G8)
  - Hard-codes `permissionGroups` (8 groups, ~20 permissions) แทนที่จะดึงจาก API
  - Hard-codes `systemRoles = ['owner','director','admin','teacher','staff','finance_staff','student','parent']` — **ขาด** `registrar`, `card_admin`, `guest` ที่ backend มี
  - CRUD ครบ (list / create / edit / delete) + modal
- Composable: [ui/composables/useAcademyRole.ts](../../ui/composables/useAcademyRole.ts)
  - Client-side `can(permission)` มี hierarchical check ('a.b' grants 'a.b.c')
  - Fetch `/api/academies/{id}/my-role`
  - ไม่มี invalidation / refresh หลัง role assignment
- Layout: [ui/layouts/academy-admin.vue](../../ui/layouts/academy-admin.vue) ใช้ `can('roles.view')` แต่ backend คีย์คือ `members.roles.manage` (G4)

### Backend
- Controller: [AcademyRoleController.php](../../api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyRoleController.php)
- Routes: [routes/learn/academy.php:236-241](../../api/nuxnanravel/routes/learn/academy.php)
  - `GET /academies/{academy}/roles`
  - `GET /academies/{academy}/roles/available` ⚠️ controller มี แต่ไม่มี route (จริง ๆ ยังไม่พบ — verify ตอน S1)
  - `GET /academies/{academy}/my-role`
  - `POST /academies/{academy}/roles`
  - `PUT/DELETE /academies/{academy}/roles/{role}`
  - ❌ **ไม่มี** route `permissions` (controller มี method `permissions()` แต่ไม่มี route wire ไว้ → frontend ดึงสิทธิ์กลาง ๆ ไม่ได้)
  - ❌ **ไม่มี** route `assignRole` / `bulkAssignRole` (มีใน controller แต่ไม่มี route)
- Models:
  - [AcademyRole.php](../../api/nuxnanravel/app/Models/AcademyRole.php) — มี `SYSTEM_ROLES` constant 10 roles พร้อม default permissions
  - [AcademyPermission.php](../../api/nuxnanravel/app/Models/AcademyPermission.php) — มี `PERMISSIONS` constant 17 groups
  - `hasPermission()` มี wildcard `*` + hierarchical check
- Middleware: `academy.permission` — ต้อง verify ว่าใช้กับ route ทุก endpoint ที่ควรตรวจ (audit ตอน S1)

### Database
- `academy_roles` (id, academy_id?, name, display_name_th/en, description, permissions:json, is_system, is_active, sort_order, color, icon)
- `academy_permissions` (seed table — verify ตอน S1 ว่ามีจริงหรือใช้แค่ const)
- `academy_members.academy_role_id` (FK) + legacy column `academy_members.role` (string)

---

## 3. Feature Checklist (ควรมี vs มี)

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | ดูรายการ system roles + custom roles | ✅ | แสดง 2 กลุ่มแยกกัน |
| 2 | สร้าง custom role | ✅ | + icon + color + description |
| 3 | แก้ไข custom role (name/permissions/color/icon) | ✅ | name เปลี่ยนไม่ได้หลังสร้าง |
| 4 | ลบ custom role | ✅ | บล็อกถ้ามีสมาชิกใช้อยู่ |
| 5 | Assign role ให้สมาชิก | ⚠️ | controller มี method แต่**ไม่มี route** → หน้า members อาจใช้ endpoint อื่น (audit) |
| 6 | Bulk assign role | ⚠️ | เหมือน 5 |
| 7 | ดู permissions ทั้งหมด (frontend fetch จาก API) | ❌ | Hard-coded ใน page (drift) |
| 8 | แสดงจำนวนสมาชิกในแต่ละ role | ⚠️ | Backend `withCount('members')` แต่ frontend ไม่แสดง |
| 9 | Filter/search roles | ❌ | ยังไม่มี |
| 10 | Duplicate role (clone) | ❌ | ยังไม่มี — คุณค่าสูงสำหรับสร้าง custom role คล้าย system |
| 11 | Preview: role นี้เห็นเมนู/ปุ่มอะไรบ้าง | ❌ | ช่วย verify permissions |
| 12 | Audit log (ใครแก้ role/permission เมื่อไหร่) | ❌ | ต้องผูกกับเมนู #22 activity-log |
| 13 | **Department-scoped role** (admin ของฝ่าย) | ❌ | Gap หลักตาม requirement |
| 14 | Permission inheritance / group | ⚠️ | Backend มี hierarchical check แล้ว แต่ไม่มี UI ให้เห็น |
| 15 | Reset custom role → default | ❌ | สำหรับ system role ที่ถูก override (ยัง edit ไม่ได้ตอนนี้) |
| 16 | ป้องกัน owner ลบสิทธิ์ตัวเอง / demote ตัวเอง | ⚠️ | Controller ยังไม่ check |

---

## 4. Permission Matrix (ที่ควรจะเป็น)

| Permission key | Owner | Admin | Dept Admin | Teacher | Staff | Student | Guardian |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| `roles.view` (alias ของ `members.roles.manage`?) | ✅ | ✅ | ✅ (เฉพาะ role ในฝ่าย) | ❌ | ❌ | ❌ | ❌ |
| `members.roles.manage` | ✅ | ✅ | ✅ (เฉพาะ role scope ฝ่าย) | ❌ | ❌ | ❌ | ❌ |
| `roles.system.view` (ใหม่) | ✅ | ✅ | ✅ read-only | ❌ | ❌ | ❌ | ❌ |
| `roles.custom.create` (ใหม่) | ✅ | ✅ | ✅ (scope ฝ่าย) | ❌ | ❌ | ❌ | ❌ |

⚠️ **ต้องตัดสินใจ:** จะรวม `roles.view/manage` เป็น key เดียวกับ `members.roles.manage` หรือแยกเป็นของตัวเอง? แนะนำ **แยก** เพราะบางบทบาท (เช่น HR/นายทะเบียน) ควรจัดการบทบาทได้แต่ไม่ควรจัดการสมาชิกทั้งหมด

---

## 5. Gap Analysis

- **G1** — Route `/permissions` ไม่ได้ wire ใน `academy.php` → frontend ต้อง hard-code
- **G2** — Route `/roles/available`, `/members/{m}/role`, `/members/roles/bulk-assign` หายไปจาก routes (controller methods มีอยู่แล้ว)
- **G3** — Orphan permissions ใน `AcademyRole::SYSTEM_ROLES` ที่ **ไม่ได้** อยู่ใน `AcademyPermission::PERMISSIONS`:
  - `behavior.*` (view, record, approve, manage, view.own) — director/admin/teacher/student ใช้
  - `students.cards.request`, `students.cards.produce` (มีใน PERMISSIONS แต่กลุ่ม `students` OK — ✅)
  - Verify ให้ครบตอน S1
- **G4** — Frontend UI key vs backend key mismatch:
  - Layout ใช้ `roles.view`, `roles.manage` (ไม่มีใน backend)
  - Backend คีย์คือ `members.roles.manage`
  - Frontend `permissionGroups` ประกาศ `roles.view/manage` (สร้างขึ้นมาลอย ๆ)
  - **ต้องเลือกทางเดียว** และ align ทั้งระบบ
- **G5** — `systemRoles` list ใน `roles.vue` **ไม่ตรง** กับ `SYSTEM_ROLES` ใน model (ขาด `registrar`, `card_admin`, `guest`) → custom role ชื่อเดียวกับ system อาจ bypass การ block edit
- **G6** — `permissionGroups` (frontend) มีแค่ 8 groups / 18 keys — backend มี 17 groups / 60+ keys → drift ใหญ่มาก
- **G7** — ไม่มี frontend endpoint ให้ **assign role ให้สมาชิก** ผ่านหน้า members (ต้อง audit ตอนเข้าเมนู #2)
- **G8** — `roles.vue` ประกาศ `layout: 'main'` แต่ควรใช้ `academy-admin` เพื่อ consistency + sidebar
- **G9** — ไม่มี **department scope** — โครงสร้างปัจจุบันไม่รองรับ "admin ของฝ่ายวิชาการ vs admin ของฝ่ายกิจการนักเรียน" ที่มีสิทธิ์เต็มเฉพาะฝ่ายตนเอง (ตรงกับ vision ของ user)
- **G10** — ไม่มี audit log สำหรับการเปลี่ยน permission
- **G11** — Owner สามารถ demote / ลบสิทธิ์ตัวเองได้ (controller ไม่ block)
- **G12** — ยังไม่ทดสอบว่าตอนลบ role ที่มีสมาชิก จะ downgrade ไป student role หรือแค่ block (ข้อความ swal บอกว่าจะเปลี่ยนเป็น "สมาชิกปกติ" แต่ controller แค่ block — mismatch UX)

---

## 6. Implementation Tasks (จะส่ง codex ทีละ step)

**หลักการ:** เริ่มจาก audit → fix โครงสร้าง → เพิ่มฟีเจอร์ใหม่ → จบด้วย department scope (ที่ใหญ่สุด)

| Step | Title | Depends | Deliverable | Status |
|---|---|---|---|---|
| **S1** | **Audit + Fix drift** ระหว่าง frontend hard-code กับ backend permissions | — | รายงาน audit (frontend keys vs backend keys ที่ใช้จริง), fix G1–G6 (wire missing routes, sync permissionGroups from API, fix key alias) | ⚪ |
| S2 | Sync systemRoles list + block edit/delete ให้ครบทุก system role (G5) | S1 | Fix `systemRoles` array ใน roles.vue หรือย้ายไปเช็คจาก `role.is_system` แทน | ⚪ |
| S3 | เพิ่ม UI: จำนวนสมาชิกต่อ role + role duplicate + preview permissions (G8, features 8/10/11) | S1 | Card ใหม่/badge บนหน้า roles | ⚪ |
| S4 | Guard: owner ห้าม demote ตัวเอง + safe-delete role ที่มีสมาชิก (auto-reassign to `student` — ตรงกับ swal message) | S1 | Backend guard + test | ⚪ |
| S5 | Audit log สำหรับการเปลี่ยน role/permission (G10) | S1 | เขียน event + hook เข้าตาราง activity log (จะ align กับเมนู #22) | ⚪ |
| S6 | เปลี่ยน layout `roles.vue` จาก `main` → `academy-admin` (G8) | — | Small fix | ⚪ |
| **S7** | **Department-scoped role model** (G9) — schema + service + policy | S1–S5 | migration `academy_role_scopes` (role_id, scope_type: department/classroom, scope_id) + `hasPermissionInScope()` + docs | ⚪ |
| S8 | UI: ผูก role กับ department ที่หน้าจัดการฝ่าย (จะทำจริงในเมนู #9) | S7 | เตรียม hook | ⚪ |

**ลำดับส่งงาน codex:** S1 → S2 → S6 → S3 → S4 → S5 → S7 → S8

---

## 7. Codex Prompt Template — Step 1

```
Context: .agents/school-admin/01-roles-permissions.md §S1 (Audit + Fix drift)
Working dir: C:\wamp64\www\nuxnan
Goal: ทำให้ frontend permission list ตรงกับ backend และ wire routes ที่ขาดหาย

Investigation phase (report only, no code yet):
1. เปิด grep ทั้ง ui/ หา literal permission keys ทุกแบบ (grep pattern:
   can\(['"][a-z_.]+['"]\) และ hasPermission\(['"][a-z_.]+['"]\))
2. เปิด grep ทั้ง api/nuxnanravel/app หา literal permission keys (grep pattern:
   ->hasPermission\(['"][a-z_.]+['"]\) และ ในไฟล์ policy/middleware)
3. รวบรวมทุก key ที่ frontend ใช้ vs ทุก key ที่ backend รู้จัก (จาก AcademyPermission::PERMISSIONS + SYSTEM_ROLES)
4. ระบุ key ที่ frontend ใช้แต่ backend ไม่รู้จัก (mismatch)
5. ระบุ key ที่ backend เอาไปใช้ใน SYSTEM_ROLES แต่ไม่มีใน PERMISSIONS (orphan)

Deliverable ของ investigation: ตารางในไฟล์ .agents/school-admin/S1-audit-report.md

Fix phase (หลัง Claude review รายงาน):
- คุยกับ Claude เพื่อยืนยัน key ที่ต้อง add / rename / alias
- Wire routes ที่ขาด: permissions(), roles/available, assignRole, bulkAssignRole (ใน routes/learn/academy.php)
- แก้ roles.vue: ดึง permissionGroups จาก endpoint แทน hard-code (loading state)
- Add missing permissions ใน AcademyPermission::PERMISSIONS (behavior.*)
- Migration seed table academy_permissions ถ้ายังใช้งานจริง (verify)
- รัน tests + Pint

Constraints:
- ห้ามลบ permission key ใด ๆ ที่ยังมีการ reference (แค่ deprecate ด้วย alias)
- ห้ามแตะ system role default permissions โดยไม่ confirm กับ Claude ก่อน
- ต้อง Laravel Pint pass และ npm run build pass

Verification:
- `php artisan test --filter=Role` ต้องผ่านทุก test
- `npm run build` ต้อง exit 0
- Manual: เข้าหน้า /academies/{name}/admin/roles ต้องยังใช้งานได้เหมือนเดิม (regression)

Report back:
- ตาราง audit
- รายการ fix ที่ทำจริง
- ผล test / build / manual
- ค้าง / decision ที่ต้องคุย
```

---

## 8. Review Log

### 2026-07-19 · S1 investigation — codex `task-mrrag7bp-qa0o5v` (9m48s)

**Output:** [S1-audit-report.md](S1-audit-report.md) (โดน codex เขียนผิดที่ก่อน แล้ว claude ย้ายมา)

**Counts:** 60 canonical / 67 FE / 27 BE / 56 role-granted (non-wildcard) · **11 FE-only orphans / 10 BE-only / 1 both / 29 unused canonical**

**Confirmed (จาก plan เดิม):**
- ✅ G3 confirmed — 6 role keys ที่ไม่มีใน `PERMISSIONS`: `behavior.view/record/approve/manage/view.own`, `children.behavior.view`
- ✅ G4 confirmed — `roles.view` ใช้ที่ [academy-admin.vue:113](../../ui/layouts/academy-admin.vue), [admin.vue:76](../../ui/pages/academies/%5Bname%5D/admin.vue), [roles.vue:118](../../ui/pages/academies/%5Bname%5D/admin/roles.vue) · `roles.manage` ที่ [roles.vue:307](../../ui/pages/academies/%5Bname%5D/admin/roles.vue) · BE ใช้ `members.roles.manage` แทน

**คำแก้/พลิก (จาก plan เดิม):**
- ❌ **G1/G2 ผิด** — ทั้ง 5 methods routed ครบแล้ว: `GET /permissions/all` (l248), `GET /{a}/roles/available` (l237), `GET /{a}/my-role` (l238), `POST /{a}/members/{m}/role` (l244), `POST /{a}/members/bulk-role` (l245) — ผมสแกนแค่ 236-241 เลยพลาด

**พบเพิ่มที่ plan ไม่ได้ระบุ (Gap ใหม่ G13–G16):**
- **G13** — Layout menu permissions หลายอันเป็น **FE-only orphans** ทั้งชุด: `groups.view`, `schedule.view`, `grades.view`, `staff.view` — สร้างขึ้นลอย ๆ ที่ layout · Backend ไม่เคยเช็ค (menu แค่ซ่อน แต่ endpoint จริงเปิด — potential IDOR)
- **G14** — `courses.manage` ที่ [admin.vue:105,109](../../ui/pages/academies/%5Bname%5D/admin.vue), `events.manage`, `manage_members` (spelling drift), `school_attendance.manage`, `payments.make` (canonical เป็น `payments.pay`) — spelling/naming drift
- **G15** — `settings.manage` = orphan **ทั้ง FE และ BE** ([roles.vue ไม่ใช้ตัวนี้] แต่ layout ที่ 263,269 + [admin.vue:219,247] + [settings.vue:69] + BE `AcademyController.php:487`) → **ไม่มี role ไหนใน SYSTEM_ROLES ให้สิทธิ์นี้เลย** → หน้า settings ปกติเข้าไม่ได้แม้กระทั่ง admin ยกเว้นผ่าน `isAdmin` bypass
- **G16** — BE-only orphans (`enrollment.*`, `student.import`, `student.intake`, `donate`, `enrollment.viewBatches`, `manage`) → คนละ namespace? หรือควรรวม? ต้องตัดสินใจ

**29 unused canonical keys** — code ไม่มีที่ไหนเช็ค — บาง key ควรลบ / บาง key ควรเริ่มใช้

---

## 9. Decisions Required (ก่อนไป S1-fix)

รอ user ตัดสินใจ ก่อนที่ codex จะเริ่ม fix (task #3):

**D1 — Naming contract:** `members.roles.manage` (BE) vs `roles.view/manage` (FE)
  - (a) เพิ่ม `roles.view`, `roles.manage` เข้า canonical + BE alias → เข้ากับ FE ที่ใช้อยู่แล้ว
  - (b) แก้ FE ให้ใช้ `members.roles.manage` ทั้งหมด → align กับ BE
  - **แนะนำ (a)** — เพราะแยก concern: จัดการบทบาท ≠ จัดการสมาชิก

**D2 — Layout menu orphans (G13):** `groups/schedule/grades/staff.view`
  - (a) เพิ่มเข้า canonical + assign ให้ system roles
  - (b) เปลี่ยนไปใช้ canonical ที่ตรง (`students.view` แทน `grades.view`?)
  - **แนะนำ (a)** — canonical set สั้นไปจริง ๆ ต้องแยก concern ตามเมนู

**D3 — `settings.manage` (G15):** จะให้ role ไหนเข้าถึงบ้าง?
  - **แนะนำ:** เพิ่มเข้า canonical + ให้กับ `owner` (auto) + `director` + `admin`

**D4 — `behavior.*` (G3):** เพิ่มเข้า canonical แน่นอน (มี role granted อยู่แล้ว) — confirm

**D5 — spelling drift (G14):** `payments.make` vs `payments.pay`, `student.import` vs `students.import`, `manage_members` vs `members.manage`
  - **แนะนำ:** rename FE ให้ตรงกับ canonical (แก้ callsite เดียว vs BE ที่มีหลายที่)

**D6 — 29 unused canonical keys:**
  - **แนะนำ:** เก็บไว้ก่อน (จะทยอยใช้เมื่อทำเมนูอื่น) — เขียน note ในไฟล์เมนูที่ควรใช้

**D7 — BE-only orphans (G16) `enrollment.*`, `student.*`, `donate`:**
  - เหล่านี้เป็น namespace แยก (Gate/Policy) ไม่ใช่ role-permission เดียวกัน → **แนะนำ:** ปล่อยไว้ก่อน แล้ว document ใน section แยกว่า "Non-role Gates" (ไม่ต้องอยู่ใน canonical role permissions)

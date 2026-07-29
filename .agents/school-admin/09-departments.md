# 09 — ฝ่าย/แผนก (Departments)

> ไฟล์รองของเมนู **#9 ฝ่าย/แผนก** ใน [OVERVIEW.md](OVERVIEW.md)
> เมนูนี้เป็น **รากของสิทธิ์ระดับฝ่าย** ที่เมนู #1 (S7/S8), #6 เฟส B, #14, #17, #18, #19 รออยู่
> วันที่สแกน: 2026-07-29

---

## 0. ข้อตกลงที่ล็อกแล้ว

| # | ประเด็น | ข้อสรุป |
|---|---|---|
| **P1** | โมเดลสิทธิ์ระดับฝ่าย | **ต่อยอด `academy_group_permissions` ที่มีอยู่** — แนวคิด "ฝ่ายนี้มีสิทธิ์อะไร" แล้วสมาชิกฝ่ายได้สิทธิ์นั้น *ภายในขอบเขตของฝ่ายตน* · **ไม่ทำ `academy_role_scopes` ตามแผน S7 เดิม** (แผนนั้นเขียนตอนยังไม่รู้ว่ามีตารางนี้อยู่แล้ว) |
| **P2** | ช่องโหว่ route | **เป็นช่องโหว่จริง ไม่ได้ตั้งใจ** → ต้องอุดเป็น step แรก (ต่างจากเคส guardians ที่เปิดไว้ชั่วคราวโดยเจตนา) |

---

## 1. Scope & Purpose

โครงสร้างองค์กรของโรงเรียน (สำนัก → ฝ่าย → งาน/กลุ่มสาระ) และ **การกระจายสิทธิ์ตามหน้าที่** ครอบคลุม:

1. **จัดการโครงสร้างฝ่าย** — สร้าง/แก้/ลบ, ลำดับชั้น `parent_id`, ประเภท (`office`/`department`/`section`/`academic_group`), ติดตั้งจากเทมเพลตมาตรฐาน
2. **สมาชิกฝ่าย** — เพิ่ม/ลบ/bulk, บทบาทในฝ่าย (หัวหน้าฝ่าย/สมาชิก)
3. **สิทธิ์ของฝ่าย** — ฝ่ายไหนรับผิดชอบงานอะไร แล้วสมาชิกฝ่ายได้สิทธิ์นั้นในขอบเขตฝ่ายตน
4. **เชื่อมกับเมนูอื่น** — เป็นตัวตัดสินว่าใครเห็น/แก้อะไรได้ในเมนู #6, #14, #17, #18, #19

---

## 2. Current State (สแกนโค้ด + DB จริง 2026-07-29)

### Frontend
- `ui/pages/academies/[name]/admin/departments/index.vue` — **1,161 บรรทัด** (รายการฝ่าย, สร้าง/แก้/ลบ, ติดตั้งเทมเพลต, จัดการสมาชิก, จัดการสิทธิ์)
- `ui/pages/academies/[name]/admin/departments/[id].vue` — 155 บรรทัด

### Backend
- `DepartmentController` — 12 endpoint: `index`, `store`, `show`, `update`, `destroy`, `getMembers`, `addMember`, `bulkAddMembers`, `removeMember`, `updateMemberRole`, `getTemplate`, `setupDepartments`, `getStatistics`
- `AcademyGroupPermissionController` + `AcademyGroupPermissionService` — อ่าน/sync สิทธิ์ต่อฝ่าย
- `SchoolDepartmentSetupService` — เทมเพลต 1 สำนัก + 5 ฝ่าย + งานย่อย
- `AcademyScopeAccessService` — ตรวจ scope `academy|department|classroom` (ใช้กับฟีด/ประกาศ/workspace เท่านั้น)
- Routes: `routes/learn/academy.php:360-382` — **ไม่มี middleware ใด ๆ นอกจาก `auth:api`**
- Models: `AcademyGroup`, `AcademyGroupMember`, `AcademyGroupPermission`, `AcademyGroupAdmin`

### Database (ของจริงบนเครื่องนี้)

| ตาราง | สภาพ |
|---|---|
| `academy_groups` | **35 แถว** — office 1 · department 5 · section 21 · academic_group 8 (เทมเพลตถูกติดตั้งแล้ว) |
| `academy_group_permissions` | **0 แถว** — ว่างเปล่าสนิท |
| `academy_group_members` | **1 แถว** — แทบไม่มีใครอยู่ในฝ่ายเลย |

→ **โครงสร้างฝ่ายมีอยู่ แต่ไม่มีคนและไม่มีสิทธิ์** ทั้งชั้นนี้เป็นโครงเปล่า

---

## 3. Feature Checklist

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | CRUD ฝ่าย + ลำดับชั้น | ✅ | ทำงานได้ |
| 2 | ติดตั้งจากเทมเพลตมาตรฐาน | ✅ | idempotent |
| 3 | จัดการสมาชิกฝ่าย + bulk | ✅ | แต่ยังไม่มีใครใช้ (1 แถว) |
| 4 | บทบาทในฝ่าย | ⚠️ | คอลัมน์ `role` เป็น varchar default `'student'` — ไม่มี enum/นิยามหัวหน้าฝ่ายที่ชัด |
| 5 | กำหนดสิทธิ์ให้ฝ่าย | ⚠️ | มี UI + API แต่ตารางว่าง และ**ไม่มีผลกับเมนูแอดมิน** |
| 6 | **สิทธิ์ฝ่ายมีผลจริงกับการเข้าถึงเมนู** | ❌ | **หัวใจของเมนูนี้ ยังไม่มี** |
| 7 | ขอบเขตข้อมูล (เห็นเฉพาะของฝ่ายตน) | ❌ | ยังไม่มีแนวคิดนี้ในระบบสิทธิ์ |
| 8 | Guard ระดับ route | ❌ | ไม่มีเลย |
| 9 | Audit log การเปลี่ยนโครงสร้าง/สมาชิกฝ่าย | ❌ | เมนู #2–#5 มีแล้ว เมนูนี้ยังไม่มี |
| 10 | หน้ารายละเอียดฝ่าย | ⚠️ | `[id].vue` 155 บรรทัด บางมากเทียบกับ index 1,161 |

---

## 4. Permission Matrix (เป้าหมาย)

| Permission key | Owner | Admin | หัวหน้าฝ่าย | สมาชิกฝ่าย | ครูทั่วไป |
|---|:-:|:-:|:-:|:-:|:-:|
| `departments.view` | ✅ | ✅ | ✅ | ✅ | ✅ (อ่านโครงสร้าง) |
| `departments.manage` (สร้าง/แก้/ลบฝ่าย) | ✅ | ✅ | ⚠️ เฉพาะฝ่ายตน | ❌ | ❌ |
| `departments.manage-members` | ✅ | ✅ | ✅ เฉพาะฝ่ายตน | ❌ | ❌ |
| `departments.permissions.manage` | ✅ | ✅ | ❌ | ❌ | ❌ |

> **หลักการ P1:** สิทธิ์ที่ผูกกับฝ่าย (เช่น `guardians.manage` ของฝ่ายกิจการนักเรียน) จะมีผล **เฉพาะกับข้อมูลในขอบเขตของฝ่ายนั้น** ไม่ใช่ทั้งโรงเรียน — ยกเว้นฝ่ายที่โดยธรรมชาติมีขอบเขตทั้งโรงเรียน (เช่น งานทะเบียน)

---

## 5. Gap Analysis

| ID | Gap | ระดับ |
|---|---|---|
| **D1** | **route ทั้ง 12 ตัวไม่มี middleware** — ผู้ใช้ที่ล็อกอินคนไหนก็ได้ (ไม่ต้องเป็นสมาชิกโรงเรียนด้วยซ้ำ) สร้าง/แก้/**ลบฝ่าย** และเพิ่ม/ลบสมาชิกฝ่ายของโรงเรียนไหนก็ได้ | 🔴 critical |
| **D2** | **`checkPermission()` ไม่ใช่การตรวจสิทธิ์ผู้ใช้** — มันถามว่า *ฝ่ายนี้เปิดใช้ฟีเจอร์นี้ไหม* ไม่ได้ถามว่า *คนที่กดมีสิทธิ์ไหม* และ `hasPermission()` **คืน `true` เมื่อไม่มีแถว** ประกอบกับตารางว่าง 0 แถว → ด่านนี้ผ่านหมดทุกคนเสมอ | 🔴 critical |
| **D3** | `CheckAcademyPermission` ไม่มองฝ่ายเลย — อ่านแค่ `$member->academyRole` ทำให้สิทธิ์ระดับฝ่ายไม่มีผลกับเมนูใด ๆ | 🔴 |
| **D4** | ไม่มีแนวคิด "ขอบเขตข้อมูลของฝ่าย" — ต่อให้ให้สิทธิ์ได้ ก็ยังจำกัดไม่ได้ว่าเห็นเฉพาะของฝ่ายตน | 🔴 |
| **D5** | `academy_group_members.role` เป็น varchar default `'student'` ไม่มีนิยามหัวหน้าฝ่าย | 🟡 |
| **D6** | สมาชิกฝ่ายมี 1 แถว / สิทธิ์ฝ่าย 0 แถว — ต้องมีทางนำคนเข้าฝ่ายจำนวนมาก (จากบุคลากรที่มีอยู่) | 🟡 |
| **D7** | ไม่มี audit log | 🟡 |
| **D8** | `[id].vue` บางมาก ยังไม่ใช่หน้าจัดการฝ่ายเชิงลึก | 🟢 |

---

## 6. Implementation Tasks

| Step | Title | Depends | Deliverable | Status |
|---|---|---|---|---|
| **D-S1** | **อุดช่องโหว่ (D1+D2)** — `academy.permission:groups.view` ครอบทั้งกลุ่ม + `groups.manage` ทุก route เขียน · เปลี่ยนชื่อ `checkPermission()` → `ensureDepartmentFeatureEnabled()` ให้ความหมายตรงกับพฤติกรรม | — | routes + controller + `DepartmentAuthorizationTest` | 🟢 **verified 2026-07-29** — 15/15 route มี guard · เทสต์ 8 ผ่าน |
| **D-S2** | **นิยามบทบาทในฝ่าย (D5)** — กำหนดค่าที่ใช้ได้ (`head`, `deputy`, `member`) + migration ปรับ default + backfill | D-S1 | migration + model + tests | ⚪ |
| **D-S3** | **ต่อสิทธิ์ฝ่ายเข้ากับ middleware (D3)** — `CheckAcademyPermission` ตรวจเพิ่ม: ถ้า role ระดับโรงเรียนไม่ผ่าน ให้ดูว่า user เป็นสมาชิกฝ่ายที่มีสิทธิ์นั้นหรือไม่ | D-S1, D-S2 | middleware + service + tests | ⚪ |
| **D-S4** | **ขอบเขตข้อมูลของฝ่าย (D4)** — กำหนดว่าสิทธิ์ที่ได้จากฝ่ายมีผลกับข้อมูลชุดไหน (ทั้งโรงเรียน / เฉพาะที่ผูกกับฝ่าย) + helper ให้เมนูอื่นเรียกใช้ | D-S3 | service + tests + docs | ⚪ |
| **D-S5** | **Audit log (D7)** — ผูก `MemberActivityLog` กับ CRUD ฝ่ายและสมาชิกฝ่าย | D-S1 | controller + tests | ⚪ |
| **D-S6** | **นำคนเข้าฝ่าย (D6)** — เครื่องมือ bulk จากรายชื่อบุคลากร + หน้าจอที่ใช้งานได้จริง | D-S2 | FE + BE | ⚪ |
| **D-S7** | **หน้ารายละเอียดฝ่าย (D8)** — ยกเครื่อง `[id].vue` ตามสกิล `hopeui-port` | D-S6 | FE | ⚪ |

**Rule:** ทุก step ต้อง verify (test/build/ตรวจจริง) ก่อนขึ้น 🟢 · ห้ามเปลี่ยนปลายทาง relation เดิม (กฎจาก #6 §6.1)

---

## 7. Codex Prompt Template

```
Context: .agents/school-admin/09-departments.md §<step-id>
Working dir: C:\wamp64\www\nuxnan
Files touched (expected): <รายการ>
Task: <what codex should do>
Constraints:
  - ห้ามแก้ ui/ ถ้า step ไม่ได้ระบุ
  - ห้าม commit · ห้ามแตะ .agents/
  - ห้าม migrate:fresh/refresh/reset (DB มีข้อมูลจริง)
  - ./vendor/bin/pint ก่อนจบ
Verification: php artisan test --filter=<...> + ตัวเลขจาก DB จริง
Report back: diff summary + ผลเทสต์ + คำสั่งที่รันไป
```

---

## 8. Review Log

- **2026-07-29 D-S1** — codex ทำ, claude ตรวจ → **ผ่าน** · ยืนยันเอง: `route:list --json` 15/15 route มี guard (อ่าน `groups.view` · เขียน `groups.manage`) ไม่มี route ไหนหลุด · เทสต์ 8 ผ่าน (authz 4 + guardian regression 4) · **ไม่แก้ `AcademyGroupPermissionService::hasPermission()`** ตามที่กำกับ เพราะ default `true` ของมันถูกใช้โดยฟีเจอร์โพสต์ในฟีดกลุ่ม (`AcademyPostController`, `AcademyGroupController`) การเปลี่ยนจะทำให้ทุกคนโพสต์ในกลุ่มไม่ได้ทันที (ตารางว่าง 0 แถว)
- **2026-07-29 — สแกน + สรุปสเปก** — พบว่าเมนูนี้มีโครงครบแต่ **ชั้นสิทธิ์ทั้งชั้นไม่ทำงาน**: route ไม่มี guard, `checkPermission()` ตรวจธงของฝ่ายแทนสิทธิ์ของผู้ใช้ และ `hasPermission()` คืน `true` เมื่อไม่มีข้อมูล (ตารางว่าง 0 แถว) → ใครก็ลบฝ่ายได้ · เจ้าของโปรเจคยืนยันว่าเป็นช่องโหว่จริง ไม่ได้ตั้งใจ และเลือกต่อยอด `academy_group_permissions` แทนการทำ `academy_role_scopes` ตามแผน S7 เดิม

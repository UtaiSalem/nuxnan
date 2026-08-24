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

## 5.1 ทำไม D-S4 ถึงเปลี่ยนรูป (2026-07-29)

สเปกเดิมเขียนว่า "จำกัดให้สมาชิกฝ่ายเห็นเฉพาะข้อมูลของฝ่ายตน" — **ตรวจแล้วพบว่าผิดรูป**

- คอลัมน์ `scope_type`/`scope_id` มีเฉพาะตาราง *เนื้อหา* (`academy_posts`, `school_announcements`, `academy_scope_tasks`, `academy_scope_files`, `adverts`, `revenue_share_policies`) และมี `AcademyScopeAccessService` บังคับอยู่แล้ว
- **ไม่มีตารางข้อมูลหลักตารางไหนผูกกับฝ่ายเลย** (นักเรียน ผู้ปกครอง การเข้าเรียน ผลการเรียน บุคลากร ห้องเรียน) — เพราะนักเรียนสังกัด *ห้องเรียน/ระดับชั้น* ไม่ได้สังกัดฝ่าย
- ในความเป็นจริงฝ่ายมีหน้าที่ **ระดับทั้งโรงเรียน** อยู่แล้ว (งานทะเบียนดูแลทะเบียนทุกคน · ฝ่ายกิจการนักเรียนดูแลนักเรียนทุกคน)

→ การสร้างกลไกจำกัดขอบเขตข้อมูลจึงเป็นการแก้ปัญหาที่ไม่มีอยู่ ด้วยงานที่ต้องแตะเกือบทุกตาราง

**ความเสี่ยงจริงคืออีกเรื่อง:** มีคนเผลอเปิดสิทธิ์ที่ไม่ควรให้ฝ่าย โดยเฉพาะ `roles.*` (สมาชิกฝ่ายสร้าง role ที่มีทุกสิทธิ์แล้วใส่ตัวเอง) และ `groups.manage` (ไปกดเปิดสิทธิ์เพิ่มให้ฝ่ายตัวเอง) — ทั้งสองทางทำให้ระบบสิทธิ์ทั้งระบบไร้ความหมาย

→ D-S4 จึงกลายเป็น **allow-list** ที่ใช้ตรรกะ default-deny (คีย์ใหม่ในอนาคตมอบให้ฝ่ายไม่ได้เอง)

**ผลการจัดหมวด 82 คีย์:** มอบได้ = 17 ตระกูล (`students`, `behavior`, `home_visits`, `attendance`, `school_attendance`, `grades`, `gradebook`, `schedule`, `courses`, `assignments`, `announcements`, `events`, `reports`, `messages`, `teachers`, `staff`, `children`) + `groups.view` + `members.view` · มอบไม่ได้ = `roles.*`, `groups.manage`, `members.manage/invite/roles.manage`, `academy.*`, `settings.*`, `finance.*`, `payments.*` · **ไม่มีคีย์ตกหล่น**

---

## 6. Implementation Tasks

| Step | Title | Depends | Deliverable | Status |
|---|---|---|---|---|
| **D-S1** | **อุดช่องโหว่ (D1+D2)** — `academy.permission:groups.view` ครอบทั้งกลุ่ม + `groups.manage` ทุก route เขียน · เปลี่ยนชื่อ `checkPermission()` → `ensureDepartmentFeatureEnabled()` ให้ความหมายตรงกับพฤติกรรม | — | routes + controller + `DepartmentAuthorizationTest` | 🟢 **verified 2026-07-29** — 15/15 route มี guard · เทสต์ 8 ผ่าน |
| **D-S2** | **นิยามบทบาทในฝ่าย (D5)** — พบว่า controller validate `in:member,staff,admin,head` อยู่แล้ว เหลือแค่แก้ default ของ DB จาก `'student'` → `'member'` | D-S1 | migration | 🟢 **verified 2026-07-29** (ยุบรวมกับ D-S6) |
| **D-S3** | **ต่อสิทธิ์ฝ่ายเข้ากับ middleware (D3)** — `AcademyGroupPermissionAccessService` (explicit opt-in: ต้องมีแถว `enabled = true` เท่านั้น) + เรียกใน `CheckAcademyPermission` หลังด่าน role | D-S1, D-S2 | middleware + service + 13 tests | 🟢 **verified 2026-07-29** |
| **D-S4** | **allow-list สิทธิ์ที่มอบให้ฝ่ายได้** — **เปลี่ยนรูปจากแผนเดิม** (ดู §5.1) · `AcademyPermission::DEPARTMENT_DELEGABLE_FAMILIES` + บังคับ 3 ชั้น: ปฏิเสธตอนบันทึก (422) · intersect ตอนตรวจสิทธิ์ · กรองตอนส่งรายการ | D-S3 | model + 2 service + controller + 5 tests | 🟢 **verified 2026-07-29** |
| **D-S5** | **Audit log (D7)** — 8 action ใหม่ (`department_create/update/delete/setup`, `member_add/remove/role_change`, **`permission_update`**) ผูกกับ `DepartmentController` + `AcademyGroupPermissionController` และลงทะเบียนใน `getAvailableActions()` ครบ · log สิทธิ์เก็บ `turned_on`/`turned_off` ให้เลย | D-S1 | model + 3 controller + 5 tests | 🟢 **verified 2026-07-29** |
| **D-S6** | **นำคนเข้าฝ่าย (D6)** — modal เพิ่มสมาชิกเดิมดึงแค่ 100 คนแรกจาก 3,063 คนแล้วกรองฝั่ง client → **ครู 119 คนอาจไม่โผล่เลย** นี่คือเหตุผลจริงที่สมาชิกฝ่ายมีแค่ 1 คน · แก้เป็นค้นหาฝั่ง server ผ่าน `/members/search` + กรองครู/บุคลากรเป็นค่าเริ่มต้น + เลือกหลายคน + pagination | D-S1 | migration + `departments/index.vue` | 🟢 **verified 2026-07-29** — ค้นหาครูเจอครบ 119/119 |
| **D-S6b** | **ป้ายบอกว่าอยู่ฝ่ายอื่นแล้ว** — `members/search` ยังไม่ส่งข้อมูลสังกัดฝ่ายมาด้วย ทำให้คนที่กดไม่เห็นว่าครูคนนี้อยู่ฝ่ายอื่นอยู่หรือไม่ก่อนตัดสินใจ · **สเปกเต็มใน [`.agents/tasks/ds6b-department-membership-badge.md`](../tasks/ds6b-department-membership-badge.md)** — opt-in `with_departments=1` เท่านั้น ห้ามแตะ `AcademyMemberResource` · แตกเป็น D-S6b-a (BE) / D-S6b-b (FE) | D-S6 | BE + FE | ⚪ |
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

- **2026-07-29 D-S3** — codex ทำ, claude ตรวจ → **พบข้อบกพร่องเรื่องลำดับ**: `if (! $role) return 403` อยู่ก่อนด่านฝ่าย ทำให้คนที่ไม่มี academy role (มี **2,898 คนจาก 3,063**) เข้าไม่ถึงสิทธิ์จากฝ่ายเลย — เทสต์ 6 เคสแรกพลาดเพราะทุกเคสสร้างผู้ใช้ที่มี role อยู่แล้ว → แก้เป็น `if ($role && $role->hasAnyPermission(...))` แล้วปล่อยให้ไหลไปด่านฝ่าย + เพิ่ม 3 เทสต์ · **verified: 17 เทสต์ผ่าน** (Department 13 + Guardian 4) · ยืนยันว่าไม่ได้ใช้ `AcademyGroupPermissionService::hasPermission()` ที่ default `true`
- **2026-07-29 D-S6/D-S2** — codex ทำ, claude ตรวจ → ผ่าน · ต้นเหตุจริงที่สมาชิกฝ่ายมีแค่ 1 คน คือ modal ดึงสมาชิกแค่ 100 คนแรกจาก 3,063 คน ทำให้ครู 119 คนอาจไม่โผล่เลย · หลังแก้ค้นหาเจอครบ 119/119 · ค้าง D-S6b (ป้ายบอกว่าอยู่ฝ่ายอื่นแล้ว — API ยังไม่ส่งข้อมูลสังกัดมา)
- **2026-07-29 D-S1** — codex ทำ, claude ตรวจ → **ผ่าน** · ยืนยันเอง: `route:list --json` 15/15 route มี guard (อ่าน `groups.view` · เขียน `groups.manage`) ไม่มี route ไหนหลุด · เทสต์ 8 ผ่าน (authz 4 + guardian regression 4) · **ไม่แก้ `AcademyGroupPermissionService::hasPermission()`** ตามที่กำกับ เพราะ default `true` ของมันถูกใช้โดยฟีเจอร์โพสต์ในฟีดกลุ่ม (`AcademyPostController`, `AcademyGroupController`) การเปลี่ยนจะทำให้ทุกคนโพสต์ในกลุ่มไม่ได้ทันที (ตารางว่าง 0 แถว)
- **2026-07-29 — สแกน + สรุปสเปก** — พบว่าเมนูนี้มีโครงครบแต่ **ชั้นสิทธิ์ทั้งชั้นไม่ทำงาน**: route ไม่มี guard, `checkPermission()` ตรวจธงของฝ่ายแทนสิทธิ์ของผู้ใช้ และ `hasPermission()` คืน `true` เมื่อไม่มีข้อมูล (ตารางว่าง 0 แถว) → ใครก็ลบฝ่ายได้ · เจ้าของโปรเจคยืนยันว่าเป็นช่องโหว่จริง ไม่ได้ตั้งใจ และเลือกต่อยอด `academy_group_permissions` แทนการทำ `academy_role_scopes` ตามแผน S7 เดิม

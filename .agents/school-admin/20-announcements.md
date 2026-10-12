# 20 — ประกาศ (Announcements)

> ไฟล์รองของเมนู **#20 ประกาศ** (`admin/announcements.vue`)
> อ่านคู่กับ [OVERVIEW.md](OVERVIEW.md)
> สถานะ: 🔎 **audit เสร็จ 2026-10-12 — ขั้น [1] อ่าน + [2] หา gap เสร็จ · รอเจ้าของเคาะ Q1–Q3**

---

## 1. Scope & Purpose

ระบบประกาศระดับโรงเรียน — สร้าง/เผยแพร่/ปักหมุดประกาศถึงสมาชิก โดยมี **ขอบเขต (scope)** 3 ระดับ:
- `academy` — ทั้งโรงเรียน
- `department` — เฉพาะฝ่าย/แผนก (ผูก AcademyGroup ของ #9)
- `classroom` — เฉพาะห้องเรียน (ครูประจำชั้นประกาศถึงห้องตน)

มี `target_audience` (roles/grades/departments), `priority` (low/normal/high/urgent), `announcement_type` (general/academic/financial/event/emergency), pin, published/expires

**ผู้ใช้:** ผู้บริหาร/ธุรการ (ประกาศทั้งโรงเรียน), ครู (ประกาศเข้าห้อง/ฝ่ายตน — *ออกแบบไว้แต่ยังทำไม่ได้*), สมาชิกทุกคน (อ่าน)

---

## 2. Current State (จากการสแกนโค้ดจริง 2026-10-12)

### Frontend
- Page: `ui/pages/academies/[name]/admin/announcements.vue` — list + create/edit modal + stats
- Components: `ui/components/school/SchoolPinnedAnnouncement.vue`, `ui/components/learn/academy/widgets/AcademyAnnouncementWidget.vue`
- ไม่มี composable/store เฉพาะ — เรียก `useApi` ตรง
- เมนูซ้าย (`admin.vue:219-220`): `show: can('announcements.view') || can('announcements.manage')`
- **หน้า guard (`:84-87`): `if (!isAdmin.value) { navigateTo('.../admin'); return }`** — เด้งทุกคนที่ไม่ใช่ academy-admin ออกทันที

### Backend
Routes (`routes/learn/academy.php:840-849`) — group `['academy.visibility:content', 'academy.permission']`:
| Method | Route | middleware | controller authz ภายใน |
|---|---|---|---|
| GET index | `/` | +`academy.visibility:content` | scope-based (dept/classroom ตรวจ group/homeroom) |
| GET show | `/{announcement}` | (member) | — |
| POST store | `/` | `announcements.manage` | **+ `isAcademyAdmin()`** |
| GET stats | `/stats` | `announcements.manage` | — |
| PATCH update | `/{announcement}` | `announcements.manage` | **+ `isAcademyAdmin()`** |
| POST publish/unpublish | … | `announcements.manage` | **+ `isAcademyAdmin()`** |
| DELETE destroy | `/{announcement}` | `announcements.manage` | **+ `isAcademyAdmin()`** |

Controller: `AnnouncementController` · `isAcademyAdmin($user,$academy) = $academy->isAdmin($user)` (owner / `academy_admins` / superadmin — คอมเมนต์ระบุ "pivot role ไม่ใช่แหล่งความจริง") · index รองรับ scope academy/department/classroom พร้อม authz อ่าน (ครูประจำชั้น/นักเรียนในห้อง)

Model: `SchoolAnnouncement` (`academy_id`, `scope_type`, `scope_id`, `created_by`, `target_audience`, `priority`, `announcement_type`, `is_pinned`, `is_published`, `published_at`, `expires_at`)

### Permission keys (`AcademyPermission.php:136-139`)
`announcements.view` · `announcements.create` · `announcements.create.own` (สำหรับคลาสของตน) · `announcements.manage`
แจก (`AcademyRole.php`): director/admin ได้ `view`+`create`+`manage` (`:61,88`) · teacher ได้ `view`+`create.own` (`:113`) · บทบาทอื่นได้ `view` อย่างเดียว

### Tests
`TargetAudienceNormalizationTest`, `AcademyScopeFilteringTest`, `SchoolManagementRouteGuardTest`, `ParentDashboardSectionsTest`, `Audit/AuditLogCallSitesTest` แตะ announcement บางส่วน — **ไม่มีเทสต์ยืนยันการบังคับคีย์ `announcements.manage` / `create.own`**

---

## 3. Feature Checklist

| # | ฟีเจอร์ | สถานะ | หมายเหตุ |
|---|---|---|---|
| 1 | ประกาศทั้งโรงเรียน CRUD | ✅ | ใช้ได้ (เฉพาะ academy-admin) |
| 2 | pin / publish / expire | ✅ | มี |
| 3 | scope department/classroom (อ่าน) | ✅ | index มี authz ตาม scope |
| 4 | ครูประกาศเข้าห้อง/ฝ่ายตน (เขียน) | ❌ | `create.own` ไม่ถูก wire — store บังคับ `isAcademyAdmin` |
| 5 | คีย์ `announcements.manage` บังคับใช้จริง | ❌ | dead key — ด่านจริงคือ `isAdmin` ทั้ง FE+BE |
| 6 | ผู้ถือ `announcements.view` เปิดหน้าแอดมินได้ | ❌ | หน้าเด้งออกถ้าไม่ใช่ `isAdmin` |

---

## 4. Permission Matrix (ที่ *ควรจะ* เป็น — รอเจ้าของยืนยัน)

| key | Owner | Director/Admin (role) | custom (manage) | Teacher | สมาชิกอื่น |
|---|---|---|---|---|---|
| `announcements.view` (อ่าน) | ✅ | ✅ | ✅ | ✅ | ✅ |
| `announcements.manage` (CRUD ทั้งโรงเรียน) | ✅ | ✅ | ✅ | ❌ | ❌ |
| `announcements.create.own` (ประกาศห้อง/ฝ่ายตน) | ✅ | ✅ | — | ✅ | ❌ |

> **ปัญหา:** matrix บังคับใช้ไม่ได้ — ทั้ง FE (`isAdmin`) และ BE (`isAcademyAdmin`) ข้ามคีย์ไปเช็ก academy-admin ตรง ๆ

---

## 5. Gap Analysis

- **G1 (dead key — หลัก):** write ทุกเส้นถูกคุมด้วย `isAcademyAdmin()` = `Academy::isAdmin()` (owner/`academy_admins`/superadmin) **ซ้อน** route `announcements.manage` → คีย์ `announcements.manage` ไม่มีผลจริง · member ที่ถือ `announcements.manage` ผ่าน role (รวม **director/admin** ที่ไม่ได้อยู่ในตาราง `academy_admins`) หรือ custom role **สร้าง/แก้ประกาศไม่ได้** · ขัดทิศทาง #11/#15/#19 (ย้ายไป `Academy::userCan`)
- **G2 (FE bounce — ซ้ำรอย #14 F1):** หน้า `announcements.vue:84-87` เด้งทุกคนที่ `!isAdmin` ออกทันที แต่เมนูโชว์ให้ผู้ถือ `announcements.view`/`announcements.manage` → ครู/custom role เห็นเมนู กดแล้วถูกเด้ง
- **G3 (create.own ไม่ wire):** คีย์ `announcements.create` / `announcements.create.own` + scope classroom/department (ฝั่งอ่านมีครบ: ครูประจำชั้น/นักเรียนในห้องเห็น) แต่ **ไม่มีทางให้ครูสร้าง** ประกาศ scope ห้องตน — store บังคับ academy-admin
- **G4 (tests):** ไม่มีเทสต์ยืนยันการบังคับคีย์ announcements.* / create.own

---

## 6. คำถามที่ต้องให้เจ้าของเคาะ (Q1–Q3)

- **Q1 — แหล่งความจริงของสิทธิ์ (เหมือน #19 Q1):** ย้าย write ทั้งหมดไปใช้ `Academy::userCan('announcements.manage')` (เลิก `isAcademyAdmin` ซ้อน) + FE page guard เป็น `can('announcements.manage')` แทน `isAdmin` ใช่ไหม? → คีย์มีผลจริง + director/admin/custom role ทำงานได้
- **Q2 — ใครเปิดหน้าแอดมินประกาศได้:** ให้ผู้ถือ `announcements.view` (เช่นครู) เปิดหน้า **ดูรายการได้แบบอ่านอย่างเดียว** (ปุ่มสร้าง/แก้ซ่อนถ้าไม่มี manage) หรือจำกัดหน้าให้เฉพาะผู้มี manage (แล้วแก้เมนูให้โชว์เฉพาะ manage)? เสนอ: เปิดให้ view ดูได้ ปุ่มจัดการ gate ด้วย manage
- **Q3 — create.own (ครูประกาศเข้าห้อง/ฝ่ายตน):** ทำรอบนี้เลย (wire ให้ครูประจำชั้นสร้างประกาศ scope=classroom ของห้องตน + แก้/ลบของตน) หรือเลื่อน? ฝั่งอ่าน scope มีแล้ว เหลือฝั่งเขียน — เป็นงานใหญ่กว่า G1/G2

---

## 7. Implementation Tasks

เจ้าของเคาะ 2026-10-12: **Q1** = ย้ายไป `userCan('announcements.manage')` (เหมือน #19) · **Q2** = `announcements.view` เปิดหน้าดูได้ · ปุ่มจัดการ gate ด้วย manage · **Q3** = เลื่อน create.own ไปทำแยก (โฟกัส G1/G2)

| Step | Title | Depends on | Deliverable | Status |
|---|---|---|---|---|
| AN-S1 | write authz → `userCan('announcements.manage')` (G1) | Q1 | `canManageAnnouncements` (เดิม `isAcademyAdmin`) delegate `userCan` | 🟡 code done |
| AN-S2 | FE guard + ปุ่มจัดการ ตรงคีย์ (G2) | Q1,Q2 | announcements.vue: guard = view\|\|manage · ปุ่มสร้าง/แก้/เผยแพร่/ลบ `v-if="canManage"` · stats ข้ามถ้าไม่มี manage | 🟡 code done |
| AN-S3 | wire create.own (ครูประกาศ scope ห้อง/ฝ่ายตน) (G3) | Q3 | — | ⏸️ **เลื่อน (เจ้าของเคาะ)** |
| AN-S4 | เทสต์ authz (G4) | AN-S1,S2 | `AnnouncementAuthzTest` 6 เคส (custom role manage, view-only, owner, delete, cross-tenant) | 🟡 เขียนแล้ว |

**การเปลี่ยนแปลงจริง (branch `claude/wonderful-maxwell-hfve3x`):**
- `AnnouncementController` — rename `isAcademyAdmin`→`canManageAnnouncements` (10 call sites) · body `$academy->isAdmin()` → `$academy->userCan($user, 'announcements.manage')` (ครอบทั้ง write + read-scope override)
- `ui/pages/academies/[name]/admin/announcements.vue` — guard `can('announcements.view')||can('announcements.manage')` · `canManage` computed · ปุ่มจัดการ gate · `fetchStatistics` ข้ามถ้าไม่มี manage · ถอด `isAdmin` ที่ไม่ใช้
- `tests/Feature/AnnouncementAuthzTest.php` — ใหม่

**Rule:** เทสต์รันบน MySQL จริง (`phpunit.mysql.xml`) ก่อนขึ้น 🟢

### 🔴 residual debt
- **G3 (create.own)** เลื่อนตามที่เจ้าของเคาะ — ครูยังประกาศเข้าห้อง/ฝ่ายตนเองไม่ได้ (ฝั่งอ่าน scope classroom/department มีครบแล้ว) · ทำเป็นงานแยกภายหลัง
- `announcements.create` (non-own) key ยังไม่มีผู้ใช้ — ผูกกับ create.own

---

## 8. Review Log
- **2026-10-12** — audit แรก (Claude สแกนโค้ดจริง): ยืนยัน G1–G4 · `announcements.manage` เป็น dead key (ด่านจริง `isAdmin` ทั้ง FE+BE) · G2 หน้าเด้ง non-admin (ซ้ำ #14 F1) · เจ้าของเคาะ Q1–Q3
- **2026-10-12 (ต่อ)** — ลงมือ AN-S1/S2/S4: BE ย้ายไป `userCan` · FE เปิดหน้าให้ view + gate ปุ่มด้วย manage · เขียน `AnnouncementAuthzTest` 6 เคส · php -l ผ่าน · ⏳ รันเทสต์ในคลาวด์ไม่ได้ (PHP 8.3.6 vs lock 8.4) → **เหลือเจ้าของรัน `php artisan test -c phpunit.mysql.xml --filter=AnnouncementAuthzTest` + `npm run build`** · AN-S3 (create.own) เลื่อน

# ส่วนงาน (Sub-departments) ภายในโรงเรียน — บทวิเคราะห์ + แผน

วันที่: 2026-06-20
อ้างอิง design: [`.agents/design-ref/School Homepage.html`](./School Homepage.html)

---

## 1. ยืนยันบทวิเคราะห์ของผู้ใช้

### ✅ บทวิเคราะห์ของผู้ใช้: ถูกต้อง

จากตัวอย่าง design มีผู้โพสต์หลากหลายแบบ:

| ผู้โพสต์ใน design | ประเภท |
|---|---|
| ฝ่ายวิชาการ | Department (ฝ่ายบริหาร) |
| สำนักผู้อำนวยการ | Office (ผู้บริหาร) |
| ฝ่ายทะเบียน | Department |
| งานหอพักนักเรียน | Department/Service |
| ฝ่ายกิจการนักเรียน | Department |
| ชมรมหุ่นยนต์ | Club |
| ห้องเรียน ม.5/2 | Classroom |
| กลุ่มสาระคณิตศาสตร์ | Academic group |

**ข้อสรุป:** แต่ละโพสต์มี "ส่วนงาน" เป็นผู้พูดแทน user ตรงๆ → ระบบต้องมี entity ระดับกลุ่มย่อยภายในโรงเรียน

### ✅ Hierarchy ที่ผู้ใช้เสนอ: ถูกต้อง

```
โรงเรียน (Academy)
├── สมาชิกโรงเรียน (academy_members)
└── ส่วนงาน (academy_groups) ── หลายส่วนงาน
    ├── หัวหน้า / Admin (academy_group_admins) — แต่งตั้งโดย Academy admin
    ├── สมาชิก (academy_group_members) — เชิญโดยหัวหน้า; ต้องเป็นสมาชิกโรงเรียนก่อน
    └── permissions (academy_group_permissions) — ระบุได้ว่ากลุ่มนี้ทำอะไรได้บ้าง
```

---

## 2. ข่าวดี: โครงสร้างพื้นฐานมีอยู่แล้ว 80%

### Tables ที่มีในระบบ

| Table | สถานะ | สาระ |
|---|---|---|
| `academy_groups` | ✅ มี | `id, academy_id, name, description, type, settings (json)` — type มี `department, classroom, club` (ตามตัวเอง) |
| `academy_group_admins` | ✅ มี | กำหนดหัวหน้า/admin ของกลุ่ม |
| `academy_group_members` | ✅ มี | สมาชิกในกลุ่ม + role |
| `academy_group_permissions` | ✅ มี | สิทธิ์ระดับกลุ่ม (เช่น "โพสต์ได้", "เชิญสมาชิกได้") |

### Routes ที่มีในระบบ ([`routes/learn/academy.php`](../../api/nuxnanravel/routes/learn/academy.php))

```
GET    /academies/{academy}/groups                — list
PATCH  /academies/{academy}/groups/reorder        — เรียงลำดับ
GET    /academies/{academy}/groups/type/{type}    — list by type ⭐
POST   /academies/{academy}/groups                — สร้างใหม่
GET    /groups/{academyGroup}                     — show
PATCH  /groups/{academyGroup}                     — update
DELETE /groups/{academyGroup}                     — delete

GET    /groups/{academyGroup}/members             — list members
POST   /groups/{academyGroup}/members             — เพิ่ม
DELETE /groups/{academyGroup}/members             — ลบ
PATCH  /groups/{academyGroup}/members/role        — เปลี่ยน role
```

### Models + Controller + Permission system ✅ ครบ

> นั่นแปลว่า **backend แทบจะพร้อมแล้ว** — เราต้องเติม UI + เพิ่ม `type` ให้ครอบคลุม + ทำ post-as-group + enforce permission flow

---

## 3. Gap จากของที่มี → เป้าหมายตาม design

### 🔴 G-A: Type ของกลุ่มจำกัด (3 ประเภท) แต่ design มีมากกว่า

ปัจจุบัน: `department, classroom, club`
ต้องการ: รองรับชื่อภาษาไทย + เพิ่ม **office (สำนัก), section (งาน/แผนก), committee (คณะกรรมการ), academic_group (กลุ่มสาระ)**

**ทางแก้:**
- ขยาย enum (string field อยู่แล้วเลือกเขียนได้)
- เพิ่ม metadata `display_label`, `icon`, `color` ผ่าน `settings.json` หรือทำ table `academy_group_types` แยก

### 🔴 G-B: ไม่มี post-as-group ใน Feed

ปัจจุบัน: โพสต์ของระบบมี `user_id` (เจ้าของ = user) เท่านั้น
ต้องการ: โพสต์ในนาม "กลุ่ม" ได้ (เช่น "ชมรมหุ่นยนต์" โพสต์ → แสดง avatar + ชื่อกลุ่ม + verified)

**ทางเลือก architecture:**
1. เพิ่ม `academy_group_id` (nullable) ใน `activities/posts` — ถ้ามี ค่อย override author display
2. ทำ polymorphic `postable_type, postable_id` (User หรือ AcademyGroup)
3. สร้าง entity "group page" + ใช้ user proxy account (เช่น user_type='group_account')

**แนะนำ:** option 1 (ง่าย, รบกวนน้อย) — `posts.posted_as_group_id`

### 🟡 G-C: ยังไม่มี UI จัดการกลุ่ม (โครงเท่านั้น)

ปัจจุบัน: tab "กลุ่ม" ใน `[name].vue` มี ดูจาก code ที่ผ่านมา — render groups list แต่ create/manage modal ยังไม่ครบ

### 🟡 G-D: ยังไม่มี Group Page (หน้าโปรไฟล์ของกลุ่ม)

design ไม่ได้แสดง group page แต่แนวคิดต้องมี — click avatar/name ของกลุ่มในโพสต์ → ดูโปรไฟล์กลุ่ม + ดูโพสต์ทั้งหมดของกลุ่ม + member list

### 🟡 G-E: Permission flow ยังไม่ enforce

มี table `academy_group_permissions` แต่ต้องเช็คว่า controller เช็คสิทธิ์จริงไหม

### 🟢 G-F: Workflow แต่งตั้ง / เชิญ ยังไม่มี UI

- Academy admin แต่งตั้ง group admin/leader
- Group admin เชิญสมาชิก (ต้องเป็น academy member ก่อน — เช็คก่อนเชิญ)
- มี API แล้ว แต่ UI ยังไม่มี

---

## 4. แผนการทำงาน (Phase G → K)

> ทำต่อจาก Phase C-D (cover polish + pinned announcement) — ตั้งชื่อ G-K เพื่อไม่ชน

### 🛣️ Phase G — Backend foundation (เติมที่ขาด)

**G.1 — ขยาย type enum + เพิ่ม metadata**
- ไม่ต้อง migration เพราะ field เป็น `string` อยู่แล้ว
- สร้าง config/constant ฝั่ง backend: `app/Constants/AcademyGroupTypes.php`
  ```php
  return [
    'office'          => ['label' => 'สำนัก',         'icon' => 'heroicons:building-office',     'color' => 'purple'],
    'department'      => ['label' => 'ฝ่าย',           'icon' => 'heroicons:briefcase',           'color' => 'cyan'],
    'section'         => ['label' => 'งาน',            'icon' => 'heroicons:clipboard-document',  'color' => 'green'],
    'academic_group'  => ['label' => 'กลุ่มสาระ',      'icon' => 'heroicons:book-open',           'color' => 'orange'],
    'classroom'       => ['label' => 'ห้องเรียน',      'icon' => 'heroicons:academic-cap',        'color' => 'cyan'],
    'club'            => ['label' => 'ชมรม',           'icon' => 'heroicons:trophy',              'color' => 'pink'],
    'committee'       => ['label' => 'คณะกรรมการ',    'icon' => 'heroicons:user-group',          'color' => 'amber'],
  ];
  ```
- expose ผ่าน `GET /api/academies/group-types` หรือฝัง config ใน frontend

**G.2 — Posts: เพิ่ม `posted_as_group_id`**
- Migration: `add_posted_as_group_id_to_activities_table.php` (หรือ posts table — เช็คชื่อจริง)
  ```php
  $table->foreignId('posted_as_group_id')
        ->nullable()
        ->after('user_id')
        ->constrained('academy_groups')
        ->nullOnDelete();
  ```
- Model relation: `Activity::belongsTo(AcademyGroup::class, 'posted_as_group_id', 'id')->as('postedAsGroup')`
- Validation: ตอน store post — ถ้า user เลือก post-as-group ต้อง:
  - user เป็น group admin/member
  - group มี permission `can_post = true`
- Resource: คืน `posted_as_group` object ถ้ามี

**G.3 — Group profile endpoint**
- เพิ่ม endpoint: `GET /academies/{academy}/groups/{group}/posts` → list โพสต์ที่ `posted_as_group_id = group.id`
- เพิ่ม endpoint: `GET /academies/{academy}/groups/{group}/stats` → member count, post count

**G.4 — Validation: invite member ต้องเป็น academy member**
- ใน `AcademyGroupController::addMember` เช็ค:
  ```php
  $isMember = AcademyMember::where('academy_id', $group->academy_id)
      ->where('user_id', $request->user_id)
      ->where('status', 'approved')
      ->exists();
  abort_unless($isMember, 422, 'ต้องเป็นสมาชิกโรงเรียนก่อน');
  ```

**G.5 — Permission constants**
- กำหนด list `permission_key`:
  - `can_post`, `can_invite_member`, `can_remove_member`, `can_pin_post`, `can_create_event`, `can_send_announcement`
- ใส่ใน `app/Constants/GroupPermissions.php`

**Files (G):** ~3 migrations + 1 config + edit 2 controllers + 1 model
**Risk:** Med — ต้องเช็คชื่อ table `posts` vs `activities` ก่อน + ทดสอบ pivot

---

### 🛣️ Phase H — UI: Manage Departments (Admin)

**H.1 — หน้า/Modal "เปิดส่วนงานใหม่"**
- จุดเข้า: tab "กลุ่ม" (currentTab === 'groups') มีปุ่ม "+ เปิดส่วนงานใหม่" (admin only)
- Modal: `ui/components/academy/groups/GroupCreateModal.vue` (ถ้ายังไม่มี)
  - field: ชื่อ, รายละเอียด, type (dropdown), color/icon (จาก type metadata)
- เรียก `POST /academies/{academy}/groups`

**H.2 — Group list grouped by type**
- ใน tab groups: group_by type → section per type
  ```
  📂 สำนัก (1)        [สำนักผู้อำนวยการ]
  📂 ฝ่าย (4)         [ฝ่ายวิชาการ] [ฝ่ายทะเบียน] [ฝ่ายกิจการนักเรียน] [ฝ่ายบริหารทั่วไป]
  📂 งาน (2)          [งานหอพักนักเรียน] [งานอนามัย]
  📂 กลุ่มสาระ (8)   [คณิตศาสตร์] [วิทยาศาสตร์] ...
  📂 ห้องเรียน (32)  ...
  📂 ชมรม (12)       [ชมรมหุ่นยนต์] ...
  ```

**H.3 — Group card**
- New component: `ui/components/academy/groups/GroupCard.vue`
- แสดง: avatar/icon + ชื่อ + member count + ปุ่ม "ดู" / "จัดการ" (admin only)

**H.4 — Group manage modal (admin)**
- New component: `ui/components/academy/groups/GroupManageModal.vue`
- Tabs:
  1. **ข้อมูล** — แก้ชื่อ/รายละเอียด/type
  2. **หัวหน้า/Admin** — list + เพิ่ม/ลบ (search academy member)
  3. **สมาชิก** — list + เพิ่ม/ลบ (search academy member, role)
  4. **สิทธิ์** — toggle permissions
  5. **ลบกลุ่ม** — confirm + soft delete

**Files (H):** ~4 components + edit `[name].vue` (groups tab section)
**Risk:** Low

---

### 🛣️ Phase I — Group Profile Page

**I.1 — Route ใหม่**
- `ui/pages/academies/[name]/groups/[groupId].vue` (NEW)
- หรือ `ui/pages/academies/[name]/groups/[slug].vue` ถ้าจะใช้ slug

**I.2 — Page layout (mini version ของ school homepage)**
- Cover (gradient + icon ของ type)
- ชื่อกลุ่ม + verified + type badge + member count + post count
- ปุ่ม "เข้าร่วม" (สำหรับสมาชิกโรงเรียนที่ยังไม่อยู่ในกลุ่ม) / "ออก"
- Tabs: ฟีดของกลุ่ม / สมาชิก / กิจกรรม / เกี่ยวกับ

**I.3 — Group feed**
- เรียก `GET /academies/{academy}/groups/{group}/posts`
- Composer สำหรับ admin/member ที่มี `can_post`

**I.4 — Group member list**
- Show admins (banner) + regular members (list)
- Search + filter by role

**Files (I):** 1 route + 4-5 components
**Risk:** Med — เพราะ overlap กับ academy page

---

### 🛣️ Phase J — Post-as-Group (in composer & feed)

**J.1 — Composer: เพิ่มตัวเลือก "โพสต์ในนาม..."**
- ใน `CreatePostBox` / `CreatePostModal`
- ถ้า user เป็น admin/member ของกลุ่มที่มี `can_post` → แสดง dropdown:
  ```
  โพสต์ในนาม:
  ○ พิมพ์ ส. (ตัวเอง)
  ○ ฝ่ายวิชาการ (ที่คุณเป็นหัวหน้า)
  ○ ชมรมหุ่นยนต์ (ที่คุณเป็นสมาชิก + can_post)
  ```
- POST `/posts` ส่ง `posted_as_group_id` ไป

**J.2 — FeedPost: render group header**
- ถ้า `post.posted_as_group` มีค่า → แสดง avatar ของกลุ่ม + ชื่อกลุ่ม + verified แทน user
- ใต้ชื่อกลุ่ม: "โดย {user.name}" (เครดิตคนโพสต์จริง — เห็นเฉพาะ admin/member ของกลุ่ม)

**J.3 — Permission check ฝั่ง backend ใน store**
- ตามที่วาง G.2

**Files (J):** edit 2-3 frontend components + edit 1 controller (PostController/ActivityController)
**Risk:** Med-High — เพราะกระทบ flow โพสต์เดิม

---

### 🛣️ Phase K — Invite Flow + Polish

**K.1 — Invite member to group (UI)**
- ใน `GroupManageModal` → tab "สมาชิก" → ปุ่ม "เชิญสมาชิก"
- เปิด modal: search bar (autocomplete จาก `academy_members` ที่ status = approved + ยังไม่อยู่ในกลุ่มนี้)
- เลือก role (member/leader)
- POST `/groups/{id}/members`

**K.2 — Group admin appointment (school admin)**
- ใน admin section ของ academy: หน้า "แต่งตั้ง"
- เลือกกลุ่ม → เลือก academy member → เพิ่มเป็น group admin
- POST `/groups/{id}/admins` (อาจต้องเพิ่ม route)

**K.3 — Group notifications**
- เมื่อมีการเชิญ → notify user
- เมื่อกลุ่มโพสต์ใหม่ → notify members

**K.4 — Permission UI**
- toggle UI ใน manage modal สำหรับแต่ละ permission_key

**Files (K):** ~3 modal components + edit notification system
**Risk:** Low-Med

---

## 5. ลำดับ Phase แนะนำ + เหตุผล

```
Phase G (Backend foundation)
   ↓ [must be solid first]
Phase H (Manage UI)  ←─── สามารถใช้งานจัดการได้ก่อน
   ↓
Phase I (Group profile page)  ←─── เห็นหน้าตาแล้ว
   ↓
Phase J (Post-as-group)       ←─── ฟีเจอร์เด่นที่ทำให้ตรง design
   ↓
Phase K (Invite + polish)     ←─── workflow ครบ
```

**Total estimate:**
- Backend (G): ~3-5 ชั่วโมง
- UI Manage (H): ~6-8 ชั่วโมง
- Group page (I): ~6-8 ชั่วโมง
- Post-as-group (J): ~5-7 ชั่วโมง
- Invite + polish (K): ~3-5 ชั่วโมง

**รวม: ~25-35 ชั่วโมง** (สามารถแบ่งทำเป็นช่วงๆ ได้)

---

## 6. คำถาม/Decision ที่ต้องตัดสินใจก่อนเริ่ม

### Q1: Post-as-group architecture
- [ ] **Option A** — เพิ่ม `posted_as_group_id` (แนะนำ)
- [ ] **Option B** — polymorphic `postable_type, postable_id`
- [ ] **Option C** — group มี user proxy account

### Q2: Type metadata
- [ ] **Option A** — Config constant (PHP file) — เปลี่ยนต้อง deploy
- [ ] **Option B** — Table `academy_group_types` แยก — admin จัดการได้ใน admin panel
- [ ] **Option C** — `academy.settings.group_types[]` — แต่ละโรงเรียนกำหนดเอง

### Q3: Hierarchy ระดับลึก?
- [ ] **Flat** — ทุกกลุ่มอยู่ใต้ academy โดยตรง
- [ ] **Nested** — กลุ่มมีกลุ่มย่อย (เช่น ฝ่ายวิชาการ → กลุ่มสาระคณิตศาสตร์)
  - ต้องเพิ่ม `parent_id` ใน `academy_groups`

### Q4: Naming
- ใน UI ใช้คำว่าอะไร — "ส่วนงาน", "หน่วยงาน", "กลุ่ม", หรือใช้ label ตาม type (สำนัก/ฝ่าย/งาน/ชมรม...)
- กระทบ tab label เดิม "กลุ่ม" — เปลี่ยนเป็น "ส่วนงาน" / "หน่วยงาน" ดีไหม?

### Q5: Sub-feeds vs unified feed
- โพสต์ของกลุ่มแสดงใน school feed รวมกัน หรือ
- แสดงใน group page เท่านั้น (school feed = academy-level announcement)?

→ design ปนกัน (รวมในฟีดเดียว) — แนะนำ unified แต่มี filter เพิ่ม

---

## 7. Quick wins (ทำได้ก่อนทันที — ไม่ต้องรอ decisions)

แม้ยังไม่ตัดสินใจคำถามด้านบน ทำสิ่งเหล่านี้ได้:
1. **เปลี่ยน tab label** "กลุ่ม" → "ส่วนงาน" (ถ้าตอบ Q4 = ส่วนงาน)
2. **สร้าง config type metadata** — ไม่กระทบ schema
3. **เพิ่ม UI list groups grouped by type** — ใช้ API ที่มีอยู่
4. **สร้าง `GroupCreateModal`** — เรียก POST /groups ที่มีอยู่
5. **สร้าง `GroupCard`** — แสดง avatar/icon + member count

5 ข้อด้านบนเสร็จ = หน้าจัดการกลุ่มทำงานได้ครึ่งทาง

---

## 8. ไฟล์ใหม่ที่จะเกิดขึ้น (สรุป)

### Backend
- `app/Constants/AcademyGroupTypes.php`
- `app/Constants/GroupPermissions.php`
- 1-2 migration files (posted_as_group_id, optional parent_id)

### Frontend
- `ui/components/academy/groups/GroupCreateModal.vue`
- `ui/components/academy/groups/GroupCard.vue`
- `ui/components/academy/groups/GroupManageModal.vue`
- `ui/components/academy/groups/GroupInviteMemberModal.vue`
- `ui/components/academy/groups/GroupPermissionToggle.vue`
- `ui/components/academy/groups/PostAsGroupSelector.vue` (Phase J)
- `ui/pages/academies/[name]/groups/[groupId].vue` (Phase I)
- `ui/composables/useAcademyGroups.ts` (encapsulate API calls)

### Edit
- `ui/pages/academies/[name].vue` (groups tab section)
- `ui/components/play/feed/FeedPost.vue` (Phase J — group header)
- `ui/components/play/feed/CreatePostBox.vue` (Phase J — post-as selector)

---

## 9. สรุป

✅ บทวิเคราะห์ของผู้ใช้ **ถูกต้องและละเอียด**
✅ ระบบมี foundation ครบ 80% แล้ว — ใช้ `academy_groups` + admin + member + permission
🎯 ต้องเติม: type metadata + post-as-group + UI ทั้งหมด (manage, page, composer)
🎯 ทำ Phase G→K ตามลำดับ ~25-35 ชั่วโมง

**Next action:** ตอบ Q1-Q5 (โดยเฉพาะ Q1 + Q4) แล้วเริ่ม Phase G ได้เลย หรือเริ่มจาก Quick wins (section 7) ก่อนถ้าอยากเห็นผลเร็ว

# School Departments — Decisions & Locked-in Architecture

อ้างอิง: [`school-departments-analysis-plan.md`](./school-departments-analysis-plan.md)
วันที่: 2026-06-20

> ผมตัดสินใจทั้ง 5 ข้อ พร้อมเหตุผล — ใช้เป็น "source of truth" ระหว่างทำ Phase G→K

---

## Q1 — Post-as-Group architecture

### 🏆 เลือก: **Option A — เพิ่ม `posted_as_group_id` (nullable FK)**

**โครงสร้าง:**
```
posts/activities:
  - id
  - user_id            (ผู้โพสต์จริง — ไม่เปลี่ยน)
  - posted_as_group_id (nullable FK → academy_groups.id)  ⬅ ใหม่
```

**Logic:**
- ถ้า `posted_as_group_id` = null → render เป็นโพสต์ส่วนตัว (user เป็น author)
- ถ้า `posted_as_group_id` มีค่า → render avatar/ชื่อกลุ่มเป็น author, ใต้นั้นมี "โดย {user.name}" เล็กๆ (admin/member ของกลุ่มเห็น)

### ทำไมไม่เลือก B (polymorphic)?
- Polymorphic ทำให้ query ซับซ้อนขึ้น (eager load 2 type)
- เสีย audit trail (ไม่รู้ว่า user จริงๆ ใครโพสต์)
- Refactor ของเดิมเยอะ (model trait + resource)

### ทำไมไม่เลือก C (proxy user)?
- สร้าง fake users → schema สกปรก
- Permission ซับซ้อน (group account login ยังไง?)
- Notification ผิดทาง

### ✅ ข้อดีของ A
1. Audit trail ครบ (รู้คนโพสต์จริงเสมอ)
2. Migration เล็กที่สุด (1 column)
3. Display logic ฝั่ง frontend ง่าย (`if (post.posted_as_group)`)
4. Permission check ตรงไปตรงมา (user ต้องเป็น member ของ group นั้น + group มี `can_post`)

---

## Q2 — Type metadata storage

### 🏆 เลือก: **Option A — PHP config constant** (สำหรับ v1)

**ไฟล์:** `api/nuxnanravel/app/Constants/AcademyGroupTypes.php`

```php
return [
  'office' => [
    'label' => 'สำนัก',
    'label_en' => 'Office',
    'icon' => 'heroicons:building-office',
    'color' => 'purple',
    'order' => 1,
  ],
  'department' => [
    'label' => 'ฝ่าย',
    'label_en' => 'Department',
    'icon' => 'heroicons:briefcase',
    'color' => 'cyan',
    'order' => 2,
  ],
  'section' => [
    'label' => 'งาน',
    'label_en' => 'Section',
    'icon' => 'heroicons:clipboard-document-list',
    'color' => 'green',
    'order' => 3,
  ],
  'academic_group' => [
    'label' => 'กลุ่มสาระ',
    'label_en' => 'Academic Group',
    'icon' => 'heroicons:book-open',
    'color' => 'orange',
    'order' => 4,
  ],
  'classroom' => [
    'label' => 'ห้องเรียน',
    'label_en' => 'Classroom',
    'icon' => 'heroicons:academic-cap',
    'color' => 'cyan',
    'order' => 5,
  ],
  'club' => [
    'label' => 'ชมรม',
    'label_en' => 'Club',
    'icon' => 'heroicons:trophy',
    'color' => 'pink',
    'order' => 6,
  ],
  'committee' => [
    'label' => 'คณะกรรมการ',
    'label_en' => 'Committee',
    'icon' => 'heroicons:user-group',
    'color' => 'amber',
    'order' => 7,
  ],
];
```

**Expose ผ่าน:** `GET /api/academy-group-types` (public read) → frontend cache

### ทำไมไม่เลือก B (table แยก)?
- Over-engineering สำหรับ v1 — ปัจจุบันไม่มี requirement ให้ admin custom type
- YAGNI — ถ้าวันหนึ่งต้องการ → migrate config → seed table ทีหลังได้

### ทำไมไม่เลือก C (per-academy)?
- ทุกโรงเรียนไทยใช้โครงสร้างคล้ายกัน (กระทรวงกำหนด)
- กระจาย type จะทำให้ analytics/reporting ระดับ multi-school ยาก

### 🔄 Upgrade path (เผื่ออนาคต)
- ถ้า requirement เปลี่ยน → สร้าง migration `academy_group_types` table แล้ว seed จาก config
- Frontend ไม่ต้องเปลี่ยน (API contract เดิม)

---

## Q3 — Hierarchy (flat vs nested)

### 🏆 เลือก: **Hybrid — เพิ่ม `parent_id` ใน schema แต่ render flat ใน v1**

**Migration:**
```php
Schema::table('academy_groups', function (Blueprint $table) {
  $table->foreignId('parent_id')
        ->nullable()
        ->after('academy_id')
        ->constrained('academy_groups')
        ->nullOnDelete();
  $table->index(['academy_id', 'parent_id']);
});
```

**Logic v1:**
- เก็บ `parent_id` ในฐานข้อมูล (เผื่ออนาคต)
- Query และ render กลุ่มทุกระดับเป็น flat list (group by `type` แล้วแสดง)
- ไม่มี UI tree view, ไม่มี breadcrumb

**Logic v2 (Phase L+ เมื่อมี requirement):**
- Tree view ใน manage page
- Breadcrumb ใน group profile
- Permission inheritance (กลุ่มแม่ปิดสิทธิ์ → ลูกถูกปิดด้วย)

### ทำไมเลือก hybrid?
- เพิ่ม column ตอนนี้ = cheap (1 nullable column)
- ถ้ารอ → จะต้อง migrate ข้อมูลทีหลังตอน records โต = ยุ่งกว่า
- โครงสร้างจริงในโรงเรียนไทย **มี nesting** (สำนักผู้อำนวยการ → ฝ่ายวิชาการ → กลุ่มสาระคณิต) — ไม่ใช่ขนานกัน
- ไม่บังคับใช้ใน v1 → ไม่เพิ่มความซับซ้อน UI

### ✅ ข้อดี
1. Future-proof — ไม่ต้อง migrate ใหญ่ทีหลัง
2. Backward compatible — ทุกกลุ่มปัจจุบัน `parent_id = null` = top-level
3. ทดสอบได้แต่ไม่ expose ใน UI v1

---

## Q4 — Naming

### 🏆 เลือก: **"ส่วนงาน"** เป็นชื่อกลาง + ใช้ type label เฉพาะรายการ

**การใช้งาน:**

| Context | คำที่ใช้ |
|---|---|
| Tab ในหน้า academy | "ส่วนงาน" (เปลี่ยนจาก "กลุ่ม") |
| ปุ่ม create | "เปิดส่วนงานใหม่" |
| Section header | "ส่วนงานทั้งหมด" / group by type → ใช้ type label ("ฝ่าย", "งาน", "ชมรม") |
| Badge บน group card | type label (สำนัก/ฝ่าย/งาน/กลุ่มสาระ/ห้องเรียน/ชมรม/คณะกรรมการ) |
| Page title group | ชื่อกลุ่ม + type badge ข้างๆ |

### ทำไมไม่เลือก "กลุ่ม"?
- คำกว้างเกิน — ไม่สื่อ authority/hierarchy
- ตอนนี้ระบบมี "กลุ่ม" หลายความหมาย (group chat, friendship group, course group) → confused

### ทำไมไม่เลือก "หน่วยงาน"?
- เป็นทางการเกินสำหรับชมรม/ห้องเรียน (ฟังดูเหมือนกระทรวง/ราชการ)

### ทำไม "ส่วนงาน"?
- ครอบคลุมทุกประเภท (ฝ่ายงานวิชาการ, งานหอพัก, ชมรม ก็เป็นส่วนงานหนึ่งของโรงเรียน)
- ตรงกับคำที่คุณใช้
- เป็นภาษาที่คนไทยในระบบการศึกษาคุ้นเคย

### 🔄 Migration plan สำหรับ tab rename
- เปลี่ยน label ใน `tabs` array (`'กลุ่ม'` → `'ส่วนงาน'`)
- เปลี่ยน `id` คงไว้ `'groups'` (ไม่กระทบ URL/state)
- เปลี่ยน i18n keys ตามมา

---

## Q5 — Feed strategy

### 🏆 เลือก: **Unified feed + filter chips + per-group mute**

**โครงสร้าง:**

```
School Homepage Feed (default view: All)
┌─────────────────────────────────────────────┐
│ [ทั้งหมด] [ประกาศ] [กิจกรรม] [วิชาการ] ... │ ← filter chips
├─────────────────────────────────────────────┤
│ 📌 Pinned announcement (ฝ่ายวิชาการ)       │
│ 📰 Director's message (สำนักผู้อำนวยการ)   │
│ 📰 Course assignment (กลุ่มสาระคณิต)        │
│ 📰 Robot club achievement (ชมรมหุ่นยนต์)   │
│ ...                                          │
└─────────────────────────────────────────────┘

Group Page Feed (เฉพาะกลุ่มนั้น)
┌─────────────────────────────────────────────┐
│ ชมรมหุ่นยนต์                                 │
│ โพสต์ทั้งหมดของชมรมเท่านั้น                 │
└─────────────────────────────────────────────┘
```

**Logic:**

1. **Default unified** — โพสต์ของทุกส่วนงาน + โพสต์ส่วนตัวของสมาชิก รวมในฟีดเดียว
2. **Filter chips** ใต้ tab "หน้าหลัก":
   - ทั้งหมด (default)
   - ประกาศ (post_type = announcement)
   - กิจกรรม (post_type = event)
   - ตาม type ของกลุ่ม (ฝ่าย/ชมรม/...)
3. **Per-group mute** — user คลิก "..." บนโพสต์กลุ่ม → "ปิดโพสต์จาก {ชื่อกลุ่ม}"
4. **Group page** = โพสต์ของกลุ่มเดียวเท่านั้น (deep-dive)

### ทำไมไม่ separate?
- Design ของคุณแสดง unified ชัด — สมาชิกอยากเห็นทุกอย่างในที่เดียว
- การบังคับให้ user ไปไล่ดูทุกกลุ่ม = friction
- Discoverability ตก (ชมรมเล็กๆ จะไม่มีใครเข้าไปดู)

### ทำไมต้องมี filter chip?
- ป้องกัน feed spam ตอนกลุ่มเยอะ
- ทำให้หา "ประกาศสำคัญ" ง่ายขึ้น

### ทำไม per-group mute?
- ผู้ปกครองอาจไม่อยากเห็นโพสต์ชมรมที่ลูกไม่ได้เข้า
- ครู ม.ปลาย ไม่อยากเห็นโพสต์ห้อง ม.ต้น

### ⚠️ Backend implication
- Feed endpoint ต้องรับ param `filter_type` และ `excluded_group_ids[]`
- ต้องมี table `user_muted_groups (user_id, academy_group_id)`
- API: `POST/DELETE /api/academy-groups/{id}/mute`

---

## 📋 สรุป Decisions

| Q | เลือก | Action ใน Phase G |
|---|---|---|
| 1 | `posted_as_group_id` FK | migration + relation + resource update |
| 2 | PHP config constant | สร้าง `AcademyGroupTypes.php` + API endpoint |
| 3 | `parent_id` ใน schema (hidden ใน UI v1) | migration เพิ่ม column |
| 4 | "ส่วนงาน" | rename tab + i18n update |
| 5 | Unified feed + filter + mute | migration `user_muted_groups` + endpoint filter |

---

## 🛣️ Phase G (Backend Foundation) — refined work order

ปรับจากแผนเดิมโดยใส่ decisions ที่ตัดสินใจแล้ว:

### G.1 — Type metadata (สร้างก่อน — ไม่กระทบ DB)
1. ✏️ Create `api/nuxnanravel/app/Constants/AcademyGroupTypes.php`
2. ✏️ Create `app/Http/Controllers/Api/Learn/Academy/GroupTypeController.php` — method `index()` คืน config
3. ✏️ Add route: `Route::get('/academy-group-types', [GroupTypeController::class, 'index'])` (public read)
4. ✏️ Create composable: `ui/composables/useAcademyGroupTypes.ts` — fetch + cache

### G.2 — Migrations (รวม 2 ตัว)
1. ✏️ `add_parent_id_to_academy_groups_table.php`
   ```php
   $table->foreignId('parent_id')->nullable()->after('academy_id')
         ->constrained('academy_groups')->nullOnDelete();
   $table->index(['academy_id', 'parent_id']);
   ```
2. ✏️ `add_posted_as_group_id_to_X_table.php` (ต้องเช็คชื่อ table ก่อน: `posts` หรือ `activities`)
   ```php
   $table->foreignId('posted_as_group_id')->nullable()->after('user_id')
         ->constrained('academy_groups')->nullOnDelete();
   $table->index('posted_as_group_id');
   ```
3. ✏️ `create_user_muted_groups_table.php`
   ```php
   $table->id();
   $table->foreignId('user_id')->constrained()->cascadeOnDelete();
   $table->foreignId('academy_group_id')->constrained()->cascadeOnDelete();
   $table->timestamps();
   $table->unique(['user_id', 'academy_group_id']);
   ```

### G.3 — Models + Relations
- `AcademyGroup::parent()` BelongsTo self
- `AcademyGroup::children()` HasMany self
- `Post::postedAsGroup()` BelongsTo AcademyGroup
- `User::mutedGroups()` BelongsToMany AcademyGroup through `user_muted_groups`

### G.4 — Resources update
- `PostResource` (หรือ `ActivityResource`) — append `posted_as_group` ถ้ามี
- `AcademyGroupResource` — append `type_meta` (label/icon/color) จาก config

### G.5 — Validation rules
- `StorePostRequest`:
  - ถ้า `posted_as_group_id` ส่งมา → ตรวจว่า user เป็น group admin/member + group มี permission `can_post`
- `AcademyGroupController::addMember`:
  - ตรวจ user_id ที่จะเพิ่มเป็นสมาชิกโรงเรียนแล้ว (status='approved')
  - ถ้าไม่ → return 422 "ต้องเป็นสมาชิกโรงเรียนก่อน"

### G.6 — Permission keys
- `app/Constants/GroupPermissions.php`:
  ```php
  return [
    'can_post' => 'โพสต์ในนามกลุ่ม',
    'can_invite_member' => 'เชิญสมาชิกใหม่',
    'can_remove_member' => 'นำสมาชิกออก',
    'can_pin_post' => 'ปักหมุดโพสต์',
    'can_create_event' => 'สร้างกิจกรรม',
    'can_send_announcement' => 'ออกประกาศ',
  ];
  ```

### G.7 — Mute endpoints
- `POST /api/academy-groups/{id}/mute` — toggle on
- `DELETE /api/academy-groups/{id}/mute` — toggle off

### G.8 — Feed filter
- เพิ่ม query param ใน `GET /academies/{id}/feed` หรือ activities endpoint:
  - `?filter_type=announcement|event|all`
  - `?group_type=office|department|...`
- Auto-exclude muted groups ของ user ที่ login

**ลำดับ commit G:**
```
feat(academy): add group type constants + public endpoint        [G.1]
feat(db): add parent_id + posted_as_group_id + user_muted_groups [G.2-3]
feat(api): expose posted_as_group in post resource              [G.4]
feat(api): validate post-as-group + invite member academy check [G.5]
feat(api): add group permission constants                       [G.6]
feat(api): user mute/unmute group endpoints                     [G.7]
feat(api): feed filter by type + auto-exclude muted groups      [G.8]
```

---

## 🚀 Quick wins ก่อน Phase G (ทำได้ทันที — ไม่กระทบ schema)

ถ้าอยากเห็นความคืบหน้าเร็ว ทำลำดับนี้ก่อน (ใช้ API/data ที่มีอยู่):

1. **Rename tab** "กลุ่ม" → "ส่วนงาน" + เปลี่ยน icon (`fluent:people-community` → `fluent:building-multiple`)
   - แตะแค่ `ui/pages/academies/[name].vue:148`

2. **สร้าง config type ฝั่ง frontend ก่อน** (ไม่รอ backend)
   - `ui/constants/academyGroupTypes.ts` — copy 7 types จาก G.1
   - ใช้ใน UI ทันที, ค่อย sync กับ backend ทีหลัง

3. **Render groups grouped by type** (ใช้ API `GET /academies/{id}/groups` ที่มีอยู่)
   - group by `g.type` → render section per type พร้อม header + count

4. **GroupCreateModal** เปิดส่วนงานใหม่ (ใช้ API `POST /academies/{id}/groups` ที่มีอยู่)
   - dropdown type จาก local constant
   - field: name, description, type

5. **GroupCard** — avatar/icon + name + member count + action button
   - Click → ไปหน้า group (Phase I) — pending

5 ข้อนี้ทำได้ภายใน 3-4 ชั่วโมง = แสดง progress ที่จับต้องได้ก่อนเริ่ม backend work

---

## 🎯 Next Action ที่ผมแนะนำ

**ลำดับงานที่แนะนำ:**

```
1️⃣ Quick wins (3-4 ชม.)
   → เห็นผล UI ทันที, ไม่กระทบ schema
   ↓
2️⃣ Phase G (Backend foundation) (3-5 ชม.)
   → migrations + constants + endpoints + validation
   ↓
3️⃣ Phase H (Manage UI) (6-8 ชม.)
   → upgrade GroupCreateModal + เพิ่ม GroupManageModal + permission toggles
   ↓
4️⃣ Phase I (Group profile page) (6-8 ชม.)
   ↓
5️⃣ Phase J (Post-as-group) (5-7 ชม.)
   → ฟีเจอร์เด่นที่ตรง design มากที่สุด
   ↓
6️⃣ Phase K (Invite + polish) (3-5 ชม.)
```

**สิ่งที่ผมแนะนำให้เริ่มทำเลย:** Quick wins ข้อ 1+2+3 ใน 1-2 ชั่วโมง — ได้เห็น tab rename + group list group by type + create modal ทำงานได้ก่อน แล้วค่อย commit แล้วไป Phase G

ถ้าพร้อมแล้ว บอกได้เลยจะให้ทำ:
- 🟢 **เริ่ม Quick wins (1-3)** ตอนนี้
- 🔵 **ไป Phase G เลย** (backend ก่อน)
- 🟡 **เขียนแผนละเอียดรายไฟล์ระดับ Phase G** ให้คุณทำเอง

# School Homepage — Work Plan (Phase A → D)

อ้างอิง: [`school-homepage-gap-analysis.md`](./school-homepage-gap-analysis.md) (priority แก้ตาม direction ของผู้ใช้)
Target page: [`ui/pages/academies/[name].vue`](../../ui/pages/academies/%5Bname%5D.vue)
Design ref: [`School Homepage.html`](./School Homepage.html)

## หลักการ
- ไม่แตะ backend ใน Phase A-D (ใช้ของที่มี: `school_announcements`, `school_events`, `analytics/dashboard-stats`)
- Reuse `BaseCard`, `BaseBadge`, `BaseAvatar`, `BaseButton`, `StatsBoxWidget`, `EventsWidget` ถ้าเหมาะ
- ไม่ลบ tab content / business logic เดิม แค่ปรับ shell + ใส่ widgets ใหม่
- `FeedPost` รื้อทีหลัง (Phase หลัง F)

---

## Backend verified (มีอยู่แล้ว)

| API | composable | ใช้ที่ |
|---|---|---|
| `GET /api/academies/{id}/announcements?is_pinned=1` | `useSchoolManagement().getAnnouncements` | Phase D pinned card |
| `GET /api/academies/{id}/announcements/stats` | (เรียกตรง api.call) | Phase D stats |
| `GET /api/academies/{id}/events/upcoming` | `useSchoolManagement().getEvents` (param) | Phase B upcoming widget |
| `GET /api/academies/{id}/analytics/dashboard-stats` | `useSchoolManagement().getDashboardStats` | Phase B stat grid |

`SchoolAnnouncement` มี field: `is_pinned`, `priority`, `target_audience[]`, `announcement_type`, `expires_at`
`SchoolEvent` มี field: `event_type`, `category`, `start_datetime`, `end_datetime`, `target_audience[]`, `registration_deadline`

---

# Phase A — 3-Column Shell

**Goal:** เปลี่ยน `[name].vue` จาก main+sidebar เป็น sticky 3-col grid (260 / 1fr / 320)

### A.1 — Refactor layout container
**File:** `ui/pages/academies/[name].vue`
**Lines:** 903-1030 (academy content + cover + tabs) + 1032-1035 (tab content grid)

**Change:**
- ปัจจุบัน `<div class="max-w-7xl mx-auto px-4 py-6">` ครอบทั้ง Cover+Tabs+Content
- Cover/Tabs ยังคงเป็น full-width ของ container เดิม (อยู่บน 3-col grid)
- เปลี่ยน `<!-- Tab Content -->` block (line 1032) จาก:
  ```html
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2"> ... tab content ... </div>
    <div> ... existing sidebar ... </div>
  </div>
  ```
- เป็น:
  ```html
  <div class="grid grid-cols-1 lg:grid-cols-[260px_1fr] xl:grid-cols-[260px_1fr_320px] gap-4 lg:gap-5 items-start">
    <aside class="hidden lg:block sticky top-[86px] space-y-4">
      <!-- LEFT widgets (Phase B) -->
    </aside>
    <main class="min-w-0 space-y-4">
      <!-- TAB content (current code) -->
    </main>
    <aside class="hidden xl:block sticky top-[86px] space-y-4">
      <!-- RIGHT widgets (Phase B) -->
    </aside>
  </div>
  ```

**Breakpoint behavior** (match design line 7-8 of bundler CSS):
- `< lg (1024px)`: 1 col — ทั้ง left+right ซ่อน (สอดคล้อง design `<820px`)
- `lg–xl`: 2 col — left + main (สอดคล้อง design `<1080px`)
- `≥ xl`: 3 col

### A.2 — Move `AcademyActionGuide` ออกจาก center feed
**File:** `ui/pages/academies/[name].vue:1036-1041`
- ลบออกจาก main feed (ปัจจุบันยึด real-estate ของ feed)
- ไปเรนเดอร์เป็น banner เหนือ tabs (line ~1010, ก่อน `<!-- Tabs -->`) เฉพาะตอน non-member/pending
- หรือ — ย้ายไป left sidebar ใน Phase B แทนทั้ง block (ผู้ใช้บอก "ย้ายแนวคิดทางลัดไป sidebar ซ้าย")
- **ตัดสินใจ:** เก็บ `AcademyActionGuide` ไว้เป็น banner เหนือ tabs สำหรับ non-member เท่านั้น เพราะมันเป็น call-to-action สำหรับการเข้าร่วม; ส่วน "ทางลัด" สร้าง `SchoolQuickMenu` แยกใน Phase B (คนละ concept)

### A.3 — เพิ่ม CSS sticky top offset
- Header height ใน main layout ดูเท่าไร — ผูก `top-[86px]` ตามตัวอย่าง (header sticky 64 + gap)
- ถ้า main layout ไม่ sticky → ใช้ `top-4` แทน

### A.4 — Smoke test
- รัน `npm run dev` ที่ `ui/`
- เปิด `/academies/{name}` 3 ขนาดจอ (375, 1024, 1440)
- ตรวจ: tab content ไม่หาย, scroll behavior, sticky ทำงาน

**Files touched (A):** 1 ไฟล์
- `ui/pages/academies/[name].vue` (refactor block 903-1041)

---

# Phase B — Left + Right Sidebar Widgets (reuse data)

**Goal:** เติม widgets ที่ design ต้องการ โดย reuse data ที่มีอยู่ (ไม่แตะ backend)

### B.1 — `SchoolQuickMenu.vue` (LEFT)
**New file:** `ui/components/school/SchoolQuickMenu.vue`
**Props:** `academy: { name: string, slug: string, authIsAcademyAdmin: boolean }`
**Render:**
- หัวการ์ด "เมนูลัด"
- 5 ลิงค์ (NuxtLink) — ใช้ icon บ็อกซ์สีตาม design:
  | label | icon | color tone | href |
  |---|---|---|---|
  | ประกาศทั้งหมด | `heroicons:megaphone` | purple | `?tab=announcements` (หรือ scroll to feed filter) |
  | ตารางเรียน | `heroicons:table-cells` | cyan | `/academies/{name}/schedule` (ถ้าไม่มี → link `?tab=classrooms`) |
  | ผลการเรียน | `heroicons:chart-bar` | green | `/academies/{name}/my-transcript` |
  | ห้องสมุดดิจิทัล | `heroicons:book-open` | orange | `?tab=courses` (หรือ library tab ถ้ามี) |
  | แต้มรางวัล | `heroicons:trophy` | pink | `/profile/{userId}#rewards` |
- ตรวจก่อนเขียนว่าแต่ละ link มี route จริงไหม — ถ้าไม่มี ให้เป็น `<button @click="toast('coming soon')">` แทน

### B.2 — `SchoolStatGrid.vue` (RIGHT)
**New file:** `ui/components/school/SchoolStatGrid.vue`
**Props:** `academy` (มี `total_students`, `courses_offered`)
**Render:** `grid grid-cols-2 gap-3` 4 cards (re-skin จาก `organisms/StatsBoxWidget.vue` ถ้า reuse ได้):
- นักเรียน (cyan) `heroicons:academic-cap` ← `academy.total_students`
- ครูและบุคลากร (purple) `heroicons:user-group` ← ใช้ `getDashboardStats` API หรือ mock เป็น `--` ถ้ายังไม่มี
- รายวิชา (green) `heroicons:book-open` ← `academy.courses_offered`
- อันดับจังหวัด (orange) `heroicons:trophy` ← **mock "—" หรือซ่อน card นี้ทั้งหมด** (business rule ยังไม่ชัด ตามที่ user บอก)

**Fallback:** ถ้า `getDashboardStats` มี field `total_teachers` → ใช้; ถ้าไม่มี → 3 card grid (ไม่ทำ rank)

### B.3 — `SchoolUpcomingEvents.vue` (RIGHT)
**New file:** `ui/components/school/SchoolUpcomingEvents.vue`
**Props:** `academyId: number`
**Data:** `useSchoolManagement().getEvents(academyId, { upcoming: 1, limit: 3 })` หรือ endpoint `/events/upcoming`
**Render:**
- หัวการ์ด "กิจกรรมที่จะถึง" + ลิงค์ `?tab=events` ("ปฏิทิน")
- Loop max 3 events:
  - Date chip (วันที่ใหญ่ + เดือนย่อ — แปลงจาก `start_datetime` ผ่าน Day.js)
  - Title (font-semibold)
  - Audience badge — map `target_audience[0]`:
    - `student` → purple "นักเรียน"
    - `teacher` → cyan "ครู"
    - `parent` → warning "ผู้ปกครอง"
    - `all` → gray "ทุกคน"
- Empty state: "ยังไม่มีกิจกรรมที่จะถึง"

**Reuse check:** `ui/components/organisms/EventsWidget.vue` มีอยู่ — เช็คก่อนว่าใช้ได้ไหม ถ้าโครงสร้างต่าง → wrap หรือเขียนใหม่ที่ลีน

### B.4 — Mount widgets ใน `[name].vue`
**File:** `ui/pages/academies/[name].vue`
- ใส่ใน left aside (Phase A.1):
  ```html
  <SchoolQuickMenu :academy="academy" />
  <!-- placeholder for SchoolLevelCard (Phase F) -->
  ```
- ใส่ใน right aside:
  ```html
  <SchoolStatGrid :academy="academy" />
  <SchoolUpcomingEvents :academy-id="academy.id" />
  <!-- placeholder for SchoolClassroomLeaderboard (Phase F) -->
  ```

**Files touched (B):** 4 ไฟล์
- NEW `ui/components/school/SchoolQuickMenu.vue`
- NEW `ui/components/school/SchoolStatGrid.vue`
- NEW `ui/components/school/SchoolUpcomingEvents.vue`
- MODIFY `ui/pages/academies/[name].vue` (mount 3 widget)

---

# Phase C — Cover & Tabs Polish

**Goal:** ทำ cover ให้ใกล้ design (pattern overlay, verified badge, tab count)

### C.1 — Cover visual treatment
**File:** `ui/pages/academies/[name].vue:918-923`
- Cover ปัจจุบัน: `bg-cover bg-center` + bottom dark gradient
- เพิ่ม:
  - **Pattern overlay** (SVG inline หรือ tiled bg)
    - ถ้าไม่มี asset → ใช้ Tailwind `bg-[url('data:image/svg+xml...')]` หรือ skip
  - **Stronger gradient** ด้านล่าง (จาก `transparent 40%` → `rgba(13,17,24,0.35) 100%`)
  - ปรับ height: `h-48 md:h-[180px]` ให้ตรงกับ design (180px)

### C.2 — Logo treatment
**File:** `[name].vue:927-936`
- ปัจจุบัน: 28/36 rounded-xl border-4 white
- เปลี่ยน:
  - Container: 112x112 `rounded-2xl` + padding inner 6px (white frame)
  - Inner: gradient fill (`from-vikinger-purple to-vikinger-cyan`) ถ้าไม่มีโลโก้
  - Position: ปัจจุบัน absolute -top-16 → เปลี่ยนเป็น flex layout (margin-top -46 แบบ design)

### C.3 — Verified badge + Level badge ข้างชื่อ
**File:** `[name].vue:942-948`
- หลัง `<h1>` เพิ่ม:
  ```html
  <Icon v-if="academy.verified" icon="heroicons:check-badge-solid" class="w-6 h-6 text-vikinger-cyan" />
  <BaseBadge v-if="academy.level" variant="purple">เลเวล {{ academy.level }}</BaseBadge>
  ```
- **Note:** ถ้า `academy.verified` / `academy.level` ยังไม่มีใน schema → skip ทั้ง 2 block (จะ wire ทีหลังใน Phase F)
- Sub-line: เพิ่ม `@{{ academy.slug }}` หรือ slug-handle

### C.4 — Stats inline + Posts count
**File:** `[name].vue:949-963`
- ปัจจุบันมี: type / members / courses
- เพิ่ม posts count: ใช้ `academy.total_posts` ถ้ามี ไม่งั้น skip
- ปรับ font: `<b>` value + label `text-muted` ตาม design

### C.5 — Tab count badges
**File:** `[name].vue:142-149` + `1014-1027`
- เพิ่ม `count` ใน tab definitions:
  ```ts
  { id: 'courses', label: 'รายวิชา', icon: '...', count: () => courses.value?.length }
  { id: 'members', label: 'สมาชิก', icon: '...', count: () => academy.value?.total_students }
  ```
- ใน template:
  ```html
  <span v-if="tab.count?.()" class="ml-1 text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700">
    {{ formatNumber(tab.count()) }}
  </span>
  ```
- เพิ่ม tab "เกี่ยวกับ" (`about`) ถ้ายังไม่มี — link ไปหน้า About หรือ render `AcademyAboutTab` (เช็คก่อนว่ามี)

### C.6 — Action buttons polish
**File:** `[name].vue:986-1006`
- ปุ่ม Join เปลี่ยน label:
  - Non-member: `ขอเป็นสมาชิก` + icon `plus`
  - Pending: `รออนุมัติ` (disabled)
  - Member: `เป็นสมาชิกแล้ว` + icon `check` (variant outline)
- เพิ่มปุ่ม **Share** ข้างๆ:
  ```html
  <button @click="shareAcademy" class="...outline">
    <Icon icon="heroicons:share" /> แชร์
  </button>
  ```
- `shareAcademy()` = `navigator.share({...})` หรือ copy link to clipboard

**Files touched (C):** 1 ไฟล์
- `ui/pages/academies/[name].vue` (lines 918-1028 + tabs array 142-149)

---

# Phase D — Pinned Announcement Card

**Goal:** ดึง `school_announcements` ที่ `is_pinned=1` มาแสดงเหนือ feed (ตาม design: pinned card ที่อยู่บนสุดของ center feed)

### D.1 — Composable wrapper
**File:** `ui/composables/useSchoolAnnouncements.ts` (NEW เล็กๆ)
หรือใช้ `useSchoolManagement().getAnnouncements` ตรงๆ ก็ได้
- เพิ่ม method shortcut:
  ```ts
  const getPinnedAnnouncements = (academyId, limit = 3) =>
    api.call(`/api/academies/${academyId}/announcements`, {
      params: { is_pinned: 1, is_published: 1, limit }
    })
  ```
- **ตรวจก่อน:** AnnouncementController `index` รับ param `is_pinned` ไหม — ถ้าไม่ ต้องเพิ่ม (เล็กน้อย)

### D.2 — `SchoolPinnedAnnouncement.vue` (NEW)
**New file:** `ui/components/school/SchoolPinnedAnnouncement.vue`
**Props:** `announcement: SchoolAnnouncement`
**Render** (match design line 114-137):
- การ์ดมี **purple ring** (`ring-2 ring-vikinger-purple`) + shadow
- Badge bar บนสุด: 🔖 "ปักหมุดไว้" (purple, 12px bold)
- Author row: Avatar + name + verified check + **priority badge**:
  - `urgent` → red dot "ด่วน"
  - `high` → orange dot "สูง"
  - `normal` → ไม่แสดง
- Timestamp: "X ชั่วโมงที่แล้ว" (relative time)
- Title `<h3>` (heading font, 18px)
- Content paragraph
- **Target audience line**: 👥 "กลุ่มเป้าหมาย: {label}"
  - Map `target_audience` array → "นักเรียนทุกระดับชั้น" / "ครู" / "ผู้ปกครอง" / "ทุกคน"
- Footer: like / comment count / share / **+X แต้ม chip** (ใช้ `reward_points` ถ้ามี ไม่งั้น skip)

### D.3 — Render ใน feed tab
**File:** `ui/pages/academies/[name].vue` (feed tab section, line ~1048)
- ก่อน FeedPost loop:
  ```html
  <SchoolPinnedAnnouncement
    v-for="ann in pinnedAnnouncements"
    :key="ann.id"
    :announcement="ann"
  />
  ```
- โหลด pinnedAnnouncements ตอน `loadAcademy()` หรือ `switchTab('feed')`:
  ```ts
  const pinnedAnnouncements = ref<any[]>([])
  const loadPinned = async () => {
    const res = await getPinnedAnnouncements(academy.value.id, 3)
    pinnedAnnouncements.value = res?.data ?? []
  }
  ```

### D.4 — Click → detail
- คลิก pinned card → navigate `/academies/{name}/announcements/{id}` (เช็คว่ามี route นี้ไหม)
- ถ้าไม่มี → expand inline หรือ modal

**Files touched (D):** 3 ไฟล์
- NEW `ui/components/school/SchoolPinnedAnnouncement.vue`
- MODIFY `ui/pages/academies/[name].vue` (load pinned + render)
- MODIFY `ui/composables/useSchoolManagement.ts` (เพิ่ม `getPinnedAnnouncements` helper) — optional

---

## Phase summary table

| Phase | files new | files modified | backend touched | risk |
|---|---|---|---|---|
| A | 0 | 1 | 0 | Low — refactor layout เท่านั้น |
| B | 3 | 1 | 0 | Low — reuse data |
| C | 0 | 1 | 0 | Low — visual polish |
| D | 1 (+ optional) | 1-2 | maybe `?is_pinned` param | Low-Med |

**Total:** 4 new files, ~3 modified files (ส่วนใหญ่อยู่ใน `[name].vue` เดียวกัน)

---

## Phase E-F (out of scope ตอนนี้ — รอ business rule)

- **E** — Integrate `school_events` ลง center feed เป็น event card variant (เมื่อ FeedPost รื้อแล้ว)
- **F** — Gamification: school level, classroom leaderboard (รอ business rule ก่อนทำ migration)

---

## ลำดับ commit ที่แนะนำ

1. `refactor(academy): switch [name].vue to 3-column layout shell` (Phase A)
2. `feat(school): add SchoolQuickMenu sidebar widget` (B.1)
3. `feat(school): add SchoolStatGrid + SchoolUpcomingEvents widgets` (B.2 + B.3 + mount)
4. `style(academy): polish cover + add verified badge & tab counts` (Phase C)
5. `feat(school): render pinned announcements above feed` (Phase D)

แต่ละ commit revert ได้แยก ไม่กระทบ tabs/business logic เดิม

---

## ก่อนเริ่ม Phase A — ขอ confirm 3 ข้อ

1. **Header sticky offset** — main layout มี sticky header height เท่าไร? (จะใช้ `top-[86px]` ถ้าตรง 64+gap; ไม่ตรงต้องเปลี่ยน)
2. **`AcademyActionGuide`** — เห็นด้วยกับการย้ายไปเป็น banner เหนือ tabs สำหรับ non-member only ไหม? (ผู้ใช้บอกให้ลดบทบาท แต่ไม่ได้บอกย้ายไปไหน)
3. **Tab "ฟีด" vs "หน้าหลัก"** — design ใช้ "หน้าหลัก" (`feed` tab) — เปลี่ยน label ไหม? หรือคงไว้

# Phase C & D — Detailed Implementation Plan (DIY-ready)

อ้างอิงไฟล์: [`ui/pages/academies/[name].vue`](../../ui/pages/academies/%5Bname%5D.vue)
สถานะตอนเริ่ม: Phase A+B เสร็จแล้ว (3-col shell + Quick Menu + StatGrid + UpcomingEvents)
หมายเลขบรรทัดทั้งหมดอ้างจาก state หลัง Phase A+B

---

# Phase C — Cover & Tabs Polish

**Goal:** ให้ cover/tabs ใกล้ design มากขึ้น โดยไม่แตะ backend

## C.1 — Cover Image (pattern overlay + stronger gradient + height)

**File:** `ui/pages/academies/[name].vue`
**Lines:** 917-923 (ปัจจุบัน)

**ปัจจุบัน:**
```html
<!-- Cover Image -->
<div
  class="h-48 md:h-64 bg-gray-300 dark:bg-gray-700 bg-cover bg-center relative"
  :style="{ backgroundImage: `url(${coverUrl})` }"
>
  <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
</div>
```

**เปลี่ยนเป็น:**
```html
<!-- Cover Image -->
<div
  class="h-44 md:h-[180px] bg-cover bg-center relative bg-gradient-to-br from-vikinger-purple to-vikinger-cyan"
  :style="academy.cover ? { backgroundImage: `url(${coverUrl})` } : {}"
>
  <!-- Pattern overlay (subtle dot grid for sci-fi feel) -->
  <div
    class="absolute inset-0 opacity-25 mix-blend-overlay"
    style="background-image: radial-gradient(circle, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 24px 24px;"
  ></div>
  <!-- Bottom dark gradient -->
  <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-black/40"></div>
</div>
```

**Why:**
- design ใช้ height 180px (ไม่ 256px)
- gradient base = Vikinger purple→cyan ตอนไม่มี cover image
- pattern overlay = sci-fi feel จาก design
- bottom-only gradient (สีเข้มล่าง) ดีกว่า top-to-bottom เพราะใช้ darken พื้นที่ที่โลโก้จะ overlap

**Test:** เปิดหน้า academy ทั้งที่มีและไม่มี cover image — gradient ต้อง fallback สวย

---

## C.2 — Logo Treatment (white frame + gradient fill)

**File:** `ui/pages/academies/[name].vue`
**Lines:** 927-936

**ปัจจุบัน:**
```html
<!-- Logo -->
<div class="absolute -top-16 left-4 md:left-8">
  <div class="w-28 h-28 md:w-36 md:h-36 rounded-xl border-4 border-white dark:border-vikinger-dark-200 shadow-lg overflow-hidden bg-white">
    <img
      :src="logoUrl"
      :alt="academy.name"
      class="w-full h-full object-cover"
    />
  </div>
</div>
```

**เปลี่ยนเป็น:**
```html
<!-- Logo -->
<div class="absolute -top-14 md:-top-12 left-4 md:left-8">
  <div class="w-24 h-24 md:w-28 md:h-28 rounded-2xl bg-white dark:bg-vikinger-dark-100 shadow-lg p-1.5">
    <div
      v-if="academy.logo"
      class="w-full h-full rounded-xl overflow-hidden bg-white"
    >
      <img :src="logoUrl" :alt="academy.name" class="w-full h-full object-cover" />
    </div>
    <!-- Fallback: gradient + initial letter -->
    <div
      v-else
      class="w-full h-full rounded-xl bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center text-white font-bold text-2xl md:text-3xl"
    >
      {{ (academy.name || '?').charAt(0).toUpperCase() }}
    </div>
  </div>
</div>
```

**Why:**
- design ใช้ `rounded-2xl` + white padding 6px (= `p-1.5`) แทน `border-4`
- ขนาดลดลงเล็กน้อย (112px ใน design = 28 หน่วย Tailwind)
- มี fallback gradient + ตัวอักษรแรก
- offset `-top-12` (md) แทน `-top-16` เพราะ cover เตี้ยลง

**Test:** ลองกับ academy ที่ไม่มี logo — ต้องเห็น gradient + ตัวอักษรชื่อ

---

## C.3 — Verified Badge + Level Badge ข้างชื่อ

**File:** `ui/pages/academies/[name].vue`
**Lines:** 941-948

**ปัจจุบัน:**
```html
<div class="mb-4 md:mb-0">
  <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-2">
    {{ academy.name }}
  </h1>
  <p v-if="academy.slogan" class="text-gray-600 dark:text-gray-400 mb-3">
    {{ academy.slogan }}
  </p>
```

**เปลี่ยนเป็น:**
```html
<div class="mb-4 md:mb-0">
  <div class="flex items-center gap-2 flex-wrap mb-1">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
      {{ academy.name }}
    </h1>
    <!-- Verified (only show if backend supplies it) -->
    <Icon
      v-if="academy.is_verified"
      icon="heroicons:check-badge-solid"
      class="w-6 h-6 text-vikinger-cyan"
      title="โรงเรียนที่ได้รับการยืนยัน"
    />
    <!-- Level badge (only show if backend supplies it) -->
    <span
      v-if="academy.level"
      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-vikinger-purple/15 text-vikinger-purple"
    >
      <Icon icon="heroicons:bolt-solid" class="w-3 h-3" />
      เลเวล {{ academy.level }}
    </span>
  </div>
  <p v-if="academy.slogan" class="text-sm text-gray-500 dark:text-gray-400 mb-3">
    {{ academy.slogan }}
  </p>
```

**Why:**
- `v-if` ป้องกัน error ถ้า backend ยังไม่มี field (Phase F จะเพิ่ม)
- ไม่ต้องแก้ backend ใน Phase C — ถ้า field ไม่มีก็ไม่ render เฉยๆ

**Decision needed:**
- ถ้าอยากให้ verified badge แสดงเสมอ สำหรับโรงเรียนทุกแห่ง (เพราะ business rule บอกว่าทุกโรงเรียนที่อยู่ในระบบถือว่า verified) → เปลี่ยน `v-if="academy.is_verified"` เป็น `v-if="true"` หรือลบ v-if ทิ้ง

---

## C.4 — Stats Inline (เพิ่ม posts count + ปรับ styling)

**File:** `ui/pages/academies/[name].vue`
**Lines:** 949-963

**ปัจจุบัน:**
```html
<!-- Stats -->
<div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
  <div class="flex items-center gap-1.5">
    <Icon :icon="getAcademyTypeInfo(academy.type).icon" :class="['w-4 h-4', getAcademyTypeInfo(academy.type).color]" />
    <span>{{ getAcademyTypeInfo(academy.type).label }}</span>
  </div>
  <div class="flex items-center gap-1.5">
    <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
    <span>{{ academy.total_students || 0 }} สมาชิก</span>
  </div>
  <div class="flex items-center gap-1.5">
    <Icon icon="fluent:book-24-regular" class="w-4 h-4" />
    <span>{{ academy.courses_offered || 0 }} รายวิชา</span>
  </div>
</div>
```

**เปลี่ยนเป็น:**
```html
<!-- Stats inline (design style: bold number + muted label) -->
<div class="flex flex-wrap items-center gap-4 text-sm">
  <div class="flex items-center gap-1.5 text-gray-600 dark:text-gray-400">
    <Icon :icon="getAcademyTypeInfo(academy.type).icon" :class="['w-4 h-4', getAcademyTypeInfo(academy.type).color]" />
    <span>{{ getAcademyTypeInfo(academy.type).label }}</span>
  </div>
  <span class="text-gray-600 dark:text-gray-400">
    <b class="text-gray-900 dark:text-white">{{ academy.total_students || 0 }}</b>
    สมาชิก
  </span>
  <span class="text-gray-600 dark:text-gray-400">
    <b class="text-gray-900 dark:text-white">{{ academy.courses_offered || 0 }}</b>
    รายวิชา
  </span>
  <span v-if="academy.total_posts != null" class="text-gray-600 dark:text-gray-400">
    <b class="text-gray-900 dark:text-white">{{ academy.total_posts }}</b>
    โพสต์
  </span>
</div>
```

**Why:** design ใช้ตัวเลขหนา + label muted แทน icon + ตัวเลข inline
**Note:** `total_posts` ใส่ v-if เพราะ backend อาจไม่มี — ถ้าอยากใช้ → backend ต้องเพิ่ม field นี้ใน academy resource

---

## C.5 — Action Buttons (Share + ปรับ Join labels)

**File:** `ui/pages/academies/[name].vue`
**Lines:** 967-1007

**ปัจจุบัน:** มี 4 ปุ่ม — status badge, admin button, join button, member settings button
**ปัญหา:** ไม่มี Share button; Join label "เข้าร่วมโรงเรียน" (design ใช้ "ขอเป็นสมาชิก")

**ขั้นตอน:**

**C.5.1** — เปลี่ยน label Join button (line 995):
```html
เข้าร่วมโรงเรียน
```
→
```html
ขอเป็นสมาชิก
```

**C.5.2** — เพิ่ม Share button หลัง member settings button (ก่อน `</div>` ปิด actions ที่ line 1007):

```html
<!-- Share -->
<button
  @click="shareAcademy"
  class="px-4 py-2.5 bg-white dark:bg-vikinger-dark-100 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 transition-colors flex items-center gap-2"
  title="แชร์โรงเรียน"
>
  <Icon icon="heroicons:share" class="w-4 h-4" />
  <span class="hidden sm:inline">แชร์</span>
</button>
```

**C.5.3** — เพิ่ม method `shareAcademy` (ใน `<script setup>`, หาที่ใกล้ `requestMembership`):
```ts
const shareAcademy = async () => {
  const url = window.location.href
  const title = academy.value?.name || 'โรงเรียน'

  // Try Web Share API first (mobile)
  if (navigator.share) {
    try {
      await navigator.share({ title, url })
      return
    } catch (e) {
      // user cancelled — fall through to clipboard
    }
  }

  // Fallback: copy to clipboard
  try {
    await navigator.clipboard.writeText(url)
    Swal.fire({
      icon: 'success',
      title: 'คัดลอกลิงค์แล้ว',
      timer: 1500,
      showConfirmButton: false,
    })
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'คัดลอกล้มเหลว', timer: 1500 })
  }
}
```

**Test:**
- Desktop Chrome → ควรคัดลอกลิงค์ลง clipboard + toast แจ้ง
- Mobile Safari → เปิด native share sheet

---

## C.6 — Tab Count Badges

**File:** `ui/pages/academies/[name].vue`
**Lines:** 142-149 (tabs array) + 1013-1028 (tabs render)

### C.6.1 — เปลี่ยน `tabs` definition

**ปัจจุบัน (line 142-149):**
```ts
const tabs = [
  { id: 'feed', label: 'หน้าหลัก', icon: 'fluent:home-24-regular' },
  { id: 'courses', label: 'รายวิชา', icon: 'fluent:book-24-regular' },
  { id: 'members', label: 'สมาชิก', icon: 'fluent:people-24-regular' },
  { id: 'classrooms', label: 'ห้องเรียน', icon: 'fluent:board-24-regular' },
  { id: 'events', label: 'กิจกรรม', icon: 'fluent:calendar-star-24-regular' },
  { id: 'groups', label: 'กลุ่ม', icon: 'fluent:people-community-24-regular' },
]
```

**เปลี่ยนเป็น computed (เพื่อให้ count reactive):**
```ts
const tabs = computed(() => [
  { id: 'feed', label: 'หน้าหลัก', icon: 'fluent:home-24-regular' },
  { id: 'courses', label: 'รายวิชา', icon: 'fluent:book-24-regular', count: academy.value?.courses_offered },
  { id: 'members', label: 'สมาชิก', icon: 'fluent:people-24-regular', count: academy.value?.total_students },
  { id: 'classrooms', label: 'ห้องเรียน', icon: 'fluent:board-24-regular', count: classrooms.value?.length || null },
  { id: 'events', label: 'กิจกรรม', icon: 'fluent:calendar-star-24-regular', count: eventsPagination.value?.total || null },
  { id: 'groups', label: 'กลุ่ม', icon: 'fluent:people-community-24-regular', count: groups.value?.length || null },
])
```

**⚠️ Side effect:** `tabs` กลายเป็น `computed` ref — ทุกที่ใน script ที่ใช้ `tabs.some(...)` ต้องเปลี่ยนเป็น `tabs.value.some(...)`. ค้นด้วย Grep:
```
grep -n "\btabs\." ui/pages/academies/\[name\].vue
```
แล้วเปลี่ยน `tabs.some` → `tabs.value.some`, `tabs.find` → `tabs.value.find` ฯลฯ

จากที่ผมเห็น (lines 860, 867) มี:
```ts
if (tabs.some(t => t.id === hash)) {
```
ต้องเปลี่ยนเป็น:
```ts
if (tabs.value.some(t => t.id === hash)) {
```

### C.6.2 — เปลี่ยน tab render (line 1014-1027)

**ปัจจุบัน:**
```html
<button
  v-for="tab in tabs"
  :key="tab.id"
  @click="switchTab(tab.id)"
  :class="[...]"
>
  <Icon :icon="tab.icon" class="w-5 h-5" />
  {{ tab.label }}
</button>
```

**เปลี่ยนเป็น:**
```html
<button
  v-for="tab in tabs"
  :key="tab.id"
  @click="switchTab(tab.id)"
  :class="[
    'flex items-center gap-2 px-6 py-4 text-sm font-medium transition-colors whitespace-nowrap',
    currentTab === tab.id
      ? 'text-vikinger-purple border-b-2 border-vikinger-purple'
      : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
  ]"
>
  <Icon :icon="tab.icon" class="w-5 h-5" />
  <span>{{ tab.label }}</span>
  <span
    v-if="tab.count != null && tab.count > 0"
    :class="[
      'ml-1 px-1.5 py-0.5 rounded text-[11px] font-semibold',
      currentTab === tab.id
        ? 'bg-vikinger-purple/15 text-vikinger-purple'
        : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'
    ]"
  >
    {{ formatTabCount(tab.count) }}
  </span>
</button>
```

### C.6.3 — เพิ่ม helper `formatTabCount` (ในไฟล์เดียวกัน)
```ts
const formatTabCount = (n: number): string => {
  if (n >= 1000) return (n / 1000).toFixed(1).replace(/\.0$/, '') + 'k'
  return new Intl.NumberFormat('th-TH').format(n)
}
```

**Why:** ตัวเลข 1,248 → "1.2k" เพื่อไม่ให้ tab bar กว้างเกิน

---

## C.7 — Phase C Test Checklist

- [ ] Cover เตี้ยลง (180px) + มี pattern + ไม่มี cover ก็มี gradient
- [ ] โลโก้ rounded-2xl + ถ้าไม่มี logo เห็นอักษรแรกบนพื้น gradient
- [ ] Verified badge แสดงเมื่อ `academy.is_verified === true` (ทดสอบโดยเปิด Vue Devtools แก้ค่า)
- [ ] Level badge แสดงเมื่อ `academy.level` มีค่า
- [ ] Stats inline: ตัวเลขหนา + label muted
- [ ] Join button label = "ขอเป็นสมาชิก"
- [ ] Share button — desktop = clipboard + toast / mobile = share sheet
- [ ] Tab badges แสดง count, ตัวเลข > 1000 → "1.2k"
- [ ] Active tab badge มีโทน purple, inactive โทนเทา
- [ ] รัน `npm run dev` ไม่มี error

**Files touched in C:** 1 file
- `ui/pages/academies/[name].vue` (5 จุด)

**Commit suggestion:**
```
style(academy): polish cover, add verified/level badges, tab counts, share button
```

---

---

# Phase D — Pinned Announcement Card

**Goal:** ดึง `school_announcements` ที่ pinned + published มาแสดงเหนือ feed
**Backend pre-check:** ต้องเช็คก่อนว่า `AnnouncementController::index` รับ query `is_pinned`, `is_published` ไหม

## D.0 — Backend pre-check (อาจต้องแก้)

**File:** `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AnnouncementController.php`

หา method `index` แล้วดูว่ามี filter เหล่านี้หรือยัง:
```php
if ($request->has('is_pinned')) {
    $query->where('is_pinned', (bool) $request->is_pinned);
}
if ($request->has('is_published')) {
    $query->where('is_published', (bool) $request->is_published);
}
if ($request->has('limit')) {
    $query->limit((int) $request->limit);
}
```

**ถ้ายังไม่มี:**
- เพิ่ม 3 filter ข้างบนใน `index` method
- ตรวจให้ filter อยู่ก่อน `->get()` หรือ `->paginate()`

**ถ้าไม่อยากแตะ backend:** กรอง client-side แทน (รับทั้งหมดมาแล้ว filter ใน JS)
```ts
const all = res?.data ?? []
pinnedAnnouncements.value = all
  .filter(a => a.is_pinned && a.is_published)
  .slice(0, 3)
```
ข้อเสีย: โหลดเกิน — ใช้ได้ถ้าจำนวน announcement น้อย

**Decision:** สำหรับ DIY แนะนำ client-side filter ก่อน (ไม่ต้องแตะ controller); ถ้า dataset โตค่อยย้าย server-side ทีหลัง

---

## D.1 — สร้าง `SchoolPinnedAnnouncement.vue`

**File:** `ui/components/school/SchoolPinnedAnnouncement.vue` (NEW)

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

interface Announcement {
  id: number
  title: string
  content: string
  announcement_type?: string | null
  priority?: 'low' | 'normal' | 'high' | 'urgent' | null
  target_audience?: string[] | null
  is_pinned?: boolean
  published_at?: string | null
  created_at?: string
  creator?: {
    id: number
    name: string
    avatar?: string | null
    is_verified?: boolean
  } | null
  likes_count?: number
  comments_count?: number
  reward_points?: number
}

interface Props {
  announcement: Announcement
}

const props = defineProps<Props>()
const emit = defineEmits<{ open: [id: number]; like: [id: number] }>()

// Priority → badge
const priorityBadge = computed(() => {
  switch (props.announcement.priority) {
    case 'urgent': return { label: 'ด่วน', cls: 'bg-red-500/15 text-red-600 dark:text-red-400', dot: 'bg-red-500' }
    case 'high':   return { label: 'สูง', cls: 'bg-amber-500/15 text-amber-700 dark:text-amber-400', dot: 'bg-amber-500' }
    default:       return null
  }
})

// target_audience array → readable label
const audienceLabel = computed(() => {
  const arr = props.announcement.target_audience || []
  if (arr.length === 0 || arr.includes('all')) return 'ประชาคมโรงเรียนทุกคน'
  const map: Record<string, string> = {
    student: 'นักเรียน',
    teacher: 'ครูและบุคลากร',
    parent: 'ผู้ปกครอง',
    staff: 'เจ้าหน้าที่',
  }
  return arr.map(a => map[a] || a).join(' · ')
})

// "X ชั่วโมงที่แล้ว"
const relativeTime = computed(() => {
  const iso = props.announcement.published_at || props.announcement.created_at
  if (!iso) return ''
  const diff = (Date.now() - new Date(iso).getTime()) / 1000
  if (diff < 60) return 'เมื่อสักครู่'
  if (diff < 3600) return `${Math.floor(diff / 60)} นาทีที่แล้ว`
  if (diff < 86400) return `${Math.floor(diff / 3600)} ชั่วโมงที่แล้ว`
  if (diff < 86400 * 7) return `${Math.floor(diff / 86400)} วันที่แล้ว`
  return new Date(iso).toLocaleDateString('th-TH')
})

const initial = computed(() => (props.announcement.creator?.name || 'A').charAt(0).toUpperCase())
</script>

<template>
  <article
    class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm ring-2 ring-vikinger-purple/60 p-4 md:p-5"
  >
    <!-- Pinned label -->
    <div class="flex items-center gap-1.5 mb-3 text-vikinger-purple text-xs font-bold">
      <Icon icon="heroicons:bookmark-solid" class="w-3.5 h-3.5" />
      ปักหมุดไว้
    </div>

    <!-- Author row -->
    <div class="flex gap-3">
      <div class="w-10 h-10 rounded-full bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center text-white font-bold text-sm flex-shrink-0 overflow-hidden">
        <img v-if="announcement.creator?.avatar" :src="announcement.creator.avatar" class="w-full h-full object-cover" />
        <span v-else>{{ initial }}</span>
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5 flex-wrap">
          <span class="font-bold text-sm text-gray-900 dark:text-white">
            {{ announcement.creator?.name || 'ฝ่ายวิชาการ' }}
          </span>
          <Icon
            v-if="announcement.creator?.is_verified"
            icon="heroicons:check-badge-solid"
            class="w-4 h-4 text-vikinger-cyan"
          />
          <span
            v-if="priorityBadge"
            :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold', priorityBadge.cls]"
          >
            <span :class="['w-1.5 h-1.5 rounded-full', priorityBadge.dot]"></span>
            {{ priorityBadge.label }}
          </span>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
          ประกาศอย่างเป็นทางการ · {{ relativeTime }}
        </div>
      </div>
      <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
        <Icon icon="heroicons:ellipsis-horizontal" class="w-5 h-5" />
      </button>
    </div>

    <!-- Title + content -->
    <h3 class="mt-3 mb-1.5 text-base md:text-lg font-bold text-gray-900 dark:text-white leading-snug">
      {{ announcement.title }}
    </h3>
    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
      {{ announcement.content }}
    </p>

    <!-- Target audience line -->
    <div class="mt-3 inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
      <Icon icon="heroicons:user-group" class="w-4 h-4" />
      <span>กลุ่มเป้าหมาย: {{ audienceLabel }}</span>
    </div>

    <!-- Footer: actions + reward -->
    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-5 text-sm text-gray-500 dark:text-gray-400">
      <button
        @click="emit('like', announcement.id)"
        class="inline-flex items-center gap-1.5 font-semibold hover:text-vikinger-pink transition-colors"
      >
        <Icon icon="heroicons:heart" class="w-5 h-5" />
        {{ announcement.likes_count ?? 0 }}
      </button>
      <button
        @click="emit('open', announcement.id)"
        class="inline-flex items-center gap-1.5 font-semibold hover:text-vikinger-purple transition-colors"
      >
        <Icon icon="heroicons:chat-bubble-oval-left" class="w-5 h-5" />
        {{ announcement.comments_count ?? 0 }}
      </button>
      <button class="inline-flex items-center gap-1.5 font-semibold hover:text-vikinger-cyan transition-colors">
        <Icon icon="heroicons:share" class="w-5 h-5" />
        แชร์
      </button>
      <span
        v-if="announcement.reward_points"
        class="ml-auto inline-flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400"
      >
        <Icon icon="heroicons:sparkles-solid" class="w-3.5 h-3.5" />
        +{{ announcement.reward_points }} แต้ม
      </span>
    </div>
  </article>
</template>
```

**Notes:**
- ใช้ `ring-2 ring-vikinger-purple/60` แทน border เพื่อให้ shadow ติดอยู่ + ring แสดงทั้งสี่ด้าน
- Reward chip ปิดอัตโนมัติถ้า `reward_points` ไม่มีค่า — ไม่ต้องแก้ backend
- Click → emit `open` → parent navigate ไปหน้า detail

---

## D.2 — โหลด pinned announcements ใน `[name].vue`

**File:** `ui/pages/academies/[name].vue`

### D.2.1 — เพิ่ม state (หาที่ใกล้ `const activities = ref<any[]>([])` ราว line 24)

```ts
// Pinned announcements (Phase D)
const pinnedAnnouncements = ref<any[]>([])
const isLoadingPinned = ref(false)
```

### D.2.2 — เพิ่ม loader function (หาที่ใกล้ method อื่นๆ ที่โหลดข้อมูล tab)

```ts
const loadPinnedAnnouncements = async () => {
  if (!academy.value?.id) return
  isLoadingPinned.value = true
  try {
    const res: any = await api.call(
      `/api/academies/${academy.value.id}/announcements`,
      { params: { limit: 20 } } // เผื่อ pinned อาจปนกับ regular
    )
    const all = res?.data ?? []
    pinnedAnnouncements.value = all
      .filter((a: any) => a.is_pinned && a.is_published)
      .slice(0, 3)
  } catch (e) {
    pinnedAnnouncements.value = []
  } finally {
    isLoadingPinned.value = false
  }
}
```

### D.2.3 — เรียก loader หลัง academy โหลดเสร็จ

หา `fetchAcademy` (ราว line 152) — ตอนท้าย method หลัง `academy.value = ...` เพิ่ม:
```ts
loadPinnedAnnouncements() // fire-and-forget
```

---

## D.3 — Render ใน feed tab

**File:** `ui/pages/academies/[name].vue`
**Lines:** หา `<!-- Feed Tab -->` (ราว line 1057)

**ภายใน feed tab** — ก่อน `<PlayFeedCreatePostBox>` หรือ ก่อน FeedPost loop เพิ่ม:

```html
<!-- Pinned announcements (Phase D) -->
<SchoolPinnedAnnouncement
  v-for="ann in pinnedAnnouncements"
  :key="`pinned-${ann.id}`"
  :announcement="ann"
  @open="navigateToAnnouncement"
  @like="likeAnnouncement"
/>
```

### D.3.1 — เพิ่ม navigation handler
```ts
const navigateToAnnouncement = (id: number) => {
  // ถ้ามีหน้า detail แล้ว
  navigateTo(`/academies/${academyName.value}/announcements/${id}`)
}

const likeAnnouncement = async (id: number) => {
  // optional: optimistic update + API call
  // ถ้ายังไม่มี like endpoint — แค่ console.log ก่อน
  console.log('like announcement', id)
}
```

**Check route exists:**
```bash
ls "ui/pages/academies/[name]/announcements" 2>/dev/null
```
ถ้าไม่มี → expand inline แทน หรือเปิด modal — ตัดสินใจตามต้องการ

---

## D.4 — Refresh logic

เมื่อ admin ปักหมุด/ถอนปักหมุด announcement → ต้อง refresh:
- เพิ่ม watch หรือ event listener
- หรือเพิ่ม polling ทุก 60 วินาที (ถ้าต้องการ realtime ก็ใช้ Reverb)
- หรือเรียก `loadPinnedAnnouncements()` ตอน `switchTab('feed')`

**Simple approach** — เรียกซ้ำตอน switch กลับมา feed tab:
```ts
// ใน switchTab
if (tabId === 'feed') {
  loadPinnedAnnouncements()
}
```

---

## D.5 — Phase D Test Checklist

- [ ] เปิด phpMyAdmin → DB `nuxnan` → table `school_announcements` → INSERT ทดสอบ:
  - `academy_id` = id โรงเรียนที่เปิดอยู่
  - `created_by` = user id ผู้สร้าง
  - `title`, `content` = ข้อความทดสอบ
  - `announcement_type` = `general`
  - `priority` = `urgent`
  - `target_audience` = `["student","teacher"]` (JSON)
  - `is_pinned` = 1
  - `is_published` = 1
  - `published_at` = now()
- [ ] รีโหลดหน้า — เห็น pinned card สีม่วง ring อยู่บนสุดของ feed
- [ ] กดปุ่ม heart — emit event แล้วเห็น console log (Phase D ไม่ทำ backend like)
- [ ] กดที่ comment count — navigate ไป detail (ถ้ามีหน้า) หรือ 404
- [ ] เพิ่ม pinned ที่ 4-5 — แสดงแค่ 3 อันแรกตาม slice
- [ ] ตั้ง `is_pinned = 0` แล้ว refresh — card หาย

**Files touched in D:** 2 files
- NEW `ui/components/school/SchoolPinnedAnnouncement.vue`
- MODIFY `ui/pages/academies/[name].vue` (state + loader + render + switchTab hook)

**Commit suggestion:**
```
feat(school): render pinned announcements above academy feed
```

---

# Recap & Tips

## ลำดับ commit ที่แนะนำ (5 commits)

1. (✅ done) `refactor(academy): switch [name].vue to 3-column layout shell`
2. (✅ done) `feat(school): add quick menu, stat grid, upcoming events sidebars`
3. `style(academy): polish cover, add verified/level badges, tab counts, share button`
4. `feat(school): render pinned announcements above academy feed`

## Debugging tips

- **TypeError: Cannot read 'value' of undefined** — มาจาก `tabs.some()` ตอนเปลี่ยน tabs เป็น computed → ต้องใส่ `.value`
- **Sticky ไม่ติด** — เช็คว่า parent มี overflow-hidden อยู่ไหม (sticky ต้องไม่อยู่ใน overflow-hidden ancestor)
- **3-col grid ไม่ขึ้น** — เช็คว่าหน้าจอกว้างพอถึง `xl` (1280px); ลด Chrome devtools width ดู
- **SchoolXxx component ไม่เจอ** — Nuxt auto-import อาจ delay; restart `npm run dev`
- **pinned card ไม่ขึ้น** — เปิด Network tab ตรวจ response ของ `/announcements` ว่า `is_pinned` คืนมาเป็น boolean ไหม (อาจเป็น 0/1 ของ MySQL)

## Pitfalls ที่ผมเห็นล่วงหน้า

1. **C.6** เปลี่ยน `tabs` เป็น computed — มี side effect; ถ้าไม่อยากเสี่ยง → ใช้ helper function `getTabCount(id)` แทน แล้ว tabs ยังเป็น array นิ่ง
2. **C.5** Web Share API — บางเบราว์เซอร์ throw error ถ้า not HTTPS — try-catch ครอบไว้แล้ว
3. **D.0** Client-side filter — ถ้า academy หนึ่งมี announcement > 100 → ดึงมาทั้งหมดจะช้า; ต้องเพิ่ม backend filter (D.0 ทางเลือก 1)
4. **D.3** Auto-refresh — pinned ที่เพิ่งสร้างจะไม่ขึ้นจนกว่าจะ reload หน้า — เพิ่ม polling/realtime ถ้าจำเป็น

## ก่อนเริ่ม — รัน dev server ตรวจ Phase A+B ก่อน

```bash
cd ui
npm run dev
```
เปิด `http://localhost:3000/academies/{ชื่อโรงเรียน}` ดู 3 ขนาดจอ:
- 375 (mobile) — 1 col, ไม่มี sidebar
- 1100 (laptop) — 2 col (main + right)
- 1440 (desktop) — 3 col

ถ้า A+B ใช้ได้แล้วค่อยทำ C → D ตามลำดับ commit แยกย่อย

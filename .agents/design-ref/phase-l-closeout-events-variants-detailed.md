# Phase L — K Closeout + School Events in Feed + Post Variants

อ้างอิง: Phases C-D [✅], G-K [✅]
Target: ปิดงาน K ที่ค้าง + เติม post variants ให้ feed ตรง [School Homepage.html](./School Homepage.html) — director gradient / event card / embedded progress / target audience / reward chip
วันที่: 2026-06-20

---

## 📌 Scope Decision

ผมรวม Phase L = **K closeout + E (events) + post variants** เพื่อ:
- ปิดงานเล็กๆ ที่หล่นจาก K (2 จุด)
- เติม UI elements ที่ design ตัวอย่างมี แต่ feed ยังไม่มี

Gamification (school level/XP, classroom leaderboard) แยกเป็น **Phase M** เพราะรอ business rule

---

# 🗺️ Phase L Sub-phases Overview

| # | งาน | Priority | Est. |
|---|---|---|---|
| **L.0** | K closeout: mount NotificationBell ใน main.vue | 🟡 Polish | 15 min |
| **L.1** | K closeout: `appointed_by` set + display | 🟡 Polish | 30 min |
| **L.2** | Backend: `posts.post_type`, `target_audience`, `reward_points`, `embed_data` migration | 🔵 Feature | 1 hr |
| **L.3** | Backend: include school_events ใน feed via Activity polymorphic | 🔵 Feature | 1.5 hr |
| **L.4** | FeedPost: target audience line + reward chip | 🔵 Feature | 1 hr |
| **L.5** | FeedPost: Director gradient strip variant | 🔵 Feature | 30 min |
| **L.6** | FeedPost: Event card variant (date chip + register button) | 🔵 Feature | 1.5 hr |
| **L.7** | FeedPost: Embedded progress bar (attendance summary) | 🔵 Feature | 1 hr |
| **L.8** | Composer: post_type picker (announcement / event / regular) | 🔵 Feature | 1.5 hr |
| **L.9** | E2E smoke test 3 scenarios | 🟢 Test | 30 min |
| **รวม** | | | **~9 ชม.** |

---

# L.0 — Mount NotificationBell

**Goal:** ปิดงาน K.5 — ทำให้ bell แสดงใน navbar

**File:** `ui/layouts/main.vue`

ขั้นตอน:
1. หา navbar/header section (มี `to="/notifications"` link 5 จุดอยู่)
2. **เปลี่ยน 1 จุดในส่วน main desktop navbar เป็น** `<NotificationsNotificationBell />` แทน
3. คง mobile drawer link ไว้ (พื้นที่จำกัด ไม่เหมาะใส่ dropdown)

**Pattern:**
```html
<!-- Before -->
<NuxtLink
  to="/notifications"
  :class="[..., route.path === '/notifications' ? 'active' : '']"
>
  <Icon icon="heroicons:bell" />
</NuxtLink>

<!-- After (desktop navbar position) -->
<NotificationsNotificationBell v-if="user" />
```

> 💡 ปุ่ม bell มี dropdown ในตัว → คลิก dot ⋯ "ดูทั้งหมด" ในแถวล่าง dropdown → link `/notifications` page (legacy ยังใช้ได้)

**Test:**
- [ ] เปิดทุกหน้า → เห็น bell บน navbar
- [ ] มี notification ใหม่ → badge count แสดง
- [ ] คลิก → dropdown โผล่ + รายการ
- [ ] คลิก notification → mark read + navigate ตาม `action_url`

---

# L.1 — Appointment polish (K.7 closeout)

**Goal:** ใช้คอลัมน์ `appointed_by` ที่ K.0 เตรียมไว้

## L.1.1 — Set ตอน store

**File:** `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupAdminController.php`
**Method:** `store()`

```php
$admin = AcademyGroupAdmin::create([
    'academy_group_id' => $academyGroup->id,
    'user_id'          => $validated['user_id'],
    'role'             => $validated['role'] ?? 'leader',
    'appointed_by'     => auth()->id(),   // ⭐ NEW
])->load([
    'user:id,name,email,profile_photo_path',
    'appointer:id,name',                  // ⭐ NEW eager load
]);
```

## L.1.2 — Add relation

**File:** `api/nuxnanravel/app/Models/AcademyGroupAdmin.php`

```php
public function appointer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
{
    return $this->belongsTo(\App\Models\User::class, 'appointed_by');
}
```

## L.1.3 — Update `listAdmins` ให้ eager load

**File:** `AcademyGroupAdminController::index()`

```php
$admins = AcademyGroupAdmin::with([
    'user:id,name,profile_photo_path,email',
    'appointer:id,name',                  // ⭐ NEW
])->where('academy_group_id', $academyGroup->id)->get();
```

## L.1.4 — Display ใน UI

**File:** `ui/components/academy/groups/ManageTabAdmins.vue`

ใน admin list item เพิ่ม:
```vue
<div v-if="admin.appointer" class="text-[10px] text-gray-400 mt-0.5">
  แต่งตั้งโดย {{ admin.appointer.name }} · {{ relativeDate(admin.created_at) }}
</div>
```

helper:
```ts
const relativeDate = (iso?: string) => {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' })
}
```

---

# L.2 — Backend: post enhancement columns

**Goal:** เพิ่ม fields รองรับ post variants ตาม design

## L.2.1 — Migration

**File:** `2026_06_20_190000_add_variant_fields_to_academy_posts.php`

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academy_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('academy_posts', 'post_type')) {
                $table->string('post_type', 32)->default('regular')->after('content');
                // regular | announcement | event | director | attendance | achievement
                $table->index('post_type');
            }
            if (!Schema::hasColumn('academy_posts', 'target_audience')) {
                $table->json('target_audience')->nullable()->after('post_type');
                // ['student', 'teacher', 'parent', 'all']
            }
            if (!Schema::hasColumn('academy_posts', 'reward_points')) {
                $table->unsignedSmallInteger('reward_points')->default(0)->after('target_audience');
            }
            if (!Schema::hasColumn('academy_posts', 'embed_data')) {
                $table->json('embed_data')->nullable()->after('reward_points');
                // shape varies by post_type:
                // event:      { event_date, location, register_url, max_participants }
                // attendance: { current, total, label }
                // director:   { signer_name, signer_role }
            }
            if (!Schema::hasColumn('academy_posts', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('embed_data');
                $table->index(['academy_id', 'is_pinned']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('academy_posts', function (Blueprint $table) {
            $table->dropColumn(['post_type', 'target_audience', 'reward_points', 'embed_data', 'is_pinned']);
        });
    }
};
```

## L.2.2 — Model casts

**File:** `app/Models/AcademyPost.php`

```php
protected $casts = [
    'target_audience' => 'array',
    'embed_data'      => 'array',
    'is_pinned'       => 'boolean',
];
```

## L.2.3 — Validation in StorePostRequest / controller

ใน `AcademyPostController::store` เพิ่ม:
```php
'post_type'         => 'nullable|string|in:regular,announcement,event,director,attendance,achievement',
'target_audience'   => 'nullable|array',
'target_audience.*' => 'string|in:student,teacher,parent,all',
'reward_points'     => 'nullable|integer|min:0|max:999',
'embed_data'        => 'nullable|array',
'is_pinned'         => 'nullable|boolean',
```

แล้ว assign ลง model ตามปกติ

## L.2.4 — Resource update

**File:** `AcademyPostResource.php`
เพิ่มใน toArray():
```php
'post_type'       => $this->post_type ?? 'regular',
'target_audience' => $this->target_audience,
'reward_points'   => $this->reward_points,
'embed_data'      => $this->embed_data,
'is_pinned'       => $this->is_pinned,
```

---

# L.3 — Backend: School events ใน feed

**Goal:** ให้ `school_events` แสดงในฟีดเหมือนโพสต์ (ตรงตาม design — event card "นวัตกรรมเกมส์ 2569")

**ทางเลือก:**
- **A** — Polymorphic Activity: ให้ `SchoolEvent` เป็น activityable อีก type หนึ่ง
- **B** — Mirror events to posts: เมื่อสร้าง event → auto-create AcademyPost เชื่อมกัน (`post_type = 'event'`, embed_data = event details)
- **C** — Multi-source feed: backend คืน 2 streams รวมกัน

### 🏆 เลือก: ทาง B — auto-mirror (เหตุผล):
- ใช้ feed pipeline เดิมไม่ต้องแก้
- post_type variant ของ L.4-L.7 ใช้กับ event ได้ทันที
- ลด complexity ฝั่ง frontend (1 stream)
- ตอน event update/cancel → update mirror post ตาม

## L.3.1 — Event → Post mirror service

**File:** `app/Services/EventToPostMirror.php` (NEW)

```php
<?php

namespace App\Services;

use App\Models\AcademyPost;
use App\Models\Activity;
use App\Models\SchoolEvent;

class EventToPostMirror
{
    public function mirror(SchoolEvent $event): AcademyPost
    {
        $post = AcademyPost::updateOrCreate(
            ['academy_id' => $event->academy_id, 'embed_data->event_id' => $event->id],
            [
                'user_id'         => $event->created_by,
                'content'         => $event->description ?? $event->title,
                'post_type'       => 'event',
                'target_audience' => $event->target_audience,
                'embed_data'      => [
                    'event_id'        => $event->id,
                    'event_title'     => $event->title,
                    'event_date'      => $event->start_datetime?->toIso8601String(),
                    'event_end'       => $event->end_datetime?->toIso8601String(),
                    'location'        => $event->location,
                    'event_type'      => $event->event_type,
                    'requires_register' => $event->requires_registration ?? false,
                ],
            ]
        );

        // Wire Activity polymorphic for feed
        Activity::firstOrCreate(
            ['activityable_type' => AcademyPost::class, 'activityable_id' => $post->id],
            [
                'user_id'       => $post->user_id,
                'activity_type' => 'event_published',
            ]
        );

        return $post;
    }

    public function unmirror(SchoolEvent $event): void
    {
        AcademyPost::where('academy_id', $event->academy_id)
            ->where('embed_data->event_id', $event->id)
            ->delete();
    }
}
```

## L.3.2 — Hook into SchoolEventController

```php
public function publish(SchoolEvent $event)
{
    $event->update(['status' => 'published', 'published_at' => now()]);
    app(EventToPostMirror::class)->mirror($event);
    return response()->json(['success' => true]);
}

public function cancel(SchoolEvent $event)
{
    $event->update(['status' => 'cancelled']);
    app(EventToPostMirror::class)->unmirror($event);
    return response()->json(['success' => true]);
}
```

(Apply ทำนองเดียวกันกับ store/update/destroy)

## L.3.3 — Backfill seeder

**File:** `database/seeders/MirrorExistingEventsSeeder.php`

```php
public function run(EventToPostMirror $mirror): void
{
    SchoolEvent::where('status', 'published')->chunk(50, function ($events) use ($mirror) {
        foreach ($events as $e) $mirror->mirror($e);
    });
}
```

```bash
php artisan db:seed --class=MirrorExistingEventsSeeder
```

---

# L.4 — FeedPost: Target audience + reward chip

**File:** `ui/components/play/feed/FeedPost.vue`

## L.4.1 — Computed `audienceLabel`

```ts
const audienceLabel = computed(() => {
  const arr: string[] = postData.value?.target_audience || []
  if (!arr.length || arr.includes('all')) return null
  const map: Record<string, string> = {
    student: 'นักเรียน', teacher: 'ครู', parent: 'ผู้ปกครอง', staff: 'บุคลากร',
  }
  return arr.map(a => map[a] || a).join(' · ')
})
```

## L.4.2 — Render line (ใต้ content, ก่อน footer)

```vue
<div
  v-if="audienceLabel"
  class="mt-2 inline-flex items-center gap-1.5 text-xs text-gray-500"
>
  <Icon icon="heroicons:user-group" class="w-4 h-4" />
  <span>กลุ่มเป้าหมาย: {{ audienceLabel }}</span>
</div>
```

## L.4.3 — Reward chip ใน footer

หา footer ของ post (มี like / comment / share):
```vue
<span
  v-if="postData?.reward_points && postData.reward_points > 0"
  class="ml-auto inline-flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400"
>
  <Icon icon="heroicons:sparkles-solid" class="w-3.5 h-3.5" />
  +{{ postData.reward_points }} แต้ม
</span>
```

> 💡 ใช้ `ml-auto` ดัน chip ไปขวาสุดของ footer ตามที่ design มี

---

# L.5 — Director gradient strip variant

**Goal:** โพสต์ที่ `post_type === 'director'` มีแถบ gradient ด้านบน + Badge "ฝ่ายบริหาร"

**File:** `ui/components/play/feed/FeedPost.vue`

## L.5.1 — Computed

```ts
const isDirector = computed(() => postData.value?.post_type === 'director')
```

## L.5.2 — Render strip + badge

ใส่ที่ด้านบนของ article ก่อน header row:
```vue
<div v-if="isDirector" class="h-1 bg-gradient-to-r from-vikinger-purple via-vikinger-cyan to-vikinger-purple -mx-4 -mt-4 mb-3 md:-mx-5 md:-mt-5"></div>
```

ใน badge zone ใต้ avatar name:
```vue
<span v-if="isDirector" class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-vikinger-purple/15 text-vikinger-purple">
  ฝ่ายบริหาร
</span>
```

---

# L.6 — Event card variant

**Goal:** โพสต์ `post_type === 'event'` แสดง date chip + เวลา + สถานที่ + ปุ่ม "ลงทะเบียน"

**File:** `ui/components/play/feed/FeedPost.vue`

## L.6.1 — Computed

```ts
const eventData = computed(() => {
  if (postData.value?.post_type !== 'event') return null
  const d = postData.value?.embed_data || {}
  if (!d.event_date) return null
  const date = new Date(d.event_date)
  return {
    ...d,
    dateObj: date,
    day: String(date.getDate()).padStart(2, '0'),
    monthShort: ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'][date.getMonth()],
    timeRange: d.event_end
      ? `${formatTime(d.event_date)} - ${formatTime(d.event_end)}`
      : formatTime(d.event_date),
  }
})

const formatTime = (iso: string) => new Date(iso).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })

const isEvent = computed(() => eventData.value !== null)
```

## L.6.2 — Render block (ใต้ content)

```vue
<div
  v-if="isEvent"
  class="mt-3 flex items-center gap-4 p-3.5 rounded-xl bg-gray-50 dark:bg-vikinger-dark-100 border border-gray-200 dark:border-gray-700"
>
  <!-- Date chip -->
  <div class="w-14 text-center rounded-lg overflow-hidden shadow-sm flex-shrink-0">
    <div class="bg-vikinger-purple text-white font-bold text-lg py-1">{{ eventData.day }}</div>
    <div class="bg-white text-gray-700 text-xs font-bold py-1">{{ eventData.monthShort }}</div>
  </div>

  <div class="flex-1 min-w-0">
    <!-- Time + location -->
    <div class="flex flex-wrap gap-3 text-xs text-gray-600 dark:text-gray-400 mb-2">
      <span class="inline-flex items-center gap-1">
        <Icon icon="heroicons:clock" class="w-4 h-4" />
        {{ eventData.timeRange }}
      </span>
      <span v-if="eventData.location" class="inline-flex items-center gap-1">
        <Icon icon="heroicons:map-pin" class="w-4 h-4" />
        {{ eventData.location }}
      </span>
    </div>

    <!-- Register button -->
    <button
      v-if="eventData.requires_register"
      type="button"
      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-vikinger-purple text-white text-xs font-semibold hover:bg-vikinger-purple/90"
      @click="onRegisterEvent"
    >
      <Icon icon="heroicons:pencil-square" class="w-3.5 h-3.5" />
      ลงทะเบียน
    </button>
  </div>
</div>
```

## L.6.3 — Handler

```ts
const onRegisterEvent = async () => {
  if (!eventData.value?.event_id) return
  try {
    await $fetch(`/api/academies/${academyName.value}/events/${eventData.value.event_id}/enroll`, {
      method: 'POST',
      // include auth header via $fetch helper
    })
    // toast success
  } catch (e: any) {
    // toast error
  }
}
```

---

# L.7 — Embedded ProgressBar variant

**Goal:** โพสต์ `post_type === 'attendance'` มี progress bar inline (เช่น "เข้าร่วม 1,180 / 1,248 — 95%")

**File:** `ui/components/play/feed/FeedPost.vue`

## L.7.1 — Computed

```ts
const progressData = computed(() => {
  if (postData.value?.post_type !== 'attendance') return null
  const d = postData.value?.embed_data
  if (!d?.current || !d?.total) return null
  const pct = Math.round((d.current / d.total) * 100)
  return { ...d, pct }
})
```

## L.7.2 — Render block

```vue
<div
  v-if="progressData"
  class="mt-3 p-3.5 rounded-xl bg-gray-50 dark:bg-vikinger-dark-100 border border-gray-200 dark:border-gray-700"
>
  <div class="flex justify-between items-baseline mb-2">
    <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">
      {{ progressData.label || 'เข้าร่วม' }}
    </span>
    <span class="text-sm font-bold text-gray-900 dark:text-white">
      {{ progressData.current.toLocaleString() }}
      <span class="text-xs text-gray-400">/ {{ progressData.total.toLocaleString() }}</span>
    </span>
  </div>
  <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
    <div
      class="h-full bg-gradient-to-r from-green-500 to-emerald-400"
      :style="{ width: `${progressData.pct}%` }"
    ></div>
  </div>
  <div class="text-right text-[10px] text-gray-400 mt-1">{{ progressData.pct }}%</div>
</div>
```

---

# L.8 — Composer: post type picker

**Goal:** ตอน admin สร้างโพสต์ใน academy → เลือก post_type ได้

**File:** `ui/components/play/feed/CreatePostModal.vue`

## L.8.1 — Computed permission (admin only)

```ts
const canSetType = computed(() =>
  // pass `isAcademyAdmin` prop จาก parent
  props.isAcademyAdmin === true
)
```

## L.8.2 — Picker UI

ใน form ก่อน submit button:
```vue
<div v-if="canSetType && context === 'academy'" class="mb-3">
  <label class="block text-xs font-semibold text-gray-600 mb-2">ประเภทโพสต์</label>
  <div class="grid grid-cols-3 gap-2">
    <button
      v-for="t in [
        { key: 'regular',      label: 'โพสต์ทั่วไป',  icon: 'heroicons:chat-bubble-left' },
        { key: 'announcement', label: 'ประกาศ',       icon: 'heroicons:megaphone' },
        { key: 'event',        label: 'กิจกรรม',      icon: 'heroicons:calendar-days' },
        { key: 'director',     label: 'ฝ่ายบริหาร',  icon: 'heroicons:user-circle' },
        { key: 'attendance',   label: 'สรุปการเข้าร่วม', icon: 'heroicons:chart-bar' },
        { key: 'achievement',  label: 'ผลงาน',        icon: 'heroicons:trophy' },
      ]"
      :key="t.key"
      type="button"
      :class="[
        'p-2 rounded-lg border-2 flex items-center gap-2 text-xs font-semibold transition-all',
        postType === t.key
          ? 'border-vikinger-purple bg-vikinger-purple/10 text-vikinger-purple'
          : 'border-gray-200 text-gray-600'
      ]"
      @click="postType = t.key"
    >
      <Icon :icon="t.icon" class="w-4 h-4" />
      {{ t.label }}
    </button>
  </div>
</div>
```

## L.8.3 — Target audience + reward (optional advanced section)

```vue
<details v-if="canSetType" class="mb-3 text-xs">
  <summary class="cursor-pointer font-semibold text-gray-600">ตั้งค่าเพิ่มเติม</summary>
  <div class="mt-3 space-y-3">
    <!-- audience -->
    <div>
      <label class="block mb-1">กลุ่มเป้าหมาย</label>
      <div class="flex flex-wrap gap-2">
        <label v-for="a in ['student','teacher','parent','all']" :key="a" class="inline-flex items-center gap-1">
          <input type="checkbox" :value="a" v-model="targetAudience" />
          {{ {student:'นักเรียน',teacher:'ครู',parent:'ผู้ปกครอง',all:'ทุกคน'}[a] }}
        </label>
      </div>
    </div>
    <!-- reward -->
    <div>
      <label class="block mb-1">แต้มรางวัล (0-999)</label>
      <input v-model.number="rewardPoints" type="number" min="0" max="999" class="w-24 px-2 py-1 border rounded" />
    </div>
  </div>
</details>
```

## L.8.4 — Submit ส่ง fields

```ts
formData.append('post_type', postType.value)
if (targetAudience.value.length) {
  for (const a of targetAudience.value) formData.append('target_audience[]', a)
}
formData.append('reward_points', String(rewardPoints.value || 0))
```

---

# L.9 — E2E smoke test

## Scenario 1 — Notification bell live
1. Login user A → เห็น bell + count
2. Login user B (อีก browser/incognito) → invite A เข้ากลุ่ม
3. User A: รอ < 60s → count เพิ่ม + dropdown มีรายการใหม่
4. คลิก → mark read + navigate ไปหน้ากลุ่ม

## Scenario 2 — Director announcement
1. Login academy admin → main feed composer
2. เลือก post_type = director + content "สารจากผู้อำนวยการ..."
3. เลือก target_audience = all + reward = 5
4. โพสต์ → feed แสดง: gradient strip บน + badge "ฝ่ายบริหาร" + "กลุ่มเป้าหมาย: ทุกคน" + chip "+5 แต้ม"

## Scenario 3 — Event auto-mirror
1. Login admin → สร้าง school_event "กีฬาสีภายใน" + publish
2. กลับมา main feed → เห็น event card อัตโนมัติ (date chip + เวลา + สถานที่ + "ลงทะเบียน")
3. คลิก "ลงทะเบียน" → enroll สำเร็จ + toast
4. Cancel event → mirror post หาย

---

# 📋 Phase L — Files Summary

## ✨ New files (3)
```
api/nuxnanravel/database/migrations/{ts}_add_variant_fields_to_academy_posts.php
api/nuxnanravel/app/Services/EventToPostMirror.php
api/nuxnanravel/database/seeders/MirrorExistingEventsSeeder.php
```

## 🔧 Modified files (~7)
```
ui/layouts/main.vue                                                          (L.0 mount bell)
api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupAdminController.php  (L.1)
api/nuxnanravel/app/Models/AcademyGroupAdmin.php                              (L.1 appointer relation)
ui/components/academy/groups/ManageTabAdmins.vue                              (L.1 display)
api/nuxnanravel/app/Models/AcademyPost.php                                    (L.2 casts)
api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyPostController.php (L.2 validation + store)
api/nuxnanravel/app/Http/Resources/Learn/Academy/AcademyPostResource.php      (L.2 fields)
api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/SchoolEventController.php  (L.3 hooks)
ui/components/play/feed/FeedPost.vue                                          (L.4-L.7 variants)
ui/components/play/feed/CreatePostModal.vue                                   (L.8 type picker)
```

---

# 🛣️ Commit plan (6 commits)

```
1. fix(ui): mount NotificationBell in main layout                  (L.0)
2. feat(academy): track appointed_by on group admin                (L.1)
3. feat(db): add post variant columns to academy_posts             (L.2)
4. feat(academy): mirror school_events into feed as event posts    (L.3)
5. feat(ui): render post variants in FeedPost                      (L.4-L.7)
6. feat(ui): post type picker in composer (admin only)             (L.8)
```

---

# ✅ Phase L — Test Checklist

## L.0 Bell mount
- [ ] เห็น bell บน navbar ทุกหน้า
- [ ] Badge count แสดงเมื่อมี unread
- [ ] กดเปิด dropdown → list + mark read

## L.1 Appointment
- [ ] เพิ่ม group admin ใหม่ → DB `appointed_by` = current user id
- [ ] ManageTabAdmins แสดง "แต่งตั้งโดย {ชื่อ} · {วันที่}"

## L.2 Post columns
- [ ] migration run สำเร็จ → 5 columns ใหม่
- [ ] POST academy_post + post_type → DB save ถูก
- [ ] target_audience array, embed_data json cast ทำงาน

## L.3 Event mirror
- [ ] publish event → 1 academy_post auto-create + 1 activity record
- [ ] cancel event → mirror post หาย
- [ ] backfill seeder mirror events เก่าทั้งหมด

## L.4-L.7 FeedPost variants
- [ ] post_type=director → gradient strip + ฝ่ายบริหาร badge
- [ ] post_type=event → date chip + เวลา + ลงทะเบียน
- [ ] post_type=attendance → progress bar
- [ ] target_audience → "กลุ่มเป้าหมาย:" line
- [ ] reward_points > 0 → "+X แต้ม" chip ที่ footer

## L.8 Composer
- [ ] Admin เห็น type picker 6 ปุ่ม
- [ ] non-admin ไม่เห็น
- [ ] เลือก + โพสต์ → DB ถูก
- [ ] advanced section: audience + reward save ได้

## Regression
- [ ] โพสต์ทั่วไป (post_type=regular) ยังแสดงเหมือนเดิม
- [ ] โพสต์ในนามกลุ่ม (Phase J) ยังใช้ได้
- [ ] Pinned announcement (Phase D) ยังใช้ได้

---

# ⚠️ Pitfalls & Notes

## 1. JSON column query (L.3 mirror)
- `where('embed_data->event_id', $event->id)` ใช้ MySQL JSON operator
- ถ้า MySQL < 5.7 → ใช้ workaround หรือเก็บ event_id เป็น column แยก
- ทดสอบใน WAMP version ที่ใช้

## 2. Mirror timing
- ถ้า user แก้ event ผ่าน controller update → ต้อง re-mirror
- เพิ่ม `EventToPostMirror::mirror($event)` ใน `update()` ด้วย

## 3. Dual feed dedup
- `school_events` ที่ status = published เท่านั้นที่ mirror
- draft/cancelled → ไม่มี post → feed ไม่เห็น

## 4. Admin permission for post_type
- L.8 limit picker เฉพาะ academy admin
- backend ก็ต้อง enforce: ถ้า non-admin ส่ง `post_type !== 'regular'` → reject หรือ silently downgrade

## 5. NotificationBell z-index ใน mobile drawer
- ถ้า main.vue มี drawer overlay → bell dropdown ต้อง z สูงพอ
- ถ้า conflict → mount bell ใน position absolute แยก stacking context

## 6. Auto-mirror loop
- ถ้า frontend POST academy_post พร้อม `embed_data.event_id` → อาจ mirror ทับซ้อน
- `updateOrCreate` ด้วย unique key (`academy_id` + `embed_data->event_id`) → idempotent ✅

## 7. Mass-assignment
- AcademyPost ใช้ `$guarded = []` (จาก Phase G verification) — fields ใหม่ mass-assign ได้ทันที
- ถ้าเปลี่ยนเป็น `$fillable` → ต้องเพิ่ม 5 fields

## 8. Test data setup
```sql
-- ตั้ง post ตัวอย่าง 3 variant ใน DB เพื่อ test UI
UPDATE academy_posts SET post_type='director', target_audience='["all"]', reward_points=5 WHERE id=X;
UPDATE academy_posts SET post_type='attendance', embed_data='{"current":1180,"total":1248,"label":"เข้าร่วมกิจกรรมหน้าเสาธง"}' WHERE id=Y;
```

---

# 🎯 ลำดับงานแนะนำ

```
1. L.0 (15 นาที) → ปิดงาน K ที่ค้าง เห็นผลทันที
2. L.1 (30 นาที) → appointment trail
3. L.2 (1 ชม.) → migration + casts → foundation
4. L.4 (1 ชม.) → audience + reward → simplest variant ทดสอบเร็ว
5. L.5 (30 นาที) → director strip
6. L.6 (1.5 ชม.) → event card (ใหญ่สุดของ variant)
7. L.7 (1 ชม.) → progress
8. L.8 (1.5 ชม.) → composer picker
9. L.3 (1.5 ชม.) → event mirror (ทำหลังเพราะ depend on L.6)
10. L.9 (30 นาที) → E2E test
```

หลัง L เสร็จ → feed ของระบบจะ "ดูเหมือน design" ใน [School Homepage.html](./School Homepage.html) ตรงทุก variant 🎯

**Phase M** (Gamification — school level + classroom leaderboard) จะรอ business rule ตามแผนเดิม

ติดตรงไหนตอนทำมาถามได้เลยครับ 🙌

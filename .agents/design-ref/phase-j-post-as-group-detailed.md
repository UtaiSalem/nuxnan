# Phase J — Post-as-Group (Composer + Feed Header) — Detailed DIY Plan

อ้างอิง: [decisions](./school-departments-decisions.md) + Phase [G ✅](./phase-g-backend-detailed.md) [H ✅](./phase-h-manage-ui-detailed.md) [I ✅](./phase-i-group-profile-detailed.md)
Target: ให้สมาชิก/หัวหน้าของส่วนงานเลือกโพสต์ "ในนามส่วนงาน" ได้จาก **main academy feed** + ให้ feed แสดง avatar/ชื่อกลุ่มแทน user

วันที่: 2026-06-20

---

## 📌 Pre-check ที่ผมยืนยันให้แล้ว

| ข้อเท็จจริง | ผลกระทบ |
|---|---|
| `CreatePostBox` + `CreatePostModal` รับ `postedAsGroupId` prop แล้ว (Phase I) | reuse — แค่ใส่ UI selector |
| Phase G validate: user ต้องเป็น admin/member + group มี `can_post = true` | backend block แล้ว — frontend แค่ filter list |
| `FeedPost.vue` มี computed `postAuthor` ที่ map `post.author || post.user` | extend: ถ้า `post.posted_as_group` มีค่า → override |
| `AcademyPostResource` คืน `posted_as_group: { id, name, type, type_meta }` (Phase G G.4) | frontend ใช้ key นี้ตรงๆ |
| Academy main composer (`[name].vue:1195`) เรียก `<PlayFeedCreatePostBox>` ใน feed tab | จุดที่ต้อง mount selector |
| ❌ **ยังไม่มี endpoint** "กลุ่มที่ user โพสต์ได้ในโรงเรียนนี้" | J.0 จะเพิ่ม |

---

# 🗺️ Phase J Sub-phases Overview

| # | งาน | Est. |
|---|---|---|
| **J.0** | Backend: endpoint `/academies/{academy}/postable-groups` | 45 min |
| **J.1** | Extend `useAcademyGroups`: `getPostableGroups` + cache | 30 min |
| **J.2** | `PostAsSelector.vue` — chip + dropdown component (shared) | 1.5 hr |
| **J.3** | Wire selector ใน `CreatePostBox` (trigger area) | 30 min |
| **J.4** | Wire selector ใน `CreatePostModal` (full form) + state sync | 1 hr |
| **J.5** | `FeedPost.vue`: render group header when `posted_as_group` exists | 1 hr |
| **J.6** | `FeedPost.vue`: "โดย {user.name}" credit line (admin/member only) | 30 min |
| **J.7** | Group profile page: ลด selector (default = group นั้น lock) | 15 min |
| **J.8** | Edge cases & polish | 30 min |
| **รวม** | | **~6 ชม.** |

---

# J.0 — Backend: postable-groups endpoint

**Goal:** คืน list ของส่วนงานในโรงเรียนนี้ที่ user ปัจจุบันมีสิทธิ์โพสต์

## J.0.1 — เพิ่ม method ใน `AcademyGroupController`

**File:** `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupController.php`

```php
/**
 * GET /academies/{academy}/postable-groups
 * Returns groups within this academy where the current user is admin/member
 * AND the group has can_post = true.
 */
public function postableForUser(\Illuminate\Http\Request $request, \App\Models\Academy $academy)
{
    $userId = $request->user()?->id;
    if (!$userId) {
        return response()->json(['success' => true, 'data' => []]);
    }

    // 1. group ids ที่ user เป็น admin
    $adminGroupIds = \App\Models\AcademyGroupAdmin::where('user_id', $userId)
        ->pluck('academy_group_id');

    // 2. group ids ที่ user เป็น member
    $memberGroupIds = \App\Models\AcademyGroupMember::where('user_id', $userId)
        ->pluck('academy_group_id');

    $candidateIds = $adminGroupIds->merge($memberGroupIds)->unique();

    if ($candidateIds->isEmpty()) {
        return response()->json(['success' => true, 'data' => []]);
    }

    // 3. filter: must belong to this academy AND have can_post enabled
    $postableIds = \App\Models\AcademyGroupPermission::whereIn('academy_group_id', $candidateIds)
        ->where('permission_key', 'can_post')
        ->where('enabled', true)
        ->pluck('academy_group_id');

    $groups = \App\Models\AcademyGroup::whereIn('id', $postableIds)
        ->where('academy_id', $academy->id)
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get(['id', 'name', 'type', 'description']);

    // attach role (admin/member) per group สำหรับ UI ใส่ badge
    $roleMap = [];
    foreach ($adminGroupIds as $gid) $roleMap[$gid] = 'admin';
    foreach ($memberGroupIds as $gid) {
        if (!isset($roleMap[$gid])) $roleMap[$gid] = 'member';
    }

    $payload = $groups->map(function ($g) use ($roleMap) {
        return [
            'id'        => $g->id,
            'name'      => $g->name,
            'type'      => $g->type,
            'type_meta' => \App\Constants\AcademyGroupTypes::get($g->type),
            'role'      => $roleMap[$g->id] ?? null,
        ];
    });

    return response()->json(['success' => true, 'data' => $payload]);
}
```

## J.0.2 — Register route

**File:** `api/nuxnanravel/routes/learn/academy.php`
**Location:** ใต้ routes ของ `/{academy}/feeds` หรือใกล้ๆ ใน `auth:api` group

```php
Route::get('/{academy}/postable-groups', [AcademyGroupController::class, 'postableForUser'])
    ->name('api.academy.postableGroups');
```

ทำซ้ำใน 2 group scope ตามโครงไฟล์ routes (ถ้ามี duplicate scope)

## J.0.3 — Verify

```bash
php artisan route:list | grep "postable-groups"
# ควรเห็น 1-2 routes
```

```bash
# Test ด้วย token user
curl -H "Authorization: Bearer {token}" \
  http://localhost:8000/api/academies/1/postable-groups
# Expected: { success: true, data: [{ id, name, type, type_meta, role }] }
# user ที่ไม่อยู่ใน group ไหน → data: []
# user ที่อยู่ใน group แต่ can_post = false → ไม่อยู่ใน list
```

---

# J.1 — Extend `useAcademyGroups`

**File:** `ui/composables/useAcademyGroups.ts`

เพิ่ม method + cache เพื่อไม่โหลดซ้ำ:

```ts
// Module-level cache (key = academyId, value = postable groups)
// อยู่นอก function เพื่อ persist ข้าม component instances
const postableCache = new Map<number | string, any[]>()

// ในตัว useAcademyGroups():
const getPostableGroups = async <T = any>(
  academyId: number | string,
  options?: { forceRefresh?: boolean },
): Promise<T[]> => {
  if (!options?.forceRefresh && postableCache.has(academyId)) {
    return postableCache.get(academyId) as T[]
  }
  try {
    const res: any = await api.call(`/api/academies/${academyId}/postable-groups`)
    const data = (res?.data ?? []) as T[]
    postableCache.set(academyId, data as any[])
    return data
  } catch {
    return []
  }
}

const invalidatePostableCache = (academyId?: number | string) => {
  if (academyId != null) postableCache.delete(academyId)
  else postableCache.clear()
}
```

อย่าลืม export ใน return block:
```ts
return {
  // ... existing
  getPostableGroups,
  invalidatePostableCache,
}
```

> 💡 **เมื่อใดควร invalidate?**
> - ตอนเปิด/ปิด `can_post` ใน `GroupManageModal` → call `invalidatePostableCache(academyId)`
> - ตอน user เปิดส่วนงานใหม่และเพิ่มสมาชิก (ตัวเอง) → invalidate

---

# J.2 — `PostAsSelector.vue` (shared component)

**File:** `ui/components/academy/groups/PostAsSelector.vue` (NEW)

**สาระ:**
- Chip แสดง "โพสต์ในนาม: {ชื่อปัจจุบัน}" + dropdown arrow
- คลิกเปิด dropdown รายการ
- option "ตัวเอง" (default) + รายการกลุ่ม
- ใส่ icon + badge role (admin/member) ในแต่ละรายการ
- emit `update:selection` (model value)

```vue
<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { Icon } from '@iconify/vue'
import { getAcademyGroupTypeMeta, GROUP_TYPE_COLOR_CLASSES } from '~/constants/academyGroupTypes'

interface PostableGroup {
  id: number
  name: string
  type: string
  role?: 'admin' | 'member' | null
}

interface Props {
  academyId: number | string
  /** Currently selected group id, or null = post as self */
  modelValue?: number | null
  /** Hide group selector and lock to this group (used in group profile page) */
  lockedGroupId?: number | null
  /** Current user (for "self" label/avatar) */
  user: { id: number; name: string; profile_photo_path?: string | null } | null
  /** Compact = inline chip (CreatePostBox), full = button row (CreatePostModal) */
  variant?: 'compact' | 'full'
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  lockedGroupId: null,
  variant: 'full',
})

const emit = defineEmits<{
  'update:modelValue': [value: number | null]
}>()

const { getPostableGroups } = useAcademyGroups()

const postable = ref<PostableGroup[]>([])
const isLoading = ref(false)
const isOpen = ref(false)
const triggerRef = ref<HTMLElement | null>(null)

const selectedGroup = computed<PostableGroup | null>(() => {
  if (props.modelValue == null) return null
  return postable.value.find((g) => g.id === props.modelValue) ?? null
})

const lockedGroup = computed<PostableGroup | null>(() => {
  if (props.lockedGroupId == null) return null
  return postable.value.find((g) => g.id === props.lockedGroupId) ?? null
})

const load = async () => {
  isLoading.value = true
  try {
    postable.value = await getPostableGroups(props.academyId)
  } finally {
    isLoading.value = false
  }
}

const select = (id: number | null) => {
  emit('update:modelValue', id)
  isOpen.value = false
}

const close = (e: MouseEvent) => {
  if (!triggerRef.value) return
  if (!triggerRef.value.contains(e.target as Node)) isOpen.value = false
}

onMounted(() => {
  load()
  document.addEventListener('click', close)
})
onBeforeUnmount(() => document.removeEventListener('click', close))

const roleLabel: Record<string, string> = { admin: 'หัวหน้า', member: 'สมาชิก' }
</script>

<template>
  <div ref="triggerRef" class="relative">
    <!-- Locked state: just show the chip, no toggle -->
    <div
      v-if="lockedGroup"
      class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-vikinger-purple/10 text-vikinger-purple"
    >
      <Icon :icon="getAcademyGroupTypeMeta(lockedGroup.type).icon" class="w-3.5 h-3.5" />
      โพสต์ในนาม {{ lockedGroup.name }}
    </div>

    <!-- Selectable trigger -->
    <button
      v-else
      type="button"
      :class="[
        'inline-flex items-center gap-2 rounded-full font-semibold transition-colors',
        variant === 'compact' ? 'px-2.5 py-1 text-[11px]' : 'px-3 py-1.5 text-xs',
        selectedGroup
          ? 'bg-vikinger-purple/10 text-vikinger-purple'
          : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200',
      ]"
      @click.stop="isOpen = !isOpen"
    >
      <Icon
        v-if="selectedGroup"
        :icon="getAcademyGroupTypeMeta(selectedGroup.type).icon"
        class="w-3.5 h-3.5"
      />
      <Icon v-else icon="heroicons:user" class="w-3.5 h-3.5" />
      <span>
        โพสต์ในนาม:
        <b>{{ selectedGroup ? selectedGroup.name : (user?.name || 'ฉัน') }}</b>
      </span>
      <Icon icon="heroicons:chevron-down" class="w-3 h-3 opacity-70" />
    </button>

    <!-- Dropdown -->
    <div
      v-if="isOpen && !lockedGroup"
      class="absolute z-30 mt-2 w-72 bg-white dark:bg-vikinger-dark-100 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
    >
      <div class="px-3 py-2 border-b border-gray-100 dark:border-gray-700 text-[11px] font-bold uppercase text-gray-400">
        เลือกผู้โพสต์
      </div>

      <!-- Self -->
      <button
        type="button"
        class="w-full flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 text-left"
        :class="modelValue === null && 'bg-vikinger-purple/5'"
        @click="select(null)"
      >
        <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
          <img v-if="user?.profile_photo_path" :src="user.profile_photo_path" class="w-full h-full object-cover" />
          <Icon v-else icon="heroicons:user" class="w-full h-full p-2 text-gray-400" />
        </div>
        <div class="min-w-0 flex-1">
          <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
            {{ user?.name || 'ฉัน' }}
          </div>
          <div class="text-[11px] text-gray-500">โพสต์เป็นตัวเอง</div>
        </div>
        <Icon
          v-if="modelValue === null"
          icon="heroicons:check-circle-solid"
          class="w-5 h-5 text-vikinger-purple flex-shrink-0"
        />
      </button>

      <!-- Group options -->
      <div v-if="isLoading" class="px-3 py-3 text-center text-xs text-gray-500">
        กำลังโหลด...
      </div>
      <div v-else-if="postable.length === 0" class="px-3 py-3 text-center text-xs text-gray-500">
        คุณยังไม่มีส่วนงานที่โพสต์ได้
      </div>
      <button
        v-for="g in postable"
        :key="g.id"
        type="button"
        class="w-full flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 text-left"
        :class="modelValue === g.id && 'bg-vikinger-purple/5'"
        @click="select(g.id)"
      >
        <div
          :class="[
            'w-9 h-9 rounded-lg bg-gradient-to-br flex items-center justify-center flex-shrink-0',
            GROUP_TYPE_COLOR_CLASSES[getAcademyGroupTypeMeta(g.type).color].gradient,
          ]"
        >
          <Icon :icon="getAcademyGroupTypeMeta(g.type).icon" class="w-4 h-4 text-white" />
        </div>
        <div class="min-w-0 flex-1">
          <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
            {{ g.name }}
          </div>
          <div class="text-[11px] text-gray-500">
            {{ getAcademyGroupTypeMeta(g.type).label }}
            <span v-if="g.role" class="mx-1 text-gray-300">·</span>
            <span v-if="g.role">{{ roleLabel[g.role] }}</span>
          </div>
        </div>
        <Icon
          v-if="modelValue === g.id"
          icon="heroicons:check-circle-solid"
          class="w-5 h-5 text-vikinger-purple flex-shrink-0"
        />
      </button>
    </div>
  </div>
</template>
```

> 🎨 **UX rationale:**
> - chip pattern (Twitter/X "Reply to..." style)
> - locked state = ไม่มีตัวเลือก (group profile page เพราะ context ชัด)
> - dropdown ปิดเอง ตอน click นอก
> - badge role ให้ user รู้ตัวเองอยู่ในสถานะใด

---

# J.3 — Wire selector ใน `CreatePostBox`

**File:** `ui/components/play/feed/CreatePostBox.vue`

CreatePostBox เป็น "trigger area" — เมื่อกดจะเปิด CreatePostModal เต็มหน้า

## J.3.1 — Props เพิ่ม + state

```ts
// เพิ่ม prop
const props = defineProps<{
  context: 'academy' | ...
  contextId: number
  contextName?: string
  postedAsGroupId?: number | null    // ⭐ มาจาก Phase I แล้ว
  /** Lock the selector to a specific group (group profile page) */
  lockedGroupId?: number | null      // ⭐ NEW
}>()

const internalPostedAsGroupId = ref<number | null>(props.postedAsGroupId ?? props.lockedGroupId ?? null)

// Sync ไปกับ prop change
watch(() => props.postedAsGroupId, (v) => { internalPostedAsGroupId.value = v ?? null })
watch(() => props.lockedGroupId, (v) => {
  if (v != null) internalPostedAsGroupId.value = v
})
```

## J.3.2 — Render selector ใต้ trigger area (academy context only)

หา section ที่ render "เริ่มเขียนโพสต์..." trigger box แล้วเพิ่มแถว selector:

```vue
<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm p-4">
    <!-- Existing trigger row -->
    <div class="flex items-center gap-3" @click="openModal">
      <BaseAvatar ... />
      <button class="flex-1 text-left text-gray-400 ...">
        เริ่มเขียนโพสต์...
      </button>
    </div>

    <!-- ⭐ NEW: Post-as selector (academy context only) -->
    <div
      v-if="context === 'academy'"
      class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700"
    >
      <AcademyGroupsPostAsSelector
        v-model="internalPostedAsGroupId"
        :academy-id="contextId"
        :locked-group-id="lockedGroupId"
        :user="user"
        variant="compact"
      />
    </div>

    <!-- Modal -->
    <CreatePostModal
      v-if="showModal"
      ...
      :posted-as-group-id="internalPostedAsGroupId"
      @close="showModal = false"
    />
  </div>
</template>
```

## J.3.3 — Pass `internalPostedAsGroupId` ลง modal

ตอนเปิด `CreatePostModal` ส่ง `internalPostedAsGroupId.value` แทน `props.postedAsGroupId`

---

# J.4 — Wire selector ใน `CreatePostModal`

**File:** `ui/components/play/feed/CreatePostModal.vue`

## J.4.1 — Props รับ + state

```ts
const props = defineProps<{
  context: ...
  contextId: number
  postedAsGroupId?: number | null         // จาก Phase I
  lockedGroupId?: number | null           // ⭐ NEW
}>()

const selectedGroupId = ref<number | null>(props.postedAsGroupId ?? null)

watch(() => props.postedAsGroupId, (v) => { selectedGroupId.value = v ?? null })
```

## J.4.2 — Render selector ในส่วน header ของ modal

ใส่ใต้ avatar + user name ในส่วนหัว modal:

```vue
<div class="flex items-start gap-3">
  <BaseAvatar :user="user" />
  <div class="flex-1">
    <div class="font-bold text-gray-900 dark:text-white">{{ user?.name }}</div>
    <!-- ⭐ NEW: selector -->
    <div class="mt-1">
      <AcademyGroupsPostAsSelector
        v-if="context === 'academy'"
        v-model="selectedGroupId"
        :academy-id="contextId"
        :locked-group-id="lockedGroupId"
        :user="user"
        variant="full"
      />
    </div>
  </div>
</div>
```

## J.4.3 — Submit ใช้ `selectedGroupId.value` (แทน props ตรงๆ)

แก้ block ที่ Phase I เขียนไว้:
```ts
// Old (Phase I)
if (props.postedAsGroupId) {
  formData.append('posted_as_group_id', String(props.postedAsGroupId))
}

// New (Phase J)
if (selectedGroupId.value) {
  formData.append('posted_as_group_id', String(selectedGroupId.value))
}
```

---

# J.5 — `FeedPost.vue`: render group header

**File:** `ui/components/play/feed/FeedPost.vue` (~3000 lines, big component)

## J.5.1 — Computed: `groupAuthor`

ใกล้ `postAuthor` computed (line 247) เพิ่ม:

```ts
// Group that this post is "in the name of" — null when posted as user
const groupAuthor = computed(() => {
  const g = props.post?.posted_as_group ?? props.post?.activityable?.posted_as_group ?? null
  if (!g || !g.id) return null
  return {
    id: g.id,
    name: g.name,
    type: g.type,
    typeMeta: g.type_meta || null,
  }
})

const isGroupPost = computed(() => groupAuthor.value !== null)
```

## J.5.2 — Avatar override

หา block ที่ render avatar ของ author (น่าจะใช้ `<BaseAvatar :user="postAuthor">` หรือ NuxtImg):

```vue
<!-- Existing -->
<BaseAvatar v-if="!isGroupPost" :user="postAuthor" :size="40" />

<!-- ⭐ NEW: Group avatar -->
<div
  v-else
  :class="[
    'w-10 h-10 rounded-lg bg-gradient-to-br flex items-center justify-center flex-shrink-0',
    GROUP_TYPE_COLOR_CLASSES[getAcademyGroupTypeMeta(groupAuthor.type).color].gradient,
  ]"
>
  <Icon
    :icon="getAcademyGroupTypeMeta(groupAuthor.type).icon"
    class="w-5 h-5 text-white"
  />
</div>
```

## J.5.3 — Display name override

หา line ที่แสดง `{{ postAuthor.name }}`:

```vue
<!-- Existing -->
<NuxtLink :to="`/profile/${postAuthor.id}`" class="font-bold ...">
  {{ postAuthor.name }}
</NuxtLink>

<!-- ⭐ NEW conditional -->
<template v-if="isGroupPost">
  <NuxtLink
    :to="`/academies/${academyName}/groups/${groupAuthor.id}`"
    class="font-bold text-gray-900 dark:text-white hover:underline flex items-center gap-1.5"
  >
    {{ groupAuthor.name }}
    <Icon icon="heroicons:check-badge-solid" class="w-4 h-4 text-vikinger-cyan" />
    <span
      v-if="groupAuthor.typeMeta"
      :class="[
        'text-[10px] px-1.5 py-0.5 rounded-full font-bold',
        GROUP_TYPE_COLOR_CLASSES[groupAuthor.typeMeta.color].badge,
      ]"
    >
      {{ groupAuthor.typeMeta.label }}
    </span>
  </NuxtLink>
</template>
<template v-else>
  <NuxtLink :to="`/profile/${postAuthor.id}`" class="font-bold ...">
    {{ postAuthor.name }}
  </NuxtLink>
</template>
```

> **⚠️ academyName** — อาจไม่ available ใน FeedPost props โดยตรง
> - ทาง A: ดึงจาก `post.posted_as_group.academy?.name` (ถ้า backend eager-load)
> - ทาง B: ดึงจาก route param ปัจจุบัน
> - ทาง C: ใช้ groupId routing แทน — `/groups/${groupAuthor.id}` (ต้องมี alias route)

## J.5.4 — Import dependencies

ใต้ existing imports:
```ts
import { Icon } from '@iconify/vue'
import { getAcademyGroupTypeMeta, GROUP_TYPE_COLOR_CLASSES } from '~/constants/academyGroupTypes'
```

---

# J.6 — "โดย {user.name}" credit line

**File:** `ui/components/play/feed/FeedPost.vue`

## J.6.1 — Computed: should show credit?

```ts
// Show "Posted by {real user}" only when:
// - it's a group post
// - AND current user is part of the academy (member or admin)
// Avoid revealing actor to outsiders
const showActorCredit = computed(() => {
  if (!isGroupPost.value) return false
  // Heuristic: if FeedPost is rendered within academy context, user is implicitly a member
  // Simple version: always show — admins อยากเห็น, regular member ก็เห็น actor (transparency)
  return true
})
```

> 💡 **Decision:** เปิดให้ทุกคนเห็น actor — simpler + transparency principle
> ถ้าต้องการ hide จาก outsider → pass prop `currentUserIsMember` จาก parent

## J.6.2 — Render line ใต้ชื่อกลุ่ม

ใต้ avatar+name block:
```vue
<div class="flex items-center text-xs text-gray-500 dark:text-gray-400 mt-0.5">
  <span v-if="showActorCredit">
    โดย <NuxtLink :to="`/profile/${postAuthor.id}`" class="hover:underline">{{ postAuthor.name }}</NuxtLink>
    · {{ relativeTime }}
  </span>
  <span v-else>{{ relativeTime }}</span>
</div>
```

(`relativeTime` ที่มีอยู่แล้ว — reuse)

---

# J.7 — Group profile page: lock selector

**File:** `ui/pages/academies/[name]/groups/[groupId].vue` (Phase I)
**File:** `ui/components/academy/groups/GroupFeedTab.vue` (Phase I)

ใน `GroupFeedTab`:
```vue
<PlayFeedCreatePostBox
  v-if="canPost"
  context="academy"
  :context-id="group.academy_id"
  :context-name="group.name"
  :locked-group-id="group.id"        <!-- ⭐ NEW: ไม่ใช่ posted-as-group-id -->
  @post-created="onPostCreated"
/>
```

> **เหตุผล:** `lockedGroupId` ทำให้ selector แสดงเป็น chip readonly ("โพสต์ในนาม ฝ่ายวิชาการ") + ส่ง `internalPostedAsGroupId = group.id` ลง modal อัตโนมัติ

Selector ใน `PostAsSelector` (J.2) จะตรวจ `lockedGroupId` → render chip ไม่มี dropdown → UX ชัด

---

# J.8 — Edge cases & polish

## J.8.1 — Invalidate cache เมื่อ permission เปลี่ยน
**File:** `ui/components/academy/groups/ManageTabPermissions.vue` (Phase H)

หลัง `updatePermissions` save สำเร็จ:
```ts
const { invalidatePostableCache } = useAcademyGroups()
// ...
await updatePermissions(...)
invalidatePostableCache(props.academyId)   // ⭐ NEW
```

## J.8.2 — Default selection persistence
- ถ้า user เลือก group แล้วปิด modal แล้วเปิดใหม่ → ควรจำได้ภายใน session
- store ใน Pinia? overkill — ใช้ ref ใน `CreatePostBox` (component instance state) → reset ตอน "post-created"

## J.8.3 — Hashtags/mentions ของกลุ่ม
- ตอนนี้ระบบ mention/hashtag ดึงจาก user — เพิ่ม "@group" mention เป็น future work (Phase L)

## J.8.4 — Edit existing post
- ถ้า user แก้โพสต์ที่ `posted_as_group_id` ตั้งไว้ → modal edit ควร lock เป็น group นั้น (ไม่ให้ swap)
- หรือ allow swap → backend Phase G จะ validate ใหม่
- **แนะนำ:** lock (UX consistent + ไม่งง)

## J.8.5 — Delete group post
- โพสต์ที่ `posted_as_group_id` ตั้งไว้: ใครลบได้?
  - poster (user_id) ✅
  - group admin ✅
  - academy admin ✅
- ต้องเพิ่ม authorization check ใน `AcademyPostController::destroy` (อาจมีอยู่แล้ว — ตรวจ)

---

# 📋 Phase J — Files Summary

## ✨ New files (1)
```
ui/components/academy/groups/PostAsSelector.vue        — chip + dropdown
```

## 🔧 Modified files (~6)
```
api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupController.php  (postableForUser)
api/nuxnanravel/routes/learn/academy.php                                          (route register)

ui/composables/useAcademyGroups.ts                       (getPostableGroups + cache)
ui/components/play/feed/CreatePostBox.vue                (mount selector + sync)
ui/components/play/feed/CreatePostModal.vue              (mount selector + submit selectedGroupId)
ui/components/play/feed/FeedPost.vue                     (group header + actor credit)
ui/components/academy/groups/GroupFeedTab.vue            (locked-group-id prop)
ui/components/academy/groups/ManageTabPermissions.vue    (invalidate cache after save)
```

---

# 🛣️ Commit plan (4 commits)

```
1. feat(api): add postable-groups endpoint for current user
   - J.0 + route

2. feat(ui): add PostAsSelector component + composable cache
   - J.1 + J.2

3. feat(ui): integrate PostAsSelector into composer (box + modal)
   - J.3 + J.4 + J.7 (locked variant for group profile)

4. feat(ui): render group header + actor credit in FeedPost
   - J.5 + J.6 + J.8 polish
```

---

# ✅ Phase J — Test Checklist

## Backend (J.0)
- [ ] `php artisan route:list | grep postable-groups` แสดง route
- [ ] curl token ของ user ที่อยู่ใน 2 กลุ่ม (1 มี can_post=true, 1 ไม่มี) → คืนแค่ 1 กลุ่ม
- [ ] user ที่ไม่อยู่ในกลุ่มไหนเลย → คืน `[]`
- [ ] user ที่อยู่ใน group ของอีกโรงเรียน → ไม่อยู่ใน list ของโรงเรียนนี้

## Composer in main academy feed (J.3 + J.4)
- [ ] เข้าหน้า `/academies/{name}` → tab หน้าหลัก
- [ ] เห็น chip "โพสต์ในนาม: ฉัน" ใต้ trigger
- [ ] กด chip → dropdown แสดง "ตัวเอง" + รายการกลุ่มที่ post ได้
- [ ] เลือกกลุ่ม → chip เปลี่ยนเป็นชื่อกลุ่ม + icon ตาม type
- [ ] เปิด modal → ใน modal มี selector อันใหญ่ + state sync กับ chip นอก
- [ ] กดโพสต์ → DB `academy_posts.posted_as_group_id` = id ที่เลือก

## Composer in group profile page (J.7)
- [ ] เข้าหน้า `/academies/{name}/groups/{id}` → tab ฟีด
- [ ] เห็น chip readonly "โพสต์ในนาม {ชื่อกลุ่ม}" — **ไม่มี dropdown arrow**
- [ ] โพสต์ → DB ตั้ง `posted_as_group_id = group.id` อัตโนมัติ

## FeedPost header (J.5)
- [ ] โพสต์ที่ `posted_as_group_id != null` แสดง:
  - avatar เป็น gradient ของ type
  - ชื่อกลุ่มแทนชื่อ user
  - verified badge + type label badge
  - คลิกชื่อกลุ่ม → ไปหน้า group profile
- [ ] โพสต์ปกติยังแสดง user header ปกติ (ไม่ regression)

## Actor credit (J.6)
- [ ] ใต้ชื่อกลุ่มมีบรรทัด "โดย {user.name} · เมื่อ X นาทีก่อน"
- [ ] คลิกชื่อ user → ไป profile

## Cache invalidation (J.8.1)
- [ ] เปิด ManageTabPermissions → toggle `can_post` ของกลุ่ม A → save
- [ ] กลับมาหน้า feed → เปิด selector → กลุ่ม A หาย/เพิ่ม ตามการ toggle (ไม่ต้อง refresh)

## Edge cases
- [ ] โพสต์ที่ลบกลุ่มไปแล้ว (`posted_as_group_id` = null จาก nullOnDelete) → แสดงเป็นโพสต์ user ปกติ
- [ ] กลุ่มที่ user เพิ่งโดนปลดออก → selector update หลัง refresh cache
- [ ] โหลด postable-groups ตอน offline → fallback graceful (selector แสดงแค่ "ตัวเอง")

## Regression
- [ ] โพสต์ใน main academy feed ปกติ (ไม่เลือกกลุ่ม) ยังใช้ได้
- [ ] โพสต์ใน main feed (non-academy context) ไม่แสดง selector
- [ ] CreatePostBox ใน context อื่น (เช่น personal newsfeed) ทำงานเดิม

---

# ⚠️ Pitfalls & Notes

## 1. Backend response shape variation
- `postable-groups` คืน `data: []` หรือ `data: {data: []}` — ตรวจ wrapper
- composable normalize: `const data = res?.data ?? []`

## 2. FeedPost prop drilling for academyName
- `FeedPost` ไม่ได้รับ `academyName` prop เดิม
- ทางแก้: ใช้ `useRoute().params.name` ใน computed link (ทำงานเฉพาะใน academy page context)
- หรือ pass prop จาก parent + fallback

## 3. activityable nested shape
- บาง use case `post` ที่ส่งเข้า FeedPost อาจเป็น Activity wrapper
- `groupAuthor` computed รองรับทั้ง `post.posted_as_group` และ `post.activityable.posted_as_group`
- ต้องเช็คใน Vue devtools ว่า shape จริงเป็นแบบไหน

## 4. Click-outside ของ dropdown vs modal overlay
- PostAsSelector ใช้ `document click listener` ปิด dropdown
- ใน CreatePostModal มี modal overlay (z-50) → ตรวจว่า dropdown ของ selector อยู่ z สูงพอเห็น
- เพิ่ม `z-50` หรือ `z-60` ใน dropdown panel

## 5. SSR safety
- `document.addEventListener` ต้องอยู่ใน `onMounted` (client only)
- ✅ ใน J.2 ทำแล้ว

## 6. Type meta missing
- ถ้า backend คืน `posted_as_group.type` แต่ไม่มี `type_meta` → frontend fallback ใช้ `getAcademyGroupTypeMeta(type)`
- ✅ J.5.1 fallback แล้ว

## 7. Edit post — change group?
- Phase J ไม่รองรับ change group ระหว่าง edit (lock)
- ถ้าจะรองรับ → ต้องแก้ `update()` ใน `AcademyPostController` รับ `posted_as_group_id` + validate

## 8. Performance — postable cache TTL
- ตอนนี้ cache จนกว่าจะ explicit invalidate
- ถ้าระบบโตและกลุ่มเยอะ → ใส่ TTL 5 นาที (timestamp + check)

---

# 🎯 ลำดับงานแนะนำ

```
1. J.0 backend → curl test
2. J.1 composable → console test
3. J.2 PostAsSelector (standalone) → test ใน Storybook หรือเปิดหน้าทดสอบ
4. J.3 + J.4 wire composer → ดู chip + dropdown ใช้งานได้
5. J.7 lock variant ใน group profile (1 prop เปลี่ยน)
6. J.5 + J.6 FeedPost header → ทำหลังสุดเพราะ FeedPost ซับซ้อน
7. J.8 polish + cache invalidate
```

## Quick test data setup

```sql
-- ใน phpMyAdmin DB nuxnan
-- 1. เปิด can_post ของกลุ่มทดสอบ
UPDATE academy_group_permissions
SET enabled = 1
WHERE academy_group_id = 1 AND permission_key = 'can_post';

-- 2. เพิ่มตัวเองเป็น member ของกลุ่ม (ปรับ user_id)
INSERT INTO academy_group_members (academy_group_id, user_id, role, created_at, updated_at)
VALUES (1, {YOUR_USER_ID}, 'member', NOW(), NOW());

-- 3. (optional) เพิ่มเป็น admin
INSERT INTO academy_group_admins (academy_group_id, user_id, role, created_at, updated_at)
VALUES (1, {YOUR_USER_ID}, 'leader', NOW(), NOW());
```

จากนั้น curl `/postable-groups` ต้องเห็นกลุ่มนี้

หลัง J เสร็จ → ระบบ post-as-group ครบลูป + design ใน [`.agents/design-ref/School Homepage.html`](./School Homepage.html) จะ "feel right" สมจริงเหมือนตัวอย่าง 🎯

**Phase K** (Invite + appointment + notifications) จะปิดงาน workflow ทั้งหมด

ติดตรงไหนตอนทำมาถามได้เลยครับ 🙌

# Phase I — Group Profile Page (Detailed DIY Plan)

อ้างอิง: [decisions](./school-departments-decisions.md) + [Phase G ✅](./phase-g-backend-detailed.md) + [Phase H ✅](./phase-h-manage-ui-detailed.md)
Target: หน้าโปรไฟล์ของส่วนงานเดี่ยว — ดูคล้าย "mini school homepage" สำหรับ 1 ส่วนงาน
วันที่: 2026-06-20

---

## 📌 Pre-check ที่ผมยืนยันให้แล้ว

| ข้อเท็จจริง | ผลกระทบ |
|---|---|
| `onViewGroup` ใน `[name].vue:950` ตอนนี้เป็น **SweetAlert popup stub** (ไม่ navigate จริง) | Phase I.6 จะเปลี่ยนเป็น `navigateTo()` |
| nested page pattern ใช้ได้แล้ว — มี `[name]/admin.vue`, `[name]/dashboard/`, `[name]/students/` | สร้าง `[name]/groups/[groupId].vue` ได้ทันที |
| `academy_groups` schema มี `id, academy_id, parent_id, sort_order, name, description, type, settings` — **ไม่มี slug** | URL ใช้ `id` (numeric) |
| ❌ **ยังไม่มี** endpoint `GET /groups/{group}/posts` (list โพสต์ของกลุ่ม) | I.0 จะเพิ่ม |
| ❌ **ยังไม่มี** endpoint `GET /groups/{group}/stats` (counts) | I.0 จะเพิ่ม |
| ✅ มี `useAcademyGroups` composable (Phase H) | reuse — แค่เพิ่ม method `listGroupPosts` |
| ✅ `AcademyPost.postedAsGroup` relation (Phase G) | query โพสต์ของกลุ่มได้ตรงๆ |

---

# 🗺️ Phase I Sub-phases Overview

| # | งาน | Est. |
|---|---|---|
| **I.0** | Backend gap-fill: 2 endpoints (`/posts`, `/stats`) | 1 hr |
| **I.1** | Extend `useAcademyGroups`: เพิ่ม `listGroupPosts`, `getGroupStats` | 30 min |
| **I.2** | Create route page `[name]/groups/[groupId].vue` shell + data loaders | 1 hr |
| **I.3** | `GroupProfileCover.vue` (hero + identity + actions) | 1.5 hr |
| **I.4** | `GroupFeedTab.vue` (composer + feed list) | 1.5 hr |
| **I.5** | `GroupMembersTab.vue` (admins banner + members list + search) | 1.5 hr |
| **I.6** | `GroupAboutTab.vue` + sidebar widgets | 1 hr |
| **I.7** | Wire `onViewGroup` → navigate (แทน SweetAlert stub) | 15 min |
| **I.8** | Permission gating: composer / manage button / member CTA | 30 min |
| **รวม** | | **~8 ชม.** |

---

# I.0 — Backend gap-fill

## I.0.1 — เพิ่ม `posts()` ใน `AcademyGroupController`

**File:** `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupController.php`
**Location:** ใต้ method `show()` หรือใกล้ๆ

```php
/**
 * GET /groups/{academyGroup}/posts
 * List posts where posted_as_group_id = group.id, paginated.
 */
public function posts(\Illuminate\Http\Request $request, AcademyGroup $academyGroup)
{
    $perPage = (int) $request->input('per_page', 10);

    $posts = \App\Models\AcademyPost::where('posted_as_group_id', $academyGroup->id)
        ->with([
            'user:id,name,profile_photo_path',
            'postedAsGroup:id,name,type',
            'images',
            'likedPost:id',
        ])
        ->withCount(['likedPost as likes_count', 'comments'])
        ->latest()
        ->paginate($perPage);

    return response()->json([
        'success' => true,
        'data'    => $posts,
    ]);
}
```

> ⚠️ ตรวจชื่อ relation: `likedPost` / `comments` / `images` ใน `AcademyPost.php` — ปรับตามจริง

## I.0.2 — เพิ่ม `stats()` ใน `AcademyGroupController`

```php
/**
 * GET /groups/{academyGroup}/stats
 */
public function stats(AcademyGroup $academyGroup)
{
    return response()->json([
        'success' => true,
        'data'    => [
            'members_count' => \App\Models\AcademyGroupMember::where('academy_group_id', $academyGroup->id)->count(),
            'admins_count'  => \App\Models\AcademyGroupAdmin::where('academy_group_id', $academyGroup->id)->count(),
            'posts_count'   => \App\Models\AcademyPost::where('posted_as_group_id', $academyGroup->id)->count(),
            'created_at'    => $academyGroup->created_at,
        ],
    ]);
}
```

## I.0.3 — Register routes

**File:** `api/nuxnanravel/routes/learn/academy.php`
**Location:** ใกล้ routes `/groups/{academyGroup}/admins` (Phase H) เพิ่ม:

```php
Route::get('/groups/{academyGroup}/posts', [AcademyGroupController::class, 'posts'])
    ->name('api.academy.groups.posts');

Route::get('/groups/{academyGroup}/stats', [AcademyGroupController::class, 'stats'])
    ->name('api.academy.groups.stats');
```

ทำซ้ำใน 2 group (auth + api scope) ตามโครงไฟล์ routes

## I.0.4 — Verify

```bash
php artisan route:list | grep "groups.*posts\|groups.*stats"
# ควรเห็น 2 routes
```

```bash
# Test ด้วยกลุ่มจริง
curl http://localhost:8000/api/academies/groups/1/posts
curl http://localhost:8000/api/academies/groups/1/stats
```

---

# I.1 — Extend composable

**File:** `ui/composables/useAcademyGroups.ts`
เพิ่ม 2 method ในส่วน return + implementation:

```ts
// === Group profile-specific ===
const listGroupPosts = <T = any>(groupId: number, params?: { per_page?: number; page?: number }) =>
  api.call<T>(`/api/academies/groups/${groupId}/posts`, { params })

const getGroupStats = <T = any>(groupId: number) =>
  api.call<T>(`/api/academies/groups/${groupId}/stats`)
```

อย่าลืม export ใน return block

---

# I.2 — Route page shell

**File:** `ui/pages/academies/[name]/groups/[groupId].vue` (NEW)

```vue
<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'
import { getAcademyGroupTypeMeta, GROUP_TYPE_COLOR_CLASSES } from '~/constants/academyGroupTypes'

definePageMeta({
  layout: 'main',
  middleware: ['auth'],
})

const route = useRoute()
const { user } = storeToRefs(useAuthStore())
const { getGroup, listAdmins, listMembers, getGroupStats, mute, unmute } = useAcademyGroups()
const api = useApi()

// ===== Route params =====
const academyName = computed(() => route.params.name as string)
const groupId = computed(() => Number(route.params.groupId))

// ===== State =====
const group = ref<any>(null)
const academy = ref<any>(null)
const admins = ref<any[]>([])
const members = ref<any[]>([])
const stats = ref<{ members_count: number; admins_count: number; posts_count: number; created_at?: string } | null>(null)
const isLoading = ref(true)
const error = ref<string | null>(null)
const currentTab = ref<'feed' | 'members' | 'about'>('feed')
const isMuted = ref(false)

// ===== Membership status (derive from members/admins) =====
const isGroupAdmin = computed(() =>
  admins.value.some((a) => a.user_id === user.value?.id)
)
const isGroupMember = computed(() =>
  members.value.some((m) => m.user_id === user.value?.id) || isGroupAdmin.value
)
const isAcademyAdmin = computed(() => academy.value?.authIsAcademyAdmin === true)

// Derived: can the current user post in this group?
const groupPermissions = ref<string[]>([]) // array of enabled keys

const canPost = computed(() =>
  isGroupMember.value && groupPermissions.value.includes('can_post')
)
const canManage = computed(() => isAcademyAdmin.value || isGroupAdmin.value)

// ===== Loaders =====
const loadGroup = async () => {
  isLoading.value = true
  error.value = null
  try {
    // 1. group + academy (parent)
    const gRes: any = await getGroup(groupId.value)
    if (!gRes?.success) throw new Error(gRes?.message || 'group not found')
    group.value = gRes.group
    academy.value = gRes.group.academy

    // 2. stats + admins + members in parallel
    const [statsRes, adminsRes, membersRes, permsRes]: any = await Promise.all([
      getGroupStats(groupId.value),
      listAdmins(groupId.value),
      listMembers(groupId.value),
      api.call(`/api/academies/${academy.value.id}/departments/${groupId.value}/permissions`),
    ])

    stats.value = statsRes?.data ?? null
    admins.value = adminsRes?.admins ?? []
    members.value = membersRes?.members ?? membersRes?.data ?? []
    groupPermissions.value = permsRes?.data?.enabled_keys ?? []
  } catch (e: any) {
    error.value = e?.message || 'โหลดข้อมูลไม่สำเร็จ'
  } finally {
    isLoading.value = false
  }
}

const onMute = async () => {
  isMuted.value = true
  try { await mute(groupId.value) } catch { isMuted.value = false }
}
const onUnmute = async () => {
  isMuted.value = false
  try { await unmute(groupId.value) } catch { isMuted.value = true }
}

const onTabChange = (tab: typeof currentTab.value) => {
  currentTab.value = tab
  // Sync URL hash so reload keeps current tab
  const newHash = `#${tab}`
  if (route.hash !== newHash) {
    history.replaceState(history.state, '', `${route.path}${newHash}`)
  }
}

// Pick up tab from URL hash on initial load
onMounted(() => {
  const hash = route.hash.replace('#', '')
  if (['feed', 'members', 'about'].includes(hash)) {
    currentTab.value = hash as any
  }
  loadGroup()
})

watch(groupId, loadGroup)

// ===== Meta for cover styling =====
const typeMeta = computed(() => group.value ? getAcademyGroupTypeMeta(group.value.type) : null)
const typeCls = computed(() => typeMeta.value ? GROUP_TYPE_COLOR_CLASSES[typeMeta.value.color] : null)
</script>

<template>
  <div class="min-h-screen bg-gray-200 dark:bg-vikinger-dark-300">
    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <Icon icon="svg-spinners:ring-resize" class="w-12 h-12 text-vikinger-purple mx-auto mb-4" />
        <p class="text-gray-600 dark:text-gray-400">กำลังโหลดส่วนงาน...</p>
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="flex items-center justify-center min-h-screen">
      <div class="text-center p-8 max-w-md">
        <Icon icon="heroicons:exclamation-triangle" class="w-16 h-16 text-amber-500 mx-auto mb-4" />
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบส่วนงาน</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">{{ error }}</p>
        <NuxtLink
          :to="`/academies/${academyName}#groups`"
          class="inline-flex items-center gap-2 px-6 py-3 bg-vikinger-purple text-white rounded-lg"
        >
          <Icon icon="heroicons:arrow-left" class="w-4 h-4" />
          กลับหน้าโรงเรียน
        </NuxtLink>
      </div>
    </div>

    <!-- Main content -->
    <div v-else-if="group" class="max-w-7xl mx-auto px-4 py-6">
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 mb-4 text-sm text-gray-500 dark:text-gray-400">
        <NuxtLink :to="`/academies/${academyName}`" class="hover:text-vikinger-purple">
          {{ academy?.name }}
        </NuxtLink>
        <Icon icon="heroicons:chevron-right" class="w-4 h-4" />
        <NuxtLink :to="`/academies/${academyName}#groups`" class="hover:text-vikinger-purple">
          ส่วนงาน
        </NuxtLink>
        <Icon icon="heroicons:chevron-right" class="w-4 h-4" />
        <span class="text-gray-700 dark:text-gray-200 font-semibold">{{ group.name }}</span>
      </div>

      <!-- Cover (Phase I.3) -->
      <AcademyGroupsGroupProfileCover
        :group="group"
        :stats="stats"
        :is-member="isGroupMember"
        :is-admin="isGroupAdmin"
        :can-manage="canManage"
        :is-muted="isMuted"
        @mute="onMute"
        @unmute="onUnmute"
      />

      <!-- Tabs -->
      <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-vikinger-dark-200 rounded-b-xl shadow-sm">
        <div class="flex overflow-x-auto">
          <button
            v-for="t in [
              { key: 'feed', label: 'ฟีด', icon: 'heroicons:home', count: stats?.posts_count },
              { key: 'members', label: 'สมาชิก', icon: 'heroicons:user-group', count: stats?.members_count },
              { key: 'about', label: 'เกี่ยวกับ', icon: 'heroicons:information-circle' },
            ]"
            :key="t.key"
            type="button"
            :class="[
              'flex items-center gap-2 px-6 py-4 text-sm font-medium transition-colors whitespace-nowrap',
              currentTab === t.key
                ? 'text-vikinger-purple border-b-2 border-vikinger-purple'
                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
            ]"
            @click="onTabChange(t.key as any)"
          >
            <Icon :icon="t.icon" class="w-5 h-5" />
            <span>{{ t.label }}</span>
            <span
              v-if="t.count != null && t.count > 0"
              class="ml-1 text-[11px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-semibold"
            >
              {{ t.count }}
            </span>
          </button>
        </div>
      </div>

      <!-- 2-col layout: main + sidebar -->
      <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_320px] gap-4 lg:gap-5 items-start mt-5">
        <main class="min-w-0">
          <AcademyGroupsGroupFeedTab
            v-if="currentTab === 'feed'"
            :group="group"
            :can-post="canPost"
          />
          <AcademyGroupsGroupMembersTab
            v-else-if="currentTab === 'members'"
            :group="group"
            :admins="admins"
            :members="members"
            :can-manage="canManage"
          />
          <AcademyGroupsGroupAboutTab
            v-else-if="currentTab === 'about'"
            :group="group"
            :academy="academy"
            :stats="stats"
            :type-meta="typeMeta"
          />
        </main>

        <!-- Right sidebar: quick info + member preview -->
        <aside class="hidden lg:flex flex-col gap-4 sticky top-20 self-start">
          <!-- Quick stats card -->
          <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm p-4">
            <div class="grid grid-cols-3 gap-3 text-center">
              <div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ stats?.posts_count ?? 0 }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">โพสต์</div>
              </div>
              <div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ stats?.members_count ?? 0 }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">สมาชิก</div>
              </div>
              <div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ stats?.admins_count ?? 0 }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">หัวหน้า</div>
              </div>
            </div>
          </div>

          <!-- Admins preview (3 บนสุด) -->
          <div v-if="admins.length > 0" class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
              <span class="font-bold text-gray-900 dark:text-white text-sm">หัวหน้าส่วนงาน</span>
              <button
                class="text-xs font-semibold text-vikinger-purple"
                @click="onTabChange('members')"
              >
                ดูทั้งหมด
              </button>
            </div>
            <div class="p-4 space-y-3">
              <div
                v-for="a in admins.slice(0, 3)"
                :key="a.id"
                class="flex items-center gap-3"
              >
                <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                  <img v-if="a.user?.profile_photo_path" :src="a.user.profile_photo_path" class="w-full h-full object-cover" />
                  <Icon v-else icon="heroicons:user" class="w-full h-full p-1.5 text-gray-400" />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                    {{ a.user?.name }}
                  </div>
                  <div class="text-xs text-gray-500">{{ a.role }}</div>
                </div>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>
```

---

# I.3 — `GroupProfileCover.vue`

**File:** `ui/components/academy/groups/GroupProfileCover.vue` (NEW)

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { getAcademyGroupTypeMeta, GROUP_TYPE_COLOR_CLASSES } from '~/constants/academyGroupTypes'

interface Props {
  group: any
  stats: { members_count?: number; posts_count?: number; admins_count?: number } | null
  isMember: boolean
  isAdmin: boolean
  canManage: boolean
  isMuted: boolean
}
const props = defineProps<Props>()
const emit = defineEmits<{
  mute: []
  unmute: []
  manage: []
  share: []
}>()

const meta = computed(() => getAcademyGroupTypeMeta(props.group.type))
const cls = computed(() => GROUP_TYPE_COLOR_CLASSES[meta.value.color])

const share = async () => {
  const url = window.location.href
  if (navigator.share) {
    try { await navigator.share({ title: props.group.name, url }); return } catch {}
  }
  try {
    await navigator.clipboard.writeText(url)
    emit('share')
  } catch {}
}
</script>

<template>
  <section class="bg-white dark:bg-vikinger-dark-200 rounded-t-xl shadow-sm overflow-hidden">
    <!-- Cover gradient -->
    <div :class="['relative h-32 bg-gradient-to-br', cls.gradient]">
      <div
        class="absolute inset-0 opacity-20 mix-blend-overlay"
        style="background-image: radial-gradient(circle, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 24px 24px;"
      ></div>
      <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/30"></div>
    </div>

    <!-- Identity row -->
    <div class="px-5 md:px-8 pb-4">
      <div class="flex items-end gap-4 -mt-10">
        <!-- Icon medallion -->
        <div :class="['w-20 h-20 rounded-2xl flex items-center justify-center border-4 border-white dark:border-vikinger-dark-200 shadow-md bg-gradient-to-br flex-shrink-0', cls.gradient]">
          <Icon :icon="meta.icon" class="w-10 h-10 text-white" />
        </div>

        <!-- Name + type badge + stats -->
        <div class="flex-1 min-w-0 pb-2">
          <div class="flex items-center gap-2 flex-wrap">
            <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white truncate">
              {{ group.name }}
            </h1>
            <span :class="['text-[11px] px-2 py-0.5 rounded-full font-bold', cls.badge]">
              {{ meta.label }}
            </span>
          </div>
          <div class="flex items-center gap-4 mt-1 text-sm">
            <span class="text-gray-600 dark:text-gray-400">
              <b class="text-gray-900 dark:text-white">{{ stats?.members_count ?? 0 }}</b>
              สมาชิก
            </span>
            <span class="text-gray-600 dark:text-gray-400">
              <b class="text-gray-900 dark:text-white">{{ stats?.posts_count ?? 0 }}</b>
              โพสต์
            </span>
          </div>
        </div>

        <!-- Actions (desktop only inline) -->
        <div class="hidden md:flex items-center gap-2 pb-2">
          <button
            v-if="canManage"
            class="px-3 py-2 rounded-lg text-sm font-semibold bg-vikinger-purple text-white hover:bg-vikinger-purple/90 flex items-center gap-1.5"
            @click="emit('manage')"
          >
            <Icon icon="heroicons:cog-6-tooth" class="w-4 h-4" />
            จัดการ
          </button>
          <button
            class="px-3 py-2 rounded-lg text-sm font-semibold border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 flex items-center gap-1.5"
            @click="share"
          >
            <Icon icon="heroicons:share" class="w-4 h-4" />
            แชร์
          </button>
          <button
            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100"
            :title="isMuted ? 'เปิดการแจ้งเตือน' : 'ปิดการแจ้งเตือน'"
            @click="emit(isMuted ? 'unmute' : 'mute')"
          >
            <Icon :icon="isMuted ? 'heroicons:bell-slash' : 'heroicons:bell'" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Description -->
      <p v-if="group.description" class="mt-3 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
        {{ group.description }}
      </p>

      <!-- Action bar (mobile) -->
      <div class="flex md:hidden gap-2 mt-4">
        <button
          v-if="canManage"
          class="flex-1 px-3 py-2 rounded-lg text-sm font-semibold bg-vikinger-purple text-white"
          @click="emit('manage')"
        >
          จัดการ
        </button>
        <button class="px-3 py-2 rounded-lg text-sm font-semibold border" @click="share">
          แชร์
        </button>
        <button class="p-2 rounded-lg border" @click="emit(isMuted ? 'unmute' : 'mute')">
          <Icon :icon="isMuted ? 'heroicons:bell-slash' : 'heroicons:bell'" class="w-4 h-4" />
        </button>
      </div>
    </div>
  </section>
</template>
```

> **Manage button** — เปิด `<AcademyGroupsGroupManageModal>` ตรงหน้านี้ ได้เลย (mount เพิ่มใน [groupId].vue)
> หรือ — กลับไปหน้า academy แล้วเปิด modal — แนะนำ **mount ในหน้า group profile** เพื่อ self-contained

---

# I.4 — `GroupFeedTab.vue`

**File:** `ui/components/academy/groups/GroupFeedTab.vue` (NEW)

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  group: any
  /** Can the current user post in this group? */
  canPost: boolean
}
const props = defineProps<Props>()

const { listGroupPosts } = useAcademyGroups()

const posts = ref<any[]>([])
const isLoading = ref(true)
const hasMore = ref(false)
const currentPage = ref(1)

const load = async (page = 1) => {
  if (page === 1) isLoading.value = true
  try {
    const res: any = await listGroupPosts(props.group.id, { per_page: 10, page })
    const data = res?.data
    if (page === 1) {
      posts.value = data?.data ?? []
    } else {
      posts.value.push(...(data?.data ?? []))
    }
    currentPage.value = data?.current_page ?? page
    hasMore.value = (data?.current_page ?? 0) < (data?.last_page ?? 0)
  } finally {
    isLoading.value = false
  }
}

const loadMore = () => load(currentPage.value + 1)
const onPostCreated = (post: any) => posts.value.unshift(post)
const onPostDeleted = (id: number) => {
  posts.value = posts.value.filter((p) => p.id !== id)
}

onMounted(() => load(1))
</script>

<template>
  <div class="space-y-3">
    <!-- Composer (only if canPost) -->
    <PlayFeedCreatePostBox
      v-if="canPost"
      context="academy"
      :context-id="group.academy_id"
      :context-name="group.name"
      :posted-as-group-id="group.id"
      @post-created="onPostCreated"
    />

    <!-- Info panel for non-members -->
    <div
      v-else
      class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-xl p-4 text-sm text-blue-700 dark:text-blue-300 flex items-center gap-2"
    >
      <Icon icon="heroicons:information-circle" class="w-5 h-5 flex-shrink-0" />
      <span>เฉพาะสมาชิกของส่วนงานนี้และมีสิทธิ์โพสต์เท่านั้นที่สามารถโพสต์ในนามส่วนงานได้</span>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="py-10 text-center">
      <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-vikinger-purple mx-auto" />
    </div>

    <!-- Empty -->
    <div
      v-else-if="posts.length === 0"
      class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center"
    >
      <Icon icon="heroicons:document-text" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
      <p class="text-gray-500 dark:text-gray-400">ยังไม่มีโพสต์</p>
      <p v-if="canPost" class="text-sm text-gray-400 dark:text-gray-500 mt-1">เริ่มโพสต์เพื่อแชร์ข่าวสารในนามส่วนงาน</p>
    </div>

    <!-- Feed -->
    <FeedPost
      v-for="post in posts"
      :key="post.id"
      :post="post"
      @delete-success="onPostDeleted"
    />

    <!-- Load more -->
    <div v-if="hasMore" class="text-center py-4">
      <button
        type="button"
        class="px-6 py-2.5 bg-white dark:bg-vikinger-dark-200 text-gray-700 dark:text-gray-300 rounded-lg font-medium shadow-sm border border-gray-200 dark:border-gray-700"
        @click="loadMore"
      >
        โหลดเพิ่มเติม
      </button>
    </div>
  </div>
</template>
```

> ⚠️ **Composer integration:** ส่ง prop `posted-as-group-id` ไปยัง `PlayFeedCreatePostBox`
> ปัจจุบัน `CreatePostBox` อาจไม่รับ prop นี้ — ต้องเพิ่ม:
> ```ts
> const props = defineProps<{
>   context: 'academy' | ...
>   contextId: number
>   contextName?: string
>   postedAsGroupId?: number       // ⭐ NEW
> }>()
> ```
> แล้วใน method ที่ submit post → ส่ง `posted_as_group_id: props.postedAsGroupId` ใน body

---

# I.5 — `GroupMembersTab.vue`

**File:** `ui/components/academy/groups/GroupMembersTab.vue` (NEW)

```vue
<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  group: any
  admins: any[]
  members: any[]
  canManage: boolean
}
const props = defineProps<Props>()
const emit = defineEmits<{ openManage: [tab: 'admins' | 'members'] }>()

const query = ref('')
const filteredMembers = computed(() => {
  const q = query.value.toLowerCase().trim()
  if (!q) return props.members
  return props.members.filter((m) => (m.user?.name || '').toLowerCase().includes(q))
})

const roleLabel: Record<string, string> = {
  leader: 'หัวหน้า',
  co_leader: 'รองหัวหน้า',
  advisor: 'ที่ปรึกษา',
  student: 'นักเรียน',
  teacher: 'ครู',
  admin: 'แอดมิน',
}
</script>

<template>
  <div class="space-y-5">
    <!-- Admins banner -->
    <section class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden">
      <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <Icon icon="heroicons:star-solid" class="w-5 h-5 text-amber-500" />
          <h3 class="font-bold text-gray-900 dark:text-white">หัวหน้าส่วนงาน</h3>
          <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 font-semibold">
            {{ admins.length }}
          </span>
        </div>
        <button
          v-if="canManage"
          class="text-xs font-semibold text-vikinger-purple"
          @click="emit('openManage', 'admins')"
        >
          จัดการ
        </button>
      </div>
      <div class="p-5">
        <div v-if="admins.length === 0" class="text-sm text-gray-500 text-center py-2">
          ยังไม่มีหัวหน้า
        </div>
        <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          <div
            v-for="a in admins"
            :key="a.id"
            class="flex flex-col items-center text-center"
          >
            <div class="w-16 h-16 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden mb-2">
              <img v-if="a.user?.profile_photo_path" :src="a.user.profile_photo_path" class="w-full h-full object-cover" />
              <Icon v-else icon="heroicons:user" class="w-full h-full p-3 text-gray-400" />
            </div>
            <div class="text-sm font-semibold text-gray-900 dark:text-white truncate w-full">
              {{ a.user?.name }}
            </div>
            <div class="text-xs text-gray-500">{{ roleLabel[a.role] || a.role }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Members list -->
    <section class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden">
      <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <Icon icon="heroicons:user-group" class="w-5 h-5 text-vikinger-purple" />
          <h3 class="font-bold text-gray-900 dark:text-white">สมาชิก</h3>
          <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 font-semibold">
            {{ members.length }}
          </span>
        </div>
        <button
          v-if="canManage"
          class="text-xs font-semibold text-vikinger-purple"
          @click="emit('openManage', 'members')"
        >
          จัดการ
        </button>
      </div>

      <!-- Search -->
      <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700">
        <div class="relative">
          <Icon icon="heroicons:magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            v-model="query"
            type="text"
            placeholder="ค้นหาสมาชิกในส่วนงาน..."
            class="w-full pl-9 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-sm"
          />
        </div>
      </div>

      <!-- List -->
      <div class="divide-y divide-gray-100 dark:divide-gray-700">
        <div v-if="filteredMembers.length === 0" class="py-8 text-center text-sm text-gray-500">
          {{ query ? 'ไม่พบ' : 'ยังไม่มีสมาชิก' }}
        </div>
        <div
          v-for="m in filteredMembers"
          :key="m.id"
          class="flex items-center gap-3 px-5 py-3"
        >
          <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
            <img v-if="m.user?.profile_photo_path" :src="m.user.profile_photo_path" class="w-full h-full object-cover" />
            <Icon v-else icon="heroicons:user" class="w-full h-full p-2 text-gray-400" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
              {{ m.user?.name }}
            </div>
            <div class="text-xs text-gray-500">{{ roleLabel[m.role] || m.role || 'สมาชิก' }}</div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
```

---

# I.6 — `GroupAboutTab.vue`

**File:** `ui/components/academy/groups/GroupAboutTab.vue` (NEW)

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  group: any
  academy: any
  stats: any
  typeMeta: any
}
const props = defineProps<Props>()

const createdDate = computed(() => {
  if (!props.stats?.created_at) return '-'
  return new Date(props.stats.created_at).toLocaleDateString('th-TH', {
    year: 'numeric', month: 'long', day: 'numeric',
  })
})
</script>

<template>
  <div class="space-y-4">
    <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm p-5 md:p-6">
      <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <Icon icon="heroicons:information-circle" class="w-5 h-5 text-vikinger-purple" />
        เกี่ยวกับส่วนงาน
      </h3>

      <p v-if="group.description" class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mb-5">
        {{ group.description }}
      </p>
      <p v-else class="text-sm text-gray-400 italic mb-5">ยังไม่มีคำอธิบาย</p>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
        <div class="flex items-start gap-3">
          <Icon :icon="typeMeta?.icon || 'heroicons:squares-2x2'" class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" />
          <div>
            <div class="text-xs text-gray-500">ประเภท</div>
            <div class="font-semibold text-gray-900 dark:text-white">{{ typeMeta?.label || 'ไม่ระบุ' }}</div>
          </div>
        </div>

        <div class="flex items-start gap-3">
          <Icon icon="heroicons:building-library" class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" />
          <div>
            <div class="text-xs text-gray-500">สังกัด</div>
            <NuxtLink
              :to="`/academies/${academy?.name}`"
              class="font-semibold text-vikinger-purple hover:underline"
            >
              {{ academy?.name }}
            </NuxtLink>
          </div>
        </div>

        <div class="flex items-start gap-3">
          <Icon icon="heroicons:calendar" class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" />
          <div>
            <div class="text-xs text-gray-500">เปิดเมื่อ</div>
            <div class="font-semibold text-gray-900 dark:text-white">{{ createdDate }}</div>
          </div>
        </div>

        <div class="flex items-start gap-3">
          <Icon icon="heroicons:chart-bar" class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" />
          <div>
            <div class="text-xs text-gray-500">กิจกรรมรวม</div>
            <div class="font-semibold text-gray-900 dark:text-white">
              {{ stats?.posts_count ?? 0 }} โพสต์
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
```

---

# I.7 — Wire `onViewGroup` ใน `[name].vue`

**File:** `ui/pages/academies/[name].vue`
**Line:** 950-973 (SweetAlert stub)

**ลบทั้งบล็อก** (รวมถึง `escapeHtml` helper ถ้าใช้แค่ในนั้น) **แทนด้วย:**

```ts
const onViewGroup = (group: any) => {
  if (!group?.id) return
  navigateTo(`/academies/${academyName.value}/groups/${group.id}`)
}
```

---

# I.8 — Permission gating recap

| Action | Allowed when |
|---|---|
| ดูหน้า group | ทุกคน (group เป็น public ของโรงเรียน) |
| โพสต์ในนามกลุ่ม | `isGroupMember || isGroupAdmin` **และ** `groupPermissions.includes('can_post')` |
| ปุ่ม "จัดการ" | `isAcademyAdmin || isGroupAdmin` |
| Mute/Unmute | ทุกคนที่ login |
| Share | ทุกคน |

> Backend Phase G validation จะ enforce อีกชั้นตอน POST — frontend gating แค่ป้องกัน UI noise

---

# 📋 Phase I — Files Summary

## ✨ New files (6)

### Backend (0 new files — 2 methods added to existing controller)
```
✏️ AcademyGroupController.php   — เพิ่ม posts() + stats()
✏️ routes/learn/academy.php      — register 2 routes (×2 scopes)
```

### Frontend (6 new)
```
ui/pages/academies/[name]/groups/[groupId].vue       — Route page
ui/components/academy/groups/GroupProfileCover.vue   — Hero
ui/components/academy/groups/GroupFeedTab.vue        — Feed
ui/components/academy/groups/GroupMembersTab.vue     — Members
ui/components/academy/groups/GroupAboutTab.vue       — About
ui/composables/useAcademyGroups.ts (extend)          — listGroupPosts, getGroupStats
```

## 🔧 Modified files (3)
```
ui/pages/academies/[name].vue                         — onViewGroup → navigate
ui/components/play/feed/CreatePostBox.vue             — รับ postedAsGroupId prop
api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupController.php
```

---

# 🛣️ Commit plan (5 commits)

```
1. feat(api): add group posts + stats endpoints
   - I.0: posts(), stats() + routes

2. feat(ui): extend useAcademyGroups with group profile API helpers
   - I.1

3. feat(ui): add group profile route + cover hero
   - I.2 + I.3

4. feat(ui): add group feed + members + about tabs
   - I.4 + I.5 + I.6 + composer postedAsGroupId support

5. feat(ui): navigate to group profile page on view click
   - I.7 (remove SweetAlert stub)
```

---

# ✅ Phase I — Test Checklist

## Pre-flight
- [ ] `php artisan route:list | grep "groups.*posts\|groups.*stats"` แสดง 4 routes (×2 scope)
- [ ] `curl /api/academies/groups/{id}/posts` คืน `{success:true, data: paginated}`
- [ ] `curl /api/academies/groups/{id}/stats` คืน 3 counts + created_at

## Navigation
- [ ] เปิด `/academies/{name}` → tab "ส่วนงาน" → กดการ์ดส่วนงาน → **navigate ไปหน้า profile** (ไม่ใช่ SweetAlert)
- [ ] Breadcrumb แสดง: ชื่อโรงเรียน › ส่วนงาน › ชื่อกลุ่ม → คลิกได้
- [ ] URL hash `#feed`, `#members`, `#about` รักษา tab หลัง reload

## Cover (I.3)
- [ ] Gradient cover ใช้สีตาม `type` ของกลุ่ม (purple/cyan/pink ฯลฯ)
- [ ] Icon medallion ตรงตาม type
- [ ] Stats inline แสดง members/posts ตามจริง
- [ ] ปุ่ม "จัดการ" แสดงเฉพาะ academy admin หรือ group admin
- [ ] ปุ่ม share — desktop = clipboard / mobile = native share sheet
- [ ] ปุ่ม bell → ปิด/เปิดเตือน (mute/unmute) ทดสอบกับ DB `user_muted_groups`

## Feed tab (I.4)
- [ ] โพสต์ที่ `posted_as_group_id = groupId` แสดงในรายการ
- [ ] **Composer แสดงเฉพาะคนที่ canPost** (member + can_post enabled)
- [ ] non-member เห็น info panel แทน composer
- [ ] โพสต์ใหม่ใส่ `posted_as_group_id` ใน body (backend Phase G จะ validate)
- [ ] Load more pagination ใช้ได้

## Members tab (I.5)
- [ ] Admins banner แสดง avatar + ชื่อ + role
- [ ] Members list มี search local
- [ ] ปุ่ม "จัดการ" → open `GroupManageModal` (Phase H) บน tab admins/members ตามที่กด

## About tab (I.6)
- [ ] description / type / academy / createdDate / posts_count ครบ
- [ ] คลิก academy name → ไปหน้าโรงเรียน

## Permission gating
- [ ] Login เป็น non-member → composer ซ่อน + ปุ่มจัดการซ่อน
- [ ] Login เป็น group member + can_post = true → composer แสดง
- [ ] Login เป็น academy admin → ปุ่มจัดการแสดงเสมอ

## Regression
- [ ] หน้า `/academies/{name}` ยังโหลดได้ปกติ
- [ ] tab อื่นๆ ทำงานได้
- [ ] `GroupCreateModal`/`GroupManageModal` ใน Phase H ยังเปิดได้

---

# ⚠️ Pitfalls & Notes

## 1. `CreatePostBox` prop addition
- ปัจจุบัน `PlayFeedCreatePostBox` รับ `context`, `contextId`, `contextName`
- ต้องเพิ่ม `postedAsGroupId?: number` + ส่งเข้า body ตอน submit
- ⚠️ **กระทบหน้า academy feed เดิมไหม?** ไม่ — ถ้าไม่ส่ง prop, body จะไม่มี `posted_as_group_id` → backend treat เป็น user post

## 2. `[name]/groups/[groupId].vue` vs child route handling
- หน้า `[name].vue` มี `isChildRoute` check (อ่านจาก code เดิม) — ส่งผลให้ render `<NuxtPage>` แทน main content
- ตรวจว่า groups child route ถูก detect ถูกต้อง → ดู `isChildRoute` logic
- ถ้าไม่ — group profile จะไม่แสดง (academy content render ทับ)

## 3. groupId เป็น string จาก route param
- ใช้ `Number(route.params.groupId)` แปลงเป็น number ก่อนใช้ใน API call
- ใส่ `computed` ที่ guard `if (Number.isNaN(...))` → 404

## 4. Permission state อาจ stale
- ถ้า admin เปิด `can_post` ใน `GroupManageModal` ตอนเปิดหน้า profile → composer ไม่ update real-time
- ทางแก้: emit `permissions-updated` จาก ManageModal → reload `groupPermissions`
- หรือ simpler: refresh ทั้ง `loadGroup()` หลังปิด modal

## 5. SSR safety
- `navigator.share` / `navigator.clipboard` ใช้บน client only
- ใช้ใน `share()` function ที่เรียกจาก click event → ปลอดภัย (event = client-side)
- ถ้าใช้ใน `setup()` ต้อง guard ด้วย `if (import.meta.client) { ... }`

## 6. URL hash sync
- ใช้ `history.replaceState` แทน `router.push` เพื่อไม่สร้าง history entry ทุกครั้ง tab change
- เพื่อให้ back button ทำงานปกติ (ไม่ต้องกด 5 ครั้งเพื่อออกจากหน้า)

## 7. Empty admins/members race
- ตอน initial load, sidebar อาจแสดง "ยังไม่มีหัวหน้า" ก่อน data โหลดเสร็จ → ใช้ `v-if="!isLoading"` รอบ widget
- หรือ skeleton placeholder

## 8. Posts count vs Activities count
- `stats.posts_count` = `AcademyPost::where('posted_as_group_id')` — ไม่ใช่ feed activities count
- ถ้าคนใน group post ส่วนตัว (ไม่ได้ใช้ posted_as_group_id) → ไม่นับ
- ตรงตาม design intent: count "โพสต์ในนามกลุ่ม"

---

# 🎯 ลำดับงานแนะนำ

```
1. I.0 backend → verify curl คืน data
2. I.1 composable → console test
3. I.2 page shell → mock data ก่อนก็ได้, เห็น layout
4. I.3 cover → ตรงนี้สวยที่สุด ดู design ออก
5. I.4 feed → ทดสอบ post-as-group submit
6. I.5 members → reuse pattern จาก H.5/H.6
7. I.6 about → เร็ว
8. I.7 wire → 5 นาที + ทดสอบ navigate
```

หลัง I เสร็จ — มีหน้า academy + หน้า group profile ครบ พร้อมเข้า:
- **Phase J** (Post-as-group ใน composer หลัก + FeedPost group header) — ทำให้โพสต์ของกลุ่มแสดงสวยใน main feed ด้วย
- **Phase K** (Invite + appointment workflow + notifications)

ติดตรงไหนตอนทำมาถามได้เลยครับ 🙌

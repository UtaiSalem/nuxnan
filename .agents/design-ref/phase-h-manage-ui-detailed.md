# Phase H — UI: Manage Departments (Admin) — Detailed DIY Plan

อ้างอิง: [decisions](./school-departments-decisions.md) + [Phase G ที่เสร็จแล้ว](./phase-g-backend-detailed.md)
Target: ให้ Academy admin จัดการส่วนงานได้ครบ — แก้ข้อมูล / แต่งตั้งหัวหน้า / เชิญสมาชิก / ตั้งสิทธิ์ / ลบ
วันที่: 2026-06-20

---

## 📌 Pre-check: Endpoint ที่มีอยู่แล้วใน Backend

ผม audit ระบบให้แล้ว — เกือบทุกอย่างมี

### ✅ Group management (พร้อมใช้)
```
PATCH  /api/academies/groups/{academyGroup}                — update name/description/type/parent_id
DELETE /api/academies/groups/{academyGroup}                — destroy
GET    /api/academies/groups/{academyGroup}/members        — list members
POST   /api/academies/groups/{academyGroup}/members        — add (Phase G validates academy member + dup)
DELETE /api/academies/groups/{academyGroup}/members        — remove
PATCH  /api/academies/groups/{academyGroup}/members/role   — change role
```

### ✅ Permission management (พร้อมใช้ — `DepartmentController` namespace)
```
GET    /api/academies/{academy}/departments/{department}/permissions
PUT    /api/academies/{academy}/departments/{department}/permissions
```
> 💡 `{department}` route uses ID — ก็คือ `academy_group_id` (ใช้ table เดียวกัน)

### ✅ Academy member search (สำหรับ autocomplete)
```
GET    /api/academies/{academy}/members?search=...           — list (paginated)
GET    /api/academies/{academy}/members/search?q=...         — autocomplete
```

### ❌ Group Admin (หัวหน้า) — **ยังไม่ได้ register route**
- `AcademyGroupAdminController.php` มีอยู่ + มี methods `index/store/destroy/update`
- แต่ route block ยังไม่ถูกประกาศใน `routes/learn/academy.php`
- ⚠️ **H.0 จะแก้จุดนี้**

---

# 🗺️ H Sub-phases Overview

| # | งาน | Est. |
|---|---|---|
| **H.0** | Backend gap-fill: register Group Admin routes | 30 min |
| **H.1** | `useAcademyGroups.ts` composable | 1 hr |
| **H.2** | `GroupManageModal.vue` shell + tabs | 1 hr |
| **H.3** | Tab 1: ข้อมูล (Info form) | 1 hr |
| **H.4** | `MemberAutocompleteInput.vue` (shared) | 1 hr |
| **H.5** | Tab 2: หัวหน้า (Admin list + add/remove) | 1.5 hr |
| **H.6** | Tab 3: สมาชิก (Member list + add/remove + role) | 1.5 hr |
| **H.7** | Tab 4: สิทธิ์ (Permission toggles) | 1 hr |
| **H.8** | Tab 5: ลบกลุ่ม (Delete confirm) | 30 min |
| **H.9** | Wire `onManageGroup` ใน [name].vue | 15 min |
| **H.10** | `useAcademyGroupTypes.ts` (backend-driven type sync) | 45 min |
| **รวม** | | **~10 ชม.** |

---

# H.0 — Backend gap-fill: Group Admin routes

**Goal:** เพิ่ม CRUD route สำหรับ `AcademyGroupAdmin` (หัวหน้าของกลุ่ม)

## H.0.1 — ตรวจ `AcademyGroupAdminController` ก่อน

```bash
cat api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupAdminController.php | head -100
```

ถ้า methods `index/store/destroy` ยัง **เป็น stub** (return view หรือ blank) → ต้องเขียนใหม่:

**File:** `api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupAdminController.php` (rewrite)

```php
<?php

namespace App\Http\Controllers\Api\Learn\Academy;

use App\Http\Controllers\Controller;
use App\Models\AcademyGroup;
use App\Models\AcademyGroupAdmin;
use App\Models\AcademyMember;
use Illuminate\Http\Request;

class AcademyGroupAdminController extends Controller
{
    /**
     * GET /groups/{academyGroup}/admins
     */
    public function index(AcademyGroup $academyGroup)
    {
        $admins = AcademyGroupAdmin::with('user:id,name,profile_photo_path,email')
            ->where('academy_group_id', $academyGroup->id)
            ->get();

        return response()->json([
            'success' => true,
            'admins'  => $admins,
        ]);
    }

    /**
     * POST /groups/{academyGroup}/admins
     * Body: { user_id, role? = 'leader' }
     */
    public function store(Request $request, AcademyGroup $academyGroup)
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role'    => 'nullable|string|in:leader,co_leader,advisor',
        ]);

        // 1. ต้องเป็น academy member ที่ approved (status = 2)
        $isAcademyMember = AcademyMember::where('academy_id', $academyGroup->academy_id)
            ->where('user_id', $data['user_id'])
            ->where('status', 2)
            ->exists();

        if (!$isAcademyMember) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้ใช้รายนี้ยังไม่ใช่สมาชิกที่ได้รับการอนุมัติของโรงเรียน',
            ], 422);
        }

        // 2. ป้องกัน duplicate
        $exists = AcademyGroupAdmin::where('academy_group_id', $academyGroup->id)
            ->where('user_id', $data['user_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'ผู้ใช้รายนี้เป็นหัวหน้าของส่วนงานนี้อยู่แล้ว',
            ], 422);
        }

        $admin = AcademyGroupAdmin::create([
            'academy_group_id' => $academyGroup->id,
            'user_id'          => $data['user_id'],
            'role'             => $data['role'] ?? 'leader',
        ]);

        return response()->json([
            'success' => true,
            'admin'   => $admin->load('user:id,name,profile_photo_path'),
        ], 201);
    }

    /**
     * DELETE /groups/{academyGroup}/admins
     * Body: { user_id }
     */
    public function destroy(Request $request, AcademyGroup $academyGroup)
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $deleted = AcademyGroupAdmin::where('academy_group_id', $academyGroup->id)
            ->where('user_id', $data['user_id'])
            ->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบหัวหน้ารายนี้ในส่วนงาน',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'นำหัวหน้าออกแล้ว',
        ]);
    }

    /**
     * PATCH /groups/{academyGroup}/admins/role
     * Body: { user_id, role }
     */
    public function updateRole(Request $request, AcademyGroup $academyGroup)
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role'    => 'required|string|in:leader,co_leader,advisor',
        ]);

        $admin = AcademyGroupAdmin::where('academy_group_id', $academyGroup->id)
            ->where('user_id', $data['user_id'])
            ->first();

        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'ไม่พบ'], 404);
        }

        $admin->role = $data['role'];
        $admin->save();

        return response()->json(['success' => true, 'admin' => $admin]);
    }
}
```

## H.0.2 — Register routes

**File:** `api/nuxnanravel/routes/learn/academy.php`
**Location:** ใต้ routes สำหรับ `/groups/{academyGroup}/members` (ราว line 79-82) เพิ่ม:

```php
use App\Http\Controllers\Api\Learn\Academy\AcademyGroupAdminController;
// ...
Route::get('/groups/{academyGroup}/admins',           [AcademyGroupAdminController::class, 'index'])->name('api.academy.groups.admins.index');
Route::post('/groups/{academyGroup}/admins',          [AcademyGroupAdminController::class, 'store'])->name('api.academy.groups.admins.add');
Route::delete('/groups/{academyGroup}/admins',        [AcademyGroupAdminController::class, 'destroy'])->name('api.academy.groups.admins.remove');
Route::patch('/groups/{academyGroup}/admins/role',    [AcademyGroupAdminController::class, 'updateRole'])->name('api.academy.groups.admins.updateRole');
```

## H.0.3 — Verify
```bash
php artisan route:list | grep "groups.*admin"
# ควรเห็น 4 routes
```

---

# H.1 — Composable `useAcademyGroups.ts`

**Goal:** DRY API calls — ทุกที่ใน UI จะเรียกผ่าน composable นี้

**File:** `ui/composables/useAcademyGroups.ts` (NEW)

```ts
/**
 * useAcademyGroups — encapsulate all academy_group + member + admin + permission API calls.
 * Group profile page (Phase I) and feed components (Phase J) will reuse this composable.
 */
export const useAcademyGroups = () => {
  const api = useApi()

  // ===== Group CRUD =====
  const getGroup = (groupId: number) =>
    api.call(`/api/academies/groups/${groupId}`)

  const updateGroup = (groupId: number, payload: {
    name?: string
    description?: string | null
    type?: string
    parent_id?: number | null
  }) => api.call(`/api/academies/groups/${groupId}`, { method: 'PATCH', body: payload })

  const deleteGroup = (groupId: number) =>
    api.call(`/api/academies/groups/${groupId}`, { method: 'DELETE' })

  // ===== Members =====
  const listMembers = (groupId: number, params?: { search?: string }) =>
    api.call(`/api/academies/groups/${groupId}/members`, { params })

  const addMember = (groupId: number, userId: number, role: 'member' | 'leader' | 'observer' = 'member') =>
    api.call(`/api/academies/groups/${groupId}/members`, {
      method: 'POST',
      body: { user_id: userId, role },
    })

  const removeMember = (groupId: number, userId: number) =>
    api.call(`/api/academies/groups/${groupId}/members`, {
      method: 'DELETE',
      body: { user_id: userId },
    })

  const updateMemberRole = (groupId: number, userId: number, role: string) =>
    api.call(`/api/academies/groups/${groupId}/members/role`, {
      method: 'PATCH',
      body: { user_id: userId, role },
    })

  // ===== Admins (หัวหน้า) =====
  const listAdmins = (groupId: number) =>
    api.call(`/api/academies/groups/${groupId}/admins`)

  const addAdmin = (groupId: number, userId: number, role: 'leader' | 'co_leader' | 'advisor' = 'leader') =>
    api.call(`/api/academies/groups/${groupId}/admins`, {
      method: 'POST',
      body: { user_id: userId, role },
    })

  const removeAdmin = (groupId: number, userId: number) =>
    api.call(`/api/academies/groups/${groupId}/admins`, {
      method: 'DELETE',
      body: { user_id: userId },
    })

  const updateAdminRole = (groupId: number, userId: number, role: string) =>
    api.call(`/api/academies/groups/${groupId}/admins/role`, {
      method: 'PATCH',
      body: { user_id: userId, role },
    })

  // ===== Permissions =====
  const listPermissions = (academyId: number | string, groupId: number) =>
    api.call(`/api/academies/${academyId}/departments/${groupId}/permissions`)

  const updatePermissions = (
    academyId: number | string,
    groupId: number,
    permissions: Record<string, boolean>,
  ) =>
    api.call(`/api/academies/${academyId}/departments/${groupId}/permissions`, {
      method: 'PUT',
      body: { permissions },
    })

  // ===== Mute (Phase G) =====
  const mute = (groupId: number) =>
    api.call(`/api/academies/groups/${groupId}/mute`, { method: 'POST' })

  const unmute = (groupId: number) =>
    api.call(`/api/academies/groups/${groupId}/mute`, { method: 'DELETE' })

  return {
    // group
    getGroup, updateGroup, deleteGroup,
    // members
    listMembers, addMember, removeMember, updateMemberRole,
    // admins
    listAdmins, addAdmin, removeAdmin, updateAdminRole,
    // permissions
    listPermissions, updatePermissions,
    // mute
    mute, unmute,
  }
}
```

> ⚠️ ปรับ `api.call` vs `api.post`/`api.patch` ตาม signature ของ `useApi` ใน project (เคยใช้ `api.post` ใน [name].vue) — ดูตัวอย่างจริงก่อน

---

# H.2 — GroupManageModal shell + tab navigation

**File:** `ui/components/academy/groups/GroupManageModal.vue` (NEW)

```vue
<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { getAcademyGroupTypeMeta } from '~/constants/academyGroupTypes'

interface Props {
  open: boolean
  group: any | null              // AcademyGroup
  academyId: number | string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:open': [value: boolean]
  close: []
  updated: [group: any]          // emit after info save
  deleted: [groupId: number]     // emit after delete
}>()

type TabKey = 'info' | 'admins' | 'members' | 'permissions' | 'delete'

const tabs: Array<{ key: TabKey; label: string; icon: string }> = [
  { key: 'info',        label: 'ข้อมูล',       icon: 'heroicons:information-circle' },
  { key: 'admins',      label: 'หัวหน้า',     icon: 'heroicons:star' },
  { key: 'members',     label: 'สมาชิก',      icon: 'heroicons:user-group' },
  { key: 'permissions', label: 'สิทธิ์',       icon: 'heroicons:shield-check' },
  { key: 'delete',      label: 'ลบส่วนงาน',  icon: 'heroicons:trash' },
]

const activeTab = ref<TabKey>('info')

// Reset tab to "info" each time modal opens
watch(() => props.open, (isOpen) => {
  if (isOpen) activeTab.value = 'info'
})

const typeMeta = computed(() => props.group ? getAcademyGroupTypeMeta(props.group.type) : null)

const close = () => {
  emit('update:open', false)
  emit('close')
}

// Forward sub-tab events upward
const onInfoUpdated = (g: any) => {
  emit('updated', g)
}
const onGroupDeleted = (id: number) => {
  emit('deleted', id)
  close()
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && group"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      @click.self="close"
    >
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

      <div class="relative bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between flex-shrink-0">
          <div class="flex items-center gap-3 min-w-0">
            <span
              v-if="typeMeta"
              class="w-10 h-10 rounded-lg flex items-center justify-center bg-vikinger-purple/10 text-vikinger-purple flex-shrink-0"
            >
              <Icon :icon="typeMeta.icon" class="w-5 h-5" />
            </span>
            <div class="min-w-0">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate">
                จัดการ {{ group.name }}
              </h3>
              <p v-if="typeMeta" class="text-xs text-gray-500 dark:text-gray-400">
                {{ typeMeta.label }}
              </p>
            </div>
          </div>
          <button
            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
            @click="close"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
          </button>
        </div>

        <!-- Tabs -->
        <div class="px-2 border-b border-gray-100 dark:border-gray-700 flex-shrink-0 overflow-x-auto">
          <div class="flex">
            <button
              v-for="t in tabs"
              :key="t.key"
              type="button"
              @click="activeTab = t.key"
              :class="[
                'flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors whitespace-nowrap',
                activeTab === t.key
                  ? 'text-vikinger-purple border-b-2 border-vikinger-purple'
                  : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200',
                t.key === 'delete' && 'text-red-500 dark:text-red-400'
              ]"
            >
              <Icon :icon="t.icon" class="w-4 h-4" />
              {{ t.label }}
            </button>
          </div>
        </div>

        <!-- Body (each tab is its own component) -->
        <div class="p-6 overflow-y-auto flex-1">
          <AcademyGroupsManageTabInfo
            v-if="activeTab === 'info'"
            :group="group"
            @updated="onInfoUpdated"
          />
          <AcademyGroupsManageTabAdmins
            v-else-if="activeTab === 'admins'"
            :group="group"
            :academy-id="academyId"
          />
          <AcademyGroupsManageTabMembers
            v-else-if="activeTab === 'members'"
            :group="group"
            :academy-id="academyId"
          />
          <AcademyGroupsManageTabPermissions
            v-else-if="activeTab === 'permissions'"
            :group="group"
            :academy-id="academyId"
          />
          <AcademyGroupsManageTabDelete
            v-else-if="activeTab === 'delete'"
            :group="group"
            @deleted="onGroupDeleted"
            @cancel="activeTab = 'info'"
          />
        </div>
      </div>
    </div>
  </Teleport>
</template>
```

---

# H.3 — Tab 1: ข้อมูล (Info form)

**File:** `ui/components/academy/groups/ManageTabInfo.vue` (NEW)

```vue
<script setup lang="ts">
import { ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { ACADEMY_GROUP_TYPES } from '~/constants/academyGroupTypes'

interface Props {
  group: any
}
const props = defineProps<Props>()
const emit = defineEmits<{ updated: [group: any] }>()

const { updateGroup } = useAcademyGroups()

const form = ref({
  name: props.group.name,
  description: props.group.description || '',
  type: props.group.type,
})
const isSaving = ref(false)
const isDirty = computed(() =>
  form.value.name !== props.group.name ||
  form.value.description !== (props.group.description || '') ||
  form.value.type !== props.group.type
)

// Reset whenever the group prop swaps
watch(() => props.group?.id, () => {
  form.value = {
    name: props.group.name,
    description: props.group.description || '',
    type: props.group.type,
  }
})

const save = async () => {
  if (!form.value.name.trim() || isSaving.value) return
  isSaving.value = true
  try {
    const res: any = await updateGroup(props.group.id, {
      name: form.value.name,
      description: form.value.description || null,
      type: form.value.type,
    })
    if (res?.success) {
      emit('updated', res.group)
      Swal.fire({ icon: 'success', title: 'บันทึกแล้ว', timer: 1500, showConfirmButton: false })
    }
  } catch (e: any) {
    Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', text: e?.data?.message || '' })
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="space-y-5">
    <!-- Name -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        ชื่อส่วนงาน <span class="text-red-500">*</span>
      </label>
      <input
        v-model="form.name"
        type="text"
        class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"
      />
    </div>

    <!-- Type -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท</label>
      <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
        <button
          v-for="gtype in ACADEMY_GROUP_TYPES"
          :key="gtype.key"
          type="button"
          :class="[
            'p-2.5 rounded-lg border-2 flex flex-col items-center gap-1.5 transition-all',
            form.type === gtype.key
              ? 'border-vikinger-purple bg-vikinger-purple/10 text-vikinger-purple'
              : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-vikinger-purple/50'
          ]"
          @click="form.type = gtype.key"
        >
          <Icon :icon="gtype.icon" class="w-5 h-5" />
          <span class="text-[11px] font-semibold leading-tight">{{ gtype.label }}</span>
        </button>
      </div>
    </div>

    <!-- Description -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รายละเอียด</label>
      <textarea
        v-model="form.description"
        rows="3"
        class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50 resize-none"
      ></textarea>
    </div>

    <!-- Save button -->
    <div class="flex justify-end pt-3 border-t border-gray-100 dark:border-gray-700">
      <button
        type="button"
        :disabled="!isDirty || !form.name.trim() || isSaving"
        class="px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium hover:bg-vikinger-purple/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
        @click="save"
      >
        <Icon v-if="isSaving" icon="svg-spinners:ring-resize" class="w-4 h-4" />
        <Icon v-else icon="heroicons:check" class="w-4 h-4" />
        บันทึก
      </button>
    </div>
  </div>
</template>
```

> 📌 **เพิ่ม `parent_id` field** ถ้าจะรองรับ hierarchy ใน v1 → เพิ่ม `<select>` รายชื่อกลุ่มอื่นในโรงเรียน (skip self + descendants) แต่แนะนำเก็บไว้ Phase L ตามแผน

---

# H.4 — MemberAutocompleteInput (shared by H.5 + H.6)

**File:** `ui/components/academy/groups/MemberAutocompleteInput.vue` (NEW)

```vue
<script setup lang="ts">
import { ref, watch } from 'vue'
import { Icon } from '@iconify/vue'

interface AcademyMember {
  id: number
  user_id: number
  user: {
    id: number
    name: string
    profile_photo_path?: string | null
    email?: string
  }
}

interface Props {
  academyId: number | string
  /** IDs ที่ต้องไม่แสดงในผลค้นหา (เช่น members ปัจจุบัน) */
  excludeUserIds?: number[]
  placeholder?: string
}

const props = withDefaults(defineProps<Props>(), {
  excludeUserIds: () => [],
  placeholder: 'ค้นหาสมาชิกในโรงเรียน...',
})
const emit = defineEmits<{ select: [member: AcademyMember] }>()

const api = useApi()
const query = ref('')
const results = ref<AcademyMember[]>([])
const isLoading = ref(false)
const showDropdown = ref(false)

let searchTimer: any = null

const search = async () => {
  if (query.value.trim().length < 2) {
    results.value = []
    return
  }
  isLoading.value = true
  try {
    const res: any = await api.call(`/api/academies/${props.academyId}/members/search`, {
      params: { q: query.value, status: 2 },
    })
    const raw = res?.data ?? res?.members ?? []
    results.value = raw.filter((m: AcademyMember) => !props.excludeUserIds.includes(m.user_id))
  } catch {
    results.value = []
  } finally {
    isLoading.value = false
  }
}

watch(query, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(search, 250) // debounce 250ms
})

const select = (member: AcademyMember) => {
  emit('select', member)
  query.value = ''
  results.value = []
  showDropdown.value = false
}
</script>

<template>
  <div class="relative">
    <div class="relative">
      <Icon icon="heroicons:magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
      <input
        v-model="query"
        type="text"
        :placeholder="placeholder"
        class="w-full pl-9 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"
        @focus="showDropdown = true"
      />
      <Icon
        v-if="isLoading"
        icon="svg-spinners:ring-resize"
        class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-vikinger-purple"
      />
    </div>

    <!-- Dropdown results -->
    <div
      v-if="showDropdown && query.length >= 2 && results.length > 0"
      class="absolute z-10 mt-1 w-full bg-white dark:bg-vikinger-dark-100 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 max-h-64 overflow-y-auto"
    >
      <button
        v-for="m in results"
        :key="m.id"
        type="button"
        class="w-full flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200 text-left"
        @click="select(m)"
      >
        <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
          <img
            v-if="m.user.profile_photo_path"
            :src="m.user.profile_photo_path"
            class="w-full h-full object-cover"
          />
          <Icon v-else icon="heroicons:user" class="w-full h-full p-1.5 text-gray-400" />
        </div>
        <div class="min-w-0">
          <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
            {{ m.user.name }}
          </div>
          <div v-if="m.user.email" class="text-xs text-gray-500 dark:text-gray-400 truncate">
            {{ m.user.email }}
          </div>
        </div>
      </button>
    </div>

    <div
      v-else-if="showDropdown && query.length >= 2 && !isLoading && results.length === 0"
      class="absolute z-10 mt-1 w-full bg-white dark:bg-vikinger-dark-100 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 px-3 py-3 text-center text-sm text-gray-500"
    >
      ไม่พบสมาชิก
    </div>
  </div>
</template>
```

**Test:** เปิด tab Admin หรือ Member → พิมพ์ค้นหา → ดู debounce + dropdown แสดงผลถูก

---

# H.5 — Tab 2: หัวหน้า (Admins)

**File:** `ui/components/academy/groups/ManageTabAdmins.vue` (NEW)

```vue
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

interface Props {
  group: any
  academyId: number | string
}
const props = defineProps<Props>()

const { listAdmins, addAdmin, removeAdmin, updateAdminRole } = useAcademyGroups()

const admins = ref<any[]>([])
const isLoading = ref(true)

const load = async () => {
  isLoading.value = true
  try {
    const res: any = await listAdmins(props.group.id)
    admins.value = res?.admins ?? []
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

const excludeUserIds = computed(() => admins.value.map((a) => a.user_id))

const onSelectMember = async (member: any) => {
  try {
    const res: any = await addAdmin(props.group.id, member.user_id, 'leader')
    if (res?.success) {
      admins.value.push(res.admin)
      Swal.fire({ icon: 'success', title: 'แต่งตั้งเป็นหัวหน้าแล้ว', timer: 1500, showConfirmButton: false })
    }
  } catch (e: any) {
    Swal.fire({ icon: 'error', title: 'เพิ่มไม่สำเร็จ', text: e?.data?.message || '' })
  }
}

const onRemove = async (admin: any) => {
  const ok = await Swal.fire({
    icon: 'warning',
    title: 'ปลดหัวหน้า?',
    text: `นำคุณ ${admin.user?.name} ออกจากหัวหน้า`,
    showCancelButton: true,
    confirmButtonText: 'ปลด',
    cancelButtonText: 'ยกเลิก',
  })
  if (!ok.isConfirmed) return
  try {
    await removeAdmin(props.group.id, admin.user_id)
    admins.value = admins.value.filter((a) => a.id !== admin.id)
  } catch (e: any) {
    Swal.fire({ icon: 'error', title: 'ลบไม่สำเร็จ' })
  }
}

const onChangeRole = async (admin: any, newRole: string) => {
  try {
    await updateAdminRole(props.group.id, admin.user_id, newRole)
    admin.role = newRole
  } catch {
    Swal.fire({ icon: 'error', title: 'เปลี่ยนตำแหน่งไม่สำเร็จ' })
  }
}

const roleLabel: Record<string, string> = {
  leader: 'หัวหน้า',
  co_leader: 'รองหัวหน้า',
  advisor: 'ที่ปรึกษา',
}
</script>

<template>
  <div class="space-y-4">
    <p class="text-sm text-gray-500 dark:text-gray-400">
      Academy admin สามารถแต่งตั้ง/ปลดหัวหน้าของส่วนงาน — หัวหน้าจะมีสิทธิ์เชิญสมาชิกและจัดการกลุ่ม
    </p>

    <!-- Search & add -->
    <AcademyGroupsMemberAutocompleteInput
      :academy-id="academyId"
      :exclude-user-ids="excludeUserIds"
      placeholder="พิมพ์ชื่อสมาชิกโรงเรียนเพื่อแต่งตั้งเป็นหัวหน้า..."
      @select="onSelectMember"
    />

    <!-- Admin list -->
    <div v-if="isLoading" class="py-6 text-center">
      <Icon icon="svg-spinners:ring-resize" class="w-6 h-6 text-vikinger-purple mx-auto" />
    </div>
    <div v-else-if="admins.length === 0" class="py-8 text-center text-sm text-gray-500">
      ยังไม่มีหัวหน้า
    </div>
    <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
      <li
        v-for="admin in admins"
        :key="admin.id"
        class="flex items-center gap-3 py-3"
      >
        <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
          <img v-if="admin.user?.profile_photo_path" :src="admin.user.profile_photo_path" class="w-full h-full object-cover" />
          <Icon v-else icon="heroicons:user" class="w-full h-full p-2 text-gray-400" />
        </div>
        <div class="flex-1 min-w-0">
          <div class="font-semibold text-gray-900 dark:text-white truncate">
            {{ admin.user?.name || 'ผู้ใช้' }}
          </div>
          <div class="text-xs text-gray-500">{{ roleLabel[admin.role] || admin.role }}</div>
        </div>
        <select
          :value="admin.role"
          @change="onChangeRole(admin, ($event.target as HTMLSelectElement).value)"
          class="text-xs rounded border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100"
        >
          <option value="leader">หัวหน้า</option>
          <option value="co_leader">รองหัวหน้า</option>
          <option value="advisor">ที่ปรึกษา</option>
        </select>
        <button
          type="button"
          class="p-1.5 text-gray-400 hover:text-red-500"
          @click="onRemove(admin)"
        >
          <Icon icon="heroicons:trash" class="w-4 h-4" />
        </button>
      </li>
    </ul>
  </div>
</template>
```

---

# H.6 — Tab 3: สมาชิก (Members)

**File:** `ui/components/academy/groups/ManageTabMembers.vue` (NEW)

โครงคล้าย Tab Admins มาก — ต่างที่:
- `role` เป็น `member` / `leader` / `observer` (จาก backend validation enum)
- เพิ่มช่อง search ผลลัพธ์เดิม (filter member list)
- ใช้ `addMember` / `removeMember` / `updateMemberRole`

```vue
<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

interface Props { group: any; academyId: number | string }
const props = defineProps<Props>()

const { listMembers, addMember, removeMember, updateMemberRole } = useAcademyGroups()

const members = ref<any[]>([])
const isLoading = ref(true)
const filter = ref('')

const load = async () => {
  isLoading.value = true
  try {
    const res: any = await listMembers(props.group.id)
    members.value = res?.members ?? res?.data ?? []
  } finally {
    isLoading.value = false
  }
}
onMounted(load)

const filteredMembers = computed(() => {
  const q = filter.value.toLowerCase().trim()
  if (!q) return members.value
  return members.value.filter((m) =>
    (m.user?.name || '').toLowerCase().includes(q),
  )
})

const excludeUserIds = computed(() => members.value.map((m) => m.user_id))

const onSelectMember = async (member: any) => {
  try {
    const res: any = await addMember(props.group.id, member.user_id, 'member')
    if (res?.success) {
      members.value.push(res.member ?? res.data)
      Swal.fire({ icon: 'success', title: 'เพิ่มสมาชิกแล้ว', timer: 1500, showConfirmButton: false })
    }
  } catch (e: any) {
    Swal.fire({ icon: 'error', title: 'เพิ่มไม่สำเร็จ', text: e?.data?.message || '' })
  }
}

const onRemove = async (m: any) => {
  const ok = await Swal.fire({
    icon: 'warning', title: 'ออกจากกลุ่ม?', text: `นำคุณ ${m.user?.name} ออก`,
    showCancelButton: true, confirmButtonText: 'นำออก', cancelButtonText: 'ยกเลิก',
  })
  if (!ok.isConfirmed) return
  try {
    await removeMember(props.group.id, m.user_id)
    members.value = members.value.filter((x) => x.id !== m.id)
  } catch {
    Swal.fire({ icon: 'error', title: 'ลบไม่สำเร็จ' })
  }
}

const onChangeRole = async (m: any, role: string) => {
  try {
    await updateMemberRole(props.group.id, m.user_id, role)
    m.role = role
  } catch {
    Swal.fire({ icon: 'error', title: 'เปลี่ยนตำแหน่งไม่สำเร็จ' })
  }
}

const roleLabel: Record<string, string> = {
  member: 'สมาชิก', leader: 'หัวหน้าทีม', observer: 'ผู้สังเกตการณ์',
}
</script>

<template>
  <div class="space-y-4">
    <p class="text-sm text-gray-500 dark:text-gray-400">
      เชิญสมาชิกของโรงเรียนเข้าร่วมส่วนงานนี้
    </p>

    <AcademyGroupsMemberAutocompleteInput
      :academy-id="academyId"
      :exclude-user-ids="excludeUserIds"
      placeholder="พิมพ์ชื่อสมาชิกโรงเรียนเพื่อเชิญ..."
      @select="onSelectMember"
    />

    <!-- Filter local list -->
    <div class="relative">
      <Icon icon="heroicons:funnel" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
      <input
        v-model="filter"
        type="text"
        placeholder="กรองสมาชิกในส่วนงาน..."
        class="w-full pl-9 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-sm"
      />
    </div>

    <div v-if="isLoading" class="py-6 text-center">
      <Icon icon="svg-spinners:ring-resize" class="w-6 h-6 text-vikinger-purple mx-auto" />
    </div>
    <div v-else-if="filteredMembers.length === 0" class="py-8 text-center text-sm text-gray-500">
      ยังไม่มีสมาชิก
    </div>
    <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700 max-h-96 overflow-y-auto">
      <li v-for="m in filteredMembers" :key="m.id" class="flex items-center gap-3 py-2.5">
        <div class="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
          <img v-if="m.user?.profile_photo_path" :src="m.user.profile_photo_path" class="w-full h-full object-cover" />
          <Icon v-else icon="heroicons:user" class="w-full h-full p-1.5 text-gray-400" />
        </div>
        <div class="flex-1 min-w-0">
          <div class="font-medium text-sm text-gray-900 dark:text-white truncate">{{ m.user?.name }}</div>
          <div class="text-xs text-gray-500">{{ roleLabel[m.role] || m.role || 'สมาชิก' }}</div>
        </div>
        <select
          :value="m.role || 'member'"
          @change="onChangeRole(m, ($event.target as HTMLSelectElement).value)"
          class="text-xs rounded border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100"
        >
          <option value="member">สมาชิก</option>
          <option value="leader">หัวหน้าทีม</option>
          <option value="observer">ผู้สังเกตการณ์</option>
        </select>
        <button class="p-1.5 text-gray-400 hover:text-red-500" @click="onRemove(m)">
          <Icon icon="heroicons:trash" class="w-4 h-4" />
        </button>
      </li>
    </ul>
  </div>
</template>
```

---

# H.7 — Tab 4: สิทธิ์ (Permissions)

**File:** `ui/components/academy/groups/ManageTabPermissions.vue` (NEW)

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

interface Props { group: any; academyId: number | string }
const props = defineProps<Props>()

const api = useApi()
const { listPermissions, updatePermissions } = useAcademyGroups()

// shape: { permission_key: enabled boolean }
const permissions = ref<Record<string, boolean>>({})
const meta = ref<Array<{ key: string; label: string; default: boolean }>>([])
const isLoading = ref(true)
const isSaving = ref(false)

const load = async () => {
  isLoading.value = true
  try {
    // load metadata (labels for each key) + current state
    const [metaRes, permRes]: any = await Promise.all([
      api.call('/api/academy-group-permissions'),
      listPermissions(props.academyId, props.group.id),
    ])
    meta.value = metaRes?.data ?? []
    const current = (permRes?.permissions ?? permRes?.data ?? []) as any[]
    const map: Record<string, boolean> = {}
    for (const m of meta.value) {
      const existing = current.find((p: any) => p.permission_key === m.key)
      map[m.key] = existing ? !!existing.enabled : m.default
    }
    permissions.value = map
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

const save = async () => {
  isSaving.value = true
  try {
    await updatePermissions(props.academyId, props.group.id, permissions.value)
    Swal.fire({ icon: 'success', title: 'บันทึกสิทธิ์แล้ว', timer: 1500, showConfirmButton: false })
  } catch (e: any) {
    Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', text: e?.data?.message || '' })
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="space-y-5">
    <p class="text-sm text-gray-500 dark:text-gray-400">
      เปิดสิทธิ์ที่สมาชิกส่วนงานนี้สามารถทำได้
    </p>

    <div v-if="isLoading" class="py-6 text-center">
      <Icon icon="svg-spinners:ring-resize" class="w-6 h-6 text-vikinger-purple mx-auto" />
    </div>

    <ul v-else class="divide-y divide-gray-100 dark:divide-gray-700">
      <li
        v-for="p in meta"
        :key="p.key"
        class="flex items-center justify-between py-3"
      >
        <div class="min-w-0">
          <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ p.label }}</div>
          <code class="text-[10px] text-gray-400">{{ p.key }}</code>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input v-model="permissions[p.key]" type="checkbox" class="sr-only peer" />
          <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-checked:bg-vikinger-purple peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
        </label>
      </li>
    </ul>

    <div class="flex justify-end pt-3 border-t border-gray-100 dark:border-gray-700">
      <button
        :disabled="isSaving || isLoading"
        class="px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium hover:bg-vikinger-purple/90 disabled:opacity-50 flex items-center gap-2"
        @click="save"
      >
        <Icon v-if="isSaving" icon="svg-spinners:ring-resize" class="w-4 h-4" />
        <Icon v-else icon="heroicons:shield-check" class="w-4 h-4" />
        บันทึกสิทธิ์
      </button>
    </div>
  </div>
</template>
```

> ⚠️ **เช็ค backend PUT payload format**:
> - `updatePermissions` ตอนนี้ส่ง `{ permissions: { can_post: true, ... } }` — ดู `AcademyGroupPermissionController::update` ว่ารับ format ไหน
> - ถ้ารับเป็น array `[{ permission_key, enabled }]` → ปรับ payload transform ใน composable

---

# H.8 — Tab 5: ลบส่วนงาน

**File:** `ui/components/academy/groups/ManageTabDelete.vue` (NEW)

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

interface Props { group: any }
const props = defineProps<Props>()
const emit = defineEmits<{ deleted: [groupId: number]; cancel: [] }>()

const { deleteGroup } = useAcademyGroups()

const confirmText = ref('')
const isDeleting = ref(false)
const canDelete = computed(() => confirmText.value.trim() === props.group.name)

const onDelete = async () => {
  if (!canDelete.value || isDeleting.value) return
  isDeleting.value = true
  try {
    await deleteGroup(props.group.id)
    emit('deleted', props.group.id)
    Swal.fire({ icon: 'success', title: 'ลบส่วนงานแล้ว', timer: 1500, showConfirmButton: false })
  } catch (e: any) {
    Swal.fire({ icon: 'error', title: 'ลบไม่สำเร็จ', text: e?.data?.message || '' })
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="space-y-5 max-w-xl">
    <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50">
      <div class="flex items-start gap-3">
        <Icon icon="heroicons:exclamation-triangle" class="w-6 h-6 text-red-500 flex-shrink-0 mt-0.5" />
        <div>
          <div class="font-bold text-red-700 dark:text-red-300">การลบส่วนงานเป็นการดำเนินการถาวร</div>
          <ul class="text-sm text-red-700 dark:text-red-400 mt-1 list-disc list-inside space-y-0.5">
            <li>ข้อมูลสมาชิกและหัวหน้าของส่วนงานจะถูกลบ</li>
            <li>โพสต์ที่โพสต์ในนามส่วนงานนี้จะยังอยู่ แต่จะแสดงเป็นโพสต์ส่วนตัวแทน</li>
            <li>ลบแล้วจะไม่สามารถกู้คืนได้</li>
          </ul>
        </div>
      </div>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        พิมพ์ชื่อ <b>"{{ group.name }}"</b> เพื่อยืนยัน
      </label>
      <input
        v-model="confirmText"
        type="text"
        class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500/50"
      />
    </div>

    <div class="flex justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
      <button
        type="button"
        class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300"
        @click="emit('cancel')"
      >
        ยกเลิก
      </button>
      <button
        type="button"
        :disabled="!canDelete || isDeleting"
        class="px-4 py-2 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
        @click="onDelete"
      >
        <Icon v-if="isDeleting" icon="svg-spinners:ring-resize" class="w-4 h-4" />
        <Icon v-else icon="heroicons:trash" class="w-4 h-4" />
        ลบส่วนงานถาวร
      </button>
    </div>
  </div>
</template>
```

---

# H.9 — Wire `onManageGroup` ใน [name].vue

**File:** `ui/pages/academies/[name].vue`

## H.9.1 — Add ref + handler updates

หา section ที่เก็บ refs (ราว `const showCreateGroupModal = ref(false)`) เพิ่ม:

```ts
// Group manage modal (Phase H)
const showManageModal = ref(false)
const manageTargetGroup = ref<any | null>(null)
```

อัปเดต `onManageGroup` (เดิมเป็น console.log):
```ts
const onManageGroup = (group: any) => {
  manageTargetGroup.value = group
  showManageModal.value = true
}

const onGroupUpdated = (updated: any) => {
  const idx = groups.value.findIndex((g) => g.id === updated.id)
  if (idx !== -1) groups.value[idx] = { ...groups.value[idx], ...updated }
}

const onGroupDeleted = (groupId: number) => {
  groups.value = groups.value.filter((g) => g.id !== groupId)
}
```

## H.9.2 — Mount modal (ใต้ `<AcademyGroupsGroupCreateModal>`)

```html
<AcademyGroupsGroupManageModal
  v-if="academy && manageTargetGroup"
  v-model:open="showManageModal"
  :group="manageTargetGroup"
  :academy-id="academy.id"
  @updated="onGroupUpdated"
  @deleted="onGroupDeleted"
/>
```

---

# H.10 — Backend-driven type sync (optional แต่แนะนำ)

**Goal:** ตอนนี้ frontend hardcode ใน `ui/constants/academyGroupTypes.ts` — sync กับ backend Phase G constant

**File:** `ui/composables/useAcademyGroupTypes.ts` (NEW)

```ts
import { ACADEMY_GROUP_TYPES, type AcademyGroupTypeMeta } from '~/constants/academyGroupTypes'

const cache = ref<AcademyGroupTypeMeta[] | null>(null)

export const useAcademyGroupTypes = () => {
  const api = useApi()

  const types = computed(() => cache.value ?? ACADEMY_GROUP_TYPES)

  const fetch = async () => {
    if (cache.value) return cache.value
    try {
      const res: any = await api.call('/api/academy-group-types')
      if (res?.success && Array.isArray(res.data)) {
        // Normalize backend → frontend shape
        cache.value = res.data.map((t: any) => ({
          key: t.key,
          label: t.label,
          labelEn: t.label_en,
          icon: t.icon,
          color: t.color,
          order: t.order,
        }))
      }
    } catch {
      // fallback to static constants
    }
    return types.value
  }

  return { types, fetch }
}
```

**ใช้ใน:** `GroupCreateModal`, `ManageTabInfo` → call `fetch()` ใน `onMounted` แทน import ตรง

> 💡 **ระวัง:** ถ้า fetch ไม่ทันก่อน render → fallback ที่ใช้ `ACADEMY_GROUP_TYPES` (static) แสดงก่อนแล้วค่อย swap

---

# 📋 Phase H — Files Summary

## ✨ New files (11)

### Backend (1)
```
api/nuxnanravel/app/Http/Controllers/Api/Learn/Academy/AcademyGroupAdminController.php  (rewrite stubs)
```

### Frontend (10)
```
ui/composables/useAcademyGroups.ts
ui/composables/useAcademyGroupTypes.ts (H.10)

ui/components/academy/groups/GroupManageModal.vue        (shell + tabs)
ui/components/academy/groups/ManageTabInfo.vue
ui/components/academy/groups/ManageTabAdmins.vue
ui/components/academy/groups/ManageTabMembers.vue
ui/components/academy/groups/ManageTabPermissions.vue
ui/components/academy/groups/ManageTabDelete.vue
ui/components/academy/groups/MemberAutocompleteInput.vue
```

## 🔧 Modified files (2)
```
api/nuxnanravel/routes/learn/academy.php          (register admin routes — H.0.2)
ui/pages/academies/[name].vue                     (wire onManageGroup — H.9)
```

---

# 🛣️ Commit plan (suggested — 5 commits)

```
1. feat(api): register academy group admin routes + rewrite controller
   - H.0: AcademyGroupAdminController + routes

2. feat(ui): add useAcademyGroups composable
   - H.1

3. feat(ui): add GroupManageModal shell + Info tab
   - H.2 + H.3

4. feat(ui): add member autocomplete + Admin/Member tabs
   - H.4 + H.5 + H.6

5. feat(ui): add Permissions tab + Delete tab + wire onManageGroup
   - H.7 + H.8 + H.9 (+ optional H.10)
```

---

# ✅ Phase H — Test Checklist

## Pre-flight
- [ ] รัน `php artisan route:list | grep "groups.*admin"` เห็น 4 routes
- [ ] `php artisan tinker` ทดสอบ `AcademyGroupAdmin::create([...])` → save ได้
- [ ] เปิด `/academies/{name}` → tab "ส่วนงาน" → กดการ์ดส่วนงานใดส่วนหนึ่ง

## Tab Info
- [ ] เปิด tab → form pre-fill ด้วยข้อมูลปัจจุบัน
- [ ] เปลี่ยน name → save → toast success + group ใน list อัปเดต
- [ ] เปลี่ยน type → save → badge สีในการ์ดเปลี่ยน
- [ ] dirty state — ปุ่ม save disabled เมื่อไม่มีการแก้

## Tab Admins
- [ ] Autocomplete แสดงผลเมื่อพิมพ์ ≥2 ตัวอักษร
- [ ] เพิ่ม admin → ดู in DB `academy_group_admins`
- [ ] เปลี่ยน role → DB update
- [ ] ลบ admin → DB delete
- [ ] เพิ่ม user ที่ไม่ใช่ academy member → 422 toast

## Tab Members
- [ ] เพิ่ม member → DB `academy_group_members`
- [ ] filter local search ทำงาน
- [ ] role change → DB update

## Tab Permissions
- [ ] toggle 6 permission → save → ดู `academy_group_permissions` มี enabled ตรง
- [ ] เปิด `can_post = true` → ทดสอบ post-as-group ผ่าน (Phase G validation จะ allow)

## Tab Delete
- [ ] ปุ่มลบ disabled จนกว่าจะพิมพ์ชื่อตรง
- [ ] ลบสำเร็จ → modal ปิด + card หายจาก list
- [ ] โพสต์ที่มี `posted_as_group_id` → ดู `posted_as_group_id` เป็น NULL ไม่ใช่ลบทั้งโพสต์

## Regression
- [ ] `GroupCreateModal` (Quick Win) ยังเปิด/สร้างได้
- [ ] feed tab โหลด activities ได้
- [ ] tab อื่นๆ (members/classrooms/events) ยังใช้ได้

---

# ⚠️ Pitfalls & Notes

## 1. `useApi` signature
- ผมเขียน `api.call(url, { method, body, params })` ในแผน — ดู signature จริงใน `ui/composables/useApi.ts` ก่อนใช้
- ถ้าใช้ `api.post(url, body)` / `api.patch(url, body)` style → ปรับ composable ตาม

## 2. Auto-import path
- `ui/components/academy/groups/GroupManageModal.vue` → `<AcademyGroupsGroupManageModal>`
- `ManageTabInfo.vue` → `<AcademyGroupsManageTabInfo>`
- `MemberAutocompleteInput.vue` → `<AcademyGroupsMemberAutocompleteInput>`
- ✅ pattern เดียวกับที่ใช้ใน Quick Wins

## 3. Permission save format
- Phase G สร้าง record ทุกอันเป็น row ใน `academy_group_permissions`
- Existing `AcademyGroupPermissionController::update` อาจรับ payload เป็น `{permissions: {can_post: bool, ...}}` หรือ `[{key, enabled}, ...]`
- **เช็คก่อนเขียน composable** ดูใน `update()` method

## 4. Search endpoint response shape
- `/members/search` อาจคืน `{data: []}` หรือ `[]` ตรงๆ
- `MemberAutocompleteInput` รองรับทั้ง `res?.data ?? res?.members ?? []`

## 5. Body ใน DELETE request
- บาง HTTP client (เช่น old `$fetch`) ไม่ส่ง body ใน DELETE
- ถ้ามีปัญหา → ส่ง `user_id` ผ่าน query string แทน: `?user_id=...`
- ทดสอบ Network tab ดู payload จริง

## 6. Modal sizing บนมือถือ
- modal `max-w-3xl` กว้างไป mobile
- เพิ่ม `w-full` + responsive padding `p-2 sm:p-4`
- tab scroll-x แล้วบน narrow screen ทำงาน

## 7. State sync ตอน delete admin/member
- ปัจจุบันใช้ `.filter()` กับ `id` — เช็คว่า `id` เป็น primary key ของ table `academy_group_admins/members` ไม่ใช่ user_id

## 8. Permission default behavior conflict
- ใน `ManageTabPermissions` ถ้า backend คืน `permissions: []` ว่าง → fallback ใช้ `meta.default`
- ตรงกับ Phase G strict policy: backend จะมี records ครบเสมอ (auto-seed) — แต่กลุ่มเก่าก่อน Phase G ต้องรัน backfill seeder ก่อน

---

# 🎯 ลำดับงานแนะนำ

1. **H.0 (backend)** → register routes + verify `route:list`
2. **H.1 composable** → test ใน console: `useAcademyGroups().listAdmins(1)` ใน devtools
3. **H.2 modal shell** → mount empty modal → ดูว่า tab navigation ทำงาน
4. **H.3 + H.9** → Info tab + wire — เห็นผลปลายทางก่อนทำ tab อื่น
5. **H.4** autocomplete (shared) → test ค้นหา
6. **H.5 + H.6** Admin + Member tabs (parallel เหมือนๆ กัน)
7. **H.7** Permissions
8. **H.8** Delete
9. **H.10** type sync (optional)

หลัง H เสร็จ → **Phase I** (Group profile page) จะ reuse `useAcademyGroups` ได้ทันที + GroupCard click navigate ไป page ที่สร้างใน I

ติดตรงไหนตอนทำมาถามได้เลยครับ 🙌

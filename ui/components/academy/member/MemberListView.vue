<script setup lang="ts">
/**
 * Academy Member List View Component
 * แสดงรายการสมาชิกโรงเรียน พร้อมมุมมอง Card และ Table
 */
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

interface Member {
  id: number
  member_name?: string
  member_code?: string
  member_avatar?: string
  status: number
  enrollment_date?: string
  created_at?: string
  last_activity_at?: string
  academy_role?: {
    id: number
    name: string
    display_name_th: string
    color: string
    icon: string
  }
  role_display_name?: string
  user?: {
    id: number
    name: string
    email: string
    avatar?: string
    profile_photo_url?: string
  }
  tags?: {
    id: number
    name: string
    color: string
  }[]
}

interface Props {
  members: Member[]
  viewMode?: 'card' | 'table'
  isAdmin?: boolean
  showCheckbox?: boolean
  selectedIds?: number[]
}

const props = withDefaults(defineProps<Props>(), {
  viewMode: 'card',
  isAdmin: false,
  showCheckbox: false,
  selectedIds: () => []
})

const emit = defineEmits<{
  'accept-member': [member: Member]
  'reject-member': [member: Member]
  'remove-member': [member: Member]
  'edit-role': [member: Member]
  'manage-member': [member: Member]
  'update:selectedIds': [ids: number[]]
}>()

// Helper functions
const getMemberName = (member: Member) => 
  member.member_name || member.user?.name || 'ไม่ระบุ'

const getMemberAvatar = (member: Member) =>
  member.member_avatar || member.user?.avatar || member.user?.profile_photo_url || '/images/default-avatar.png'

const getMemberEmail = (member: Member) =>
  member.user?.email || '-'

const getMemberCode = (member: Member) =>
  member.member_code || '-'

const formatDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('th-TH', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

const formatLastActivity = (dateStr?: string) => {
  if (!dateStr) return 'ไม่เคยเข้าใช้งาน'
  const date = new Date(dateStr)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  
  if (days === 0) return 'วันนี้'
  if (days === 1) return 'เมื่อวาน'
  if (days < 7) return `${days} วันที่แล้ว`
  if (days < 30) return `${Math.floor(days / 7)} สัปดาห์ที่แล้ว`
  return formatDate(dateStr)
}

const getStatusBadge = (status: number) => {
  const statuses: Record<number, { label: string; color: string; bgColor: string; icon: string }> = {
    1: { 
      label: 'รอการอนุมัติ', 
      color: 'text-yellow-700 dark:text-yellow-400', 
      bgColor: 'bg-yellow-100 dark:bg-yellow-900/40',
      icon: 'fluent:clock-24-regular'
    },
    2: { 
      label: 'สมาชิก', 
      color: 'text-green-700 dark:text-green-400', 
      bgColor: 'bg-green-100 dark:bg-green-900/40',
      icon: 'fluent:checkmark-circle-24-regular'
    },
    3: { 
      label: 'ถูกปฏิเสธ', 
      color: 'text-red-700 dark:text-red-400', 
      bgColor: 'bg-red-100 dark:bg-red-900/40',
      icon: 'fluent:dismiss-circle-24-regular'
    },
    4: { 
      label: 'ได้รับเชิญ', 
      color: 'text-blue-700 dark:text-blue-400', 
      bgColor: 'bg-blue-100 dark:bg-blue-900/40',
      icon: 'fluent:mail-24-regular'
    },
    5: { 
      label: 'ถูกระงับ', 
      color: 'text-orange-700 dark:text-orange-400', 
      bgColor: 'bg-orange-100 dark:bg-orange-900/40',
      icon: 'fluent:person-prohibited-24-regular'
    },
  }
  return statuses[status] || { label: 'ไม่ทราบ', color: 'text-gray-700', bgColor: 'bg-gray-100', icon: 'fluent:question-24-regular' }
}

const getRoleBadge = (member: Member) => {
  if (member.academy_role) {
    return {
      label: member.academy_role.display_name_th,
      color: member.academy_role.color || 'gray',
      icon: member.academy_role.icon || 'fluent:person-24-regular'
    }
  }
  return {
    label: member.role_display_name || 'สมาชิก',
    color: 'gray',
    icon: 'fluent:person-24-regular'
  }
}

const getRoleColorClass = (color: string) => {
  const colors: Record<string, string> = {
    red: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
    orange: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400',
    amber: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
    yellow: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
    lime: 'bg-lime-100 text-lime-700 dark:bg-lime-900/40 dark:text-lime-400',
    green: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
    emerald: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
    teal: 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-400',
    cyan: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-400',
    sky: 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-400',
    blue: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
    indigo: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-400',
    violet: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-400',
    purple: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400',
    fuchsia: 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/40 dark:text-fuchsia-400',
    pink: 'bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-400',
    rose: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400',
    gray: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
  }
  return colors[color] || colors.gray
}

// Selection handling
const isSelected = (id: number) => props.selectedIds.includes(id)

const toggleSelection = (id: number) => {
  const newIds = isSelected(id) 
    ? props.selectedIds.filter(i => i !== id)
    : [...props.selectedIds, id]
  emit('update:selectedIds', newIds)
}

const toggleSelectAll = () => {
  if (props.selectedIds.length === props.members.length) {
    emit('update:selectedIds', [])
  } else {
    emit('update:selectedIds', props.members.map(m => m.id))
  }
}

const isAllSelected = computed(() => 
  props.members.length > 0 && props.selectedIds.length === props.members.length
)
</script>

<template>
  <div>
    <!-- Card View -->
    <div v-if="viewMode === 'card'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="member in members"
        :key="member.id"
        class="group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-700 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden"
      >
        <!-- Checkbox -->
        <div v-if="showCheckbox" class="absolute top-3 left-3 z-10">
          <input
            type="checkbox"
            :checked="isSelected(member.id)"
            @change="toggleSelection(member.id)"
            class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
          />
        </div>

        <!-- Status Indicator Bar -->
        <div 
          class="h-1.5 w-full"
          :class="getStatusBadge(member.status).bgColor"
        />

        <!-- Card Content -->
        <div class="p-5">
          <!-- Header: Avatar + Info -->
          <div class="flex items-start gap-4">
            <!-- Avatar -->
            <div class="relative flex-shrink-0">
              <img
                :src="getMemberAvatar(member)"
                :alt="getMemberName(member)"
                class="w-16 h-16 rounded-xl object-cover ring-2 ring-gray-100 dark:ring-gray-700"
              />
              <!-- Status Icon -->
              <div 
                class="absolute -bottom-1 -right-1 w-6 h-6 flex items-center justify-center rounded-full shadow-lg"
                :class="getStatusBadge(member.status).bgColor"
              >
                <Icon :icon="getStatusBadge(member.status).icon" class="w-3.5 h-3.5" :class="getStatusBadge(member.status).color" />
              </div>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                  <h3 class="font-bold text-gray-900 dark:text-white truncate text-lg leading-tight">
                    {{ getMemberName(member) }}
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400 truncate mt-0.5">
                    {{ getMemberCode(member) }}
                  </p>
                </div>
              </div>

              <!-- Email -->
              <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-1">
                {{ getMemberEmail(member) }}
              </p>
            </div>
          </div>

          <!-- Role & Status -->
          <div class="flex items-center gap-2 mt-4 flex-wrap">
            <!-- Role Badge -->
            <button 
              @click="emit('edit-role', member)"
              :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium transition-all hover:opacity-80', getRoleColorClass(getRoleBadge(member).color)]"
            >
              <Icon :icon="getRoleBadge(member).icon" class="w-3.5 h-3.5" />
              {{ getRoleBadge(member).label }}
            </button>

            <!-- Status Badge -->
            <span :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium', getStatusBadge(member.status).bgColor, getStatusBadge(member.status).color]">
              {{ getStatusBadge(member.status).label }}
            </span>
            
            <!-- Tags -->
            <span
              v-for="tag in (member.tags || []).slice(0, 3)"
              :key="tag.id"
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium border"
              :style="{ backgroundColor: tag.color + '20', color: tag.color, borderColor: tag.color }"
            >
              <Icon icon="mdi:tag" class="w-2.5 h-2.5" />
              {{ tag.name }}
            </span>
            <span 
              v-if="(member.tags || []).length > 3"
              class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400"
            >
              +{{ (member.tags || []).length - 3 }}
            </span>
          </div>

          <!-- Meta Info -->
          <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-1.5 text-xs text-gray-400">
              <Icon icon="fluent:calendar-24-regular" class="w-3.5 h-3.5" />
              <span>{{ formatDate(member.enrollment_date || member.created_at) }}</span>
            </div>
            <div class="flex items-center gap-1.5 text-xs text-gray-400">
              <Icon icon="fluent:clock-24-regular" class="w-3.5 h-3.5" />
              <span>{{ formatLastActivity(member.last_activity_at) }}</span>
            </div>
          </div>

          <!-- Pending Actions -->
          <div v-if="member.status === 1 && isAdmin" class="flex items-center gap-2 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
            <button
              @click="emit('accept-member', member)"
              class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-medium transition-colors"
            >
              <Icon icon="fluent:checkmark-24-filled" class="w-4 h-4" />
              อนุมัติ
            </button>
            <button
              @click="emit('reject-member', member)"
              class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-medium transition-colors"
            >
              <Icon icon="fluent:dismiss-24-filled" class="w-4 h-4" />
              ปฏิเสธ
            </button>
          </div>
        </div>

        <!-- Actions on Hover -->
        <div v-if="isAdmin && member.status !== 1" class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
          <div class="flex items-center gap-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 p-1">
            <button
              @click="emit('edit-role', member)"
              class="p-1.5 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30 rounded-md transition-colors"
              title="กำหนดบทบาท"
            >
              <Icon icon="fluent:shield-person-24-regular" class="w-4 h-4" />
            </button>
            <button
              @click="emit('manage-member', member)"
              class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-md transition-colors"
              title="จัดการสมาชิก"
            >
              <Icon icon="fluent:settings-24-regular" class="w-4 h-4" />
            </button>
            <button
              @click="emit('remove-member', member)"
              class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-md transition-colors"
              title="ลบสมาชิก"
            >
              <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Table View -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
              <th v-if="showCheckbox" class="w-12 px-4 py-3">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                  class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
              </th>
              <th class="px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                สมาชิก
              </th>
              <th class="px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                รหัส
              </th>
              <th class="px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                บทบาท
              </th>
              <th class="px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                สถานะ
              </th>
              <th class="px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                วันที่เข้าร่วม
              </th>
              <th class="px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                ใช้งานล่าสุด
              </th>
              <th v-if="isAdmin" class="px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">
                การดำเนินการ
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr 
              v-for="member in members" 
              :key="member.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
              <!-- Checkbox -->
              <td v-if="showCheckbox" class="px-4 py-3">
                <input
                  type="checkbox"
                  :checked="isSelected(member.id)"
                  @change="toggleSelection(member.id)"
                  class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
              </td>

              <!-- Member -->
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <img
                    :src="getMemberAvatar(member)"
                    :alt="getMemberName(member)"
                    class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700"
                  />
                  <div class="min-w-0">
                    <p class="font-semibold text-gray-900 dark:text-white truncate max-w-[180px]">
                      {{ getMemberName(member) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[180px]">
                      {{ getMemberEmail(member) }}
                    </p>
                  </div>
                </div>
              </td>

              <!-- Member Code -->
              <td class="px-6 py-4">
                <span class="text-sm text-gray-600 dark:text-gray-400 font-mono">
                  {{ getMemberCode(member) }}
                </span>
              </td>

              <!-- Role -->
              <td class="px-6 py-4">
                <button 
                  @click="emit('edit-role', member)"
                  :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium transition-all hover:opacity-80', getRoleColorClass(getRoleBadge(member).color)]"
                >
                  <Icon :icon="getRoleBadge(member).icon" class="w-3.5 h-3.5" />
                  {{ getRoleBadge(member).label }}
                </button>
              </td>

              <!-- Status -->
              <td class="px-6 py-4">
                <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium', getStatusBadge(member.status).bgColor, getStatusBadge(member.status).color]">
                  <Icon :icon="getStatusBadge(member.status).icon" class="w-3.5 h-3.5" />
                  {{ getStatusBadge(member.status).label }}
                </span>
              </td>

              <!-- Join Date -->
              <td class="px-6 py-4">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                  {{ formatDate(member.enrollment_date || member.created_at) }}
                </span>
              </td>

              <!-- Last Activity -->
              <td class="px-6 py-4">
                <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                  {{ formatLastActivity(member.last_activity_at) }}
                </span>
              </td>

              <!-- Actions -->
              <td v-if="isAdmin" class="px-6 py-4">
                <div class="flex items-center justify-end gap-1">
                  <!-- Accept/Reject for pending -->
                  <template v-if="member.status === 1">
                    <button
                      @click="emit('accept-member', member)"
                      class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition-colors"
                      title="อนุมัติ"
                    >
                      <Icon icon="fluent:checkmark-24-filled" class="w-5 h-5" />
                    </button>
                    <button
                      @click="emit('reject-member', member)"
                      class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                      title="ปฏิเสธ"
                    >
                      <Icon icon="fluent:dismiss-24-filled" class="w-5 h-5" />
                    </button>
                  </template>

                  <!-- Regular actions -->
                  <button
                    @click="emit('edit-role', member)"
                    class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30 rounded-lg transition-colors"
                    title="กำหนดบทบาท"
                  >
                    <Icon icon="fluent:shield-person-24-regular" class="w-4 h-4" />
                  </button>
                  <button
                    @click="emit('manage-member', member)"
                    class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                    title="จัดการสมาชิก"
                  >
                    <Icon icon="fluent:settings-24-regular" class="w-4 h-4" />
                  </button>
                  <button
                    @click="emit('remove-member', member)"
                    class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                    title="ลบสมาชิก"
                  >
                    <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="members.length === 0" class="py-16 text-center">
        <Icon icon="fluent:people-24-regular" class="w-12 h-12 mx-auto text-gray-400 mb-4" />
        <p class="text-gray-500 dark:text-gray-400">ไม่พบสมาชิก</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * MemberListView - Course Member List Component
 * แสดงรายการสมาชิกรายวิชา พร้อมมุมมอง Card และ Table
 */
import { computed, ref, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { Icon } from '@iconify/vue'

interface Member {
  id: number
  member_name?: string
  member_code?: string
  order_number?: number | string
  achieved_score?: number
  role?: number
  status?: number
  avatar?: string
  enrollment_date?: string
  last_activity_at?: string
  lessons_completed?: number
  assignments_submitted?: number
  attendance_rate?: number
  group?: {
    id: number
    name: string
  }
  group_id?: number
  user?: {
    id: number
    name: string
    email: string
    avatar?: string
    profile_photo_url?: string
  }
  student?: {
    id: number
    name: string
  }
}

interface Group {
  id: number
  name: string
}

interface Props {
  members: Member[]
  viewMode?: 'card' | 'table'
  courseTotalScore?: number
  isCourseAdmin?: boolean
  showCheckbox?: boolean
  selectedIds?: number[]
  availableGroups?: Group[]
  assigningMemberId?: number | null
}

const props = withDefaults(defineProps<Props>(), {
  viewMode: 'card',
  courseTotalScore: 100,
  isCourseAdmin: false,
  showCheckbox: false,
  selectedIds: () => [],
  availableGroups: () => [],
  assigningMemberId: null
})

const emit = defineEmits<{
  'request-unmember': [{ memberId: number; memberName: string }]
  'view-member': [member: Member]
  'edit-member': [member: Member]
  'update:selectedIds': [ids: number[]]
  'assign-group': [{ memberId: number; groupId: number }]
}>()

const showGroupAssign = computed(() => props.availableGroups.length > 0)

// Helper functions
const getMemberName = (member: Member) => 
  member.member_name || member.user?.name || member.student?.name || 'ไม่ระบุ'

const getMemberAvatar = (member: Member) =>
  member.avatar || member.user?.avatar || member.user?.profile_photo_url || '/images/default-avatar.png'

const getMemberEmail = (member: Member) =>
  member.user?.email || '-'

const getMemberCode = (member: Member) =>
  member.member_code || '-'

const getMemberGroup = (member: Member) =>
  member.group?.name || '-'

const getMemberOrderNumber = (member: Member) =>
  member.order_number || '-'

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

const getRoleBadge = (role?: number) => {
  const roles: Record<number, { label: string; color: string; icon: string }> = {
    1: { label: 'นักเรียน', color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', icon: 'fluent:hat-graduation-24-regular' },
    2: { label: 'ผู้ช่วยสอน', color: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300', icon: 'fluent:person-support-24-regular' },
    4: { label: 'ผู้สอน', color: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', icon: 'fluent:person-board-24-regular' },
  }
  return roles[role || 1] || roles[1]
}

const getStatusBadge = (status?: number) => {
  const statuses: Record<number, { label: string; color: string }> = {
    1: { label: 'สมาชิก', color: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' },
    2: { label: 'รออนุมัติ', color: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300' },
    3: { label: 'ถูกระงับ', color: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' },
  }
  return statuses[status || 1] || statuses[1]
}

// Progress calculation for each member
const getMemberProgress = (member: Member) => {
  const score = (member.achieved_score || 0) + (member.bonus_points || 0)
  const total = props.courseTotalScore || 100
  const percentage = Math.min(Math.round((score / total) * 100), 100)
  
  let status = 'ยังไม่เริ่มเรียน'
  let colorClass = 'text-gray-500'
  let bgClass = 'bg-gray-200 dark:bg-gray-700'
  let progressBg = 'bg-gray-400'
  
  if (percentage >= 80) {
    status = 'ยอดเยี่ยม'
    colorClass = 'text-emerald-600 dark:text-emerald-400'
    progressBg = 'bg-gradient-to-r from-emerald-400 to-emerald-600'
  } else if (percentage >= 60) {
    status = 'ดีมาก'
    colorClass = 'text-blue-600 dark:text-blue-400'
    progressBg = 'bg-gradient-to-r from-blue-400 to-blue-600'
  } else if (percentage >= 40) {
    status = 'กำลังพัฒนา'
    colorClass = 'text-amber-600 dark:text-amber-400'
    progressBg = 'bg-gradient-to-r from-amber-400 to-amber-600'
  } else if (percentage > 0) {
    status = 'เพิ่งเริ่มต้น'
    colorClass = 'text-orange-600 dark:text-orange-400'
    progressBg = 'bg-gradient-to-r from-orange-400 to-orange-600'
  }
  
  return { percentage, status, colorClass, bgClass, progressBg, score, total }
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

// Top scrollbar sync for table view
const topScrollRef = ref<HTMLElement | null>(null)
const tableScrollRef = ref<HTMLElement | null>(null)
const tableRef = ref<HTMLElement | null>(null)
const tableScrollWidth = ref(0)
let isSyncingScroll = false

const updateTableWidth = () => {
  if (tableRef.value) {
    tableScrollWidth.value = tableRef.value.scrollWidth
  }
}

const onTopScroll = () => {
  if (isSyncingScroll) return
  isSyncingScroll = true
  if (tableScrollRef.value && topScrollRef.value) {
    tableScrollRef.value.scrollLeft = topScrollRef.value.scrollLeft
  }
  nextTick(() => { isSyncingScroll = false })
}

const onTableScroll = () => {
  if (isSyncingScroll) return
  isSyncingScroll = true
  if (topScrollRef.value && tableScrollRef.value) {
    topScrollRef.value.scrollLeft = tableScrollRef.value.scrollLeft
  }
  nextTick(() => { isSyncingScroll = false })
}

onMounted(() => {
  updateTableWidth()
  window.addEventListener('resize', updateTableWidth)
})

onUnmounted(() => {
  window.removeEventListener('resize', updateTableWidth)
})

watch(() => props.members, () => {
  nextTick(updateTableWidth)
}, { deep: true })

watch(() => props.viewMode, (val) => {
  if (val === 'table') {
    nextTick(updateTableWidth)
  }
})
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

        <!-- Progress Bar at Top -->
        <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700">
          <div 
            class="h-full transition-all duration-500"
            :class="getMemberProgress(member).progressBg"
            :style="{ width: `${getMemberProgress(member).percentage}%` }"
          />
        </div>

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
              <!-- Order Number Badge -->
              <div 
                v-if="getMemberOrderNumber(member) !== '-'"
                class="absolute -bottom-1 -right-1 w-6 h-6 flex items-center justify-center rounded-full bg-primary-500 text-white text-xs font-bold shadow-lg"
              >
                {{ getMemberOrderNumber(member) }}
              </div>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div>
                <h3 class="font-bold text-gray-900 dark:text-white text-base leading-snug break-words line-clamp-2">
                  {{ getMemberName(member) }}
                </h3>
                <div class="flex items-center gap-2 mt-1 flex-wrap">
                  <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[140px]">
                    {{ getMemberCode(member) }}
                  </p>
                  <!-- Role Badge -->
                  <span :class="['inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium whitespace-nowrap', getRoleBadge(member.role).color]">
                    <Icon :icon="getRoleBadge(member.role).icon" class="w-3 h-3" />
                    {{ getRoleBadge(member.role).label }}
                  </span>
                </div>
              </div>

              <!-- Group -->
              <div class="flex items-center gap-1.5 mt-2 text-sm text-gray-600 dark:text-gray-400">
                <Icon icon="fluent:people-team-24-regular" class="w-4 h-4" />
                <template v-if="!member.group && showGroupAssign">
                  <select
                    :disabled="assigningMemberId === member.id"
                    class="text-xs border border-gray-300 dark:border-gray-600 rounded-lg px-2 py-1 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:ring-1 focus:ring-blue-500 outline-none disabled:opacity-50"
                    @change="(e) => { const val = e.target.value; if (val) { emit('assign-group', { memberId: member.id, groupId: Number(val) }); e.target.value = '' } }"
                  >
                    <option value="">เลือกกลุ่ม...</option>
                    <option v-for="g in availableGroups" :key="g.id" :value="g.id">{{ g.name }}</option>
                  </select>
                  <Icon v-if="assigningMemberId === member.id" icon="svg-spinners:ring-resize" class="w-4 h-4 text-blue-500" />
                </template>
                <span v-else>{{ getMemberGroup(member) }}</span>
              </div>
            </div>
          </div>

          <!-- Stats Grid -->
          <div class="grid grid-cols-3 gap-3 mt-5 pt-4 border-t border-gray-100 dark:border-gray-700">
            <!-- Score -->
            <div class="text-center">
              <p class="text-2xl font-bold" :class="getMemberProgress(member).colorClass">
                {{ getMemberProgress(member).percentage }}%
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">คะแนน</p>
            </div>
            <!-- Lessons -->
            <div class="text-center border-x border-gray-100 dark:border-gray-700">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ member.lessons_completed || 0 }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">บทเรียน</p>
            </div>
            <!-- Attendance -->
            <div class="text-center">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ member.attendance_rate || 0 }}%
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">เข้าเรียน</p>
            </div>
          </div>

          <!-- Progress Status -->
          <div class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-2">
              <div class="w-2.5 h-2.5 rounded-full" :class="getMemberProgress(member).progressBg" />
              <span class="text-sm font-medium" :class="getMemberProgress(member).colorClass">
                {{ getMemberProgress(member).status }}
              </span>
            </div>
            <span class="text-xs text-gray-400">
              {{ getMemberProgress(member).score }}/{{ getMemberProgress(member).total }} คะแนน
            </span>
          </div>

          <!-- Last Activity -->
          <div class="flex items-center gap-1.5 mt-3 text-xs text-gray-400">
            <Icon icon="fluent:clock-24-regular" class="w-3.5 h-3.5" />
            <span>ใช้งานล่าสุด: {{ formatLastActivity(member.last_activity_at) }}</span>
          </div>
        </div>

        <!-- Actions on Hover -->
        <div v-if="isCourseAdmin" class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
          <div class="flex items-center gap-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 p-1">
            <button
              @click="emit('view-member', member)"
              class="p-1.5 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30 rounded-md transition-colors"
              title="ดูรายละเอียด"
            >
              <Icon icon="fluent:eye-24-regular" class="w-4 h-4" />
            </button>
            <button
              @click="emit('edit-member', member)"
              class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-md transition-colors"
              title="แก้ไข"
            >
              <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
            </button>
            <button
              @click="emit('request-unmember', { memberId: member.id, memberName: getMemberName(member) })"
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
      <!-- Top Scrollbar -->
      <div ref="topScrollRef" class="overflow-x-auto overflow-y-hidden scrollbar-thin" style="height: 12px;" @scroll="onTopScroll">
        <div :style="{ width: tableScrollWidth + 'px', height: '1px' }"></div>
      </div>
      <div ref="tableScrollRef" class="overflow-x-auto" @scroll="onTableScroll">
        <table ref="tableRef" class="w-full text-left min-w-[900px]">
          <thead class="sticky top-0 z-10">
            <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
              <th v-if="showCheckbox" class="w-12 px-4 py-3">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  @change="toggleSelectAll"
                  class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
              </th>
              <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap w-16">
                เลขที่
              </th>
              <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[200px]">
                สมาชิก
              </th>
              <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[100px]">
                รหัส
              </th>
              <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[90px]">
                กลุ่ม
              </th>
              <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[90px]">
                บทบาท
              </th>
              <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[160px]">
                ความคืบหน้า
              </th>
              <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center whitespace-nowrap min-w-[80px]">
                คะแนน
              </th>
              <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[70px]">
                บทเรียน
              </th>
              <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[70px]">
                เข้าเรียน
              </th>
              <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap min-w-[110px]">
                ใช้งานล่าสุด
              </th>
              <th v-if="isCourseAdmin" class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right whitespace-nowrap min-w-[120px]">
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

              <!-- Order Number -->
              <td class="px-4 py-3">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 text-sm font-bold text-gray-700 dark:text-gray-300">
                  {{ getMemberOrderNumber(member) }}
                </span>
              </td>

              <!-- Member -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <img
                    :src="getMemberAvatar(member)"
                    :alt="getMemberName(member)"
                    class="w-10 h-10 rounded-full object-cover ring-2 ring-gray-100 dark:ring-gray-700 flex-shrink-0"
                  />
                  <div class="min-w-0">
                    <p class="font-semibold text-gray-900 dark:text-white text-sm leading-snug break-words line-clamp-2 max-w-[200px]">
                      {{ getMemberName(member) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[200px]">
                      {{ getMemberEmail(member) }}
                    </p>
                  </div>
                </div>
              </td>

              <!-- Member Code -->
              <td class="px-4 py-3">
                <span class="text-sm text-gray-600 dark:text-gray-400 font-mono">
                  {{ getMemberCode(member) }}
                </span>
              </td>

              <!-- Group -->
              <td class="px-4 py-3">
                <template v-if="!member.group && showGroupAssign">
                  <div class="flex items-center gap-2">
                    <select
                      :disabled="assigningMemberId === member.id"
                      class="text-xs border border-gray-300 dark:border-gray-600 rounded-lg px-2.5 py-1.5 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:ring-1 focus:ring-blue-500 outline-none disabled:opacity-50"
                      @change="(e) => { const val = e.target.value; if (val) { emit('assign-group', { memberId: member.id, groupId: Number(val) }); e.target.value = '' } }"
                    >
                      <option value="">เลือกกลุ่ม...</option>
                      <option v-for="g in availableGroups" :key="g.id" :value="g.id">{{ g.name }}</option>
                    </select>
                    <Icon v-if="assigningMemberId === member.id" icon="svg-spinners:ring-resize" class="w-4 h-4 text-blue-500" />
                  </div>
                </template>
                <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-sm text-gray-700 dark:text-gray-300">
                  <Icon icon="fluent:people-team-24-regular" class="w-3.5 h-3.5" />
                  {{ getMemberGroup(member) }}
                </span>
              </td>

              <!-- Role -->
              <td class="px-4 py-3">
                <span :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium', getRoleBadge(member.role).color]">
                  <Icon :icon="getRoleBadge(member.role).icon" class="w-3.5 h-3.5" />
                  {{ getRoleBadge(member.role).label }}
                </span>
              </td>

              <!-- Progress -->
              <td class="px-4 py-3 min-w-[140px]">
                <div class="flex items-center gap-2">
                  <div class="flex-1 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div 
                      class="h-full transition-all duration-500"
                      :class="getMemberProgress(member).progressBg"
                      :style="{ width: `${getMemberProgress(member).percentage}%` }"
                    />
                  </div>
                  <span class="text-sm font-semibold w-10 text-right" :class="getMemberProgress(member).colorClass">
                    {{ getMemberProgress(member).percentage }}%
                  </span>
                </div>
              </td>

              <!-- Score -->
              <td class="px-4 py-3 text-center">
                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                  {{ getMemberProgress(member).score }}
                </span>
                <span class="text-xs text-gray-400">/{{ getMemberProgress(member).total }}</span>
              </td>

              <!-- Lessons -->
              <td class="px-4 py-3">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  {{ member.lessons_completed || 0 }} บท
                </span>
              </td>

              <!-- Attendance -->
              <td class="px-4 py-3">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                  {{ member.attendance_rate || 0 }}%
                </span>
              </td>

              <!-- Last Activity -->
              <td class="px-4 py-3">
                <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                  {{ formatLastActivity(member.last_activity_at) }}
                </span>
              </td>

              <!-- Actions -->
              <td v-if="isCourseAdmin" class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <button
                    @click="emit('view-member', member)"
                    class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30 rounded-lg transition-colors"
                    title="ดูรายละเอียด"
                  >
                    <Icon icon="fluent:eye-24-regular" class="w-4 h-4" />
                  </button>
                  <button
                    @click="emit('edit-member', member)"
                    class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                    title="แก้ไข"
                  >
                    <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
                  </button>
                  <button
                    @click="emit('request-unmember', { memberId: member.id, memberName: getMemberName(member) })"
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

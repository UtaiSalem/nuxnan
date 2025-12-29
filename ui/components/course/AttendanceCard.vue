<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  attendance: any
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const emit = defineEmits<{
  'edit': [attendance: any]
  'delete': [attendanceId: number]
  'check-in': [attendance: any]
  'view-details': [attendance: any]
}>()

// Format date
const formatDate = (date: string) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('th-TH', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Format time
const formatTime = (time: string) => {
  if (!time) return ''
  return time.substring(0, 5)
}

// Get status info
const statusInfo = computed(() => {
  const now = new Date()
  const startDate = new Date(props.attendance.start_date || props.attendance.date)
  const endDate = props.attendance.end_date ? new Date(props.attendance.end_date) : null
  
  if (props.attendance.is_closed) {
    return { text: 'ปิดแล้ว', class: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400', icon: 'fluent:checkmark-circle-24-filled' }
  }
  
  if (endDate && now > endDate) {
    return { text: 'หมดเวลา', class: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400', icon: 'fluent:clock-24-regular' }
  }
  
  if (now >= startDate && (!endDate || now <= endDate)) {
    return { text: 'เปิดอยู่', class: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400', icon: 'fluent:checkmark-circle-24-regular' }
  }
  
  return { text: 'รอเปิด', class: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400', icon: 'fluent:clock-24-regular' }
})

// Check if can check-in
const canCheckIn = computed(() => {
  if (props.attendance.is_closed) return false
  if (props.attendance.user_checked_in) return false
  
  const now = new Date()
  const startDate = new Date(props.attendance.start_date || props.attendance.date)
  const endDate = props.attendance.end_date ? new Date(props.attendance.end_date) : null
  
  return now >= startDate && (!endDate || now <= endDate)
})

// Attendance stats
const stats = computed(() => ({
  total: props.attendance.total_members || 0,
  present: props.attendance.present_count || 0,
  absent: props.attendance.absent_count || 0,
  late: props.attendance.late_count || 0
}))

const attendanceRate = computed(() => {
  if (stats.value.total === 0) return 0
  return Math.round((stats.value.present / stats.value.total) * 100)
})
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">
    <!-- Header -->
    <div class="p-4 border-b border-gray-100 dark:border-gray-700">
      <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
          <!-- Date icon -->
          <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex flex-col items-center justify-center">
            <span class="text-xs text-blue-600 dark:text-blue-400 font-medium">
              {{ new Date(attendance.start_date || attendance.date).toLocaleDateString('th-TH', { month: 'short' }) }}
            </span>
            <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
              {{ new Date(attendance.start_date || attendance.date).getDate() }}
            </span>
          </div>
          
          <div class="flex-1">
            <h4 class="font-semibold text-gray-900 dark:text-white line-clamp-1">
              {{ attendance.title || 'เช็คชื่อ' }}
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              {{ formatDate(attendance.start_date || attendance.date) }}
            </p>
            <div v-if="attendance.start_time" class="flex items-center gap-1 text-sm text-gray-400 mt-1">
              <Icon icon="fluent:clock-24-regular" class="w-4 h-4" />
              <span>{{ formatTime(attendance.start_time) }}</span>
              <template v-if="attendance.end_time">
                <span>-</span>
                <span>{{ formatTime(attendance.end_time) }}</span>
              </template>
            </div>
          </div>
        </div>
        
        <!-- Status badge -->
        <span class="px-2 py-1 rounded-full text-xs font-medium flex items-center gap-1" :class="statusInfo.class">
          <Icon :icon="statusInfo.icon" class="w-3 h-3" />
          {{ statusInfo.text }}
        </span>
      </div>
    </div>
    
    <!-- Stats (for admin) -->
    <div v-if="isCourseAdmin && stats.total > 0" class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50">
      <div class="flex items-center gap-4 text-sm">
        <div class="flex items-center gap-1">
          <div class="w-2 h-2 rounded-full bg-green-500"></div>
          <span class="text-gray-600 dark:text-gray-400">มา {{ stats.present }}</span>
        </div>
        <div class="flex items-center gap-1">
          <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
          <span class="text-gray-600 dark:text-gray-400">สาย {{ stats.late }}</span>
        </div>
        <div class="flex items-center gap-1">
          <div class="w-2 h-2 rounded-full bg-red-500"></div>
          <span class="text-gray-600 dark:text-gray-400">ขาด {{ stats.absent }}</span>
        </div>
        <div class="ml-auto text-gray-500 dark:text-gray-400">
          {{ attendanceRate }}%
        </div>
      </div>
      
      <!-- Progress bar -->
      <div class="mt-2 h-2 rounded-full bg-gray-200 dark:bg-gray-600 overflow-hidden flex">
        <div 
          class="h-full bg-green-500" 
          :style="{ width: `${(stats.present / stats.total) * 100}%` }"
        ></div>
        <div 
          class="h-full bg-yellow-500" 
          :style="{ width: `${(stats.late / stats.total) * 100}%` }"
        ></div>
        <div 
          class="h-full bg-red-500" 
          :style="{ width: `${(stats.absent / stats.total) * 100}%` }"
        ></div>
      </div>
    </div>
    
    <!-- User check-in status -->
    <div v-if="!isCourseAdmin && attendance.user_checked_in" class="px-4 py-2 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 text-sm flex items-center gap-2">
      <Icon icon="fluent:checkmark-circle-24-filled" class="w-4 h-4" />
      <span>เช็คชื่อแล้วเมื่อ {{ formatTime(attendance.user_check_in_time) }}</span>
    </div>
    
    <!-- Actions -->
    <div class="p-4 flex items-center gap-2">
      <!-- Admin actions -->
      <template v-if="isCourseAdmin">
        <button
          @click="emit('view-details', attendance)"
          class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          <Icon icon="fluent:people-list-24-regular" class="w-5 h-5" />
          <span>ดูรายละเอียด</span>
        </button>
        
        <button
          @click="emit('edit', attendance)"
          class="p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg"
          title="แก้ไข"
        >
          <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
        </button>
        
        <button
          @click="emit('delete', attendance.id)"
          class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"
          title="ลบ"
        >
          <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
        </button>
      </template>
      
      <!-- Student actions -->
      <template v-else>
        <button
          v-if="canCheckIn"
          @click="emit('check-in', attendance)"
          class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
        >
          <Icon icon="fluent:person-available-24-regular" class="w-5 h-5" />
          <span>เช็คชื่อ</span>
        </button>
        
        <button
          v-else-if="attendance.user_checked_in"
          disabled
          class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-lg cursor-not-allowed"
        >
          <Icon icon="fluent:checkmark-24-regular" class="w-5 h-5" />
          <span>เช็คชื่อแล้ว</span>
        </button>
        
        <button
          v-else
          disabled
          class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-lg cursor-not-allowed"
        >
          <Icon icon="fluent:clock-24-regular" class="w-5 h-5" />
          <span>{{ statusInfo.text }}</span>
        </button>
      </template>
    </div>
  </div>
</template>

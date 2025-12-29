<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useApi } from '~/composables/useApi'
import Swal from 'sweetalert2'

interface AttendanceRecord {
  id: number
  date: string
  start_at: string
  finish_at: string
  late_time?: number
  description?: string
  status: number | null // 1=มา, 2=สาย, 3=ลา, null/0=ขาด
  time_in?: string
}

interface Props {
  attendances: AttendanceRecord[]
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits(['reload', 'check-in-success'])

const api = useApi()
const checkingInId = ref<number | null>(null)

// Check if attendance is currently active (can check-in)
const isAttendanceActive = (attendance: AttendanceRecord): boolean => {
  const now = new Date()
  const startAt = new Date(attendance.start_at)
  const finishAt = new Date(attendance.finish_at)
  
  return now >= startAt && now <= finishAt
}

// Check if attendance hasn't started yet
const isAttendanceNotStarted = (attendance: AttendanceRecord): boolean => {
  const now = new Date()
  const startAt = new Date(attendance.start_at)
  return now < startAt
}

// Check if attendance has ended
const isAttendanceEnded = (attendance: AttendanceRecord): boolean => {
  const now = new Date()
  const finishAt = new Date(attendance.finish_at)
  return now > finishAt
}

// Check if student already checked in
const hasCheckedIn = (attendance: AttendanceRecord): boolean => {
  return attendance.status === 1 || attendance.status === 2
}

// Check if student would be late
const wouldBeLate = (attendance: AttendanceRecord): boolean => {
  const now = new Date()
  const startAt = new Date(attendance.start_at)
  const lateTime = attendance.late_time || 15 // Default 15 minutes
  const lateThreshold = new Date(startAt.getTime() + lateTime * 60000)
  
  return now > lateThreshold
}

// Handle check-in
const handleCheckIn = async (attendance: AttendanceRecord) => {
  if (checkingInId.value) return
  
  checkingInId.value = attendance.id
  
  try {
    const response = await api.post(`/api/attendances/${attendance.id}/check-in`)
    
    const statusText = response.status === 1 ? 'มา (ตรงเวลา)' : 'มา (สาย)'
    const iconColor = response.status === 1 ? '#10b981' : '#f59e0b'
    
    await Swal.fire({
      icon: 'success',
      title: 'รายงานตัวสำเร็จ!',
      html: `
        <div class="text-center">
          <p class="text-lg font-semibold" style="color: ${iconColor}">${statusText}</p>
          <p class="text-gray-500 mt-2">เวลาเข้าเรียน: ${response.time_in}</p>
        </div>
      `,
      confirmButtonColor: '#8b5cf6',
      confirmButtonText: 'ตกลง',
      timer: 3000,
      timerProgressBar: true
    })
    
    emit('check-in-success', { attendanceId: attendance.id, status: response.status })
    emit('reload')
  } catch (error: any) {
    const message = error.response?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่'
    
    Swal.fire({
      icon: 'error',
      title: 'ไม่สามารถรายงานตัวได้',
      text: message,
      confirmButtonColor: '#8b5cf6',
      confirmButtonText: 'ตกลง'
    })
  } finally {
    checkingInId.value = null
  }
}

// Format date to Thai format
const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  const day = date.getDate()
  const month = date.getMonth() + 1
  const year = date.getFullYear() + 543 // Buddhist Era
  return `${String(day).padStart(2, '0')}/${String(month).padStart(2, '0')}/${year}`
}

// Format day of week in Thai
const formatDayOfWeek = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const days = ['วันอาทิตย์', 'วันจันทร์', 'วันอังคาร', 'วันพุธ', 'วันพฤหัสบดี', 'วันศุกร์', 'วันเสาร์']
  return days[date.getDay()]
}

// Format time
const formatTime = (dateTimeString: string) => {
  if (!dateTimeString) return '-'
  const date = new Date(dateTimeString)
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${hours}:${minutes}`
}

// Get status config
const getStatusConfig = (status: number | null | undefined) => {
  if (status === 1) {
    return {
      label: 'มา',
      icon: 'heroicons:check-circle-20-solid',
      bgClass: 'bg-green-100 dark:bg-green-900/40',
      textClass: 'text-green-700 dark:text-green-300',
      iconClass: 'text-green-600 dark:text-green-400',
      dotClass: 'bg-green-500'
    }
  }
  if (status === 2) {
    return {
      label: 'สาย',
      icon: 'heroicons:clock-20-solid',
      bgClass: 'bg-amber-100 dark:bg-amber-900/40',
      textClass: 'text-amber-700 dark:text-amber-300',
      iconClass: 'text-amber-600 dark:text-amber-400',
      dotClass: 'bg-amber-500'
    }
  }
  if (status === 3) {
    return {
      label: 'ลา',
      icon: 'heroicons:document-text-20-solid',
      bgClass: 'bg-blue-100 dark:bg-blue-900/40',
      textClass: 'text-blue-700 dark:text-blue-300',
      iconClass: 'text-blue-600 dark:text-blue-400',
      dotClass: 'bg-blue-500'
    }
  }
  // null, undefined, 0 = ขาด (Absent)
  return {
    label: 'ขาด',
    icon: 'heroicons:x-circle-20-solid',
    bgClass: 'bg-red-100 dark:bg-red-900/40',
    textClass: 'text-red-700 dark:text-red-300',
    iconClass: 'text-red-600 dark:text-red-400',
    dotClass: 'bg-red-500'
  }
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-violet-500 via-purple-500 to-fuchsia-500 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
            <Icon icon="fluent:calendar-checkmark-24-filled" class="w-7 h-7 text-white" />
          </div>
          <div>
            <h3 class="text-white/80 text-sm font-medium">ข้อมูลการเข้าเรียน</h3>
            <p class="text-white text-lg font-bold">
              แสดง {{ attendances.length }} จาก {{ attendances.length }} รายการ
            </p>
          </div>
        </div>
        
        <!-- Reload Button -->
        <button
          @click="emit('reload')"
          :disabled="loading"
          class="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-lg transition-all duration-200 font-medium disabled:opacity-50"
        >
          <Icon 
            icon="fluent:arrow-sync-24-filled" 
            class="w-5 h-5"
            :class="{ 'animate-spin': loading }"
          />
          <span>รีโหลด</span>
        </button>
      </div>
    </div>
    
    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="w-full">
        <!-- Table Header -->
        <thead class="bg-gradient-to-r from-violet-50 via-purple-50 to-fuchsia-50 dark:from-violet-900/30 dark:via-purple-900/30 dark:to-fuchsia-900/30">
          <tr>
            <!-- ลำดับ -->
            <th class="px-4 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <div class="w-8 h-8 bg-violet-100 dark:bg-violet-800 rounded-lg flex items-center justify-center">
                  <Icon icon="fluent:number-symbol-24-regular" class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                </div>
                <span class="text-sm font-bold text-violet-700 dark:text-violet-300">ลำดับ</span>
              </div>
            </th>
            
            <!-- วันที่ -->
            <th class="px-4 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-800 rounded-lg flex items-center justify-center">
                  <Icon icon="fluent:calendar-24-regular" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                </div>
                <span class="text-sm font-bold text-purple-700 dark:text-purple-300">วันที่</span>
              </div>
            </th>
            
            <!-- เวลาเริ่ม -->
            <th class="px-4 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <div class="w-8 h-8 bg-cyan-100 dark:bg-cyan-800 rounded-lg flex items-center justify-center">
                  <Icon icon="fluent:play-circle-24-regular" class="w-5 h-5 text-cyan-600 dark:text-cyan-400" />
                </div>
                <span class="text-sm font-bold text-cyan-700 dark:text-cyan-300">เวลาเริ่ม</span>
              </div>
            </th>
            
            <!-- เวลาสิ้นสุด -->
            <th class="px-4 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <div class="w-8 h-8 bg-pink-100 dark:bg-pink-800 rounded-lg flex items-center justify-center">
                  <Icon icon="fluent:stop-24-regular" class="w-5 h-5 text-pink-600 dark:text-pink-400" />
                </div>
                <span class="text-sm font-bold text-pink-700 dark:text-pink-300">เวลาสิ้นสุด</span>
              </div>
            </th>
            
            <!-- คำอธิบาย -->
            <th class="px-4 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <div class="w-8 h-8 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center">
                  <Icon icon="fluent:document-text-24-regular" class="w-5 h-5 text-slate-600 dark:text-slate-400" />
                </div>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">คำอธิบาย</span>
              </div>
            </th>
            
            <!-- สถานะการเข้าเรียน -->
            <th class="px-4 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-800 rounded-lg flex items-center justify-center">
                  <Icon icon="fluent:checkmark-circle-24-regular" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <span class="text-sm font-bold text-emerald-700 dark:text-emerald-300">สถานะการเข้าเรียน</span>
              </div>
            </th>
          </tr>
        </thead>
        
        <!-- Table Body -->
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          <tr 
            v-for="(attendance, index) in attendances" 
            :key="attendance.id"
            class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150"
          >
            <!-- ลำดับ -->
            <td class="px-4 py-4 text-center">
              <span class="inline-flex items-center justify-center w-8 h-8 bg-violet-100 dark:bg-violet-800 text-violet-700 dark:text-violet-300 rounded-lg font-bold text-sm">
                {{ index + 1 }}
              </span>
            </td>
            
            <!-- วันที่ -->
            <td class="px-4 py-4 text-center">
              <div class="flex flex-col items-center">
                <span class="font-semibold text-gray-900 dark:text-white">
                  {{ formatDate(attendance.date || attendance.start_at) }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                  {{ formatDayOfWeek(attendance.date || attendance.start_at) }}
                </span>
              </div>
            </td>
            
            <!-- เวลาเริ่ม -->
            <td class="px-4 py-4 text-center">
              <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-cyan-50 dark:bg-cyan-900/30 rounded-lg">
                <Icon icon="fluent:play-circle-24-filled" class="w-4 h-4 text-cyan-600 dark:text-cyan-400" />
                <span class="font-semibold text-cyan-700 dark:text-cyan-300">
                  {{ formatTime(attendance.start_at) }}
                </span>
              </div>
            </td>
            
            <!-- เวลาสิ้นสุด -->
            <td class="px-4 py-4 text-center">
              <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-pink-50 dark:bg-pink-900/30 rounded-lg">
                <Icon icon="fluent:stop-24-filled" class="w-4 h-4 text-pink-600 dark:text-pink-400" />
                <span class="font-semibold text-pink-700 dark:text-pink-300">
                  {{ formatTime(attendance.finish_at) }}
                </span>
              </div>
            </td>
            
            <!-- คำอธิบาย -->
            <td class="px-4 py-4 text-center">
              <span class="text-gray-600 dark:text-gray-400">
                {{ attendance.description || '-' }}
              </span>
            </td>
            
            <!-- สถานะการเข้าเรียน -->
            <td class="px-4 py-4 text-center">
              <!-- Already checked in - show status -->
              <div 
                v-if="hasCheckedIn(attendance)"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full"
                :class="getStatusConfig(attendance.status).bgClass"
              >
                <Icon 
                  :icon="getStatusConfig(attendance.status).icon" 
                  class="w-5 h-5"
                  :class="getStatusConfig(attendance.status).iconClass"
                />
                <span 
                  class="font-semibold"
                  :class="getStatusConfig(attendance.status).textClass"
                >
                  {{ getStatusConfig(attendance.status).label }}
                </span>
                <span 
                  class="w-2 h-2 rounded-full animate-pulse"
                  :class="getStatusConfig(attendance.status).dotClass"
                ></span>
              </div>
              
              <!-- Can check-in - show button -->
              <button
                v-else-if="isAttendanceActive(attendance)"
                @click="handleCheckIn(attendance)"
                :disabled="checkingInId === attendance.id"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full font-semibold transition-all duration-200 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
                :class="wouldBeLate(attendance) 
                  ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-800/50' 
                  : 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-800/50'"
              >
                <Icon 
                  v-if="checkingInId === attendance.id"
                  icon="fluent:spinner-ios-20-filled" 
                  class="w-5 h-5 animate-spin"
                />
                <Icon 
                  v-else
                  :icon="wouldBeLate(attendance) ? 'heroicons:clock-20-solid' : 'heroicons:hand-raised-20-solid'" 
                  class="w-5 h-5"
                />
                <span>{{ wouldBeLate(attendance) ? 'รายงานตัว (สาย)' : 'รายงานตัว' }}</span>
              </button>
              
              <!-- Not started yet -->
              <div 
                v-else-if="isAttendanceNotStarted(attendance)"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gray-100 dark:bg-gray-700"
              >
                <Icon icon="fluent:clock-24-regular" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                <span class="font-semibold text-gray-500 dark:text-gray-400">รอเวลาเริ่ม</span>
              </div>
              
              <!-- Ended - show absent status -->
              <div 
                v-else
                class="inline-flex items-center gap-2 px-4 py-2 rounded-full"
                :class="getStatusConfig(attendance.status).bgClass"
              >
                <Icon 
                  :icon="getStatusConfig(attendance.status).icon" 
                  class="w-5 h-5"
                  :class="getStatusConfig(attendance.status).iconClass"
                />
                <span 
                  class="font-semibold"
                  :class="getStatusConfig(attendance.status).textClass"
                >
                  {{ getStatusConfig(attendance.status).label }}
                </span>
                <span 
                  class="w-2 h-2 rounded-full animate-pulse"
                  :class="getStatusConfig(attendance.status).dotClass"
                ></span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Empty State -->
    <div v-if="attendances.length === 0 && !loading" class="py-12 text-center">
      <div class="w-24 h-24 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center">
        <Icon icon="fluent:calendar-empty-24-regular" class="w-12 h-12 text-gray-400 dark:text-gray-500" />
      </div>
      <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">
        ยังไม่มีข้อมูลการเข้าเรียน
      </h3>
      <p class="text-gray-500 dark:text-gray-400">
        รอผู้สอนสร้างการเช็คชื่อใหม่
      </p>
    </div>
    
    <!-- Loading Skeleton -->
    <div v-if="loading" class="divide-y divide-gray-100 dark:divide-gray-700">
      <div v-for="i in 3" :key="i" class="px-4 py-4 flex items-center gap-4 animate-pulse">
        <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
        <div class="flex-1 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
        <div class="w-16 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
        <div class="w-16 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
        <div class="w-24 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
        <div class="w-20 h-8 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
      </div>
    </div>
  </div>
</template>

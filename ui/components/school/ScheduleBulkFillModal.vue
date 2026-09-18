<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { toMinutes } from '~/composables/useScheduleGrid'

const props = defineProps<{
  academyId: number
  semesterId: number
  classroom: any
  periodRows: any[]
  courses: any[]
  teachers: any[]
  occupiedSlots: any[]
  dayOptions: any[]
}>()

const emit = defineEmits(['close', 'created'])

const api = useApi()

const selectedDays = ref<number[]>([])
const selectedPeriodKeys = ref<string[]>([])
const entryType = ref<'course' | 'activity' | 'break' | 'exam'>('course')
const courseId = ref<number | null>(null)
const title = ref('')
const teacherId = ref<number | null>(null)
const room = ref('')

const roomOptions = ref<string[]>([])
const isSubmitting = ref(false)
const submitResult = ref<{ count: number; errors: any[] } | null>(null)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${props.academyId}/schedules/rooms`)
    if (response.success) {
      roomOptions.value = response.data || []
    }
  } catch (e) {
    // ไม่มีรายการสถานที่ให้เลือกก็ยังพิมพ์เองได้ ไม่ต้องรบกวนผู้ใช้
  }
})

const toggleDay = (dayValue: number) => {
  const idx = selectedDays.value.indexOf(dayValue)
  if (idx >= 0) {
    selectedDays.value.splice(idx, 1)
  } else {
    selectedDays.value.push(dayValue)
  }
}

const selectWeekdays = () => {
  selectedDays.value = [1, 2, 3, 4, 5]
}
const clearDays = () => {
  selectedDays.value = []
}

const togglePeriod = (key: string) => {
  const idx = selectedPeriodKeys.value.indexOf(key)
  if (idx >= 0) {
    selectedPeriodKeys.value.splice(idx, 1)
  } else {
    selectedPeriodKeys.value.push(key)
  }
}
const selectAllPeriods = () => {
  selectedPeriodKeys.value = props.periodRows.map(r => r.key)
}
const clearPeriods = () => {
  selectedPeriodKeys.value = []
}

const getCourseLabel = (c: any) => {
  return c.code ? `${c.code} — ${c.name}` : c.name
}

const getTeacherLabel = (t: any) => {
  return t.member_name || t.user?.name || '-'
}

const generatedSlots = computed(() => {
  const slots: any[] = []
  
  for (const day of selectedDays.value) {
    for (const key of selectedPeriodKeys.value) {
      const row = props.periodRows.find(r => r.key === key)
      if (!row) continue
      
      const start = toMinutes(row.start)
      const end = toMinutes(row.end)
      
      const conflict = props.occupiedSlots.find(occ => {
        if (occ.day !== day) return false
        const oStart = toMinutes(occ.start_time)
        const oEnd = toMinutes(occ.end_time)
        return start < oEnd && end > oStart
      })
      
      slots.push({
        day,
        row,
        conflict
      })
    }
  }
  return slots
})

const validSlots = computed(() => generatedSlots.value.filter(s => !s.conflict))
const conflictCount = computed(() => generatedSlots.value.filter(s => s.conflict).length)

// API บังคับ `title` เมื่อไม่มี `course_id` — กันไว้ตั้งแต่ฝั่งจอ
// ไม่งั้นจะยิงไปทั้งชุดแล้วตกทุกรายการด้วยเหตุผลเดียวกัน
const needsTitle = computed(() => !(entryType.value === 'course' && courseId.value))

const canSubmit = computed(() => {
  if (validSlots.value.length === 0) return false
  if (!props.classroom || !props.semesterId || !teacherId.value) return false
  if (needsTitle.value && !title.value.trim()) return false

  return true
})

const submit = async () => {
  if (!canSubmit.value || isSubmitting.value) return
  
  const schedules = validSlots.value.map(slot => ({
    semester_id: props.semesterId,
    classroom_id: props.classroom.id,
    course_id: entryType.value === 'course' ? courseId.value : null,
    title: title.value || undefined,
    entry_type: entryType.value,
    teacher_id: teacherId.value,
    day_of_week: slot.day,
    start_time: slot.row.start,
    end_time: slot.row.end,
    period_id: slot.row.periodId ?? undefined,
    room: room.value || undefined,
  }))
  
  isSubmitting.value = true
  submitResult.value = null
  
  try {
    const response: any = await api.post(`/api/academies/${props.academyId}/schedules/bulk`, { schedules })

    if (response.success) {
      submitResult.value = {
        count: response.data?.created_count ?? 0,
        errors: response.data?.errors || []
      }
    }
  } catch (err: any) {
    // useApi โยน error ที่อ่านได้จาก err.data (ไม่ใช่ err.response.data)
    const errorData = err.data?.errors
    const mappedErrors: any[] = []
    
    if (Array.isArray(errorData)) {
      mappedErrors.push(...errorData)
    } else if (errorData && typeof errorData === 'object') {
      for (const [key, msgs] of Object.entries(errorData)) {
        const match = key.match(/^schedules\.(\d+)\.(.+)$/)
        if (match) {
          mappedErrors.push({
            index: parseInt(match[1], 10),
            message: Array.isArray(msgs) ? msgs[0] : msgs
          })
        } else {
          mappedErrors.push({
            index: -1,
            message: Array.isArray(msgs) ? msgs[0] : msgs
          })
        }
      }
    } else {
      mappedErrors.push({ index: -1, message: err.data?.message || 'เกิดข้อผิดพลาด' })
    }
    
    submitResult.value = {
      count: err.data?.data?.created_count || 0,
      errors: mappedErrors
    }
  } finally {
    isSubmitting.value = false
  }
}

const getSlotSummaryByIndex = (index: number) => {
  if (index >= 0 && index < validSlots.value.length) {
    const slot = validSlots.value[index]
    const dayName = props.dayOptions.find(d => d.value === slot.day)?.label || `วัน ${slot.day}`
    return `${dayName} ${slot.row.start}-${slot.row.end}`
  }
  return 'ทั่วไป'
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex flex-col bg-black/50 sm:p-4 md:p-6 lg:p-8 sm:items-center sm:justify-center">
    <div class="bg-white dark:bg-gray-800 flex flex-col w-full h-full sm:h-auto sm:max-h-[90vh] sm:max-w-2xl sm:rounded-2xl shadow-xl overflow-hidden">
      <!-- Header -->
      <div class="min-h-[44px] px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-800 flex-shrink-0">
        <h3 class="font-semibold text-lg text-gray-900 dark:text-white flex-1 min-w-0 break-words">เติมหลายคาบ: {{ classroom.name }}</h3>
        <button @click="emit('close')" class="p-2 -mr-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex-shrink-0 whitespace-nowrap">
          <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
        </button>
      </div>
      
      <!-- Body -->
      <div v-if="!submitResult" class="flex-1 overflow-y-auto p-3 sm:p-6 space-y-6">
        
        <!-- Box 1: Details -->
        <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl space-y-4">
          <h4 class="font-medium text-gray-700 dark:text-gray-300 text-sm sm:text-base">รายละเอียดคาบ (ใช้เหมือนกันทุกช่องที่เลือก)</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex flex-col gap-1">
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">ประเภท</label>
              <select v-model="entryType" class="min-h-[44px] sm:min-h-0 bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-lg shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="course">คอร์สเรียน</option>
                <option value="activity">กิจกรรม</option>
                <option value="break">พัก</option>
                <option value="exam">สอบ</option>
              </select>
            </div>
            
            <div v-if="entryType === 'course'" class="flex flex-col gap-1">
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">คอร์ส</label>
              <CommonSearchableSelect
                v-model="courseId"
                :options="courses"
                option-value="id"
                :option-label="getCourseLabel"
                placeholder="เลือกคอร์ส"
              />
            </div>
            
            <div class="flex flex-col gap-1">
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">ชื่อคาบ <span v-if="entryType !== 'course'" class="text-red-500">*</span></label>
              <input v-model="title" type="text" class="min-h-[44px] sm:min-h-0 bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-lg shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="ชื่อคาบ" />
            </div>
            
            <div class="flex flex-col gap-1">
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">ครูผู้สอน <span class="text-red-500">*</span></label>
              <CommonSearchableSelect
                v-model="teacherId"
                :options="teachers"
                option-value="user_id"
                :option-label="getTeacherLabel"
                placeholder="เลือกครูผู้สอน"
              />
            </div>
            
            <div class="flex flex-col gap-1">
              <label class="text-sm font-medium text-gray-700 dark:text-gray-300">สถานที่</label>
              <input v-model="room" list="bulk-room-options" type="text" class="min-h-[44px] sm:min-h-0 bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-lg shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="สถานที่" />
              <datalist id="bulk-room-options">
                <option v-for="r in roomOptions" :key="r" :value="r"></option>
              </datalist>
            </div>
          </div>
        </div>
        
        <!-- Box 2: Select Days -->
        <div class="space-y-3">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <h4 class="font-medium text-gray-700 dark:text-gray-300 text-sm sm:text-base">เลือกวัน</h4>
            <div class="flex gap-2">
              <button type="button" @click="selectWeekdays" class="text-sm text-emerald-600 hover:text-emerald-700 min-h-[44px] px-3 font-medium flex-shrink-0 whitespace-nowrap">จ.–ศ.</button>
              <button type="button" @click="clearDays" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 min-h-[44px] px-3 font-medium flex-shrink-0 whitespace-nowrap">ล้าง</button>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="d in dayOptions"
              :key="d.value"
              type="button"
              @click="toggleDay(d.value)"
              class="min-h-[44px] px-4 rounded-lg font-medium border transition-colors"
              :class="selectedDays.includes(d.value) ? 'bg-emerald-100 dark:bg-emerald-900/50 border-emerald-500 text-emerald-800 dark:text-emerald-200' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600'"
            >
              {{ d.label }}
            </button>
          </div>
        </div>
        
        <!-- Box 3: Select Periods -->
        <div class="space-y-3">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <h4 class="font-medium text-gray-700 dark:text-gray-300 text-sm sm:text-base">เลือกคาบ</h4>
            <div class="flex gap-2">
              <button type="button" @click="selectAllPeriods" class="text-sm text-emerald-600 hover:text-emerald-700 min-h-[44px] px-3 font-medium flex-shrink-0 whitespace-nowrap">เลือกทุกคาบ</button>
              <button type="button" @click="clearPeriods" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 min-h-[44px] px-3 font-medium flex-shrink-0 whitespace-nowrap">ล้าง</button>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="row in periodRows"
              :key="row.key"
              type="button"
              @click="togglePeriod(row.key)"
              class="min-h-[44px] px-4 py-2 rounded-lg flex flex-col items-center justify-center border transition-colors"
              :class="selectedPeriodKeys.includes(row.key) ? 'bg-emerald-100 dark:bg-emerald-900/50 border-emerald-500 text-emerald-800 dark:text-emerald-200' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600'"
            >
              <span class="font-medium leading-tight text-sm">{{ row.label }}</span>
              <span class="text-[11px] opacity-80 leading-tight">{{ row.start }}-{{ row.end }}</span>
            </button>
          </div>
        </div>
        
        <!-- Box 4: Summary -->
        <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl p-4 text-sm text-blue-800 dark:text-blue-200 space-y-1">
          <p class="font-medium">สรุปการสร้าง</p>
          <p v-if="selectedDays.length === 0 || selectedPeriodKeys.length === 0">กรุณาเลือกวันและคาบอย่างน้อย 1 รายการ</p>
          <template v-else>
            <p>จะสร้าง <span class="font-bold">{{ validSlots.length }}</span> คาบ</p>
            <p v-if="conflictCount > 0" class="text-orange-600 dark:text-orange-400">
              * ข้าม <span class="font-bold">{{ conflictCount }}</span> คาบที่มีอยู่แล้วในตาราง
            </p>
          </template>
          <p v-if="!teacherId" class="text-red-600 dark:text-red-400">* ยังไม่ได้เลือกครูผู้สอน</p>
          <p v-if="needsTitle && !title.trim()" class="text-red-600 dark:text-red-400">* ต้องระบุคอร์ส หรือพิมพ์ชื่อคาบ</p>
        </div>
        
      </div>
      
      <!-- Result State -->
      <div v-else class="flex-1 overflow-y-auto p-4 sm:p-6 flex flex-col items-center justify-center text-center space-y-4">
        <div class="w-16 h-16 rounded-full flex items-center justify-center" :class="submitResult.errors.length === 0 ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-300' : 'bg-orange-100 dark:bg-orange-900/50 text-orange-600 dark:text-orange-300'">
          <Icon :icon="submitResult.errors.length === 0 ? 'fluent:checkmark-24-filled' : 'fluent:warning-24-filled'" class="w-8 h-8" />
        </div>
        
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
          {{ submitResult.count > 0 ? `สร้างสำเร็จ ${submitResult.count} รายการ` : 'ไม่ได้สร้างคาบใดเลย' }}
        </h3>
        
        <div v-if="submitResult.errors.length > 0" class="w-full text-left mt-4">
          <p class="font-medium text-red-600 dark:text-red-400 mb-2">ไม่สามารถสร้างได้ {{ submitResult.errors.length }} รายการ:</p>
          <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg max-h-40 overflow-y-auto p-3 text-sm space-y-1">
            <div v-for="(err, i) in submitResult.errors" :key="i" class="text-red-700 dark:text-red-300">
              <span class="font-medium">{{ getSlotSummaryByIndex(err.index) }}</span> — {{ err.message }}
            </div>
          </div>
        </div>
      </div>
      
      <!-- Footer -->
      <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex flex-col sm:flex-row gap-3 sm:justify-end flex-shrink-0">
        <template v-if="!submitResult">
          <button
            type="button"
            @click="emit('close')"
            class="min-h-[44px] px-6 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors w-full sm:w-auto text-center"
          >
            ยกเลิก
          </button>
          <button
            type="button"
            :disabled="!canSubmit || isSubmitting"
            @click="submit"
            class="min-h-[44px] px-6 py-2.5 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed w-full sm:w-auto text-center flex items-center justify-center gap-2"
          >
            <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            <span>สร้าง {{ validSlots.length }} คาบ</span>
          </button>
        </template>
        <template v-else>
          <button
            type="button"
            @click="() => { emit('created'); emit('close') }"
            class="min-h-[44px] px-6 py-2.5 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors w-full sm:w-auto text-center"
          >
            เสร็จสิ้น
          </button>
        </template>
      </div>
      
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const props = defineProps<{
  academyId: number
  schedule: any
  date: string
  initialType: 'cancelled' | 'substitute' | 'room_change'
  show: boolean
}>()

const emit = defineEmits(['close', 'saved'])

const api = useApi()

const type = ref<'cancelled' | 'substitute' | 'room_change'>(props.initialType)
const substituteTeacherId = ref<number | null>(null)
const room = ref('')
const reason = ref('')
const errors = ref<Record<string, string[]>>({})

const teachers = ref<any[]>([])
const busyCount = ref(0)
const loadingTeachers = ref(false)
const rooms = ref<string[]>([])
const saving = ref(false)

const isEditMode = computed(() => !!props.schedule?.exception)

const loadAvailableTeachers = async () => {
  if (!props.schedule || !props.show) return
  loadingTeachers.value = true
  try {
    const res = await api.get(`/api/academies/${props.academyId}/schedules/exceptions/available-teachers`, {
      params: { schedule_id: props.schedule.id, date: props.date }
    })
    teachers.value = res.data?.teachers || []
    busyCount.value = res.data?.busy_count || 0
    
    if (isEditMode.value && props.schedule.exception?.substitute_teacher) {
      const existing = teachers.value.find(t => t.id === props.schedule.exception.substitute_teacher.id)
      if (!existing) {
        teachers.value.push({
          id: props.schedule.exception.substitute_teacher.id,
          name: props.schedule.exception.substitute_teacher.name,
        })
      }
    }
  } catch (err: any) {
    console.error('Failed to load teachers', err)
  } finally {
    loadingTeachers.value = false
  }
}

const loadRooms = async () => {
  try {
    const res = await api.get(`/api/academies/${props.academyId}/schedules/rooms`)
    rooms.value = res.data || []
  } catch (err) {
    console.error('Failed to load rooms', err)
  }
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    errors.value = {}
    // ล้างรายชื่อครูว่างของคาบก่อนหน้า — ไม่งั้นเปิดโมดัลของคาบใหม่เป็น "งดคาบ" แล้วสลับเป็น "สอนแทน"
    // จะเห็นครูว่างของคาบเดิม (watch(type) โหลดใหม่เฉพาะตอนรายการว่าง)
    teachers.value = []
    busyCount.value = 0
    if (isEditMode.value && props.schedule.exception) {
      type.value = props.schedule.exception.type
      substituteTeacherId.value = props.schedule.exception.substitute_teacher?.id || null
      room.value = props.schedule.exception.room || ''
      reason.value = props.schedule.exception.reason || ''
    } else {
      type.value = props.initialType
      substituteTeacherId.value = null
      room.value = ''
      reason.value = ''
    }
    
    if (type.value === 'substitute') {
      loadAvailableTeachers()
    }
    loadRooms()
  }
})

watch(type, (newType) => {
  errors.value = {}
  if (newType === 'substitute' && teachers.value.length === 0) {
    loadAvailableTeachers()
  }
})

const formatThaiDate = (dateStr: string) => {
  if (!dateStr) return ''
  return new Date(`${dateStr}T00:00:00`).toLocaleDateString('th-TH', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
  })
}

const isValid = computed(() => {
  if (type.value === 'substitute' && !substituteTeacherId.value) return false
  if (type.value === 'room_change' && !room.value.trim()) return false
  return true
})

const save = async () => {
  if (!isValid.value) return
  saving.value = true
  errors.value = {}
  
  const payload: any = {
    type: type.value,
    reason: reason.value.trim()
  }
  
  if (type.value === 'substitute') {
    payload.substitute_teacher_id = substituteTeacherId.value
  }
  
  if (type.value === 'substitute' || type.value === 'room_change') {
    payload.room = room.value.trim()
  }
  
  try {
    if (isEditMode.value) {
      await api.patch(`/api/academies/${props.academyId}/schedules/exceptions/${props.schedule.exception.id}`, payload)
    } else {
      payload.class_schedule_id = props.schedule.id
      payload.date = props.date
      await api.post(`/api/academies/${props.academyId}/schedules/exceptions`, payload)
    }
    
    Swal.fire({
      icon: 'success',
      title: 'บันทึกแล้ว',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    })
    emit('saved')
  } catch (err: any) {
    if (err.status === 422 && err.data?.errors) {
      errors.value = err.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.data?.message || 'ไม่สามารถบันทึกข้อมูลได้'
      })
    }
  } finally {
    saving.value = false
  }
}

const close = () => {
  emit('close')
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
    <div class="absolute inset-0 bg-black/50" @click="close"></div>
    <div class="relative w-full sm:max-w-lg bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-2xl max-h-[90vh] flex flex-col shadow-xl">
      <!-- Header -->
      <div class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
          {{ schedule?.display_title || 'จัดการคาบเรียน' }}
        </h3>
        <button type="button" class="w-11 h-11 flex items-center justify-center text-gray-400 hover:text-gray-500 rounded-full shrink-0" @click="close">
          <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
        </button>
      </div>
      
      <!-- Body -->
      <div class="p-4 overflow-y-auto">
        <div v-if="errors.class_schedule_id" class="mb-4 text-sm text-red-500 bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
          {{ errors.class_schedule_id[0] }}
        </div>
        <div v-if="errors.date" class="mb-4 text-sm text-red-500 bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
          {{ errors.date[0] }}
        </div>
        
        <div class="mb-6 text-sm text-gray-600 dark:text-gray-400 break-words">
          <p>{{ formatThaiDate(date) }} · {{ schedule?.start_time }}–{{ schedule?.end_time }} · ห้อง {{ schedule?.classroom?.name || '-' }}</p>
          <p>ครูประจำคาบ: {{ schedule?.teacher?.name || '-' }}</p>
        </div>
        
        <!-- Type Selection -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทรายการ</label>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <label class="flex items-center justify-center min-h-[44px] px-3 border rounded-lg cursor-pointer transition-colors"
              :class="type === 'cancelled' ? 'bg-red-50 border-red-200 text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400' : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300'">
              <input type="radio" value="cancelled" v-model="type" class="sr-only">
              <span>งดคาบ</span>
            </label>
            <label class="flex items-center justify-center min-h-[44px] px-3 border rounded-lg cursor-pointer transition-colors"
              :class="type === 'substitute' ? 'bg-amber-50 border-amber-200 text-amber-700 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-400' : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300'">
              <input type="radio" value="substitute" v-model="type" class="sr-only">
              <span>สอนแทน</span>
            </label>
            <label class="flex items-center justify-center min-h-[44px] px-3 border rounded-lg cursor-pointer transition-colors"
              :class="type === 'room_change' ? 'bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-400' : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300'">
              <input type="radio" value="room_change" v-model="type" class="sr-only">
              <span>ย้ายห้อง</span>
            </label>
          </div>
          <p v-if="errors.type" class="mt-1 text-sm text-red-500">{{ errors.type[0] }}</p>
        </div>
        
        <!-- Substitute Teacher Section -->
        <div v-if="type === 'substitute'" class="mb-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            ครูผู้สอนแทน <span class="text-red-500">*</span>
          </label>
          <div v-if="loadingTeachers" class="text-sm text-gray-500 mb-2">กำลังโหลด...</div>
          <div v-else>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
              ครูที่ว่างในคาบนี้ {{ teachers.length }} คน · ไม่ว่าง {{ busyCount }} คน
            </p>
            <div v-if="teachers.length > 0">
              <CommonSearchableSelect
                v-model="substituteTeacherId"
                :options="teachers"
                option-value="id"
                option-label="name"
                placeholder="เลือกครูผู้สอนแทน"
              />
            </div>
            <p v-else class="text-sm text-orange-500">ไม่มีครูว่างในคาบนี้</p>
            <p v-if="errors.substitute_teacher_id" class="mt-1 text-sm text-red-500">{{ errors.substitute_teacher_id[0] }}</p>
          </div>
        </div>
        
        <!-- Room Selection -->
        <div v-if="type === 'substitute' || type === 'room_change'" class="mb-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            สถานที่ <span v-if="type === 'room_change'" class="text-red-500">*</span>
          </label>
          <input type="text" v-model="room" list="room-list" class="w-full min-h-[44px] px-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="ระบุสถานที่">
          <datalist id="room-list">
            <option v-for="r in rooms" :key="r" :value="r"></option>
          </datalist>
          <p v-if="type === 'substitute'" class="text-xs text-gray-500 mt-1">เว้นว่าง = ห้องเดิม</p>
          <p v-if="errors.room" class="mt-1 text-sm text-red-500">{{ errors.room[0] }}</p>
        </div>
        
        <!-- Reason Section -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เหตุผล (ไม่บังคับ)</label>
          <input type="text" v-model="reason" maxlength="255" class="w-full min-h-[44px] px-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="เช่น ลาป่วย / ไปราชการ / กิจกรรมโรงเรียน">
          <p v-if="errors.reason" class="mt-1 text-sm text-red-500">{{ errors.reason[0] }}</p>
        </div>
      </div>
      
      <!-- Footer -->
      <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex flex-col-reverse sm:flex-row justify-end gap-2 shrink-0">
        <button type="button" @click="close" class="min-h-[44px] px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 w-full sm:w-auto font-medium">
          ยกเลิก
        </button>
        <button type="button" @click="save" :disabled="!isValid || saving" class="min-h-[44px] px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 w-full sm:w-auto font-medium">
          {{ saving ? 'กำลังบันทึก...' : 'บันทึก' }}
        </button>
      </div>
    </div>
  </div>
</template>

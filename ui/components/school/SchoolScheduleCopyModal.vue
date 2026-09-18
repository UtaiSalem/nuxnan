<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  academyId: number
  targetSemesterId: number
  targetSemesterName: string
  semesters: any[]
  classrooms: any[]
}>()

const emit = defineEmits(['close', 'copied'])

const api = useApi()

const sourceSemesterId = ref<number | null>(null)
const limitToClassrooms = ref(false)
const selectedClassroomIds = ref<number[]>([])
const isSubmitting = ref(false)
const result = ref<any | null>(null)
const submitErrors = ref<string[]>([])

const availableSourceSemesters = computed(() => {
  return props.semesters.filter(s => s.id !== props.targetSemesterId)
})

const toggleClassroom = (id: number) => {
  const idx = selectedClassroomIds.value.indexOf(id)
  if (idx >= 0) {
    selectedClassroomIds.value.splice(idx, 1)
  } else {
    selectedClassroomIds.value.push(id)
  }
}

const selectAllClassrooms = () => {
  selectedClassroomIds.value = props.classrooms.map(c => c.id)
}

const clearClassrooms = () => {
  selectedClassroomIds.value = []
}

const canSubmit = computed(() => {
  if (!sourceSemesterId.value) return false
  if (limitToClassrooms.value && selectedClassroomIds.value.length === 0) return false
  return true
})

const submit = async () => {
  if (!canSubmit.value || isSubmitting.value) return

  isSubmitting.value = true
  submitErrors.value = []

  try {
    const payload = {
      source_semester_id: sourceSemesterId.value,
      target_semester_id: props.targetSemesterId,
      classroom_ids: limitToClassrooms.value ? selectedClassroomIds.value : undefined
    }

    const response: any = await api.post(`/api/academies/${props.academyId}/schedules/copy`, payload)

    if (response.success) {
      result.value = response.data
    }
  } catch (err: any) {
    const errorData = err.data?.errors
    if (errorData && typeof errorData === 'object') {
      const msgs: string[] = []
      Object.values(errorData).forEach((e: any) => {
        if (Array.isArray(e)) msgs.push(...e)
        else msgs.push(e)
      })
      submitErrors.value = msgs
    } else {
      submitErrors.value = [err.data?.message || 'เกิดข้อผิดพลาดในการคัดลอกตาราง']
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex flex-col bg-black/50 sm:p-4 md:p-6 lg:p-8 sm:items-center sm:justify-center">
    <div class="bg-white dark:bg-gray-800 flex flex-col w-full h-full sm:h-auto sm:max-h-[90vh] sm:max-w-2xl sm:rounded-2xl shadow-xl overflow-hidden">
      <!-- Header -->
      <div class="min-h-[44px] px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-800 flex-shrink-0">
        <h3 class="font-semibold text-lg text-gray-900 dark:text-white flex-1 min-w-0 break-words">คัดลอกตารางจากภาคเรียนอื่น</h3>
        <button @click="emit('close')" class="p-2 -mr-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex-shrink-0 whitespace-nowrap">
          <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
        </button>
      </div>
      
      <!-- Body -->
      <div v-if="!result" class="flex-1 overflow-y-auto p-3 sm:p-6 space-y-6">
        
        <!-- Target Semester Info -->
        <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl border border-gray-100 dark:border-gray-800">
          <p class="text-sm text-gray-600 dark:text-gray-400">คัดลอกมาลงที่:</p>
          <p class="font-medium text-gray-900 dark:text-white">{{ targetSemesterName }}</p>
        </div>

        <div v-if="submitErrors.length > 0" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-xl p-4 text-sm text-red-800 dark:text-red-200 space-y-1">
          <p v-for="(err, i) in submitErrors" :key="i">{{ err }}</p>
        </div>
        
        <!-- Source Semester Select -->
        <div v-if="availableSourceSemesters.length > 0" class="flex flex-col gap-2">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">เลือกภาคเรียนต้นทาง</label>
          <select v-model="sourceSemesterId" class="min-h-[44px] bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white rounded-lg shadow-sm focus:border-emerald-500 focus:ring-emerald-500 w-full px-3">
            <option :value="null" disabled>-- เลือกภาคเรียน --</option>
            <option v-for="sem in availableSourceSemesters" :key="sem.id" :value="sem.id">{{ sem.name }}</option>
          </select>
        </div>
        <div v-else class="bg-orange-50 dark:bg-orange-900/30 border border-orange-200 dark:border-orange-800 rounded-xl p-4 text-sm text-orange-800 dark:text-orange-200">
          ปีการศึกษานี้มีภาคเรียนเดียว จึงยังไม่มีภาคเรียนต้นทางให้คัดลอก
        </div>

        <!-- Limit Classrooms Checkbox -->
        <div class="space-y-4">
          <label class="flex items-center gap-3 cursor-pointer min-h-[44px]">
            <input type="checkbox" v-model="limitToClassrooms" class="w-5 h-5 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700" />
            <span class="text-gray-700 dark:text-gray-300 font-medium">เฉพาะบางห้องเรียน</span>
          </label>
          
          <div v-if="limitToClassrooms" class="pl-8 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <h4 class="font-medium text-gray-700 dark:text-gray-300 text-sm">เลือกห้องเรียน</h4>
              <div class="flex gap-2">
                <button type="button" @click="selectAllClassrooms" class="text-sm text-emerald-600 hover:text-emerald-700 min-h-[44px] px-3 font-medium flex-shrink-0 whitespace-nowrap">เลือกทั้งหมด</button>
                <button type="button" @click="clearClassrooms" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 min-h-[44px] px-3 font-medium flex-shrink-0 whitespace-nowrap">ล้าง</button>
              </div>
            </div>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="c in classrooms"
                :key="c.id"
                type="button"
                @click="toggleClassroom(c.id)"
                class="min-h-[44px] px-4 py-2 rounded-lg font-medium border transition-colors"
                :class="selectedClassroomIds.includes(c.id) ? 'bg-emerald-100 dark:bg-emerald-900/50 border-emerald-500 text-emerald-800 dark:text-emerald-200' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600'"
              >
                {{ c.name }}
              </button>
            </div>
          </div>
        </div>

        <!-- Warning Box -->
        <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 text-sm text-yellow-800 dark:text-yellow-200">
          คัดลอกได้เฉพาะภายในปีการศึกษาเดียวกัน · คาบที่ชนกับของเดิมที่ปลายทางจะถูกข้าม ไม่มีการลบของเดิม
        </div>
        
      </div>
      
      <!-- Result State -->
      <div v-else class="flex-1 overflow-y-auto p-4 sm:p-6 flex flex-col items-center justify-center text-center space-y-4">
        <div class="w-16 h-16 rounded-full flex items-center justify-center" :class="result.copied_count > 0 ? 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-300' : 'bg-orange-100 dark:bg-orange-900/50 text-orange-600 dark:text-orange-300'">
          <Icon :icon="result.copied_count > 0 ? 'fluent:checkmark-24-filled' : 'fluent:warning-24-filled'" class="w-8 h-8" />
        </div>
        
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
          {{ result.copied_count > 0 ? `คัดลอกสำเร็จ ${result.copied_count} คาบ` : 'ไม่ได้คัดลอกคาบใดเลย' }}
        </h3>
        
        <div v-if="result.course_semester_mismatch_count > 0" class="w-full text-left mt-4 bg-orange-50 dark:bg-orange-900/30 border border-orange-200 dark:border-orange-800 rounded-xl p-4 text-sm text-orange-800 dark:text-orange-200">
          มี {{ result.course_semester_mismatch_count }} คาบที่คอร์สยังเป็นของภาคเรียนเดิม — ตรวจสอบและผูกคอร์สใหม่อีกครั้ง
        </div>

        <div v-if="result.skipped_count > 0" class="w-full text-left mt-4">
          <p class="font-medium text-red-600 dark:text-red-400 mb-2">ข้าม {{ result.skipped_count }} คาบ:</p>
          <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg max-h-60 overflow-y-auto p-3 text-sm space-y-2">
            <div v-for="(skip, i) in (result.skipped || [])" :key="i" class="text-red-700 dark:text-red-300">
              <span class="font-medium">{{ skip.classroom }} · {{ skip.day }} {{ skip.time }}</span> — {{ skip.title }} · {{ skip.reason }}
            </div>
          </div>
        </div>
      </div>
      
      <!-- Footer -->
      <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex flex-col sm:flex-row gap-3 sm:justify-end flex-shrink-0">
        <template v-if="!result">
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
            <span>คัดลอก</span>
          </button>
        </template>
        <template v-else>
          <button
            type="button"
            @click="() => { emit('copied'); emit('close') }"
            class="min-h-[44px] px-6 py-2.5 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors w-full sm:w-auto text-center"
          >
            เสร็จสิ้น
          </button>
        </template>
      </div>
      
    </div>
  </div>
</template>

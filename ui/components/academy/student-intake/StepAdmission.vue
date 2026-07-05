<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useStudentIntake } from '~/composables/useStudentIntake'

const emit = defineEmits(['next', 'back'])
const route = useRoute()
const academyName = computed(() => route.params.name as string)
const { payload } = useStudentIntake(academyName.value)
const api = useApi()

const academicYears = ref<any[]>([])
const classrooms = ref<any[]>([])
const isNextClicked = ref(false)

const fetchAcademicYears = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyName.value}/academic-years`)
    academicYears.value = res?.academicYears ?? res?.data ?? []
  } catch (error) {
    console.error('Failed to fetch academic years', error)
  }
}

const fetchClassrooms = async (yearId?: number | null) => {
  try {
    const url = yearId 
      ? `/api/academies/${academyName.value}/classrooms?academic_year_id=${yearId}`
      : `/api/academies/${academyName.value}/classrooms`
    const res: any = await api.get(url)
    classrooms.value = res?.classrooms ?? res?.data ?? []
  } catch (error) {
    console.error('Failed to fetch classrooms', error)
  }
}

watch(() => payload.admission.academic_year_id, (newVal) => {
  fetchClassrooms(newVal)
})

onMounted(() => {
  fetchAcademicYears()
  fetchClassrooms(payload.admission.academic_year_id)
})

const isValid = computed(() => {
  return !!payload.admission.academic_year_id &&
         !!payload.admission.classroom_id &&
         !!payload.admission.enrollment_date
})

const handleNext = () => {
  isNextClicked.value = true
  if (isValid.value) {
    emit('next')
  }
}
</script>

<template>
  <div class="py-6 max-w-3xl mx-auto space-y-8">
    <div class="text-center">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white">ข้อมูลการเรียน</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        ระบุข้อมูลปีการศึกษา ห้องเรียน และโรงเรียนเดิม
      </p>
    </div>

    <div class="space-y-6">
      <!-- Current Admission -->
      <div class="bg-gray-50 dark:bg-gray-800/50 p-6 rounded-xl border border-gray-100 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <Icon icon="fluent:building-desktop-24-regular" class="w-5 h-5 text-gray-400" />
          การเข้าเรียน
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              ปีการศึกษา <span class="text-red-500">*</span>
            </label>
            <select
              v-model="payload.admission.academic_year_id"
              class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
              :class="isNextClicked && !payload.admission.academic_year_id ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
            >
              <option :value="null">— กรุณาเลือก —</option>
              <option v-for="year in academicYears" :key="year.id" :value="year.id">{{ year.name || year.year }}</option>
            </select>
            <p v-if="isNextClicked && !payload.admission.academic_year_id" class="text-xs text-red-500">กรุณาเลือกปีการศึกษา</p>
          </div>
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              ห้องเรียน <span class="text-red-500">*</span>
            </label>
            <select
              v-model="payload.admission.classroom_id"
              class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
              :class="isNextClicked && !payload.admission.classroom_id ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
            >
              <option :value="null">— กรุณาเลือก —</option>
              <option v-for="room in classrooms" :key="room.id" :value="room.id">{{ room.display_name || room.name || `${room.grade_level}/${room.section}` }}</option>
            </select>
            <p v-if="isNextClicked && !payload.admission.classroom_id" class="text-xs text-red-500">กรุณาเลือกห้องเรียน</p>
          </div>
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">เลขที่</label>
            <input 
              v-model="payload.admission.student_number"
              type="number" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              วันที่รับเข้าเรียน <span class="text-red-500">*</span>
            </label>
            <input
              v-model="payload.admission.enrollment_date"
              type="date"
              class="w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
              :class="isNextClicked && !payload.admission.enrollment_date ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'"
            />
            <p v-if="isNextClicked && !payload.admission.enrollment_date" class="text-xs text-red-500">กรุณาระบุวันที่รับเข้าเรียน</p>
          </div>
        </div>
      </div>

      <!-- Previous School -->
      <div class="bg-gray-50 dark:bg-gray-800/50 p-6 rounded-xl border border-gray-100 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <Icon icon="fluent:building-24-regular" class="w-5 h-5 text-gray-400" />
          โรงเรียนเดิม
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-1 gap-4">
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ชื่อโรงเรียนเดิม</label>
            <input 
              v-model="payload.previous_school.name"
              type="text" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">จังหวัด</label>
            <input 
              v-model="payload.previous_school.province"
              type="text" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ระดับชั้นที่จบมา</label>
            <input 
              v-model="payload.previous_school.grade_level"
              type="text" 
              placeholder="เช่น ป.6 หรือ ม.3"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
      <button 
        type="button" 
        class="inline-flex items-center gap-2 px-6 py-2.5 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
        @click="emit('back')"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        <span>ย้อนกลับ</span>
      </button>
      <button 
        type="button" 
        class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
        @click="handleNext"
      >
        <span>ถัดไป</span>
        <Icon icon="fluent:arrow-right-24-regular" class="w-5 h-5" />
      </button>
    </div>
  </div>
</template>

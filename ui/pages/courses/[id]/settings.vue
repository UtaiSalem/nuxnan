<script setup lang="ts">
import { Icon } from '@iconify/vue'

// Inject course data from parent
const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')
const refreshCourse = inject<() => void>('refreshCourse')

// State
const isLoading = ref(false)
const isSaving = ref(false)

const api = useApi()

// Form data
const form = ref({
  code: '',
  name: '',
  description: '',
  category: '',
  level: '',
  credit_units: 0,
  hours_per_week: 0,
  start_date: '',
  end_date: '',
  auto_accept_members: false,
  tuition_fees: 0,
  saleable: false,
  price: 0,
  status: 'draft'
})

// Course categories
const courseCategories = [
  'ภาษาไทย',
  'คณิตศาสตร์',
  'วิทยาศาสตร์',
  'สังคมศึกษา ศาสนา และวัฒนธรรม',
  'สุขศึกษาและพลศึกษา',
  'ศิลปะ',
  'การงานอาชีพและเทคโนโลยี',
  'ภาษาต่างประเทศ',
  'อื่นๆ'
]

// Course levels
const courseLevels = [
  'ชั้นประถมศึกษาปีที่ 1',
  'ชั้นประถมศึกษาปีที่ 2',
  'ชั้นประถมศึกษาปีที่ 3',
  'ชั้นประถมศึกษาปีที่ 4',
  'ชั้นประถมศึกษาปีที่ 5',
  'ชั้นประถมศึกษาปีที่ 6',
  'ชั้นมัธยมศึกษาปีที่ 1',
  'ชั้นมัธยมศึกษาปีที่ 2',
  'ชั้นมัธยมศึกษาปีที่ 3',
  'ชั้นมัธยมศึกษาปีที่ 4',
  'ชั้นมัธยมศึกษาปีที่ 5',
  'ชั้นมัธยมศึกษาปีที่ 6',
  'อุดมศึกษา',
  'ทั่วไป'
]

// Initialize form with course data
watch(() => course?.value, (newCourse) => {
  if (newCourse) {
    form.value = {
      code: newCourse.code || '',
      name: newCourse.name || '',
      description: newCourse.description || '',
      category: newCourse.category || '',
      level: newCourse.level || '',
      credit_units: newCourse.credit_units || 0,
      hours_per_week: newCourse.hours_per_week || 0,
      start_date: newCourse.start_date || '',
      end_date: newCourse.end_date || '',
      auto_accept_members: newCourse.setting?.auto_accept_members || false,
      tuition_fees: newCourse.tuition_fees || 0,
      saleable: newCourse.saleable || false,
      price: newCourse.price || 0,
      status: newCourse.status || 'draft'
    }
  }
}, { immediate: true })

// Save settings
const saveSettings = async () => {
  if (!course?.value) return
  
  isSaving.value = true
  try {
    const response = await api.put(`/api/courses/${course.value.id}`, form.value)
    if (response.success) {
      alert('บันทึกการตั้งค่าเรียบร้อยแล้ว')
      if (refreshCourse) refreshCourse()
    }
  } catch (err: any) {
    alert(err.data?.msg || 'ไม่สามารถบันทึกได้')
  } finally {
    isSaving.value = false
  }
}

// Delete course
const deleteCourse = async () => {
  if (!course?.value) return
  if (!confirm(`คุณแน่ใจหรือไม่ที่จะลบรายวิชา "${course.value.name}"? การกระทำนี้ไม่สามารถย้อนกลับได้`)) return
  
  try {
    const response = await api.delete(`/api/courses/${course.value.id}`)
    if (response.success) {
      navigateTo('/courses')
    }
  } catch (err: any) {
    alert(err.data?.msg || 'ไม่สามารถลบรายวิชาได้')
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
      <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <Icon icon="mdi-light:settings" class="w-5 h-5 text-cyan-500" />
        ตั้งค่ารายวิชา
      </h2>
    </div>

    <!-- Settings Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
      <form @submit.prevent="saveSettings" class="space-y-6">
        <!-- Basic Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รหัสวิชา</label>
            <input
              v-model="form.code"
              type="text"
              class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อรายวิชา</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            />
          </div>
        </div>

        <!-- Category & Level -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมวดหมู่</label>
            <select
              v-model="form.category"
              class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            >
              <option value="">เลือกหมวดหมู่</option>
              <option v-for="cat in courseCategories" :key="cat" :value="cat">{{ cat }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ระดับชั้น</label>
            <select
              v-model="form.level"
              class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            >
              <option value="">เลือกระดับชั้น</option>
              <option v-for="level in courseLevels" :key="level" :value="level">{{ level }}</option>
            </select>
          </div>
        </div>

        <!-- Credit & Hours -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หน่วยกิต</label>
            <input
              v-model.number="form.credit_units"
              type="number"
              min="0"
              class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชั่วโมง/สัปดาห์</label>
            <input
              v-model.number="form.hours_per_week"
              type="number"
              min="0"
              class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            />
          </div>
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันเริ่มต้น</label>
            <input
              v-model="form.start_date"
              type="date"
              class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">วันสิ้นสุด</label>
            <input
              v-model="form.end_date"
              type="date"
              class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            />
          </div>
        </div>

        <!-- Toggles -->
        <div class="space-y-4">
          <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div>
              <p class="font-medium text-gray-900 dark:text-white">อนุมัติสมาชิกอัตโนมัติ</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">อนุมัติคำขอเข้าร่วมโดยไม่ต้องยืนยัน</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="form.auto_accept_members" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-cyan-300 dark:peer-focus:ring-cyan-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-cyan-500"></div>
            </label>
          </div>

          <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div>
              <p class="font-medium text-gray-900 dark:text-white">เปิดขายรายวิชา</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">อนุญาตให้ผู้เรียนซื้อรายวิชานี้</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="form.saleable" type="checkbox" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-cyan-300 dark:peer-focus:ring-cyan-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-cyan-500"></div>
            </label>
          </div>
        </div>

        <!-- Price -->
        <div v-if="form.saleable" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ราคา (บาท)</label>
            <input
              v-model.number="form.price"
              type="number"
              min="0"
              class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            />
          </div>
        </div>

        <!-- Status -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
          <div class="flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.status" type="radio" value="draft" class="text-cyan-500 focus:ring-cyan-500">
              <span class="text-gray-700 dark:text-gray-300">ฉบับร่าง</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.status" type="radio" value="published" class="text-cyan-500 focus:ring-cyan-500">
              <span class="text-gray-700 dark:text-gray-300">เผยแพร่</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.status" type="radio" value="archived" class="text-cyan-500 focus:ring-cyan-500">
              <span class="text-gray-700 dark:text-gray-300">เก็บถาวร</span>
            </label>
          </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end">
          <button
            type="submit"
            :disabled="isSaving"
            class="flex items-center gap-2 px-6 py-2 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition-colors disabled:opacity-50"
          >
            <Icon v-if="isSaving" icon="svg-spinners:ring-resize" class="w-5 h-5" />
            <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5" />
            บันทึก
          </button>
        </div>
      </form>
    </div>

    <!-- Danger Zone -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-red-200 dark:border-red-900 p-6">
      <h3 class="text-lg font-bold text-red-600 dark:text-red-400 mb-4 flex items-center gap-2">
        <Icon icon="fluent:warning-24-regular" class="w-5 h-5" />
        โซนอันตราย
      </h3>
      <p class="text-gray-600 dark:text-gray-400 mb-4">การลบรายวิชาจะลบข้อมูลทั้งหมด รวมถึงบทเรียน, ภาระงาน, แบบทดสอบ และสมาชิก</p>
      <button
        @click="deleteCourse"
        class="flex items-center gap-2 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
      >
        <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
        ลบรายวิชา
      </button>
    </div>
  </div>
</template>

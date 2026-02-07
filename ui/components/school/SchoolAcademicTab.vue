<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ระบบวิชาการ</h2>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="section in sections"
          :key="section.id"
          @click="activeSection = section.id"
          :class="[
            activeSection === section.id
              ? 'bg-primary-500 text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300',
            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors'
          ]"
        >
          {{ section.name }}
        </button>
      </div>
    </div>

    <!-- Classrooms Section -->
    <div v-if="activeSection === 'classrooms'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">ห้องเรียน</h3>
        <button
          @click="showClassroomModal = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
        >
          <Icon icon="heroicons:plus" class="h-5 w-5" />
          <span class="hidden sm:inline">เพิ่มห้องเรียน</span>
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loadingClassrooms" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <!-- Classrooms Grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="classroom in classrooms"
          :key="classroom.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between">
            <div>
              <h4 class="font-semibold text-gray-900 dark:text-white">{{ classroom.name }}</h4>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ classroom.grade_level }} - {{ classroom.section }}</p>
            </div>
            <span :class="classroom.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 py-1 text-xs rounded-full">
              {{ classroom.is_active ? 'ใช้งาน' : 'ปิด' }}
            </span>
          </div>
          <div class="mt-3 space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <p class="flex items-center gap-2">
              <Icon icon="heroicons:map-pin" class="h-4 w-4" />
              {{ classroom.room_location }}
            </p>
            <p class="flex items-center gap-2">
              <Icon icon="heroicons:users" class="h-4 w-4" />
              ความจุ: {{ classroom.capacity }} คน
            </p>
          </div>
          <div class="mt-4 flex gap-2">
            <button @click="editClassroom(classroom)" class="flex-1 py-1.5 text-sm text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors">
              แก้ไข
            </button>
            <button @click="confirmDeleteClassroom(classroom)" class="flex-1 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
              ลบ
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="classrooms.length === 0" class="col-span-full text-center py-12 text-gray-500">
          <Icon icon="heroicons:building-office" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>ยังไม่มีห้องเรียน</p>
        </div>
      </div>
    </div>

    <!-- Subjects Section -->
    <div v-if="activeSection === 'subjects'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">รายวิชา</h3>
        <button
          @click="showSubjectModal = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
        >
          <Icon icon="heroicons:plus" class="h-5 w-5" />
          <span class="hidden sm:inline">เพิ่มรายวิชา</span>
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loadingSubjects" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>

      <!-- Subjects Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">รหัส</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">ชื่อวิชา</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300 hidden sm:table-cell">หน่วยกิต</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300 hidden md:table-cell">ชม./สัปดาห์</th>
              <th class="px-4 py-3 text-left font-medium text-gray-600 dark:text-gray-300">สถานะ</th>
              <th class="px-4 py-3 text-right font-medium text-gray-600 dark:text-gray-300">จัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="subject in subjects" :key="subject.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
              <td class="px-4 py-3 font-mono text-gray-900 dark:text-white">{{ subject.subject_code }}</td>
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900 dark:text-white">{{ subject.name_th }}</div>
                <div class="text-xs text-gray-500">{{ subject.name_en }}</div>
              </td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-400 hidden sm:table-cell">{{ subject.credits }}</td>
              <td class="px-4 py-3 text-gray-600 dark:text-gray-400 hidden md:table-cell">{{ subject.hours_per_week }}</td>
              <td class="px-4 py-3">
                <span :class="subject.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'" class="px-2 py-1 text-xs rounded-full">
                  {{ subject.is_active ? 'ใช้งาน' : 'ปิด' }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <button @click="editSubject(subject)" class="text-primary-600 hover:underline mr-2">แก้ไข</button>
                <button @click="confirmDeleteSubject(subject)" class="text-red-600 hover:underline">ลบ</button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Empty State -->
        <div v-if="subjects.length === 0" class="text-center py-12 text-gray-500">
          <Icon icon="heroicons:book-open" class="h-12 w-12 mx-auto mb-3 text-gray-300" />
          <p>ยังไม่มีรายวิชา</p>
        </div>
      </div>
    </div>

    <!-- Schedules Section -->
    <div v-if="activeSection === 'schedules'" class="space-y-4">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">ตารางเรียน</h3>
        <button
          @click="showScheduleModal = true"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
        >
          <Icon icon="heroicons:plus" class="h-5 w-5" />
          <span class="hidden sm:inline">เพิ่มตารางเรียน</span>
        </button>
      </div>

      <!-- Schedule Grid by Day -->
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
        <div
          v-for="day in weekDays"
          :key="day.value"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
          <div class="bg-primary-500 text-white px-4 py-2 font-medium text-center">
            {{ day.label }}
          </div>
          <div class="p-2 space-y-2 min-h-[200px]">
            <div
              v-for="schedule in getSchedulesByDay(day.value)"
              :key="schedule.id"
              class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-2 text-sm"
            >
              <div class="font-medium text-gray-900 dark:text-white">{{ schedule.start_time }} - {{ schedule.end_time }}</div>
              <div class="text-gray-600 dark:text-gray-400">{{ schedule.room }}</div>
              <div class="text-xs text-gray-500 mt-1">คาบที่ {{ schedule.period_number }}</div>
            </div>
            <div v-if="getSchedulesByDay(day.value).length === 0" class="text-center text-gray-400 text-sm py-4">
              ว่าง
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Classroom Modal -->
    <SchoolModal v-model="showClassroomModal" :title="editingClassroom ? 'แก้ไขห้องเรียน' : 'เพิ่มห้องเรียน'">
      <form @submit.prevent="saveClassroom" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อห้อง</label>
          <input v-model="classroomForm.name" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระดับชั้น</label>
            <input v-model="classroomForm.grade_level" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ห้อง</label>
            <input v-model="classroomForm.section" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ที่ตั้ง</label>
          <input v-model="classroomForm.room_location" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความจุ</label>
          <input v-model.number="classroomForm.capacity" type="number" min="1" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="showClassroomModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
            ยกเลิก
          </button>
          <button type="submit" :disabled="savingClassroom" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50">
            {{ savingClassroom ? 'กำลังบันทึก...' : 'บันทึก' }}
          </button>
        </div>
      </form>
    </SchoolModal>

    <!-- Subject Modal -->
    <SchoolModal v-model="showSubjectModal" :title="editingSubject ? 'แก้ไขรายวิชา' : 'เพิ่มรายวิชา'">
      <form @submit.prevent="saveSubject" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รหัสวิชา</label>
          <input v-model="subjectForm.subject_code" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อวิชา (ไทย)</label>
          <input v-model="subjectForm.name_th" type="text" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อวิชา (อังกฤษ)</label>
          <input v-model="subjectForm.name_en" type="text" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หน่วยกิต</label>
            <input v-model.number="subjectForm.credits" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชม./สัปดาห์</label>
            <input v-model.number="subjectForm.hours_per_week" type="number" min="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white" />
          </div>
        </div>
        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="showSubjectModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
            ยกเลิก
          </button>
          <button type="submit" :disabled="savingSubject" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50">
            {{ savingSubject ? 'กำลังบันทึก...' : 'บันทึก' }}
          </button>
        </div>
      </form>
    </SchoolModal>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  academyId: number
}>()

const schoolApi = useSchoolManagement()

// State
const activeSection = ref('classrooms')
const sections = [
  { id: 'classrooms', name: 'ห้องเรียน' },
  { id: 'subjects', name: 'รายวิชา' },
  { id: 'schedules', name: 'ตารางเรียน' },
]

const weekDays = [
  { value: 1, label: 'จันทร์' },
  { value: 2, label: 'อังคาร' },
  { value: 3, label: 'พุธ' },
  { value: 4, label: 'พฤหัสบดี' },
  { value: 5, label: 'ศุกร์' },
]

// Classrooms
const classrooms = ref<any[]>([])
const loadingClassrooms = ref(false)
const showClassroomModal = ref(false)
const savingClassroom = ref(false)
const editingClassroom = ref<any>(null)
const classroomForm = ref({
  name: '',
  grade_level: '',
  section: '',
  room_location: '',
  capacity: 40,
})

// Subjects
const subjects = ref<any[]>([])
const loadingSubjects = ref(false)
const showSubjectModal = ref(false)
const savingSubject = ref(false)
const editingSubject = ref<any>(null)
const subjectForm = ref({
  subject_code: '',
  name_th: '',
  name_en: '',
  credits: 2,
  hours_per_week: 4,
})

// Schedules
const schedules = ref<any[]>([])
const showScheduleModal = ref(false)

// Load data
const loadClassrooms = async () => {
  loadingClassrooms.value = true
  try {
    classrooms.value = await schoolApi.getClassrooms(props.academyId)
  } catch (error) {
    console.error('Failed to load classrooms:', error)
    classrooms.value = []
  } finally {
    loadingClassrooms.value = false
  }
}

const loadSubjects = async () => {
  loadingSubjects.value = true
  try {
    subjects.value = await schoolApi.getSubjects(props.academyId)
  } catch (error) {
    console.error('Failed to load subjects:', error)
    subjects.value = []
  } finally {
    loadingSubjects.value = false
  }
}

const loadSchedules = async () => {
  try {
    const response = await schoolApi.getSchedules(props.academyId)
    schedules.value = Array.isArray(response) ? response : (response?.data || [])
  } catch (error) {
    console.error('Failed to load schedules:', error)
    schedules.value = []
  }
}

const getSchedulesByDay = (dayOfWeek: number) => {
  return schedules.value.filter(s => s.day_of_week === dayOfWeek).sort((a, b) => a.start_time.localeCompare(b.start_time))
}

// Classroom CRUD
const editClassroom = (classroom: any) => {
  editingClassroom.value = classroom
  classroomForm.value = { ...classroom }
  showClassroomModal.value = true
}

const saveClassroom = async () => {
  savingClassroom.value = true
  try {
    if (editingClassroom.value) {
      await schoolApi.updateClassroom(props.academyId, editingClassroom.value.id, classroomForm.value)
    } else {
      await schoolApi.createClassroom(props.academyId, classroomForm.value)
    }
    showClassroomModal.value = false
    editingClassroom.value = null
    classroomForm.value = { name: '', grade_level: '', section: '', room_location: '', capacity: 40 }
    await loadClassrooms()
  } catch (error) {
    console.error('Failed to save classroom:', error)
  } finally {
    savingClassroom.value = false
  }
}

const confirmDeleteClassroom = async (classroom: any) => {
  if (confirm(`ต้องการลบห้องเรียน "${classroom.name}" หรือไม่?`)) {
    try {
      await schoolApi.deleteClassroom(props.academyId, classroom.id)
      await loadClassrooms()
    } catch (error) {
      console.error('Failed to delete classroom:', error)
    }
  }
}

// Subject CRUD
const editSubject = (subject: any) => {
  editingSubject.value = subject
  subjectForm.value = { ...subject }
  showSubjectModal.value = true
}

const saveSubject = async () => {
  savingSubject.value = true
  try {
    if (editingSubject.value) {
      await schoolApi.updateSubject(props.academyId, editingSubject.value.id, subjectForm.value)
    } else {
      await schoolApi.createSubject(props.academyId, subjectForm.value)
    }
    showSubjectModal.value = false
    editingSubject.value = null
    subjectForm.value = { subject_code: '', name_th: '', name_en: '', credits: 2, hours_per_week: 4 }
    await loadSubjects()
  } catch (error) {
    console.error('Failed to save subject:', error)
  } finally {
    savingSubject.value = false
  }
}

const confirmDeleteSubject = async (subject: any) => {
  if (confirm(`ต้องการลบรายวิชา "${subject.name_th}" หรือไม่?`)) {
    try {
      await schoolApi.deleteSubject(props.academyId, subject.id)
      await loadSubjects()
    } catch (error) {
      console.error('Failed to delete subject:', error)
    }
  }
}

// Watch section change and load data
watch(activeSection, (section) => {
  if (section === 'classrooms' && classrooms.value.length === 0) loadClassrooms()
  if (section === 'subjects' && subjects.value.length === 0) loadSubjects()
  if (section === 'schedules' && schedules.value.length === 0) loadSchedules()
}, { immediate: true })

// Reset form when modal closes
watch(showClassroomModal, (isOpen) => {
  if (!isOpen) {
    editingClassroom.value = null
    classroomForm.value = { name: '', grade_level: '', section: '', room_location: '', capacity: 40 }
  }
})

watch(showSubjectModal, (isOpen) => {
  if (!isOpen) {
    editingSubject.value = null
    subjectForm.value = { subject_code: '', name_th: '', name_en: '', credits: 2, hours_per_week: 4 }
  }
})

onMounted(() => {
  loadClassrooms()
})
</script>

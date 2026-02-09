<script setup lang="ts">
/**
 * Academy Admin - Create Home Visit
 * หน้าสร้างรายการเยี่ยมบ้านใหม่
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const zones = ref<any[]>([])
const students = ref<any[]>([])
const isLoading = ref(true)
const isSaving = ref(false)
const errors = ref<Record<string, string[]>>({})

// Form
const form = ref({
  student_id: '',
  zone_id: '',
  visit_date: new Date().toISOString().split('T')[0],
  visit_time: '',
  purpose: '',
  address: '',
  observations: '',
  recommendations: '',
  status: 'pending'
})

// Student search
const studentSearch = ref('')
const showStudentDropdown = ref(false)

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value && !can('home_visits.manage')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await Promise.all([fetchZones(), fetchStudents()])
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchZones = async () => {
  try {
    const response: any = await api.get('/api/home-visit/zones')
    zones.value = response.zones || response.data || []
  } catch (err) {
    console.error('Failed to fetch zones:', err)
  }
}

const fetchStudents = async () => {
  try {
    const response: any = await api.get('/api/home-visit/students')
    students.value = response.students || response.data || []
  } catch (err) {
    console.error('Failed to fetch students:', err)
  }
}

const filteredStudents = computed(() => {
  if (!studentSearch.value) return students.value.slice(0, 20)
  const search = studentSearch.value.toLowerCase()
  return students.value.filter(s => 
    s.full_name?.toLowerCase().includes(search) ||
    s.first_name_thai?.toLowerCase().includes(search) ||
    s.last_name_thai?.toLowerCase().includes(search) ||
    s.student_number?.includes(search)
  ).slice(0, 20)
})

const selectedStudent = computed(() => {
  return students.value.find(s => s.id == form.value.student_id)
})

const selectStudent = (student: any) => {
  form.value.student_id = student.id
  studentSearch.value = ''
  showStudentDropdown.value = false
  
  // Pre-fill address if available
  if (student.address && !form.value.address) {
    form.value.address = student.address
  }
  // Pre-fill zone if available
  if (student.zone_id && !form.value.zone_id) {
    form.value.zone_id = student.zone_id
  }
}

const clearStudent = () => {
  form.value.student_id = ''
}

const validateForm = () => {
  errors.value = {}
  
  if (!form.value.student_id) {
    errors.value.student_id = ['กรุณาเลือกนักเรียน']
  }
  if (!form.value.visit_date) {
    errors.value.visit_date = ['กรุณาระบุวันที่เยี่ยมบ้าน']
  }
  
  return Object.keys(errors.value).length === 0
}

const saveVisit = async () => {
  if (!validateForm()) return
  
  isSaving.value = true
  errors.value = {}
  
  try {
    await api.post('/api/home-visit/admin/visits', form.value)
    navigateTo(`/academies/${academyName.value}/admin/home-visits`)
  } catch (err: any) {
    if (err.errors) {
      errors.value = err.errors
    }
    console.error('Failed to save:', err)
  } finally {
    isSaving.value = false
  }
}

const purposes = [
  'เยี่ยมบ้านตามกำหนด',
  'ติดตามนักเรียนขาดเรียน',
  'ปัญหาพฤติกรรม',
  'ปัญหาการเรียน',
  'เยี่ยมนักเรียนป่วย',
  'ประสานงานผู้ปกครอง',
  'อื่นๆ'
]
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-12">
      <Icon name="fluent:spinner-ios-20-regular" class="w-8 h-8 animate-spin text-primary-600" />
    </div>

    <div v-else class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <NuxtLink 
          :to="`/academies/${academyName}/admin/home-visits`"
          class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
        >
          <Icon name="fluent:arrow-left-24-regular" class="w-5 h-5" />
        </NuxtLink>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">บันทึกการเยี่ยมบ้านใหม่</h1>
          <p class="text-gray-600 dark:text-gray-400">สร้างรายการเยี่ยมบ้านนักเรียน</p>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="saveVisit" class="space-y-6">
        <!-- Student Selection -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <Icon name="fluent:person-24-regular" class="w-5 h-5 text-primary-600" />
            เลือกนักเรียน
          </h2>
          
          <div v-if="!selectedStudent" class="relative">
            <div class="relative">
              <Icon name="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="studentSearch"
                @focus="showStudentDropdown = true"
                type="text"
                placeholder="ค้นหาชื่อหรือรหัสนักเรียน..."
                class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                :class="errors.student_id ? 'border-red-500' : ''"
              />
            </div>
            
            <!-- Dropdown -->
            <div
              v-if="showStudentDropdown && filteredStudents.length > 0"
              class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto"
            >
              <button
                v-for="student in filteredStudents"
                :key="student.id"
                type="button"
                @click="selectStudent(student)"
                class="w-full px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-600 flex items-center gap-3 border-b border-gray-100 dark:border-gray-600 last:border-b-0"
              >
                <img :src="student.avatar || '/images/default-avatar.png'" class="w-10 h-10 rounded-full object-cover" />
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900 dark:text-white truncate">
                    {{ student.full_name || `${student.first_name_thai} ${student.last_name_thai}` }}
                  </p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ student.student_number }} • ม.{{ student.class_level }}/{{ student.class_section }}
                  </p>
                </div>
              </button>
            </div>
            
            <p v-if="errors.student_id" class="text-red-500 text-sm mt-1">{{ errors.student_id[0] }}</p>
          </div>
          
          <!-- Selected Student -->
          <div v-else class="flex items-center gap-4 p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
            <img :src="selectedStudent.avatar || '/images/default-avatar.png'" class="w-14 h-14 rounded-full object-cover" />
            <div class="flex-1">
              <p class="font-medium text-gray-900 dark:text-white">
                {{ selectedStudent.full_name || `${selectedStudent.first_name_thai} ${selectedStudent.last_name_thai}` }}
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ selectedStudent.student_number }} • ม.{{ selectedStudent.class_level }}/{{ selectedStudent.class_section }}
              </p>
            </div>
            <button type="button" @click="clearStudent" class="p-2 text-gray-400 hover:text-red-500">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Visit Details -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <Icon name="fluent:calendar-24-regular" class="w-5 h-5 text-primary-600" />
            รายละเอียดการเยี่ยมบ้าน
          </h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                วันที่เยี่ยมบ้าน <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.visit_date"
                type="date"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                :class="errors.visit_date ? 'border-red-500' : ''"
              />
              <p v-if="errors.visit_date" class="text-red-500 text-sm mt-1">{{ errors.visit_date[0] }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เวลา</label>
              <input
                v-model="form.visit_time"
                type="time"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โซน</label>
              <select
                v-model="form.zone_id"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">เลือกโซน</option>
                <option v-for="zone in zones" :key="zone.id" :value="zone.id">
                  {{ zone.name }}
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วัตถุประสงค์</label>
              <select
                v-model="form.purpose"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">เลือกวัตถุประสงค์</option>
                <option v-for="purpose in purposes" :key="purpose" :value="purpose">
                  {{ purpose }}
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
              <select
                v-model="form.status"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="pending">รอดำเนินการ</option>
                <option value="completed">เสร็จสิ้น</option>
                <option value="cancelled">ยกเลิก</option>
              </select>
            </div>
          </div>
          
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ที่อยู่บ้าน</label>
            <textarea
              v-model="form.address"
              rows="2"
              placeholder="ที่อยู่บ้านนักเรียน"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            ></textarea>
          </div>
        </div>

        <!-- Observations -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <Icon name="fluent:notepad-24-regular" class="w-5 h-5 text-primary-600" />
            บันทึกการเยี่ยมบ้าน
          </h2>
          
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                สิ่งที่พบ / ข้อสังเกต
              </label>
              <textarea
                v-model="form.observations"
                rows="4"
                placeholder="บันทึกสิ่งที่พบเห็นจากการเยี่ยมบ้าน..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              ></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                ข้อเสนอแนะ
              </label>
              <textarea
                v-model="form.recommendations"
                rows="3"
                placeholder="ข้อเสนอแนะสำหรับการดูแลนักเรียน..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
          <NuxtLink
            :to="`/academies/${academyName}/admin/home-visits`"
            class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            ยกเลิก
          </NuxtLink>
          <button
            type="submit"
            :disabled="isSaving"
            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 flex items-center gap-2"
          >
            <Icon v-if="isSaving" name="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            <Icon v-else name="fluent:save-24-regular" class="w-5 h-5" />
            <span>{{ isSaving ? 'กำลังบันทึก...' : 'บันทึก' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

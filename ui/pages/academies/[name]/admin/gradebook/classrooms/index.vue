<script setup lang="ts">
/**
 * Academy Classrooms Management
 * หน้าจัดการห้องเรียน
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
const academyId = ref<number | null>(null)
const isLoading = ref(true)
const isSaving = ref(false)

// Data
const classrooms = ref<any[]>([])
const academicYears = ref<any[]>([])
const teachers = ref<any[]>([])

// Filters
const selectedAcademicYear = ref<number | null>(null)
const selectedGradeLevel = ref<string>('')
const searchQuery = ref('')

// Modal
const showModal = ref(false)
const editingClassroom = ref<any>(null)
const form = ref({
  academic_year_id: null as number | null,
  grade_level: '',
  section: '',
  name: '',
  homeroom_teacher_id: null as number | null,
  room_location: '',
  capacity: 50,
})

const filteredClassrooms = computed(() => {
  let result = classrooms.value

  if (selectedGradeLevel.value) {
    result = result.filter((c: any) => c.grade_level === selectedGradeLevel.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter((c: any) => 
      c.name?.toLowerCase().includes(query) ||
      c.grade_level?.toLowerCase().includes(query) ||
      c.section?.toLowerCase().includes(query)
    )
  }

  return result
})

const gradeLevels = computed(() => {
  const levels = [...new Set(classrooms.value.map((c: any) => c.grade_level))]
  return levels.sort()
})

const { isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchData = async () => {
  await Promise.all([
    fetchClassrooms(),
    fetchAcademicYears(),
    fetchTeachers(),
  ])
}

const fetchClassrooms = async () => {
  try {
    const params = new URLSearchParams()
    if (selectedAcademicYear.value) {
      params.append('academic_year_id', selectedAcademicYear.value.toString())
    }
    
    const res: any = await api.get(`/api/academies/${academyId.value}/classrooms?${params}`)
    if (res.success) {
      classrooms.value = res.classrooms || []
    }
  } catch (err) {
    console.error('Failed to fetch classrooms:', err)
  }
}

const fetchAcademicYears = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/academic-years`)
    if (res.success) {
      academicYears.value = res.academicYears || []
      const current = academicYears.value.find((y: any) => y.is_current)
      if (current) {
        selectedAcademicYear.value = current.id
        form.value.academic_year_id = current.id
      }
    }
  } catch (err) {
    console.error('Failed to fetch academic years:', err)
  }
}

const fetchTeachers = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/members?role=teacher`)
    if (res.success) {
      teachers.value = res.members || []
    }
  } catch (err) {
    console.error('Failed to fetch teachers:', err)
  }
}

watch(selectedAcademicYear, () => {
  form.value.academic_year_id = selectedAcademicYear.value
  fetchClassrooms()
})

const openCreateModal = () => {
  editingClassroom.value = null
  form.value = {
    academic_year_id: selectedAcademicYear.value,
    grade_level: '',
    section: '',
    name: '',
    homeroom_teacher_id: null,
    room_location: '',
    capacity: 50,
  }
  showModal.value = true
}

const openEditModal = (classroom: any) => {
  editingClassroom.value = classroom
  form.value = {
    academic_year_id: classroom.academic_year_id,
    grade_level: classroom.grade_level,
    section: classroom.section,
    name: classroom.name,
    homeroom_teacher_id: classroom.homeroom_teacher_id,
    room_location: classroom.room_location || '',
    capacity: classroom.capacity || 50,
  }
  showModal.value = true
}

const saveClassroom = async () => {
  if (!form.value.grade_level || !form.value.section || !form.value.academic_year_id) {
    alert('กรุณากรอกข้อมูลที่จำเป็น')
    return
  }

  isSaving.value = true
  try {
    if (editingClassroom.value) {
      await api.put(`/api/academies/${academyId.value}/classrooms/${editingClassroom.value.id}`, form.value)
    } else {
      await api.post(`/api/academies/${academyId.value}/classrooms`, form.value)
    }
    
    showModal.value = false
    await fetchClassrooms()
  } catch (err: any) {
    console.error('Failed to save:', err)
    alert(err.message || 'เกิดข้อผิดพลาด')
  } finally {
    isSaving.value = false
  }
}

const deleteClassroom = async (classroom: any) => {
  if (!confirm(`ต้องการลบห้องเรียน "${classroom.name}" หรือไม่?`)) return
  
  try {
    await api.delete(`/api/academies/${academyId.value}/classrooms/${classroom.id}`)
    await fetchClassrooms()
  } catch (err) {
    console.error('Failed to delete:', err)
    alert('ไม่สามารถลบได้')
  }
}

// Auto-generate name
watch([() => form.value.grade_level, () => form.value.section], () => {
  if (form.value.grade_level && form.value.section && !editingClassroom.value) {
    form.value.name = `${form.value.grade_level}/${form.value.section}`
  }
})

// Common grade levels in Thai schools
const commonGradeLevels = [
  'ป.1', 'ป.2', 'ป.3', 'ป.4', 'ป.5', 'ป.6',
  'ม.1', 'ม.2', 'ม.3', 'ม.4', 'ม.5', 'ม.6',
  'อ.1', 'อ.2', 'อ.3',
]
</script>

<template>
  <NuxtLayout name="academy-admin" :academy-name="academyName">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <NuxtLink :to="`/academies/${academyName}/admin/gradebook`" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
              <Icon icon="fluent:arrow-left-24-filled" class="w-5 h-5" />
            </NuxtLink>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
              <Icon icon="fluent:building-24-filled" class="w-7 h-7 text-purple-500" />
              จัดการห้องเรียน
            </h1>
          </div>
          <p class="text-gray-600 dark:text-gray-400">สร้างและจัดการห้องเรียนในโรงเรียน</p>
        </div>

        <button
          @click="openCreateModal"
          class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
        >
          <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
          เพิ่มห้องเรียน
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <div class="relative">
              <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="ค้นหาห้องเรียน..."
                class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
          </div>
          
          <select
            v-model="selectedAcademicYear"
            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          >
            <option :value="null">ทุกปีการศึกษา</option>
            <option v-for="year in academicYears" :key="year.id" :value="year.id">
              {{ year.name }} {{ year.is_current ? '(ปัจจุบัน)' : '' }}
            </option>
          </select>
          
          <select
            v-model="selectedGradeLevel"
            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          >
            <option value="">ทุกระดับชั้น</option>
            <option v-for="level in gradeLevels" :key="level" :value="level">{{ level }}</option>
          </select>
        </div>
      </div>

      <!-- Classrooms Grid -->
      <div v-if="filteredClassrooms.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-sm border border-gray-200 dark:border-gray-700">
        <Icon icon="fluent:building-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีห้องเรียน</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นด้วยการสร้างห้องเรียนใหม่</p>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors"
        >
          <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
          สร้างห้องเรียน
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="classroom in filteredClassrooms"
          :key="classroom.id"
          class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                <span class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ classroom.section }}</span>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ classroom.name }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">ระดับชั้น {{ classroom.grade_level }}</p>
              </div>
            </div>
            
            <div class="flex items-center gap-1">
              <button
                @click="openEditModal(classroom)"
                class="p-2 text-gray-500 hover:text-primary-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
              >
                <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
              </button>
              <button
                @click="deleteClassroom(classroom)"
                class="p-2 text-gray-500 hover:text-red-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
              >
                <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
              </button>
            </div>
          </div>

          <div class="space-y-2 text-sm">
            <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
              <Icon icon="fluent:people-24-filled" class="w-4 h-4" />
              <span>{{ classroom.active_students_count || 0 }} นักเรียน</span>
              <span v-if="classroom.capacity" class="text-gray-400">/ {{ classroom.capacity }}</span>
            </div>
            
            <div v-if="classroom.homeroom_teacher" class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
              <Icon icon="fluent:person-24-filled" class="w-4 h-4" />
              <span>ครูประจำชั้น: {{ classroom.homeroom_teacher.name }}</span>
            </div>
            
            <div v-if="classroom.room_location" class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
              <Icon icon="fluent:location-24-filled" class="w-4 h-4" />
              <span>{{ classroom.room_location }}</span>
            </div>
          </div>

          <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-2">
            <NuxtLink
              :to="`/academies/${academyName}/admin/gradebook/classrooms/${classroom.id}`"
              class="flex-1 text-center py-2 text-primary-600 dark:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg text-sm font-medium transition-colors"
            >
              จัดการนักเรียน
            </NuxtLink>
            <NuxtLink
              :to="`/academies/${academyName}/admin/gradebook/classrooms/${classroom.id}/grades`"
              class="flex-1 text-center py-2 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg text-sm font-medium transition-colors"
            >
              ดูผลการเรียน
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="showModal = false"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              {{ editingClassroom ? 'แก้ไขห้องเรียน' : 'เพิ่มห้องเรียนใหม่' }}
            </h2>
          </div>
          
          <form @submit.prevent="saveClassroom" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ปีการศึกษา *</label>
              <select
                v-model="form.academic_year_id"
                required
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              >
                <option v-for="year in academicYears" :key="year.id" :value="year.id">
                  {{ year.name }} {{ year.is_current ? '(ปัจจุบัน)' : '' }}
                </option>
              </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ระดับชั้น *</label>
                <select
                  v-model="form.grade_level"
                  required
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  <option value="">เลือกระดับชั้น</option>
                  <option v-for="level in commonGradeLevels" :key="level" :value="level">{{ level }}</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ห้อง *</label>
                <input
                  v-model="form.section"
                  type="text"
                  required
                  placeholder="เช่น 1, 2, 3..."
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อห้องเรียน</label>
              <input
                v-model="form.name"
                type="text"
                placeholder="เช่น ป.1/1"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ครูประจำชั้น</label>
              <select
                v-model="form.homeroom_teacher_id"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              >
                <option :value="null">ไม่ระบุ</option>
                <option v-for="teacher in teachers" :key="teacher.user_id" :value="teacher.user_id">
                  {{ teacher.user?.name || teacher.name }}
                </option>
              </select>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ห้องเรียน</label>
                <input
                  v-model="form.room_location"
                  type="text"
                  placeholder="เช่น อาคาร 1 ชั้น 2"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความจุ</label>
                <input
                  v-model.number="form.capacity"
                  type="number"
                  min="1"
                  max="100"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="showModal = false"
                class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                ยกเลิก
              </button>
              <button
                type="submit"
                :disabled="isSaving"
                class="flex-1 px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
              >
                <Icon v-if="isSaving" icon="fluent:spinner-ios-20-filled" class="w-5 h-5 animate-spin" />
                {{ isSaving ? 'กำลังบันทึก...' : (editingClassroom ? 'บันทึก' : 'สร้างห้องเรียน') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </NuxtLayout>
</template>

<script setup lang="ts">
/**
 * Academy Admin - Curriculum Management
 * หน้าจัดการหลักสูตรของโรงเรียน
 */
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const curriculums = ref<any[]>([])
const statistics = ref<any>(null)
const isLoading = ref(true)
const isLoadingCurriculums = ref(false)

// Filters
const searchQuery = ref('')
const selectedAcademicYear = ref<string>('')
const selectedLevel = ref<string | null>(null)

// Pagination
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0
})

// Modals
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showCoursesModal = ref(false)
const showStudentsModal = ref(false)
const showAddCoursesModal = ref(false)
const selectedCurriculum = ref<any>(null)

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Form
const curriculumForm = ref({
  name: '',
  code: '',
  description: '',
  level: '',
  academic_year: '',
  duration_years: 1,
  total_credits: 0,
  required_credits: 0,
  elective_credits: 0,
  is_active: true
})
const isSubmitting = ref(false)
const formErrors = ref<Record<string, string[]>>({})

// Courses Management
const curriculumCourses = ref<any[]>([])
const availableCourses = ref<any[]>([])
const isLoadingCourses = ref(false)
const selectedCourseIds = ref<number[]>([])
const courseSearchQuery = ref('')
const courseForm = ref({
  course_type: 'required',
  semester: '1',
  year_level: 1,
  credits: 0
})

// Students Management
const curriculumStudents = ref<any[]>([])
const isLoadingStudents = ref(false)
const studentSearchQuery = ref('')

// Academic Years
const currentYear = new Date().getFullYear() + 543
const academicYears = computed(() => {
  const years = []
  for (let i = currentYear + 1; i >= currentYear - 5; i--) {
    years.push(i.toString())
  }
  return years
})

// Levels
const levels = ['อนุบาล', 'ประถมศึกษา', 'มัธยมศึกษาตอนต้น', 'มัธยมศึกษาตอนปลาย', 'ปริญญาตรี', 'ปริญญาโท', 'ปริญญาเอก']

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      selectedAcademicYear.value = currentYear.toString()
      await fetchMyRole()
      
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }
      
      await Promise.all([
        fetchCurriculums(),
        fetchStatistics()
      ])
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

// Fetch curriculums
const fetchCurriculums = async () => {
  if (!academyId.value) return
  
  isLoadingCurriculums.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/curriculums`, {
      search: searchQuery.value || undefined,
      academic_year: selectedAcademicYear.value || undefined,
      level: selectedLevel.value || undefined,
      page: pagination.value.current_page,
      per_page: pagination.value.per_page
    })
    
    if (response.success) {
      curriculums.value = response.curriculums || []
      if (response.pagination) {
        pagination.value = response.pagination
      }
    }
  } catch (err) {
    console.error('Failed to fetch curriculums:', err)
  } finally {
    isLoadingCurriculums.value = false
  }
}

// Fetch statistics
const fetchStatistics = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/curriculums/statistics`)
    if (response.success) {
      statistics.value = response.statistics
    }
  } catch (err) {
    console.error('Failed to fetch statistics:', err)
  }
}

// Search with debounce
let searchTimeout: NodeJS.Timeout
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1
    fetchCurriculums()
  }, 300)
}

// Filter change
const handleFilterChange = () => {
  pagination.value.current_page = 1
  fetchCurriculums()
}

// Open create modal
const openCreateModal = () => {
  curriculumForm.value = {
    name: '',
    code: '',
    description: '',
    level: '',
    academic_year: selectedAcademicYear.value || currentYear.toString(),
    duration_years: 1,
    total_credits: 0,
    required_credits: 0,
    elective_credits: 0,
    is_active: true
  }
  formErrors.value = {}
  showCreateModal.value = true
}

// Open edit modal
const openEditModal = (curriculum: any) => {
  selectedCurriculum.value = curriculum
  curriculumForm.value = {
    name: curriculum.name,
    code: curriculum.code || '',
    description: curriculum.description || '',
    level: curriculum.level || '',
    academic_year: curriculum.academic_year || '',
    duration_years: curriculum.duration_years || 1,
    total_credits: curriculum.total_credits || 0,
    required_credits: curriculum.required_credits || 0,
    elective_credits: curriculum.elective_credits || 0,
    is_active: curriculum.is_active
  }
  formErrors.value = {}
  showEditModal.value = true
}

// Create curriculum
const createCurriculum = async () => {
  if (!academyId.value) return
  
  isSubmitting.value = true
  formErrors.value = {}
  
  try {
    const response: any = await api.post(`/api/academies/${academyId.value}/curriculums`, curriculumForm.value)
    
    if (response.success) {
      showCreateModal.value = false
      await fetchCurriculums()
      await fetchStatistics()
      
      Swal.fire({
        icon: 'success',
        title: 'สร้างหลักสูตรสำเร็จ',
        text: `สร้างหลักสูตร "${curriculumForm.value.name}" เรียบร้อยแล้ว`,
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถสร้างหลักสูตรได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Update curriculum
const updateCurriculum = async () => {
  if (!selectedCurriculum.value) return
  
  isSubmitting.value = true
  formErrors.value = {}
  
  try {
    const response: any = await api.patch(`/api/academies/curriculums/${selectedCurriculum.value.id}`, curriculumForm.value)
    
    if (response.success) {
      showEditModal.value = false
      await fetchCurriculums()
      
      Swal.fire({
        icon: 'success',
        title: 'อัปเดตสำเร็จ',
        text: 'อัปเดตข้อมูลหลักสูตรเรียบร้อยแล้ว',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถอัปเดตหลักสูตรได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Delete curriculum
const deleteCurriculum = async (curriculum: any) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันการลบ',
    text: `คุณต้องการลบหลักสูตร "${curriculum.name}" หรือไม่?`,
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })
  
  if (result.isConfirmed) {
    try {
      const response: any = await api.delete(`/api/academies/curriculums/${curriculum.id}`)
      
      if (response.success) {
        await fetchCurriculums()
        await fetchStatistics()
        
        Swal.fire({
          icon: 'success',
          title: 'ลบสำเร็จ',
          text: 'ลบหลักสูตรเรียบร้อยแล้ว',
          timer: 2000,
          showConfirmButton: false
        })
      }
    } catch (err: any) {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถลบหลักสูตรได้'
      })
    }
  }
}

// Open courses modal
const openCoursesModal = async (curriculum: any) => {
  selectedCurriculum.value = curriculum
  showCoursesModal.value = true
  await fetchCurriculumCourses(curriculum.id)
}

// Fetch curriculum courses
const fetchCurriculumCourses = async (curriculumId: number) => {
  isLoadingCourses.value = true
  try {
    const response: any = await api.get(`/api/academies/curriculums/${curriculumId}/courses`)
    if (response.success) {
      curriculumCourses.value = response.courses || []
    }
  } catch (err) {
    console.error('Failed to fetch courses:', err)
  } finally {
    isLoadingCourses.value = false
  }
}

// Open add courses modal
const openAddCoursesModal = async () => {
  showAddCoursesModal.value = true
  selectedCourseIds.value = []
  courseForm.value = {
    course_type: 'required',
    semester: '1',
    year_level: 1,
    credits: 0
  }
  await fetchAvailableCourses()
}

// Fetch available courses
const fetchAvailableCourses = async () => {
  if (!academyId.value || !selectedCurriculum.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/curriculums/${selectedCurriculum.value.id}/available-courses`)
    if (response.success) {
      availableCourses.value = response.courses || []
    }
  } catch (err) {
    console.error('Failed to fetch available courses:', err)
  }
}

// Add courses to curriculum
const addCoursesToCurriculum = async () => {
  if (!selectedCurriculum.value || selectedCourseIds.value.length === 0) return
  
  isSubmitting.value = true
  try {
    const coursesToAdd = selectedCourseIds.value.map(courseId => ({
      course_id: courseId,
      ...courseForm.value
    }))

    const response: any = await api.post(
      `/api/academies/curriculums/${selectedCurriculum.value.id}/courses/bulk`,
      { courses: coursesToAdd }
    )
    
    if (response.success) {
      showAddCoursesModal.value = false
      await fetchCurriculumCourses(selectedCurriculum.value.id)
      await fetchCurriculums()
      
      Swal.fire({
        icon: 'success',
        title: 'เพิ่มรายวิชาสำเร็จ',
        text: response.message,
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err.response?.data?.message || 'ไม่สามารถเพิ่มรายวิชาได้'
    })
  } finally {
    isSubmitting.value = false
  }
}

// Remove course from curriculum
const removeCourse = async (curriculumCourseId: number) => {
  if (!selectedCurriculum.value) return
  
  const result = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันการลบ',
    text: 'คุณต้องการลบรายวิชานี้ออกจากหลักสูตรหรือไม่?',
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })
  
  if (result.isConfirmed) {
    try {
      const response: any = await api.delete(
        `/api/academies/curriculums/${selectedCurriculum.value.id}/courses/${curriculumCourseId}`
      )
      
      if (response.success) {
        await fetchCurriculumCourses(selectedCurriculum.value.id)
        await fetchCurriculums()
        
        Swal.fire({
          icon: 'success',
          title: 'ลบสำเร็จ',
          timer: 1500,
          showConfirmButton: false
        })
      }
    } catch (err: any) {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถลบรายวิชาได้'
      })
    }
  }
}

// Open students modal
const openStudentsModal = async (curriculum: any) => {
  selectedCurriculum.value = curriculum
  showStudentsModal.value = true
  await fetchCurriculumStudents(curriculum.id)
}

// Fetch curriculum students
const fetchCurriculumStudents = async (curriculumId: number) => {
  isLoadingStudents.value = true
  try {
    const response: any = await api.get(`/api/academies/curriculums/${curriculumId}/students`, {
      per_page: 100
    })
    if (response.success) {
      curriculumStudents.value = response.students || []
    }
  } catch (err) {
    console.error('Failed to fetch students:', err)
  } finally {
    isLoadingStudents.value = false
  }
}

// Filtered available courses
const filteredAvailableCourses = computed(() => {
  if (!courseSearchQuery.value) return availableCourses.value
  const query = courseSearchQuery.value.toLowerCase()
  return availableCourses.value.filter((c: any) => 
    c.name?.toLowerCase().includes(query) ||
    c.code?.toLowerCase().includes(query)
  )
})

// Course type options
const courseTypeOptions = [
  { value: 'required', label: 'วิชาบังคับ', color: 'bg-red-100 text-red-700' },
  { value: 'elective', label: 'วิชาเลือก', color: 'bg-blue-100 text-blue-700' },
  { value: 'general', label: 'วิชาพื้นฐาน', color: 'bg-green-100 text-green-700' }
]

const getCourseTypeColor = (type: string) => {
  return courseTypeOptions.find(t => t.value === type)?.color || 'bg-gray-100 text-gray-700'
}

const getCourseTypeLabel = (type: string) => {
  return courseTypeOptions.find(t => t.value === type)?.label || type
}

// Status badge
const getStatusBadge = (status: string) => {
  switch (status) {
    case 'active': return { label: 'กำลังศึกษา', class: 'bg-green-100 text-green-700' }
    case 'graduated': return { label: 'จบแล้ว', class: 'bg-blue-100 text-blue-700' }
    case 'dropped': return { label: 'พ้นสภาพ', class: 'bg-red-100 text-red-700' }
    case 'suspended': return { label: 'พักการเรียน', class: 'bg-yellow-100 text-yellow-700' }
    default: return { label: status, class: 'bg-gray-100 text-gray-700' }
  }
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการหลักสูตร</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">สร้างและจัดการหลักสูตรของโรงเรียน</p>
        </div>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors"
        >
          <Icon name="fluent:add-24-filled" class="w-5 h-5" />
          <span>สร้างหลักสูตร</span>
        </button>
      </div>

      <!-- Statistics Cards -->
      <div v-if="statistics" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-xl">
              <Icon name="fluent:book-multiple-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.total_curriculums }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">หลักสูตรทั้งหมด</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
              <Icon name="fluent:checkmark-circle-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.active_curriculums }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">เปิดใช้งาน</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
              <Icon name="fluent:people-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.total_students }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">นักเรียนในหลักสูตร</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-amber-100 dark:bg-amber-900/50 rounded-xl">
              <Icon name="fluent:hat-graduation-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.graduated_students }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">สำเร็จการศึกษา</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="flex-1 relative">
            <Icon name="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              v-model="searchQuery"
              @input="handleSearch"
              type="text"
              placeholder="ค้นหาหลักสูตร..."
              class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>
          
          <select
            v-model="selectedLevel"
            @change="handleFilterChange"
            class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
          >
            <option :value="null">ทุกระดับ</option>
            <option v-for="level in levels" :key="level" :value="level">{{ level }}</option>
          </select>
          
          <select
            v-model="selectedAcademicYear"
            @change="handleFilterChange"
            class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
          >
            <option value="">ทุกปีการศึกษา</option>
            <option v-for="year in academicYears" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>
      </div>

      <!-- Curriculum List -->
      <div v-if="isLoadingCurriculums" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
      </div>

      <div v-else-if="curriculums.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-sm border border-gray-100 dark:border-gray-700">
        <Icon name="fluent:book-multiple-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีหลักสูตร</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">เริ่มต้นสร้างหลักสูตรแรกของโรงเรียน</p>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors"
        >
          <Icon name="fluent:add-24-filled" class="w-5 h-5" />
          <span>สร้างหลักสูตร</span>
        </button>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div
          v-for="curriculum in curriculums"
          :key="curriculum.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow"
        >
          <div class="p-5">
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="p-2.5 bg-purple-100 dark:bg-purple-900/50 rounded-xl">
                  <Icon name="fluent:book-multiple-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-white">{{ curriculum.name }}</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">{{ curriculum.code || 'ไม่มีรหัส' }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span 
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded-full',
                    curriculum.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                  ]"
                >
                  {{ curriculum.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                </span>
                <div class="relative group">
                  <button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <Icon name="fluent:more-vertical-24-regular" class="w-5 h-5 text-gray-400" />
                  </button>
                  <div class="absolute right-0 top-full mt-1 w-40 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10">
                    <button
                      @click="openCoursesModal(curriculum)"
                      class="w-full px-4 py-2.5 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2 first:rounded-t-xl"
                    >
                      <Icon name="fluent:book-24-regular" class="w-4 h-4" />
                      จัดการรายวิชา
                    </button>
                    <button
                      @click="openStudentsModal(curriculum)"
                      class="w-full px-4 py-2.5 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
                    >
                      <Icon name="fluent:people-24-regular" class="w-4 h-4" />
                      ดูนักเรียน
                    </button>
                    <button
                      @click="openEditModal(curriculum)"
                      class="w-full px-4 py-2.5 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
                    >
                      <Icon name="fluent:edit-24-regular" class="w-4 h-4" />
                      แก้ไข
                    </button>
                    <button
                      @click="deleteCurriculum(curriculum)"
                      class="w-full px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 flex items-center gap-2 last:rounded-b-xl"
                    >
                      <Icon name="fluent:delete-24-regular" class="w-4 h-4" />
                      ลบ
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <p v-if="curriculum.description" class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
              {{ curriculum.description }}
            </p>

            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-3 mb-4">
              <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400">ระดับ</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ curriculum.level || '-' }}</p>
              </div>
              <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400">ปีการศึกษา</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ curriculum.academic_year || '-' }}</p>
              </div>
              <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400">หน่วยกิตรวม</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ curriculum.total_credits || 0 }}</p>
              </div>
              <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                <p class="text-xs text-gray-500 dark:text-gray-400">ระยะเวลา</p>
                <p class="font-medium text-gray-900 dark:text-white">{{ curriculum.duration_years || 1 }} ปี</p>
              </div>
            </div>

            <!-- Stats -->
            <div class="flex items-center justify-between text-sm">
              <div class="flex items-center gap-4">
                <span class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                  <Icon name="fluent:book-24-regular" class="w-4 h-4" />
                  {{ curriculum.curriculum_courses_count || 0 }} รายวิชา
                </span>
                <span class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                  <Icon name="fluent:people-24-regular" class="w-4 h-4" />
                  {{ curriculum.active_students_count || 0 }} นักเรียน
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2">
        <button
          @click="pagination.current_page--; fetchCurriculums()"
          :disabled="pagination.current_page === 1"
          class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          ก่อนหน้า
        </button>
        <span class="text-gray-600 dark:text-gray-400">
          หน้า {{ pagination.current_page }} / {{ pagination.last_page }}
        </span>
        <button
          @click="pagination.current_page++; fetchCurriculums()"
          :disabled="pagination.current_page === pagination.last_page"
          class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          ถัดไป
        </button>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showCreateModal = false; showEditModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ showCreateModal ? 'สร้างหลักสูตรใหม่' : 'แก้ไขหลักสูตร' }}
            </h3>
            <button @click="showCreateModal = false; showEditModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <form @submit.prevent="showCreateModal ? createCurriculum() : updateCurriculum()" class="p-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ชื่อหลักสูตร *</label>
                <input
                  v-model="curriculumForm.name"
                  type="text"
                  required
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  placeholder="เช่น หลักสูตรวิทยาศาสตร์-คณิตศาสตร์"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">รหัสหลักสูตร</label>
                <input
                  v-model="curriculumForm.code"
                  type="text"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  placeholder="เช่น SCI-MATH-68"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ระดับการศึกษา</label>
                <select
                  v-model="curriculumForm.level"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  <option value="">เลือกระดับ</option>
                  <option v-for="level in levels" :key="level" :value="level">{{ level }}</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ปีการศึกษา</label>
                <select
                  v-model="curriculumForm.academic_year"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  <option v-for="year in academicYears" :key="year" :value="year">{{ year }}</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ระยะเวลา (ปี)</label>
                <input
                  v-model.number="curriculumForm.duration_years"
                  type="number"
                  min="1"
                  max="10"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">หน่วยกิตรวม</label>
                <input
                  v-model.number="curriculumForm.total_credits"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">หน่วยกิตบังคับ</label>
                <input
                  v-model.number="curriculumForm.required_credits"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">หน่วยกิตเลือก</label>
                <input
                  v-model.number="curriculumForm.elective_credits"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">รายละเอียด</label>
                <textarea
                  v-model="curriculumForm.description"
                  rows="3"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                  placeholder="รายละเอียดหลักสูตร..."
                ></textarea>
              </div>
              
              <div class="col-span-2">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="curriculumForm.is_active"
                    class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                  />
                  <span class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้งานหลักสูตร</span>
                </label>
              </div>
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="showCreateModal = false; showEditModal = false"
                class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                ยกเลิก
              </button>
              <button
                type="submit"
                :disabled="isSubmitting"
                class="flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                <span>{{ isSubmitting ? 'กำลังบันทึก...' : (showCreateModal ? 'สร้างหลักสูตร' : 'บันทึก') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Courses Modal -->
    <Teleport to="body">
      <div v-if="showCoursesModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showCoursesModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-3xl max-h-[85vh] overflow-hidden flex flex-col">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">รายวิชาในหลักสูตร</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedCurriculum?.name }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="openAddCoursesModal"
                class="px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm rounded-lg font-medium transition-colors flex items-center gap-1"
              >
                <Icon name="fluent:add-24-regular" class="w-4 h-4" />
                เพิ่มรายวิชา
              </button>
              <button @click="showCoursesModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
              </button>
            </div>
          </div>
          
          <div class="flex-1 overflow-y-auto p-5">
            <div v-if="isLoadingCourses" class="flex items-center justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
            </div>
            
            <div v-else-if="curriculumCourses.length === 0" class="text-center py-12">
              <Icon name="fluent:book-24-regular" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
              <p class="text-gray-500 dark:text-gray-400">ยังไม่มีรายวิชาในหลักสูตรนี้</p>
            </div>
            
            <div v-else class="space-y-3">
              <div
                v-for="cc in curriculumCourses"
                :key="cc.id"
                class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
              >
                <img
                  :src="cc.course?.cover || '/images/default-course.png'"
                  :alt="cc.course?.name"
                  class="w-16 h-12 rounded-lg object-cover"
                />
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900 dark:text-white truncate">{{ cc.course?.name }}</p>
                  <div class="flex items-center gap-2 mt-1">
                    <span :class="['px-2 py-0.5 text-xs rounded-full', getCourseTypeColor(cc.course_type)]">
                      {{ getCourseTypeLabel(cc.course_type) }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      ปี {{ cc.year_level || '-' }} / ภาค {{ cc.semester || '-' }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      {{ cc.credits || 0 }} หน่วยกิต
                    </span>
                  </div>
                </div>
                <button
                  @click="removeCourse(cc.id)"
                  class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                >
                  <Icon name="fluent:delete-24-regular" class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Add Courses Modal -->
    <Teleport to="body">
      <div v-if="showAddCoursesModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showAddCoursesModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[85vh] overflow-hidden flex flex-col">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">เพิ่มรายวิชา</h3>
            <button @click="showAddCoursesModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <div class="p-5 border-b border-gray-200 dark:border-gray-700 space-y-4">
            <div class="relative">
              <Icon name="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="courseSearchQuery"
                type="text"
                placeholder="ค้นหารายวิชา..."
                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl"
              />
            </div>
            
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">ประเภท</label>
                <select v-model="courseForm.course_type" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900">
                  <option v-for="opt in courseTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">ชั้นปี</label>
                <select v-model="courseForm.year_level" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900">
                  <option v-for="y in 6" :key="y" :value="y">ปี {{ y }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">ภาคเรียน</label>
                <select v-model="courseForm.semester" class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900">
                  <option value="1">ภาค 1</option>
                  <option value="2">ภาค 2</option>
                  <option value="summer">ฤดูร้อน</option>
                </select>
              </div>
            </div>
          </div>
          
          <div class="flex-1 overflow-y-auto p-5">
            <div v-if="filteredAvailableCourses.length === 0" class="text-center py-8">
              <p class="text-gray-500 dark:text-gray-400">ไม่พบรายวิชาที่สามารถเพิ่มได้</p>
            </div>
            
            <div v-else class="space-y-2">
              <label
                v-for="course in filteredAvailableCourses"
                :key="course.id"
                class="flex items-center gap-4 p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-xl cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="course.id"
                  v-model="selectedCourseIds"
                  class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <img
                  :src="course.cover || '/images/default-course.png'"
                  :alt="course.name"
                  class="w-12 h-9 rounded-lg object-cover"
                />
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900 dark:text-white truncate">{{ course.name }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">{{ course.code || '-' }} • {{ course.credit_units || 0 }} หน่วยกิต</p>
                </div>
              </label>
            </div>
          </div>
          
          <div class="p-5 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">เลือก {{ selectedCourseIds.length }} รายวิชา</p>
            <div class="flex items-center gap-3">
              <button
                @click="showAddCoursesModal = false"
                class="px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                ยกเลิก
              </button>
              <button
                @click="addCoursesToCurriculum"
                :disabled="selectedCourseIds.length === 0 || isSubmitting"
                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                <span>เพิ่มรายวิชา</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Students Modal -->
    <Teleport to="body">
      <div v-if="showStudentsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showStudentsModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">นักเรียนในหลักสูตร</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedCurriculum?.name }}</p>
            </div>
            <button @click="showStudentsModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <div class="flex-1 overflow-y-auto p-5">
            <div v-if="isLoadingStudents" class="flex items-center justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
            </div>
            
            <div v-else-if="curriculumStudents.length === 0" class="text-center py-12">
              <Icon name="fluent:people-24-regular" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
              <p class="text-gray-500 dark:text-gray-400">ยังไม่มีนักเรียนในหลักสูตรนี้</p>
            </div>
            
            <div v-else class="space-y-3">
              <div
                v-for="student in curriculumStudents"
                :key="student.id"
                class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
              >
                <img
                  :src="student.user?.profile_photo_path || '/images/default-avatar.png'"
                  :alt="student.user?.name"
                  class="w-10 h-10 rounded-full object-cover"
                />
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900 dark:text-white truncate">{{ student.user?.name }}</p>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs text-gray-500 dark:text-gray-400">ชั้นปี {{ student.current_year_level }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ student.completed_credits || 0 }} หน่วยกิต</span>
                  </div>
                </div>
                <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBadge(student.status).class]">
                  {{ getStatusBadge(student.status).label }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

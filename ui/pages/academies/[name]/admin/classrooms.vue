<script setup lang="ts">
/**
 * Academy Admin - Classroom Management
 * หน้าจัดการห้องเรียนของโรงเรียน
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
const classrooms = ref<any[]>([])
const gradeLevels = ref<any[]>([])
const statistics = ref<any>(null)
const isLoading = ref(true)
const isLoadingClassrooms = ref(false)

// Filters
const searchQuery = ref('')
const selectedGradeLevel = ref<string | null>(null)
const selectedAcademicYear = ref<string>('')

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
const showStudentsModal = ref(false)
const showAddStudentsModal = ref(false)
const showTransferModal = ref(false)
const selectedClassroom = ref<any>(null)

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Form
const classroomForm = ref({
  name: '',
  grade_level: '',
  academic_year: '',
  homeroom_teacher_id: null as number | null,
  capacity: 40
})
const isSubmitting = ref(false)
const formErrors = ref<Record<string, string[]>>({})

// Students Management
const classroomStudents = ref<any[]>([])
const isLoadingStudents = ref(false)
const studentSearchQuery = ref('')
const showAddStudentModal = ref(false)
const availableStudents = ref<any[]>([])
const selectedStudentIds = ref<number[]>([])

// Transfer
const transferStudentId = ref<number | null>(null)
const transferToClassroomId = ref<number | null>(null)
const otherClassrooms = ref<any[]>([])

// Academic Years
const currentYear = new Date().getFullYear() + 543 // Buddhist Era
const academicYears = computed(() => {
  const years = []
  for (let i = currentYear + 1; i >= currentYear - 3; i--) {
    years.push(i.toString())
  }
  return years
})

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
        fetchClassrooms(),
        fetchGradeLevels(),
        fetchStatistics()
      ])
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

// Fetch classrooms
const fetchClassrooms = async () => {
  if (!academyId.value) return
  
  isLoadingClassrooms.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/classrooms`, {
      search: searchQuery.value || undefined,
      grade_level: selectedGradeLevel.value || undefined,
      academic_year: selectedAcademicYear.value || undefined,
      page: pagination.value.current_page,
      per_page: pagination.value.per_page
    })
    
    if (response.success) {
      classrooms.value = response.classrooms || []
      if (response.pagination) {
        pagination.value = response.pagination
      }
    }
  } catch (err) {
    console.error('Failed to fetch classrooms:', err)
  } finally {
    isLoadingClassrooms.value = false
  }
}

// Fetch grade levels
const fetchGradeLevels = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/classrooms/grade-levels`)
    if (response.success) {
      gradeLevels.value = response.grade_levels || []
    }
  } catch (err) {
    console.error('Failed to fetch grade levels:', err)
  }
}

// Fetch statistics
const fetchStatistics = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/classrooms/statistics`, {
      academic_year: selectedAcademicYear.value
    })
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
    fetchClassrooms()
  }, 300)
}

// Filter change
const handleFilterChange = () => {
  pagination.value.current_page = 1
  fetchClassrooms()
  fetchStatistics()
}

// Open create modal
const openCreateModal = () => {
  classroomForm.value = {
    name: '',
    grade_level: gradeLevels.value[0] || '',
    academic_year: selectedAcademicYear.value || currentYear.toString(),
    homeroom_teacher_id: null,
    capacity: 40
  }
  formErrors.value = {}
  showCreateModal.value = true
}

// Open edit modal
const openEditModal = (classroom: any) => {
  selectedClassroom.value = classroom
  classroomForm.value = {
    name: classroom.name,
    grade_level: classroom.grade_level || '',
    academic_year: classroom.academic_year || '',
    homeroom_teacher_id: classroom.homeroom_teacher_id,
    capacity: classroom.capacity || 40
  }
  formErrors.value = {}
  showEditModal.value = true
}

// Create classroom
const createClassroom = async () => {
  if (!academyId.value) return
  
  isSubmitting.value = true
  formErrors.value = {}
  
  try {
    const response: any = await api.post(`/api/academies/${academyId.value}/classrooms`, {
      name: classroomForm.value.name,
      grade_level: classroomForm.value.grade_level,
      academic_year: classroomForm.value.academic_year,
      homeroom_teacher_id: classroomForm.value.homeroom_teacher_id || undefined,
      capacity: classroomForm.value.capacity
    })
    
    if (response.success) {
      showCreateModal.value = false
      await fetchClassrooms()
      await fetchStatistics()
      await fetchGradeLevels()
      
      Swal.fire({
        icon: 'success',
        title: 'สร้างห้องเรียนสำเร็จ',
        text: `สร้างห้อง "${classroomForm.value.name}" เรียบร้อยแล้ว`,
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
        text: err.response?.data?.message || 'ไม่สามารถสร้างห้องเรียนได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Update classroom
const updateClassroom = async () => {
  if (!academyId.value || !selectedClassroom.value) return
  
  isSubmitting.value = true
  formErrors.value = {}
  
  try {
    const response: any = await api.put(`/api/academies/${academyId.value}/classrooms/${selectedClassroom.value.id}`, {
      name: classroomForm.value.name,
      grade_level: classroomForm.value.grade_level,
      academic_year: classroomForm.value.academic_year,
      homeroom_teacher_id: classroomForm.value.homeroom_teacher_id || undefined,
      capacity: classroomForm.value.capacity
    })
    
    if (response.success) {
      showEditModal.value = false
      await fetchClassrooms()
      await fetchGradeLevels()
      
      Swal.fire({
        icon: 'success',
        title: 'อัปเดตสำเร็จ',
        text: 'อัปเดตข้อมูลห้องเรียนเรียบร้อยแล้ว',
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
        text: err.response?.data?.message || 'ไม่สามารถอัปเดตห้องเรียนได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Delete classroom
const deleteClassroom = async (classroom: any) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันการลบ',
    text: `คุณต้องการลบห้องเรียน "${classroom.name}" หรือไม่? นักเรียนทั้งหมดในห้องจะถูกนำออก`,
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })
  
  if (result.isConfirmed) {
    try {
      const response: any = await api.delete(`/api/academies/${academyId.value}/classrooms/${classroom.id}`)
      
      if (response.success) {
        await fetchClassrooms()
        await fetchStatistics()
        
        Swal.fire({
          icon: 'success',
          title: 'ลบสำเร็จ',
          text: 'ลบห้องเรียนเรียบร้อยแล้ว',
          timer: 2000,
          showConfirmButton: false
        })
      }
    } catch (err: any) {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถลบห้องเรียนได้'
      })
    }
  }
}

// Open students modal
const openStudentsModal = async (classroom: any) => {
  selectedClassroom.value = classroom
  showStudentsModal.value = true
  await fetchClassroomStudents(classroom.id)
}

// Fetch classroom students
const fetchClassroomStudents = async (classroomId: number) => {
  if (!academyId.value) return
  
  isLoadingStudents.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/classrooms/${classroomId}/students`)
    if (response.success) {
      classroomStudents.value = response.students || []
    }
  } catch (err) {
    console.error('Failed to fetch students:', err)
  } finally {
    isLoadingStudents.value = false
  }
}

// Open add students modal
const openAddStudentsModal = async () => {
  showAddStudentModal.value = true
  selectedStudentIds.value = []
  await fetchAvailableStudents()
}

// Fetch available students (academy students not in any classroom)
const fetchAvailableStudents = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/classrooms/students`, {
      per_page: 100
    })
    
    if (response.success) {
      // Filter students without classroom
      availableStudents.value = (response.students || []).filter(
        (s: any) => !s.classroom_id
      )
    }
  } catch (err) {
    console.error('Failed to fetch available students:', err)
  }
}

// Add students to classroom
const addStudentsToClassroom = async () => {
  if (!academyId.value || !selectedClassroom.value || selectedStudentIds.value.length === 0) return
  
  isSubmitting.value = true
  try {
    const response: any = await api.post(
      `/api/academies/${academyId.value}/classrooms/${selectedClassroom.value.id}/students`,
      { user_ids: selectedStudentIds.value }
    )
    
    if (response.success) {
      showAddStudentModal.value = false
      await fetchClassroomStudents(selectedClassroom.value.id)
      await fetchClassrooms()
      
      Swal.fire({
        icon: 'success',
        title: 'เพิ่มนักเรียนสำเร็จ',
        text: `เพิ่ม ${selectedStudentIds.value.length} คนเข้าห้องเรียนเรียบร้อยแล้ว`,
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err.response?.data?.message || 'ไม่สามารถเพิ่มนักเรียนได้'
    })
  } finally {
    isSubmitting.value = false
  }
}

// Remove student from classroom
const removeStudent = async (studentMemberId: number) => {
  if (!academyId.value || !selectedClassroom.value) return
  
  const result = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันการนำออก',
    text: 'คุณต้องการนำนักเรียนออกจากห้องเรียนนี้หรือไม่?',
    showCancelButton: true,
    confirmButtonText: 'นำออก',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })
  
  if (result.isConfirmed) {
    try {
      const response: any = await api.delete(
        `/api/academies/${academyId.value}/classrooms/${selectedClassroom.value.id}/students/${studentMemberId}`
      )
      
      if (response.success) {
        await fetchClassroomStudents(selectedClassroom.value.id)
        await fetchClassrooms()
        
        Swal.fire({
          icon: 'success',
          title: 'นำออกสำเร็จ',
          timer: 2000,
          showConfirmButton: false
        })
      }
    } catch (err: any) {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถนำนักเรียนออกได้'
      })
    }
  }
}

// Update student number
const updateStudentNumber = async (studentMemberId: number, studentNumber: string) => {
  if (!academyId.value || !selectedClassroom.value) return
  
  try {
    const response: any = await api.put(
      `/api/academies/${academyId.value}/classrooms/${selectedClassroom.value.id}/students/${studentMemberId}`,
      { student_number: studentNumber }
    )
    
    if (response.success) {
      Swal.fire({
        icon: 'success',
        title: 'อัปเดตเลขที่สำเร็จ',
        timer: 1500,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err.response?.data?.message || 'ไม่สามารถอัปเดตเลขที่ได้'
    })
  }
}

// Open transfer modal
const openTransferModal = async (studentMemberId: number) => {
  transferStudentId.value = studentMemberId
  transferToClassroomId.value = null
  showTransferModal.value = true
  
  // Fetch other classrooms
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/classrooms`, {
      academic_year: selectedAcademicYear.value,
      per_page: 100
    })
    if (response.success) {
      otherClassrooms.value = (response.classrooms || []).filter(
        (c: any) => c.id !== selectedClassroom.value?.id
      )
    }
  } catch (err) {
    console.error('Failed to fetch other classrooms:', err)
  }
}

// Transfer student
const transferStudent = async () => {
  if (!academyId.value || !transferStudentId.value || !transferToClassroomId.value) return
  
  isSubmitting.value = true
  try {
    const response: any = await api.post(
      `/api/academies/${academyId.value}/classrooms/transfer-student`,
      {
        student_member_id: transferStudentId.value,
        to_classroom_id: transferToClassroomId.value
      }
    )
    
    if (response.success) {
      showTransferModal.value = false
      await fetchClassroomStudents(selectedClassroom.value.id)
      await fetchClassrooms()
      
      Swal.fire({
        icon: 'success',
        title: 'ย้ายห้องสำเร็จ',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err.response?.data?.message || 'ไม่สามารถย้ายนักเรียนได้'
    })
  } finally {
    isSubmitting.value = false
  }
}

// Filtered students for search
const filteredAvailableStudents = computed(() => {
  if (!studentSearchQuery.value) return availableStudents.value
  const query = studentSearchQuery.value.toLowerCase()
  return availableStudents.value.filter((s: any) => 
    s.user?.name?.toLowerCase().includes(query) ||
    s.user?.email?.toLowerCase().includes(query) ||
    s.student_id?.toLowerCase().includes(query)
  )
})

// Group classrooms by grade level
const groupedClassrooms = computed(() => {
  const grouped: Record<string, any[]> = {}
  classrooms.value.forEach(classroom => {
    const level = classroom.grade_level || 'อื่นๆ'
    if (!grouped[level]) {
      grouped[level] = []
    }
    grouped[level].push(classroom)
  })
  return grouped
})

// Get grade color
const getGradeColor = (grade: string) => {
  if (grade?.includes('ป.') || grade?.includes('ประถม')) return 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
  if (grade?.includes('ม.') || grade?.includes('มัธยม')) return 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'
  if (grade?.includes('อนุบาล')) return 'bg-pink-100 text-pink-700 dark:bg-pink-900/50 dark:text-pink-300'
  return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
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
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการห้องเรียน</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการห้องเรียนและนักเรียนของโรงเรียน</p>
        </div>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors"
        >
          <Icon name="fluent:add-24-filled" class="w-5 h-5" />
          <span>สร้างห้องเรียน</span>
        </button>
      </div>

      <!-- Statistics Cards -->
      <div v-if="statistics" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
              <Icon name="fluent:building-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.total_classrooms }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ห้องเรียนทั้งหมด</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
              <Icon name="fluent:people-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.total_students }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">นักเรียนทั้งหมด</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-amber-100 dark:bg-amber-900/50 rounded-xl">
              <Icon name="fluent:person-board-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.classrooms_with_teacher }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">มีครูประจำชั้น</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-xl">
              <Icon name="fluent:chart-multiple-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ (statistics.total_students / (statistics.total_classrooms || 1)).toFixed(1) }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">เฉลี่ย/ห้อง</p>
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
              placeholder="ค้นหาห้องเรียน..."
              class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>
          
          <select
            v-model="selectedGradeLevel"
            @change="handleFilterChange"
            class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
          >
            <option :value="null">ทุกระดับชั้น</option>
            <option v-for="level in gradeLevels" :key="level" :value="level">{{ level }}</option>
          </select>
          
          <select
            v-model="selectedAcademicYear"
            @change="handleFilterChange"
            class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
          >
            <option v-for="year in academicYears" :key="year" :value="year">ปีการศึกษา {{ year }}</option>
          </select>
        </div>
      </div>

      <!-- Classroom List -->
      <div v-if="isLoadingClassrooms" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
      </div>

      <div v-else-if="classrooms.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-sm border border-gray-100 dark:border-gray-700">
        <Icon name="fluent:class-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีห้องเรียน</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">เริ่มต้นสร้างห้องเรียนแรกของโรงเรียน</p>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors"
        >
          <Icon name="fluent:add-24-filled" class="w-5 h-5" />
          <span>สร้างห้องเรียน</span>
        </button>
      </div>

      <!-- Grouped by Grade Level -->
      <div v-else class="space-y-6">
        <div v-for="(rooms, grade) in groupedClassrooms" :key="grade">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
            <span :class="['px-3 py-1 rounded-lg text-sm', getGradeColor(grade)]">{{ grade }}</span>
            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ rooms.length }} ห้อง)</span>
          </h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="classroom in rooms"
              :key="classroom.id"
              class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow"
            >
              <div class="p-5">
                <div class="flex items-start justify-between mb-3">
                  <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
                      <Icon name="fluent:class-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                      <h3 class="font-semibold text-gray-900 dark:text-white">{{ classroom.name }}</h3>
                      <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ classroom.students_count || 0 }}/{{ classroom.capacity || 40 }} คน
                      </p>
                    </div>
                  </div>
                  <div class="relative group">
                    <button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                      <Icon name="fluent:more-vertical-24-regular" class="w-5 h-5 text-gray-400" />
                    </button>
                    <div class="absolute right-0 top-full mt-1 w-40 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10">
                      <button
                        @click="openStudentsModal(classroom)"
                        class="w-full px-4 py-2.5 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2 first:rounded-t-xl"
                      >
                        <Icon name="fluent:people-24-regular" class="w-4 h-4" />
                        จัดการนักเรียน
                      </button>
                      <button
                        @click="openEditModal(classroom)"
                        class="w-full px-4 py-2.5 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
                      >
                        <Icon name="fluent:edit-24-regular" class="w-4 h-4" />
                        แก้ไข
                      </button>
                      <button
                        @click="deleteClassroom(classroom)"
                        class="w-full px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 flex items-center gap-2 last:rounded-b-xl"
                      >
                        <Icon name="fluent:delete-24-regular" class="w-4 h-4" />
                        ลบ
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-4">
                  <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div 
                      class="h-full bg-gradient-to-r from-blue-500 to-blue-600 rounded-full transition-all"
                      :style="{ width: `${Math.min((classroom.students_count || 0) / (classroom.capacity || 40) * 100, 100)}%` }"
                    ></div>
                  </div>
                </div>

                <!-- Homeroom Teacher -->
                <div v-if="classroom.homeroom_teacher" class="flex items-center gap-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
                  <img
                    :src="classroom.homeroom_teacher.profile_photo_url || '/images/default-avatar.png'"
                    :alt="classroom.homeroom_teacher.name"
                    class="w-8 h-8 rounded-full object-cover"
                  />
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ classroom.homeroom_teacher.name }}</p>
                    <p class="text-xs text-amber-600 dark:text-amber-400">ครูประจำชั้น</p>
                  </div>
                </div>
                <div v-else class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl text-center">
                  <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีครูประจำชั้น</p>
                </div>
              </div>

              <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <button
                  @click="openStudentsModal(classroom)"
                  class="text-sm text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
                >
                  <Icon name="fluent:people-24-regular" class="w-4 h-4" />
                  ดูนักเรียน
                </button>
                <span class="text-xs text-gray-400">
                  ปี {{ classroom.academic_year }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2">
        <button
          @click="pagination.current_page--; fetchClassrooms()"
          :disabled="pagination.current_page === 1"
          class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          ก่อนหน้า
        </button>
        <span class="text-gray-600 dark:text-gray-400">
          หน้า {{ pagination.current_page }} / {{ pagination.last_page }}
        </span>
        <button
          @click="pagination.current_page++; fetchClassrooms()"
          :disabled="pagination.current_page === pagination.last_page"
          class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          ถัดไป
        </button>
      </div>
    </div>

    <!-- Create Classroom Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showCreateModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">สร้างห้องเรียนใหม่</h3>
            <button @click="showCreateModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <form @submit.prevent="createClassroom" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ชื่อห้องเรียน *</label>
              <input
                v-model="classroomForm.name"
                type="text"
                required
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                placeholder="เช่น ป.1/1, ม.3/2"
              />
              <p v-if="formErrors.name" class="mt-1 text-sm text-red-500">{{ formErrors.name[0] }}</p>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ระดับชั้น *</label>
                <input
                  v-model="classroomForm.grade_level"
                  type="text"
                  required
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  placeholder="เช่น ป.1, ม.3"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ปีการศึกษา *</label>
                <select
                  v-model="classroomForm.academic_year"
                  required
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  <option v-for="year in academicYears" :key="year" :value="year">{{ year }}</option>
                </select>
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">จำนวนนักเรียนสูงสุด</label>
              <input
                v-model.number="classroomForm.capacity"
                type="number"
                min="1"
                max="100"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="showCreateModal = false"
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
                <span>{{ isSubmitting ? 'กำลังสร้าง...' : 'สร้างห้องเรียน' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Edit Classroom Modal -->
    <Teleport to="body">
      <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showEditModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">แก้ไขห้องเรียน</h3>
            <button @click="showEditModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <form @submit.prevent="updateClassroom" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ชื่อห้องเรียน *</label>
              <input
                v-model="classroomForm.name"
                type="text"
                required
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ระดับชั้น *</label>
                <input
                  v-model="classroomForm.grade_level"
                  type="text"
                  required
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ปีการศึกษา *</label>
                <select
                  v-model="classroomForm.academic_year"
                  required
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  <option v-for="year in academicYears" :key="year" :value="year">{{ year }}</option>
                </select>
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">จำนวนนักเรียนสูงสุด</label>
              <input
                v-model.number="classroomForm.capacity"
                type="number"
                min="1"
                max="100"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="showEditModal = false"
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
                <span>{{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึก' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Students Modal -->
    <Teleport to="body">
      <div v-if="showStudentsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showStudentsModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-3xl max-h-[85vh] overflow-hidden flex flex-col">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">นักเรียนในห้อง</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedClassroom?.name }} ({{ classroomStudents.length }}/{{ selectedClassroom?.capacity || 40 }} คน)</p>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="openAddStudentsModal"
                class="px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm rounded-lg font-medium transition-colors flex items-center gap-1"
              >
                <Icon name="fluent:add-24-regular" class="w-4 h-4" />
                เพิ่มนักเรียน
              </button>
              <button @click="showStudentsModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
              </button>
            </div>
          </div>
          
          <div class="flex-1 overflow-y-auto p-5">
            <div v-if="isLoadingStudents" class="flex items-center justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
            </div>
            
            <div v-else-if="classroomStudents.length === 0" class="text-center py-12">
              <Icon name="fluent:people-24-regular" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
              <p class="text-gray-500 dark:text-gray-400">ยังไม่มีนักเรียนในห้องนี้</p>
            </div>
            
            <div v-else>
              <table class="w-full">
                <thead>
                  <tr class="text-left text-sm text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                    <th class="pb-3 font-medium">เลขที่</th>
                    <th class="pb-3 font-medium">ชื่อ-นามสกุล</th>
                    <th class="pb-3 font-medium hidden sm:table-cell">รหัสนักเรียน</th>
                    <th class="pb-3 font-medium text-right">การดำเนินการ</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                  <tr v-for="(student, index) in classroomStudents" :key="student.id" class="text-gray-900 dark:text-white">
                    <td class="py-3">
                      <input
                        type="text"
                        :value="student.student_number || (index + 1)"
                        @blur="updateStudentNumber(student.id, ($event.target as HTMLInputElement).value)"
                        class="w-16 px-2 py-1 text-center border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900"
                      />
                    </td>
                    <td class="py-3">
                      <div class="flex items-center gap-3">
                        <img
                          :src="student.user?.profile_photo_url || '/images/default-avatar.png'"
                          :alt="student.user?.name"
                          class="w-8 h-8 rounded-full object-cover"
                        />
                        <span class="font-medium">{{ student.user?.name }}</span>
                      </div>
                    </td>
                    <td class="py-3 hidden sm:table-cell text-gray-500 dark:text-gray-400">
                      {{ student.student_id || '-' }}
                    </td>
                    <td class="py-3">
                      <div class="flex items-center justify-end gap-2">
                        <button
                          @click="openTransferModal(student.id)"
                          class="p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                          title="ย้ายห้อง"
                        >
                          <Icon name="fluent:arrow-swap-24-regular" class="w-4 h-4" />
                        </button>
                        <button
                          @click="removeStudent(student.id)"
                          class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                          title="นำออก"
                        >
                          <Icon name="fluent:delete-24-regular" class="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Add Student Modal -->
    <Teleport to="body">
      <div v-if="showAddStudentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showAddStudentModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[80vh] overflow-hidden flex flex-col">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">เพิ่มนักเรียน</h3>
            <button @click="showAddStudentModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <div class="p-5 border-b border-gray-200 dark:border-gray-700">
            <div class="relative">
              <Icon name="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="studentSearchQuery"
                type="text"
                placeholder="ค้นหานักเรียน..."
                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-500"
              />
            </div>
          </div>
          
          <div class="flex-1 overflow-y-auto p-5">
            <div v-if="filteredAvailableStudents.length === 0" class="text-center py-8">
              <p class="text-gray-500 dark:text-gray-400">ไม่พบนักเรียนที่ยังไม่มีห้อง</p>
            </div>
            
            <div v-else class="space-y-2">
              <label
                v-for="student in filteredAvailableStudents"
                :key="student.id"
                class="flex items-center gap-4 p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-xl cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="student.user_id"
                  v-model="selectedStudentIds"
                  class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <img
                  :src="student.user?.profile_photo_url || '/images/default-avatar.png'"
                  :alt="student.user?.name"
                  class="w-10 h-10 rounded-full object-cover"
                />
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900 dark:text-white truncate">{{ student.user?.name }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ student.student_id || student.user?.email }}</p>
                </div>
              </label>
            </div>
          </div>
          
          <div class="p-5 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">เลือก {{ selectedStudentIds.length }} คน</p>
            <div class="flex items-center gap-3">
              <button
                @click="showAddStudentModal = false"
                class="px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                ยกเลิก
              </button>
              <button
                @click="addStudentsToClassroom"
                :disabled="selectedStudentIds.length === 0 || isSubmitting"
                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                <span>เพิ่มนักเรียน</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Transfer Modal -->
    <Teleport to="body">
      <div v-if="showTransferModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showTransferModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ย้ายห้องเรียน</h3>
            <button @click="showTransferModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <div class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ย้ายไปห้อง</label>
              <select
                v-model="transferToClassroomId"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              >
                <option :value="null" disabled>เลือกห้องเรียน</option>
                <option v-for="room in otherClassrooms" :key="room.id" :value="room.id">
                  {{ room.name }} ({{ room.students_count || 0 }}/{{ room.capacity || 40 }})
                </option>
              </select>
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                @click="showTransferModal = false"
                class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                ยกเลิก
              </button>
              <button
                @click="transferStudent"
                :disabled="!transferToClassroomId || isSubmitting"
                class="flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                <span>ย้ายห้อง</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

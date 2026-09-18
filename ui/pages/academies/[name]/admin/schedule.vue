<script setup lang="ts">
/**
 * Academy Admin - Schedule Management
 * หน้าจัดการตารางเรียน
 */
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const isLoading = ref(true)
const isLoadingSchedule = ref(false)

// View mode
const viewMode = ref<'classroom' | 'teacher'>('classroom')
const selectedClassroom = ref<number | null>(null)
const selectedTeacher = ref<number | null>(null)

// Academic Year and Semester
const academicYears = ref<any[]>([])
const selectedAcademicYear = ref<number | null>(null)
const selectedSemester = ref<number | null>(null)

// Data
const classrooms = ref<any[]>([])
const teachers = ref<any[]>([])
const timetable = ref<any[]>([])
const courses = ref<any[]>([])
const periodSets = ref<any[]>([])

const allDays = ALL_SCHEDULE_DAYS

const currentGradeLevel = computed(() => {
  if (viewMode.value !== 'classroom') return null
  return classrooms.value.find((c: any) => c.id === selectedClassroom.value)?.grade_level ?? null
})

const { gridDays, gridRows, getScheduleCell, matchedPeriodId, applicableSets } = useScheduleGrid({
  periodSets,
  timetable,
  gradeLevel: currentGradeLevel,
})

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

const canView = computed(() => isAdmin.value || can('schedule.view') || can('academy.view'))
const canManage = computed(() => isAdmin.value || can('schedule.manage'))

const showBulkModal = ref(false)

const bulkPeriodRows = computed(() =>
  gridRows.value.filter((row: any) => row.type !== 'break' && row.type !== 'lunch' && row.type !== 'outside')
)

const occupiedSlots = computed(() => {
  const slots: any[] = []
  for (const day of timetable.value) {
    for (const s of day.schedules || []) {
      slots.push({
        day: Number(day.day),
        start_time: s.start_time,
        end_time: s.end_time,
        label: s.display_title || s.title || s.course?.name || 'คาบที่มีอยู่',
      })
    }
  }
  return slots
})

const selectedClassroomData = computed(() =>
  classrooms.value.find((c: any) => c.id === selectedClassroom.value) || null
)

const canBulkFill = computed(() =>
  canManage.value
  && viewMode.value === 'classroom'
  && !!selectedClassroom.value
  && !!selectedSemester.value
  && bulkPeriodRows.value.length > 0
)

// Modal
const showCreateModal = ref(false)
const showEditModal = ref(false)
const selectedSchedule = ref<any>(null)

// Form
const scheduleForm = ref({
  classroom_id: null as number | null,
  teacher_id: null as number | null,
  entry_type: 'course',
  course_id: null as number | null,
  title: '',
  day_of_week: 1,
  start_time: '08:00',
  end_time: '09:00',
  room: ''
})

const isSubmitting = ref(false)
const formErrors = ref<Record<string, string[]>>({})

const selectedYearData = computed(() => {
  return academicYears.value.find(y => y.id === selectedAcademicYear.value)
})

const availableSemesters = computed(() => {
  return selectedYearData.value?.semesters || []
})

const canCreateSchedule = computed(() => {
  return canManage.value && selectedSemester.value !== null
})

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!canView.value) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await fetchAcademicYears()
      await fetchTeachers()
      await fetchCourses()
      await fetchPeriodSets()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchPeriodSets = async () => {
  if (!academyId.value) return
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/schedule-period-sets`)
    periodSets.value = (response.data || []).filter((s: any) => s.is_active)
  } catch (err) {
    console.error('Failed to fetch period sets:', err)
    periodSets.value = []
  }
}

// Fetch academic years
const fetchAcademicYears = async () => {
  if (!academyId.value) return
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/academic-years`)
    if (response.success && response.academicYears?.length > 0) {
      academicYears.value = response.academicYears
      
      // Auto-select current year
      const currentYear = academicYears.value.find(y => y.is_current) || academicYears.value[0]
      selectedAcademicYear.value = currentYear.id
      
      // Semester will be auto-selected by the watcher
    }
  } catch (err) {
    console.error('Failed to fetch academic years:', err)
  }
}

// Watch academic year change to fetch classrooms and reset semester
watch(selectedAcademicYear, async (newVal) => {
  if (newVal && academyId.value) {
    await fetchClassrooms(newVal)
    
    // Select current semester or first semester
    const year = academicYears.value.find(y => y.id === newVal)
    if (year && year.semesters?.length > 0) {
      const currentSem = year.semesters.find((s: any) => s.is_current) || year.semesters.sort((a: any, b: any) => a.semester_number - b.semester_number)[0]
      selectedSemester.value = currentSem.id
    } else {
      selectedSemester.value = null
    }
  }
})

// Fetch classrooms
const fetchClassrooms = async (yearId: number) => {
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/classrooms`, {
      params: { academic_year_id: yearId }
    })
    if (response.success) {
      classrooms.value = response.classrooms || response.data || []
      
      if (viewMode.value === 'classroom' && classrooms.value.length > 0) {
        // Only auto-select if nothing is selected or current selection is not in list
        if (!selectedClassroom.value || !classrooms.value.find(c => c.id === selectedClassroom.value)) {
          selectedClassroom.value = classrooms.value[0].id
        }
      } else if (viewMode.value === 'classroom' && classrooms.value.length === 0) {
        selectedClassroom.value = null
      }
    }
  } catch (err) {
    console.error('Failed to fetch classrooms:', err)
  }
}

// Fetch teachers
const fetchTeachers = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/members`, {
      params: { role: 'teacher', status: 2, per_page: 200 }
    })
    if (response.success) {
      teachers.value = response.members || []
    }
  } catch (err) {
    console.error('Failed to fetch teachers:', err)
  }
}

// Fetch courses
const fetchCourses = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/courses`, {
      params: { per_page: 100 }
    })
    if (response.success) {
      courses.value = response.courses || []
    }
  } catch (err) {
    console.error('Failed to fetch courses:', err)
  }
}

// Fetch timetable
const fetchTimetable = async () => {
  if (!academyId.value || !selectedSemester.value) {
    timetable.value = []
    return
  }
  
  isLoadingSchedule.value = true
  try {
    const params: any = { semester_id: selectedSemester.value }
    if (viewMode.value === 'classroom' && selectedClassroom.value) {
      params.classroom_id = selectedClassroom.value
    } else if (viewMode.value === 'teacher' && selectedTeacher.value) {
      params.teacher_id = selectedTeacher.value
    } else {
      timetable.value = []
      isLoadingSchedule.value = false
      return
    }
    
    const response: any = await api.get(`/api/academies/${academyId.value}/schedules/timetable`, { params })
    if (response.success) {
      timetable.value = response.data?.timetable || []
    }
  } catch (err) {
    console.error('Failed to fetch timetable:', err)
    timetable.value = []
  } finally {
    isLoadingSchedule.value = false
  }
}

// Watch for view changes
watch([viewMode, selectedClassroom, selectedTeacher, selectedSemester], () => {
  if (academyId.value) {
    fetchTimetable()
  }
})

// Get color
const getScheduleColor = (schedule: any) => {
  if (schedule.course?.id) {
    const colors = [
      'bg-blue-100 dark:bg-blue-900/50 border-blue-300 dark:border-blue-700',
      'bg-green-100 dark:bg-green-900/50 border-green-300 dark:border-green-700',
      'bg-purple-100 dark:bg-purple-900/50 border-purple-300 dark:border-purple-700',
      'bg-amber-100 dark:bg-amber-900/50 border-amber-300 dark:border-amber-700',
      'bg-pink-100 dark:bg-pink-900/50 border-pink-300 dark:border-pink-700',
      'bg-cyan-100 dark:bg-cyan-900/50 border-cyan-300 dark:border-cyan-700',
      'bg-rose-100 dark:bg-rose-900/50 border-rose-300 dark:border-rose-700',
      'bg-indigo-100 dark:bg-indigo-900/50 border-indigo-300 dark:border-indigo-700',
    ]
    return colors[schedule.course.id % colors.length]
  }
  
  // Color for entry_type
  if (schedule.entry_type === 'break') return 'bg-orange-50 dark:bg-orange-900/30 border-orange-200 dark:border-orange-800'
  if (schedule.entry_type === 'exam') return 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800'
  return 'bg-gray-100 dark:bg-gray-800 border-gray-300 dark:border-gray-600'
}

// Open create modal
const openCreateModal = (day?: number, start?: string, end?: string) => {
  if (!canManage.value || !selectedSemester.value) return
  
  scheduleForm.value = {
    classroom_id: viewMode.value === 'classroom' ? selectedClassroom.value : null,
    teacher_id: viewMode.value === 'teacher' ? selectedTeacher.value : null,
    entry_type: 'course',
    course_id: null,
    title: '',
    day_of_week: day || 1,
    start_time: start || '08:00',
    end_time: end || '09:00',
    room: ''
  }
  formErrors.value = {}
  showCreateModal.value = true
}

// Open edit modal
// หมายเหตุ: payload ของ timetable ไม่มี `day` ในตัวรายการ (วันอยู่ที่กลุ่มแม่) และจะส่ง
// `classroom` มาเฉพาะมุมมองครู / ส่ง `teacher` มาเฉพาะมุมมองห้องเรียน
// จึงต้องรับวันจากช่องที่กดมา และเติมอีกฝั่งจากตัวเลือกที่กำลังดูอยู่ ไม่งั้นแก้ไขแล้วจะย้ายไปวันจันทร์
// และโดน 422 เพราะส่ง classroom_id/teacher_id เป็น null
const openEditModal = (schedule: any, dayValue?: number) => {
  if (!canManage.value) return

  selectedSchedule.value = schedule
  scheduleForm.value = {
    classroom_id: schedule.classroom?.id ?? selectedClassroom.value,
    teacher_id: schedule.teacher?.id ?? selectedTeacher.value,
    entry_type: schedule.entry_type || 'course',
    course_id: schedule.course?.id || null,
    title: schedule.title || '',
    day_of_week: dayValue ?? schedule.day ?? 1,
    start_time: schedule.start_time,
    end_time: schedule.end_time,
    room: schedule.room || ''
  }
  formErrors.value = {}
  showEditModal.value = true
}

// Handle entry type change
const handleEntryTypeChange = () => {
  if (scheduleForm.value.entry_type !== 'course') {
    scheduleForm.value.course_id = null
  }
}

// Create schedule
const createSchedule = async () => {
  if (!academyId.value || !selectedSemester.value) return
  
  // Validation
  if (scheduleForm.value.entry_type === 'course' && !scheduleForm.value.course_id && !scheduleForm.value.title) {
    Swal.fire({
      icon: 'warning',
      title: 'ข้อมูลไม่ครบ',
      text: 'กรุณาระบุคอร์สหรือชื่อคาบอย่างใดอย่างหนึ่ง'
    })
    return
  }
  if (scheduleForm.value.entry_type !== 'course' && !scheduleForm.value.title) {
    Swal.fire({
      icon: 'warning',
      title: 'ข้อมูลไม่ครบ',
      text: 'กรุณาระบุชื่อคาบ'
    })
    return
  }
  
  isSubmitting.value = true
  formErrors.value = {}
  
  try {
    const payload = {
      semester_id: selectedSemester.value,
      classroom_id: scheduleForm.value.classroom_id,
      teacher_id: scheduleForm.value.teacher_id,
      entry_type: scheduleForm.value.entry_type,
      course_id: scheduleForm.value.course_id,
      title: scheduleForm.value.title || undefined,
      day_of_week: scheduleForm.value.day_of_week,
      start_time: scheduleForm.value.start_time,
      end_time: scheduleForm.value.end_time,
      room: scheduleForm.value.room || undefined,
      period_id: matchedPeriodId(scheduleForm.value.start_time, scheduleForm.value.end_time)
    }
    
    const response: any = await api.post(`/api/academies/${academyId.value}/schedules`, payload)
    
    if (response.success) {
      showCreateModal.value = false
      await fetchTimetable()
      
      Swal.fire({
        icon: 'success',
        title: 'เพิ่มตารางเรียนสำเร็จ',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    if (err.data?.errors) {
      formErrors.value = err.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.data?.message || 'ไม่สามารถเพิ่มตารางเรียนได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Update schedule
const updateSchedule = async () => {
  if (!academyId.value || !selectedSchedule.value || !selectedSemester.value) return
  
  // Validation
  if (scheduleForm.value.entry_type === 'course' && !scheduleForm.value.course_id && !scheduleForm.value.title) {
    Swal.fire({
      icon: 'warning',
      title: 'ข้อมูลไม่ครบ',
      text: 'กรุณาระบุคอร์สหรือชื่อคาบอย่างใดอย่างหนึ่ง'
    })
    return
  }
  if (scheduleForm.value.entry_type !== 'course' && !scheduleForm.value.title) {
    Swal.fire({
      icon: 'warning',
      title: 'ข้อมูลไม่ครบ',
      text: 'กรุณาระบุชื่อคาบ'
    })
    return
  }
  
  isSubmitting.value = true
  formErrors.value = {}
  
  try {
    const payload = {
      semester_id: selectedSemester.value,
      classroom_id: scheduleForm.value.classroom_id,
      teacher_id: scheduleForm.value.teacher_id,
      entry_type: scheduleForm.value.entry_type,
      course_id: scheduleForm.value.course_id,
      title: scheduleForm.value.title || undefined,
      day_of_week: scheduleForm.value.day_of_week,
      start_time: scheduleForm.value.start_time,
      end_time: scheduleForm.value.end_time,
      room: scheduleForm.value.room || undefined,
      period_id: matchedPeriodId(scheduleForm.value.start_time, scheduleForm.value.end_time)
    }
    
    const response: any = await api.patch(
      `/api/academies/${academyId.value}/schedules/${selectedSchedule.value.id}`,
      payload
    )
    
    if (response.success) {
      showEditModal.value = false
      await fetchTimetable()
      
      Swal.fire({
        icon: 'success',
        title: 'อัปเดตตารางเรียนสำเร็จ',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    if (err.data?.errors) {
      formErrors.value = err.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.data?.message || 'ไม่สามารถอัปเดตตารางเรียนได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Delete schedule
const deleteSchedule = async () => {
  if (!academyId.value || !selectedSchedule.value) return
  
  const result = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันการลบ',
    text: `คุณต้องการลบคาบ "${selectedSchedule.value.title || selectedSchedule.value.course?.name || 'นี้'}" หรือไม่?`,
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })
  
  if (result.isConfirmed) {
    try {
      const response: any = await api.delete(
        `/api/academies/${academyId.value}/schedules/${selectedSchedule.value.id}`
      )
      
      if (response.success) {
        showEditModal.value = false
        await fetchTimetable()
        
        Swal.fire({
          icon: 'success',
          title: 'ลบสำเร็จ',
          timer: 2000,
          showConfirmButton: false
        })
      }
    } catch (err: any) {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.data?.message || 'ไม่สามารถลบได้'
      })
    }
  }
}
</script>

<template>
  <div class="px-4 sm:px-0">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ตารางเรียน</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการตารางเรียนของห้องเรียนและครู</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
          <NuxtLink
            v-if="canManage"
            :to="`/academies/${academyName}/admin/schedule-periods`"
            class="min-h-[44px] sm:min-h-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl font-medium transition-colors"
          >
            <Icon icon="fluent:clock-24-regular" class="w-5 h-5" />
            <span>ตั้งค่าโครงคาบ</span>
          </NuxtLink>
          <NuxtLink
            :to="`/academies/${academyName}/schedule-print`"
            class="min-h-[44px] sm:min-h-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl font-medium transition-colors"
          >
            <Icon icon="fluent:print-24-regular" class="w-5 h-5" />
            <span>พิมพ์ / ส่งออก</span>
          </NuxtLink>
          <button
            v-if="canManage"
            :disabled="!canBulkFill"
            @click="showBulkModal = true"
            class="min-h-[44px] sm:min-h-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Icon icon="fluent:table-add-24-filled" class="w-5 h-5" />
            <span>เติมหลายคาบ</span>
          </button>
          <button
            v-if="canManage"
            :disabled="!selectedSemester"
            @click="openCreateModal()"
            class="min-h-[44px] sm:min-h-0 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
            <span>เพิ่มตารางเรียน</span>
          </button>
        </div>
      </div>
      
      <div v-if="periodSets.length === 0" class="p-4 bg-orange-50 border border-orange-200 text-orange-800 rounded-xl">
        <div class="flex items-start gap-3">
          <Icon icon="fluent:warning-24-regular" class="w-6 h-6 mt-0.5 flex-shrink-0" />
          <div>
            <h3 class="font-medium text-orange-900">ยังไม่ได้ตั้งค่าโครงคาบเรียน</h3>
            <p class="text-sm mt-1">ตอนนี้กริดจะวาดจากคาบที่มีอยู่จริงแทน — ตั้งค่าโครงคาบเพื่อให้ตารางตรงกับคาบของโรงเรียน</p>
            <NuxtLink
              v-if="canManage"
              :to="`/academies/${academyName}/admin/schedule-periods`"
              class="inline-block mt-2 text-sm font-medium text-orange-700 hover:text-orange-900 underline"
            >
              ไปหน้าตั้งค่าโครงคาบ
            </NuxtLink>
          </div>
        </div>
      </div>

      <div v-if="canManage && viewMode === 'classroom' && selectedClassroom && selectedSemester && bulkPeriodRows.length === 0" class="p-4 bg-orange-50 border border-orange-200 text-orange-800 rounded-xl mt-4">
        <div class="flex items-start gap-3">
          <Icon icon="fluent:warning-24-regular" class="w-6 h-6 mt-0.5 flex-shrink-0" />
          <div>
            <h3 class="font-medium text-orange-900">ไม่สามารถเติมหลายคาบได้</h3>
            <p class="text-sm mt-1">เติมหลายคาบต้องมีชุดโครงคาบก่อน — ตั้งค่าคาบเรียนของโรงเรียนแล้วปุ่มจะใช้งานได้</p>
            <NuxtLink
              :to="`/academies/${academyName}/admin/schedule-periods`"
              class="inline-block mt-2 text-sm font-medium text-orange-700 hover:text-orange-900 underline"
            >
              ไปหน้าตั้งค่าโครงคาบ
            </NuxtLink>
          </div>
        </div>
      </div>

      <div v-if="academicYears.length > 0 && availableSemesters.length === 0" class="p-4 bg-orange-50 border border-orange-200 text-orange-800 rounded-xl">
        <div class="flex items-start gap-3">
          <Icon icon="fluent:warning-24-regular" class="w-6 h-6 mt-0.5 flex-shrink-0" />
          <div>
            <h3 class="font-medium text-orange-900">ปีการศึกษานี้ยังไม่มีภาคเรียน</h3>
            <p class="text-sm mt-1">ให้ไปเพิ่มที่เมนูตั้งค่าปีการศึกษาก่อน</p>
          </div>
        </div>
      </div>

      <!-- View Mode & Selector -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row gap-4 mb-4">
          <div class="flex-1 flex flex-col sm:flex-row gap-4">
            <select
              v-model="selectedAcademicYear"
              class="w-full sm:w-48 px-4 py-2.5 min-h-[44px] bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
            >
              <option :value="null" disabled>เลือกปีการศึกษา</option>
              <option v-for="year in academicYears" :key="year.id" :value="year.id">
                {{ year.name }}
              </option>
            </select>
            
            <select
              v-model="selectedSemester"
              :disabled="availableSemesters.length === 0"
              class="w-full sm:w-48 px-4 py-2.5 min-h-[44px] bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
            >
              <option :value="null" disabled>เลือกภาคเรียน</option>
              <option v-for="sem in availableSemesters" :key="sem.id" :value="sem.id">
                {{ sem.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-4">
          <!-- View Mode Toggle -->
          <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1 flex-shrink-0">
            <button class="min-h-[44px] sm:min-h-0 min-w-0 flex-1 break-words px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              @click="viewMode = 'classroom'"
              :class="viewMode === 'classroom' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400'"
            >
              <Icon icon="fluent:building-24-regular" class="w-4 h-4 inline mr-1" />
              ห้องเรียน
            </button>
            <button class="min-h-[44px] sm:min-h-0 min-w-0 flex-1 break-words px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              @click="viewMode = 'teacher'"
              :class="viewMode === 'teacher' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-600 dark:text-gray-400'"
            >
              <Icon icon="fluent:person-24-regular" class="w-4 h-4 inline mr-1" />
              ครูผู้สอน
            </button>
          </div>
          
          <!-- Selector -->
          <div class="flex-1 min-w-0">
            <CommonSearchableSelect
              v-if="viewMode === 'classroom'"
              v-model="selectedClassroom"
              :options="classrooms"
              option-value="id"
              option-label="name"
              placeholder="เลือกห้องเรียน"
              search-placeholder="ค้นหาห้องเรียน"
              class="w-full sm:w-64"
            />
            <CommonSearchableSelect
              v-else
              v-model="selectedTeacher"
              :options="teachers"
              option-value="user_id"
              :option-label="(teacher: any) => teacher.member_name || teacher.user?.name || '-'"
              placeholder="เลือกครูผู้สอน"
              search-placeholder="ค้นหาชื่อครู"
              class="w-full sm:w-64"
            />
          </div>
        </div>
      </div>

      <!-- Timetable Grid -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div v-if="isLoadingSchedule" class="flex items-center justify-center py-20">
          <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
        </div>
        
        <div v-else-if="!selectedClassroom && viewMode === 'classroom'" class="p-12 text-center">
          <Icon icon="fluent:building-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">เลือกห้องเรียน</h3>
          <p class="text-gray-500 dark:text-gray-400">กรุณาเลือกห้องเรียนเพื่อดูตารางเรียน</p>
        </div>
        
        <div v-else-if="!selectedTeacher && viewMode === 'teacher'" class="p-12 text-center">
          <Icon icon="fluent:person-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">เลือกครูผู้สอน</h3>
          <p class="text-gray-500 dark:text-gray-400">กรุณาเลือกครูเพื่อดูตารางสอน</p>
        </div>
        
        <div v-else-if="gridRows.length === 0" class="p-12 text-center">
          <Icon icon="fluent:calendar-empty-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีคาบในตารางนี้</h3>
          <p class="text-gray-500 dark:text-gray-400 mb-4">เพิ่มตารางเรียนเพื่อเริ่มต้น</p>
          <button
            v-if="canManage"
            :disabled="!selectedSemester"
            @click="openCreateModal()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors"
          >
            <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
            <span>เพิ่มตารางเรียน</span>
          </button>
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="w-full min-w-[800px]">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-700/50">
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-20 whitespace-nowrap">
                  เวลา
                </th>
                <th
                  v-for="day in gridDays"
                  :key="day.value"
                  class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap"
                >
                  {{ day.label }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr
                v-for="row in gridRows"
                :key="row.key"
                :class="{'bg-amber-50/60 dark:bg-amber-900/10': row.type === 'break' || row.type === 'lunch'}"
              >
                <td class="px-3 py-2 border-r border-gray-100 dark:border-gray-700 align-top whitespace-nowrap">
                  <p class="text-sm font-medium" :class="row.type === 'outside' ? 'text-orange-600 dark:text-orange-400' : 'text-gray-700 dark:text-gray-300'">{{ row.label }}</p>
                  <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ row.start }} - {{ row.end }}</p>
                </td>
                <td
                  v-for="day in gridDays"
                  :key="`${day.value}-${row.key}`"
                  class="px-2 py-2 min-h-[80px] h-full align-top relative"
                  @click="canManage && selectedSemester && row.type !== 'break' && row.type !== 'lunch' ? openCreateModal(day.value, row.start, row.end) : null"
                >
                  <div v-if="getScheduleCell(day.value, row).length > 0" class="flex flex-col gap-2 h-full">
                    <div
                      v-for="schedule in getScheduleCell(day.value, row)"
                      :key="schedule.id"
                      :class="[
                        'p-2 rounded-lg border flex flex-col justify-between h-full min-h-[80px]',
                        canManage ? 'cursor-pointer hover:shadow-md transition-all group' : '',
                        getScheduleColor(schedule)
                      ]"
                      @click.stop="canManage ? openEditModal(schedule, day.value) : null"
                    >
                      <div>
                        <p class="text-xs font-semibold text-gray-900 dark:text-white break-words line-clamp-2">
                          {{ schedule.title || schedule.course?.name }}
                        </p>
                        <p v-if="schedule.course?.code" class="text-[10px] text-gray-700 dark:text-gray-300 truncate mt-0.5">
                          {{ schedule.course.code }}
                        </p>
                      </div>
                      
                      <div class="mt-2">
                        <p class="text-[10px] text-gray-600 dark:text-gray-400 truncate">
                          {{ schedule.start_time }} - {{ schedule.end_time }}
                        </p>
                        <p v-if="viewMode === 'classroom' && schedule.teacher" class="text-[10px] text-gray-500 dark:text-gray-400 truncate mt-0.5">
                          <Icon icon="fluent:person-24-regular" class="w-3 h-3 inline align-middle mr-0.5" />
                          {{ schedule.teacher?.name }}
                        </p>
                        <p v-if="viewMode === 'teacher' && schedule.classroom" class="text-[10px] text-gray-500 dark:text-gray-400 truncate mt-0.5">
                          <Icon icon="fluent:building-24-regular" class="w-3 h-3 inline align-middle mr-0.5" />
                          {{ schedule.classroom?.name }}
                        </p>
                        <p v-if="schedule.room" class="text-[10px] text-gray-500 dark:text-gray-400 truncate mt-0.5">
                          <Icon icon="fluent:location-24-regular" class="w-3 h-3 inline align-middle mr-0.5" />
                          {{ schedule.room }}
                        </p>
                      </div>
                    </div>
                  </div>
                  <div
                    v-else
                    :class="[
                      'h-full w-full min-h-[80px] rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-700 flex items-center justify-center',
                      canManage && selectedSemester && row.type !== 'break' && row.type !== 'lunch' ? 'hover:border-primary-400 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors cursor-pointer' : ''
                    ]"
                  >
                    <Icon v-if="canManage && selectedSemester && row.type !== 'break' && row.type !== 'lunch'" icon="fluent:add-24-regular" class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showCreateModal = false; showEditModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-hidden flex flex-col">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ showEditModal ? 'แก้ไขตารางเรียน' : 'เพิ่มตารางเรียน' }}
            </h3>
            <button @click="showCreateModal = false; showEditModal = false" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <div class="p-5 overflow-y-auto flex-1">
            <form @submit.prevent="showEditModal ? updateSchedule() : createSchedule()" class="space-y-4">
              <!-- Entry Type -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ประเภทคาบ *</label>
                <select
                  v-model="scheduleForm.entry_type"
                  @change="handleEntryTypeChange"
                  required
                  class="w-full px-4 py-2.5 min-h-[44px] border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                >
                  <option value="course">คาบเรียน (วิชา)</option>
                  <option value="activity">กิจกรรม</option>
                  <option value="break">พัก</option>
                  <option value="exam">สอบ</option>
                </select>
              </div>

              <!-- Course (if course) -->
              <div v-if="scheduleForm.entry_type === 'course'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">คอร์ส</label>
                <CommonSearchableSelect
                  v-model="scheduleForm.course_id"
                  :options="courses"
                  option-value="id"
                  :option-label="(course: any) => (course.code ? course.code + ' - ' : '') + course.name"
                  placeholder="เลือกคอร์สเรียน"
                  search-placeholder="ค้นหาคอร์ส/รหัสวิชา"
                />
                <p class="mt-1 text-xs text-gray-500">ปล่อยว่างได้ถ้าต้องการกรอกเฉพาะชื่อคาบ</p>
              </div>

              <!-- Title -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                  ชื่อคาบ <span v-if="scheduleForm.entry_type !== 'course'">*</span>
                </label>
                <input
                  v-model="scheduleForm.title"
                  type="text"
                  :placeholder="scheduleForm.entry_type === 'course' ? 'เช่น ชุมนุม (ไม่บังคับ)' : 'เช่น พักกลางวัน, กิจกรรมชมรม'"
                  :required="scheduleForm.entry_type !== 'course'"
                  class="w-full px-4 py-2.5 min-h-[44px] border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                />
              </div>
              
              <!-- Classroom (if teacher view) -->
              <div v-if="viewMode === 'teacher'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ห้องเรียน *</label>
                <CommonSearchableSelect
                  v-model="scheduleForm.classroom_id"
                  :options="classrooms"
                  option-value="id"
                  option-label="name"
                  placeholder="เลือกห้องเรียน"
                  search-placeholder="ค้นหาห้องเรียน"
                />
              </div>
              
              <!-- Teacher (if classroom view) -->
              <div v-if="viewMode === 'classroom'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ครูผู้สอน *</label>
                <CommonSearchableSelect
                  v-model="scheduleForm.teacher_id"
                  :options="teachers"
                  option-value="user_id"
                  :option-label="(teacher: any) => teacher.member_name || teacher.user?.name || '-'"
                  placeholder="เลือกครู"
                  search-placeholder="ค้นหาชื่อครู"
                />
              </div>
              
              <!-- Day -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">วัน *</label>
                <select
                  v-model="scheduleForm.day_of_week"
                  class="w-full px-4 py-2.5 min-h-[44px] border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                >
                  <option v-for="day in allDays" :key="day.value" :value="day.value">
                    {{ day.label }}
                  </option>
                </select>
              </div>
              
              <!-- Time -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">เวลาเริ่ม *</label>
                  <input
                    v-model="scheduleForm.start_time"
                    type="time"
                    required
                    class="w-full px-4 py-2.5 min-h-[44px] border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">เวลาสิ้นสุด *</label>
                  <input
                    v-model="scheduleForm.end_time"
                    type="time"
                    required
                    class="w-full px-4 py-2.5 min-h-[44px] border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                  />
                </div>
              </div>
              
              <!-- Room -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">สถานที่/ห้อง</label>
                <input
                  v-model="scheduleForm.room"
                  type="text"
                  placeholder="เช่น ห้อง 101"
                  class="w-full px-4 py-2.5 min-h-[44px] border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                />
              </div>
              
              <div class="flex flex-col sm:flex-row gap-3 pt-4">
                <button
                  type="submit"
                  :disabled="isSubmitting"
                  class="min-h-[44px] sm:min-h-0 sm:flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50 flex items-center justify-center gap-2 order-1"
                >
                  <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                  <span>{{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึก' }}</span>
                </button>
                <button
                  v-if="showEditModal"
                  type="button"
                  @click="deleteSchedule"
                  class="min-h-[44px] sm:min-h-0 sm:flex-1 px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-xl font-medium transition-colors order-3 sm:order-2"
                >
                  ลบคาบนี้
                </button>
                <button
                  type="button"
                  @click="showCreateModal = false; showEditModal = false"
                  class="min-h-[44px] sm:min-h-0 sm:flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors order-2 sm:order-3"
                >
                  ยกเลิก
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Teleport>

    <SchoolScheduleBulkFillModal
      v-if="showBulkModal && selectedClassroomData && selectedSemester"
      :academy-id="academyId!"
      :semester-id="selectedSemester"
      :classroom="selectedClassroomData"
      :period-rows="bulkPeriodRows"
      :courses="courses"
      :teachers="teachers"
      :occupied-slots="occupiedSlots"
      :day-options="gridDays"
      @close="showBulkModal = false"
      @created="fetchTimetable"
    />
    <!-- ชื่อ component ต้องมีคำนำหน้าโฟลเดอร์ (School…) ตาม pathPrefix ที่เป็นค่าเริ่มต้นของ Nuxt
         ใช้ชื่อสั้น <ScheduleBulkFillModal> จะ resolve ไม่เจอแล้วโมดัลไม่ขึ้นแบบเงียบ ๆ -->
  </div>
</template>

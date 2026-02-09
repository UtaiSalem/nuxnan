<script setup lang="ts">
/**
 * Academy Admin - Schedule Management
 * หน้าจัดการตารางเรียน
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
const isLoading = ref(true)
const isLoadingSchedule = ref(false)

// View mode
const viewMode = ref<'classroom' | 'teacher'>('classroom')
const selectedClassroom = ref<number | null>(null)
const selectedTeacher = ref<number | null>(null)

// Data
const classrooms = ref<any[]>([])
const teachers = ref<any[]>([])
const timetable = ref<any[]>([])
const schedules = ref<any[]>([])

// Time slots for the grid
const timeSlots = [
  '08:00', '09:00', '10:00', '11:00', '12:00',
  '13:00', '14:00', '15:00', '16:00'
]

const days = [
  { value: 1, label: 'จันทร์', short: 'จ' },
  { value: 2, label: 'อังคาร', short: 'อ' },
  { value: 3, label: 'พุธ', short: 'พ' },
  { value: 4, label: 'พฤหัสบดี', short: 'พฤ' },
  { value: 5, label: 'ศุกร์', short: 'ศ' },
]

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Modal
const showCreateModal = ref(false)
const showEditModal = ref(false)
const selectedSchedule = ref<any>(null)

// Form
const scheduleForm = ref({
  classroom_id: null as number | null,
  teacher_id: null as number | null,
  subject_id: null as number | null,
  day_of_week: 1,
  start_time: '08:00',
  end_time: '09:00',
  room: ''
})
const subjects = ref<any[]>([])
const isSubmitting = ref(false)
const formErrors = ref<Record<string, string[]>>({})

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value && !can('academy.view')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await Promise.all([
        fetchClassrooms(),
        fetchTeachers(),
        fetchSubjects()
      ])
      
      // Select first classroom if available
      if (classrooms.value.length > 0) {
        selectedClassroom.value = classrooms.value[0].id
        await fetchTimetable()
      }
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
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/classrooms`, {
      per_page: 100
    })
    if (response.success) {
      classrooms.value = response.classrooms || []
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
      role: 'teacher',
      status: 2,
      per_page: 100
    })
    if (response.success) {
      teachers.value = response.members || []
    }
  } catch (err) {
    console.error('Failed to fetch teachers:', err)
  }
}

// Fetch subjects
const fetchSubjects = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/curriculums/subjects`, {
      per_page: 100
    })
    if (response.success) {
      subjects.value = response.data || response.subjects || []
    }
  } catch (err) {
    console.error('Failed to fetch subjects:', err)
  }
}

// Fetch timetable
const fetchTimetable = async () => {
  if (!academyId.value) return
  
  isLoadingSchedule.value = true
  try {
    const params: any = {}
    if (viewMode.value === 'classroom' && selectedClassroom.value) {
      params.classroom_id = selectedClassroom.value
    } else if (viewMode.value === 'teacher' && selectedTeacher.value) {
      params.teacher_id = selectedTeacher.value
    } else {
      return
    }
    
    const response: any = await api.get(`/api/academies/${academyName.value}/schedules/timetable`, params)
    if (response.success) {
      timetable.value = response.data?.timetable || []
    }
  } catch (err) {
    console.error('Failed to fetch timetable:', err)
  } finally {
    isLoadingSchedule.value = false
  }
}

// Watch for view changes
watch([viewMode, selectedClassroom, selectedTeacher], () => {
  fetchTimetable()
})

// Get schedule for a specific day and time
const getScheduleAt = (dayValue: number, time: string) => {
  const dayData = timetable.value.find((d: any) => d.day === dayValue)
  if (!dayData?.schedules) return null
  
  return dayData.schedules.find((s: any) => {
    const startHour = parseInt(s.start_time.split(':')[0])
    const timeHour = parseInt(time.split(':')[0])
    return startHour === timeHour
  })
}

// Get color for subject
const getSubjectColor = (subjectId: number) => {
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
  return colors[subjectId % colors.length]
}

// Open create modal
const openCreateModal = (day?: number, time?: string) => {
  scheduleForm.value = {
    classroom_id: viewMode.value === 'classroom' ? selectedClassroom.value : null,
    teacher_id: viewMode.value === 'teacher' ? selectedTeacher.value : null,
    subject_id: null,
    day_of_week: day || 1,
    start_time: time || '08:00',
    end_time: getEndTime(time || '08:00'),
    room: ''
  }
  formErrors.value = {}
  showCreateModal.value = true
}

const getEndTime = (startTime: string) => {
  const hour = parseInt(startTime.split(':')[0]) + 1
  return `${hour.toString().padStart(2, '0')}:00`
}

// Open edit modal
const openEditModal = (schedule: any) => {
  selectedSchedule.value = schedule
  scheduleForm.value = {
    classroom_id: schedule.classroom?.id || null,
    teacher_id: schedule.teacher?.id || null,
    subject_id: schedule.subject?.id || null,
    day_of_week: schedule.day || 1,
    start_time: schedule.start_time,
    end_time: schedule.end_time,
    room: schedule.room || ''
  }
  formErrors.value = {}
  showEditModal.value = true
}

// Create schedule
const createSchedule = async () => {
  if (!academyId.value) return
  
  isSubmitting.value = true
  formErrors.value = {}
  
  try {
    const response: any = await api.post(`/api/academies/${academyName.value}/schedules`, {
      classroom_id: scheduleForm.value.classroom_id,
      teacher_id: scheduleForm.value.teacher_id,
      subject_id: scheduleForm.value.subject_id,
      day_of_week: scheduleForm.value.day_of_week,
      start_time: scheduleForm.value.start_time,
      end_time: scheduleForm.value.end_time,
      room: scheduleForm.value.room || undefined
    })
    
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
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถเพิ่มตารางเรียนได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Update schedule
const updateSchedule = async () => {
  if (!academyId.value || !selectedSchedule.value) return
  
  isSubmitting.value = true
  formErrors.value = {}
  
  try {
    const response: any = await api.put(
      `/api/academies/${academyName.value}/schedules/${selectedSchedule.value.id}`,
      {
        classroom_id: scheduleForm.value.classroom_id,
        teacher_id: scheduleForm.value.teacher_id,
        subject_id: scheduleForm.value.subject_id,
        day_of_week: scheduleForm.value.day_of_week,
        start_time: scheduleForm.value.start_time,
        end_time: scheduleForm.value.end_time,
        room: scheduleForm.value.room || undefined
      }
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
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถอัปเดตตารางเรียนได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Delete schedule
const deleteSchedule = async (schedule: any) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันการลบ',
    text: `คุณต้องการลบรายการ "${schedule.subject?.name}" หรือไม่?`,
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })
  
  if (result.isConfirmed) {
    try {
      const response: any = await api.delete(
        `/api/academies/${academyName.value}/schedules/${schedule.id}`
      )
      
      if (response.success) {
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
        text: err.response?.data?.message || 'ไม่สามารถลบได้'
      })
    }
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
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ตารางเรียน</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการตารางเรียนของห้องเรียนและครู</p>
        </div>
        <button
          @click="openCreateModal()"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors"
        >
          <Icon name="fluent:add-24-filled" class="w-5 h-5" />
          <span>เพิ่มตารางเรียน</span>
        </button>
      </div>

      <!-- View Mode & Selector -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row gap-4">
          <!-- View Mode Toggle -->
          <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
            <button
              @click="viewMode = 'classroom'"
              :class="[
                'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                viewMode === 'classroom'
                  ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                  : 'text-gray-600 dark:text-gray-400'
              ]"
            >
              <Icon name="fluent:building-24-regular" class="w-4 h-4 inline mr-1" />
              ห้องเรียน
            </button>
            <button
              @click="viewMode = 'teacher'"
              :class="[
                'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                viewMode === 'teacher'
                  ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm'
                  : 'text-gray-600 dark:text-gray-400'
              ]"
            >
              <Icon name="fluent:person-24-regular" class="w-4 h-4 inline mr-1" />
              ครูผู้สอน
            </button>
          </div>
          
          <!-- Selector -->
          <div class="flex-1">
            <select
              v-if="viewMode === 'classroom'"
              v-model="selectedClassroom"
              class="w-full sm:w-64 px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
            >
              <option :value="null" disabled>เลือกห้องเรียน</option>
              <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">
                {{ classroom.name }}
              </option>
            </select>
            <select
              v-else
              v-model="selectedTeacher"
              class="w-full sm:w-64 px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
            >
              <option :value="null" disabled>เลือกครูผู้สอน</option>
              <option v-for="teacher in teachers" :key="teacher.user_id" :value="teacher.user_id">
                {{ teacher.user?.name }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Timetable Grid -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div v-if="isLoadingSchedule" class="flex items-center justify-center py-20">
          <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
        </div>
        
        <div v-else-if="!selectedClassroom && viewMode === 'classroom'" class="p-12 text-center">
          <Icon name="fluent:building-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">เลือกห้องเรียน</h3>
          <p class="text-gray-500 dark:text-gray-400">กรุณาเลือกห้องเรียนเพื่อดูตารางเรียน</p>
        </div>
        
        <div v-else-if="!selectedTeacher && viewMode === 'teacher'" class="p-12 text-center">
          <Icon name="fluent:person-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">เลือกครูผู้สอน</h3>
          <p class="text-gray-500 dark:text-gray-400">กรุณาเลือกครูเพื่อดูตารางสอน</p>
        </div>
        
        <div v-else class="overflow-x-auto">
          <table class="w-full min-w-[800px]">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-700/50">
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-20">
                  เวลา
                </th>
                <th
                  v-for="day in days"
                  :key="day.value"
                  class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"
                >
                  {{ day.label }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-for="time in timeSlots" :key="time">
                <td class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-700">
                  {{ time }}
                </td>
                <td
                  v-for="day in days"
                  :key="`${day.value}-${time}`"
                  class="px-2 py-2 h-20 relative"
                  @click="!getScheduleAt(day.value, time) && openCreateModal(day.value, time)"
                >
                  <div
                    v-if="getScheduleAt(day.value, time)"
                    :class="[
                      'p-2 rounded-lg border cursor-pointer transition-all hover:shadow-md group',
                      getSubjectColor(getScheduleAt(day.value, time)?.subject?.id || 0)
                    ]"
                    @click.stop="openEditModal(getScheduleAt(day.value, time))"
                  >
                    <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">
                      {{ getScheduleAt(day.value, time)?.subject?.name }}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                      {{ getScheduleAt(day.value, time)?.start_time }} - {{ getScheduleAt(day.value, time)?.end_time }}
                    </p>
                    <p v-if="viewMode === 'classroom' && getScheduleAt(day.value, time)?.teacher" class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1">
                      <Icon name="fluent:person-24-regular" class="w-3 h-3 inline" />
                      {{ getScheduleAt(day.value, time)?.teacher?.name }}
                    </p>
                    <p v-if="viewMode === 'teacher' && getScheduleAt(day.value, time)?.classroom" class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1">
                      <Icon name="fluent:building-24-regular" class="w-3 h-3 inline" />
                      {{ getScheduleAt(day.value, time)?.classroom?.name }}
                    </p>
                    
                    <!-- Delete button on hover -->
                    <button
                      @click.stop="deleteSchedule(getScheduleAt(day.value, time))"
                      class="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                      <Icon name="fluent:dismiss-12-regular" class="w-3 h-3" />
                    </button>
                  </div>
                  <div
                    v-else
                    class="h-full w-full rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-700 hover:border-primary-400 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors cursor-pointer flex items-center justify-center"
                  >
                    <Icon name="fluent:add-24-regular" class="w-4 h-4 text-gray-300 dark:text-gray-600" />
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
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ showEditModal ? 'แก้ไขตารางเรียน' : 'เพิ่มตารางเรียน' }}
            </h3>
            <button @click="showCreateModal = false; showEditModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <form @submit.prevent="showEditModal ? updateSchedule() : createSchedule()" class="p-5 space-y-4">
            <!-- Subject -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">วิชา *</label>
              <select
                v-model="scheduleForm.subject_id"
                required
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              >
                <option :value="null" disabled>เลือกวิชา</option>
                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                  {{ subject.subject_code }} - {{ subject.name_th || subject.name }}
                </option>
              </select>
              <p v-if="formErrors.subject_id" class="mt-1 text-sm text-red-500">{{ formErrors.subject_id[0] }}</p>
            </div>
            
            <!-- Classroom (if teacher view) -->
            <div v-if="viewMode === 'teacher'">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ห้องเรียน *</label>
              <select
                v-model="scheduleForm.classroom_id"
                required
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              >
                <option :value="null" disabled>เลือกห้องเรียน</option>
                <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">
                  {{ classroom.name }}
                </option>
              </select>
            </div>
            
            <!-- Teacher (if classroom view) -->
            <div v-if="viewMode === 'classroom'">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ครูผู้สอน *</label>
              <select
                v-model="scheduleForm.teacher_id"
                required
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              >
                <option :value="null" disabled>เลือกครู</option>
                <option v-for="teacher in teachers" :key="teacher.user_id" :value="teacher.user_id">
                  {{ teacher.user?.name }}
                </option>
              </select>
            </div>
            
            <!-- Day -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">วัน *</label>
              <select
                v-model="scheduleForm.day_of_week"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              >
                <option v-for="day in days" :key="day.value" :value="day.value">
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
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">เวลาสิ้นสุด *</label>
                <input
                  v-model="scheduleForm.end_time"
                  type="time"
                  required
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                />
              </div>
            </div>
            
            <!-- Room -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ห้องเรียน/สถานที่</label>
              <input
                v-model="scheduleForm.room"
                type="text"
                placeholder="เช่น ห้อง 101, ห้องปฏิบัติการ"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              />
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
                <span>{{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึก' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

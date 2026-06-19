<script setup lang="ts">
/**
 * Classroom Detail - Student Management
 * หน้าจัดการนักเรียนในห้องเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)
const classroomId = computed(() => route.params.id as string)

// State
const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const classroom = ref<any>(null)
const isLoading = ref(true)
const isSaving = ref(false)

// Students
const students = ref<any[]>([])
const availableStudents = ref<any[]>([])
const searchQuery = ref('')

// Modal
const showAddModal = ref(false)
const selectedStudents = ref<number[]>([])
const searchAvailable = ref('')

const filteredStudents = computed(() => {
  if (!searchQuery.value) return students.value
  
  const query = searchQuery.value.toLowerCase()
  return students.value.filter((s: any) => 
    s.student?.student_id?.toLowerCase().includes(query) ||
    s.student?.first_name_th?.toLowerCase().includes(query) ||
    s.student?.last_name_th?.toLowerCase().includes(query)
  )
})

const filteredAvailable = computed(() => {
  if (!searchAvailable.value) return availableStudents.value
  
  const query = searchAvailable.value.toLowerCase()
  return availableStudents.value.filter((s: any) => 
    s.student_id?.toLowerCase().includes(query) ||
    s.first_name_th?.toLowerCase().includes(query) ||
    s.last_name_th?.toLowerCase().includes(query)
  )
})

const { isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
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
  await fetchClassroom()
}

const fetchClassroom = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/classrooms/${classroomId.value}`)
    if (res.success) {
      classroom.value = res.classroom
      students.value = res.classroom.classroom_students || []
    }
  } catch (err) {
    console.error('Failed to fetch classroom:', err)
  }
}

const fetchAvailableStudents = async () => {
  try {
    // Get all students from academy that are not in this classroom
    const res: any = await api.get(`/api/academies/${academyId.value}/students?not_in_classroom=${classroomId.value}`)
    if (res.success) {
      availableStudents.value = res.students || []
    }
  } catch (err) {
    console.error('Failed to fetch available students:', err)
  }
}

const openAddModal = async () => {
  selectedStudents.value = []
  searchAvailable.value = ''
  await fetchAvailableStudents()
  showAddModal.value = true
}

const addStudents = async () => {
  if (selectedStudents.value.length === 0) {
    alert('กรุณาเลือกนักเรียน')
    return
  }

  isSaving.value = true
  try {
    await api.post(`/api/academies/${academyId.value}/classrooms/${classroomId.value}/students`, {
      student_ids: selectedStudents.value,
    })
    
    showAddModal.value = false
    await fetchClassroom()
  } catch (err: any) {
    console.error('Failed to add students:', err)
    alert(err.message || 'เกิดข้อผิดพลาด')
  } finally {
    isSaving.value = false
  }
}

const removeStudent = async (studentId: number) => {
  if (!confirm('ต้องการลบนักเรียนออกจากห้องเรียนหรือไม่?')) return
  
  try {
    await api.delete(`/api/academies/${academyId.value}/classrooms/${classroomId.value}/students/${studentId}`)
    await fetchClassroom()
  } catch (err) {
    console.error('Failed to remove student:', err)
    alert('ไม่สามารถลบได้')
  }
}

const updateStudentNumber = async (classroomStudent: any, number: number) => {
  try {
    await api.patch(`/api/academies/${academyId.value}/classrooms/${classroomId.value}/students/${classroomStudent.student_id}/number`, {
      student_number: number,
    })
  } catch (err) {
    console.error('Failed to update student number:', err)
  }
}

const toggleSelectStudent = (studentId: number) => {
  const index = selectedStudents.value.indexOf(studentId)
  if (index === -1) {
    selectedStudents.value.push(studentId)
  } else {
    selectedStudents.value.splice(index, 1)
  }
}

const selectAllFiltered = () => {
  selectedStudents.value = filteredAvailable.value.map((s: any) => s.id)
}

const deselectAll = () => {
  selectedStudents.value = []
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <NuxtLink :to="`/academies/${academyName}/admin/gradebook/classrooms`" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
              <Icon icon="fluent:arrow-left-24-filled" class="w-5 h-5" />
            </NuxtLink>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
              <Icon icon="fluent:building-24-filled" class="w-7 h-7 text-purple-500" />
              {{ classroom?.name }}
            </h1>
          </div>
          <p class="text-gray-600 dark:text-gray-400">
            ระดับชั้น {{ classroom?.grade_level }} | {{ students.length }} นักเรียน
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="openAddModal"
            class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors flex items-center gap-2"
          >
            <Icon icon="fluent:person-add-24-filled" class="w-5 h-5" />
            เพิ่มนักเรียน
          </button>
        </div>
      </div>

      <!-- Classroom Info -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div>
            <div class="text-sm text-gray-600 dark:text-gray-400">ปีการศึกษา</div>
            <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ classroom?.academic_year_info?.name || classroom?.academic_year }}</div>
          </div>
          <div>
            <div class="text-sm text-gray-600 dark:text-gray-400">ครูประจำชั้น</div>
            <div class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ classroom?.homeroom_teacher?.name || 'ยังไม่กำหนด' }}
            </div>
          </div>
          <div>
            <div class="text-sm text-gray-600 dark:text-gray-400">ห้องเรียน</div>
            <div class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ classroom?.room_location || '-' }}
            </div>
          </div>
          <div>
            <div class="text-sm text-gray-600 dark:text-gray-400">จำนวนนักเรียน</div>
            <div class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ students.length }} / {{ classroom?.capacity || '-' }}
            </div>
          </div>
        </div>
      </div>

      <!-- Search -->
      <div class="relative">
        <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="ค้นหานักเรียน..."
          class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
        />
      </div>

      <!-- Students List -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div v-if="filteredStudents.length === 0" class="p-12 text-center">
          <Icon icon="fluent:people-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีนักเรียน</h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">เพิ่มนักเรียนเข้าห้องเรียน</p>
          <button
            @click="openAddModal"
            class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors"
          >
            <Icon icon="fluent:person-add-24-filled" class="w-5 h-5" />
            เพิ่มนักเรียน
          </button>
        </div>

        <table v-else class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                เลขที่
              </th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                รหัสนักเรียน
              </th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                ชื่อ-นามสกุล
              </th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                สถานะ
              </th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                จัดการ
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="(cs, index) in filteredStudents" :key="cs.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-6 py-4 whitespace-nowrap">
                <input
                  type="number"
                  :value="cs.student_number || index + 1"
                  @change="updateStudentNumber(cs, Number(($event.target as HTMLInputElement).value))"
                  class="w-16 px-2 py-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-center focus:ring-2 focus:ring-primary-500"
                />
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                {{ cs.student?.student_id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center">
                    <span class="text-sm font-medium text-primary-600 dark:text-primary-400">
                      {{ cs.student?.first_name_th?.[0] }}
                    </span>
                  </div>
                  <div>
                    <div class="font-medium text-gray-900 dark:text-white">
                      {{ cs.student?.prefix }}{{ cs.student?.first_name_th }} {{ cs.student?.last_name_th }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ cs.student?.first_name_en }} {{ cs.student?.last_name_en }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[
                  'px-2 py-1 text-xs font-medium rounded-full',
                  cs.status === 'active' 
                    ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400'
                    : cs.status === 'transferred'
                    ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-400'
                    : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-400'
                ]">
                  {{ cs.status === 'active' ? 'กำลังเรียน' : cs.status === 'transferred' ? 'ย้าย' : cs.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <button
                  @click="removeStudent(cs.student_id)"
                  class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                >
                  <Icon icon="fluent:person-delete-24-regular" class="w-5 h-5" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add Students Modal -->
    <Teleport to="body">
      <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="showAddModal = false"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] flex flex-col">
          <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">เพิ่มนักเรียน</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              เลือกนักเรียนที่ต้องการเพิ่มเข้าห้อง {{ classroom?.name }}
            </p>
          </div>
          
          <div class="p-6 flex-1 overflow-y-auto">
            <div class="mb-4">
              <div class="relative">
                <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  v-model="searchAvailable"
                  type="text"
                  placeholder="ค้นหานักเรียน..."
                  class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </div>

            <div class="flex items-center justify-between mb-3">
              <span class="text-sm text-gray-600 dark:text-gray-400">
                เลือกแล้ว {{ selectedStudents.length }} คน
              </span>
              <div class="flex items-center gap-2">
                <button @click="selectAllFiltered" class="text-sm text-primary-600 hover:underline">
                  เลือกทั้งหมด
                </button>
                <button @click="deselectAll" class="text-sm text-gray-500 hover:underline">
                  ยกเลิกทั้งหมด
                </button>
              </div>
            </div>

            <div v-if="filteredAvailable.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
              ไม่พบนักเรียน
            </div>

            <div v-else class="space-y-2 max-h-80 overflow-y-auto">
              <div
                v-for="student in filteredAvailable"
                :key="student.id"
                @click="toggleSelectStudent(student.id)"
                :class="[
                  'p-4 rounded-lg border cursor-pointer transition-all',
                  selectedStudents.includes(student.id)
                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                ]"
              >
                <div class="flex items-center gap-3">
                  <div :class="[
                    'w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                    selectedStudents.includes(student.id)
                      ? 'border-primary-500 bg-primary-500'
                      : 'border-gray-300 dark:border-gray-600'
                  ]">
                    <Icon v-if="selectedStudents.includes(student.id)" icon="fluent:checkmark-12-filled" class="w-3 h-3 text-white" />
                  </div>
                  <div class="flex-1">
                    <div class="font-medium text-gray-900 dark:text-white">
                      {{ student.prefix }}{{ student.first_name_th }} {{ student.last_name_th }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      รหัส: {{ student.student_id }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
            <button
              @click="showAddModal = false"
              class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              ยกเลิก
            </button>
            <button
              @click="addStudents"
              :disabled="isSaving || selectedStudents.length === 0"
              class="flex-1 px-4 py-2 bg-primary-500 hover:bg-primary-600 disabled:bg-gray-400 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
            >
              <Icon v-if="isSaving" icon="fluent:spinner-ios-20-filled" class="w-5 h-5 animate-spin" />
              เพิ่มนักเรียน {{ selectedStudents.length > 0 ? `(${selectedStudents.length})` : '' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

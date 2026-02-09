<script setup lang="ts">
/**
 * Academy Student Management
 * หน้าจัดการนักเรียนแบบครบวงจร - รวมบัตรนักเรียน ห้องเรียน และผลการเรียน
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

// Data
const students = ref<any[]>([])
const classrooms = ref<any[]>([])
const selectedClassroom = ref<string>('')
const searchQuery = ref('')
const selectedStudent = ref<any>(null)
const showStudentModal = ref(false)

// Pagination
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = 20

const filteredStudents = computed(() => {
  let result = students.value

  if (selectedClassroom.value) {
    result = result.filter((s: any) => s.classroom_id == selectedClassroom.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter((s: any) =>
      s.user?.displayname?.toLowerCase().includes(query) ||
      s.student_number?.toLowerCase().includes(query) ||
      s.student_card?.card_number?.toLowerCase().includes(query)
    )
  }

  return result
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
    fetchStudents(),
  ])
}

const fetchClassrooms = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/classrooms`)
    if (res.success) {
      classrooms.value = res.classrooms || []
    }
  } catch (err) {
    console.error('Failed to fetch classrooms:', err)
  }
}

const fetchStudents = async () => {
  try {
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: perPage.toString(),
      include: 'user,classroom,studentCard,latestGrades'
    })
    
    if (selectedClassroom.value) {
      params.append('classroom_id', selectedClassroom.value)
    }
    
    const res: any = await api.get(`/api/academies/${academyId.value}/students?${params}`)
    if (res.success) {
      students.value = res.students?.data || res.students || []
      totalPages.value = res.students?.last_page || 1
    }
  } catch (err) {
    console.error('Failed to fetch students:', err)
  }
}

const openStudentDetail = (student: any) => {
  selectedStudent.value = student
  showStudentModal.value = true
}

const goToStudentCard = (student: any) => {
  if (student.student_card?.id) {
    navigateTo(`/student-card/${student.student_card.id}`)
  }
}

const goToTranscript = (student: any) => {
  navigateTo(`/academies/${academyName.value}/admin/gradebook/students/${student.id}/transcript`)
}

const getGradeColor = (gpa: number | null) => {
  if (!gpa) return 'text-gray-500'
  if (gpa >= 3.5) return 'text-green-600 dark:text-green-400'
  if (gpa >= 3.0) return 'text-blue-600 dark:text-blue-400'
  if (gpa >= 2.5) return 'text-yellow-600 dark:text-yellow-400'
  if (gpa >= 2.0) return 'text-orange-600 dark:text-orange-400'
  return 'text-red-600 dark:text-red-400'
}

const getStatusBadge = (status: string) => {
  const badges: Record<string, { label: string, color: string }> = {
    'active': { label: 'กำลังศึกษา', color: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400' },
    'graduated': { label: 'จบการศึกษา', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400' },
    'transferred': { label: 'ย้ายออก', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400' },
    'dropped': { label: 'พ้นสภาพ', color: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400' },
  }
  return badges[status] || { label: status, color: 'bg-gray-100 text-gray-800' }
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
            <NuxtLink :to="`/academies/${academyName}/admin/gradebook`" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
              <Icon icon="fluent:arrow-left-24-filled" class="w-5 h-5" />
            </NuxtLink>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
              <Icon icon="fluent:people-24-filled" class="w-7 h-7 text-blue-500" />
              จัดการนักเรียน
            </h1>
          </div>
          <p class="text-gray-600 dark:text-gray-400">ข้อมูลนักเรียน บัตรนักเรียน และผลการเรียน</p>
        </div>

        <div class="flex gap-3">
          <NuxtLink
            to="/student-card/admin"
            class="px-4 py-2 border border-purple-500 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/30 rounded-lg font-medium transition-colors flex items-center gap-2"
          >
            <Icon icon="fluent:card-ui-24-regular" class="w-5 h-5" />
            จัดการบัตรนักเรียน
          </NuxtLink>
        </div>
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
                placeholder="ค้นหานักเรียน ชื่อ หรือเลขประจำตัว..."
                class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
          </div>
          
          <select
            v-model="selectedClassroom"
            @change="fetchStudents"
            class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          >
            <option value="">ทุกห้องเรียน</option>
            <option v-for="classroom in classrooms" :key="classroom.id" :value="classroom.id">
              {{ classroom.name }} ({{ classroom.grade_level }})
            </option>
          </select>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
              <Icon icon="fluent:people-24-filled" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ students.length }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">นักเรียนทั้งหมด</div>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
              <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ students.filter((s: any) => s.status === 'active').length }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">กำลังศึกษา</div>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
              <Icon icon="fluent:card-ui-24-filled" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ students.filter((s: any) => s.student_card).length }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">มีบัตรนักเรียน</div>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-yellow-100 dark:bg-yellow-900/50 flex items-center justify-center">
              <Icon icon="fluent:building-24-filled" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
            </div>
            <div>
              <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ classrooms.length }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">ห้องเรียน</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Students Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">นักเรียน</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">เลขประจำตัว</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ห้องเรียน</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">GPA</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">บัตรนักเรียน</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จัดการ</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr 
                v-for="student in filteredStudents" 
                :key="student.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                @click="openStudentDetail(student)"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-3">
                    <img
                      v-if="student.user?.avatar"
                      :src="student.user.avatar"
                      :alt="student.user?.displayname"
                      class="w-10 h-10 rounded-full object-cover"
                    />
                    <div v-else class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                      <Icon icon="fluent:person-24-filled" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </div>
                    <div>
                      <div class="font-medium text-gray-900 dark:text-white">{{ student.user?.displayname }}</div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">{{ student.user?.email }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  {{ student.student_number || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ student.classroom?.name || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span v-if="student.gpa" :class="['font-bold', getGradeColor(student.gpa)]">
                    {{ student.gpa?.toFixed(2) }}
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <Icon
                    v-if="student.student_card"
                    icon="fluent:checkmark-circle-24-filled"
                    class="w-5 h-5 text-green-500 mx-auto"
                  />
                  <Icon
                    v-else
                    icon="fluent:dismiss-circle-24-regular"
                    class="w-5 h-5 text-gray-400 mx-auto"
                  />
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBadge(student.status).color]">
                    {{ getStatusBadge(student.status).label }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                  <div class="flex items-center justify-end gap-2" @click.stop>
                    <button
                      v-if="student.student_card"
                      @click="goToStudentCard(student)"
                      class="p-2 text-purple-500 hover:bg-purple-50 dark:hover:bg-purple-900/30 rounded-lg transition-colors"
                      title="ดูบัตรนักเรียน"
                    >
                      <Icon icon="fluent:card-ui-24-regular" class="w-5 h-5" />
                    </button>
                    <button
                      @click="goToTranscript(student)"
                      class="p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                      title="ดูผลการเรียน"
                    >
                      <Icon icon="fluent:document-text-24-regular" class="w-5 h-5" />
                    </button>
                    <button
                      @click="openStudentDetail(student)"
                      class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                      title="รายละเอียด"
                    >
                      <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="filteredStudents.length === 0" class="p-12 text-center">
          <Icon icon="fluent:people-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ไม่พบนักเรียน</h3>
          <p class="text-gray-600 dark:text-gray-400">ลองเปลี่ยนตัวกรองหรือคำค้นหา</p>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
          <button
            @click="currentPage--; fetchStudents()"
            :disabled="currentPage === 1"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            ก่อนหน้า
          </button>
          <span class="text-sm text-gray-600 dark:text-gray-400">
            หน้า {{ currentPage }} จาก {{ totalPages }}
          </span>
          <button
            @click="currentPage++; fetchStudents()"
            :disabled="currentPage === totalPages"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            ถัดไป
          </button>
        </div>
      </div>
    </div>

    <!-- Student Detail Modal -->
    <Teleport to="body">
      <div v-if="showStudentModal && selectedStudent" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" @click="showStudentModal = false"></div>
        
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
          <!-- Header with Avatar -->
          <div class="p-6 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-t-2xl">
            <div class="flex items-center gap-4">
              <img
                v-if="selectedStudent.user?.avatar"
                :src="selectedStudent.user.avatar"
                :alt="selectedStudent.user?.displayname"
                class="w-20 h-20 rounded-full object-cover border-4 border-white/30"
              />
              <div v-else class="w-20 h-20 rounded-full bg-white/20 flex items-center justify-center border-4 border-white/30">
                <Icon icon="fluent:person-24-filled" class="w-10 h-10 text-white" />
              </div>
              <div>
                <h2 class="text-2xl font-bold">{{ selectedStudent.user?.displayname }}</h2>
                <p class="text-white/80">{{ selectedStudent.student_number || 'ไม่ระบุเลขประจำตัว' }}</p>
                <span :class="['mt-2 inline-block px-2 py-1 text-xs font-medium rounded-full', selectedStudent.status === 'active' ? 'bg-green-500 text-white' : 'bg-white/20']">
                  {{ getStatusBadge(selectedStudent.status).label }}
                </span>
              </div>
              <button
                @click="showStudentModal = false"
                class="absolute top-4 right-4 p-2 text-white/80 hover:text-white hover:bg-white/10 rounded-lg"
              >
                <Icon icon="fluent:dismiss-24-filled" class="w-6 h-6" />
              </button>
            </div>
          </div>
          
          <!-- Content -->
          <div class="p-6 space-y-6">
            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">ห้องเรียน</div>
                <div class="font-semibold text-gray-900 dark:text-white">
                  {{ selectedStudent.classroom?.name || 'ไม่ได้จัดห้อง' }}
                </div>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">GPA ล่าสุด</div>
                <div :class="['font-bold text-lg', getGradeColor(selectedStudent.gpa)]">
                  {{ selectedStudent.gpa?.toFixed(2) || '-' }}
                </div>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">อีเมล</div>
                <div class="font-medium text-gray-900 dark:text-white text-sm">
                  {{ selectedStudent.user?.email || '-' }}
                </div>
              </div>
              <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">บัตรนักเรียน</div>
                <div class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                  <template v-if="selectedStudent.student_card">
                    <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-green-500" />
                    {{ selectedStudent.student_card.card_number }}
                  </template>
                  <template v-else>
                    <Icon icon="fluent:dismiss-circle-24-regular" class="w-5 h-5 text-gray-400" />
                    ยังไม่มี
                  </template>
                </div>
              </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-2 gap-3">
              <button
                v-if="selectedStudent.student_card"
                @click="goToStudentCard(selectedStudent); showStudentModal = false"
                class="px-4 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
              >
                <Icon icon="fluent:card-ui-24-filled" class="w-5 h-5" />
                ดูบัตรนักเรียน
              </button>
              <button
                @click="goToTranscript(selectedStudent); showStudentModal = false"
                class="px-4 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
              >
                <Icon icon="fluent:document-text-24-filled" class="w-5 h-5" />
                ดูผลการเรียน
              </button>
              <NuxtLink
                :to="`/academies/${academyName}/admin/gradebook/classrooms/${selectedStudent.classroom_id}`"
                @click="showStudentModal = false"
                class="px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                <Icon icon="fluent:building-24-regular" class="w-5 h-5" />
                ไปยังห้องเรียน
              </NuxtLink>
              <button
                @click="showStudentModal = false"
                class="px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                ปิด
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

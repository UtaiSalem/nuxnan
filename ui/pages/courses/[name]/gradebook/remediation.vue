<script setup lang="ts">
/**
 * Course Remediation Sessions
 * หน้าจัดการการซ่อมเสริม
 */
import { Icon } from '@iconify/vue'
import GradebookSubNav from '~/components/learn/course/gradebook/GradebookSubNav.vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const courseName = computed(() => route.params.name as string)
const courseId = ref<number | null>(null)

// State
const course = ref<any>(null)
const sessions = ref<any[]>([])
const enrolledStudents = ref<any[]>([])
const eligibleStudents = ref<any[]>([])
const isLoading = ref(true)
const isProcessing = ref(false)

// Active session
const activeSession = ref<any>(null)

// Modals
const showCreateModal = ref(false)
const showEnrollModal = ref(false)
const showScoreModal = ref(false)

// Forms
const createForm = ref({
  name: '',
  type: 'resit',
  scheduled_at: '',
  notes: ''
})

const selectedStudents = ref<number[]>([])
const selectedStudent = ref<any>(null)
const scoreForm = ref({
  score: '',
  notes: ''
})

onMounted(async () => {
  await fetchCourse()
})

const fetchCourse = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseName.value}`)
    if (res.success) {
      course.value = res.course
      courseId.value = res.course.id
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to fetch course:', err)
  } finally {
    isLoading.value = false
  }
}

const fetchData = async () => {
  await Promise.all([
    fetchSessions(),
    fetchEligibleStudents()
  ])
}

const fetchSessions = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/remediation/sessions`)
    if (res.success && res.data) {
      sessions.value = res.data.sessions || res.data || []
      // Set active session
      activeSession.value = sessions.value.find((s: any) => s.status === 'open') || sessions.value[0]
      if (activeSession.value) {
        await fetchEnrolledStudents()
      }
    }
  } catch (err) {
    console.error('Failed to fetch sessions:', err)
  }
}

const fetchEnrolledStudents = async () => {
  if (!activeSession.value) return
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/remediation/sessions/${activeSession.value.id}/enrollments`)
    if (res.success && res.data) {
      enrolledStudents.value = res.data.enrollments || res.data || []
    }
  } catch (err) {
    console.error('Failed to fetch enrollments:', err)
  }
}

const fetchEligibleStudents = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/remediation/eligible-students`)
    if (res.success && res.data) {
      eligibleStudents.value = res.data.students || res.data || []
    }
  } catch (err) {
    console.error('Failed to fetch eligible students:', err)
  }
}

// Create session
const createSession = async () => {
  if (!createForm.value.name) return
  
  isProcessing.value = true
  try {
    const res: any = await api.post(`/api/courses/${courseId.value}/remediation/sessions`, createForm.value)
    if (res.success) {
      useToast().success('สร้างรอบซ่อมเสริมสำเร็จ')
      showCreateModal.value = false
      createForm.value = { name: '', type: 'resit', scheduled_at: '', notes: '' }
      await fetchSessions()
    }
  } catch (err) {
    console.error('Failed to create session:', err)
    useToast().error('ไม่สามารถสร้างรอบซ่อมเสริม')
  } finally {
    isProcessing.value = false
  }
}

// Enroll students
const enrollStudents = async () => {
  if (selectedStudents.value.length === 0 || !activeSession.value) return
  
  isProcessing.value = true
  try {
    const res: any = await api.post(`/api/courses/${courseId.value}/remediation/sessions/${activeSession.value.id}/enroll`, {
      member_ids: selectedStudents.value
    })
    if (res.success) {
      useToast().success('ลงทะเบียนสำเร็จ')
      showEnrollModal.value = false
      selectedStudents.value = []
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to enroll students:', err)
    useToast().error('ไม่สามารถลงทะเบียน')
  } finally {
    isProcessing.value = false
  }
}

// Submit score
const submitScore = async () => {
  if (!selectedStudent.value || !scoreForm.value.score) return
  
  isProcessing.value = true
  try {
    const res: any = await api.post(`/api/courses/${courseId.value}/remediation/enrollments/${selectedStudent.value.id}/score`, scoreForm.value)
    if (res.success) {
      useToast().success('บันทึกคะแนนสำเร็จ')
      showScoreModal.value = false
      await fetchEnrolledStudents()
    }
  } catch (err) {
    console.error('Failed to submit score:', err)
    useToast().error('ไม่สามารถบันทึกคะแนน')
  } finally {
    isProcessing.value = false
  }
}

// Open score modal
const openScoreModal = (student: any) => {
  selectedStudent.value = student
  scoreForm.value = {
    score: student.score || '',
    notes: student.notes || ''
  }
  showScoreModal.value = true
}

// Select session
const selectSession = async (session: any) => {
  activeSession.value = session
  await fetchEnrolledStudents()
}

// Status helpers
const getSessionStatusColor = (status: string) => {
  switch (status) {
    case 'open': return 'bg-green-100 text-green-800'
    case 'in_progress': return 'bg-blue-100 text-blue-800'
    case 'completed': return 'bg-gray-100 text-gray-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const getSessionStatusLabel = (status: string) => {
  switch (status) {
    case 'open': return 'เปิดรับสมัคร'
    case 'in_progress': return 'กำลังดำเนินการ'
    case 'completed': return 'เสร็จสิ้น'
    default: return status
  }
}

const getEnrollmentStatusColor = (status: string) => {
  switch (status) {
    case 'enrolled': return 'bg-blue-100 text-blue-800'
    case 'passed': return 'bg-green-100 text-green-800'
    case 'failed': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const getEnrollmentStatusLabel = (status: string) => {
  switch (status) {
    case 'enrolled': return 'ลงทะเบียน'
    case 'passed': return 'ผ่าน'
    case 'failed': return 'ไม่ผ่าน'
    default: return status
  }
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const typeOptions = [
  { value: 'resit', label: 'สอบซ่อม' },
  { value: 'makeup', label: 'เรียนเพิ่มเติม' },
  { value: 'extra_work', label: 'ทำงานชดเชย' }
]
</script>

<template>
  <NuxtLayout name="course">
    <div>
    <GradebookSubNav :course-name="courseName" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Header -->
      <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                การซ่อมเสริม
              </h1>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                จัดการรอบซ่อมเสริมและลงทะเบียนนักเรียน
              </p>
            </div>
            <button
              @click="showCreateModal = true"
              class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
            >
              <Icon icon="heroicons:plus" class="w-4 h-4 mr-2" />
              สร้างรอบซ่อม
            </button>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
        <!-- Loading -->
        <div v-if="isLoading" class="flex justify-center py-12">
          <Icon icon="heroicons:arrow-path" class="w-8 h-8 animate-spin text-primary-600" />
        </div>

        <template v-else>
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sessions List -->
            <div class="lg:col-span-1">
              <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                  <h3 class="font-semibold text-gray-900 dark:text-white">รอบซ่อมเสริม</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-600">
                  <div
                    v-for="session in sessions"
                    :key="session.id"
                    @click="selectSession(session)"
                    class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    :class="{ 'bg-primary-50 dark:bg-primary-900/20': activeSession?.id === session.id }"
                  >
                    <div class="flex items-start justify-between">
                      <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ session.name }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ formatDate(session.scheduled_at) }}</p>
                      </div>
                      <span
                        :class="getSessionStatusColor(session.status)"
                        class="px-2 py-0.5 text-xs font-medium rounded-full"
                      >
                        {{ getSessionStatusLabel(session.status) }}
                      </span>
                    </div>
                    <div class="mt-2 flex items-center gap-3 text-sm text-gray-500">
                      <span class="flex items-center">
                        <Icon icon="heroicons:users" class="w-4 h-4 mr-1" />
                        {{ session.enrollments_count || 0 }} คน
                      </span>
                      <span class="text-xs text-gray-400">{{ session.type }}</span>
                    </div>
                  </div>

                  <div v-if="sessions.length === 0" class="p-6 text-center text-gray-500">
                    ยังไม่มีรอบซ่อมเสริม
                  </div>
                </div>
              </div>
            </div>

            <!-- Session Detail -->
            <div class="lg:col-span-2">
              <div v-if="activeSession" class="space-y-4">
                <!-- Session Info -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
                  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ activeSession.name }}</h3>
                      <p class="text-sm text-gray-500">{{ formatDate(activeSession.scheduled_at) }}</p>
                    </div>
                    <button
                      v-if="activeSession.status === 'open'"
                      @click="showEnrollModal = true"
                      class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                    >
                      <Icon icon="heroicons:user-plus" class="w-4 h-4 mr-2" />
                      ลงทะเบียนนักเรียน
                    </button>
                  </div>
                </div>

                <!-- Enrolled Students -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                  <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">นักเรียนที่ลงทะเบียน</h3>
                    <span class="text-sm text-gray-500">{{ enrolledStudents.length }} คน</span>
                  </div>

                  <!-- Desktop -->
                  <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                      <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                          <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">นักเรียน</th>
                          <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">เกรดเดิม</th>
                          <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">คะแนนซ่อม</th>
                          <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                          <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">จัดการ</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                        <tr v-for="student in enrolledStudents" :key="student.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                          <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                              <img :src="student.member?.user?.avatar || '/images/default-avatar.png'" class="w-10 h-10 rounded-full" />
                              <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ student.member?.user?.name }}</div>
                              </div>
                            </div>
                          </td>
                          <td class="px-6 py-4 text-center">
                            <span class="font-medium text-red-600">{{ student.original_grade || 'F' }}</span>
                          </td>
                          <td class="px-6 py-4 text-center">
                            <span class="font-medium text-gray-900 dark:text-white">{{ student.score ?? '-' }}</span>
                          </td>
                          <td class="px-6 py-4 text-center">
                            <span
                              :class="getEnrollmentStatusColor(student.status)"
                              class="px-2 py-0.5 text-xs font-medium rounded-full"
                            >
                              {{ getEnrollmentStatusLabel(student.status) }}
                            </span>
                          </td>
                          <td class="px-6 py-4 text-right">
                            <button
                              @click="openScoreModal(student)"
                              class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                            >
                              บันทึกคะแนน
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Mobile -->
                  <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
                    <div v-for="student in enrolledStudents" :key="student.id" class="p-4">
                      <div class="flex items-center justify-between">
                        <div class="flex items-center">
                          <img :src="student.member?.user?.avatar || '/images/default-avatar.png'" class="w-10 h-10 rounded-full" />
                          <div class="ml-3">
                            <p class="font-medium text-gray-900 dark:text-white">{{ student.member?.user?.name }}</p>
                            <span
                              :class="getEnrollmentStatusColor(student.status)"
                              class="px-2 py-0.5 text-xs font-medium rounded-full"
                            >
                              {{ getEnrollmentStatusLabel(student.status) }}
                            </span>
                          </div>
                        </div>
                      </div>
                      <div class="mt-3 flex items-center justify-between">
                        <div class="flex gap-4 text-sm">
                          <span class="text-gray-500">เดิม: <span class="font-medium text-red-600">{{ student.original_grade || 'F' }}</span></span>
                          <span class="text-gray-500">คะแนน: <span class="font-medium">{{ student.score ?? '-' }}</span></span>
                        </div>
                        <button
                          @click="openScoreModal(student)"
                          class="text-primary-600 text-sm font-medium"
                        >
                          บันทึก
                        </button>
                      </div>
                    </div>
                  </div>

                  <div v-if="enrolledStudents.length === 0" class="py-12 text-center">
                    <Icon icon="heroicons:users" class="w-12 h-12 mx-auto text-gray-400" />
                    <p class="mt-2 text-gray-500">ยังไม่มีนักเรียนลงทะเบียน</p>
                  </div>
                </div>
              </div>

              <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                <Icon icon="heroicons:academic-cap" class="w-16 h-16 mx-auto text-gray-400" />
                <p class="mt-4 text-gray-500">เลือกรอบซ่อมเสริมหรือสร้างใหม่</p>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- Create Session Modal -->
      <Teleport to="body">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สร้างรอบซ่อมเสริม</h3>

              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อรอบ</label>
                  <input
                    v-model="createForm.name"
                    type="text"
                    placeholder="เช่น สอบซ่อมครั้งที่ 1"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                  <select v-model="createForm.type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg">
                    <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่</label>
                  <input
                    v-model="createForm.scheduled_at"
                    type="datetime-local"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมายเหตุ</label>
                  <textarea
                    v-model="createForm.notes"
                    rows="2"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                  ></textarea>
                </div>
              </div>

              <div class="mt-6 flex gap-3">
                <button @click="showCreateModal = false" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                  ยกเลิก
                </button>
                <button @click="createSession" :disabled="!createForm.name || isProcessing" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                  สร้าง
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Enroll Modal -->
      <Teleport to="body">
        <div v-if="showEnrollModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="showEnrollModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6 max-h-[80vh] overflow-y-auto">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ลงทะเบียนนักเรียน</h3>

              <div v-if="eligibleStudents.length > 0" class="space-y-2 max-h-96 overflow-y-auto">
                <label
                  v-for="student in eligibleStudents"
                  :key="student.id"
                  class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                  <input
                    type="checkbox"
                    v-model="selectedStudents"
                    :value="student.id"
                    class="mr-3"
                  />
                  <img :src="student.user?.avatar || '/images/default-avatar.png'" class="w-8 h-8 rounded-full" />
                  <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ student.user?.name }}</p>
                    <p class="text-xs text-gray-500">เกรด: {{ student.final_grade || 'F' }}</p>
                  </div>
                </label>
              </div>
              <div v-else class="py-8 text-center text-gray-500">
                ไม่มีนักเรียนที่ต้องซ่อมเสริม
              </div>

              <div class="mt-6 flex gap-3">
                <button @click="showEnrollModal = false" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                  ยกเลิก
                </button>
                <button @click="enrollStudents" :disabled="selectedStudents.length === 0 || isProcessing" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                  ลงทะเบียน ({{ selectedStudents.length }})
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Score Modal -->
      <Teleport to="body">
        <div v-if="showScoreModal && selectedStudent" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="showScoreModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">บันทึกคะแนนซ่อม</h3>

              <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg flex items-center">
                <img :src="selectedStudent.member?.user?.avatar || '/images/default-avatar.png'" class="w-10 h-10 rounded-full" />
                <div class="ml-3">
                  <p class="font-medium text-gray-900 dark:text-white">{{ selectedStudent.member?.user?.name }}</p>
                  <p class="text-sm text-gray-500">เกรดเดิม: {{ selectedStudent.original_grade || 'F' }}</p>
                </div>
              </div>

              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คะแนน</label>
                  <input
                    v-model="scoreForm.score"
                    type="number"
                    min="0"
                    max="100"
                    placeholder="0-100"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หมายเหตุ</label>
                  <textarea
                    v-model="scoreForm.notes"
                    rows="2"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                  ></textarea>
                </div>
              </div>

              <div class="mt-6 flex gap-3">
                <button @click="showScoreModal = false" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                  ยกเลิก
                </button>
                <button @click="submitScore" :disabled="!scoreForm.score || isProcessing" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50">
                  บันทึก
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
    </div>
  </NuxtLayout>
</template>

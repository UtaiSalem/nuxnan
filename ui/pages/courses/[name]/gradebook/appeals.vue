<script setup lang="ts">
/**
 * Course Grade Appeals
 * หน้าจัดการคำร้องอุทธรณ์เกรด
 */
import { Icon } from '@iconify/vue'
import GradebookSubNav from '~/components/learn/course/gradebook/GradebookSubNav.vue'

definePageMeta({
  layout: false,
  pageTransition: false
})

const route = useRoute()
const api = useApi()
const courseName = computed(() => route.params.name as string)
const courseId = ref<number | null>(null)

// State
const course = ref<any>(null)
const appeals = ref<any[]>([])
const statistics = ref<any>(null)
const isLoading = ref(true)
const isProcessing = ref(false)

// Filters
const searchQuery = ref('')
const statusFilter = ref<string>('all')

// Detail modal
const showDetailModal = ref(false)
const selectedAppeal = ref<any>(null)
const reviewForm = ref({
  decision: '',
  new_grade: '',
  response: ''
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
    fetchAppeals(),
    fetchStatistics()
  ])
}

const fetchAppeals = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/appeals`)
    if (res.success && res.data) {
      appeals.value = res.data.appeals || res.data || []
    }
  } catch (err) {
    console.error('Failed to fetch appeals:', err)
  }
}

const fetchStatistics = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/appeals/statistics`)
    if (res.success && res.data) {
      statistics.value = res.data
    }
  } catch (err) {
    console.error('Failed to fetch statistics:', err)
  }
}

// Filtered appeals
const filteredAppeals = computed(() => {
  let result = [...appeals.value]

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter((a: any) =>
      a.member?.user?.name?.toLowerCase().includes(query)
    )
  }

  if (statusFilter.value !== 'all') {
    result = result.filter((a: any) => a.status === statusFilter.value)
  }

  return result
})

// Open detail
const openDetail = (appeal: any) => {
  selectedAppeal.value = appeal
  reviewForm.value = {
    decision: '',
    new_grade: appeal.current_grade || '',
    response: ''
  }
  showDetailModal.value = true
}

// Submit review
const submitReview = async () => {
  if (!selectedAppeal.value || !reviewForm.value.decision) return
  
  isProcessing.value = true
  try {
    const endpoint = reviewForm.value.decision === 'approved' 
      ? `/api/courses/${courseId.value}/appeals/${selectedAppeal.value.id}/approve`
      : `/api/courses/${courseId.value}/appeals/${selectedAppeal.value.id}/reject`
    
    const data: any = {
      response: reviewForm.value.response
    }
    
    if (reviewForm.value.decision === 'approved' && reviewForm.value.new_grade) {
      data.new_grade = reviewForm.value.new_grade
    }
    
    const res: any = await api.post(endpoint, data)
    if (res.success) {
      useToast().success(reviewForm.value.decision === 'approved' ? 'อนุมัติแล้ว' : 'ปฏิเสธแล้ว')
      showDetailModal.value = false
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to submit review:', err)
    useToast().error('ไม่สามารถดำเนินการได้')
  } finally {
    isProcessing.value = false
  }
}

// Status helpers
const getStatusColor = (status: string) => {
  switch (status) {
    case 'pending': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
    case 'reviewing': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
    case 'approved': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    case 'rejected': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'pending': return 'รอพิจารณา'
    case 'reviewing': return 'กำลังพิจารณา'
    case 'approved': return 'อนุมัติ'
    case 'rejected': return 'ปฏิเสธ'
    default: return status
  }
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'pending': return 'heroicons:clock'
    case 'reviewing': return 'heroicons:eye'
    case 'approved': return 'heroicons:check-circle'
    case 'rejected': return 'heroicons:x-circle'
    default: return 'heroicons:question-mark-circle'
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

const gradeOptions = ['A', 'B+', 'B', 'C+', 'C', 'D+', 'D', 'F']
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
                คำร้องอุทธรณ์เกรด
              </h1>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                จัดการคำร้องขออุทธรณ์เกรดจากนักเรียน
              </p>
            </div>
            <NuxtLink
              :to="`/courses/${courseName}/gradebook/completion`"
              class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50"
            >
              <Icon icon="heroicons:arrow-left" class="w-4 h-4 mr-2" />
              กลับการปิดเกรด
            </NuxtLink>
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
          <!-- Statistics -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 sm:p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                  <Icon icon="heroicons:document-text" class="w-5 h-5 sm:w-6 sm:h-6 text-gray-600 dark:text-gray-300" />
                </div>
                <div>
                  <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</p>
                  <p class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">
                    {{ statistics?.total || appeals.length }}
                  </p>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 sm:p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                  <Icon icon="heroicons:clock" class="w-5 h-5 sm:w-6 sm:h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                  <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">รอพิจารณา</p>
                  <p class="text-lg sm:text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                    {{ statistics?.pending || 0 }}
                  </p>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 sm:p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                  <Icon icon="heroicons:check-circle" class="w-5 h-5 sm:w-6 sm:h-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                  <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">อนุมัติ</p>
                  <p class="text-lg sm:text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ statistics?.approved || 0 }}
                  </p>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 sm:p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                  <Icon icon="heroicons:x-circle" class="w-5 h-5 sm:w-6 sm:h-6 text-red-600 dark:text-red-400" />
                </div>
                <div>
                  <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">ปฏิเสธ</p>
                  <p class="text-lg sm:text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ statistics?.rejected || 0 }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Filters -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6">
            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
              <div class="flex-1">
                <div class="relative">
                  <Icon icon="heroicons:magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                  <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="ค้นหานักเรียน..."
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  />
                </div>
              </div>
              <select
                v-model="statusFilter"
                class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="all">ทุกสถานะ</option>
                <option value="pending">รอพิจารณา</option>
                <option value="reviewing">กำลังพิจารณา</option>
                <option value="approved">อนุมัติ</option>
                <option value="rejected">ปฏิเสธ</option>
              </select>
            </div>
          </div>

          <!-- Appeals List -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Desktop -->
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">นักเรียน</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เกรดเดิม</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">เกรดที่ขอ</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่ยื่น</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                  <tr v-for="appeal in filteredAppeals" :key="appeal.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <img :src="appeal.member?.user?.avatar || '/images/default-avatar.png'" class="w-10 h-10 rounded-full" />
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900 dark:text-white">{{ appeal.member?.user?.name }}</div>
                          <div class="text-sm text-gray-500">{{ appeal.member?.member_code }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                      <span class="text-lg font-bold text-gray-900 dark:text-white">{{ appeal.current_grade }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                      <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ appeal.requested_grade || '-' }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                      <span 
                        :class="getStatusColor(appeal.status)"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      >
                        <Icon :icon="getStatusIcon(appeal.status)" class="w-3.5 h-3.5 mr-1" />
                        {{ getStatusLabel(appeal.status) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                      {{ formatDate(appeal.created_at) }}
                    </td>
                    <td class="px-6 py-4 text-right">
                      <button
                        @click="openDetail(appeal)"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-primary-600 bg-primary-50 dark:bg-primary-900/20 rounded-lg hover:bg-primary-100"
                      >
                        <Icon icon="heroicons:eye" class="w-4 h-4 mr-1" />
                        ดูรายละเอียด
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Mobile -->
            <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
              <div v-for="appeal in filteredAppeals" :key="appeal.id" class="p-4">
                <div class="flex items-start justify-between">
                  <div class="flex items-center">
                    <img :src="appeal.member?.user?.avatar || '/images/default-avatar.png'" class="w-12 h-12 rounded-full" />
                    <div class="ml-3">
                      <p class="font-medium text-gray-900 dark:text-white">{{ appeal.member?.user?.name }}</p>
                      <p class="text-xs text-gray-500">{{ formatDate(appeal.created_at) }}</p>
                    </div>
                  </div>
                  <span 
                    :class="getStatusColor(appeal.status)"
                    class="px-2 py-1 text-xs font-medium rounded-full"
                  >
                    {{ getStatusLabel(appeal.status) }}
                  </span>
                </div>

                <div class="mt-3 flex items-center justify-center gap-4">
                  <div class="text-center">
                    <p class="text-xs text-gray-500">เกรดเดิม</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ appeal.current_grade }}</p>
                  </div>
                  <Icon icon="heroicons:arrow-right" class="w-5 h-5 text-gray-400" />
                  <div class="text-center">
                    <p class="text-xs text-gray-500">เกรดที่ขอ</p>
                    <p class="text-xl font-bold text-blue-600">{{ appeal.requested_grade || '-' }}</p>
                  </div>
                </div>

                <button
                  @click="openDetail(appeal)"
                  class="mt-3 w-full py-2 text-sm font-medium text-primary-600 bg-primary-50 rounded-lg"
                >
                  ดูรายละเอียด
                </button>
              </div>
            </div>

            <!-- Empty -->
            <div v-if="filteredAppeals.length === 0" class="py-12 text-center">
              <Icon icon="heroicons:document-text" class="w-12 h-12 mx-auto text-gray-400" />
              <p class="mt-2 text-gray-500">ไม่มีคำร้องอุทธรณ์</p>
            </div>
          </div>
        </template>
      </div>

      <!-- Detail Modal -->
      <Teleport to="body">
        <div v-if="showDetailModal && selectedAppeal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="showDetailModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">รายละเอียดคำร้องอุทธรณ์</h3>

              <!-- Student Info -->
              <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center">
                  <img :src="selectedAppeal.member?.user?.avatar || '/images/default-avatar.png'" class="w-12 h-12 rounded-full" />
                  <div class="ml-3">
                    <p class="font-medium text-gray-900 dark:text-white">{{ selectedAppeal.member?.user?.name }}</p>
                    <p class="text-sm text-gray-500">{{ selectedAppeal.member?.member_code }}</p>
                  </div>
                </div>
              </div>

              <!-- Grade Info -->
              <div class="mb-4 grid grid-cols-2 gap-4">
                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-center">
                  <p class="text-xs text-gray-500">เกรดเดิม</p>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ selectedAppeal.current_grade }}</p>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
                  <p class="text-xs text-gray-500">เกรดที่ขอ</p>
                  <p class="text-2xl font-bold text-blue-600">{{ selectedAppeal.requested_grade || '-' }}</p>
                </div>
              </div>

              <!-- Reason -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เหตุผลการอุทธรณ์</label>
                <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-sm text-gray-600 dark:text-gray-300">
                  {{ selectedAppeal.reason || 'ไม่ได้ระบุ' }}
                </div>
              </div>

              <!-- Evidence -->
              <div v-if="selectedAppeal.evidence_url" class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หลักฐานประกอบ</label>
                <a 
                  :href="selectedAppeal.evidence_url" 
                  target="_blank"
                  class="inline-flex items-center text-sm text-blue-600 hover:underline"
                >
                  <Icon icon="heroicons:document" class="w-4 h-4 mr-1" />
                  ดูหลักฐาน
                </a>
              </div>

              <!-- Review Form (if pending) -->
              <template v-if="['pending', 'reviewing'].includes(selectedAppeal.status)">
                <hr class="my-4 border-gray-200 dark:border-gray-600" />
                
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">การตัดสินใจ</label>
                    <div class="flex gap-3">
                      <label class="flex-1 relative">
                        <input 
                          type="radio" 
                          v-model="reviewForm.decision" 
                          value="approved"
                          class="peer sr-only"
                        />
                        <div class="p-3 border-2 rounded-lg cursor-pointer text-center peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20">
                          <Icon icon="heroicons:check-circle" class="w-6 h-6 mx-auto text-green-600" />
                          <p class="mt-1 text-sm font-medium">อนุมัติ</p>
                        </div>
                      </label>
                      <label class="flex-1 relative">
                        <input 
                          type="radio" 
                          v-model="reviewForm.decision" 
                          value="rejected"
                          class="peer sr-only"
                        />
                        <div class="p-3 border-2 rounded-lg cursor-pointer text-center peer-checked:border-red-500 peer-checked:bg-red-50 dark:peer-checked:bg-red-900/20">
                          <Icon icon="heroicons:x-circle" class="w-6 h-6 mx-auto text-red-600" />
                          <p class="mt-1 text-sm font-medium">ปฏิเสธ</p>
                        </div>
                      </label>
                    </div>
                  </div>

                  <div v-if="reviewForm.decision === 'approved'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เกรดใหม่</label>
                    <select
                      v-model="reviewForm.new_grade"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                    >
                      <option v-for="g in gradeOptions" :key="g" :value="g">{{ g }}</option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความเห็น/เหตุผล</label>
                    <textarea
                      v-model="reviewForm.response"
                      rows="3"
                      placeholder="ระบุความเห็น..."
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                    ></textarea>
                  </div>
                </div>

                <div class="mt-6 flex gap-3">
                  <button
                    @click="showDetailModal = false"
                    class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                  >
                    ยกเลิก
                  </button>
                  <button
                    @click="submitReview"
                    :disabled="!reviewForm.decision || isProcessing"
                    class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                  >
                    ยืนยัน
                  </button>
                </div>
              </template>

              <!-- Already reviewed -->
              <template v-else>
                <div class="mt-4 p-3 rounded-lg" :class="selectedAppeal.status === 'approved' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'">
                  <p class="text-sm font-medium" :class="selectedAppeal.status === 'approved' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'">
                    {{ selectedAppeal.status === 'approved' ? 'อนุมัติแล้ว' : 'ปฏิเสธแล้ว' }}
                  </p>
                  <p v-if="selectedAppeal.response" class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ selectedAppeal.response }}
                  </p>
                </div>
                <button
                  @click="showDetailModal = false"
                  class="mt-4 w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                >
                  ปิด
                </button>
              </template>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
    </div>
  </NuxtLayout>
</template>

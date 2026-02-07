<script setup lang="ts">
/**
 * Student Grade View
 * หน้าดูเกรดสำหรับนักเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const courseName = computed(() => route.params.name as string)
const courseId = ref<number | null>(null)

// State
const course = ref<any>(null)
const gradeData = ref<any>(null)
const certificate = ref<any>(null)
const downloadPricing = ref<any>(null)
const isLoading = ref(true)
const isProcessing = ref(false)

// Modals
const showAcceptModal = ref(false)
const showAppealModal = ref(false)
const showDownloadModal = ref(false)

// Auth store for points
const authStore = useAuthStore()

// Appeal form
const appealForm = ref({
  reason: '',
  requested_grade: '',
  evidence_url: ''
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
    fetchMyGrade(),
    fetchMyCertificate(),
    fetchDownloadPricing()
  ])
}

const fetchMyGrade = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/my-grade`)
    if (res.success && res.data) {
      gradeData.value = res.data
    }
  } catch (err) {
    console.error('Failed to fetch grade:', err)
  }
}

const fetchMyCertificate = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/my-certificate`)
    if (res.success && res.data) {
      certificate.value = res.data
    }
  } catch (err) {
    // No certificate yet
  }
}

const fetchDownloadPricing = async () => {
  try {
    // Get course-specific pricing
    const url = courseId.value 
      ? `/api/courses/${courseId.value}/certificates/pricing`
      : '/api/certificates/pricing'
    const res: any = await api.get(url)
    if (res.success && res.data) {
      downloadPricing.value = res.data
    }
  } catch (err) {
    console.error('Failed to fetch pricing:', err)
  }
}

// Accept grade
const acceptGrade = async () => {
  isProcessing.value = true
  try {
    const res: any = await api.post(`/api/courses/${courseId.value}/accept-grade`)
    if (res.success) {
      useToast().success('ยืนยันเกรดสำเร็จ')
      showAcceptModal.value = false
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to accept grade:', err)
    useToast().error('ไม่สามารถยืนยันเกรด')
  } finally {
    isProcessing.value = false
  }
}

// Submit appeal
const submitAppeal = async () => {
  if (!appealForm.value.reason) return
  
  isProcessing.value = true
  try {
    const res: any = await api.post(`/api/courses/${courseId.value}/appeal`, appealForm.value)
    if (res.success) {
      useToast().success('ส่งคำร้องอุทธรณ์สำเร็จ')
      showAppealModal.value = false
      appealForm.value = { reason: '', requested_grade: '', evidence_url: '' }
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to submit appeal:', err)
    useToast().error('ไม่สามารถส่งคำร้อง')
  } finally {
    isProcessing.value = false
  }
}

// Download certificate
const downloadCertificate = async () => {
  if (!certificate.value) return
  
  // Show confirmation modal first
  showDownloadModal.value = true
}

// Confirm and process download
const confirmDownload = async () => {
  if (!certificate.value) return
  
  isProcessing.value = true
  try {
    // This will trigger points deduction and file download
    const response = await fetch(`${useRuntimeConfig().public.apiUrl}/api/certificates/${certificate.value.id}/download`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
      },
    })
    
    if (response.status === 402) {
      // Payment required - not enough points
      const data = await response.json()
      useToast().error(`แต้มไม่เพียงพอ (ต้องการ ${data.data?.required_points?.toLocaleString()} แต้ม)`)
      showDownloadModal.value = false
      return
    }
    
    if (!response.ok) {
      const data = await response.json()
      throw new Error(data.message || 'ไม่สามารถดาวน์โหลดได้')
    }
    
    // Get the PDF blob
    const blob = await response.blob()
    
    // Create download link
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${certificate.value.certificate_number}.pdf`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)
    
    // Refresh user data to update points
    await authStore.fetchUser()
    
    useToast().success('ดาวน์โหลดสำเร็จ!')
    showDownloadModal.value = false
  } catch (err: any) {
    console.error('Failed to download:', err)
    useToast().error(err.message || 'ไม่สามารถดาวน์โหลดได้')
  } finally {
    isProcessing.value = false
  }
}

// Check if user has enough points
const hasEnoughPoints = computed(() => {
  if (!downloadPricing.value) return false
  const userPoints = authStore.user?.pp || 0
  return userPoints >= downloadPricing.value.cost_points
})

const userPoints = computed(() => authStore.user?.pp || 0)

// Helpers
const getGradeColor = (grade: string) => {
  switch (grade) {
    case 'A': return 'text-green-600 dark:text-green-400'
    case 'B+':
    case 'B': return 'text-blue-600 dark:text-blue-400'
    case 'C+':
    case 'C': return 'text-yellow-600 dark:text-yellow-400'
    case 'D+':
    case 'D': return 'text-orange-600 dark:text-orange-400'
    case 'F': return 'text-red-600 dark:text-red-400'
    default: return 'text-gray-600'
  }
}

const getGradeBg = (grade: string) => {
  switch (grade) {
    case 'A': return 'bg-green-100 dark:bg-green-900'
    case 'B+':
    case 'B': return 'bg-blue-100 dark:bg-blue-900'
    case 'C+':
    case 'C': return 'bg-yellow-100 dark:bg-yellow-900'
    case 'D+':
    case 'D': return 'bg-orange-100 dark:bg-orange-900'
    case 'F': return 'bg-red-100 dark:bg-red-900'
    default: return 'bg-gray-100'
  }
}

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'pending': return 'รอการยืนยัน'
    case 'accepted': return 'ยืนยันแล้ว'
    case 'appealed': return 'อุทธรณ์'
    case 'finalized': return 'สิ้นสุด'
    default: return status
  }
}

const gradeOptions = ['A', 'B+', 'B', 'C+', 'C', 'D+', 'D']
const isPassed = computed(() => gradeData.value?.final_grade && gradeData.value.final_grade !== 'F')
const canAccept = computed(() => gradeData.value?.status === 'pending' && gradeData.value?.final_grade)
const canAppeal = computed(() => 
  gradeData.value?.status === 'pending' && 
  gradeData.value?.final_grade &&
  !gradeData.value?.has_pending_appeal &&
  course.value?.allow_grade_appeals
)
</script>

<template>
  <NuxtLayout name="course">
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
      <!-- Header -->
      <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
            ผลการเรียน
          </h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ course?.name }}
          </p>
        </div>
      </div>

      <!-- Content -->
      <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        <!-- Loading -->
        <div v-if="isLoading" class="flex justify-center py-12">
          <Icon icon="heroicons:arrow-path" class="w-8 h-8 animate-spin text-primary-600" />
        </div>

        <template v-else-if="gradeData">
          <!-- Grade Card -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-lg">
            <!-- Grade Display -->
            <div class="p-6 sm:p-8 text-center" :class="getGradeBg(gradeData.final_grade || 'F')">
              <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">เกรดของคุณ</p>
              <div 
                class="text-7xl sm:text-8xl font-bold"
                :class="getGradeColor(gradeData.final_grade || 'F')"
              >
                {{ gradeData.final_grade || '-' }}
              </div>
              <div v-if="gradeData.total_score !== null" class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                คะแนนรวม: <span class="font-semibold">{{ gradeData.total_score }}</span>/100
              </div>
            </div>

            <!-- Status -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
              <div class="flex items-center justify-center gap-2">
                <Icon 
                  :icon="gradeData.status === 'accepted' ? 'heroicons:check-circle' : 'heroicons:clock'" 
                  class="w-5 h-5"
                  :class="gradeData.status === 'accepted' ? 'text-green-600' : 'text-yellow-600'"
                />
                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                  {{ getStatusLabel(gradeData.status) }}
                </span>
              </div>
            </div>

            <!-- Score Breakdown -->
            <div v-if="gradeData.score_breakdown" class="p-6 border-t border-gray-200 dark:border-gray-600">
              <h3 class="font-semibold text-gray-900 dark:text-white mb-4">รายละเอียดคะแนน</h3>
              <div class="space-y-3">
                <div v-if="gradeData.score_breakdown.attendance !== undefined" class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-300">เข้าเรียน</span>
                  <span class="font-medium text-gray-900 dark:text-white">{{ gradeData.score_breakdown.attendance }}%</span>
                </div>
                <div v-if="gradeData.score_breakdown.assignments !== undefined" class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-300">งานที่มอบหมาย</span>
                  <span class="font-medium text-gray-900 dark:text-white">{{ gradeData.score_breakdown.assignments }}</span>
                </div>
                <div v-if="gradeData.score_breakdown.quizzes !== undefined" class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-300">แบบทดสอบ</span>
                  <span class="font-medium text-gray-900 dark:text-white">{{ gradeData.score_breakdown.quizzes }}</span>
                </div>
                <div v-if="gradeData.score_breakdown.exams !== undefined" class="flex justify-between">
                  <span class="text-gray-600 dark:text-gray-300">สอบ</span>
                  <span class="font-medium text-gray-900 dark:text-white">{{ gradeData.score_breakdown.exams }}</span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div v-if="canAccept || canAppeal" class="p-6 border-t border-gray-200 dark:border-gray-600">
              <div class="flex flex-col sm:flex-row gap-3">
                <button
                  v-if="canAccept"
                  @click="showAcceptModal = true"
                  class="flex-1 inline-flex items-center justify-center px-6 py-3 text-base font-medium text-white bg-green-600 rounded-xl hover:bg-green-700"
                >
                  <Icon icon="heroicons:check" class="w-5 h-5 mr-2" />
                  ยืนยันเกรด
                </button>
                <button
                  v-if="canAppeal"
                  @click="showAppealModal = true"
                  class="flex-1 inline-flex items-center justify-center px-6 py-3 text-base font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50"
                >
                  <Icon icon="heroicons:document-text" class="w-5 h-5 mr-2" />
                  อุทธรณ์เกรด
                </button>
              </div>
            </div>

            <!-- Appeal status -->
            <div v-if="gradeData.has_pending_appeal" class="p-6 border-t border-gray-200 dark:border-gray-600 bg-yellow-50 dark:bg-yellow-900/20">
              <div class="flex items-center gap-3">
                <Icon icon="heroicons:clock" class="w-6 h-6 text-yellow-600" />
                <div>
                  <p class="font-medium text-yellow-800 dark:text-yellow-200">กำลังอุทธรณ์</p>
                  <p class="text-sm text-yellow-600 dark:text-yellow-300">คำร้องของคุณอยู่ระหว่างการพิจารณา</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Certificate -->
          <div v-if="certificate" class="mt-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
              <div class="flex items-center gap-4 flex-1">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-xl">
                  <Icon icon="heroicons:academic-cap" class="w-8 h-8 text-green-600" />
                </div>
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-900 dark:text-white">ใบประกาศนียบัตร</h3>
                  <p class="text-sm text-gray-500">เลขที่: {{ certificate.certificate_number }}</p>
                </div>
              </div>
              
              <!-- Download button with pricing info -->
              <div class="flex flex-col items-end gap-1">
                <button
                  @click="downloadCertificate"
                  :disabled="!hasEnoughPoints"
                  :class="[
                    'inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg',
                    hasEnoughPoints 
                      ? 'text-white bg-primary-600 hover:bg-primary-700' 
                      : 'text-gray-400 bg-gray-200 dark:bg-gray-600 cursor-not-allowed'
                  ]"
                >
                  <Icon icon="heroicons:arrow-down-tray" class="w-4 h-4 mr-2" />
                  ดาวน์โหลด
                </button>
                <p v-if="downloadPricing" class="text-xs text-gray-500">
                  ค่าดาวน์โหลด: {{ downloadPricing.cost_points?.toLocaleString() }} แต้ม ({{ downloadPricing.cost_thb }} บาท)
                </p>
                <p v-if="!hasEnoughPoints && downloadPricing" class="text-xs text-red-500">
                  แต้มไม่พอ (มี {{ userPoints.toLocaleString() }} แต้ม)
                </p>
              </div>
            </div>
          </div>

          <!-- Pass/Fail message -->
          <div v-if="gradeData.status === 'finalized'" class="mt-6">
            <div 
              class="rounded-2xl p-6 text-center"
              :class="isPassed ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'"
            >
              <Icon 
                :icon="isPassed ? 'heroicons:check-badge' : 'heroicons:x-circle'" 
                class="w-12 h-12 mx-auto"
                :class="isPassed ? 'text-green-600' : 'text-red-600'"
              />
              <p class="mt-3 text-lg font-medium" :class="isPassed ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'">
                {{ isPassed ? 'ยินดีด้วย! คุณผ่านการเรียนแล้ว' : 'คุณไม่ผ่านการเรียน' }}
              </p>
              <p v-if="!isPassed && course?.has_remediation" class="mt-1 text-sm text-red-600 dark:text-red-300">
                กรุณาติดต่อผู้สอนเพื่อลงทะเบียนซ่อมเสริม
              </p>
            </div>
          </div>
        </template>

        <!-- No grade yet -->
        <template v-else>
          <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-12 text-center">
            <Icon icon="heroicons:academic-cap" class="w-16 h-16 mx-auto text-gray-400" />
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">ยังไม่มีเกรด</h3>
            <p class="mt-2 text-gray-500">เกรดของคุณจะแสดงเมื่อผู้สอนประกาศผล</p>
          </div>
        </template>
      </div>

      <!-- Accept Modal -->
      <Teleport to="body">
        <div v-if="showAcceptModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="showAcceptModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-6">
              <div class="text-center">
                <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                  <Icon icon="heroicons:check" class="w-8 h-8 text-green-600" />
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">ยืนยันเกรด</h3>
                <p class="mt-2 text-sm text-gray-500">
                  คุณต้องการยืนยันเกรด <span class="font-bold" :class="getGradeColor(gradeData?.final_grade)">{{ gradeData?.final_grade }}</span> หรือไม่?
                </p>
                <p class="mt-1 text-xs text-gray-400">เมื่อยืนยันแล้วจะไม่สามารถเปลี่ยนแปลงได้</p>
              </div>
              <div class="mt-6 flex gap-3">
                <button @click="showAcceptModal = false" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg">
                  ยกเลิก
                </button>
                <button @click="acceptGrade" :disabled="isProcessing" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg disabled:opacity-50">
                  ยืนยัน
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Appeal Modal -->
      <Teleport to="body">
        <div v-if="showAppealModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="showAppealModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ยื่นอุทธรณ์เกรด</h3>

              <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                  เกรดปัจจุบันของคุณ: <span class="font-bold">{{ gradeData?.final_grade }}</span>
                </p>
              </div>

              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เกรดที่ต้องการ (ถ้ามี)</label>
                  <select v-model="appealForm.requested_grade" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg">
                    <option value="">- ไม่ระบุ -</option>
                    <option v-for="g in gradeOptions" :key="g" :value="g">{{ g }}</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เหตุผลในการอุทธรณ์ <span class="text-red-500">*</span></label>
                  <textarea
                    v-model="appealForm.reason"
                    rows="4"
                    placeholder="อธิบายเหตุผลที่คุณต้องการอุทธรณ์เกรด..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                  ></textarea>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ลิงก์หลักฐาน (ถ้ามี)</label>
                  <input
                    v-model="appealForm.evidence_url"
                    type="url"
                    placeholder="https://..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg"
                  />
                </div>
              </div>

              <div class="mt-6 flex gap-3">
                <button @click="showAppealModal = false" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg">
                  ยกเลิก
                </button>
                <button
                  @click="submitAppeal"
                  :disabled="!appealForm.reason || isProcessing"
                  class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg disabled:opacity-50"
                >
                  ส่งคำร้อง
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>
      
      <!-- Download Confirmation Modal -->
      <Teleport to="body">
        <div v-if="showDownloadModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="showDownloadModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-6">
              <div class="text-center">
                <div class="mx-auto w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                  <Icon icon="heroicons:arrow-down-tray" class="w-8 h-8 text-primary-600" />
                </div>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">ดาวน์โหลดใบประกาศ</h3>
                <p class="mt-2 text-sm text-gray-500">
                  ต้องการดาวน์โหลดใบประกาศนียบัตรหรือไม่?
                </p>
              </div>
              
              <!-- Pricing info -->
              <div v-if="downloadPricing" class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-300">ค่าดาวน์โหลด:</span>
                  <span class="font-bold text-amber-600">{{ downloadPricing.cost_points?.toLocaleString() }} แต้ม</span>
                </div>
                <div class="flex items-center justify-between text-sm mt-1">
                  <span class="text-gray-600 dark:text-gray-300">เทียบเท่า:</span>
                  <span class="font-medium text-gray-700 dark:text-gray-200">{{ downloadPricing.cost_thb }} บาท</span>
                </div>
                <hr class="my-2 border-amber-200 dark:border-amber-700" />
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600 dark:text-gray-300">แต้มของคุณ:</span>
                  <span class="font-bold" :class="hasEnoughPoints ? 'text-green-600' : 'text-red-600'">
                    {{ userPoints.toLocaleString() }} แต้ม
                  </span>
                </div>
                <div class="flex items-center justify-between text-sm mt-1">
                  <span class="text-gray-600 dark:text-gray-300">หลังดาวน์โหลด:</span>
                  <span class="font-medium text-gray-700 dark:text-gray-200">
                    {{ Math.max(0, userPoints - (downloadPricing.cost_points || 0)).toLocaleString() }} แต้ม
                  </span>
                </div>
              </div>
              
              <!-- Distribution info -->
              <div v-if="downloadPricing" class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                  <Icon icon="heroicons:information-circle" class="w-3.5 h-3.5 inline mr-1" />
                  {{ downloadPricing.instructor_share_percent }}% มอบให้ผู้สอน
                </p>
              </div>
              
              <div class="mt-6 flex gap-3">
                <button 
                  @click="showDownloadModal = false" 
                  class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                >
                  ยกเลิก
                </button>
                <button
                  @click="confirmDownload"
                  :disabled="!hasEnoughPoints || isProcessing"
                  class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                >
                  <Icon v-if="isProcessing" icon="heroicons:arrow-path" class="w-4 h-4 mr-1 animate-spin inline" />
                  ยืนยันดาวน์โหลด
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </NuxtLayout>
</template>

<script setup lang="ts">
/**
 * Course Certificates
 * หน้าจัดการใบประกาศนียบัตร
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
const certificates = ref<any[]>([])
const eligibleStudents = ref<any[]>([])
const statistics = ref<any>(null)
const certificateSettings = ref<any>(null)
const isLoading = ref(true)
const isProcessing = ref(false)
const isSavingSettings = ref(false)

// Filters
const searchQuery = ref('')
const statusFilter = ref<string>('all')

// Modals
const showIssueModal = ref(false)
const showSettingsModal = ref(false)
const selectedStudents = ref<number[]>([])

// Settings form
const settingsForm = ref({
  certificate_download_cost: 10,
  certificate_free_download: false
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
    fetchCertificates(),
    fetchEligibleStudents(),
    fetchStatistics(),
    fetchCertificateSettings()
  ])
}

const fetchCertificates = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/certificates`)
    if (res.success && res.data) {
      certificates.value = res.data.certificates || res.data || []
    }
  } catch (err) {
    console.error('Failed to fetch certificates:', err)
  }
}

const fetchEligibleStudents = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/certificates/eligible`)
    if (res.success && res.data) {
      eligibleStudents.value = res.data.students || res.data || []
    }
  } catch (err) {
    console.error('Failed to fetch eligible students:', err)
  }
}

const fetchStatistics = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/certificates/statistics`)
    if (res.success && res.data) {
      statistics.value = res.data
    }
  } catch (err) {
    console.error('Failed to fetch statistics:', err)
  }
}

const fetchCertificateSettings = async () => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/certificates/settings`)
    if (res.success && res.data) {
      certificateSettings.value = res.data
      // Populate form
      settingsForm.value = {
        certificate_download_cost: res.data.cost_thb ?? 10,
        certificate_free_download: res.data.is_free ?? false
      }
    }
  } catch (err) {
    console.error('Failed to fetch certificate settings:', err)
  }
}

// Save certificate settings
const saveSettings = async () => {
  isSavingSettings.value = true
  try {
    const res: any = await api.patch(`/api/courses/${courseId.value}/certificates/settings`, {
      certificate_download_cost: settingsForm.value.certificate_free_download 
        ? null 
        : settingsForm.value.certificate_download_cost,
      certificate_free_download: settingsForm.value.certificate_free_download
    })
    if (res.success) {
      useToast().success('บันทึกการตั้งค่าสำเร็จ')
      showSettingsModal.value = false
      await fetchCertificateSettings()
    }
  } catch (err: any) {
    console.error('Failed to save settings:', err)
    useToast().error(err.message || 'ไม่สามารถบันทึกการตั้งค่า')
  } finally {
    isSavingSettings.value = false
  }
}

// Points calculation
const POINTS_PER_THB = 1080
const calculatedPoints = computed(() => {
  if (settingsForm.value.certificate_free_download) return 0
  return (settingsForm.value.certificate_download_cost || 0) * POINTS_PER_THB
})

// Filtered certificates
const filteredCertificates = computed(() => {
  let result = [...certificates.value]

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter((c: any) =>
      c.member?.user?.name?.toLowerCase().includes(query) ||
      c.certificate_number?.toLowerCase().includes(query)
    )
  }

  if (statusFilter.value !== 'all') {
    result = result.filter((c: any) => c.status === statusFilter.value)
  }

  return result
})

// Issue certificates
const issueCertificates = async () => {
  if (selectedStudents.value.length === 0) return
  
  isProcessing.value = true
  try {
    const res: any = await api.post(`/api/courses/${courseId.value}/certificates/issue`, {
      member_ids: selectedStudents.value
    })
    if (res.success) {
      useToast().success(`ออกใบประกาศนียบัตรสำเร็จ ${selectedStudents.value.length} ใบ`)
      showIssueModal.value = false
      selectedStudents.value = []
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to issue certificates:', err)
    useToast().error('ไม่สามารถออกใบประกาศนียบัตร')
  } finally {
    isProcessing.value = false
  }
}

// Issue all eligible
const issueAllEligible = async () => {
  if (eligibleStudents.value.length === 0) return
  
  isProcessing.value = true
  try {
    const res: any = await api.post(`/api/courses/${courseId.value}/certificates/issue-all`)
    if (res.success) {
      useToast().success('ออกใบประกาศนียบัตรทั้งหมดสำเร็จ')
      await fetchData()
    }
  } catch (err) {
    console.error('Failed to issue all certificates:', err)
    useToast().error('ไม่สามารถออกใบประกาศนียบัตร')
  } finally {
    isProcessing.value = false
  }
}

// Download certificate
const downloadCertificate = async (cert: any) => {
  try {
    const res: any = await api.get(`/api/courses/${courseId.value}/certificates/${cert.id}/download`)
    if (res.success && res.data?.url) {
      window.open(res.data.url, '_blank')
    }
  } catch (err) {
    console.error('Failed to download certificate:', err)
    useToast().error('ไม่สามารถดาวน์โหลด')
  }
}

// Revoke certificate
const revokeCertificate = async (cert: any) => {
  if (!confirm('คุณแน่ใจหรือไม่ที่จะเพิกถอนใบประกาศนียบัตรนี้?')) return
  
  isProcessing.value = true
  try {
    const res: any = await api.post(`/api/courses/${courseId.value}/certificates/${cert.id}/revoke`)
    if (res.success) {
      useToast().success('เพิกถอนสำเร็จ')
      await fetchCertificates()
    }
  } catch (err) {
    console.error('Failed to revoke certificate:', err)
    useToast().error('ไม่สามารถเพิกถอน')
  } finally {
    isProcessing.value = false
  }
}

// Status helpers
const getStatusColor = (status: string) => {
  switch (status) {
    case 'issued': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
    case 'revoked': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'issued': return 'ออกแล้ว'
    case 'revoked': return 'เพิกถอน'
    default: return status
  }
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const selectAll = () => {
  if (selectedStudents.value.length === eligibleStudents.value.length) {
    selectedStudents.value = []
  } else {
    selectedStudents.value = eligibleStudents.value.map((s: any) => s.id)
  }
}
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
                ใบประกาศนียบัตร
              </h1>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                จัดการและออกใบประกาศนียบัตรให้นักเรียนที่ผ่านการเรียน
              </p>
            </div>
            <div class="flex gap-2">
              <button
                @click="showSettingsModal = true"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600"
              >
                <Icon icon="heroicons:cog-6-tooth" class="w-4 h-4 mr-2" />
                ตั้งค่า
              </button>
              <button
                v-if="eligibleStudents.length > 0"
                @click="issueAllEligible"
                :disabled="isProcessing"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50"
              >
                <Icon icon="heroicons:check-circle" class="w-4 h-4 mr-2" />
                ออกทั้งหมด ({{ eligibleStudents.length }})
              </button>
              <button
                @click="showIssueModal = true"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
              >
                <Icon icon="heroicons:document-plus" class="w-4 h-4 mr-2" />
                ออกใบประกาศ
              </button>
            </div>
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
                  <Icon icon="heroicons:document-text" class="w-5 h-5 sm:w-6 sm:h-6 text-gray-600" />
                </div>
                <div>
                  <p class="text-xs sm:text-sm text-gray-500">ออกแล้วทั้งหมด</p>
                  <p class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">
                    {{ statistics?.total_issued || certificates.length }}
                  </p>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 sm:p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                  <Icon icon="heroicons:clock" class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" />
                </div>
                <div>
                  <p class="text-xs sm:text-sm text-gray-500">รอออกใบประกาศ</p>
                  <p class="text-lg sm:text-2xl font-bold text-blue-600">
                    {{ eligibleStudents.length }}
                  </p>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 sm:p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                  <Icon icon="heroicons:check-badge" class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" />
                </div>
                <div>
                  <p class="text-xs sm:text-sm text-gray-500">ใช้งานได้</p>
                  <p class="text-lg sm:text-2xl font-bold text-green-600">
                    {{ statistics?.active || 0 }}
                  </p>
                </div>
              </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-5 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 sm:p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                  <Icon icon="heroicons:x-circle" class="w-5 h-5 sm:w-6 sm:h-6 text-red-600" />
                </div>
                <div>
                  <p class="text-xs sm:text-sm text-gray-500">เพิกถอน</p>
                  <p class="text-lg sm:text-2xl font-bold text-red-600">
                    {{ statistics?.revoked || 0 }}
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
                    placeholder="ค้นหาชื่อหรือเลขที่ใบประกาศ..."
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  />
                </div>
              </div>
              <select
                v-model="statusFilter"
                class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="all">ทุกสถานะ</option>
                <option value="issued">ออกแล้ว</option>
                <option value="revoked">เพิกถอน</option>
              </select>
            </div>
          </div>

          <!-- Certificates List -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Desktop -->
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">นักเรียน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">เลขที่</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">เกรด</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">วันที่ออก</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">จัดการ</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                  <tr v-for="cert in filteredCertificates" :key="cert.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <img :src="cert.member?.user?.avatar || '/images/default-avatar.png'" class="w-10 h-10 rounded-full" />
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900 dark:text-white">{{ cert.member?.user?.name }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <span class="text-sm font-mono text-gray-900 dark:text-white">{{ cert.certificate_number }}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                      <span class="text-lg font-bold text-gray-900 dark:text-white">{{ cert.grade }}</span>
                    </td>
                    <td class="px-6 py-4 text-center text-sm text-gray-500">
                      {{ formatDate(cert.issued_at) }}
                    </td>
                    <td class="px-6 py-4 text-center">
                      <span
                        :class="getStatusColor(cert.status)"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      >
                        {{ getStatusLabel(cert.status) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                      <div class="flex items-center justify-end gap-2">
                        <button
                          @click="downloadCertificate(cert)"
                          class="p-2 text-gray-400 hover:text-primary-600"
                          title="ดาวน์โหลด"
                        >
                          <Icon icon="heroicons:arrow-down-tray" class="w-5 h-5" />
                        </button>
                        <button
                          v-if="cert.status === 'issued'"
                          @click="revokeCertificate(cert)"
                          class="p-2 text-gray-400 hover:text-red-600"
                          title="เพิกถอน"
                        >
                          <Icon icon="heroicons:x-mark" class="w-5 h-5" />
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Mobile -->
            <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-600">
              <div v-for="cert in filteredCertificates" :key="cert.id" class="p-4">
                <div class="flex items-start justify-between">
                  <div class="flex items-center">
                    <img :src="cert.member?.user?.avatar || '/images/default-avatar.png'" class="w-12 h-12 rounded-full" />
                    <div class="ml-3">
                      <p class="font-medium text-gray-900 dark:text-white">{{ cert.member?.user?.name }}</p>
                      <p class="text-xs text-gray-500 font-mono">{{ cert.certificate_number }}</p>
                    </div>
                  </div>
                  <span
                    :class="getStatusColor(cert.status)"
                    class="px-2 py-1 text-xs font-medium rounded-full"
                  >
                    {{ getStatusLabel(cert.status) }}
                  </span>
                </div>

                <div class="mt-3 flex items-center justify-between">
                  <div class="flex gap-4 text-sm">
                    <span>เกรด: <span class="font-bold">{{ cert.grade }}</span></span>
                    <span class="text-gray-500">{{ formatDate(cert.issued_at) }}</span>
                  </div>
                  <div class="flex gap-2">
                    <button
                      @click="downloadCertificate(cert)"
                      class="p-2 text-primary-600"
                    >
                      <Icon icon="heroicons:arrow-down-tray" class="w-5 h-5" />
                    </button>
                    <button
                      v-if="cert.status === 'issued'"
                      @click="revokeCertificate(cert)"
                      class="p-2 text-red-600"
                    >
                      <Icon icon="heroicons:x-mark" class="w-5 h-5" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty -->
            <div v-if="filteredCertificates.length === 0" class="py-12 text-center">
              <Icon icon="heroicons:document" class="w-12 h-12 mx-auto text-gray-400" />
              <p class="mt-2 text-gray-500">ยังไม่มีใบประกาศนียบัตร</p>
            </div>
          </div>
        </template>
      </div>

      <!-- Issue Modal -->
      <Teleport to="body">
        <div v-if="showIssueModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50" @click="showIssueModal = false"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full p-6 max-h-[80vh] overflow-y-auto">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ออกใบประกาศนียบัตร</h3>

              <div v-if="eligibleStudents.length > 0">
                <div class="flex items-center justify-between mb-3">
                  <label class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                    <input type="checkbox" @change="selectAll" :checked="selectedStudents.length === eligibleStudents.length" class="mr-2" />
                    เลือกทั้งหมด
                  </label>
                  <span class="text-sm text-gray-500">{{ selectedStudents.length }}/{{ eligibleStudents.length }}</span>
                </div>

                <div class="space-y-2 max-h-80 overflow-y-auto">
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
                    <img :src="student.user?.avatar || '/images/default-avatar.png'" class="w-10 h-10 rounded-full" />
                    <div class="ml-3 flex-1">
                      <p class="text-sm font-medium text-gray-900 dark:text-white">{{ student.user?.name }}</p>
                    </div>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ student.final_grade }}</span>
                  </label>
                </div>

                <div class="mt-6 flex gap-3">
                  <button @click="showIssueModal = false" class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                    ยกเลิก
                  </button>
                  <button
                    @click="issueCertificates"
                    :disabled="selectedStudents.length === 0 || isProcessing"
                    class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                  >
                    ออกใบประกาศ ({{ selectedStudents.length }})
                  </button>
                </div>
              </div>
              <div v-else class="py-8 text-center">
                <Icon icon="heroicons:check-circle" class="w-16 h-16 mx-auto text-green-400" />
                <p class="mt-4 text-gray-600 dark:text-gray-300">ออกใบประกาศนียบัตรครบทุกคนแล้ว!</p>
                <button @click="showIssueModal = false" class="mt-4 px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg">
                  ปิด
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Settings Modal -->
      <Teleport to="body">
        <div v-if="showSettingsModal" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500/75 dark:bg-gray-900/75" @click="showSettingsModal = false"></div>
            <div class="relative z-10 w-full max-w-md p-6 bg-white dark:bg-gray-800 rounded-xl shadow-xl">
              <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                  <Icon icon="heroicons:cog-6-tooth" class="inline w-5 h-5 mr-2" />
                  ตั้งค่าการดาวน์โหลดใบประกาศ
                </h3>
                <button @click="showSettingsModal = false" class="p-1 text-gray-400 hover:text-gray-500">
                  <Icon icon="heroicons:x-mark" class="w-5 h-5" />
                </button>
              </div>

              <div class="space-y-6">
                <!-- Free Download Toggle -->
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">ดาวน์โหลดฟรี</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">อนุญาตให้นักเรียนดาวน์โหลดฟรีโดยไม่หักแต้ม</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="settingsForm.certificate_free_download" class="sr-only peer" />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                  </label>
                </div>

                <!-- Download Cost -->
                <div v-if="!settingsForm.certificate_free_download" class="space-y-3">
                  <label class="block">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ค่าดาวน์โหลด (บาท)</span>
                    <div class="mt-1 relative">
                      <input
                        type="number"
                        v-model.number="settingsForm.certificate_download_cost"
                        min="0"
                        max="1000"
                        step="1"
                        class="block w-full px-4 py-2 pr-12 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      />
                      <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400">บาท</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      ค่าเริ่มต้น: 10 บาท (ใส่ 0 เพื่อให้ดาวน์โหลดฟรี)
                    </p>
                  </label>

                  <!-- Points Preview -->
                  <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <div class="flex items-center justify-between">
                      <span class="text-sm text-amber-800 dark:text-amber-200">แต้มที่จะถูกหัก:</span>
                      <span class="text-lg font-bold text-amber-600 dark:text-amber-400">
                        {{ calculatedPoints.toLocaleString() }} แต้ม
                      </span>
                    </div>
                    <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                      (1 บาท = {{ POINTS_PER_THB.toLocaleString() }} แต้ม)
                    </p>
                  </div>

                  <!-- Revenue Split Info -->
                  <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">การแบ่งรายได้:</p>
                    <div class="space-y-1 text-sm text-blue-700 dark:text-blue-300">
                      <div class="flex justify-between">
                        <span>ผู้สอน (90%):</span>
                        <span class="font-medium">{{ Math.floor(calculatedPoints * 0.9).toLocaleString() }} แต้ม</span>
                      </div>
                      <div class="flex justify-between">
                        <span>ระบบ (10%):</span>
                        <span class="font-medium">{{ Math.floor(calculatedPoints * 0.1).toLocaleString() }} แต้ม</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Free Download Info -->
                <div v-else class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                  <div class="flex items-center">
                    <Icon icon="heroicons:check-circle" class="w-5 h-5 text-green-500 mr-2" />
                    <span class="text-sm text-green-700 dark:text-green-300">
                      นักเรียนจะสามารถดาวน์โหลดใบประกาศได้ฟรี
                    </span>
                  </div>
                </div>

                <!-- Current Settings -->
                <div v-if="certificateSettings" class="p-4 bg-gray-100 dark:bg-gray-700/50 rounded-lg">
                  <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">การตั้งค่าปัจจุบัน:</p>
                  <p class="text-sm text-gray-700 dark:text-gray-300">
                    <template v-if="certificateSettings.is_free">
                      <Icon icon="heroicons:gift" class="inline w-4 h-4 text-green-500 mr-1" />
                      ดาวน์โหลดฟรี
                    </template>
                    <template v-else>
                      <Icon icon="heroicons:currency-dollar" class="inline w-4 h-4 text-amber-500 mr-1" />
                      {{ certificateSettings.cost_thb }} บาท ({{ certificateSettings.cost_points?.toLocaleString() }} แต้ม)
                      <span v-if="certificateSettings.is_custom" class="ml-1 text-xs text-primary-500">(กำหนดเอง)</span>
                    </template>
                  </p>
                </div>
              </div>

              <!-- Actions -->
              <div class="mt-6 flex gap-3">
                <button 
                  @click="showSettingsModal = false" 
                  class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
                >
                  ยกเลิก
                </button>
                <button
                  @click="saveSettings"
                  :disabled="isSavingSettings"
                  class="flex-1 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                >
                  <Icon v-if="isSavingSettings" icon="heroicons:arrow-path" class="w-4 h-4 mr-1 inline animate-spin" />
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

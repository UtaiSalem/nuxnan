<script setup lang="ts">
/**
 * Academy Admin - Students Dashboard
 * หน้าทะเบียนนักเรียน (Dashboard + List)
 */
import { Icon } from '@iconify/vue'
import { useStudentAccountService } from '~/services/studentAccountService'
import StudentDataTable from '~/components/academy/student/StudentDataTable.vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const academyName = computed(() => route.params.name as string)
const academyId = inject<Ref<number | null>>('academyId', ref(null))
const { exportInvitations } = useStudentAccountService()

// Setup basic stats
const stats = ref({
  totalStudents: 0,
  newIntakes: 0,
  unassigned: 0,
  pendingActivation: 0,
})

const isLoading = ref(true)
const api = useApi()

const fetchStats = async () => {
  if (!academyId.value) return
  isLoading.value = true
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/student-intakes/stats`)
    if (res && res.stats) {
      stats.value = res.stats
    } else {
      // Fallback
      stats.value = {
        totalStudents: res?.totalStudents || 0,
        newIntakes: res?.newIntakes || 0,
        unassigned: res?.unassigned || 0,
        pendingActivation: res?.pendingActivation || 0
      }
    }
  } catch (error) {
    console.error('Failed to fetch student stats', error)
  } finally {
    isLoading.value = false
  }
}

const exportStudents = async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyId.value}/student-intakes/export`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([res]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `students_${academyName.value}.csv`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('Failed to export students', error)
  }
}

const handleExportInvitations = async () => {
  try {
    await exportInvitations(String(academyId.value))
  } catch (error) {
    console.error('Failed to export invitations', error)
  }
}

watch(academyId, (id) => {
  if (id) fetchStats()
}, { immediate: true })
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ทะเบียนนักเรียน</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">จัดการข้อมูลนักเรียน รับเข้า และประวัติการเรียน</p>
      </div>
      
      <div class="flex flex-wrap gap-2 w-full sm:w-auto">
        <NuxtLink
          :to="`/academies/${academyName}/admin/students/import-history`"
          class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors"
        >
          <Icon icon="fluent:history-24-regular" class="w-5 h-5" />
          <span class="hidden sm:inline">ประวัตินำเข้า</span>
        </NuxtLink>
        <button
          @click="exportStudents"
          class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors"
        >
          <Icon icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
          <span class="hidden sm:inline">ส่งออกรายชื่อ</span>
        </button>
        <button
          @click="handleExportInvitations"
          class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/40 text-purple-700 dark:text-purple-400 font-medium rounded-lg transition-colors border border-purple-200 dark:border-purple-800"
        >
          <Icon icon="fluent:mail-arrow-down-24-regular" class="w-5 h-5" />
          <span class="hidden sm:inline">ลิงก์เปิดบัญชี</span>
        </button>
        <NuxtLink
          :to="`/academies/${academyName}/admin/students/import`"
          class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors"
        >
          <Icon icon="fluent:arrow-upload-24-regular" class="w-5 h-5" />
          <span class="hidden sm:inline">นำเข้าข้อมูล</span>
        </NuxtLink>
        <NuxtLink
          :to="`/academies/${academyName}/admin/students/intake`"
          class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
        >
          <Icon icon="fluent:person-add-24-filled" class="w-5 h-5" />
          รับนักเรียนใหม่
        </NuxtLink>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <!-- Total Students -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
            <Icon icon="fluent:people-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">กำลังเรียน</p>
            <div class="flex items-baseline gap-2">
              <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                <span v-if="isLoading" class="animate-pulse bg-gray-200 dark:bg-gray-700 rounded h-6 w-16 inline-block"></span>
                <span v-else>{{ stats.totalStudents }}</span>
              </h3>
            </div>
          </div>
        </div>
      </div>
      
      <!-- New Intakes -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-full bg-green-50 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
            <Icon icon="fluent:person-available-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">รับเข้าใหม่</p>
            <div class="flex items-baseline gap-2">
              <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                <span v-if="isLoading" class="animate-pulse bg-gray-200 dark:bg-gray-700 rounded h-6 w-16 inline-block"></span>
                <span v-else>{{ stats.newIntakes }}</span>
              </h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Unassigned -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-full bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center flex-shrink-0">
            <Icon icon="fluent:class-24-regular" class="w-6 h-6 text-orange-600 dark:text-orange-400" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ยังไม่มีห้องเรียน</p>
            <div class="flex items-baseline gap-2">
              <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                <span v-if="isLoading" class="animate-pulse bg-gray-200 dark:bg-gray-700 rounded h-6 w-16 inline-block"></span>
                <span v-else>{{ stats.unassigned }}</span>
              </h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending Activation -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-full bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
            <Icon icon="fluent:mail-alert-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">รอเปิดบัญชี</p>
            <div class="flex items-baseline gap-2">
              <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                <span v-if="isLoading" class="animate-pulse bg-gray-200 dark:bg-gray-700 rounded h-6 w-16 inline-block"></span>
                <span v-else>{{ stats.pendingActivation }}</span>
              </h3>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Student List -->
    <StudentDataTable :academy-id="academyId" />
  </div>
</template>

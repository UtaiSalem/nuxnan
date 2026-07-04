<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useStudentImportService } from '~/services/studentImportService'

definePageMeta({
  layout: false,
  middleware: ['auth', 'academy-role'],
})

const route = useRoute()
const academyName = computed(() => route.params.name as string)

const { listBatches } = useStudentImportService()

const batches = ref<any[]>([])
const loading = ref(true)
const currentPage = ref(1)
const totalPages = ref(1)

const fetchBatches = async () => {
  loading.value = true
  try {
    const res: any = await listBatches(academyName.value, currentPage.value)
    batches.value = res.data || []
    totalPages.value = res.last_page || 1
  } catch (error) {
    console.error('Failed to fetch import history', error)
  } finally {
    loading.value = false
  }
}

const statusConfig: Record<string, { label: string; class: string; icon: string }> = {
  uploaded: { label: 'อัปโหลดแล้ว', class: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300', icon: 'fluent:arrow-upload-24-regular' },
  validating: { label: 'กำลังตรวจสอบ', class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', icon: 'fluent:spinner-ios-20-regular' },
  validated: { label: 'รอยืนยัน', class: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400', icon: 'fluent:checkmark-circle-24-regular' },
  processing: { label: 'กำลังนำเข้า', class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', icon: 'fluent:spinner-ios-20-regular' },
  completed: { label: 'สำเร็จ', class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', icon: 'fluent:checkmark-circle-24-filled' },
  partial: { label: 'สำเร็จบางส่วน', class: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400', icon: 'fluent:warning-24-filled' },
  failed: { label: 'ล้มเหลว', class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400', icon: 'fluent:dismiss-circle-24-filled' },
  cancelled: { label: 'ยกเลิก', class: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400', icon: 'fluent:dismiss-24-regular' },
}

const getStatus = (status: string) => statusConfig[status] || statusConfig.uploaded

const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }) + ' ' + d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })
}

onMounted(() => fetchBatches())
</script>

<template>
  <div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ประวัติการนำเข้าข้อมูล</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ประวัติการนำเข้าข้อมูลนักเรียนทั้งหมดของสถาบัน</p>
      </div>
      <NuxtLink :to="`/academies/${academyName}/admin/students`" class="btn-ghost flex items-center gap-2">
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        กลับ
      </NuxtLink>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 animate-pulse">
        <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-1/3 mb-2"></div>
        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="batches.length === 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
      <Icon icon="fluent:history-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ยังไม่มีประวัติการนำเข้า</h3>
      <p class="text-gray-500 dark:text-gray-400 mt-2">เมื่อนำเข้าข้อมูลนักเรียน ประวัติจะแสดงที่นี่</p>
      <NuxtLink :to="`/academies/${academyName}/admin/students/import`" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
        <Icon icon="fluent:arrow-upload-24-regular" class="w-5 h-5" />
        นำเข้าข้อมูล
      </NuxtLink>
    </div>

    <!-- Batch List -->
    <div v-else class="space-y-3">
      <div
        v-for="batch in batches"
        :key="batch.id"
        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-4 hover:shadow-md transition-shadow"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
              <Icon icon="fluent:document-table-24-regular" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
            </div>
            <div>
              <p class="font-medium text-gray-900 dark:text-white text-sm">{{ batch.original_filename || batch.filename }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                {{ formatDate(batch.created_at) }}
                <span v-if="batch.created_by_name"> · {{ batch.created_by_name }}</span>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <!-- Stats -->
            <div class="hidden sm:flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
              <span class="flex items-center gap-1">
                <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
                {{ batch.imported_rows || 0 }}/{{ batch.total_rows || 0 }}
              </span>
              <span v-if="batch.invalid_rows > 0" class="flex items-center gap-1 text-red-500">
                <Icon icon="fluent:warning-24-regular" class="w-4 h-4" />
                {{ batch.invalid_rows }} ผิดพลาด
              </span>
            </div>

            <!-- Status Badge -->
            <span :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium', getStatus(batch.status).class]">
              <Icon :icon="getStatus(batch.status).icon" class="w-3.5 h-3.5" :class="{ 'animate-spin': batch.status === 'processing' || batch.status === 'validating' }" />
              {{ getStatus(batch.status).label }}
            </span>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex justify-center gap-2 pt-4">
        <button
          :disabled="currentPage <= 1"
          @click="currentPage--; fetchBatches()"
          class="px-3 py-1.5 rounded-md text-sm border border-gray-200 dark:border-gray-700 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
          ก่อนหน้า
        </button>
        <span class="px-3 py-1.5 text-sm text-gray-500">{{ currentPage }} / {{ totalPages }}</span>
        <button
          :disabled="currentPage >= totalPages"
          @click="currentPage++; fetchBatches()"
          class="px-3 py-1.5 rounded-md text-sm border border-gray-200 dark:border-gray-700 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
        >
          ถัดไป
        </button>
      </div>
    </div>
  </div>
</template>

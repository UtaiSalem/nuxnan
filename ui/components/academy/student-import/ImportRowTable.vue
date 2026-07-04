<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
    <!-- Header/Filter (Optional) -->
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
      <h3 class="text-sm font-semibold text-gray-900 dark:text-white">รายการข้อมูล</h3>
      <div class="flex gap-2">
        <select v-model="filterStatus" class="text-sm border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 py-1.5 px-3">
          <option value="">ทั้งหมด</option>
          <option value="valid">ผ่าน</option>
          <option value="warning">แจ้งเตือน</option>
          <option value="invalid">ไม่ผ่าน</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto min-h-[300px] relative">
      <div v-if="isLoading" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 flex items-center justify-center z-10">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      </div>

      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-800/50">
          <tr>
            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">แถว</th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อ-สกุล (ดิบ)</th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เลขประจำตัว</th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">สถานะการตรวจสอบ</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="row in rows" :key="row.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
              #{{ row.row_number }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-medium">
              {{ formatName(row.raw_data) }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
              <div class="flex flex-col gap-0.5">
                <span>รหัส: {{ row.raw_data.student_code || '-' }}</span>
                <span class="text-xs">เลข ปชช: {{ row.raw_data.citizen_id || '-' }}</span>
              </div>
            </td>
            <td class="px-4 py-3">
              <ImportErrorBadge 
                :status="row.status" 
                :errors="row.errors" 
                :warnings="row.warnings" 
              />
            </td>
          </tr>
          <tr v-if="rows.length === 0 && !isLoading">
            <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">
              ไม่พบข้อมูล
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="meta && meta.last_page > 1" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
      <div class="text-sm text-gray-500">
        แสดง <span class="font-medium">{{ ((meta.current_page - 1) * meta.per_page) + 1 }}</span> ถึง 
        <span class="font-medium">{{ Math.min(meta.current_page * meta.per_page, meta.total) }}</span> จากทั้งหมด 
        <span class="font-medium">{{ meta.total }}</span> รายการ
      </div>
      <div class="flex gap-2">
        <button 
          @click="changePage(meta.current_page - 1)" 
          :disabled="meta.current_page === 1"
          class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm disabled:opacity-50"
        >
          ก่อนหน้า
        </button>
        <button 
          @click="changePage(meta.current_page + 1)" 
          :disabled="meta.current_page === meta.last_page"
          class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm disabled:opacity-50"
        >
          ถัดไป
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useStudentImport } from '../../../composables/useStudentImport'
import { useStudentImportService } from '../../../services/studentImportService'
import type { StudentImportRow, PaginatedRowsResponse } from '../../../types/studentImport'
import ImportErrorBadge from './ImportErrorBadge.vue'

const props = defineProps<{
  batchId: number
}>()

const route = useRoute()
const academyName = route.params.name as string
const { fetchBatch } = useStudentImport(academyName)
const { getRows } = useStudentImportService()

const rows = ref<StudentImportRow[]>([])
const meta = ref<PaginatedRowsResponse['meta'] | null>(null)
const isLoading = ref(true)
const filterStatus = ref('')
const currentPage = ref(1)

const loadRows = async () => {
  isLoading.value = true
  try {
    const res = await getRows(academyName, props.batchId, currentPage.value, 20, filterStatus.value || undefined)
    rows.value = res.data
    meta.value = res.meta
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

const changePage = (page: number) => {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return
  currentPage.value = page
  loadRows()
}

watch(filterStatus, () => {
  currentPage.value = 1
  loadRows()
})

onMounted(() => {
  loadRows()
})

const formatName = (data: any) => {
  const parts = []
  if (data.title_th) parts.push(data.title_th)
  if (data.first_name_th) parts.push(data.first_name_th)
  if (data.last_name_th) parts.push(data.last_name_th)
  return parts.join(' ') || 'ไม่ระบุชื่อ'
}
</script>

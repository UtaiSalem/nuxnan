<script setup lang="ts">
/**
 * Academy Admin - Export Home Visits
 * หน้าส่งออกข้อมูลการเยี่ยมบ้าน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const zones = ref<any[]>([])
const isLoading = ref(true)
const isExporting = ref(false)
const exportSuccess = ref(false)

// Export options
const exportOptions = ref({
  format: 'csv',
  zone_id: '',
  status: '',
  date_from: '',
  date_to: ''
})

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value && !can('home_visits.view')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await fetchZones()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchZones = async () => {
  if (!academyId.value) return
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/home-visits/zones`)
    zones.value = response.zones || response.data || []
  } catch (err) {
    console.error('Failed to fetch zones:', err)
  }
}

const doExport = async () => {
  isExporting.value = true
  exportSuccess.value = false
  
  try {
    // Build query params
    const params = new URLSearchParams()
    params.append('format', exportOptions.value.format)
    if (exportOptions.value.zone_id) {
      params.append('zone_id', exportOptions.value.zone_id)
    }
    if (exportOptions.value.status) {
      params.append('status', exportOptions.value.status)
    }
    if (exportOptions.value.date_from) {
      params.append('date_from', exportOptions.value.date_from)
    }
    if (exportOptions.value.date_to) {
      params.append('date_to', exportOptions.value.date_to)
    }
    const { blob, filename } = await api.getBlob(
      `/api/academies/${academyId.value}/home-visits/admin/export/visits?${params.toString()}`
    )
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename || `home-visits-${new Date().toISOString().slice(0, 10)}.${exportOptions.value.format}`
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
    exportSuccess.value = true
    
    setTimeout(() => {
      exportSuccess.value = false
    }, 3000)
  } catch (err) {
    console.error('Export failed:', err)
  } finally {
    isExporting.value = false
  }
}

const formatOptions = [
  { value: 'csv', label: 'CSV (.csv)', icon: 'fluent:document-text-24-regular' },
  { value: 'pdf', label: 'PDF (.pdf)', icon: 'fluent:document-pdf-24-regular' }
]

const statusOptions = [
  { value: '', label: 'ทุกสถานะ' },
  { value: 'pending', label: 'รอดำเนินการ' },
  { value: 'completed', label: 'เยี่ยมแล้ว' },
  { value: 'cancelled', label: 'ยกเลิก' }
]
</script>

<template>
  <div>
    <div class="max-w-3xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <NuxtLink 
          :to="`/academies/${academyName}/admin/home-visits`"
          class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
        >
          <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        </NuxtLink>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ส่งออกข้อมูลการเยี่ยมบ้าน</h1>
          <p class="text-gray-600 dark:text-gray-400">เลือกตัวเลือกและดาวน์โหลดไฟล์</p>
        </div>
      </div>

      <!-- Success Message -->
      <div v-if="exportSuccess" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 flex items-center gap-3">
        <Icon icon="fluent:checkmark-circle-24-regular" class="w-6 h-6 text-green-500" />
        <p class="text-green-800 dark:text-green-200">เริ่มการดาวน์โหลดแล้ว กรุณาตรวจสอบโฟลเดอร์ดาวน์โหลดของคุณ</p>
      </div>

      <!-- Export Options -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="p-6 space-y-6">
          <!-- Format Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">รูปแบบไฟล์</label>
            <div class="grid grid-cols-1 gap-3">
              <label
                v-for="format in formatOptions"
                :key="format.value"
                class="flex items-center gap-3 p-4 border rounded-lg cursor-pointer transition-colors"
                :class="[
                  exportOptions.format === format.value
                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300'
                ]"
              >
                <input
                  type="radio"
                  :value="format.value"
                  v-model="exportOptions.format"
                  class="sr-only"
                />
                <Icon :icon="format.icon" class="w-6 h-6 text-gray-500" />
                <span class="text-gray-900 dark:text-white">{{ format.label }}</span>
              </label>
            </div>
          </div>

          <!-- Filters -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">โซน</label>
              <select
                v-model="exportOptions.zone_id"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">ทุกโซน</option>
                <option v-for="zone in zones" :key="zone.id" :value="zone.id">
                  {{ zone.zone_name }}
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
              <select
                v-model="exportOptions.status"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                  {{ status.label }}
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ตั้งแต่วันที่</label>
              <input
                v-model="exportOptions.date_from"
                type="date"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ถึงวันที่</label>
              <input
                v-model="exportOptions.date_to"
                type="date"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              />
            </div>
          </div>

        </div>

        <!-- Actions -->
        <div class="p-6 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
          <NuxtLink
            :to="`/academies/${academyName}/admin/home-visits`"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            ยกเลิก
          </NuxtLink>
          <button
            @click="doExport"
            :disabled="isExporting"
            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 flex items-center gap-2"
          >
            <Icon v-if="isExporting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            <Icon v-else icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
            <span>{{ isExporting ? 'กำลังส่งออก...' : 'ส่งออกข้อมูล' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

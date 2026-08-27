<script setup lang="ts">
/**
 * Course Reports - Export PDF/Excel
 * หน้าสำหรับดาวน์โหลดรายงานสรุปการเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'course',
  middleware: ['auth']
})

const route = useRoute()
const api = useApi()
const { $toast } = useNuxtApp()

const courseId = computed(() => route.params.name as string)

// State
const isLoading = ref(true)
const reportTypes = ref<any[]>([])
const selectedReport = ref<string | null>(null)
const selectedFormat = ref<'excel' | 'pdf'>('excel')
const isExporting = ref(false)

// Filters
const selectedGroup = ref<string>('all')
const dateFrom = ref<string>('')
const dateTo = ref<string>('')
const groups = ref<any[]>([])

// Report Icons
const reportIcons: Record<string, string> = {
  grades: 'tabler:chart-bar',
  attendance: 'tabler:calendar-check',
  progress: 'tabler:trending-up',
  full: 'tabler:file-report',
  certificates: 'tabler:certificate'
}

// Report descriptions
const reportDescriptions: Record<string, string> = {
  grades: 'รายงานผลการเรียนของนักเรียนทั้งหมด รวมถึงคะแนน เกรด และสถานะ',
  attendance: 'สรุปการเข้าเรียนของนักเรียน จำนวนครั้งที่เข้าเรียน มาสาย ขาด และลา',
  progress: 'ความคืบหน้าการเรียน การทำบทเรียน แบบฝึกหัด และแบบทดสอบ',
  full: 'รายงานฉบับเต็มรวมทุกข้อมูล (เกรด การเข้าเรียน ความคืบหน้า)',
  certificates: 'รายชื่อนักเรียนที่ได้รับใบประกาศนียบัตร พร้อมสถานะ'
}

// Fetch report types
const fetchReportTypes = async () => {
  isLoading.value = true
  try {
    const response = await api.get(`/api/courses/${courseId.value}/reports/types`)
    if (response.success) {
      reportTypes.value = response.data.reports || []
      groups.value = response.data.groups || []
    }
  } catch (err) {
    console.error('Failed to fetch report types:', err)
    $toast?.error('ไม่สามารถโหลดข้อมูลรายงานได้')
  } finally {
    isLoading.value = false
  }
}

// Check if format is available for selected report
const isFormatAvailable = (format: 'excel' | 'pdf'): boolean => {
  if (!selectedReport.value) return false
  const report = reportTypes.value.find(r => r.key === selectedReport.value)
  return report?.formats?.includes(format) ?? false
}

// Get available formats for selected report
const availableFormats = computed(() => {
  if (!selectedReport.value) return []
  const report = reportTypes.value.find(r => r.key === selectedReport.value)
  return report?.formats || []
})

// Select report type
const selectReport = (reportKey: string) => {
  selectedReport.value = reportKey
  const report = reportTypes.value.find(r => r.key === reportKey)
  
  // Set default format to first available
  if (report?.formats?.length > 0) {
    selectedFormat.value = report.formats[0]
  }
}

// Export report
const exportReport = async () => {
  if (!selectedReport.value) {
    $toast?.warning('กรุณาเลือกประเภทรายงาน')
    return
  }

  isExporting.value = true
  
  try {
    // Build query params
    const params = new URLSearchParams()
    params.append('format', selectedFormat.value)
    
    if (selectedGroup.value !== 'all') {
      params.append('group_id', selectedGroup.value)
    }
    if (dateFrom.value) {
      params.append('date_from', dateFrom.value)
    }
    if (dateTo.value) {
      params.append('date_to', dateTo.value)
    }

    const url = `/api/courses/${courseId.value}/reports/${selectedReport.value}?${params.toString()}`
    
    // Download file
    const response = await api.getBlob(url)
    
    if (response.blob) {
      // Create download link
      const blob = response.blob
      const downloadUrl = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = downloadUrl
      
      // Set filename based on format
      const extension = selectedFormat.value === 'pdf' ? 'pdf' : 'xlsx'
      const reportName = reportTypes.value.find(r => r.key === selectedReport.value)?.name || selectedReport.value
      link.download = `${reportName}_${new Date().toISOString().split('T')[0]}.${extension}`
      
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(downloadUrl)
      
      $toast?.success('ดาวน์โหลดรายงานสำเร็จ')
    } else {
      throw new Error('Failed to download file')
    }
  } catch (err: any) {
    console.error('Export failed:', err)
    $toast?.error(err?.message || 'ไม่สามารถ export รายงานได้')
  } finally {
    isExporting.value = false
  }
}

// Quick export (direct download without selection UI)
const quickExport = async (reportKey: string, format: 'excel' | 'pdf') => {
  isExporting.value = true
  
  try {
    const url = `/api/courses/${courseId.value}/reports/${reportKey}?format=${format}`
    const response = await api.getBlob(url)
    
    if (response.blob) {
      const blob = response.blob
      const downloadUrl = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = downloadUrl
      
      const extension = format === 'pdf' ? 'pdf' : 'xlsx'
      const reportName = reportTypes.value.find(r => r.key === reportKey)?.name || reportKey
      link.download = `${reportName}_${new Date().toISOString().split('T')[0]}.${extension}`
      
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(downloadUrl)
      
      $toast?.success('ดาวน์โหลดรายงานสำเร็จ')
    }
  } catch (err: any) {
    console.error('Quick export failed:', err)
    $toast?.error(err?.message || 'ไม่สามารถ export รายงานได้')
  } finally {
    isExporting.value = false
  }
}

// Reset filters
const resetFilters = () => {
  selectedGroup.value = 'all'
  dateFrom.value = ''
  dateTo.value = ''
}

onMounted(() => {
  fetchReportTypes()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-10">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
              <Icon icon="tabler:file-report" class="inline mr-2" />
              รายงานสรุปการเรียน
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              ดาวน์โหลดรายงานในรูปแบบ PDF หรือ Excel
            </p>
          </div>
          
          <NuxtLink
            :to="`/Learn/Courses/${courseId}/instructor-dashboard`"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
          >
            <Icon icon="tabler:arrow-left" />
            กลับไปแดชบอร์ด
          </NuxtLink>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Loading State -->
      <div v-if="isLoading" class="flex justify-center items-center py-20">
        <Icon icon="tabler:loader" class="w-10 h-10 animate-spin text-primary-500" />
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Report Type Selection -->
        <div class="lg:col-span-2 space-y-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            เลือกประเภทรายงาน
          </h2>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div
              v-for="report in reportTypes"
              :key="report.key"
              @click="selectReport(report.key)"
              :class="[
                'relative p-4 sm:p-5 rounded-xl border-2 cursor-pointer transition-all duration-200',
                selectedReport === report.key
                  ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 shadow-md'
                  : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-300 dark:hover:border-primary-700'
              ]"
            >
              <!-- Selected indicator -->
              <div
                v-if="selectedReport === report.key"
                class="absolute top-3 right-3"
              >
                <Icon 
                  icon="tabler:circle-check-filled" 
                  class="w-6 h-6 text-primary-500" 
                />
              </div>

              <!-- Icon -->
              <div class="flex items-center gap-3 mb-3">
                <div :class="[
                  'w-12 h-12 rounded-lg flex items-center justify-center',
                  selectedReport === report.key
                    ? 'bg-primary-100 dark:bg-primary-800'
                    : 'bg-gray-100 dark:bg-gray-700'
                ]">
                  <Icon 
                    :icon="reportIcons[report.key] || 'tabler:file'" 
                    :class="[
                      'w-6 h-6',
                      selectedReport === report.key
                        ? 'text-primary-600 dark:text-primary-400'
                        : 'text-gray-600 dark:text-gray-400'
                    ]"
                  />
                </div>
                
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-900 dark:text-white">
                    {{ report.name }}
                  </h3>
                </div>
              </div>
              
              <!-- Description -->
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                {{ reportDescriptions[report.key] || report.description }}
              </p>
              
              <!-- Available Formats -->
              <div class="flex items-center gap-2">
                <span class="text-xs text-gray-400 dark:text-gray-500">รูปแบบ:</span>
                <span
                  v-for="format in report.formats"
                  :key="format"
                  :class="[
                    'inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded',
                    format === 'excel'
                      ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                  ]"
                >
                  <Icon :icon="format === 'excel' ? 'tabler:file-spreadsheet' : 'tabler:file-type-pdf'" class="w-3 h-3" />
                  {{ format.toUpperCase() }}
                </span>
              </div>

              <!-- Quick Export Buttons (on hover) -->
              <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                <button
                  v-if="report.formats.includes('excel')"
                  @click.stop="quickExport(report.key, 'excel')"
                  :disabled="isExporting"
                  class="min-h-[44px] sm:min-h-0 flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-medium text-green-700 bg-green-100 rounded-lg hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50 disabled:opacity-50"
                >
                  <Icon icon="tabler:download" class="w-4 h-4" />
                  Excel
                </button>
                <button
                  v-if="report.formats.includes('pdf')"
                  @click.stop="quickExport(report.key, 'pdf')"
                  :disabled="isExporting"
                  class="min-h-[44px] sm:min-h-0 flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-medium text-red-700 bg-red-100 rounded-lg hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 disabled:opacity-50"
                >
                  <Icon icon="tabler:download" class="w-4 h-4" />
                  PDF
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Export Options Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 sm:p-5 sticky top-24">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              ตัวเลือกการ Export
            </h3>

            <!-- Selected Report Info -->
            <div v-if="selectedReport" class="mb-4 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
              <div class="flex items-center gap-2">
                <Icon :icon="reportIcons[selectedReport] || 'tabler:file'" class="w-5 h-5 text-primary-600" />
                <span class="font-medium text-primary-700 dark:text-primary-300">
                  {{ reportTypes.find(r => r.key === selectedReport)?.name }}
                </span>
              </div>
            </div>

            <div v-else class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-center">
              <Icon icon="tabler:click" class="w-8 h-8 text-gray-400 mx-auto mb-2" />
              <p class="text-sm text-gray-500 dark:text-gray-400">
                กรุณาเลือกประเภทรายงาน
              </p>
            </div>

            <!-- Format Selection -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                รูปแบบไฟล์
              </label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  @click="selectedFormat = 'excel'"
                  :disabled="!isFormatAvailable('excel')"
                  :class="[
                    'flex items-center justify-center gap-2 px-4 py-3 rounded-lg border-2 transition-all',
                    selectedFormat === 'excel' && isFormatAvailable('excel')
                      ? 'border-green-500 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400'
                      : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400',
                    !isFormatAvailable('excel') && 'opacity-50 cursor-not-allowed'
                  ]"
                >
                  <Icon icon="tabler:file-spreadsheet" class="w-5 h-5" />
                  <span class="font-medium">Excel</span>
                </button>
                <button
                  @click="selectedFormat = 'pdf'"
                  :disabled="!isFormatAvailable('pdf')"
                  :class="[
                    'flex items-center justify-center gap-2 px-4 py-3 rounded-lg border-2 transition-all',
                    selectedFormat === 'pdf' && isFormatAvailable('pdf')
                      ? 'border-red-500 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400'
                      : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400',
                    !isFormatAvailable('pdf') && 'opacity-50 cursor-not-allowed'
                  ]"
                >
                  <Icon icon="tabler:file-type-pdf" class="w-5 h-5" />
                  <span class="font-medium">PDF</span>
                </button>
              </div>
            </div>

            <!-- Group Filter -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                กลุ่มเรียน
              </label>
              <select
                v-model="selectedGroup"
                class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500"
              >
                <option value="all">ทุกกลุ่ม</option>
                <option v-for="group in groups" :key="group.id" :value="group.id">
                  {{ group.name }}
                </option>
              </select>
            </div>

            <!-- Date Range (for attendance report) -->
            <div v-if="selectedReport === 'attendance'" class="mb-4 space-y-3">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                ช่วงวันที่
              </label>
              <div class="space-y-2">
                <input
                  v-model="dateFrom"
                  type="date"
                  class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500"
                  placeholder="จากวันที่"
                />
                <input
                  v-model="dateTo"
                  type="date"
                  class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500"
                  placeholder="ถึงวันที่"
                />
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-2 pt-4 border-t border-gray-200 dark:border-gray-700">
              <button
                @click="exportReport"
                :disabled="!selectedReport || isExporting"
                class="w-full flex items-center justify-center gap-2 px-4 py-3 text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
              >
                <Icon 
                  :icon="isExporting ? 'tabler:loader' : 'tabler:download'" 
                  :class="['w-5 h-5', isExporting && 'animate-spin']" 
                />
                <span class="font-medium">
                  {{ isExporting ? 'กำลังสร้างรายงาน...' : 'ดาวน์โหลดรายงาน' }}
                </span>
              </button>
              
              <button
                @click="resetFilters"
                class="min-h-[44px] sm:min-h-0 w-full flex items-center justify-center gap-2 px-4 py-2 text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
              >
                <Icon icon="tabler:refresh" class="w-4 h-4" />
                <span class="text-sm">รีเซ็ตตัวกรอง</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Reference Section -->
      <div class="mt-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
          คำอธิบายเพิ่มเติม
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Excel Info -->
          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                <Icon icon="tabler:file-spreadsheet" class="w-5 h-5 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <h3 class="font-medium text-gray-900 dark:text-white">ไฟล์ Excel (.xlsx)</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  เหมาะสำหรับการวิเคราะห์ข้อมูล แก้ไข และนำเข้าระบบอื่น สามารถเปิดด้วย Microsoft Excel, Google Sheets หรือโปรแกรมตารางคำนวณอื่นๆ
                </p>
              </div>
            </div>
          </div>
          
          <!-- PDF Info -->
          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                <Icon icon="tabler:file-type-pdf" class="w-5 h-5 text-red-600 dark:text-red-400" />
              </div>
              <div>
                <h3 class="font-medium text-gray-900 dark:text-white">ไฟล์ PDF</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  เหมาะสำหรับการพิมพ์และเก็บเป็นเอกสาร รูปแบบตายตัวไม่เปลี่ยนแปลง สามารถแชร์และดูได้บนทุกอุปกรณ์
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animation for loading spinner */
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>


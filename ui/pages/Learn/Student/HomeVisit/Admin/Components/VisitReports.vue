<template>
  <div class="space-y-6">
    <!-- Header with Actions -->
    <div class="bg-white shadow-sm rounded-lg p-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">
            <i class="fas fa-chart-line mr-2 text-indigo-600"></i>
            รายงานการเยี่ยมบ้าน
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            แสดงและวิเคราะห์ข้อมูลการเยี่ยมบ้านทั้งหมด
          </p>
        </div>
        <div class="flex gap-2">
          <button
            @click="exportToExcel"
            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"
            :disabled="isExporting"
          >
            <i class="fas fa-file-excel mr-2"></i>
            {{ isExporting ? 'กำลังส่งออก...' : 'ส่งออก Excel' }}
          </button>
          <button
            @click="exportToPDF"
            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors"
            :disabled="isExporting"
          >
            <i class="fas fa-file-pdf mr-2"></i>
            ส่งออก PDF
          </button>
          <button
            @click="refreshData"
            class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors"
          >
            <i class="fas fa-sync-alt mr-2"></i>
            รีเฟรช
          </button>
        </div>
      </div>
    </div>

    <!-- Statistics Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">การเยี่ยมบ้านทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ filteredStats.total }}</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-home text-blue-600 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">เสร็จสิ้นแล้ว</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ filteredStats.completed }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ filteredStats.completedRate }}%</p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-check-circle text-green-600 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">กำลังดำเนินการ</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1">{{ filteredStats.inProgress }}</p>
          </div>
          <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-spinner text-yellow-600 text-xl"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">รอดำเนินการ</p>
            <p class="text-2xl font-bold text-orange-600 mt-1">{{ filteredStats.pending }}</p>
          </div>
          <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
            <i class="fas fa-clock text-orange-600 text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white shadow-sm rounded-lg p-6">
      <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">
          <i class="fas fa-filter mr-2 text-indigo-600"></i>
          ตัวกรองและค้นหา
        </h3>
        <button
          @click="clearFilters"
          class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
        >
          <i class="fas fa-times mr-1"></i>
          ล้างตัวกรอง
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Date Range Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">วันที่เริ่มต้น</label>
          <input
            v-model="filters.startDate"
            type="date"
            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">วันที่สิ้นสุด</label>
          <input
            v-model="filters.endDate"
            type="date"
            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
        </div>

        <!-- Status Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
          <select
            v-model="filters.status"
            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">ทั้งหมด</option>
            <option value="pending">รอดำเนินการ</option>
            <option value="in-progress">กำลังดำเนินการ</option>
            <option value="completed">เสร็จสิ้น</option>
            <option value="cancelled">ยกเลิก</option>
          </select>
        </div>

        <!-- Zone Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">โซน</label>
          <select
            v-model="filters.zoneId"
            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="">ทั้งหมด</option>
            <option v-for="zone in zones" :key="zone.id" :value="zone.id">
              {{ zone.zone_name }}
            </option>
          </select>
        </div>

        <!-- Teacher Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">ครูผู้เยี่ยม</label>
          <input
            v-model="filters.teacherName"
            type="text"
            placeholder="ชื่อครู..."
            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
        </div>

        <!-- Student Search -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">นักเรียน</label>
          <input
            v-model="filters.studentName"
            type="text"
            placeholder="ชื่อนักเรียน..."
            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
        </div>

        <!-- Sort By -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">เรียงตาม</label>
          <select
            v-model="filters.sortBy"
            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="visit_date_desc">วันที่ล่าสุด</option>
            <option value="visit_date_asc">วันที่เก่าสุด</option>
            <option value="student_name">ชื่อนักเรียน</option>
            <option value="status">สถานะ</option>
          </select>
        </div>

        <!-- Results Per Page -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">แสดงผล</label>
          <select
            v-model="filters.perPage"
            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option :value="10">10 รายการ</option>
            <option :value="25">25 รายการ</option>
            <option :value="50">50 รายการ</option>
            <option :value="100">100 รายการ</option>
          </select>
        </div>
      </div>

      <!-- Search Button -->
      <div class="mt-4">
        <button
          @click="applyFilters"
          class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors"
        >
          <i class="fas fa-search mr-2"></i>
          ค้นหา
        </button>
      </div>
    </div>

    <!-- Results Table -->
    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900">
            ผลลัพธ์การค้นหา ({{ filteredVisits.length }} รายการ)
          </h3>
          <div class="text-sm text-gray-500">
            หน้า {{ currentPage }} จาก {{ totalPages }}
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="p-12 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        <p class="mt-4 text-gray-500">กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredVisits.length === 0" class="p-12 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <i class="fas fa-inbox text-gray-400 text-2xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">ไม่พบข้อมูล</h3>
        <p class="text-gray-500">ไม่พบรายการเยี่ยมบ้านตามเงื่อนไขที่ค้นหา</p>
      </div>

      <!-- Data Table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                วันที่เยี่ยม
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                นักเรียน
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                โซน
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                ครูผู้เยี่ยม
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                สถานะ
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                รูปภาพ
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                การดำเนินการ
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="visit in paginatedVisits"
              :key="visit.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  {{ formatDate(visit.visit_date) }}
                </div>
                <div v-if="visit.visit_time" class="text-sm text-gray-500">
                  {{ formatTime(visit.visit_time) }}
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                      <span class="text-indigo-600 font-medium text-sm">
                        {{ getInitials(visit.student) }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">
                      {{ getStudentFullName(visit.student) }}
                    </div>
                    <div v-if="visit.student?.student_code" class="text-sm text-gray-500">
                      รหัส: {{ visit.student.student_code }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="visit.zone" class="text-sm text-gray-900">
                  <i class="fas fa-map-marker-alt mr-1 text-indigo-600"></i>
                  {{ visit.zone.zone_name }}
                </div>
                <div v-else class="text-sm text-gray-400 italic">
                  ไม่ระบุ
                </div>
              </td>
              <td class="px-6 py-4">
                <div v-if="visit.participants && visit.participants.length > 0" class="space-y-1">
                  <div
                    v-for="participant in visit.participants.slice(0, 2)"
                    :key="participant.id"
                    class="text-sm text-gray-900"
                  >
                    <i class="fas fa-user mr-1 text-gray-400"></i>
                    {{ participant.participant_name }}
                  </div>
                  <div v-if="visit.participants.length > 2" class="text-xs text-gray-500">
                    +{{ visit.participants.length - 2 }} คนอื่นๆ
                  </div>
                </div>
                <div v-else-if="visit.visitor_name" class="text-sm text-gray-900">
                  <i class="fas fa-user mr-1 text-gray-400"></i>
                  {{ visit.visitor_name }}
                </div>
                <div v-else class="text-sm text-gray-400 italic">
                  ไม่ระบุ
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(visit.visit_status)">
                  {{ getStatusText(visit.visit_status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <i class="fas fa-image mr-2 text-gray-400"></i>
                  <span class="text-sm text-gray-900">
                    {{ visit.images_count || 0 }} รูป
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click="viewDetails(visit)"
                  class="text-indigo-600 hover:text-indigo-900 mr-3"
                  title="ดูรายละเอียด"
                >
                  <i class="fas fa-eye"></i>
                </button>
                <button
                  @click="downloadReport(visit)"
                  class="text-green-600 hover:text-green-900 mr-3"
                  title="ดาวน์โหลดรายงาน"
                >
                  <i class="fas fa-download"></i>
                </button>
                <button
                  @click="editVisit(visit)"
                  class="text-yellow-600 hover:text-yellow-900"
                  title="แก้ไข"
                >
                  <i class="fas fa-edit"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="filteredVisits.length > 0" class="px-6 py-4 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500">
            แสดง {{ (currentPage - 1) * filters.perPage + 1 }} ถึง 
            {{ Math.min(currentPage * filters.perPage, filteredVisits.length) }} 
            จาก {{ filteredVisits.length }} รายการ
          </div>
          <div class="flex items-center space-x-2">
            <button
              @click="previousPage"
              :disabled="currentPage === 1"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <i class="fas fa-chevron-left"></i>
            </button>
            
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'px-3 py-1 border rounded-md text-sm font-medium',
                page === currentPage
                  ? 'bg-indigo-600 text-white border-indigo-600'
                  : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
            
            <button
              @click="nextPage"
              :disabled="currentPage === totalPages"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <VisitDetailModal
      v-if="showDetailModal"
      :visit="selectedVisit"
      @close="closeDetailModal"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import VisitDetailModal from './VisitDetailModal.vue'

const props = defineProps({
  visits: {
    type: Array,
    default: () => []
  },
  zones: {
    type: Array,
    default: () => []
  }
})

// Reactive data
const isLoading = ref(false)
const isExporting = ref(false)
const currentPage = ref(1)
const showDetailModal = ref(false)
const selectedVisit = ref(null)

const filters = ref({
  startDate: '',
  endDate: '',
  status: '',
  zoneId: '',
  teacherName: '',
  studentName: '',
  sortBy: 'visit_date_desc',
  perPage: 25
})

// Computed properties
const filteredVisits = computed(() => {
  let result = [...props.visits]

  // Filter by date range
  if (filters.value.startDate) {
    result = result.filter(visit => 
      new Date(visit.visit_date) >= new Date(filters.value.startDate)
    )
  }
  if (filters.value.endDate) {
    result = result.filter(visit => 
      new Date(visit.visit_date) <= new Date(filters.value.endDate)
    )
  }

  // Filter by status
  if (filters.value.status) {
    result = result.filter(visit => visit.visit_status === filters.value.status)
  }

  // Filter by zone
  if (filters.value.zoneId) {
    result = result.filter(visit => visit.zone_id == filters.value.zoneId)
  }

  // Filter by teacher name
  if (filters.value.teacherName) {
    const searchTerm = filters.value.teacherName.toLowerCase()
    result = result.filter(visit => {
      if (visit.visitor_name && visit.visitor_name.toLowerCase().includes(searchTerm)) {
        return true
      }
      if (visit.participants) {
        return visit.participants.some(p => 
          p.participant_name.toLowerCase().includes(searchTerm)
        )
      }
      return false
    })
  }

  // Filter by student name
  if (filters.value.studentName) {
    const searchTerm = filters.value.studentName.toLowerCase()
    result = result.filter(visit => {
      const fullName = getStudentFullName(visit.student).toLowerCase()
      return fullName.includes(searchTerm)
    })
  }

  // Sort
  switch (filters.value.sortBy) {
    case 'visit_date_desc':
      result.sort((a, b) => new Date(b.visit_date) - new Date(a.visit_date))
      break
    case 'visit_date_asc':
      result.sort((a, b) => new Date(a.visit_date) - new Date(b.visit_date))
      break
    case 'student_name':
      result.sort((a, b) => 
        getStudentFullName(a.student).localeCompare(getStudentFullName(b.student), 'th')
      )
      break
    case 'status':
      result.sort((a, b) => a.visit_status.localeCompare(b.visit_status))
      break
  }

  return result
})

const filteredStats = computed(() => {
  const visits = filteredVisits.value
  const total = visits.length
  const completed = visits.filter(v => v.visit_status === 'completed').length
  const inProgress = visits.filter(v => v.visit_status === 'in-progress').length
  const pending = visits.filter(v => v.visit_status === 'pending').length
  const completedRate = total > 0 ? Math.round((completed / total) * 100) : 0

  return {
    total,
    completed,
    inProgress,
    pending,
    completedRate
  }
})

const totalPages = computed(() => {
  return Math.ceil(filteredVisits.value.length / filters.value.perPage)
})

const paginatedVisits = computed(() => {
  const start = (currentPage.value - 1) * filters.value.perPage
  const end = start + filters.value.perPage
  return filteredVisits.value.slice(start, end)
})

const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) pages.push(i)
      pages.push('...')
      pages.push(total)
    } else if (current >= total - 3) {
      pages.push(1)
      pages.push('...')
      for (let i = total - 4; i <= total; i++) pages.push(i)
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) pages.push(i)
      pages.push('...')
      pages.push(total)
    }
  }
  
  return pages
})

// Methods
const applyFilters = () => {
  currentPage.value = 1
  // Optionally load data from server
}

const clearFilters = () => {
  filters.value = {
    startDate: '',
    endDate: '',
    status: '',
    zoneId: '',
    teacherName: '',
    studentName: '',
    sortBy: 'visit_date_desc',
    perPage: 25
  }
  currentPage.value = 1
}

const refreshData = () => {
  router.reload({ only: ['visits'] })
}

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const goToPage = (page) => {
  if (page !== '...') {
    currentPage.value = page
  }
}

const viewDetails = (visit) => {
  selectedVisit.value = visit
  showDetailModal.value = true
}

const closeDetailModal = () => {
  showDetailModal.value = false
  selectedVisit.value = null
}

const editVisit = (visit) => {
  router.visit(`/home-visit/admin/visits/${visit.id}/edit`)
}

const downloadReport = async (visit) => {
  try {
    const response = await axios.get(`/api/home-visit/admin/visits/${visit.id}/report`, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `home-visit-report-${visit.id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Download failed:', error)
    alert('เกิดข้อผิดพลาดในการดาวน์โหลด')
  }
}

const exportToExcel = async () => {
  isExporting.value = true
  try {
    const response = await axios.post('/api/home-visit/admin/visits/export/excel', {
      filters: filters.value,
      visits: filteredVisits.value.map(v => v.id)
    }, {
      responseType: 'blob'
    })
    
    // Check if response is valid
    if (!response.data || response.data.size === 0) {
      throw new Error('ไม่มีข้อมูลที่จะส่งออก')
    }
    
    // Create blob with correct MIME type for Excel
    const blob = new Blob([response.data], { 
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
    })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `home-visit-reports-${new Date().toISOString().split('T')[0]}.xlsx`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
    // Show success message
    alert(`ส่งออกข้อมูลเรียบร้อยแล้ว (${filteredVisits.value.length} รายการ)`)
  } catch (error) {
    console.error('Export failed:', error)
    if (error.response) {
      // Server responded with error
      const errorText = await error.response.data.text()
      alert(`เกิดข้อผิดพลาด: ${errorText || error.message}`)
    } else {
      alert('เกิดข้อผิดพลาดในการส่งออก: ' + error.message)
    }
  } finally {
    isExporting.value = false
  }
}

const exportToPDF = async () => {
  isExporting.value = true
  try {
    const response = await axios.post('/api/home-visit/admin/visits/export/pdf', {
      filters: filters.value,
      visits: filteredVisits.value.map(v => v.id)
    }, {
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `home-visit-reports-${new Date().toISOString().split('T')[0]}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Export failed:', error)
    alert('เกิดข้อผิดพลาดในการส่งออก')
  } finally {
    isExporting.value = false
  }
}

// Utility functions
const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatTime = (timeString) => {
  if (!timeString) return '-'
  try {
    return new Date(`2000-01-01 ${timeString}`).toLocaleTimeString('th-TH', {
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return timeString
  }
}

const getStudentFullName = (student) => {
  if (!student) return 'ไม่ระบุ'
  return `${student.first_name || ''} ${student.last_name || ''}`.trim() || 'ไม่ระบุชื่อ'
}

const getInitials = (student) => {
  if (!student || !student.first_name) return 'N'
  return student.first_name.charAt(0).toUpperCase()
}

const getStatusText = (status) => {
  const statusMap = {
    'pending': 'รอดำเนินการ',
    'in-progress': 'กำลังดำเนินการ',
    'completed': 'เสร็จสิ้น',
    'cancelled': 'ยกเลิก'
  }
  return statusMap[status] || status
}

const getStatusBadgeClass = (status) => {
  const baseClass = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full'
  const statusClasses = {
    'pending': 'bg-orange-100 text-orange-800',
    'in-progress': 'bg-yellow-100 text-yellow-800',
    'completed': 'bg-green-100 text-green-800',
    'cancelled': 'bg-gray-100 text-gray-800'
  }
  return `${baseClass} ${statusClasses[status] || 'bg-gray-100 text-gray-800'}`
}

// Watch for changes
watch(() => filters.value.perPage, () => {
  currentPage.value = 1
})

onMounted(() => {
  // Set default date range to current month
  const now = new Date()
  const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
  filters.value.startDate = firstDay.toISOString().split('T')[0]
  filters.value.endDate = now.toISOString().split('T')[0]
})
</script>

<style scoped>
/* Animation for loading */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>


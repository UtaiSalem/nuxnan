<template>
  <div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <!-- Header with Actions -->
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            <i class="fas fa-clipboard-list mr-2 text-indigo-600"></i>
            การเยี่ยมบ้านทั้งหมด
          </h3>
          <p class="mt-1 text-sm text-gray-500">
            ค้นหาและดูรายละเอียดการเยี่ยมบ้าน
          </p>
        </div>
        <div class="flex gap-2">
          <button
            @click="exportToExcel"
            class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"
            :disabled="isExporting"
          >
            <i class="fas fa-file-excel mr-2"></i>
            {{ isExporting ? 'กำลังส่งออก...' : 'Excel' }}
          </button>
          <button
            @click="toggleFilters"
            :class="[
              showFilters ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700',
              'inline-flex items-center px-3 py-2 hover:bg-indigo-700 text-sm font-medium rounded-lg transition-colors'
            ]"
          >
            <i class="fas fa-filter mr-2"></i>
            ตัวกรอง
          </button>
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div v-if="showFilters" class="px-4 py-4 bg-gray-50 border-b border-gray-200">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">วันที่เริ่มต้น</label>
          <input
            v-model="filters.startDate"
            type="date"
            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
          >
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">วันที่สิ้นสุด</label>
          <input
            v-model="filters.endDate"
            type="date"
            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
          >
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
          <select
            v-model="filters.status"
            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
          >
            <option value="">ทั้งหมด</option>
            <option value="pending">รอดำเนินการ</option>
            <option value="in-progress">กำลังดำเนินการ</option>
            <option value="completed">เสร็จสิ้น</option>
            <option value="cancelled">ยกเลิก</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">โซน</label>
          <select
            v-model="filters.zoneId"
            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
          >
            <option value="">ทั้งหมด</option>
            <option v-for="zone in zones" :key="zone.id" :value="zone.id">
              {{ zone.zone_name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">ค้นหานักเรียน</label>
          <input
            v-model="filters.studentName"
            type="text"
            placeholder="ชื่อนักเรียน..."
            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
          >
        </div>
        <div class="flex items-end">
          <button
            @click="clearFilters"
            class="w-full px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors"
          >
            <i class="fas fa-times mr-2"></i>
            ล้างตัวกรอง
          </button>
        </div>
      </div>
    </div>

    <!-- Results Summary -->
    <div class="px-4 py-3 bg-white border-b border-gray-200">
      <div class="flex items-center justify-between text-sm">
        <span class="text-gray-700">
          แสดง {{ paginatedVisits.length }} จาก {{ filteredVisits.length }} รายการ
        </span>
        <div class="flex items-center gap-4">
          <span class="text-gray-500">หน้า {{ currentPage }}/{{ totalPages }}</span>
          <select
            v-model="perPage"
            class="border-gray-300 rounded-md text-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Visits List -->
    <div v-if="filteredVisits.length === 0" class="p-12 text-center">
      <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-inbox text-gray-400 text-2xl"></i>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">ไม่พบข้อมูล</h3>
      <p class="text-gray-500">ไม่พบรายการเยี่ยมบ้านตามเงื่อนไขที่ค้นหา</p>
    </div>

    <ul v-else class="divide-y divide-gray-200">
      <li
        v-for="visit in paginatedVisits"
        :key="visit.id"
        class="px-4 py-4 hover:bg-gray-50 cursor-pointer transition-colors"
        @click="viewVisitDetails(visit)"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center flex-1">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 bg-indigo-500 rounded-full flex items-center justify-center">
                <span class="text-white font-medium text-sm">
                  {{ getStudentInitial(visit.student) }}
                </span>
              </div>
            </div>
            <div class="ml-4 flex-1">
              <div class="flex items-center gap-2">
                <div class="text-sm font-medium text-gray-900">
                  {{ getStudentFullName(visit.student) }}
                </div>
                <span v-if="visit.zone" class="text-xs text-gray-500">
                  <i class="fas fa-map-marker-alt mr-1"></i>{{ visit.zone.zone_name }}
                </span>
              </div>
              <div class="text-sm text-gray-500 mt-1">
                {{ formatDate(visit.visit_date) }}
                <span v-if="visit.visitor_name" class="ml-2">
                  | <i class="fas fa-user text-xs"></i> {{ visit.visitor_name }}
                </span>
                <span v-else-if="visit.participants && visit.participants.length > 0" class="ml-2">
                  | <i class="fas fa-users text-xs"></i> {{ visit.participants.length }} คน
                </span>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <span v-if="visit.images_count" class="text-xs text-gray-500">
              <i class="fas fa-images mr-1"></i>{{ visit.images_count }}
            </span>
            <span
              :class="[
                visit.visit_status === 'completed' ? 'bg-green-100 text-green-800' :
                visit.visit_status === 'in-progress' ? 'bg-yellow-100 text-yellow-800' :
                visit.visit_status === 'pending' ? 'bg-orange-100 text-orange-800' :
                'bg-gray-100 text-gray-800',
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'
              ]"
            >
              {{ getStatusText(visit.visit_status) }}
            </span>
            <i class="fas fa-chevron-right text-gray-400 text-sm"></i>
          </div>
        </div>
      </li>
    </ul>

    <!-- Pagination -->
    <div v-if="filteredVisits.length > 0" class="px-4 py-3 border-t border-gray-200 bg-gray-50">
      <div class="flex items-center justify-center space-x-2">
        <button
          @click="previousPage"
          :disabled="currentPage === 1"
          class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <i class="fas fa-chevron-left"></i>
        </button>
        <span class="text-sm text-gray-700">
          หน้า {{ currentPage }} จาก {{ totalPages }}
        </span>
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
</template>

<script setup>
import { ref, computed } from 'vue'
import { useVisitReports } from '@/composables/useVisitReports'

const props = defineProps({
  visits: {
    type: Array,
    required: true
  },
  zones: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['view-details'])

const visitsRef = ref(props.visits)
const zonesRef = ref(props.zones)

const {
  showFilters,
  isExporting,
  currentPage,
  perPage,
  filters,
  filteredVisits,
  totalPages,
  paginatedVisits,
  toggleFilters,
  clearFilters,
  previousPage,
  nextPage,
  exportToExcel,
  getStudentFullName,
  getStudentInitial
} = useVisitReports(visitsRef, zonesRef)

const getStatusText = (status) => {
  const statusMap = {
    'pending': 'รอดำเนินการ',
    'in-progress': 'กำลังดำเนินการ',
    'completed': 'เสร็จสิ้น',
    'cancelled': 'ยกเลิก'
  }
  return statusMap[status] || status
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const viewVisitDetails = (visit) => {
  emit('view-details', visit)
}
</script>


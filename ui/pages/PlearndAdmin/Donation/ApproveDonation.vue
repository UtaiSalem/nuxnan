<script setup>
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import ApproveDonateCard from '~/components/earn/donates/ApproveDonateCard.vue'

const api = useApi()
const config = useRuntimeConfig()

definePageMeta({
  layout: 'main',
  middleware: ['plearnd-admin'],
})

const donates = ref([])
const isLoading = ref(true)
const error = ref(null)
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const filterStatus = ref('all') // 'all', 'pending', 'approved', 'rejected'
const viewMode = ref('card') // 'card' or 'table'
const selectedSlip = ref(null) // For slip modal

// Filtered donates based on status
const filteredDonates = computed(() => {
  if (filterStatus.value === 'all') return donates.value
  
  const statusMap = {
    pending: 0,
    approved: 1,
    rejected: 2,
  }
  
  return donates.value.filter(d => d.status === statusMap[filterStatus.value])
})

// Stats
const stats = computed(() => {
  const pending = donates.value.filter(d => d.status === 0).length
  const approved = donates.value.filter(d => d.status === 1).length
  const rejected = donates.value.filter(d => d.status === 2).length
  
  return { pending, approved, rejected, total: donates.value.length }
})

const fetchDonates = async (page = 1) => {
  try {
    isLoading.value = true
    error.value = null
    
    const response = await api.get(`/api/plearnd-admin/supports/donates?page=${page}`)
    
    if (response.donates) {
      donates.value = response.donates.data || response.donates
      currentPage.value = response.donates.current_page || 1
      lastPage.value = response.donates.last_page || 1
      total.value = response.donates.total || donates.value.length
    }
  } catch (err) {
    console.error('Failed to fetch donates:', err)
    error.value = 'ไม่สามารถโหลดข้อมูลได้ กรุณาลองใหม่อีกครั้ง'
  } finally {
    isLoading.value = false
  }
}

const handleApproved = (donateId) => {
  const donate = donates.value.find(d => d.id === donateId)
  if (donate) {
    donate.status = 1
  }
}

const handleRejected = (donateId) => {
  const donate = donates.value.find(d => d.id === donateId)
  if (donate) {
    donate.status = 2
  }
}

const nextPage = () => {
  if (currentPage.value < lastPage.value) {
    fetchDonates(currentPage.value + 1)
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    fetchDonates(currentPage.value - 1)
  }
}

// Get full slip URL
const getSlipUrl = (slip) => {
  if (!slip) return null
  if (slip.startsWith('http')) return slip
  return `${config.public.apiBase}${slip}`
}

// Get status info for table
const getStatusInfo = (status) => {
  switch (status) {
    case 0:
      return { text: 'รออนุมัติ', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', icon: 'mdi:clock-outline' }
    case 1:
      return { text: 'อนุมัติแล้ว', class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', icon: 'mdi:check-circle' }
    case 2:
      return { text: 'ปฏิเสธ', class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', icon: 'mdi:close-circle' }
    default:
      return { text: 'ไม่ทราบ', class: 'bg-gray-100 text-gray-800', icon: 'mdi:help-circle' }
  }
}

import Swal from 'sweetalert2'

// Handle approve from table
const handleTableApprove = async (donate) => {
  try {
    const result = await Swal.fire({
      title: 'ยืนยันการอนุมัติ?',
      text: `คุณต้องการอนุมัติการสนับสนุนนี้ใช่หรือไม่ (#${donate.id})`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#10B981',
      cancelButtonColor: '#6B7280',
      confirmButtonText: 'ยืนยัน, อนุมัติ!',
      cancelButtonText: 'ยกเลิก'
    })

    if (result.isConfirmed) {
      const response = await api.patch(`/api/plearnd-admin/supports/donates/${donate.id}/recieve`, {})
      if (response.success) {
        handleApproved(donate.id)
        Swal.fire('สำเร็จ!', 'อนุมัติการสนับสนุนเรียบร้อยแล้ว', 'success')
      }
    }
  } catch (err) {
    console.error('Failed to approve:', err)
    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถอนุมัติได้ กรุณาลองใหม่', 'error')
  }
}

// Handle reject from table
const handleTableReject = async (donate) => {
  try {
    const result = await Swal.fire({
      title: 'ยืนยันการปฏิเสธ?',
      text: "คุณแน่ใจหรือไม่ที่จะปฏิเสธการสนับสนุนนี้? การกระทำนี้ไม่สามารถย้อนกลับได้",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#EF4444',
      cancelButtonColor: '#6B7280',
      confirmButtonText: 'ยืนยัน, ปฏิเสธ!',
      cancelButtonText: 'ยกเลิก'
    })

    if (result.isConfirmed) {
      const response = await api.patch(`/api/plearnd-admin/supports/donates/${donate.id}/reject`, {})
      if (response.success) {
        handleRejected(donate.id)
        Swal.fire('เรียบร้อย', 'ปฏิเสธการสนับสนุนแล้ว', 'success')
      }
    }
  } catch (err) {
    console.error('Failed to reject:', err)
    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถปฏิเสธได้ กรุณาลองใหม่', 'error')
  }
}

onMounted(() => {
  fetchDonates()
})
</script>

<template>
  <div class="py-6">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center gap-3 mb-2">
        <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
          <Icon icon="mdi:hand-coin" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">อนุมัติการสนับสนุนแต้ม</h1>
      </div>
      <p class="text-gray-600 dark:text-gray-400">ตรวจสอบและอนุมัติการสนับสนุนจากผู้บริจาค</p>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-md border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
            <Icon icon="mdi:format-list-bulleted" class="w-5 h-5 text-gray-600 dark:text-gray-400" />
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">ทั้งหมด</p>
          </div>
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-md border border-yellow-200 dark:border-yellow-800">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
            <Icon icon="mdi:clock-outline" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
          </div>
          <div>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.pending }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">รออนุมัติ</p>
          </div>
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-md border border-green-200 dark:border-green-800">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
            <Icon icon="mdi:check-circle" class="w-5 h-5 text-green-600 dark:text-green-400" />
          </div>
          <div>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.approved }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">อนุมัติแล้ว</p>
          </div>
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-md border border-red-200 dark:border-red-800">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
            <Icon icon="mdi:close-circle" class="w-5 h-5 text-red-600 dark:text-red-400" />
          </div>
          <div>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.rejected }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">ปฏิเสธ</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Filter Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-2 mb-6">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex flex-wrap gap-2">
          <button
            @click="filterStatus = 'all'"
            :class="[
              'px-4 py-2 rounded-lg font-medium text-sm transition-colors',
              filterStatus === 'all' 
                ? 'bg-purple-500 text-white' 
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
            ]"
          >
            ทั้งหมด
          </button>
          <button
            @click="filterStatus = 'pending'"
            :class="[
              'px-4 py-2 rounded-lg font-medium text-sm transition-colors flex items-center gap-2',
              filterStatus === 'pending' 
                ? 'bg-yellow-500 text-white' 
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
            ]"
          >
            <Icon icon="mdi:clock-outline" class="w-4 h-4" />
            รออนุมัติ
            <span v-if="stats.pending > 0" class="bg-yellow-600 text-white text-xs px-2 py-0.5 rounded-full">
              {{ stats.pending }}
            </span>
          </button>
          <button
            @click="filterStatus = 'approved'"
            :class="[
              'px-4 py-2 rounded-lg font-medium text-sm transition-colors flex items-center gap-2',
              filterStatus === 'approved' 
                ? 'bg-green-500 text-white' 
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
            ]"
          >
            <Icon icon="mdi:check-circle" class="w-4 h-4" />
            อนุมัติแล้ว
          </button>
          <button
            @click="filterStatus = 'rejected'"
            :class="[
              'px-4 py-2 rounded-lg font-medium text-sm transition-colors flex items-center gap-2',
              filterStatus === 'rejected' 
                ? 'bg-red-500 text-white' 
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
            ]"
          >
            <Icon icon="mdi:close-circle" class="w-4 h-4" />
            ปฏิเสธ
          </button>
        </div>
        
        <!-- View Mode Toggle -->
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
          <button
            @click="viewMode = 'card'"
            :class="[
              'p-2 rounded-md transition-colors',
              viewMode === 'card'
                ? 'bg-white dark:bg-gray-600 shadow text-purple-600 dark:text-purple-400'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
            ]"
            title="มุมมองแบบการ์ด"
          >
            <Icon icon="mdi:view-grid" class="w-5 h-5" />
          </button>
          <button
            @click="viewMode = 'table'"
            :class="[
              'p-2 rounded-md transition-colors',
              viewMode === 'table'
                ? 'bg-white dark:bg-gray-600 shadow text-purple-600 dark:text-purple-400'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
            ]"
            title="มุมมองแบบตาราง"
          >
            <Icon icon="mdi:table" class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
    
    <!-- Loading State -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-20">
      <Icon icon="mdi:loading" class="w-12 h-12 text-purple-500 animate-spin" />
      <p class="mt-4 text-gray-600 dark:text-gray-400">กำลังโหลดข้อมูล...</p>
    </div>
    
    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 dark:bg-red-900/20 rounded-xl p-8 text-center">
      <Icon icon="mdi:alert-circle" class="w-12 h-12 text-red-500 mx-auto mb-4" />
      <p class="text-red-600 dark:text-red-400 mb-4">{{ error }}</p>
      <button
        @click="fetchDonates"
        class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors"
      >
        ลองใหม่อีกครั้ง
      </button>
    </div>
    
    <!-- Empty State -->
    <div v-else-if="filteredDonates.length === 0" class="bg-gray-50 dark:bg-gray-800 rounded-xl p-12 text-center">
      <Icon icon="mdi:inbox-outline" class="w-16 h-16 text-gray-400 mx-auto mb-4" />
      <p class="text-lg font-medium text-gray-600 dark:text-gray-400">
        {{ filterStatus === 'pending' ? 'ไม่มีรายการที่รออนุมัติ' : 'ไม่มีข้อมูลการสนับสนุน' }}
      </p>
    </div>
    
    <!-- Donates Card Grid -->
    <div v-else-if="viewMode === 'card'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <ApproveDonateCard
        v-for="donate in filteredDonates"
        :key="donate.id"
        :donate="donate"
        @approved="handleApproved"
        @rejected="handleRejected"
      />
    </div>
    
    <!-- Donates Table View -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ID</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ผู้บริจาค</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">จำนวนเงิน</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">แต้ม</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">วันที่/เวลาโอน</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สลิป</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
              <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ดำเนินการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr 
              v-for="donate in filteredDonates" 
              :key="donate.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
              <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-medium">#{{ donate.id }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 overflow-hidden flex items-center justify-center">
                    <img v-if="donate.donor?.avatar" :src="donate.donor.avatar" class="w-full h-full object-cover" />
                    <Icon v-else icon="mdi:account" class="w-4 h-4 text-gray-400" />
                  </div>
                  <span class="text-sm text-gray-900 dark:text-white">{{ donate.donor_name || 'ไม่ประสงค์ออกนาม' }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-sm text-blue-600 dark:text-blue-400 font-medium">{{ donate.amounts }}</td>
              <td class="px-4 py-3 text-sm text-purple-600 dark:text-purple-400 font-medium">{{ donate.total_points?.toLocaleString() || 0 }}</td>
              <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                <div>{{ donate.transfer_date || '-' }}</div>
                <div v-if="donate.transfer_time" class="text-xs text-gray-500">{{ donate.transfer_time }}</div>
              </td>
              <td class="px-4 py-3">
                <button 
                  v-if="getSlipUrl(donate.slip)"
                  @click="selectedSlip = getSlipUrl(donate.slip)"
                  class="w-12 h-12 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600 hover:border-purple-500 transition-colors"
                >
                  <img :src="getSlipUrl(donate.slip)" class="w-full h-full object-cover" />
                </button>
                <span v-else class="text-gray-400 text-sm">-</span>
              </td>
              <td class="px-4 py-3">
                <span :class="[getStatusInfo(donate.status).class, 'px-2 py-1 rounded-full text-xs font-medium inline-flex items-center gap-1']">
                  <Icon :icon="getStatusInfo(donate.status).icon" class="w-3 h-3" />
                  {{ getStatusInfo(donate.status).text }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div v-if="donate.status === 0" class="flex items-center justify-center gap-2">
                  <button
                    @click="handleTableApprove(donate)"
                    class="p-2 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors"
                    title="อนุมัติ"
                  >
                    <Icon icon="mdi:check" class="w-5 h-5" />
                  </button>
                  <button
                    @click="handleTableReject(donate)"
                    class="p-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                    title="ปฏิเสธ"
                  >
                    <Icon icon="mdi:close" class="w-5 h-5" />
                  </button>
                </div>
                <span v-else class="text-gray-400 text-sm">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Slip Modal -->
    <Teleport to="body">
      <div 
        v-if="selectedSlip" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
        @click="selectedSlip = null"
      >
        <div class="relative max-w-4xl max-h-[90vh]">
          <button 
            @click="selectedSlip = null"
            class="absolute -top-10 right-0 text-white hover:text-gray-300 transition-colors"
          >
            <Icon icon="mdi:close" class="w-8 h-8" />
          </button>
          <img 
            :src="selectedSlip" 
            alt="สลิปโอนเงิน" 
            class="max-w-full max-h-[85vh] object-contain rounded-lg"
            @click.stop
          />
        </div>
      </div>
    </Teleport>
    
    <!-- Pagination -->
    <div v-if="lastPage > 1" class="mt-8 flex items-center justify-center gap-4">
      <button
        @click="prevPage"
        :disabled="currentPage <= 1"
        class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
      >
        <Icon icon="mdi:chevron-left" class="w-5 h-5" />
        ก่อนหน้า
      </button>
      
      <span class="text-gray-600 dark:text-gray-400">
        หน้า {{ currentPage }} จาก {{ lastPage }}
      </span>
      
      <button
        @click="nextPage"
        :disabled="currentPage >= lastPage"
        class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
      >
        ถัดไป
        <Icon icon="mdi:chevron-right" class="w-5 h-5" />
      </button>
    </div>
  </div>
</template>

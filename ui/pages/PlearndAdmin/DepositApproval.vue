<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import BaseCard from '~/components/atoms/BaseCard.vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

useHead({
  title: 'อนุมัติคำขอเติมเงิน - Admin'
})

const authStore = useAuthStore()
const config = useRuntimeConfig()

// State
const depositRequests = ref<any[]>([])
const isLoading = ref(true)
const isProcessing = ref(false)
const selectedRequest = ref<any>(null)
const showModal = ref(false)
const modalAction = ref<'approve' | 'reject'>('approve')

// Filters
const statusFilter = ref('pending')
const searchQuery = ref('')
const dateFrom = ref('')
const dateTo = ref('')

// Stats
const stats = ref({
  pending: 0,
  approved_today: 0,
  total_pending_amount: 0
})

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)

// Form
const rejectForm = ref({
  rejection_reason: '',
  admin_note: ''
})
const approveForm = ref({
  admin_note: ''
})

// Computed
const isAdmin = computed(() => authStore.user?.is_plearnd_admin)

// API Base
const apiBase = computed(() => config.public.apiBase || '')

// Methods
const loadRequests = async () => {
  try {
    isLoading.value = true
    
    const params = new URLSearchParams()
    if (statusFilter.value) params.append('status', statusFilter.value)
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (dateFrom.value) params.append('from_date', dateFrom.value)
    if (dateTo.value) params.append('to_date', dateTo.value)
    params.append('page', currentPage.value.toString())
    params.append('per_page', '20')
    
    const response = await $fetch(`${apiBase.value}/api/admin/deposit-requests?${params}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
      },
    }) as any
    
    if (response.success) {
      depositRequests.value = response.data
      stats.value = response.stats
      currentPage.value = response.pagination.current_page
      lastPage.value = response.pagination.last_page
      total.value = response.pagination.total
    }
  } catch (err) {
    console.error('Failed to load deposit requests:', err)
  } finally {
    isLoading.value = false
  }
}

const openApproveModal = (request: any) => {
  selectedRequest.value = request
  modalAction.value = 'approve'
  approveForm.value.admin_note = ''
  showModal.value = true
}

const openRejectModal = (request: any) => {
  selectedRequest.value = request
  modalAction.value = 'reject'
  rejectForm.value.rejection_reason = ''
  rejectForm.value.admin_note = ''
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedRequest.value = null
}

const handleApprove = async () => {
  if (!selectedRequest.value) return
  
  try {
    isProcessing.value = true
    
    const response = await $fetch(`${apiBase.value}/api/admin/deposit-requests/${selectedRequest.value.id}/approve`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
      },
      body: {
        admin_note: approveForm.value.admin_note
      }
    }) as any
    
    if (response.success) {
      alert(`อนุมัติสำเร็จ! เติมเงิน ${formatMoney(selectedRequest.value.amount)} ให้ ${selectedRequest.value.user.name}`)
      closeModal()
      await loadRequests()
    } else {
      alert(response.message || 'เกิดข้อผิดพลาด')
    }
  } catch (err: any) {
    alert(err.data?.message || err.message || 'เกิดข้อผิดพลาด')
  } finally {
    isProcessing.value = false
  }
}

const handleReject = async () => {
  if (!selectedRequest.value) return
  
  if (!rejectForm.value.rejection_reason.trim()) {
    alert('กรุณาระบุเหตุผลที่ปฏิเสธ')
    return
  }
  
  try {
    isProcessing.value = true
    
    const response = await $fetch(`${apiBase.value}/api/admin/deposit-requests/${selectedRequest.value.id}/reject`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
      },
      body: {
        rejection_reason: rejectForm.value.rejection_reason,
        admin_note: rejectForm.value.admin_note
      }
    }) as any
    
    if (response.success) {
      alert('ปฏิเสธคำขอสำเร็จ')
      closeModal()
      await loadRequests()
    } else {
      alert(response.message || 'เกิดข้อผิดพลาด')
    }
  } catch (err: any) {
    alert(err.data?.message || err.message || 'เกิดข้อผิดพลาด')
  } finally {
    isProcessing.value = false
  }
}

const formatMoney = (value: number) => {
  return new Intl.NumberFormat('th-TH', {
    style: 'currency',
    currency: 'THB',
  }).format(value)
}

const getStatusBadgeClass = (status: string) => {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
    case 'approved':
      return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
    case 'rejected':
      return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const viewSlip = (url: string) => {
  window.open(url, '_blank')
}

// Initialize
onMounted(async () => {
  if (!isAdmin.value) {
    navigateTo('/')
    return
  }
  await loadRequests()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <!-- Page Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">อนุมัติคำขอเติมเงิน</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">ตรวจสอบและอนุมัติคำขอเติมเงินจากผู้ใช้</p>
      </div>
      
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <BaseCard>
          <div class="p-4 flex items-center gap-4">
            <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center">
              <Icon icon="mdi:clock-outline" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">รอดำเนินการ</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.pending }}</p>
            </div>
          </div>
        </BaseCard>
        
        <BaseCard>
          <div class="p-4 flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
              <Icon icon="mdi:check-circle" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">อนุมัติวันนี้</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.approved_today }}</p>
            </div>
          </div>
        </BaseCard>
        
        <BaseCard>
          <div class="p-4 flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
              <Icon icon="mdi:cash-multiple" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">ยอดรอดำเนินการ</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatMoney(stats.total_pending_amount) }}</p>
            </div>
          </div>
        </BaseCard>
      </div>
      
      <!-- Filters -->
      <BaseCard class="mb-6">
        <div class="p-4">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะ</label>
              <select 
                v-model="statusFilter"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg"
                @change="loadRequests"
              >
                <option value="">ทั้งหมด</option>
                <option value="pending">รอดำเนินการ</option>
                <option value="approved">อนุมัติแล้ว</option>
                <option value="rejected">ปฏิเสธแล้ว</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
              <input 
                v-model="searchQuery"
                type="text"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg"
                placeholder="ชื่อ, อีเมล, รหัสอ้างอิง"
                @keyup.enter="loadRequests"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จากวันที่</label>
              <input 
                v-model="dateFrom"
                type="date"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg"
                @change="loadRequests"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ถึงวันที่</label>
              <input 
                v-model="dateTo"
                type="date"
                class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg"
                @change="loadRequests"
              >
            </div>
          </div>
        </div>
      </BaseCard>
      
      <!-- Requests Table -->
      <BaseCard>
        <div class="overflow-x-auto">
          <div v-if="isLoading" class="py-12 text-center">
            <Icon icon="mdi:loading" class="w-8 h-8 text-primary-500 animate-spin mx-auto" />
            <p class="text-gray-500 mt-2">กำลังโหลด...</p>
          </div>
          
          <table v-else-if="depositRequests.length > 0" class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">ผู้ใช้</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">จำนวนเงิน</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">ข้อมูลการโอน</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">สลิป</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">วันที่</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300">สถานะ</th>
                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700 dark:text-gray-300">จัดการ</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="req in depositRequests" :key="req.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                <td class="px-4 py-4">
                  <div class="flex items-center gap-3">
                    <img 
                      :src="req.user.avatar || '/default-avatar.png'" 
                      :alt="req.user.name"
                      class="w-10 h-10 rounded-full object-cover"
                    >
                    <div>
                      <p class="font-medium text-gray-900 dark:text-white">{{ req.user.name }}</p>
                      <p class="text-xs text-gray-500">{{ req.user.email }}</p>
                      <p class="text-xs text-primary-500">{{ req.user.reference_code }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-4">
                  <p class="font-semibold text-lg text-gray-900 dark:text-white">{{ formatMoney(req.amount) }}</p>
                  <p class="text-xs text-gray-500">{{ req.payment_method_label }}</p>
                </td>
                <td class="px-4 py-4">
                  <div class="text-sm">
                    <p v-if="req.bank_name"><span class="text-gray-500">ธนาคาร:</span> {{ req.bank_name }}</p>
                    <p v-if="req.transfer_date"><span class="text-gray-500">วันที่โอน:</span> {{ req.transfer_date }}</p>
                    <p v-if="req.reference_number"><span class="text-gray-500">อ้างอิง:</span> {{ req.reference_number }}</p>
                  </div>
                </td>
                <td class="px-4 py-4">
                  <img 
                    v-if="req.transfer_slip"
                    :src="req.transfer_slip" 
                    alt="Transfer slip"
                    class="w-16 h-16 object-cover rounded-lg cursor-pointer hover:opacity-80 border"
                    @click="viewSlip(req.transfer_slip)"
                  >
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="px-4 py-4">
                  <p class="text-sm text-gray-900 dark:text-white">{{ req.created_at }}</p>
                </td>
                <td class="px-4 py-4">
                  <span 
                    class="px-3 py-1 text-xs font-medium rounded-full"
                    :class="getStatusBadgeClass(req.status)"
                  >
                    {{ req.status_label }}
                  </span>
                </td>
                <td class="px-4 py-4 text-right">
                  <div v-if="req.status === 'pending'" class="flex justify-end gap-2">
                    <button 
                      class="px-3 py-1.5 bg-green-500 text-white text-sm rounded-lg hover:bg-green-600 transition-colors"
                      @click="openApproveModal(req)"
                    >
                      <Icon icon="mdi:check" class="w-4 h-4 inline mr-1" />
                      อนุมัติ
                    </button>
                    <button 
                      class="px-3 py-1.5 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 transition-colors"
                      @click="openRejectModal(req)"
                    >
                      <Icon icon="mdi:close" class="w-4 h-4 inline mr-1" />
                      ปฏิเสธ
                    </button>
                  </div>
                  <div v-else class="text-sm text-gray-500">
                    <p v-if="req.reviewed_at">{{ req.reviewed_at }}</p>
                    <p v-if="req.rejection_reason" class="text-red-500 text-xs mt-1">{{ req.rejection_reason }}</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          
          <div v-else class="py-12 text-center text-gray-500 dark:text-gray-400">
            <Icon icon="mdi:inbox-outline" class="w-16 h-16 mx-auto mb-4" />
            <p>ไม่พบคำขอเติมเงิน</p>
          </div>
        </div>
        
        <!-- Pagination -->
        <div v-if="lastPage > 1" class="p-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            แสดง {{ depositRequests.length }} จาก {{ total }} รายการ
          </p>
          <div class="flex gap-2">
            <button 
              :disabled="currentPage <= 1"
              class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg disabled:opacity-50"
              @click="currentPage--; loadRequests()"
            >
              <Icon icon="mdi:chevron-left" class="w-5 h-5" />
            </button>
            <span class="px-3 py-1 bg-primary-500 text-white rounded-lg">{{ currentPage }}</span>
            <button 
              :disabled="currentPage >= lastPage"
              class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg disabled:opacity-50"
              @click="currentPage++; loadRequests()"
            >
              <Icon icon="mdi:chevron-right" class="w-5 h-5" />
            </button>
          </div>
        </div>
      </BaseCard>
    </div>
    
    <!-- Modal -->
    <Teleport to="body">
      <div 
        v-if="showModal" 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="closeModal"
      >
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
          <div class="p-6">
            <!-- Approve Modal -->
            <template v-if="modalAction === 'approve'">
              <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                  <Icon icon="mdi:check-circle" class="w-8 h-8 text-green-600" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">อนุมัติการเติมเงิน</h3>
                <p class="text-gray-600 dark:text-gray-400 mt-2">ยืนยันอนุมัติการเติมเงินนี้?</p>
              </div>
              
              <div v-if="selectedRequest" class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 mb-6">
                <div class="flex items-center gap-3 mb-3">
                  <img 
                    :src="selectedRequest.user.avatar || '/default-avatar.png'" 
                    class="w-12 h-12 rounded-full"
                  >
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ selectedRequest.user.name }}</p>
                    <p class="text-sm text-gray-500">{{ selectedRequest.user.email }}</p>
                  </div>
                </div>
                <p class="text-2xl font-bold text-green-600 text-center">{{ formatMoney(selectedRequest.amount) }}</p>
              </div>
              
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ Admin (ถ้ามี)</label>
                <textarea 
                  v-model="approveForm.admin_note"
                  rows="2"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                  placeholder="หมายเหตุเพิ่มเติม..."
                ></textarea>
              </div>
              
              <div class="flex gap-3">
                <button 
                  class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors"
                  @click="closeModal"
                >
                  ยกเลิก
                </button>
                <button 
                  class="flex-1 px-4 py-3 bg-green-500 text-white font-medium rounded-xl hover:bg-green-600 transition-colors disabled:opacity-50"
                  :disabled="isProcessing"
                  @click="handleApprove"
                >
                  <Icon v-if="isProcessing" icon="mdi:loading" class="w-5 h-5 animate-spin inline mr-2" />
                  ยืนยันอนุมัติ
                </button>
              </div>
            </template>
            
            <!-- Reject Modal -->
            <template v-else-if="modalAction === 'reject'">
              <div class="text-center mb-6">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                  <Icon icon="mdi:close-circle" class="w-8 h-8 text-red-600" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">ปฏิเสธการเติมเงิน</h3>
                <p class="text-gray-600 dark:text-gray-400 mt-2">ยืนยันปฏิเสธคำขอเติมเงินนี้?</p>
              </div>
              
              <div v-if="selectedRequest" class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 mb-6">
                <div class="flex items-center gap-3 mb-3">
                  <img 
                    :src="selectedRequest.user.avatar || '/default-avatar.png'" 
                    class="w-12 h-12 rounded-full"
                  >
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ selectedRequest.user.name }}</p>
                    <p class="text-sm text-gray-500">{{ selectedRequest.user.email }}</p>
                  </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white text-center">{{ formatMoney(selectedRequest.amount) }}</p>
              </div>
              
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  เหตุผลที่ปฏิเสธ <span class="text-red-500">*</span>
                </label>
                <textarea 
                  v-model="rejectForm.rejection_reason"
                  rows="3"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                  placeholder="กรุณาระบุเหตุผล..."
                ></textarea>
              </div>
              
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมายเหตุ Admin (ถ้ามี)</label>
                <textarea 
                  v-model="rejectForm.admin_note"
                  rows="2"
                  class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl"
                  placeholder="หมายเหตุเพิ่มเติม..."
                ></textarea>
              </div>
              
              <div class="flex gap-3">
                <button 
                  class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors"
                  @click="closeModal"
                >
                  ยกเลิก
                </button>
                <button 
                  class="flex-1 px-4 py-3 bg-red-500 text-white font-medium rounded-xl hover:bg-red-600 transition-colors disabled:opacity-50"
                  :disabled="isProcessing || !rejectForm.rejection_reason.trim()"
                  @click="handleReject"
                >
                  <Icon v-if="isProcessing" icon="mdi:loading" class="w-5 h-5 animate-spin inline mr-2" />
                  ยืนยันปฏิเสธ
                </button>
              </div>
            </template>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

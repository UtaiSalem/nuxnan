<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useStudentMasterStore } from '~/stores/studentMaster'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'admin',
  middleware: ['auth']
})

const studentStore = useStudentMasterStore()
const authStore = useAuthStore()

// State
const selectedStatus = ref('pending')
const currentPage = ref(1)
const perPage = ref(20)

// Modals
const showRejectModal = ref(false)
const selectedRequestId = ref<number | string | null>(null)
const rejectReason = ref('')

const fetchRequests = async () => {
  await studentStore.fetchChangeRequests({
    page: currentPage.value,
    per_page: perPage.value,
    status: selectedStatus.value,
    academy_id: authStore.user?.academy_id || ''
  })
}

watch(selectedStatus, () => {
  currentPage.value = 1
  fetchRequests()
})

const changePage = (page: number) => {
  currentPage.value = page
  fetchRequests()
}

onMounted(() => {
  fetchRequests()
})

const handleApprove = async (id: number | string) => {
  if (confirm('คุณแน่ใจหรือไม่ว่าต้องการอนุมัติการแก้ไขนี้?')) {
    try {
      await studentStore.approveRequest(id)
      alert('อนุมัติคำขอสำเร็จ')
      fetchRequests()
    } catch (e: any) {
      alert(e.message || 'เกิดข้อผิดพลาดในการอนุมัติ')
    }
  }
}

const openRejectModal = (id: number | string) => {
  selectedRequestId.value = id
  rejectReason.value = ''
  showRejectModal.value = true
}

const handleReject = async () => {
  if (!rejectReason.value) {
    alert('กรุณาระบุเหตุผลการปฏิเสธ')
    return
  }
  
  try {
    await studentStore.rejectRequest(selectedRequestId.value as string, rejectReason.value)
    alert('ปฏิเสธคำขอสำเร็จ')
    showRejectModal.value = false
    fetchRequests()
  } catch (e: any) {
    alert(e.message || 'เกิดข้อผิดพลาดในการปฏิเสธ')
  }
}

const formatDate = (dateString: string) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const translateField = (field: string) => {
  const map: Record<string, string> = {
    'title_prefix_th': 'คำนำหน้า (ไทย)',
    'first_name_th': 'ชื่อ (ไทย)',
    'last_name_th': 'นามสกุล (ไทย)',
    'citizen_id': 'รหัสประจำตัวประชาชน',
    'address.house_number': 'บ้านเลขที่',
    // ... add more translations as needed
  }
  return map[field] || field
}
</script>

<template>
  <div class="admin-student-requests p-6 max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
      <div class="flex items-center">
        <NuxtLink to="/admin/students" class="text-gray-500 hover:text-gray-700 mr-4">
          <i class="fas fa-arrow-left text-xl"></i>
        </NuxtLink>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">คำขอแก้ไขข้อมูลนักเรียน</h1>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow mb-6">
      <div class="flex border-b border-gray-200 dark:border-gray-700">
        <button 
          @click="selectedStatus = 'pending'"
          class="py-2 px-4 border-b-2 font-medium text-sm transition"
          :class="selectedStatus === 'pending' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          รอการอนุมัติ
        </button>
        <button 
          @click="selectedStatus = 'approved'"
          class="py-2 px-4 border-b-2 font-medium text-sm transition"
          :class="selectedStatus === 'approved' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          อนุมัติแล้ว
        </button>
        <button 
          @click="selectedStatus = 'rejected'"
          class="py-2 px-4 border-b-2 font-medium text-sm transition"
          :class="selectedStatus === 'rejected' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          ถูกปฏิเสธ
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
      <div v-if="studentStore.isLoading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary-600 mx-auto"></div>
        <p class="mt-2 text-gray-500">กำลังโหลดข้อมูล...</p>
      </div>

      <div v-else-if="studentStore.error" class="p-8 text-center text-red-500">
        <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
        <p>{{ studentStore.error }}</p>
      </div>

      <div v-else-if="studentStore.changeRequests.length === 0" class="p-12 text-center text-gray-500">
        <i class="fas fa-check-circle text-4xl mb-4 text-gray-300"></i>
        <p class="text-lg">ไม่มีคำขอที่รอการดำเนินการ</p>
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">นักเรียน / เวลาที่ขอ</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ข้อมูลที่ขอแก้ไข</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ค่าเดิม <i class="fas fa-arrow-right mx-2"></i> ค่าใหม่</th>
            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">จัดการ</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
          <tr v-for="request in studentStore.changeRequests" :key="request.id" class="hover:bg-gray-50 dark:hover:bg-gray-750">
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900 dark:text-white">
                {{ request.student?.first_name_th }} {{ request.student?.last_name_th }}
              </div>
              <div class="text-xs text-gray-500 mt-1">รหัส: {{ request.student?.student_id || '-' }}</div>
              <div class="text-xs text-gray-400 mt-1"><i class="far fa-clock mr-1"></i>{{ formatDate(request.created_at) }}</div>
            </td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-blue-100 text-blue-800">
                {{ request.model_type }}
              </span>
              <div class="text-sm font-medium mt-2">{{ translateField(request.field) }}</div>
            </td>
            <td class="px-6 py-4 text-sm">
              <div class="flex flex-col space-y-2">
                <div class="text-red-600 line-through decoration-red-400">{{ request.old_value || '(ว่าง)' }}</div>
                <div class="text-green-600 font-medium">{{ request.new_value || '(ว่าง)' }}</div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <div v-if="request.status === 'pending'" class="flex justify-end space-x-2">
                <button @click="handleApprove(request.id)" class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 p-2 rounded-md transition" title="อนุมัติ">
                  <i class="fas fa-check"></i>
                </button>
                <button @click="openRejectModal(request.id)" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 p-2 rounded-md transition" title="ไม่อนุมัติ">
                  <i class="fas fa-times"></i>
                </button>
              </div>
              <div v-else-if="request.status === 'approved'" class="text-green-600 font-medium text-xs">
                <i class="fas fa-check-circle mr-1"></i> อนุมัติแล้ว
              </div>
              <div v-else-if="request.status === 'rejected'" class="text-red-600 font-medium text-xs flex flex-col items-end">
                <span><i class="fas fa-times-circle mr-1"></i> ถูกปฏิเสธ</span>
                <span class="text-gray-500 mt-1" :title="request.reason">เหตุผล: {{ request.reason?.substring(0, 20) }}{{ request.reason?.length > 20 ? '...' : '' }}</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="studentStore.requestsPagination && studentStore.requestsPagination.last_page > 1" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6 flex items-center justify-between">
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700 dark:text-gray-300">
              แสดง <span class="font-medium">{{ ((currentPage - 1) * perPage) + 1 }}</span> ถึง 
              <span class="font-medium">{{ Math.min(currentPage * perPage, studentStore.requestsPagination.total) }}</span> 
              จากทั้งหมด <span class="font-medium">{{ studentStore.requestsPagination.total }}</span> รายการ
            </p>
          </div>
          <div>
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
              <button 
                @click="changePage(currentPage - 1)" 
                :disabled="currentPage === 1"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                <i class="fas fa-chevron-left"></i>
              </button>
              
              <button 
                v-for="page in studentStore.requestsPagination.last_page" 
                :key="page"
                @click="changePage(page)"
                :class="page === currentPage ? 'z-10 bg-primary-50 border-primary-500 text-primary-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                {{ page }}
              </button>
              
              <button 
                @click="changePage(currentPage + 1)" 
                :disabled="currentPage === studentStore.requestsPagination.last_page"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                <i class="fas fa-chevron-right"></i>
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="showRejectModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showRejectModal = false" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
          <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
              </div>
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">ปฏิเสธคำขอแก้ไขข้อมูล</h3>
                <div class="mt-4">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผลการปฏิเสธ (เพื่อแจ้งให้นักเรียนทราบ)</label>
                  <textarea 
                    v-model="rejectReason" 
                    rows="3" 
                    class="shadow-sm focus:ring-red-500 focus:border-red-500 mt-1 block w-full sm:text-sm border border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700"
                    placeholder="เช่น ข้อมูลไม่ตรงกับหลักฐาน, ไม่ชัดเจน..."
                  ></textarea>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button @click="handleReject" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm" :disabled="studentStore.isLoading">
              <span v-if="studentStore.isLoading" class="animate-spin mr-2 h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
              ยืนยันการปฏิเสธ
            </button>
            <button @click="showRejectModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
              ยกเลิก
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

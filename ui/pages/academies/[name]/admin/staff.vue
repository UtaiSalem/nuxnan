<script setup lang="ts">
/**
 * Academy Admin - Staff Management
 * หน้าจัดการบุคลากร
 */
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const staff = ref<any[]>([])
const positions = ref<any[]>([])
const summary = ref<any>(null)
const isLoading = ref(true)
const isLoadingStaff = ref(false)

// Filters
const searchQuery = ref('')
const filterStatus = ref<string>('')
const filterPosition = ref<number | null>(null)

// Pagination
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0
})

// Modals
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showPositionModal = ref(false)
const selectedStaff = ref<any>(null)

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Form
const staffForm = ref({
  user_id: null as number | null,
  position_id: null as number | null,
  employee_type: 'full_time',
  status: 'active',
  hire_date: '',
  department: ''
})
const isSubmitting = ref(false)
const formErrors = ref<Record<string, string[]>>({})

// Available members (for adding staff)
const availableMembers = ref<any[]>([])

// Status options
const statusOptions = [
  { value: 'active', label: 'ทำงาน', color: 'green' },
  { value: 'on_leave', label: 'ลาพัก', color: 'amber' },
  { value: 'resigned', label: 'ลาออก', color: 'gray' },
  { value: 'retired', label: 'เกษียณ', color: 'blue' }
]

const employeeTypes = [
  { value: 'full_time', label: 'พนักงานประจำ' },
  { value: 'part_time', label: 'พนักงานพาร์ทไทม์' },
  { value: 'contract', label: 'พนักงานสัญญาจ้าง' },
  { value: 'intern', label: 'นักศึกษาฝึกงาน' }
]

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }
      
      await Promise.all([
        fetchStaff(),
        fetchPositions(),
        fetchSummary()
      ])
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

// Fetch staff
const fetchStaff = async () => {
  if (!academyId.value) return
  
  isLoadingStaff.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/staff`, {
      search: searchQuery.value || undefined,
      status: filterStatus.value || undefined,
      position_id: filterPosition.value || undefined,
      page: pagination.value.current_page,
      per_page: pagination.value.per_page
    })
    
    if (response.success) {
      staff.value = response.data || []
      if (response.pagination) {
        pagination.value = response.pagination
      }
    }
  } catch (err) {
    console.error('Failed to fetch staff:', err)
  } finally {
    isLoadingStaff.value = false
  }
}

// Fetch positions
const fetchPositions = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/staff/positions`)
    if (response.success) {
      positions.value = response.data || []
    }
  } catch (err) {
    console.error('Failed to fetch positions:', err)
  }
}

// Fetch summary
const fetchSummary = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/staff/summary`)
    if (response.success) {
      summary.value = response.data
    }
  } catch (err) {
    console.error('Failed to fetch summary:', err)
  }
}

// Fetch available members
const fetchAvailableMembers = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/members`, {
      status: 2,
      per_page: 100
    })
    if (response.success) {
      // Filter out members who are already staff
      const staffUserIds = staff.value.map(s => s.user_id)
      availableMembers.value = (response.members || []).filter(
        (m: any) => !staffUserIds.includes(m.user_id)
      )
    }
  } catch (err) {
    console.error('Failed to fetch members:', err)
  }
}

// Search with debounce
let searchTimeout: NodeJS.Timeout
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1
    fetchStaff()
  }, 300)
}

// Open create modal
const openCreateModal = async () => {
  staffForm.value = {
    user_id: null,
    position_id: null,
    employee_type: 'full_time',
    status: 'active',
    hire_date: new Date().toISOString().split('T')[0],
    department: ''
  }
  formErrors.value = {}
  await fetchAvailableMembers()
  showCreateModal.value = true
}

// Open edit modal
const openEditModal = (staffMember: any) => {
  selectedStaff.value = staffMember
  staffForm.value = {
    user_id: staffMember.user_id,
    position_id: staffMember.position_id,
    employee_type: staffMember.employee_type || 'full_time',
    status: staffMember.status || 'active',
    hire_date: staffMember.hire_date?.split('T')[0] || '',
    department: staffMember.department || ''
  }
  formErrors.value = {}
  showEditModal.value = true
}

// Create staff
const createStaff = async () => {
  if (!academyId.value) return
  
  isSubmitting.value = true
  formErrors.value = {}
  
  try {
    const response: any = await api.post(`/api/academies/${academyId.value}/staff`, staffForm.value)
    
    if (response.success) {
      showCreateModal.value = false
      await fetchStaff()
      await fetchSummary()
      
      Swal.fire({
        icon: 'success',
        title: 'เพิ่มบุคลากรสำเร็จ',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถเพิ่มบุคลากรได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Update staff
const updateStaff = async () => {
  if (!academyId.value || !selectedStaff.value) return
  
  isSubmitting.value = true
  formErrors.value = {}
  
  try {
    const response: any = await api.put(
      `/api/academies/${academyId.value}/staff/${selectedStaff.value.id}`,
      staffForm.value
    )
    
    if (response.success) {
      showEditModal.value = false
      await fetchStaff()
      
      Swal.fire({
        icon: 'success',
        title: 'อัปเดตสำเร็จ',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    if (err.response?.data?.errors) {
      formErrors.value = err.response.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถอัปเดตได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Update status
const updateStatus = async (staffMember: any, newStatus: string) => {
  try {
    const response: any = await api.put(
      `/api/academies/${academyId.value}/staff/${staffMember.id}/status`,
      { status: newStatus }
    )
    
    if (response.success) {
      await fetchStaff()
      await fetchSummary()
      
      Swal.fire({
        icon: 'success',
        title: 'อัปเดตสถานะสำเร็จ',
        timer: 1500,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err.response?.data?.message || 'ไม่สามารถอัปเดตสถานะได้'
    })
  }
}

// Delete staff
const deleteStaff = async (staffMember: any) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันการลบ',
    text: `คุณต้องการลบ "${staffMember.user?.name}" ออกจากระบบบุคลากรหรือไม่?`,
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })
  
  if (result.isConfirmed) {
    try {
      const response: any = await api.delete(
        `/api/academies/${academyId.value}/staff/${staffMember.id}`
      )
      
      if (response.success) {
        await fetchStaff()
        await fetchSummary()
        
        Swal.fire({
          icon: 'success',
          title: 'ลบสำเร็จ',
          timer: 2000,
          showConfirmButton: false
        })
      }
    } catch (err: any) {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถลบได้'
      })
    }
  }
}

// Helper functions
const getStatusInfo = (status: string) => {
  return statusOptions.find(s => s.value === status) || statusOptions[0]
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการบุคลากร</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการข้อมูลครูและบุคลากรของโรงเรียน</p>
        </div>
        <div class="flex gap-2">
          <button
            @click="showPositionModal = true"
            class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            <Icon name="fluent:tag-24-regular" class="w-5 h-5" />
            <span>ตำแหน่ง</span>
          </button>
          <button
            @click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors"
          >
            <Icon name="fluent:add-24-filled" class="w-5 h-5" />
            <span>เพิ่มบุคลากร</span>
          </button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div v-if="summary" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
              <Icon name="fluent:people-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.total || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">บุคลากรทั้งหมด</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
              <Icon name="fluent:person-available-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.active || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ทำงานอยู่</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-amber-100 dark:bg-amber-900/50 rounded-xl">
              <Icon name="fluent:person-clock-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.on_leave || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ลาพัก</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-xl">
              <Icon name="fluent:briefcase-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ positions.length }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ตำแหน่ง</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="relative flex-1">
            <Icon name="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              v-model="searchQuery"
              @input="handleSearch"
              type="text"
              placeholder="ค้นหาบุคลากร..."
              class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>
          <select
            v-model="filterPosition"
            @change="pagination.current_page = 1; fetchStaff()"
            class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
          >
            <option :value="null">ตำแหน่งทั้งหมด</option>
            <option v-for="pos in positions" :key="pos.id" :value="pos.id">
              {{ pos.name }}
            </option>
          </select>
          <select
            v-model="filterStatus"
            @change="pagination.current_page = 1; fetchStaff()"
            class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white"
          >
            <option value="">สถานะทั้งหมด</option>
            <option v-for="status in statusOptions" :key="status.value" :value="status.value">
              {{ status.label }}
            </option>
          </select>
        </div>
      </div>

      <!-- Staff List -->
      <div v-if="isLoadingStaff" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
      </div>

      <div v-else-if="staff.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-sm border border-gray-100 dark:border-gray-700">
        <Icon name="fluent:people-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีบุคลากร</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">เริ่มต้นเพิ่มบุคลากรคนแรก</p>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors"
        >
          <Icon name="fluent:add-24-filled" class="w-5 h-5" />
          <span>เพิ่มบุคลากร</span>
        </button>
      </div>

      <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">บุคลากร</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ตำแหน่ง</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ประเภท</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">จัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="member in staff" :key="member.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <img
                    :src="member.user?.avatar || '/images/default-avatar.png'"
                    :alt="member.user?.name"
                    class="w-10 h-10 rounded-full object-cover"
                  />
                  <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ member.user?.name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ member.employee_id }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3">
                <p class="text-gray-900 dark:text-white">{{ member.position?.name || '-' }}</p>
                <p v-if="member.department" class="text-sm text-gray-500 dark:text-gray-400">{{ member.department }}</p>
              </td>
              <td class="px-4 py-3">
                <span class="text-gray-600 dark:text-gray-400">
                  {{ employeeTypes.find(t => t.value === member.employee_type)?.label || member.employee_type }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium',
                    getStatusInfo(member.status).color === 'green' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' :
                    getStatusInfo(member.status).color === 'amber' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' :
                    getStatusInfo(member.status).color === 'blue' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' :
                    'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                  ]"
                >
                  {{ getStatusInfo(member.status).label }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    @click="openEditModal(member)"
                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    title="แก้ไข"
                  >
                    <Icon name="fluent:edit-24-regular" class="w-5 h-5 text-gray-500" />
                  </button>
                  <button
                    @click="deleteStaff(member)"
                    class="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                    title="ลบ"
                  >
                    <Icon name="fluent:delete-24-regular" class="w-5 h-5 text-red-500" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        
        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2 py-4 border-t border-gray-100 dark:border-gray-700">
          <button
            @click="pagination.current_page--; fetchStaff()"
            :disabled="pagination.current_page === 1"
            class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-50"
          >
            ก่อนหน้า
          </button>
          <span class="text-gray-600 dark:text-gray-400">
            หน้า {{ pagination.current_page }} / {{ pagination.last_page }}
          </span>
          <button
            @click="pagination.current_page++; fetchStaff()"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-50"
          >
            ถัดไป
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showCreateModal = false; showEditModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ showEditModal ? 'แก้ไขข้อมูลบุคลากร' : 'เพิ่มบุคลากร' }}
            </h3>
            <button @click="showCreateModal = false; showEditModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <form @submit.prevent="showEditModal ? updateStaff() : createStaff()" class="p-5 space-y-4">
            <!-- Select Member (Create only) -->
            <div v-if="showCreateModal">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">สมาชิก *</label>
              <select
                v-model="staffForm.user_id"
                required
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              >
                <option :value="null" disabled>เลือกสมาชิก</option>
                <option v-for="member in availableMembers" :key="member.user_id" :value="member.user_id">
                  {{ member.user?.name }} ({{ member.user?.email }})
                </option>
              </select>
            </div>
            
            <!-- Position -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ตำแหน่ง *</label>
              <select
                v-model="staffForm.position_id"
                required
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              >
                <option :value="null" disabled>เลือกตำแหน่ง</option>
                <option v-for="pos in positions" :key="pos.id" :value="pos.id">
                  {{ pos.name }}
                </option>
              </select>
            </div>
            
            <!-- Employee Type -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ประเภท</label>
              <select
                v-model="staffForm.employee_type"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              >
                <option v-for="type in employeeTypes" :key="type.value" :value="type.value">
                  {{ type.label }}
                </option>
              </select>
            </div>
            
            <!-- Hire Date -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">วันที่เริ่มงาน</label>
              <input
                v-model="staffForm.hire_date"
                type="date"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              />
            </div>
            
            <!-- Department -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ฝ่าย/แผนก</label>
              <input
                v-model="staffForm.department"
                type="text"
                placeholder="เช่น ฝ่ายวิชาการ"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              />
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="showCreateModal = false; showEditModal = false"
                class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                ยกเลิก
              </button>
              <button
                type="submit"
                :disabled="isSubmitting"
                class="flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                <span>{{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึก' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Position Management Modal -->
    <Teleport to="body">
      <div v-if="showPositionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showPositionModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">จัดการตำแหน่ง</h3>
            <button @click="showPositionModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <div class="p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
              รายการตำแหน่งที่มีอยู่ในระบบ
            </p>
            
            <div v-if="positions.length === 0" class="text-center py-8">
              <Icon name="fluent:tag-24-regular" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
              <p class="text-gray-500 dark:text-gray-400">ยังไม่มีตำแหน่ง</p>
            </div>
            
            <ul v-else class="space-y-2">
              <li
                v-for="pos in positions"
                :key="pos.id"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
              >
                <span class="text-gray-900 dark:text-white">{{ pos.name }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ pos.staff_count || 0 }} คน</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
/**
 * Academy Admin - Staff Management
 * หน้าจัดการบุคลากร (เมนู #14)
 * - อ่าน = staff.view · จัดการ = staff.manage (ST-S1)
 * - ชื่อ/รูปจากบัญชี user ที่ผูก (Q1) · ฝ่ายผูก department_id (Q4)
 */
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const staff = ref<any[]>([])
const positions = ref<any[]>([])
const departments = ref<any[]>([])
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
const canManage = computed(() => isAdmin.value || can('staff.manage'))

// Form (ตรงกับ schema จริง: employment_type / department_id)
const staffForm = ref({
  user_id: null as number | null,
  position_id: null as number | null,
  department_id: null as number | null,
  employment_type: 'full_time',
  hire_date: ''
})
const isSubmitting = ref(false)
const formErrors = ref<Record<string, string[]>>({})

// Position form (ST-S4)
const positionForm = ref({
  id: null as number | null,
  name: '',
  code: '',
  department_id: null as number | null,
  is_teaching_position: false,
  is_active: true
})
const isSubmittingPosition = ref(false)
const positionErrors = ref<Record<string, string[]>>({})

// Available members (for adding staff)
const availableMembers = ref<any[]>([])

// Status options (ตรงกับ enum จริง — ST-S5 / Q5)
const statusOptions = [
  { value: 'active', label: 'ปฏิบัติงาน', color: 'green' },
  { value: 'on_leave', label: 'ลา', color: 'amber' },
  { value: 'suspended', label: 'พักงาน', color: 'orange' },
  { value: 'resigned', label: 'ลาออก', color: 'gray' },
  { value: 'terminated', label: 'เลิกจ้าง', color: 'red' }
]

// Employment types (ตรงกับ enum จริง — Q5, ตัด intern ใส่ temporary)
const employeeTypes = [
  { value: 'full_time', label: 'พนักงานประจำ' },
  { value: 'part_time', label: 'พนักงานไม่เต็มเวลา' },
  { value: 'contract', label: 'พนักงานสัญญาจ้าง' },
  { value: 'temporary', label: 'พนักงานชั่วคราว' }
]

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()

      // เปิดหน้าให้ผู้ถือ staff.view (ไม่ใช่แค่ admin) — Q3
      if (!isAdmin.value && !can('staff.view')) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }

      await Promise.all([
        fetchStaff(),
        fetchPositions(),
        fetchDepartments(),
        fetchSummary()
      ])
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

// Fetch staff (query params ต้องอยู่ใน URL — ST-S3 F4)
const fetchStaff = async () => {
  if (!academyId.value) return

  isLoadingStaff.value = true
  try {
    const params = new URLSearchParams()
    if (searchQuery.value) params.set('search', searchQuery.value)
    if (filterStatus.value) params.set('status', filterStatus.value)
    if (filterPosition.value) params.set('position_id', String(filterPosition.value))
    params.set('page', String(pagination.value.current_page))
    params.set('per_page', String(pagination.value.per_page))

    const response: any = await api.get(`/api/academies/${academyId.value}/staff?${params.toString()}`)

    if (response.success) {
      // backend คืน paginator object ใน data — ST-S3 F2
      const pageData = response.data || {}
      staff.value = pageData.data || []
      pagination.value = {
        current_page: pageData.current_page || 1,
        last_page: pageData.last_page || 1,
        per_page: pageData.per_page || pagination.value.per_page,
        total: pageData.total || 0
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

// Fetch departments (สำหรับ dropdown — Q4)
const fetchDepartments = async () => {
  if (!academyId.value) return

  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/staff/departments`)
    if (response.success) {
      departments.value = response.data || []
    }
  } catch (err) {
    console.error('Failed to fetch departments:', err)
  }
}

// Fetch summary (counts ซ้อนใต้ by_status — ST-S3 F3)
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
    const response: any = await api.get(`/api/academies/${academyId.value}/members?status=2&per_page=100`)
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
    department_id: null,
    employment_type: 'full_time',
    hire_date: new Date().toISOString().split('T')[0]
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
    department_id: staffMember.department_id ?? null,
    employment_type: staffMember.employment_type || 'full_time',
    hire_date: staffMember.hire_date?.split('T')[0] || ''
  }
  formErrors.value = {}
  showEditModal.value = true
}

const closeStaffModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
}

// Create staff
const createStaff = async () => {
  if (!academyId.value) return

  isSubmitting.value = true
  formErrors.value = {}

  try {
    const response: any = await api.post(`/api/academies/${academyId.value}/staff`, staffForm.value)

    if (response.success) {
      closeStaffModal()
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
    if (err.data?.errors) {
      formErrors.value = err.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.data?.message || 'ไม่สามารถเพิ่มบุคลากรได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Update staff (PATCH — ST-S3 F6)
const updateStaff = async () => {
  if (!academyId.value || !selectedStaff.value) return

  isSubmitting.value = true
  formErrors.value = {}

  try {
    const response: any = await api.patch(
      `/api/academies/${academyId.value}/staff/${selectedStaff.value.id}`,
      staffForm.value
    )

    if (response.success) {
      closeStaffModal()
      await fetchStaff()

      Swal.fire({
        icon: 'success',
        title: 'อัปเดตสำเร็จ',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    if (err.data?.errors) {
      formErrors.value = err.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.data?.message || 'ไม่สามารถอัปเดตได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Update status (PATCH — ST-S3 F6 / ST-S5)
const updateStatus = async (staffMember: any, newStatus: string) => {
  if (newStatus === staffMember.status) return

  try {
    const response: any = await api.patch(
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
      text: err.data?.message || 'ไม่สามารถอัปเดตสถานะได้'
    })
    await fetchStaff()
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
        text: err.data?.message || 'ไม่สามารถลบได้'
      })
    }
  }
}

// --- Position management (ST-S4) ---
const openPositionModal = () => {
  resetPositionForm()
  showPositionModal.value = true
}

const resetPositionForm = () => {
  positionForm.value = {
    id: null,
    name: '',
    code: '',
    department_id: null,
    is_teaching_position: false,
    is_active: true
  }
  positionErrors.value = {}
}

const editPosition = (pos: any) => {
  positionForm.value = {
    id: pos.id,
    name: pos.name || '',
    code: pos.code || '',
    department_id: pos.department_id ?? null,
    is_teaching_position: !!pos.is_teaching_position,
    is_active: pos.is_active ?? true
  }
  positionErrors.value = {}
}

const savePosition = async () => {
  if (!academyId.value) return

  isSubmittingPosition.value = true
  positionErrors.value = {}

  const payload = {
    name: positionForm.value.name,
    code: positionForm.value.code || null,
    department_id: positionForm.value.department_id,
    is_teaching_position: positionForm.value.is_teaching_position,
    is_active: positionForm.value.is_active
  }

  try {
    const response: any = positionForm.value.id
      ? await api.patch(`/api/academies/${academyId.value}/staff/positions/${positionForm.value.id}`, payload)
      : await api.post(`/api/academies/${academyId.value}/staff/positions`, payload)

    if (response.success) {
      resetPositionForm()
      await fetchPositions()

      Swal.fire({
        icon: 'success',
        title: positionForm.value.id ? 'อัปเดตตำแหน่งสำเร็จ' : 'เพิ่มตำแหน่งสำเร็จ',
        timer: 1500,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    if (err.data?.errors) {
      positionErrors.value = err.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.data?.message || 'ไม่สามารถบันทึกตำแหน่งได้'
      })
    }
  } finally {
    isSubmittingPosition.value = false
  }
}

const deletePosition = async (pos: any) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันการลบตำแหน่ง',
    text: `ลบตำแหน่ง "${pos.name}" หรือไม่?`,
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })
  if (!result.isConfirmed) return

  try {
    const response: any = await api.delete(`/api/academies/${academyId.value}/staff/positions/${pos.id}`)
    if (response.success) {
      await fetchPositions()
      Swal.fire({ icon: 'success', title: 'ลบตำแหน่งสำเร็จ', timer: 1500, showConfirmButton: false })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err.data?.message || 'ไม่สามารถลบตำแหน่งได้'
    })
  }
}

// Helper functions
const getStatusInfo = (status: string) => {
  return statusOptions.find(s => s.value === status) || statusOptions[0]
}
const statusBadgeClass = (status: string) => {
  const color = getStatusInfo(status).color
  const map: Record<string, string> = {
    green: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
    amber: 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    orange: 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300',
    red: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
    gray: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
  }
  return map[color] || map.gray
}
</script>

<template>
  <div class="px-4 sm:px-0">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="min-w-0">
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการบุคลากร</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการข้อมูลครูและบุคลากรของโรงเรียน</p>
        </div>
        <div v-if="canManage" class="flex flex-wrap gap-2 flex-shrink-0">
          <button
            @click="openPositionModal"
            class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors whitespace-nowrap"
          >
            <Icon icon="fluent:tag-24-regular" class="w-5 h-5" />
            <span>ตำแหน่ง</span>
          </button>
          <button
            @click="openCreateModal"
            class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors whitespace-nowrap"
          >
            <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
            <span>เพิ่มบุคลากร</span>
          </button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div v-if="summary" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl flex-shrink-0">
              <Icon icon="fluent:people-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="min-w-0">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.total || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">บุคลากรทั้งหมด</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl flex-shrink-0">
              <Icon icon="fluent:person-available-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div class="min-w-0">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.by_status?.active || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ปฏิบัติงาน</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-amber-100 dark:bg-amber-900/50 rounded-xl flex-shrink-0">
              <Icon icon="fluent:person-clock-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div class="min-w-0">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.by_status?.on_leave || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ลา</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-xl flex-shrink-0">
              <Icon icon="fluent:briefcase-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div class="min-w-0">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ positions.length }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ตำแหน่ง</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="relative flex-1 min-w-0">
            <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
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

      <div v-else-if="staff.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-12 text-center shadow-sm border border-gray-100 dark:border-gray-700">
        <Icon icon="fluent:people-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีบุคลากร</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">
          {{ positions.length === 0 ? 'เริ่มจากสร้างตำแหน่งก่อน แล้วค่อยเพิ่มบุคลากร' : 'เริ่มต้นเพิ่มบุคลากรคนแรก' }}
        </p>
        <button
          v-if="canManage"
          @click="positions.length === 0 ? openPositionModal() : openCreateModal()"
          class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors"
        >
          <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
          <span>{{ positions.length === 0 ? 'สร้างตำแหน่ง' : 'เพิ่มบุคลากร' }}</span>
        </button>
      </div>

      <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">บุคลากร</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">ตำแหน่ง</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">ประเภท</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">สถานะ</th>
                <th v-if="canManage" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap">จัดการ</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-for="member in staff" :key="member.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <img
                      :src="member.user?.avatar || '/images/default-avatar.png'"
                      :alt="member.user?.name"
                      class="w-10 h-10 rounded-full object-cover flex-shrink-0"
                    />
                    <div class="min-w-0">
                      <p class="font-medium text-gray-900 dark:text-white break-words">{{ member.user?.name }}</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">{{ member.employee_id }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <p class="text-gray-900 dark:text-white break-words">{{ member.position?.name || '-' }}</p>
                  <p v-if="member.department" class="text-sm text-gray-500 dark:text-gray-400 break-words">{{ member.department?.name }}</p>
                </td>
                <td class="px-4 py-3">
                  <span class="text-gray-600 dark:text-gray-400 whitespace-nowrap">
                    {{ employeeTypes.find(t => t.value === member.employment_type)?.label || member.employment_type }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <select
                    v-if="canManage"
                    :value="member.status"
                    @change="updateStatus(member, ($event.target as HTMLSelectElement).value)"
                    class="min-h-[44px] sm:min-h-0 px-2.5 py-1 rounded-lg text-xs font-medium border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                  >
                    <option v-for="s in statusOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                  </select>
                  <span
                    v-else
                    :class="['inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium whitespace-nowrap', statusBadgeClass(member.status)]"
                  >
                    {{ getStatusInfo(member.status).label }}
                  </span>
                </td>
                <td v-if="canManage" class="px-4 py-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="openEditModal(member)"
                      class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                      title="แก้ไข"
                    >
                      <Icon icon="fluent:edit-24-regular" class="w-5 h-5 text-gray-500" />
                    </button>
                    <button
                      @click="deleteStaff(member)"
                      class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                      title="ลบ"
                    >
                      <Icon icon="fluent:delete-24-regular" class="w-5 h-5 text-red-500" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2 py-4 border-t border-gray-100 dark:border-gray-700">
          <button
            @click="pagination.current_page--; fetchStaff()"
            :disabled="pagination.current_page === 1"
            class="min-h-[44px] sm:min-h-0 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-50"
          >
            ก่อนหน้า
          </button>
          <span class="text-gray-600 dark:text-gray-400 whitespace-nowrap">
            หน้า {{ pagination.current_page }} / {{ pagination.last_page }}
          </span>
          <button
            @click="pagination.current_page++; fetchStaff()"
            :disabled="pagination.current_page === pagination.last_page"
            class="min-h-[44px] sm:min-h-0 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-50"
          >
            ถัดไป
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeStaffModal"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ showEditModal ? 'แก้ไขข้อมูลบุคลากร' : 'เพิ่มบุคลากร' }}
            </h3>
            <button @click="closeStaffModal" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
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
              <p v-if="formErrors.user_id" class="text-sm text-red-500 mt-1">{{ formErrors.user_id[0] }}</p>
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
              <p v-if="formErrors.position_id" class="text-sm text-red-500 mt-1">{{ formErrors.position_id[0] }}</p>
            </div>

            <!-- Department -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ฝ่าย/แผนก</label>
              <select
                v-model="staffForm.department_id"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              >
                <option :value="null">ไม่ระบุ</option>
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                  {{ dept.name }}
                </option>
              </select>
            </div>

            <!-- Employee Type -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ประเภท</label>
              <select
                v-model="staffForm.employment_type"
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

            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="closeStaffModal"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
              >
                ยกเลิก
              </button>
              <button
                type="submit"
                :disabled="isSubmitting"
                class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
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
            <button @click="showPositionModal = false" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>

          <div class="p-5 space-y-5">
            <!-- Create / Edit form -->
            <form v-if="canManage" @submit.prevent="savePosition" class="space-y-3 pb-4 border-b border-gray-100 dark:border-gray-700">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ positionForm.id ? 'แก้ไขตำแหน่ง' : 'เพิ่มตำแหน่งใหม่' }}
              </p>
              <div>
                <input
                  v-model="positionForm.name"
                  type="text"
                  required
                  placeholder="ชื่อตำแหน่ง เช่น ครูผู้สอน *"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                />
                <p v-if="positionErrors.name" class="text-sm text-red-500 mt-1">{{ positionErrors.name[0] }}</p>
              </div>
              <div class="flex flex-col sm:flex-row gap-3">
                <input
                  v-model="positionForm.code"
                  type="text"
                  placeholder="รหัส (ไม่บังคับ)"
                  class="flex-1 min-w-0 px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                />
                <select
                  v-model="positionForm.department_id"
                  class="flex-1 min-w-0 px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
                >
                  <option :value="null">ไม่ระบุฝ่าย</option>
                  <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                </select>
              </div>
              <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 min-h-[44px] sm:min-h-0">
                <input v-model="positionForm.is_teaching_position" type="checkbox" class="rounded border-gray-300" />
                <span>เป็นตำแหน่งสายการสอน</span>
              </label>
              <p v-if="positionErrors.code" class="text-sm text-red-500">{{ positionErrors.code[0] }}</p>
              <div class="flex items-center gap-2">
                <button
                  v-if="positionForm.id"
                  type="button"
                  @click="resetPositionForm"
                  class="min-h-[44px] sm:min-h-0 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium"
                >
                  ยกเลิก
                </button>
                <button
                  type="submit"
                  :disabled="isSubmittingPosition"
                  class="min-h-[44px] sm:min-h-0 flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                >
                  <div v-if="isSubmittingPosition" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                  <span>{{ positionForm.id ? 'บันทึกการแก้ไข' : 'เพิ่มตำแหน่ง' }}</span>
                </button>
              </div>
            </form>

            <!-- List -->
            <div v-if="positions.length === 0" class="text-center py-8">
              <Icon icon="fluent:tag-24-regular" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
              <p class="text-gray-500 dark:text-gray-400">ยังไม่มีตำแหน่ง</p>
            </div>

            <ul v-else class="space-y-2">
              <li
                v-for="pos in positions"
                :key="pos.id"
                class="flex items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
              >
                <div class="min-w-0">
                  <span class="text-gray-900 dark:text-white break-words">{{ pos.name }}</span>
                  <span class="block text-xs text-gray-500 dark:text-gray-400">{{ pos.staff_count || 0 }} คน</span>
                </div>
                <div v-if="canManage" class="flex items-center gap-1 flex-shrink-0">
                  <button
                    @click="editPosition(pos)"
                    class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg"
                    title="แก้ไข"
                  >
                    <Icon icon="fluent:edit-24-regular" class="w-4 h-4 text-gray-500" />
                  </button>
                  <button
                    @click="deletePosition(pos)"
                    class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg"
                    title="ลบ"
                  >
                    <Icon icon="fluent:delete-24-regular" class="w-4 h-4 text-red-500" />
                  </button>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
/**
 * Academy Admin - Department Management
 * หน้าจัดการฝ่ายงาน/แผนกของโรงเรียน
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
const departments = ref<any[]>([])
const statistics = ref<any>(null)
const isLoading = ref(true)
const isLoadingDepartments = ref(false)

// Filters
const searchQuery = ref('')

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
const showMembersModal = ref(false)
const selectedDepartment = ref<any>(null)

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Form
const departmentForm = ref({
  name: '',
  description: '',
  head_user_id: null as number | null
})
const isSubmitting = ref(false)
const formErrors = ref<Record<string, string[]>>({})

// Members Management
const departmentMembers = ref<any[]>([])
const isLoadingMembers = ref(false)
const memberSearchQuery = ref('')
const showAddMemberModal = ref(false)
const availableMembers = ref<any[]>([])
const selectedMemberIds = ref<number[]>([])
const memberRole = ref('member')

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
        fetchDepartments(),
        fetchStatistics()
      ])
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

// Fetch departments
const fetchDepartments = async () => {
  if (!academyId.value) return
  
  isLoadingDepartments.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/departments`, {
      search: searchQuery.value || undefined,
      page: pagination.value.current_page,
      per_page: pagination.value.per_page
    })
    
    if (response.success) {
      departments.value = response.departments || []
      if (response.pagination) {
        pagination.value = response.pagination
      }
    }
  } catch (err) {
    console.error('Failed to fetch departments:', err)
  } finally {
    isLoadingDepartments.value = false
  }
}

// Fetch statistics
const fetchStatistics = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/departments/statistics`)
    if (response.success) {
      statistics.value = response.statistics
    }
  } catch (err) {
    console.error('Failed to fetch statistics:', err)
  }
}

// Search with debounce
let searchTimeout: NodeJS.Timeout
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1
    fetchDepartments()
  }, 300)
}

// Open create modal
const openCreateModal = () => {
  departmentForm.value = {
    name: '',
    description: '',
    head_user_id: null
  }
  formErrors.value = {}
  showCreateModal.value = true
}

// Open edit modal
const openEditModal = (department: any) => {
  selectedDepartment.value = department
  departmentForm.value = {
    name: department.name,
    description: department.description || '',
    head_user_id: department.head_user_id
  }
  formErrors.value = {}
  showEditModal.value = true
}

// Create department
const createDepartment = async () => {
  if (!academyId.value) return
  
  isSubmitting.value = true
  formErrors.value = {}
  
  try {
    const response: any = await api.post(`/api/academies/${academyId.value}/departments`, {
      name: departmentForm.value.name,
      description: departmentForm.value.description || undefined,
      head_user_id: departmentForm.value.head_user_id || undefined
    })
    
    if (response.success) {
      showCreateModal.value = false
      await fetchDepartments()
      await fetchStatistics()
      
      Swal.fire({
        icon: 'success',
        title: 'สร้างฝ่ายงานสำเร็จ',
        text: `สร้างฝ่าย "${departmentForm.value.name}" เรียบร้อยแล้ว`,
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
        text: err.response?.data?.message || 'ไม่สามารถสร้างฝ่ายงานได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Update department
const updateDepartment = async () => {
  if (!academyId.value || !selectedDepartment.value) return
  
  isSubmitting.value = true
  formErrors.value = {}
  
  try {
    const response: any = await api.put(`/api/academies/departments/${selectedDepartment.value.id}`, {
      name: departmentForm.value.name,
      description: departmentForm.value.description || undefined,
      head_user_id: departmentForm.value.head_user_id || undefined
    })
    
    if (response.success) {
      showEditModal.value = false
      await fetchDepartments()
      
      Swal.fire({
        icon: 'success',
        title: 'อัปเดตสำเร็จ',
        text: 'อัปเดตข้อมูลฝ่ายงานเรียบร้อยแล้ว',
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
        text: err.response?.data?.message || 'ไม่สามารถอัปเดตฝ่ายงานได้'
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

// Delete department
const deleteDepartment = async (department: any) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันการลบ',
    text: `คุณต้องการลบฝ่าย "${department.name}" หรือไม่?`,
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })
  
  if (result.isConfirmed) {
    try {
      const response: any = await api.delete(`/api/academies/departments/${department.id}`)
      
      if (response.success) {
        await fetchDepartments()
        await fetchStatistics()
        
        Swal.fire({
          icon: 'success',
          title: 'ลบสำเร็จ',
          text: 'ลบฝ่ายงานเรียบร้อยแล้ว',
          timer: 2000,
          showConfirmButton: false
        })
      }
    } catch (err: any) {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถลบฝ่ายงานได้'
      })
    }
  }
}

// Open members modal
const openMembersModal = async (department: any) => {
  selectedDepartment.value = department
  showMembersModal.value = true
  await fetchDepartmentMembers(department.id)
}

// Fetch department members
const fetchDepartmentMembers = async (departmentId: number) => {
  isLoadingMembers.value = true
  try {
    const response: any = await api.get(`/api/academies/departments/${departmentId}/members`)
    if (response.success) {
      departmentMembers.value = response.members || []
    }
  } catch (err) {
    console.error('Failed to fetch members:', err)
  } finally {
    isLoadingMembers.value = false
  }
}

// Open add member modal
const openAddMemberModal = async () => {
  showAddMemberModal.value = true
  selectedMemberIds.value = []
  memberRole.value = 'member'
  await fetchAvailableMembers()
}

// Fetch available members (academy members not in this department)
const fetchAvailableMembers = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/members`, {
      status: 2, // Approved members
      per_page: 100
    })
    
    if (response.success) {
      const existingMemberIds = departmentMembers.value.map((m: any) => m.user_id)
      availableMembers.value = (response.members || []).filter(
        (m: any) => !existingMemberIds.includes(m.user_id)
      )
    }
  } catch (err) {
    console.error('Failed to fetch available members:', err)
  }
}

// Add members to department
const addMembersToDepartment = async () => {
  if (!selectedDepartment.value || selectedMemberIds.value.length === 0) return
  
  isSubmitting.value = true
  try {
    const response: any = await api.post(
      `/api/academies/departments/${selectedDepartment.value.id}/members/bulk`,
      {
        user_ids: selectedMemberIds.value,
        role: memberRole.value
      }
    )
    
    if (response.success) {
      showAddMemberModal.value = false
      await fetchDepartmentMembers(selectedDepartment.value.id)
      await fetchDepartments()
      
      Swal.fire({
        icon: 'success',
        title: 'เพิ่มสมาชิกสำเร็จ',
        text: `เพิ่ม ${selectedMemberIds.value.length} คนเข้าฝ่ายงานเรียบร้อยแล้ว`,
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err.response?.data?.message || 'ไม่สามารถเพิ่มสมาชิกได้'
    })
  } finally {
    isSubmitting.value = false
  }
}

// Remove member from department
const removeMember = async (memberId: number) => {
  if (!selectedDepartment.value) return
  
  const result = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันการนำออก',
    text: 'คุณต้องการนำสมาชิกออกจากฝ่ายงานนี้หรือไม่?',
    showCancelButton: true,
    confirmButtonText: 'นำออก',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })
  
  if (result.isConfirmed) {
    try {
      const response: any = await api.delete(
        `/api/academies/departments/${selectedDepartment.value.id}/members/${memberId}`
      )
      
      if (response.success) {
        await fetchDepartmentMembers(selectedDepartment.value.id)
        await fetchDepartments()
        
        Swal.fire({
          icon: 'success',
          title: 'นำออกสำเร็จ',
          timer: 2000,
          showConfirmButton: false
        })
      }
    } catch (err: any) {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถนำสมาชิกออกได้'
      })
    }
  }
}

// Update member role
const updateMemberRole = async (memberId: number, newRole: string) => {
  if (!selectedDepartment.value) return
  
  try {
    const response: any = await api.put(
      `/api/academies/departments/${selectedDepartment.value.id}/members/${memberId}/role`,
      { role: newRole }
    )
    
    if (response.success) {
      await fetchDepartmentMembers(selectedDepartment.value.id)
      
      Swal.fire({
        icon: 'success',
        title: 'อัปเดตบทบาทสำเร็จ',
        timer: 1500,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err.response?.data?.message || 'ไม่สามารถอัปเดตบทบาทได้'
    })
  }
}

// Filtered members for search
const filteredAvailableMembers = computed(() => {
  if (!memberSearchQuery.value) return availableMembers.value
  const query = memberSearchQuery.value.toLowerCase()
  return availableMembers.value.filter((m: any) => 
    m.user?.name?.toLowerCase().includes(query) ||
    m.user?.email?.toLowerCase().includes(query)
  )
})

// Role options
const roleOptions = [
  { value: 'member', label: 'สมาชิก' },
  { value: 'head', label: 'หัวหน้าฝ่าย' },
  { value: 'deputy', label: 'รองหัวหน้าฝ่าย' },
  { value: 'secretary', label: 'เลขานุการ' }
]

const getRoleBadgeClass = (role: string) => {
  switch (role) {
    case 'head': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'
    case 'deputy': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'
    case 'secretary': return 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300'
    default: return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
  }
}

const getRoleLabel = (role: string) => {
  return roleOptions.find(r => r.value === role)?.label || role
}
</script>

<template>
  <NuxtLayout name="academy-admin" :academy-name="academyName">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการฝ่ายงาน</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการฝ่ายงาน/แผนกต่างๆ ของโรงเรียน</p>
        </div>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors"
        >
          <Icon name="fluent:add-24-filled" class="w-5 h-5" />
          <span>สร้างฝ่ายงาน</span>
        </button>
      </div>

      <!-- Statistics Cards -->
      <div v-if="statistics" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
              <Icon name="fluent:building-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.total_departments }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ฝ่ายงานทั้งหมด</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
              <Icon name="fluent:people-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.total_members }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">สมาชิกทั้งหมด</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-amber-100 dark:bg-amber-900/50 rounded-xl">
              <Icon name="fluent:person-star-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.departments_with_head }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">มีหัวหน้าฝ่าย</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-xl">
              <Icon name="fluent:chart-multiple-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ (statistics.total_members / (statistics.total_departments || 1)).toFixed(1) }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">เฉลี่ย/ฝ่าย</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Search Bar -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="relative">
          <Icon name="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="ค้นหาฝ่ายงาน..."
            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
        </div>
      </div>

      <!-- Department List -->
      <div v-if="isLoadingDepartments" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
      </div>

      <div v-else-if="departments.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-sm border border-gray-100 dark:border-gray-700">
        <Icon name="fluent:building-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีฝ่ายงาน</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-4">เริ่มต้นสร้างฝ่ายงานแรกของโรงเรียน</p>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors"
        >
          <Icon name="fluent:add-24-filled" class="w-5 h-5" />
          <span>สร้างฝ่ายงาน</span>
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="department in departments"
          :key="department.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow"
        >
          <div class="p-5">
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="p-2.5 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
                  <Icon name="fluent:building-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900 dark:text-white">{{ department.name }}</h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400">{{ department.members_count || 0 }} สมาชิก</p>
                </div>
              </div>
              <div class="relative group">
                <button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                  <Icon name="fluent:more-vertical-24-regular" class="w-5 h-5 text-gray-400" />
                </button>
                <div class="absolute right-0 top-full mt-1 w-40 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10">
                  <button
                    @click="openMembersModal(department)"
                    class="w-full px-4 py-2.5 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2 first:rounded-t-xl"
                  >
                    <Icon name="fluent:people-24-regular" class="w-4 h-4" />
                    จัดการสมาชิก
                  </button>
                  <button
                    @click="openEditModal(department)"
                    class="w-full px-4 py-2.5 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
                  >
                    <Icon name="fluent:edit-24-regular" class="w-4 h-4" />
                    แก้ไข
                  </button>
                  <button
                    @click="deleteDepartment(department)"
                    class="w-full px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 flex items-center gap-2 last:rounded-b-xl"
                  >
                    <Icon name="fluent:delete-24-regular" class="w-4 h-4" />
                    ลบ
                  </button>
                </div>
              </div>
            </div>

            <p v-if="department.description" class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
              {{ department.description }}
            </p>

            <!-- Head User -->
            <div v-if="department.head_user" class="flex items-center gap-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl">
              <img
                :src="department.head_user.profile_photo_url || '/images/default-avatar.png'"
                :alt="department.head_user.name"
                class="w-8 h-8 rounded-full object-cover"
              />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ department.head_user.name }}</p>
                <p class="text-xs text-amber-600 dark:text-amber-400">หัวหน้าฝ่าย</p>
              </div>
            </div>
            <div v-else class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl text-center">
              <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีหัวหน้าฝ่าย</p>
            </div>
          </div>

          <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <button
              @click="openMembersModal(department)"
              class="text-sm text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
            >
              <Icon name="fluent:people-24-regular" class="w-4 h-4" />
              ดูสมาชิก
            </button>
            <span class="text-xs text-gray-400">
              สร้างเมื่อ {{ new Date(department.created_at).toLocaleDateString('th-TH') }}
            </span>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2">
        <button
          @click="pagination.current_page--; fetchDepartments()"
          :disabled="pagination.current_page === 1"
          class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          ก่อนหน้า
        </button>
        <span class="text-gray-600 dark:text-gray-400">
          หน้า {{ pagination.current_page }} / {{ pagination.last_page }}
        </span>
        <button
          @click="pagination.current_page++; fetchDepartments()"
          :disabled="pagination.current_page === pagination.last_page"
          class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          ถัดไป
        </button>
      </div>
    </div>

    <!-- Create Department Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showCreateModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">สร้างฝ่ายงานใหม่</h3>
            <button @click="showCreateModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <form @submit.prevent="createDepartment" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ชื่อฝ่ายงาน *</label>
              <input
                v-model="departmentForm.name"
                type="text"
                required
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                placeholder="เช่น ฝ่ายวิชาการ, ฝ่ายบุคคล"
              />
              <p v-if="formErrors.name" class="mt-1 text-sm text-red-500">{{ formErrors.name[0] }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">รายละเอียด</label>
              <textarea
                v-model="departmentForm.description"
                rows="3"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                placeholder="รายละเอียดฝ่ายงาน..."
              ></textarea>
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="showCreateModal = false"
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
                <span>{{ isSubmitting ? 'กำลังสร้าง...' : 'สร้างฝ่ายงาน' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Edit Department Modal -->
    <Teleport to="body">
      <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showEditModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">แก้ไขฝ่ายงาน</h3>
            <button @click="showEditModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <form @submit.prevent="updateDepartment" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ชื่อฝ่ายงาน *</label>
              <input
                v-model="departmentForm.name"
                type="text"
                required
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
              <p v-if="formErrors.name" class="mt-1 text-sm text-red-500">{{ formErrors.name[0] }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">รายละเอียด</label>
              <textarea
                v-model="departmentForm.description"
                rows="3"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
              ></textarea>
            </div>
            
            <div class="flex items-center gap-3 pt-4">
              <button
                type="button"
                @click="showEditModal = false"
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

    <!-- Members Modal -->
    <Teleport to="body">
      <div v-if="showMembersModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showMembersModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">สมาชิกฝ่ายงาน</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedDepartment?.name }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="openAddMemberModal"
                class="px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm rounded-lg font-medium transition-colors flex items-center gap-1"
              >
                <Icon name="fluent:add-24-regular" class="w-4 h-4" />
                เพิ่มสมาชิก
              </button>
              <button @click="showMembersModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
              </button>
            </div>
          </div>
          
          <div class="flex-1 overflow-y-auto p-5">
            <div v-if="isLoadingMembers" class="flex items-center justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
            </div>
            
            <div v-else-if="departmentMembers.length === 0" class="text-center py-12">
              <Icon name="fluent:people-24-regular" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
              <p class="text-gray-500 dark:text-gray-400">ยังไม่มีสมาชิกในฝ่ายงานนี้</p>
            </div>
            
            <div v-else class="space-y-3">
              <div
                v-for="member in departmentMembers"
                :key="member.id"
                class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
              >
                <img
                  :src="member.user?.profile_photo_url || '/images/default-avatar.png'"
                  :alt="member.user?.name"
                  class="w-10 h-10 rounded-full object-cover"
                />
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900 dark:text-white truncate">{{ member.user?.name }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ member.user?.email }}</p>
                </div>
                <div class="flex items-center gap-2">
                  <select
                    :value="member.role"
                    @change="updateMemberRole(member.id, ($event.target as HTMLSelectElement).value)"
                    class="text-sm px-3 py-1.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                  >
                    <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
                  </select>
                  <button
                    @click="removeMember(member.id)"
                    class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                  >
                    <Icon name="fluent:delete-24-regular" class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Add Member Modal -->
    <Teleport to="body">
      <div v-if="showAddMemberModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showAddMemberModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[80vh] overflow-hidden flex flex-col">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">เพิ่มสมาชิก</h3>
            <button @click="showAddMemberModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon name="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <div class="p-5 border-b border-gray-200 dark:border-gray-700 space-y-4">
            <div class="relative">
              <Icon name="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="memberSearchQuery"
                type="text"
                placeholder="ค้นหาสมาชิก..."
                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">บทบาทในฝ่ายงาน</label>
              <select
                v-model="memberRole"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              >
                <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
              </select>
            </div>
          </div>
          
          <div class="flex-1 overflow-y-auto p-5">
            <div v-if="filteredAvailableMembers.length === 0" class="text-center py-8">
              <p class="text-gray-500 dark:text-gray-400">ไม่พบสมาชิก</p>
            </div>
            
            <div v-else class="space-y-2">
              <label
                v-for="member in filteredAvailableMembers"
                :key="member.id"
                class="flex items-center gap-4 p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-xl cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="member.user_id"
                  v-model="selectedMemberIds"
                  class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <img
                  :src="member.user?.profile_photo_url || '/images/default-avatar.png'"
                  :alt="member.user?.name"
                  class="w-10 h-10 rounded-full object-cover"
                />
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900 dark:text-white truncate">{{ member.user?.name }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ member.user?.email }}</p>
                </div>
              </label>
            </div>
          </div>
          
          <div class="p-5 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">เลือก {{ selectedMemberIds.length }} คน</p>
            <div class="flex items-center gap-3">
              <button
                @click="showAddMemberModal = false"
                class="px-4 py-2 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                ยกเลิก
              </button>
              <button
                @click="addMembersToDepartment"
                :disabled="selectedMemberIds.length === 0 || isSubmitting"
                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                <span>เพิ่มสมาชิก</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </NuxtLayout>
</template>

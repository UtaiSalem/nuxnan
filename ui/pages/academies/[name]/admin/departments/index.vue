<script setup lang="ts">
/**
 * Academy Admin - Department Management
 * หน้าจัดการแผนกของโรงเรียน
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
const showPermissionsModal = ref(false)
const showSetupModal = ref(false)
const selectedDepartment = ref<any>(null)

// Permission Management
const isLoadingPermissions = ref(false)
const departmentPermissions = ref<string[]>([])
const permissionOptions = [
  { key: 'departments.view', label: 'การมองเห็นแผนก', description: 'อนุญาตให้สมาชิกทั่วไปมองเห็นแผนกนี้' },
  { key: 'departments.manage', label: 'การจัดการข้อมูลแผนก', description: 'อนุญาตให้แก้ไขข้อมูลและลบแผนก' },
  { key: 'departments.manage-members', label: 'การจัดการสมาชิก', description: 'อนุญาตให้เพิ่ม/ลบ และแก้ไขบทบาทสมาชิก' }
]

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
const memberRoleFilter = ref('staff')
const memberResultsPagination = ref({ current_page: 1, last_page: 1, total: 0 })
const isLoadingAvailableMembers = ref(false)
let memberSearchTimer: ReturnType<typeof setTimeout> | null = null

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
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
      query: {
        search: searchQuery.value || undefined,
        page: pagination.value.current_page,
        per_page: pagination.value.per_page
      }
    })
    
    if (response.success) {
      const departmentData = response.data || {}
      departments.value = departmentData.departments || []
      pagination.value.total = departmentData.total || departments.value.length
      pagination.value.last_page = departmentData.last_page || 1
      pagination.value.current_page = departmentData.current_page || pagination.value.current_page
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
      statistics.value = response.data
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

const openDepartmentPage = (departmentId: number) => {
  return navigateTo(`/academies/${academyName.value}/admin/departments/${departmentId}`)
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
    head_user_id: department.head_user_id || department.settings?.head_user_id || null
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
        title: 'สร้างแผนกสำเร็จ',
        text: `สร้างแผนก "${departmentForm.value.name}" เรียบร้อยแล้ว`,
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
        text: err.response?.data?.message || 'ไม่สามารถสร้างแผนกได้'
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
    const response: any = await api.patch(`/api/academies/${academyId.value}/departments/${selectedDepartment.value.id}`, {
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
        text: 'อัปเดตข้อมูลแผนกเรียบร้อยแล้ว',
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
        text: err.response?.data?.message || 'ไม่สามารถอัปเดตแผนกได้'
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
    text: `คุณต้องการลบแผนก "${department.name}" หรือไม่?`,
    showCancelButton: true,
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })
  
  if (result.isConfirmed) {
    try {
      const response: any = await api.delete(`/api/academies/${academyId.value}/departments/${department.id}`)
      
      if (response.success) {
        await fetchDepartments()
        await fetchStatistics()
        
        Swal.fire({
          icon: 'success',
          title: 'ลบสำเร็จ',
          text: 'ลบแผนกเรียบร้อยแล้ว',
          timer: 2000,
          showConfirmButton: false
        })
      }
    } catch (err: any) {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err.response?.data?.message || 'ไม่สามารถลบแผนกได้'
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

// Open permissions modal
const openPermissionsModal = async (department: any) => {
  selectedDepartment.value = department
  showPermissionsModal.value = true
  await fetchDepartmentPermissions(department.id)
}

// Fetch department permissions
const fetchDepartmentPermissions = async (departmentId: number) => {
  if (!academyId.value) return
  isLoadingPermissions.value = true
  try {
    const response: any = await api.get(
      `/api/academies/${academyId.value}/departments/${departmentId}/permissions`
    )
    if (response.success) {
      departmentPermissions.value = response.data.enabled_keys || []
    }
  } catch (err) {
    console.error('Failed to fetch permissions:', err)
  } finally {
    isLoadingPermissions.value = false
  }
}

// Save department permissions
const saveDepartmentPermissions = async () => {
  if (!selectedDepartment.value || !academyId.value) return

  isSubmitting.value = true
  try {
    const response: any = await api.put(
      `/api/academies/${academyId.value}/departments/${selectedDepartment.value.id}/permissions`,
      { permission_keys: departmentPermissions.value }
    )

    if (response.success) {
      showPermissionsModal.value = false
      Swal.fire({
        icon: 'success',
        title: 'บันทึกสำเร็จ',
        text: 'อัปเดตสิทธิ์การใช้งานแผนกเรียบร้อยแล้ว',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err.response?.data?.message || 'ไม่สามารถบันทึกสิทธิ์ได้'
    })
  } finally {
    isSubmitting.value = false
  }
}

// Fetch department members
const fetchDepartmentMembers = async (departmentId: number) => {
  if (!academyId.value) return
  isLoadingMembers.value = true
  try {
    const response: any = await api.get(
      `/api/academies/${academyId.value}/departments/${departmentId}/members`
    )
    if (response.success) {
      const members = response.data?.members || response.members || []
      departmentMembers.value = members.map((member: any) => ({
        ...member,
        user_id: member.user_id || member.id,
        user: member.user || {
          id: member.id,
          name: member.name,
          email: member.email,
          profile_photo_url: member.profile_photo_url || member.avatar
        }
      }))
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
  memberSearchQuery.value = ''
  memberRoleFilter.value = 'staff'
  memberResultsPagination.value = { current_page: 1, last_page: 1, total: 0 }
  await fetchAvailableMembers()
}

// Fetch a server-filtered page of academy members (academy members not in this department)
const fetchAvailableMembers = async (page = 1) => {
  if (!academyId.value) return

  isLoadingAvailableMembers.value = true
  try {
    const query: Record<string, any> = {
      search: memberSearchQuery.value || undefined,
      status: 2,
      page,
      per_page: 25
    }
    if (memberRoleFilter.value === 'staff') query.roles = ['teacher', 'staff']

    const response: any = await api.get(`/api/academies/${academyId.value}/members/search`, {
      query
    })

    if (response.success) {
      const existingMemberIds = departmentMembers.value.map((m: any) => m.user_id || m.id)
      availableMembers.value = (response.members || []).filter(
        (m: any) => !existingMemberIds.includes(m.user_id)
      )
      memberResultsPagination.value = response.pagination || { current_page: page, last_page: page, total: availableMembers.value.length }
    }
  } catch (err) {
    console.error('Failed to fetch available members:', err)
  } finally {
    isLoadingAvailableMembers.value = false
  }
}

const scheduleMemberSearch = () => {
  if (memberSearchTimer) clearTimeout(memberSearchTimer)
  memberSearchTimer = setTimeout(() => fetchAvailableMembers(1), 300)
}

const selectAllMatchingMembers = () => {
  const ids = availableMembers.value.map((member: any) => member.user_id).filter(Boolean)
  selectedMemberIds.value = Array.from(new Set([...selectedMemberIds.value, ...ids]))
}

const clearMemberSelection = () => {
  selectedMemberIds.value = []
}

const changeMemberRoleFilter = () => fetchAvailableMembers(1)

// Add members to department
const addMembersToDepartment = async () => {
  if (!selectedDepartment.value || !academyId.value || selectedMemberIds.value.length === 0) return

  isSubmitting.value = true
  try {
    const response: any = await api.post(
      `/api/academies/${academyId.value}/departments/${selectedDepartment.value.id}/members/bulk`,
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
        text: `เพิ่ม ${selectedMemberIds.value.length} คนเข้าแผนกเรียบร้อยแล้ว`,
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
  if (!selectedDepartment.value || !academyId.value) return

  const result = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันการนำออก',
    text: 'คุณต้องการนำสมาชิกออกจากแผนกนี้หรือไม่?',
    showCancelButton: true,
    confirmButtonText: 'นำออก',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })

  if (result.isConfirmed) {
    try {
      const response: any = await api.delete(
        `/api/academies/${academyId.value}/departments/${selectedDepartment.value.id}/members`,
        { body: { user_id: memberId } }
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
  if (!selectedDepartment.value || !academyId.value) return

  try {
    const response: any = await api.patch(
      `/api/academies/${academyId.value}/departments/${selectedDepartment.value.id}/members/role`,
      { user_id: memberId, role: newRole }
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

const filteredAvailableMembers = computed(() => availableMembers.value)

// Role options
const roleOptions = [
  { value: 'member', label: 'สมาชิก' },
  { value: 'head', label: 'หัวหน้าแผนก' },
  { value: 'staff', label: 'เจ้าหน้าที่' },
  { value: 'admin', label: 'ผู้ดูแล' }
]

const getRoleBadgeClass = (role: string) => {
  switch (role) {
    case 'head': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'
    case 'staff': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'
    case 'admin': return 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300'
    default: return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
  }
}

const getRoleLabel = (role: string) => {
  return roleOptions.find(r => r.value === role)?.label || role
}

const onSetupSuccess = async () => {
  showSetupModal.value = false
  await Promise.all([fetchDepartments(), fetchStatistics()])
}
</script>

<template>
  <NuxtPage v-if="route.params.id" />
  <div v-else>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการแผนก</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการแผนกต่างๆ ของโรงเรียน</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="showSetupModal = true"
            class="inline-flex items-center gap-2 px-4 py-2.5 border border-primary-300 dark:border-primary-700 text-primary-700 dark:text-primary-300 hover:bg-primary-50 dark:hover:bg-primary-900/30 rounded-xl font-medium transition-colors"
          >
            <Icon icon="heroicons:building-office" class="w-5 h-5" />
            <span>โครงสร้างมาตรฐาน</span>
          </button>
          <button
            @click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors"
          >
            <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
            <span>สร้างแผนก</span>
          </button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div v-if="statistics" class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
              <Icon icon="fluent:building-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.total_departments }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">แผนกทั้งหมด</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
              <Icon icon="fluent:people-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
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
              <Icon icon="fluent:person-star-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics.departments_with_head }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">มีหัวหน้าแผนก</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-xl">
              <Icon icon="fluent:chart-multiple-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ (statistics.total_members / (statistics.total_departments || 1)).toFixed(1) }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">เฉลี่ย/แผนก</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Search Bar -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="relative">
          <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="ค้นหาแผนก..."
            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-500 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
        </div>
      </div>

      <!-- Department List -->
      <div v-if="isLoadingDepartments" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
      </div>

      <div v-else-if="departments.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-sm border border-gray-100 dark:border-gray-700">
        <Icon icon="heroicons:building-office" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">ยังไม่มีแผนก</h3>
        <p class="text-gray-500 dark:text-gray-400 mb-6">เริ่มต้นสร้างโครงสร้างฝ่ายงานมาตรฐาน 5 ฝ่ายตามแนวทาง สพฐ. หรือสร้างแผนกเอง</p>
        <div class="flex items-center justify-center gap-3">
          <button
            @click="showSetupModal = true"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors shadow-lg shadow-primary-500/20"
          >
            <Icon icon="heroicons:building-office" class="w-5 h-5" />
            <span>ตั้งค่าโครงสร้างมาตรฐาน</span>
          </button>
          <button
            @click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
            <span>สร้างแผนกเอง</span>
          </button>
        </div>
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
                  <Icon icon="fluent:building-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <NuxtLink
                    :to="`/academies/${academyName}/admin/departments/${department.id}`"
                    @click.stop="openDepartmentPage(department.id)"
                    class="font-semibold text-gray-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                  >
                    {{ department.name }}
                  </NuxtLink>
                  <p class="text-sm text-gray-500 dark:text-gray-400">{{ department.members_count || 0 }} สมาชิก</p>
                </div>
              </div>
              <div class="relative group">
                <button class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                  <Icon icon="fluent:more-vertical-24-regular" class="w-5 h-5 text-gray-400" />
                </button>
                <div class="absolute right-0 top-full mt-1 w-40 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10">
                  <button
                    @click="openMembersModal(department)"
                    class="w-full px-4 py-2.5 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2 first:rounded-t-xl"
                  >
                    <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
                    จัดการสมาชิก
                  </button>
                  <button
                    @click="openPermissionsModal(department)"
                    class="w-full px-4 py-2.5 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
                  >
                    <Icon icon="fluent:shield-lock-24-regular" class="w-4 h-4" />
                    สิทธิ์การใช้งาน
                  </button>
                  <button
                    @click="openEditModal(department)"
                    class="w-full px-4 py-2.5 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
                  >
                    <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
                    แก้ไข
                  </button>
                  <button
                    @click="deleteDepartment(department)"
                    class="w-full px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 flex items-center gap-2 last:rounded-b-xl"
                  >
                    <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
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
                <p class="text-xs text-amber-600 dark:text-amber-400">หัวหน้าแผนก</p>
              </div>
            </div>
            <div v-else class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl text-center">
              <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีหัวหน้าแผนก</p>
            </div>
          </div>

          <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <button
              @click="openMembersModal(department)"
              class="text-sm text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
            >
              <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
              ดูสมาชิก
            </button>
            <NuxtLink
              :to="`/academies/${academyName}/admin/departments/${department.id}`"
              @click.stop="openDepartmentPage(department.id)"
              class="text-sm font-medium text-gray-600 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400"
            >
              ดูรายละเอียด
              <Icon icon="fluent:arrow-right-24-regular" class="ml-1 inline-block h-4 w-4" />
            </NuxtLink>
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
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">สร้างแผนกใหม่</h3>
            <button @click="showCreateModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <form @submit.prevent="createDepartment" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ชื่อแผนก *</label>
              <input
                v-model="departmentForm.name"
                type="text"
                required
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                placeholder="เช่น แผนกวิชาการ, แผนกบุคคล"
              />
              <p v-if="formErrors.name" class="mt-1 text-sm text-red-500">{{ formErrors.name[0] }}</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">รายละเอียด</label>
              <textarea
                v-model="departmentForm.description"
                rows="3"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"
                placeholder="รายละเอียดแผนก..."
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
                <span>{{ isSubmitting ? 'กำลังสร้าง...' : 'สร้างแผนก' }}</span>
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
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">แก้ไขแผนก</h3>
            <button @click="showEditModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <form @submit.prevent="updateDepartment" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ชื่อแผนก *</label>
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
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">สมาชิกแผนก</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedDepartment?.name }}</p>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="openAddMemberModal"
                class="px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm rounded-lg font-medium transition-colors flex items-center gap-1"
              >
                <Icon icon="fluent:add-24-regular" class="w-4 h-4" />
                เพิ่มสมาชิก
              </button>
              <button @click="showMembersModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
              </button>
            </div>
          </div>
          
          <div class="flex-1 overflow-y-auto p-5">
            <div v-if="isLoadingMembers" class="flex items-center justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
            </div>
            
            <div v-else-if="departmentMembers.length === 0" class="text-center py-12">
              <Icon icon="fluent:people-24-regular" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
              <p class="text-gray-500 dark:text-gray-400">ยังไม่มีสมาชิกในแผนกนี้</p>
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
                    <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
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
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <div class="p-5 border-b border-gray-200 dark:border-gray-700 space-y-4">
            <div class="relative">
              <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="memberSearchQuery"
                @input="scheduleMemberSearch"
                type="text"
                placeholder="ค้นหาสมาชิก..."
                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-500"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">กลุ่มสมาชิก</label>
              <select
                v-model="memberRoleFilter"
                @change="changeMemberRoleFilter"
                class="w-full px-4 py-2.5 mb-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              >
                <option value="staff">ครูและเจ้าหน้าที่</option>
                <option value="all">สมาชิกทุกบทบาท</option>
              </select>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">บทบาทในแผนก</label>
              <select
                v-model="memberRole"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white"
              >
                <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
              </select>
            </div>
          </div>
          
          <div class="flex-1 overflow-y-auto p-5">
            <div class="flex items-center justify-between mb-3 text-sm">
              <span class="text-gray-500 dark:text-gray-400">พบ {{ memberResultsPagination.total }} คน · เลือกแล้ว {{ selectedMemberIds.length }} คน</span>
              <div class="flex items-center gap-2">
                <button type="button" @click="selectAllMatchingMembers" class="text-primary-600 hover:text-primary-700 dark:text-primary-400">เลือกทั้งหมดในผลลัพธ์</button>
                <button type="button" @click="clearMemberSelection" class="text-gray-500 hover:text-gray-700 dark:text-gray-400">ล้างการเลือก</button>
              </div>
            </div>
            <div v-if="isLoadingAvailableMembers" class="text-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent mx-auto"></div>
            </div>
            <div v-else-if="filteredAvailableMembers.length === 0" class="text-center py-8">
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
          <div v-if="memberResultsPagination.last_page > 1" class="px-5 pb-3 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <button type="button" :disabled="memberResultsPagination.current_page <= 1" @click="fetchAvailableMembers(memberResultsPagination.current_page - 1)" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-40">ก่อนหน้า</button>
            <span>หน้า {{ memberResultsPagination.current_page }} / {{ memberResultsPagination.last_page }}</span>
            <button type="button" :disabled="memberResultsPagination.current_page >= memberResultsPagination.last_page" @click="fetchAvailableMembers(memberResultsPagination.current_page + 1)" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 disabled:opacity-40">ถัดไป</button>
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

    <!-- Department Setup Modal -->
    <SchoolDepartmentSetupModal
      :visible="showSetupModal"
      :academy-id="academyId"
      @close="showSetupModal = false"
      @success="onSetupSuccess"
    />

    <!-- Permissions Modal -->
    <Teleport to="body">
      <div v-if="showPermissionsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showPermissionsModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md overflow-hidden flex flex-col">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ตั้งค่าสิทธิ์การใช้งาน</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedDepartment?.name }}</p>
            </div>
            <button @click="showPermissionsModal = false" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <div class="p-5 flex-1 overflow-y-auto">
            <div v-if="isLoadingPermissions" class="flex items-center justify-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
            </div>
            
            <div v-else class="space-y-4">
              <div
                v-for="permission in permissionOptions"
                :key="permission.key"
                class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl"
              >
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-900 dark:text-white">{{ permission.label }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ permission.description }}</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                  <input
                    type="checkbox"
                    :value="permission.key"
                    v-model="departmentPermissions"
                    class="sr-only peer"
                  />
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                </label>
              </div>
            </div>
          </div>
          
          <div class="p-5 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3 bg-gray-50 dark:bg-gray-800/50">
            <button
              @click="showPermissionsModal = false"
              class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            >
              ยกเลิก
            </button>
            <button
              @click="saveDepartmentPermissions"
              :disabled="isSubmitting"
              class="flex-1 px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-colors shadow-lg shadow-primary-500/20"
            >
              <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
              <span>บันทึกตั้งค่า</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
  </div>
</template>

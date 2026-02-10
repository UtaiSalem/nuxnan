<script setup lang="ts">
/**
 * Academy Admin - Members Management
 * หน้าจัดการสมาชิกของโรงเรียน
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
const members = ref<any[]>([])
const roles = ref<any[]>([])
const tags = ref<any[]>([])
const isLoading = ref(true)
const isLoadingMembers = ref(false)

// Error state for graceful error handling
const error = ref<string | null>(null)
const hasError = ref(false)
const retryCount = ref(0)

// Filter Options from API
const filterOptions = ref<{
  class_levels: { value: string; label: string }[]
  class_sections: { value: string; label: string }[]
  classrooms: { class_level: string; class_section: string; label: string; count: number }[]
  genders: { value: number; label: string; count: number }[]
}>({
  class_levels: [],
  class_sections: [],
  classrooms: [],
  genders: []
})

// Filters
const searchQuery = ref('')
const selectedStatus = ref<number | null>(null)
const selectedRole = ref<string | null>(null)
const selectedTag = ref<number | null>(null)
const selectedClassLevel = ref<string | null>(null)
const selectedClassSection = ref<string | null>(null)
const selectedGender = ref<number | null>(null)
const selectedMemberType = ref<string | null>(null)
const sortBy = ref('created_at')
const sortOrder = ref<'asc' | 'desc'>('desc')
const viewMode = ref<'card' | 'table'>('card')
const groupBy = ref<'none' | 'classroom' | 'class_level' | 'gender'>('none')

// Persist view mode preference
const savedViewMode = useCookie<'card' | 'table'>('academy-members-view-mode')
onMounted(() => {
  if (savedViewMode.value) {
    viewMode.value = savedViewMode.value
  }
})
watch(viewMode, (val) => {
  savedViewMode.value = val
})

// Pagination
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0
})

// Modals
const showRoleModal = ref(false)
const showManageModal = ref(false)
const showInviteModal = ref(false)
const showBulkRoleModal = ref(false)
const showAdvancedFilter = ref(false)
const selectedMember = ref<any>(null)

// Bulk Selection
const selectedMemberIds = ref<number[]>([])
const isBulkProcessing = ref(false)

// Advanced Filters
const advancedFilters = ref({
  dateFrom: null as string | null,
  dateTo: null as string | null,
  roleId: null as number | null,
})

const activeFiltersCount = computed(() => {
  let count = 0
  if (advancedFilters.value.dateFrom) count++
  if (advancedFilters.value.dateTo) count++
  if (advancedFilters.value.roleId) count++
  if (selectedClassLevel.value) count++
  if (selectedClassSection.value) count++
  if (selectedGender.value !== null) count++
  if (searchQuery.value) count++
  if (selectedMemberType.value) count++
  return count
})

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Stats
const stats = ref({
  total: 0,
  approved: 0,
  pending: 0,
  invited: 0,
  rejected: 0,
  suspended: 0
})

const statusOptions = [
  { value: null, label: 'ทุกสถานะ' },
  { value: 2, label: 'สมาชิก' },
  { value: 1, label: 'รอการอนุมัติ' },
  { value: 4, label: 'ได้รับเชิญ' },
  { value: 5, label: 'ถูกระงับ' },
  { value: 3, label: 'ถูกปฏิเสธ' },
]

const roleOptions = computed(() => [
  { value: null, label: 'ทุกบทบาท' },
  ...roles.value.map(r => ({ value: r.name, label: r.display_name_th }))
])

const tagOptions = computed(() => [
  { value: null, label: 'ทุกแท็ก' },
  ...tags.value.map(t => ({ value: t.id, label: t.name, color: t.color }))
])

const classLevelOptions = computed(() => [
  { value: null, label: 'ทุกชั้น' },
  ...filterOptions.value.class_levels
])

const classSectionOptions = computed(() => [
  { value: null, label: 'ทุกห้อง' },
  ...filterOptions.value.class_sections
])

const genderOptions = computed(() => [
  { value: null, label: 'ทุกเพศ' },
  ...filterOptions.value.genders.map(g => ({
    value: g.value,
    label: `${g.label} (${g.count})`
  }))
])

const memberTypeOptions = [
  { value: null, label: 'ทุกประเภท' },
  { value: 'student', label: 'นักเรียน' },
  { value: 'user', label: 'ผู้ใช้ทั่วไป' },
]

// Grouped members for display
const groupedMembers = computed(() => {
  if (groupBy.value === 'none') {
    return [{ key: 'all', label: 'ทั้งหมด', members: members.value }]
  }
  
  const groups: Record<string, { key: string; label: string; members: any[] }> = {}
  
  for (const member of members.value) {
    let key = 'unknown'
    let label = 'ไม่ระบุ'
    
    if (groupBy.value === 'classroom') {
      const level = member.student?.class_level || ''
      const section = member.student?.class_section || ''
      if (level || section) {
        key = `${level}-${section}`
        label = level + (section ? `/${section}` : '')
      } else {
        key = 'no-class'
        label = 'ยังไม่กำหนดชั้น/ห้อง'
      }
    } else if (groupBy.value === 'class_level') {
      const level = member.student?.class_level
      if (level) {
        key = level
        label = level
      } else {
        key = 'no-level'
        label = 'ยังไม่กำหนดชั้น'
      }
    } else if (groupBy.value === 'gender') {
      const gender = member.student?.gender
      if (gender === 1) {
        key = 'male'
        label = 'ชาย'
      } else if (gender === 0) {
        key = 'female'
        label = 'หญิง'
      } else {
        key = 'unknown'
        label = 'ไม่ระบุ'
      }
    }
    
    if (!groups[key]) {
      groups[key] = { key, label, members: [] }
    }
    groups[key].members.push(member)
  }
  
  return Object.values(groups).sort((a, b) => a.label.localeCompare(b.label, 'th'))
})

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!can('members.view')) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }
      
      // Check if student filter cookie exists (from /students page)
      const studentFilter = useCookie('academy-members-student-filter')
      if (studentFilter.value === 'student') {
        selectedMemberType.value = 'student'
        // Clear the cookie after applying the filter
        studentFilter.value = null
      }
      
      // Make API calls more resilient - if one fails, the others should still work
      // fetchMembers and fetchStats are critical, fetchTags and fetchFilterOptions are secondary
      const criticalCalls = [fetchMembers(), fetchStats()]
      const secondaryCalls = [fetchTags(), fetchFilterOptions()]
      
      // Wait for critical calls to complete
      await Promise.allSettled(criticalCalls)
      
      // Secondary calls can fail without breaking the page
      await Promise.allSettled(secondaryCalls)
      
      // Check if critical data loaded successfully
      if (members.value.length === 0 && !isLoadingMembers.value) {
        // Members failed to load, set error state
        hasError.value = true
        error.value = 'Failed to load members data. Please try again.'
      }
    }
  } catch (err) {
    // Set error state for user-friendly feedback
    hasError.value = true
    error.value = 'Failed to load data. Please try again.'
    // Note: useApi.ts already logs the error, so we don't need to log again
  } finally {
    isLoading.value = false
  }
})

const fetchMembers = async (page = 1) => {
  if (!academyId.value) return
  
  isLoadingMembers.value = true
  try {
    const params = new URLSearchParams()
    params.append('page', String(page))
    params.append('per_page', String(pagination.value.per_page))
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (selectedStatus.value !== null) params.append('status', String(selectedStatus.value))
    if (selectedRole.value) params.append('role', selectedRole.value)
    if (selectedTag.value !== null) params.append('tag_id', String(selectedTag.value))
    if (selectedClassLevel.value) params.append('class_level', selectedClassLevel.value)
    if (selectedClassSection.value) params.append('class_section', selectedClassSection.value)
    if (selectedGender.value !== null) params.append('gender', String(selectedGender.value))
    if (selectedMemberType.value) params.append('member_type', selectedMemberType.value)
    params.append('sort_by', sortBy.value)
    params.append('sort_order', sortOrder.value)

    const response: any = await api.get(`/api/academies/${academyId.value}/members/search?${params}`)
    if (response.success) {
      members.value = response.members || []
      pagination.value = response.pagination || pagination.value
      // Clear error state on successful fetch
      if (hasError.value) {
        hasError.value = false
        error.value = null
      }
    }
  } catch (err) {
    // Set error state for user-friendly feedback
    hasError.value = true
    error.value = 'Failed to load members. Please try again.'
    // Note: useApi.ts already logs the error, so we don't need to log again
  } finally {
    isLoadingMembers.value = false
  }
}

const fetchRoles = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/roles/available`)
    if (response.success) {
      roles.value = response.roles || []
    }
  } catch (err) {
    console.error('Failed to fetch roles:', err)
  }
}

const fetchStats = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/members/stats`)
    if (response.success) {
      stats.value = response.stats
    }
  } catch (err) {
    console.error('Failed to fetch stats:', err)
  }
}

const fetchTags = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/member-tags`)
    if (response.success) {
      tags.value = response.tags || []
    }
  } catch (err) {
    // Tags are secondary data - failure shouldn't break the main page
    // Just log a warning, don't set error state
    // Note: useApi.ts already logs the error, so we don't need to log again
  }
}

const fetchFilterOptions = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/members/filter-options`)
    if (response.success && response.filters) {
      filterOptions.value = response.filters
    }
  } catch (err) {
    // Filter options are secondary data - failure shouldn't break the main page
    // Just log a warning, don't set error state
    // Note: useApi.ts already logs the error, so we don't need to log again
  }
}

// Retry function to retry failed API calls
const retryFetch = async () => {
  // Clear error state before retrying
  hasError.value = false
  error.value = null
  retryCount.value++
  
  // Retry critical API calls
  await Promise.allSettled([
    fetchMembers(),
    fetchStats(),
  ])
  
  // Retry secondary API calls
  await Promise.allSettled([
    fetchTags(),
    fetchFilterOptions(),
  ])
}

// Actions
const openRoleModal = (member: any) => {
  selectedMember.value = member
  showRoleModal.value = true
}

const openManageModal = (member: any) => {
  selectedMember.value = member
  showManageModal.value = true
}

const acceptMember = async (member: any) => {
  const result = await Swal.fire({
    title: 'ยืนยันการอนุมัติ',
    text: `อนุมัติ ${member.member_name} เป็นสมาชิก?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'อนุมัติ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#10b981'
  })

  if (result.isConfirmed) {
    try {
      await api.post(`/api/academies/${academyId.value}/members/${member.id}/accept`)
      await fetchMembers(pagination.value.current_page)
      await fetchStats()
      Swal.fire('สำเร็จ', 'อนุมัติสมาชิกเรียบร้อยแล้ว', 'success')
    } catch (err) {
      Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถอนุมัติได้', 'error')
    }
  }
}

const rejectMember = async (member: any) => {
  const result = await Swal.fire({
    title: 'ยืนยันการปฏิเสธ',
    text: `ปฏิเสธคำขอของ ${member.member_name}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ปฏิเสธ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })

  if (result.isConfirmed) {
    try {
      await api.post(`/api/academies/${academyId.value}/members/${member.id}/reject`)
      await fetchMembers(pagination.value.current_page)
      await fetchStats()
      Swal.fire('สำเร็จ', 'ปฏิเสธคำขอเรียบร้อยแล้ว', 'success')
    } catch (err) {
      Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถปฏิเสธได้', 'error')
    }
  }
}

const removeMember = async (member: any) => {
  const result = await Swal.fire({
    title: 'ยืนยันการลบสมาชิก',
    text: `ลบ ${member.member_name} ออกจากโรงเรียน? การกระทำนี้ไม่สามารถย้อนกลับได้`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ลบสมาชิก',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })

  if (result.isConfirmed) {
    try {
      await api.delete(`/api/academies/${academyId.value}/members/${member.id}`)
      await fetchMembers(pagination.value.current_page)
      await fetchStats()
      Swal.fire('สำเร็จ', 'ลบสมาชิกเรียบร้อยแล้ว', 'success')
    } catch (err: any) {
      Swal.fire('เกิดข้อผิดพลาด', err.data?.message || 'ไม่สามารถลบได้', 'error')
    }
  }
}

const onRoleAssigned = async () => {
  showRoleModal.value = false
  await fetchMembers(pagination.value.current_page)
}

const onMemberUpdated = async () => {
  showManageModal.value = false
  await fetchMembers(pagination.value.current_page)
  await fetchStats()
}

const onSearch = () => {
  pagination.value.current_page = 1
  fetchMembers(1)
}

// Advanced Filter handlers
const applyAdvancedFilters = (filters: any) => {
  advancedFilters.value = {
    dateFrom: filters.dateFrom,
    dateTo: filters.dateTo,
    roleId: filters.roleId,
  }
  
  // Update sort options
  if (filters.sortBy) sortBy.value = filters.sortBy
  if (filters.sortOrder) sortOrder.value = filters.sortOrder
  
  // Update status if specified
  if (filters.status !== undefined) selectedStatus.value = filters.status
  
  onSearch()
}

const resetAdvancedFilters = () => {
  advancedFilters.value = {
    dateFrom: null,
    dateTo: null,
    roleId: null,
  }
  selectedStatus.value = null
  selectedRole.value = null
  selectedClassLevel.value = null
  selectedClassSection.value = null
  selectedGender.value = null
  selectedMemberType.value = null
  sortBy.value = 'created_at'
  sortOrder.value = 'desc'
  groupBy.value = 'none'
  onSearch()
}

const clearAllFilters = () => {
  searchQuery.value = ''
  resetAdvancedFilters()
}

// ============================================
// Bulk Actions
// ============================================
const clearBulkSelection = () => {
  selectedMemberIds.value = []
}

const bulkApprove = async () => {
  const result = await Swal.fire({
    title: 'ยืนยันการอนุมัติ',
    text: `อนุมัติสมาชิก ${selectedMemberIds.value.length} คน?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'อนุมัติทั้งหมด',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#10b981'
  })

  if (result.isConfirmed) {
    isBulkProcessing.value = true
    try {
      const response: any = await api.post(`/api/academies/${academyId.value}/members/bulk-action`, {
        member_ids: selectedMemberIds.value,
        action: 'approve'
      })
      if (response.success) {
        Swal.fire('สำเร็จ', response.message, 'success')
        clearBulkSelection()
        await fetchMembers(pagination.value.current_page)
        await fetchStats()
      }
    } catch (err: any) {
      Swal.fire('เกิดข้อผิดพลาด', err.data?.message || 'ไม่สามารถอนุมัติได้', 'error')
    } finally {
      isBulkProcessing.value = false
    }
  }
}

const bulkReject = async () => {
  const result = await Swal.fire({
    title: 'ยืนยันการปฏิเสธ',
    text: `ปฏิเสธสมาชิก ${selectedMemberIds.value.length} คน?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ปฏิเสธทั้งหมด',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })

  if (result.isConfirmed) {
    isBulkProcessing.value = true
    try {
      const response: any = await api.post(`/api/academies/${academyId.value}/members/bulk-action`, {
        member_ids: selectedMemberIds.value,
        action: 'reject'
      })
      if (response.success) {
        Swal.fire('สำเร็จ', response.message, 'success')
        clearBulkSelection()
        await fetchMembers(pagination.value.current_page)
        await fetchStats()
      }
    } catch (err: any) {
      Swal.fire('เกิดข้อผิดพลาด', err.data?.message || 'ไม่สามารถปฏิเสธได้', 'error')
    } finally {
      isBulkProcessing.value = false
    }
  }
}

const bulkSuspend = async () => {
  const result = await Swal.fire({
    title: 'ยืนยันการระงับ',
    text: `ระงับสมาชิก ${selectedMemberIds.value.length} คน?`,
    icon: 'warning',
    input: 'textarea',
    inputLabel: 'เหตุผล (ไม่บังคับ)',
    inputPlaceholder: 'ระบุเหตุผลในการระงับ...',
    showCancelButton: true,
    confirmButtonText: 'ระงับทั้งหมด',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#f97316'
  })

  if (result.isConfirmed) {
    isBulkProcessing.value = true
    try {
      const response: any = await api.post(`/api/academies/${academyId.value}/members/bulk-action`, {
        member_ids: selectedMemberIds.value,
        action: 'suspend',
        reason: result.value || ''
      })
      if (response.success) {
        Swal.fire('สำเร็จ', response.message, 'success')
        clearBulkSelection()
        await fetchMembers(pagination.value.current_page)
        await fetchStats()
      }
    } catch (err: any) {
      Swal.fire('เกิดข้อผิดพลาด', err.data?.message || 'ไม่สามารถระงับได้', 'error')
    } finally {
      isBulkProcessing.value = false
    }
  }
}

const bulkRemove = async () => {
  const result = await Swal.fire({
    title: 'ยืนยันการลบ',
    text: `ลบสมาชิก ${selectedMemberIds.value.length} คน? การกระทำนี้ไม่สามารถย้อนกลับได้`,
    icon: 'error',
    showCancelButton: true,
    confirmButtonText: 'ลบทั้งหมด',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#ef4444'
  })

  if (result.isConfirmed) {
    isBulkProcessing.value = true
    try {
      const response: any = await api.post(`/api/academies/${academyId.value}/members/bulk-action`, {
        member_ids: selectedMemberIds.value,
        action: 'remove'
      })
      if (response.success) {
        Swal.fire('สำเร็จ', response.message, 'success')
        clearBulkSelection()
        await fetchMembers(pagination.value.current_page)
        await fetchStats()
      }
    } catch (err: any) {
      Swal.fire('เกิดข้อผิดพลาด', err.data?.message || 'ไม่สามารถลบได้', 'error')
    } finally {
      isBulkProcessing.value = false
    }
  }
}

const bulkExport = async () => {
  isBulkProcessing.value = true
  try {
    const response = await api.post(`/api/academies/${academyId.value}/members/export-selected`, {
      member_ids: selectedMemberIds.value
    }, { responseType: 'blob' })
    
    // Create download link
    const blob = new Blob([response as any], { type: 'text/csv;charset=utf-8' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `academy_members_${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    
    Swal.fire('สำเร็จ', 'ส่งออกข้อมูลเรียบร้อยแล้ว', 'success')
  } catch (err: any) {
    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถส่งออกข้อมูลได้', 'error')
  } finally {
    isBulkProcessing.value = false
  }
}

const onBulkRoleAssigned = async () => {
  showBulkRoleModal.value = false
  clearBulkSelection()
  await fetchMembers(pagination.value.current_page)
}

const exportAllMembers = async () => {
  try {
    // Use the existing export API
    window.open(`/api/academies/${academyId.value}/members/export`, '_blank')
  } catch (err: any) {
    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถส่งออกข้อมูลได้', 'error')
  }
}

const getStatusBadge = (status: number) => {
  const badges: Record<number, { label: string; class: string }> = {
    1: { label: 'รอการอนุมัติ', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' },
    2: { label: 'สมาชิก', class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' },
    3: { label: 'ถูกปฏิเสธ', class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' },
    4: { label: 'ได้รับเชิญ', class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' },
    5: { label: 'ถูกระงับ', class: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' },
  }
  return badges[status] || { label: 'ไม่ทราบ', class: 'bg-gray-100 text-gray-800' }
}

const getRoleBadge = (member: any) => {
  if (member.academy_role) {
    return {
      label: member.academy_role.display_name_th,
      color: member.academy_role.color,
      icon: member.academy_role.icon
    }
  }
  return {
    label: member.role_display_name || 'สมาชิก',
    color: 'gray',
    icon: 'fluent:person-24-regular'
  }
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <!-- Error State UI -->
    <div v-else-if="hasError" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 py-16 text-center px-4">
      <Icon icon="fluent:error-circle-24-regular" class="w-16 h-16 mx-auto text-red-500 mb-4" />
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
        เกิดข้อผิดพลาด
      </h2>
      <p class="text-gray-600 dark:text-gray-400 mb-6">
        {{ error || 'Failed to load data. Please try again.' }}
      </p>
      <button
        @click="retryFetch"
        :disabled="isLoadingMembers"
        class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 mx-auto"
      >
        <Icon icon="fluent:arrow-clockwise-24-regular" class="w-5 h-5" />
        <span v-if="isLoadingMembers">กำลังลองใหม่...</span>
        <span v-else>ลองใหม่</span>
      </button>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ selectedMemberType === 'student' ? 'รายการนักเรียน' : 'จัดการสมาชิก' }}
          </h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">
            {{ selectedMemberType === 'student' ? 'จัดการข้อมูลนักเรียนทั้งหมดของโรงเรียน' : 'จัดการสมาชิกทั้งหมดของโรงเรียน' }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <!-- Export Button -->
          <button 
            v-if="can('members.manage')"
            @click="exportAllMembers"
            class="px-3 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-2"
            title="ส่งออกข้อมูลสมาชิก"
          >
            <Icon icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
            <span class="hidden sm:inline">ส่งออก CSV</span>
          </button>
          <NuxtLink 
            :to="`/academies/${academyName}/admin/roles`"
            class="px-3 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-2"
          >
            <Icon icon="fluent:shield-person-24-regular" class="w-5 h-5" />
            <span class="hidden sm:inline">จัดการบทบาท</span>
          </NuxtLink>
          <button 
            v-if="can('members.manage')"
            @click="showInviteModal = true"
            class="px-3 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors flex items-center gap-2"
          >
            <Icon icon="fluent:person-add-24-regular" class="w-5 h-5" />
            <span class="hidden sm:inline">เชิญสมาชิก</span>
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div 
          @click="selectedStatus = null; onSearch()"
          class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow"
          :class="{ 'ring-2 ring-primary-500': selectedStatus === null }"
        >
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
          <p class="text-sm text-gray-500">ทั้งหมด</p>
        </div>
        <div 
          @click="selectedStatus = 2; onSearch()"
          class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow"
          :class="{ 'ring-2 ring-green-500': selectedStatus === 2 }"
        >
          <p class="text-2xl font-bold text-green-600">{{ stats.approved }}</p>
          <p class="text-sm text-gray-500">สมาชิก</p>
        </div>
        <div 
          @click="selectedStatus = 1; onSearch()"
          class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow"
          :class="{ 'ring-2 ring-yellow-500': selectedStatus === 1 }"
        >
          <p class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</p>
          <p class="text-sm text-gray-500">รอการอนุมัติ</p>
        </div>
        <div 
          @click="selectedStatus = 4; onSearch()"
          class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow"
          :class="{ 'ring-2 ring-blue-500': selectedStatus === 4 }"
        >
          <p class="text-2xl font-bold text-blue-600">{{ stats.invited }}</p>
          <p class="text-sm text-gray-500">ได้รับเชิญ</p>
        </div>
        <div 
          @click="selectedStatus = 5; onSearch()"
          class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow"
          :class="{ 'ring-2 ring-orange-500': selectedStatus === 5 }"
        >
          <p class="text-2xl font-bold text-orange-600">{{ stats.suspended }}</p>
          <p class="text-sm text-gray-500">ถูกระงับ</p>
        </div>
        <div 
          @click="selectedStatus = 3; onSearch()"
          class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 cursor-pointer hover:shadow-md transition-shadow"
          :class="{ 'ring-2 ring-red-500': selectedStatus === 3 }"
        >
          <p class="text-2xl font-bold text-red-600">{{ stats.rejected }}</p>
          <p class="text-sm text-gray-500">ถูกปฏิเสธ</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
        <!-- Main Search Row -->
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <div class="relative">
              <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="searchQuery"
                @input="onSearch"
                type="text"
                placeholder="ค้นหาชื่อ, อีเมล, รหัสนักเรียน..."
                class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>
          </div>
          
          <!-- View Mode Toggle -->
          <div class="flex bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
            <button 
              @click="viewMode = 'card'"
              class="p-2 rounded-md transition-all"
              :class="viewMode === 'card' ? 'bg-white dark:bg-gray-600 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
              title="มุมมองการ์ด"
            >
              <Icon icon="fluent:grid-24-regular" class="w-5 h-5" />
            </button>
            <button 
              @click="viewMode = 'table'"
              class="p-2 rounded-md transition-all"
              :class="viewMode === 'table' ? 'bg-white dark:bg-gray-600 text-primary-600 dark:text-primary-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
              title="มุมมองตาราง"
            >
              <Icon icon="fluent:table-24-regular" class="w-5 h-5" />
            </button>
          </div>
        </div>
        
        <!-- Filter Row -->
        <div class="flex flex-wrap gap-3 items-center">
          <!-- Status Filter -->
          <select
            v-model="selectedStatus"
            @change="onSearch"
            class="px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
          >
            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          
          <!-- Role Filter -->
          <select
            v-model="selectedRole"
            @change="onSearch"
            class="px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
          >
            <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          
          <!-- Class Level Filter -->
          <select
            v-if="filterOptions.class_levels.length > 0"
            v-model="selectedClassLevel"
            @change="onSearch"
            class="px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
          >
            <option v-for="opt in classLevelOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          
          <!-- Class Section Filter -->
          <select
            v-if="filterOptions.class_sections.length > 0"
            v-model="selectedClassSection"
            @change="onSearch"
            class="px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
          >
            <option v-for="opt in classSectionOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          
          <!-- Gender Filter -->
          <select
            v-if="filterOptions.genders.some(g => g.count > 0)"
            v-model="selectedGender"
            @change="onSearch"
            class="px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
          >
            <option v-for="opt in genderOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          
          <!-- Tag Filter -->
          <select
            v-if="tags.length > 0"
            v-model="selectedTag"
            @change="onSearch"
            class="px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
          >
            <option v-for="opt in tagOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          
          <!-- Group By -->
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-500">จัดกลุ่ม:</span>
            <select
              v-model="groupBy"
              class="px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
            >
              <option value="none">ไม่จัดกลุ่ม</option>
              <option value="classroom">ตามห้องเรียน</option>
              <option value="class_level">ตามชั้นเรียน</option>
              <option value="gender">ตามเพศ</option>
            </select>
          </div>
          
          <!-- Clear Filters -->
          <button
            v-if="activeFiltersCount > 0"
            @click="clearAllFilters"
            class="flex items-center gap-1 px-3 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm transition-colors"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
            ล้างตัวกรอง ({{ activeFiltersCount }})
          </button>
          
          <!-- Student Filter Indicator -->
          <div
            v-if="selectedMemberType === 'student'"
            class="flex items-center gap-2 px-3 py-2 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 rounded-lg text-sm"
          >
            <Icon icon="fluent:person-student-24-regular" class="w-4 h-4" />
            <span>แสดงเฉพาะนักเรียน</span>
            <button
              @click="selectedMemberType = null; onSearch()"
              class="ml-1 hover:bg-primary-100 dark:hover:bg-primary-800/50 rounded p-0.5 transition-colors"
              title="แสดงสมาชิกทั้งหมด"
            >
              <Icon icon="fluent:dismiss-24-regular" class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
        
        <!-- Classroom Quick Filters -->
        <div v-if="filterOptions.classrooms.length > 0 && filterOptions.classrooms.length <= 20" class="flex flex-wrap gap-2">
          <button
            v-for="classroom in filterOptions.classrooms"
            :key="`${classroom.class_level}-${classroom.class_section}`"
            @click="selectedClassLevel = classroom.class_level; selectedClassSection = classroom.class_section; onSearch()"
            :class="[
              'px-3 py-1.5 rounded-full text-sm font-medium transition-colors',
              selectedClassLevel === classroom.class_level && selectedClassSection === classroom.class_section
                ? 'bg-primary-500 text-white'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
            ]"
          >
            {{ classroom.label }} <span class="opacity-70">({{ classroom.count }})</span>
          </button>
        </div>
      </div>

      <!-- Members List -->
      <div>
        <div v-if="isLoadingMembers" class="flex items-center justify-center py-16">
          <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
        </div>

        <div v-else-if="members.length === 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 py-16 text-center">
          <Icon icon="fluent:people-24-regular" class="w-12 h-12 mx-auto text-gray-400 mb-4" />
          <p class="text-gray-500 dark:text-gray-400">ไม่พบสมาชิก</p>
          <button 
            v-if="activeFiltersCount > 0"
            @click="clearAllFilters"
            class="mt-3 text-primary-600 hover:text-primary-700 text-sm font-medium"
          >
            ล้างตัวกรองทั้งหมด
          </button>
        </div>

        <!-- Grouped View -->
        <div v-else-if="groupBy !== 'none'" class="space-y-6">
          <div 
            v-for="group in groupedMembers" 
            :key="group.key"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
          >
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
              <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <Icon 
                  :icon="groupBy === 'gender' ? (group.key === 'male' ? 'fluent:person-24-filled' : 'fluent:person-24-filled') : 'fluent:class-24-regular'" 
                  class="w-5 h-5"
                  :class="groupBy === 'gender' ? (group.key === 'male' ? 'text-blue-500' : 'text-pink-500') : 'text-gray-500'"
                />
                {{ group.label }}
              </h3>
              <span class="px-2 py-1 bg-gray-200 dark:bg-gray-600 rounded-full text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ group.members.length }} คน
              </span>
            </div>
            <AcademyMemberListView
              :members="group.members"
              :view-mode="viewMode"
              :is-admin="can('members.manage')"
              :show-checkbox="can('members.manage')"
              v-model:selected-ids="selectedMemberIds"
              @accept-member="acceptMember"
              @reject-member="rejectMember"
              @remove-member="removeMember"
              @edit-role="openRoleModal"
              @manage-member="openManageModal"
            />
          </div>
        </div>

        <!-- Non-Grouped View -->
        <AcademyMemberListView
          v-else
          :members="members"
          :view-mode="viewMode"
          :is-admin="can('members.manage')"
          :show-checkbox="can('members.manage')"
          v-model:selected-ids="selectedMemberIds"
          @accept-member="acceptMember"
          @reject-member="rejectMember"
          @remove-member="removeMember"
          @edit-role="openRoleModal"
          @manage-member="openManageModal"
        />

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="flex items-center justify-between px-6 py-4 mt-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            แสดง {{ members.length }} จาก {{ pagination.total }} รายการ
          </p>
          <div class="flex items-center gap-2">
            <button
              @click="fetchMembers(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              ก่อนหน้า
            </button>
            <span class="px-3 py-1.5 text-sm">
              หน้า {{ pagination.current_page }} / {{ pagination.last_page }}
            </span>
            <button
              @click="fetchMembers(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              ถัดไป
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Role Modal -->
    <AcademyMemberRoleModal
      v-model="showRoleModal"
      :member="selectedMember"
      :academy-id="academyId!"
      @role-assigned="onRoleAssigned"
    />

    <!-- Manage Modal -->
    <AcademyMemberManageModal
      v-model="showManageModal"
      :member="selectedMember"
      :academy-id="academyId!"
      @member-updated="onMemberUpdated"
      @member-removed="onMemberUpdated"
      @member-suspended="onMemberUpdated"
    />

    <!-- Invite Modal -->
    <LazyAcademyInviteMemberModal
      v-model="showInviteModal"
      :academy-id="academyId!"
      @invited="fetchMembers(1); fetchStats()"
    />

    <!-- Bulk Role Modal -->
    <AcademyMemberBulkRoleModal
      v-model="showBulkRoleModal"
      :academy-id="academyId!"
      :member-ids="selectedMemberIds"
      @role-assigned="onBulkRoleAssigned"
    />

    <!-- Advanced Filter Modal -->
    <AcademyMemberAdvancedFilterModal
      v-model="showAdvancedFilter"
      :roles="roles"
      :current-filters="{
        status: selectedStatus,
        roleId: advancedFilters.roleId,
        dateFrom: advancedFilters.dateFrom,
        dateTo: advancedFilters.dateTo,
        sortBy: sortBy,
        sortOrder: sortOrder
      }"
      @apply="applyAdvancedFilters"
      @reset="resetAdvancedFilters"
    />

    <!-- Bulk Action Bar -->
    <AcademyMemberBulkActionBar
      v-if="can('members.manage')"
      :count="selectedMemberIds.length"
      :is-processing="isBulkProcessing"
      @approve="bulkApprove"
      @reject="bulkReject"
      @suspend="bulkSuspend"
      @remove="bulkRemove"
      @assign-role="showBulkRoleModal = true"
      @export="bulkExport"
      @clear="clearBulkSelection"
    />
  </div>
</template>

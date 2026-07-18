<script setup lang="ts">
/**
 * Academy Admin - Members Management
 * หน้าจัดการสมาชิกของโรงเรียน
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
const selectedRole = ref<number | null>(null)
const selectedTag = ref<number | null>(null)
const selectedClassLevel = ref<string | null>(null)
const selectedClassSection = ref<string | null>(null)
const selectedClassroomKey = ref<string | null>(null)
const selectedGender = ref<number | null>(null)
const selectedMemberType = ref<string | null>(null)
const sortBy = ref('created_at')
const sortOrder = ref<'asc' | 'desc'>('desc')
const viewMode = ref<'card' | 'table'>('card')
const groupBy = ref<'none' | 'classroom' | 'class_level' | 'gender'>('none')

// Mobile detection for view mode
const isMobile = ref(false)
const updateMobileDetection = () => {
  isMobile.value = window.innerWidth < 768 // md breakpoint
}

// Auto-switch to card view on mobile
const effectiveViewMode = computed(() => {
  return isMobile.value ? 'card' : viewMode.value
})

// Persist view mode preference (only for desktop)
const savedViewMode = useCookie<'card' | 'table'>('academy-members-view-mode')
onMounted(() => {
  updateMobileDetection()
  if (savedViewMode.value && !isMobile.value) {
    viewMode.value = savedViewMode.value
  }
})
watch(viewMode, (val) => {
  if (!isMobile.value) {
    savedViewMode.value = val
  }
})

// Watch for mobile changes
watch(isMobile, (mobile) => {
  if (mobile && viewMode.value !== 'card') {
    // Force card view on mobile
    viewMode.value = 'card'
  } else if (!mobile && savedViewMode.value) {
    // Restore saved preference on desktop
    viewMode.value = savedViewMode.value
  }
})

// Window resize listener
onMounted(() => {
  window.addEventListener('resize', updateMobileDetection)
})
onUnmounted(() => {
  window.removeEventListener('resize', updateMobileDetection)
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
  ...roles.value.map(r => ({ value: r.id, label: r.display_name_th }))
])

const tagOptions = computed(() => [
  { value: null, label: 'ทุกแท็ก' },
  ...tags.value.map(t => ({ value: t.id, label: t.name, color: t.color }))
])

// Pagination page numbers (show max 5 pages with ellipsis)
const paginationPages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages: (number | string)[] = []
  
  if (last <= 7) {
    for (let i = 1; i <= last; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 3) pages.push('...')
    
    const start = Math.max(2, current - 1)
    const end = Math.min(last - 1, current + 1)
    for (let i = start; i <= end; i++) pages.push(i)
    
    if (current < last - 2) pages.push('...')
    pages.push(last)
  }
  return pages
})

const classLevelOptions = computed(() => [
  { value: null, label: 'ทุกชั้น' },
  ...filterOptions.value.class_levels
])

const classSectionOptions = computed(() => [
  { value: null, label: 'ทุกห้อง' },
  ...[...filterOptions.value.classrooms]
    .sort((a, b) => String(a.label).localeCompare(String(b.label), 'th', { numeric: true, sensitivity: 'base' }))
    .map(classroom => ({
      value: `${classroom.class_level}::${classroom.class_section}`,
      label: classroom.label
    }))
])

const genderOptions = computed(() => [
  { value: null, label: 'ทุกเพศ' },
  ...filterOptions.value.genders.map(g => ({
    value: g.value,
    label: `${g.value === 1 ? 'ชาย' : 'หญิง'} (${g.count})`
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
    const response: any = await api.get(`/api/academies/${academyName.value}`)
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
      const secondaryCalls = [fetchRoles(), fetchTags(), fetchFilterOptions()]
      
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
    if (selectedRole.value !== null) params.append('academy_role_id', String(selectedRole.value))
    if (selectedTag.value !== null) params.append('tag_id', String(selectedTag.value))
    if (selectedClassLevel.value) params.append('class_level', selectedClassLevel.value)
    if (selectedClassSection.value) params.append('class_section', selectedClassSection.value)
    if (selectedGender.value !== null) params.append('gender', String(selectedGender.value))
    if (selectedMemberType.value) params.append('member_type', selectedMemberType.value)
    if (advancedFilters.value.dateFrom) params.append('date_from', advancedFilters.value.dateFrom)
    if (advancedFilters.value.dateTo) params.append('date_to', advancedFilters.value.dateTo)
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

const onClassroomChange = () => {
  if (selectedClassroomKey.value) {
    const [level, section] = selectedClassroomKey.value.split('::')
    selectedClassLevel.value = level || null
    selectedClassSection.value = section || null
  } else {
    selectedClassLevel.value = null
    selectedClassSection.value = null
  }
  onSearch()
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
  selectedClassroomKey.value = null
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
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon :icon="selectedMemberType === 'student' ? 'fluent:people-team-24-filled' : 'fluent:people-community-24-filled'" class="w-7 h-7 text-primary-600 dark:text-primary-400" />
            {{ selectedMemberType === 'student' ? 'รายการนักเรียน' : 'จัดการสมาชิก' }}
          </h1>
          <p class="text-gray-500 dark:text-gray-400 mt-1 text-sm">
            {{ selectedMemberType === 'student' ? 'จัดการข้อมูลนักเรียนทั้งหมดของโรงเรียน' : 'จัดการสมาชิกทั้งหมดของโรงเรียน' }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <!-- Export Button -->
          <button 
            v-if="can('members.manage')"
            @click="exportAllMembers"
            class="px-3 py-2.5 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2 text-sm font-medium shadow-sm"
            title="ส่งออกข้อมูลสมาชิก"
          >
            <Icon icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
            <span class="hidden sm:inline">ส่งออก CSV</span>
          </button>
          <NuxtLink 
            :to="`/academies/${academyName}/admin/roles`"
            class="px-3 py-2.5 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2 text-sm font-medium shadow-sm"
          >
            <Icon icon="fluent:shield-person-24-regular" class="w-5 h-5" />
            <span class="hidden sm:inline">จัดการบทบาท</span>
          </NuxtLink>
          <button 
            v-if="can('members.manage')"
            @click="showInviteModal = true"
            class="px-4 py-2.5 bg-primary-600 text-white rounded-xl hover:bg-primary-700 transition-colors flex items-center gap-2 text-sm font-medium shadow-sm shadow-primary-500/25"
          >
            <Icon icon="fluent:person-add-24-filled" class="w-5 h-5" />
            <span class="hidden sm:inline">เชิญสมาชิก</span>
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- ทั้งหมด -->
        <button 
          @click="selectedStatus = null; onSearch()"
          class="relative group bg-white dark:bg-gray-800 rounded-xl p-4 border-2 transition-all duration-200 text-left overflow-hidden"
          :class="selectedStatus === null 
            ? 'border-primary-500 shadow-lg shadow-primary-500/10 dark:shadow-primary-500/5' 
            : 'border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600 shadow-sm hover:shadow-md'"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
              <Icon icon="fluent:people-team-24-filled" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
            </div>
            <div class="min-w-0">
              <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ stats.total }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ทั้งหมด</p>
            </div>
          </div>
        </button>

        <!-- สมาชิก -->
        <button 
          @click="selectedStatus = 2; onSearch()"
          class="relative group bg-white dark:bg-gray-800 rounded-xl p-4 border-2 transition-all duration-200 text-left overflow-hidden"
          :class="selectedStatus === 2 
            ? 'border-green-500 shadow-lg shadow-green-500/10 dark:shadow-green-500/5' 
            : 'border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600 shadow-sm hover:shadow-md'"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
              <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-green-600 dark:text-green-400" />
            </div>
            <div class="min-w-0">
              <p class="text-2xl font-bold text-green-600 dark:text-green-400 leading-none">{{ stats.approved }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">สมาชิก</p>
            </div>
          </div>
        </button>

        <!-- รอการอนุมัติ -->
        <button 
          @click="selectedStatus = 1; onSearch()"
          class="relative group bg-white dark:bg-gray-800 rounded-xl p-4 border-2 transition-all duration-200 text-left overflow-hidden"
          :class="selectedStatus === 1 
            ? 'border-yellow-500 shadow-lg shadow-yellow-500/10 dark:shadow-yellow-500/5' 
            : 'border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600 shadow-sm hover:shadow-md'"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-yellow-50 dark:bg-yellow-900/30 flex items-center justify-center flex-shrink-0">
              <Icon icon="fluent:clock-24-filled" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
            </div>
            <div class="min-w-0">
              <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 leading-none">{{ stats.pending }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">รอการอนุมัติ</p>
            </div>
          </div>
          <span v-if="stats.pending > 0" class="absolute top-2 right-2 w-2.5 h-2.5 bg-yellow-500 rounded-full animate-pulse" />
        </button>

        <!-- ได้รับเชิญ -->
        <button 
          @click="selectedStatus = 4; onSearch()"
          class="relative group bg-white dark:bg-gray-800 rounded-xl p-4 border-2 transition-all duration-200 text-left overflow-hidden"
          :class="selectedStatus === 4 
            ? 'border-blue-500 shadow-lg shadow-blue-500/10 dark:shadow-blue-500/5' 
            : 'border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600 shadow-sm hover:shadow-md'"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
              <Icon icon="fluent:mail-24-filled" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="min-w-0">
              <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 leading-none">{{ stats.invited }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ได้รับเชิญ</p>
            </div>
          </div>
        </button>

        <!-- ถูกระงับ -->
        <button 
          @click="selectedStatus = 5; onSearch()"
          class="relative group bg-white dark:bg-gray-800 rounded-xl p-4 border-2 transition-all duration-200 text-left overflow-hidden"
          :class="selectedStatus === 5 
            ? 'border-orange-500 shadow-lg shadow-orange-500/10 dark:shadow-orange-500/5' 
            : 'border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600 shadow-sm hover:shadow-md'"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center flex-shrink-0">
              <Icon icon="fluent:person-prohibited-24-filled" class="w-5 h-5 text-orange-600 dark:text-orange-400" />
            </div>
            <div class="min-w-0">
              <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 leading-none">{{ stats.suspended }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ถูกระงับ</p>
            </div>
          </div>
        </button>

        <!-- ถูกปฏิเสธ -->
        <button 
          @click="selectedStatus = 3; onSearch()"
          class="relative group bg-white dark:bg-gray-800 rounded-xl p-4 border-2 transition-all duration-200 text-left overflow-hidden"
          :class="selectedStatus === 3 
            ? 'border-red-500 shadow-lg shadow-red-500/10 dark:shadow-red-500/5' 
            : 'border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600 shadow-sm hover:shadow-md'"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
              <Icon icon="fluent:dismiss-circle-24-filled" class="w-5 h-5 text-red-600 dark:text-red-400" />
            </div>
            <div class="min-w-0">
              <p class="text-2xl font-bold text-red-600 dark:text-red-400 leading-none">{{ stats.rejected }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ถูกปฏิเสธ</p>
            </div>
          </div>
        </button>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <!-- Main Search Row -->
        <div class="flex flex-col sm:flex-row gap-3 p-4 border-b border-gray-100 dark:border-gray-700">
          <div class="flex-1">
            <div class="relative">
              <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="searchQuery"
                @input="onSearch"
                type="text"
                placeholder="ค้นหาชื่อ, อีเมล, รหัสนักเรียน..."
                class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
              />
            </div>
          </div>
          
          <div class="flex items-center gap-2">
            <!-- View Mode Toggle -->
            <div v-if="!isMobile" class="flex bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
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
        </div>
        
        <!-- Filter Row -->
        <div class="px-4 py-3 flex flex-wrap gap-2 items-center">
          <!-- Status Filter -->
          <div class="relative inline-flex items-center">
            <Icon icon="fluent:status-24-regular" class="absolute left-2.5 w-4 h-4 text-gray-400 pointer-events-none" />
            <select
              v-model="selectedStatus"
              @change="onSearch"
              class="pl-8 pr-8 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm appearance-none cursor-pointer hover:border-gray-300 dark:hover:border-gray-500 transition-colors"
            >
              <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <Icon icon="fluent:chevron-down-24-regular" class="absolute right-2 w-4 h-4 text-gray-400 pointer-events-none" />
          </div>
          
          <!-- Role Filter -->
          <div class="relative inline-flex items-center">
            <Icon icon="fluent:shield-person-24-regular" class="absolute left-2.5 w-4 h-4 text-gray-400 pointer-events-none" />
            <select
              v-model="selectedRole"
              @change="onSearch"
              class="pl-8 pr-8 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm appearance-none cursor-pointer hover:border-gray-300 dark:hover:border-gray-500 transition-colors"
            >
              <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <Icon icon="fluent:chevron-down-24-regular" class="absolute right-2 w-4 h-4 text-gray-400 pointer-events-none" />
          </div>
          
          <!-- Class Level Filter -->
          <div v-if="filterOptions.class_levels.length > 0" class="relative inline-flex items-center">
            <Icon icon="fluent:class-24-regular" class="absolute left-2.5 w-4 h-4 text-gray-400 pointer-events-none" />
            <select
              v-model="selectedClassLevel"
              @change="onSearch"
              class="pl-8 pr-8 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm appearance-none cursor-pointer hover:border-gray-300 dark:hover:border-gray-500 transition-colors"
            >
              <option v-for="opt in classLevelOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <Icon icon="fluent:chevron-down-24-regular" class="absolute right-2 w-4 h-4 text-gray-400 pointer-events-none" />
          </div>
          
          <!-- Class Section Filter -->
          <div v-if="filterOptions.classrooms.length > 0" class="relative inline-flex items-center">
            <Icon icon="fluent:door-24-regular" class="absolute left-2.5 w-4 h-4 text-gray-400 pointer-events-none" />
            <select
              v-model="selectedClassroomKey"
              @change="onClassroomChange"
              class="pl-8 pr-8 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm appearance-none cursor-pointer hover:border-gray-300 dark:hover:border-gray-500 transition-colors"
            >
              <option v-for="opt in classSectionOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <Icon icon="fluent:chevron-down-24-regular" class="absolute right-2 w-4 h-4 text-gray-400 pointer-events-none" />
          </div>
          
          <!-- Gender Filter -->
          <div v-if="filterOptions.genders.some(g => g.count > 0)" class="relative inline-flex items-center">
            <Icon icon="fluent:people-24-regular" class="absolute left-2.5 w-4 h-4 text-gray-400 pointer-events-none" />
            <select
              v-model="selectedGender"
              @change="onSearch"
              class="pl-8 pr-8 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm appearance-none cursor-pointer hover:border-gray-300 dark:hover:border-gray-500 transition-colors"
            >
              <option v-for="opt in genderOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <Icon icon="fluent:chevron-down-24-regular" class="absolute right-2 w-4 h-4 text-gray-400 pointer-events-none" />
          </div>
          
          <!-- Tag Filter -->
          <div v-if="tags.length > 0" class="relative inline-flex items-center">
            <Icon icon="mdi:tag-outline" class="absolute left-2.5 w-4 h-4 text-gray-400 pointer-events-none" />
            <select
              v-model="selectedTag"
              @change="onSearch"
              class="pl-8 pr-8 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm appearance-none cursor-pointer hover:border-gray-300 dark:hover:border-gray-500 transition-colors"
            >
              <option v-for="opt in tagOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <Icon icon="fluent:chevron-down-24-regular" class="absolute right-2 w-4 h-4 text-gray-400 pointer-events-none" />
          </div>
          
          <!-- Divider -->
          <div class="hidden sm:block w-px h-6 bg-gray-200 dark:bg-gray-600 mx-1" />
          
          <!-- Group By -->
          <div class="relative inline-flex items-center">
            <Icon icon="fluent:group-list-24-regular" class="absolute left-2.5 w-4 h-4 text-gray-400 pointer-events-none" />
            <select
              v-model="groupBy"
              class="pl-8 pr-8 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700/50 text-gray-900 dark:text-white text-sm appearance-none cursor-pointer hover:border-gray-300 dark:hover:border-gray-500 transition-colors"
            >
              <option value="none">ไม่จัดกลุ่ม</option>
              <option value="classroom">ตามห้องเรียน</option>
              <option value="class_level">ตามชั้นเรียน</option>
              <option value="gender">ตามเพศ</option>
            </select>
            <Icon icon="fluent:chevron-down-24-regular" class="absolute right-2 w-4 h-4 text-gray-400 pointer-events-none" />
          </div>
          
          <!-- Clear Filters -->
          <button
            v-if="activeFiltersCount > 0"
            @click="clearAllFilters"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg text-sm font-medium transition-colors"
          >
            <Icon icon="fluent:dismiss-circle-24-regular" class="w-4 h-4" />
            ล้างตัวกรอง
            <span class="bg-red-200 dark:bg-red-800 text-red-700 dark:text-red-300 text-xs px-1.5 py-0.5 rounded-full font-bold">{{ activeFiltersCount }}</span>
          </button>
          
          <!-- Student Filter Indicator -->
          <div
            v-if="selectedMemberType === 'student'"
            class="inline-flex items-center gap-2 px-3 py-2 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 rounded-lg text-sm font-medium border border-primary-200 dark:border-primary-800"
          >
            <Icon icon="fluent:person-student-24-filled" class="w-4 h-4" />
            <span>เฉพาะนักเรียน</span>
            <button
              @click="selectedMemberType = null; onSearch()"
              class="ml-0.5 hover:bg-primary-100 dark:hover:bg-primary-800/50 rounded p-0.5 transition-colors"
              title="แสดงสมาชิกทั้งหมด"
            >
              <Icon icon="fluent:dismiss-24-regular" class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
        
        <!-- Classroom Quick Filters -->
        <div v-if="filterOptions.classrooms.length > 0 && filterOptions.classrooms.length <= 20" class="px-4 pb-3 flex flex-wrap gap-2">
          <button
            v-for="classroom in filterOptions.classrooms"
            :key="`${classroom.class_level}-${classroom.class_section}`"
            @click="selectedClassroomKey = `${classroom.class_level}::${classroom.class_section}`; selectedClassLevel = classroom.class_level; selectedClassSection = classroom.class_section; onSearch()"
            :class="[
              'px-3 py-1.5 rounded-full text-sm font-medium transition-all duration-200',
              selectedClassLevel === classroom.class_level && selectedClassSection === classroom.class_section
                ? 'bg-primary-500 text-white shadow-sm shadow-primary-500/25'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
            ]"
          >
            {{ classroom.label }} <span class="opacity-70">({{ classroom.count }})</span>
          </button>
        </div>
      </div>

      <!-- Members List -->
      <div>
        <!-- Results Info Bar -->
        <div v-if="!isLoadingMembers && members.length > 0" class="flex items-center justify-between mb-3">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            <Icon icon="fluent:document-copy-24-regular" class="w-4 h-4 inline -mt-0.5 mr-1" />
            หน้า {{ pagination.current_page }}/{{ pagination.last_page }}
            <span class="mx-1.5 text-gray-300 dark:text-gray-600">|</span>
            <span class="font-medium text-gray-700 dark:text-gray-300">{{ pagination.total }}</span> คน
          </p>
          <div v-if="selectedMemberIds.length > 0" class="text-sm text-primary-600 dark:text-primary-400 font-medium">
            เลือกแล้ว {{ selectedMemberIds.length }} คน
          </div>
        </div>

        <div v-if="isLoadingMembers" class="flex flex-col items-center justify-center py-16 gap-3">
          <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
          <p class="text-sm text-gray-500 dark:text-gray-400">กำลังโหลดข้อมูลสมาชิก...</p>
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
              :view-mode="effectiveViewMode"
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
          :view-mode="effectiveViewMode"
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
        <div v-if="pagination.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 sm:px-6 py-4 mt-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            แสดง <span class="font-semibold text-gray-700 dark:text-gray-300">{{ members.length }}</span> จาก <span class="font-semibold text-gray-700 dark:text-gray-300">{{ pagination.total }}</span> รายการ
          </p>
          <div class="flex items-center gap-1">
            <button
              @click="fetchMembers(1)"
              :disabled="pagination.current_page === 1"
              class="p-2 rounded-lg border border-gray-200 dark:border-gray-600 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 transition-colors"
              title="หน้าแรก"
            >
              <Icon icon="fluent:chevron-double-left-24-regular" class="w-4 h-4" />
            </button>
            <button
              @click="fetchMembers(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="p-2 rounded-lg border border-gray-200 dark:border-gray-600 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 transition-colors"
              title="ก่อนหน้า"
            >
              <Icon icon="fluent:chevron-left-24-regular" class="w-4 h-4" />
            </button>
            
            <!-- Page Numbers -->
            <template v-for="p in paginationPages" :key="p">
              <span v-if="p === '...'" class="px-2 py-1 text-gray-400 select-none">...</span>
              <button
                v-else
                @click="fetchMembers(p as number)"
                class="min-w-[36px] h-9 px-2 rounded-lg text-sm font-medium transition-all"
                :class="p === pagination.current_page 
                  ? 'bg-primary-600 text-white shadow-sm shadow-primary-500/25' 
                  : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600'"
              >
                {{ p }}
              </button>
            </template>
            
            <button
              @click="fetchMembers(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="p-2 rounded-lg border border-gray-200 dark:border-gray-600 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 transition-colors"
              title="ถัดไป"
            >
              <Icon icon="fluent:chevron-right-24-regular" class="w-4 h-4" />
            </button>
            <button
              @click="fetchMembers(pagination.last_page)"
              :disabled="pagination.current_page === pagination.last_page"
              class="p-2 rounded-lg border border-gray-200 dark:border-gray-600 disabled:opacity-30 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 transition-colors"
              title="หน้าสุดท้าย"
            >
              <Icon icon="fluent:chevron-double-right-24-regular" class="w-4 h-4" />
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

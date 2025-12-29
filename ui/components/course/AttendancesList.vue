<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useApi } from '~/composables/useApi'
import AttendancesTable from '~/components/course/AttendancesTable.vue'
import StudentAttendanceTable from '~/components/course/StudentAttendanceTable.vue'
import Swal from 'sweetalert2'

interface Props {
  courseId: string | number
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const api = useApi()

// State
const attendances = ref<any[]>([])
const groups = ref<any[]>([])
const selectedGroupId = ref<number | null>(null)
const selectedGroupMembers = ref<any[]>([])
const courseMemberOfAuth = ref<any>(null)
const loading = ref(true)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDetailsModal = ref(false)
const editingAttendance = ref<any>(null)
const selectedAttendance = ref<any>(null)
const attendanceDetails = ref<any[]>([])
const checkingIn = ref(false)

// Auto-refresh state
const autoRefreshEnabled = ref(true)
const autoRefreshInterval = ref(15) // seconds
const refreshTimer = ref<NodeJS.Timeout | null>(null)
const lastRefreshed = ref<Date | null>(null)
const isAutoRefreshing = ref(false)

// Helper function to get datetime-local format
const getDateTimeLocal = (date: Date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
}

// Convert any datetime string to datetime-local format
const toDateTimeLocalFormat = (dateTimeString: string) => {
  if (!dateTimeString) return ''
  const date = new Date(dateTimeString)
  if (isNaN(date.getTime())) return ''
  return getDateTimeLocal(date)
}

// Add 45 minutes to a datetime-local string
const addMinutesToDateTime = (dateTimeLocal: string, minutes: number = 45) => {
  if (!dateTimeLocal) return ''
  const date = new Date(dateTimeLocal)
  if (isNaN(date.getTime())) return ''
  date.setMinutes(date.getMinutes() + minutes)
  return getDateTimeLocal(date)
}

// Get initial times
const now = new Date()
const endTime = new Date(now.getTime() + 45 * 60000) // +45 minutes

// New attendance form
const newAttendance = ref({
  title: '',
  date: new Date().toISOString().split('T')[0],
  start_time: getDateTimeLocal(now),
  end_time: getDateTimeLocal(endTime),
  description: '',
  late_time: 15
})

// Watch for newAttendance start_time changes to auto-update end_time (+45 minutes)
watch(() => newAttendance.value.start_time, (newStartTime, oldStartTime) => {
  if (newStartTime && newStartTime !== oldStartTime) {
    newAttendance.value.end_time = addMinutesToDateTime(newStartTime, 45)
  }
})

// Fetch attendances (silent = don't show loading spinner for auto-refresh)
const fetchAttendances = async (groupId?: number | null, silent: boolean = false) => {
  if (!silent) {
    loading.value = true
  }
  try {
    // Build query params
    const params = new URLSearchParams()
    if (groupId) {
      params.append('group_id', groupId.toString())
    }
    
    const queryString = params.toString()
    const url = `/api/courses/${props.courseId}/attendances${queryString ? `?${queryString}` : ''}`
    
    const response = await api.get(url)
    
    // Store course member data
    if (response.courseMemberOfAuth) {
      courseMemberOfAuth.value = response.courseMemberOfAuth
    }
    
    // Store groups data (only on first load)
    if (response.groups && groups.value.length === 0) {
      groups.value = response.groups
      
      // Set default selected group based on last_accessed_group_tab or first group
      if (props.isCourseAdmin && groups.value.length > 0 && !selectedGroupId.value) {
        // Try to use last accessed group from courseMemberOfAuth
        const lastAccessedGroupId = courseMemberOfAuth.value?.last_accessed_group_tab
        
        if (lastAccessedGroupId && groups.value.some(g => g.id === lastAccessedGroupId)) {
          selectedGroupId.value = lastAccessedGroupId
        } else {
          // Fallback to first group
          selectedGroupId.value = groups.value[0].id
        }
        
        // Set group members
        const selectedGroup = groups.value.find(g => g.id === selectedGroupId.value)
        if (selectedGroup?.members) {
          selectedGroupMembers.value = selectedGroup.members.sort((a: any, b: any) => (a.order_number || 0) - (b.order_number || 0))
        }
        
        return // Skip setting attendances, will be fetched by watch
      }
    }
    
    // Update group members when group changes
    if (groupId) {
      const selectedGroup = groups.value.find(g => g.id === groupId)
      if (selectedGroup?.members) {
        selectedGroupMembers.value = selectedGroup.members.sort((a: any, b: any) => (a.order_number || 0) - (b.order_number || 0))
      }
    }
    
    attendances.value = response.data || response
  } catch (error) {
    console.error('Error fetching attendances:', error)
    attendances.value = []
  } finally {
    loading.value = false
  }
}

// Update last access group tab
const updateLastAccessGroupTab = async (groupId: number) => {
  if (!props.isCourseAdmin || !courseMemberOfAuth.value) return
  
  try {
    await api.patch(`/api/courses/${props.courseId}/members/update-last-access-group`, {
      last_accessed_group_tab: groupId
    })
  } catch (error) {
    console.error('Error updating last access group tab:', error)
  }
}

// Create attendance
const createAttendance = async () => {
  if (!selectedGroupId.value) {
    alert('กรุณาเลือกกลุ่มเรียน')
    return
  }
  
  try {
    const payload = {
      description: newAttendance.value.description,
      start_at: newAttendance.value.start_time,
      finish_at: newAttendance.value.end_time,
      late_time: newAttendance.value.late_time || 15
    }
    
    await api.post(`/api/courses/${props.courseId}/groups/${selectedGroupId.value}/attendances`, payload)
    showCreateModal.value = false
    resetForm()
    await fetchAttendances(selectedGroupId.value)
    
    // Success toast
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'สร้างสำเร็จ',
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
      customClass: {
        popup: 'colored-toast'
      }
    })
  } catch (error) {
    console.error('Error creating attendance:', error)
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: 'ไม่สามารถสร้างการเช็คชื่อได้',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    })
  }
}

// Update attendance
const updateAttendance = async () => {
  if (!editingAttendance.value) return
  
  try {
    const payload = {
      description: editingAttendance.value.description,
      start_at: editingAttendance.value.start_at,
      finish_at: editingAttendance.value.finish_at,
      late_time: editingAttendance.value.late_time || 0,
    }
    
    await api.patch(`/api/attendances/${editingAttendance.value.id}`, payload)
    showEditModal.value = false
    await fetchAttendances(selectedGroupId.value)
  } catch (error) {
    console.error('Error updating attendance:', error)
    alert('เกิดข้อผิดพลาดในการอัพเดทการเช็คชื่อ')
  }
}

// Delete attendance
const deleteAttendance = async (attendanceId: number) => {
  if (!confirm('คุณต้องการลบการเช็คชื่อนี้หรือไม่?')) return
  
  try {
    await api.delete(`/api/attendances/${attendanceId}`)
    fetchAttendances()
  } catch (error) {
    console.error('Error deleting attendance:', error)
  }
}

// Check-in
const checkIn = async (attendance: any) => {
  checkingIn.value = true
  try {
    await api.post(`/api/attendances/${attendance.id}/check-in`)
    fetchAttendances()
  } catch (error) {
    console.error('Error checking in:', error)
  } finally {
    checkingIn.value = false
  }
}

// View details
const viewDetails = (attendance: any) => {
  // Convert datetime to datetime-local format for input fields
  selectedAttendance.value = {
    ...attendance,
    start_at: toDateTimeLocalFormat(attendance.start_at),
    finish_at: toDateTimeLocalFormat(attendance.finish_at)
  }
  showDetailsModal.value = true
}

// Open edit modal
const openEditModal = (attendance: any) => {
  // Convert datetime to datetime-local format for input fields
  editingAttendance.value = {
    ...attendance,
    start_at: toDateTimeLocalFormat(attendance.start_at),
    finish_at: toDateTimeLocalFormat(attendance.finish_at)
  }
  showEditModal.value = true
}

// Watch for start_at changes to auto-update finish_at (+45 minutes)
watch(() => selectedAttendance.value?.start_at, (newStartAt, oldStartAt) => {
  if (newStartAt && newStartAt !== oldStartAt && selectedAttendance.value) {
    selectedAttendance.value.finish_at = addMinutesToDateTime(newStartAt, 45)
  }
})

// Watch for editingAttendance start_at changes
watch(() => editingAttendance.value?.start_at, (newStartAt, oldStartAt) => {
  if (newStartAt && newStartAt !== oldStartAt && editingAttendance.value) {
    editingAttendance.value.finish_at = addMinutesToDateTime(newStartAt, 45)
  }
})

// Save attendance changes from details modal
const saveAttendanceChanges = async () => {
  if (!selectedAttendance.value) return
  
  try {
    const payload = {
      description: selectedAttendance.value.description,
      start_at: selectedAttendance.value.start_at,
      finish_at: selectedAttendance.value.finish_at,
      late_time: selectedAttendance.value.late_time || 0,
    }
    
    await api.patch(`/api/attendances/${selectedAttendance.value.id}`, payload)
    showDetailsModal.value = false
    await fetchAttendances(selectedGroupId.value)
    
    // Success toast
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'บันทึกสำเร็จ',
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
      customClass: {
        popup: 'colored-toast'
      }
    })
  } catch (error) {
    console.error('Error updating attendance:', error)
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: 'ไม่สามารถอัพเดทข้อมูลได้',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    })
  }
}

// Open edit modal from details modal (deprecated - now edit directly in details)
const openEditModalFromDetails = () => {
  if (!selectedAttendance.value) return
  
  editingAttendance.value = { ...selectedAttendance.value }
  showDetailsModal.value = false
  showEditModal.value = true
}

// Confirm delete from details modal
const confirmDeleteAttendance = async () => {
  if (!selectedAttendance.value) return
  
  const result = await Swal.fire({
    title: 'ลบการเช็คชื่อ?',
    html: `<p class="text-gray-600">คุณต้องการลบการเช็คชื่อ<br/><strong>${selectedAttendance.value.description || 'วันที่ ' + new Date(selectedAttendance.value.date).toLocaleDateString('th-TH')}</strong></p><p class="text-red-600 text-sm mt-2">การดำเนินการน้ีไม่สามารถย้อนกลับได้</p>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก',
    reverseButtons: true,
    customClass: {
      popup: 'rounded-xl',
      confirmButton: 'px-5 py-2 rounded-lg font-medium',
      cancelButton: 'px-5 py-2 rounded-lg font-medium'
    }
  })
  
  if (!result.isConfirmed) return
  
  try {
    await api.delete(`/api/attendances/${selectedAttendance.value.id}`)
    showDetailsModal.value = false
    await fetchAttendances(selectedGroupId.value)
  } catch (error) {
    console.error('Error deleting attendance:', error)
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: 'ไม่สามารถลบการเช็คชื่อได้',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true
    })
  }
}

// Reset form
const resetForm = () => {
  const now = new Date()
  const endTime = new Date(now.getTime() + 45 * 60000) // +45 minutes
  
  newAttendance.value = {
    title: '',
    date: new Date().toISOString().split('T')[0],
    start_time: getDateTimeLocal(now),
    end_time: getDateTimeLocal(endTime),
    description: '',
    late_time: 15
  }
}

// Update member attendance status
const updateMemberStatus = async (memberId: number, status: number) => {
  if (!selectedAttendance.value) return
  
  try {
    await api.post(`/api/attendances/${selectedAttendance.value.id}/member/${memberId}/update-status`, {
      status
    })
    // Refresh details
    const response = await api.get(`/api/attendances/${selectedAttendance.value.id}/details`)
    attendanceDetails.value = response.data || response
    await fetchAttendances(selectedGroupId.value)
  } catch (error) {
    console.error('Error updating member status:', error)
    alert('เกิดข้อผิดพลาดในการอัพเดทสถานะ')
  }
}

// Handle update member status from table (with optimistic update)
const handleUpdateMemberStatus = async ({ attendanceId, memberId, status }: { attendanceId: number, memberId: number, status: number | null }) => {
  // Find the attendance and update locally first (optimistic update)
  const attendance = attendances.value.find(a => a.id === attendanceId)
  if (!attendance) return
  
  // Store old status for rollback
  const detail = attendance.details?.find((d: any) => d.course_member_id === memberId)
  const oldStatus = detail ? detail.status : null
  
  // Update UI immediately
  if (detail) {
    detail.status = status
  } else if (attendance.details) {
    // Create new detail if doesn't exist
    attendance.details.push({
      course_member_id: memberId,
      status: status
    })
  }
  
  try {
    // Convert null to 0 for API (0 = ขาด)
    const apiStatus = status === null ? 0 : status
    
    // Send to server
    await api.post(`/api/attendances/${attendanceId}/member/${memberId}/update-status`, {
      status: apiStatus
    })
    
    // Show subtle toast notification
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'อัพเดทสถานะสำเร็จ',
      showConfirmButton: false,
      timer: 1500,
      timerProgressBar: true
    })
  } catch (error) {
    // Rollback on error
    if (detail && oldStatus !== null) {
      detail.status = oldStatus
    } else if (detail && oldStatus === null) {
      // Remove the detail we just added
      const index = attendance.details.indexOf(detail)
      if (index > -1) {
        attendance.details.splice(index, 1)
      }
    }
    
    console.error('Error updating member status:', error)
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: 'ไม่สามารถอัพเดทสถานะได้',
      showConfirmButton: false,
      timer: 2000
    })
  }
}

// Format date
const formatDate = (date: string) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Filtered attendances by selected group
const filteredAttendances = computed(() => {
  if (!props.isCourseAdmin || !selectedGroupId.value) {
    return attendances.value
  }
  
  return attendances.value.filter((attendance: any) => {
    return attendance.group_id === selectedGroupId.value
  })
})

// Student attendances - format attendance data for student view
// Only show attendances from the student's own group
const studentAttendances = computed(() => {
  if (props.isCourseAdmin || !courseMemberOfAuth.value) {
    return []
  }
  
  const studentGroupId = courseMemberOfAuth.value.group_id
  
  // Filter attendances by student's group and map with student's status
  return attendances.value
    .filter((attendance: any) => {
      // Only include attendances from the student's group
      return !studentGroupId || attendance.group_id === studentGroupId
    })
    .map((attendance: any) => {
      // Find student's detail in attendance details
      const memberDetail = attendance.details?.find(
        (d: any) => d.course_member_id === courseMemberOfAuth.value?.id
      )
      
      return {
        id: attendance.id,
        date: attendance.date || attendance.start_at,
        start_at: attendance.start_at,
        finish_at: attendance.finish_at,
        late_time: attendance.late_time || 15, // Default 15 minutes
        description: attendance.description,
        status: memberDetail?.status ?? null, // null = ขาด
        time_in: memberDetail?.time_in
      }
    })
    .sort((a: any, b: any) => {
      // Sort by date descending (newest first)
      return new Date(b.date).getTime() - new Date(a.date).getTime()
    })
})

// Watch for group changes and fetch attendances
watch(selectedGroupId, (newGroupId, oldGroupId) => {
  if (newGroupId && props.isCourseAdmin) {
    // Only update backend if this is a user-initiated change (not initial load)
    if (oldGroupId !== undefined && oldGroupId !== null) {
      updateLastAccessGroupTab(newGroupId)
    }
    fetchAttendances(newGroupId)
  }
})

// Auto-refresh functions
const startAutoRefresh = () => {
  if (refreshTimer.value) {
    clearInterval(refreshTimer.value)
  }
  
  if (autoRefreshEnabled.value && props.isCourseAdmin) {
    refreshTimer.value = setInterval(async () => {
      if (!loading.value && !showCreateModal.value && !showEditModal.value && !showDetailsModal.value) {
        isAutoRefreshing.value = true
        await fetchAttendances(selectedGroupId.value, true) // silent refresh
        lastRefreshed.value = new Date()
        isAutoRefreshing.value = false
      }
    }, autoRefreshInterval.value * 1000)
  }
}

const stopAutoRefresh = () => {
  if (refreshTimer.value) {
    clearInterval(refreshTimer.value)
    refreshTimer.value = null
  }
}

const toggleAutoRefresh = () => {
  autoRefreshEnabled.value = !autoRefreshEnabled.value
  if (autoRefreshEnabled.value) {
    startAutoRefresh()
  } else {
    stopAutoRefresh()
  }
}

// Manual refresh
const manualRefresh = async () => {
  isAutoRefreshing.value = true
  await fetchAttendances(selectedGroupId.value, true)
  lastRefreshed.value = new Date()
  isAutoRefreshing.value = false
}

// Format last refreshed time
const formatLastRefreshed = computed(() => {
  if (!lastRefreshed.value) return ''
  const now = new Date()
  const diff = Math.floor((now.getTime() - lastRefreshed.value.getTime()) / 1000)
  if (diff < 60) return `${diff} วินาทีที่แล้ว`
  if (diff < 3600) return `${Math.floor(diff / 60)} นาทีที่แล้ว`
  return lastRefreshed.value.toLocaleTimeString('th-TH')
})

// Watch auto-refresh enabled state
watch(autoRefreshEnabled, (enabled) => {
  if (enabled) {
    startAutoRefresh()
  } else {
    stopAutoRefresh()
  }
})

// Init
onMounted(() => {
  fetchAttendances()
  if (props.isCourseAdmin) {
    startAutoRefresh()
  }
})

// Cleanup on unmount
onUnmounted(() => {
  stopAutoRefresh()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Group Selection for Admin -->
    <div v-if="isCourseAdmin && groups.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
        <Icon icon="fluent:people-team-24-regular" class="w-5 h-5 inline-block mr-2" />
        เลือกกลุ่มเรียน
      </label>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
        <button
          v-for="group in groups"
          :key="group.id"
          @click="selectedGroupId = group.id"
          :class="[
            'relative p-4 rounded-xl border-2 transition-all duration-300 text-left',
            selectedGroupId === group.id
              ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-md scale-105'
              : 'border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700 hover:shadow-sm'
          ]"
        >
          <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <div
                  :class="[
                    'w-3 h-3 rounded-full transition-all',
                    selectedGroupId === group.id ? 'bg-blue-500 animate-pulse' : 'bg-gray-400'
                  ]"
                ></div>
                <h4 class="font-semibold text-gray-900 dark:text-white truncate">
                  {{ group.name || `กลุ่ม ${group.id}` }}
                </h4>
              </div>
              <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                <Icon icon="fluent:people-20-regular" class="w-4 h-4" />
                <span>{{ group.members?.length || group.member_count || 0 }} คน</span>
              </p>
            </div>
            <Icon
              v-if="selectedGroupId === group.id"
              icon="fluent:checkmark-circle-24-filled"
              class="w-6 h-6 text-blue-500 flex-shrink-0"
            />
          </div>
        </button>
      </div>
      
      <!-- Loading indicator when changing groups -->
      <div v-if="loading" class="mt-3 flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
        <div class="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
        <span>กำลังโหลดประวัติการเช็คชื่อ...</span>
      </div>
    </div>
    
    <!-- Auto-Refresh Controls for Admin -->
    <div v-if="isCourseAdmin && groups.length > 0" class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-xl shadow-sm p-4 border border-blue-200 dark:border-gray-600">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
            <Icon icon="fluent:arrow-sync-24-filled" class="w-5 h-5 text-white" :class="{ 'animate-spin': isAutoRefreshing }" />
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 dark:text-white">อัพเดทสถานะอัตโนมัติ</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              <template v-if="autoRefreshEnabled">
                รีเฟรชทุก {{ autoRefreshInterval }} วินาที
                <span v-if="lastRefreshed" class="ml-2">• อัพเดทล่าสุด: {{ formatLastRefreshed }}</span>
              </template>
              <template v-else>ปิดอยู่ - กดปุ่มรีเฟรชเพื่ออัพเดทข้อมูล</template>
            </p>
          </div>
        </div>
        
        <div class="flex items-center gap-3">
          <!-- Manual Refresh Button -->
          <button
            @click="manualRefresh"
            :disabled="isAutoRefreshing"
            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 rounded-lg border border-blue-300 dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-gray-600 transition-all disabled:opacity-50"
          >
            <Icon icon="fluent:arrow-clockwise-24-regular" class="w-5 h-5" :class="{ 'animate-spin': isAutoRefreshing }" />
            <span class="hidden sm:inline">รีเฟรช</span>
          </button>
          
          <!-- Auto-Refresh Toggle -->
          <button
            @click="toggleAutoRefresh"
            :class="[
              'relative inline-flex h-8 w-14 items-center rounded-full transition-colors duration-300',
              autoRefreshEnabled ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'
            ]"
          >
            <span
              :class="[
                'inline-block h-6 w-6 transform rounded-full bg-white shadow-lg transition-transform duration-300',
                autoRefreshEnabled ? 'translate-x-7' : 'translate-x-1'
              ]"
            />
          </button>
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ autoRefreshEnabled ? 'เปิด' : 'ปิด' }}
          </span>
        </div>
      </div>
      
      <!-- Live indicator -->
      <div v-if="autoRefreshEnabled" class="mt-3 flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
        <span class="relative flex h-3 w-3">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
        </span>
        <span>กำลังติดตามสถานะการมาเรียนแบบ Live</span>
      </div>
    </div>
    
    <!-- Loading with spinner -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-16">
      <div class="relative">
        <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
        <Icon icon="fluent:calendar-24-regular" class="w-8 h-8 text-blue-600 absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2" />
      </div>
      <p class="mt-4 text-gray-500 dark:text-gray-400 font-medium">กำลังโหลดข้อมูล...</p>
    </div>
    
    <!-- Attendances Table for Admin -->
    <AttendancesTable
      v-else-if="isCourseAdmin && filteredAttendances.length > 0 && selectedGroupMembers.length > 0"
      :attendances="filteredAttendances"
      :group-members="selectedGroupMembers"
      :is-course-admin="isCourseAdmin"
      @create="showCreateModal = true"
      @view-details="viewDetails"
      @edit="openEditModal"
      @delete="deleteAttendance"
      @update-status="handleUpdateMemberStatus"
    />
    
    <!-- Attendances Table for Student -->
    <StudentAttendanceTable
      v-else-if="!isCourseAdmin && studentAttendances.length > 0"
      :attendances="studentAttendances"
      :loading="loading"
      @reload="fetchAttendances()"
    />
    
    <!-- Empty State with illustration -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-12 text-center">
      <div class="w-32 h-32 mx-auto mb-6 bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 rounded-3xl flex items-center justify-center">
        <Icon icon="fluent:calendar-empty-24-regular" class="w-20 h-20 text-blue-500 dark:text-blue-400" />
      </div>
      <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
        ยังไม่มีการเช็คชื่อ
      </h3>
      <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
        {{ isCourseAdmin ? 'เริ่มต้นสร้างการเช็คชื่อเพื่อติดตามการเข้าเรียนของนักเรียน' : 'รอผู้สอนสร้างการเช็คชื่อ คุณจะได้รับการแจ้งเตือนเมื่อมีการเช็คชื่อใหม่' }}
      </p>
      <button
        v-if="isCourseAdmin"
        @click="showCreateModal = true"
        class="inline-flex items-center gap-2 px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all duration-300 font-semibold shadow-lg hover:shadow-xl hover:scale-105"
      >
        <Icon icon="fluent:add-circle-24-filled" class="w-6 h-6" />
        <span>สร้างการเช็คชื่อแรก</span>
      </button>
    </div>
    
    <!-- Create Modal -->
    <DialogModal :show="showCreateModal" @close="showCreateModal = false">
      <template #title>สร้างการเช็คชื่อ</template>
      
      <template #content>
        <div class="space-y-4">
          <!-- Selected Group Display -->
          <div v-if="selectedGroupId" class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                <Icon icon="fluent:people-team-24-filled" class="w-6 h-6 text-white" />
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">กำลังสร้างการเช็คชื่อสำหรับ</p>
                <p class="font-semibold text-gray-900 dark:text-white">
                  {{ groups.find(g => g.id === selectedGroupId)?.name || `กลุ่ม ${selectedGroupId}` }}
                </p>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              <Icon icon="fluent:text-24-regular" class="inline-block w-4 h-4 mr-1" />
              หัวข้อ/คำอธิบาย
            </label>
            <input
              v-model="newAttendance.description"
              type="text"
              placeholder="ครั้งที่หรือสัปดาห์ที่"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            />
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                <Icon icon="fluent:calendar-24-regular" class="inline-block w-4 h-4 mr-1" />
                เวลาเริ่มต้น
              </label>
              <input
                v-model="newAttendance.start_time"
                type="datetime-local"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                <Icon icon="fluent:calendar-checkmark-24-regular" class="inline-block w-4 h-4 mr-1" />
                เวลาสิ้นสุด
              </label>
              <input
                v-model="newAttendance.end_time"
                type="datetime-local"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
              />
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
              <Icon icon="fluent:clock-24-regular" class="inline-block w-4 h-4 mr-1" />
              เวลาสาย (นาที)
            </label>
            <input
              v-model.number="newAttendance.late_time"
              type="number"
              min="0"
              placeholder="15"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            />
          </div>
        </div>
      </template>
      
      <template #footer>
        <button
          @click="showCreateModal = false"
          class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
        >
          ยกเลิก
        </button>
        <button
          @click="createAttendance"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
          สร้าง
        </button>
      </template>
    </DialogModal>
    
    <!-- Edit Modal -->
    <DialogModal :show="showEditModal" @close="showEditModal = false">
      <template #title>
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
            <Icon icon="fluent:edit-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
          </div>
          <div>
            <h3 class="text-lg font-bold">แก้ไขการเช็คชื่อ</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">อัพเดทข้อมูลการเช็คชื่อ</p>
          </div>
        </div>
      </template>
      
      <template #content>
        <div v-if="editingAttendance" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <Icon icon="fluent:text-24-regular" class="inline-block w-4 h-4 mr-1" />
              หัวข้อ/คำอธิบาย
            </label>
            <input
              v-model="editingAttendance.description"
              type="text"
              placeholder="เช่น บทที่ 1, สอบกลางภาค"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
            />
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                <Icon icon="fluent:calendar-24-regular" class="inline-block w-4 h-4 mr-1" />
                เวลาเริ่มต้น
              </label>
              <input
                v-model="editingAttendance.start_at"
                type="datetime-local"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                <Icon icon="fluent:calendar-checkmark-24-regular" class="inline-block w-4 h-4 mr-1" />
                เวลาสิ้นสุด
              </label>
              <input
                v-model="editingAttendance.finish_at"
                type="datetime-local"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              <Icon icon="fluent:clock-24-regular" class="inline-block w-4 h-4 mr-1" />
              เวลาสาย (นาที)
            </label>
            <input
              v-model.number="editingAttendance.late_time"
              type="number"
              min="0"
              placeholder="จำนวนนาทีที่ถือว่าสาย"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
      </template>
      
      <template #footer>
        <button
          @click="showEditModal = false"
          class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
        >
          ยกเลิก
        </button>
        <button
          @click="updateAttendance"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          <Icon icon="fluent:save-24-regular" class="inline-block w-4 h-4 mr-1" />
          บันทึกการเปลี่ยนแปลง
        </button>
      </template>
    </DialogModal>
    
    <!-- Details/Edit Modal (Combined) -->
    <DialogModal :show="showDetailsModal" @close="showDetailsModal = false" max-width="2xl">
      <template #title>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center shadow-md">
              <Icon icon="fluent:calendar-checkmark-24-filled" class="w-6 h-6 text-white" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-white">รายละเอียดการเช็คชื่อ</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                {{ formatDate(selectedAttendance?.date || selectedAttendance?.start_at) }}
              </p>
            </div>
          </div>
          
          <!-- Close button -->
          <button
            @click="showDetailsModal = false"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            title="ปิด"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
          </button>
        </div>
      </template>
      
      <template #content>
        <div class="space-y-4">
          <!-- Attendance Info Form (Editable for Admin) -->
          <div v-if="selectedAttendance" class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-4 border border-blue-200 dark:border-gray-600">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                  <Icon icon="fluent:text-24-regular" class="inline-block w-4 h-4 mr-1" />
                  หัวข้อ/คำอธิบาย
                </label>
                <input
                  v-model="selectedAttendance.description"
                  :disabled="!isCourseAdmin"
                  type="text"
                  placeholder="ครั้งที่หรือสัปดาห์ที่"
                  class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                  <Icon icon="fluent:clock-24-regular" class="inline-block w-4 h-4 mr-1" />
                  เวลาสาย (นาที)
                </label>
                <input
                  v-model.number="selectedAttendance.late_time"
                  :disabled="!isCourseAdmin"
                  type="number"
                  min="0"
                  placeholder="จำนวนนาทีที่ถือว่าสาย"
                  class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                  <Icon icon="fluent:calendar-24-regular" class="inline-block w-4 h-4 mr-1" />
                  เวลาเริ่มต้น
                </label>
                <input
                  v-model="selectedAttendance.start_at"
                  :disabled="!isCourseAdmin"
                  type="datetime-local"
                  class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                  <Icon icon="fluent:calendar-checkmark-24-regular" class="inline-block w-4 h-4 mr-1" />
                  เวลาสิ้นสุด
                </label>
                <input
                  v-model="selectedAttendance.finish_at"
                  :disabled="!isCourseAdmin"
                  type="datetime-local"
                  class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed"
                />
              </div>
            </div>
          </div>
        </div>
      </template>
      
      <template #footer>
        <div class="flex items-center justify-between w-full">
          <button
            @click="showDetailsModal = false"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
            <span>ปิด</span>
          </button>
          
          <div v-if="isCourseAdmin" class="flex items-center gap-3">
            <button
              @click="confirmDeleteAttendance"
              class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium shadow-lg hover:shadow-xl"
            >
              <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
              <span>ลบการเช็คชื่อ</span>
            </button>
            
            <button
              @click="saveAttendanceChanges"
              class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all font-medium shadow-lg hover:shadow-xl"
            >
              <Icon icon="fluent:save-24-filled" class="w-5 h-5" />
              <span>บันทึกการเปลี่ยนแปลง</span>
            </button>
          </div>
        </div>
      </template>
    </DialogModal>
  </div>
</template>
<style scoped>
@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in-up {
  animation: fade-in-up 0.6s ease-out forwards;
  opacity: 0;
}

/* Custom scrollbar for modal */
.max-h-\[60vh\]::-webkit-scrollbar {
  width: 8px;
}

.max-h-\[60vh\]::-webkit-scrollbar-track {
  background: transparent;
}

.max-h-\[60vh\]::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.max-h-\[60vh\]::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.dark .max-h-\[60vh\]::-webkit-scrollbar-thumb {
  background: #475569;
}

.dark .max-h-\[60vh\]::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}
</style>
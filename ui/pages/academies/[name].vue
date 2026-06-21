<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import FeedPost from '~/components/play/feed/FeedPost.vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

const route = useRoute()
const api = useApi()
const config = useRuntimeConfig()
const { user } = storeToRefs(useAuthStore())

// State
const academy = ref<any>(null)
const courses = ref<any[]>([])
const members = ref<any[]>([])
const groups = ref<any[]>([])
const activities = ref<any[]>([])
const classrooms = ref<any[]>([])
const events = ref<any[]>([])
const pinnedAnnouncements = ref<any[]>([])
const isLoading = ref(true)
const isLoadingTab = ref(false)
const error = ref<string | null>(null)
const currentTab = ref('feed')
const isAcademyAdmin = ref(false)
const isMemberActionLoading = ref(false)
const gamificationSummary = ref<any>(null)
const isGamificationLoading = ref(true)
const showMobileLeftDrawer = ref(false)
const showMobileRightDrawer = ref(false)
const mainContentRef = ref<HTMLElement | null>(null)

// Group creation state
const showCreateGroupModal = ref(false)

// Event creation state
const showCreateEventModal = ref(false)
const newEvent = ref({
  title: '',
  description: '',
  event_type: 'activity',
  start_datetime: '',
  end_datetime: '',
  location: '',
  location_type: 'onsite',
  max_participants: null as number | null,
  requires_registration: false,
})
const isCreatingEvent = ref(false)

// Events pagination
const eventsPagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
})
const isLoadingMoreEvents = ref(false)

// Invite member modal state
const showInviteMemberModal = ref(false)
const pendingRequests = ref<any[]>([])
const isLoadingPendingRequests = ref(false)

// Pagination State
const membersPagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
})
const isLoadingMoreMembers = ref(false)

// Activities pagination state
const activitiesPagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
})
const isLoadingMoreActivities = ref(false)
const activitiesNextPageUrl = ref<string | null>(null)

// Computed
const academyName = computed(() => route.params.name as string)

// Check if current route is a child route (e.g., /admin, /dashboard)
const isChildRoute = computed(() => {
  const basePath = `/academies/${academyName.value}`
  const decodedBasePath = `/academies/${academyName.value}`
  return route.path !== basePath && route.path !== decodedBasePath && 
         (route.path.startsWith(basePath + '/') || route.path.startsWith(decodedBasePath + '/'))
})

const logoUrl = computed(() => {
  if (!academy.value?.logo) {
    return `${config.public.apiBase}/storage/images/academies/logos/default_logo.png`
  }
  if (academy.value.logo.startsWith('http')) {
    return academy.value.logo
  }
  return academy.value.logo
})

const coverUrl = computed(() => {
  if (!academy.value?.cover) {
    return `${config.public.apiBase}/storage/images/academies/covers/default_cover.png`
  }
  if (academy.value.cover.startsWith('http')) {
    return academy.value.cover
  }
  return academy.value.cover
})

const academyVerified = computed(() => {
  return Boolean(
    academy.value?.verified ||
    academy.value?.is_verified ||
    academy.value?.email_verified_at,
  )
})

const academyHandle = computed(() => {
  if (!academy.value) return ''
  return academy.value.slug || academyName.value
})

const memberStatusText = computed(() => {
  if (!academy.value) return null
  
  // Don't show member status for admins/owners
  if (academy.value.authIsAcademyAdmin) return null

  const status = academy.value.memberStatus
  if (status === null || status === undefined) return null
  
  // Status values: 1=Pending, 2=Approved, 3=Rejected, 4=Invited, 5=Suspended
  if (status === 1 || status === 'pending') return { text: 'รอการอนุมัติ', color: 'bg-yellow-500' }
  if (status === 2 || status === 'approved' || status === 'member') return { text: 'สมาชิก', color: 'bg-green-500' }
  if (status === 3 || status === 'rejected') return { text: 'ถูกปฏิเสธ', color: 'bg-red-500' }
  if (status === 4 || status === 'invited') return { text: 'ได้รับเชิญ', color: 'bg-blue-500' }
  if (status === 5 || status === 'suspended') return { text: 'ถูกระงับ', color: 'bg-gray-500' }
  return null
})

const canJoin = computed(() => {
  return academy.value && !academy.value.memberStatus && !academy.value.authIsAcademyAdmin
})

const canLeave = computed(() => {
  return academy.value && academy.value.memberStatus && !academy.value.authIsAcademyAdmin
})

// Tabs
const tabs = [
  { id: 'feed', label: 'Feed', icon: 'fluent:feed-24-regular' },
  { id: 'courses', label: 'Courses', icon: 'fluent:book-24-regular' },
  { id: 'members', label: 'Members', icon: 'fluent:people-24-regular' },
  { id: 'classrooms', label: 'Classrooms', icon: 'fluent:board-24-regular' },
  { id: 'events', label: 'Events', icon: 'fluent:calendar-star-24-regular' },
  { id: 'groups', label: 'Groups', icon: 'fluent:people-community-24-regular' },
  { id: 'about', label: 'About', icon: 'fluent:info-24-regular' },
]

const formatCompactNumber = (value?: number | null) => {
  if (value == null) return null
  return new Intl.NumberFormat('th-TH').format(value)
}

const heroStats = computed(() => {
  if (!academy.value) return []

  return [
    {
      id: 'members',
      icon: 'fluent:people-24-regular',
      label: 'สมาชิก',
      value: formatCompactNumber(academy.value.total_students || membersPagination.value.total || 0),
    },
    {
      id: 'courses',
      icon: 'fluent:book-24-regular',
      label: 'รายวิชา',
      value: formatCompactNumber(academy.value.courses_offered || courses.value.length || 0),
    },
    {
      id: 'groups',
      icon: 'fluent:people-community-24-regular',
      label: 'ส่วนงาน',
      value: formatCompactNumber(groups.value.length || 0),
    },
  ].filter((item) => item.value !== null)
})

const getTabCount = (tabId: string) => {
  const counts: Record<string, number | null> = {
    feed: activitiesPagination.value.total || activities.value.length || null,
    courses: academy.value?.courses_offered || courses.value.length || null,
    members: academy.value?.total_students || membersPagination.value.total || members.value.length || null,
    classrooms: classrooms.value.length || null,
    events: eventsPagination.value.total || events.value.length || null,
    groups: groups.value.length || null,
  }

  return counts[tabId]
}

const shareAcademy = async () => {
  if (!academy.value || !import.meta.client) return

  const url = window.location.href

  try {
    if (navigator.share) {
      await navigator.share({
        title: academy.value.name,
        text: academy.value.slogan || `มาดูหน้าโรงเรียน ${academy.value.name} กัน`,
        url,
      })
      return
    }

    await navigator.clipboard.writeText(url)
    await Swal.fire({
      icon: 'success',
      title: 'คัดลอกลิงก์แล้ว',
      timer: 1800,
      showConfirmButton: false,
    })
  } catch (err) {
    console.error('Failed to share academy page:', err)
  }
}

const loadPinnedAnnouncements = async () => {
  if (!academy.value) return

  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/announcements`)
    const rows = response?.data || response?.announcements || []
    pinnedAnnouncements.value = rows
      .filter((row: any) => row?.is_pinned)
      .slice(0, 3)
  } catch (err) {
    console.error('Failed to load pinned announcements:', err)
  }
}

const escapeAnnouncementHtml = (value: string) => {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

const openPinnedAnnouncement = async (announcementId: number) => {
  const selected = pinnedAnnouncements.value.find((item: any) => item.id === announcementId)
  if (!selected) return

  await Swal.fire({
    title: selected.title,
    html: `
      <div style="text-align:left;white-space:pre-line;line-height:1.7;color:#4b5563;">
        ${escapeAnnouncementHtml(selected.content || '')}
      </div>
    `,
    width: 720,
    confirmButtonText: 'ปิด',
  })
}

// Methods
const fetchGamificationSummary = async () => {
  if (!academy.value) return
  isGamificationLoading.value = true
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/gamification/summary`)
    if (response.success) {
      gamificationSummary.value = response.data
      academy.value.level = response.data.level
      academy.value.xp_to_next = response.data.xp_to_next
      academy.value.progress_pct = response.data.progress_pct
    }
  } catch (err) {
    console.error('Failed to fetch gamification summary:', err)
  } finally {
    isGamificationLoading.value = false
  }
}

const switchToNextTab = () => {
  const i = tabs.findIndex(t => t.id === currentTab.value)
  if (i < tabs.length - 1) switchTab(tabs[i + 1].id)
}

const switchToPreviousTab = () => {
  const i = tabs.findIndex(t => t.id === currentTab.value)
  if (i > 0) switchTab(tabs[i - 1].id)
}

useSwipe(mainContentRef, {
  onSwipeLeft: switchToNextTab,
  onSwipeRight: switchToPreviousTab,
})

const fetchAcademy = async () => {
  if (!user.value) return
  
  isLoading.value = true
  error.value = null
  
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    
    if (response.success) {
      academy.value = JSON.parse(JSON.stringify(response.academy))
      isAcademyAdmin.value = response.isAcademyAdmin || false
      
      // Parallel fetch gamification summary
      await fetchGamificationSummary()
      await loadPinnedAnnouncements()
      
      // Auto-fetch activities for the default tab (feed)
      await fetchActivities()
    } else {
      error.value = response.message || 'ไม่พบข้อมูลโรงเรียน'
    }
  } catch (err: any) {
    console.error('Failed to fetch academy:', err)
    error.value = err?.data?.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล'
  } finally {
    isLoading.value = false
  }
}

const fetchCourses = async () => {
  if (!academy.value) return
  
  isLoadingTab.value = true
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/courses`)
    if (response.success) {
      courses.value = JSON.parse(JSON.stringify(response.courses || []))
    }
  } catch (err) {
    console.error('Failed to fetch courses:', err)
  } finally {
    isLoadingTab.value = false
  }
}

const fetchMembers = async (page = 1) => {
  if (!academy.value) return
  
  if (page === 1) {
    isLoadingTab.value = true
  } else {
    isLoadingMoreMembers.value = true
  }

  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/members?page=${page}`)
    if (response.success) {
      // Handle potential different response structures (paginated object vs array)
      const data = response.members?.data || response.members || []
      const newMembers = JSON.parse(JSON.stringify(data))
      
      if (page === 1) {
        members.value = newMembers
      } else {
        members.value = [...members.value, ...newMembers]
      }

      if (response.pagination) {
        membersPagination.value = response.pagination
      }
    }
  } catch (err) {
    console.error('Failed to fetch members:', err)
  } finally {
    isLoadingTab.value = false
    isLoadingMoreMembers.value = false
  }
}

const loadMoreMembers = () => {
  if (membersPagination.value.current_page < membersPagination.value.last_page) {
    fetchMembers(membersPagination.value.current_page + 1)
  }
}

const fetchGroups = async () => {
  if (!academy.value) return
  
  isLoadingTab.value = true
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/groups`)
    if (response.success) {
      groups.value = JSON.parse(JSON.stringify(response.groups || []))
    }
  } catch (err) {
    console.error('Failed to fetch groups:', err)
  } finally {
    isLoadingTab.value = false
  }
}

const fetchActivities = async (page = 1, append = false) => {
  if (!academy.value) return
  
  if (page === 1) {
    isLoadingTab.value = true
  } else {
    isLoadingMoreActivities.value = true
  }
  
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/activities?page=${page}`)
    if (response.success) {
      const newActivities = JSON.parse(JSON.stringify(response.activities?.data || response.activities || []))
      
      if (append) {
        activities.value = [...activities.value, ...newActivities]
      } else {
        activities.value = newActivities
      }
      
      // Update pagination info
      if (response.activities?.current_page) {
        activitiesPagination.value = {
          current_page: response.activities.current_page,
          last_page: response.activities.last_page,
          total: response.activities.total
        }
        activitiesNextPageUrl.value = response.activities.next_page_url || null
      }
    }
  } catch (err) {
    console.error('Failed to fetch activities:', err)
  } finally {
    isLoadingTab.value = false
    isLoadingMoreActivities.value = false
  }
}

const loadMoreActivities = () => {
  if (activitiesPagination.value.current_page < activitiesPagination.value.last_page) {
    fetchActivities(activitiesPagination.value.current_page + 1, true)
  }
}

const hasMoreActivities = computed(() => {
  return activitiesPagination.value.current_page < activitiesPagination.value.last_page
})

// Classroom detail state
const selectedClassroom = ref<any>(null)
const classroomMembers = ref<any[]>([])
const classroomStudents = ref<any[]>([])
const isLoadingClassroomDetail = ref(false)

// Fetch classrooms with member counts
const fetchClassrooms = async () => {
  if (!academy.value) return
  
  isLoadingTab.value = true
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/classrooms?include_members=1`)
    if (response.success) {
      classrooms.value = JSON.parse(JSON.stringify(response.data || response.classrooms || []))
    }
  } catch (err) {
    console.error('Failed to fetch classrooms:', err)
  } finally {
    isLoadingTab.value = false
  }
}

// Fetch classroom detail with members
const fetchClassroomDetail = async (classroom: any) => {
  if (!academy.value) return
  
  selectedClassroom.value = classroom
  isLoadingClassroomDetail.value = true
  
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/classrooms/${classroom.id}`)
    if (response.success) {
      const classroomData = response.classroom || response.data
      selectedClassroom.value = JSON.parse(JSON.stringify(classroomData))
      classroomMembers.value = JSON.parse(JSON.stringify(classroomData?.members || response.members || []))
      classroomStudents.value = JSON.parse(JSON.stringify(classroomData?.students || response.students || []))
    }
  } catch (err) {
    console.error('Failed to fetch classroom detail:', err)
  } finally {
    isLoadingClassroomDetail.value = false
  }
}

const closeClassroomDetail = () => {
  selectedClassroom.value = null
  classroomMembers.value = []
  classroomStudents.value = []
}

const getStudentGender = (gender: number | null) => {
  if (gender === 1) return { label: 'ชาย', color: 'text-blue-500', icon: 'fluent:person-24-regular' }
  if (gender === 0) return { label: 'หญิง', color: 'text-pink-500', icon: 'fluent:person-24-regular' }
  return { label: '-', color: 'text-gray-400', icon: 'fluent:person-24-regular' }
}

const getMemberRoleInfo = (role: string) => {
  switch (role) {
    case 'teacher': return { label: 'ครูประจำชั้น', color: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400', icon: 'fluent:hat-graduation-24-regular' }
    case 'co_teacher': return { label: 'ครูผู้ช่วย', color: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400', icon: 'fluent:person-support-24-regular' }
    case 'student': return { label: 'นักเรียน', color: 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400', icon: 'fluent:person-24-regular' }
    case 'observer': return { label: 'ผู้สังเกตการณ์', color: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400', icon: 'fluent:eye-24-regular' }
    default: return { label: role, color: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400', icon: 'fluent:person-24-regular' }
  }
}

// Fetch events (กิจกรรม)
const fetchEvents = async (page = 1) => {
  if (!academy.value) return
  
  if (page === 1) {
    isLoadingTab.value = true
  } else {
    isLoadingMoreEvents.value = true
  }
  
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/events?page=${page}&per_page=10`)
    if (response.success) {
      const newEvents = JSON.parse(JSON.stringify(response.data || []))
      
      if (page === 1) {
        events.value = newEvents
      } else {
        events.value = [...events.value, ...newEvents]
      }
      
      if (response.meta) {
        eventsPagination.value = {
          current_page: response.meta.current_page,
          last_page: response.meta.last_page,
          total: response.meta.total
        }
      }
    }
  } catch (err) {
    console.error('Failed to fetch events:', err)
  } finally {
    isLoadingTab.value = false
    isLoadingMoreEvents.value = false
  }
}

const loadMoreEvents = () => {
  if (eventsPagination.value.current_page < eventsPagination.value.last_page) {
    fetchEvents(eventsPagination.value.current_page + 1)
  }
}

const hasMoreEvents = computed(() => {
  return eventsPagination.value.current_page < eventsPagination.value.last_page
})

// Create event
const createEvent = async () => {
  if (!academy.value || !newEvent.value.title.trim() || isCreatingEvent.value) return
  
  isCreatingEvent.value = true
  try {
    const response: any = await api.post(`/api/academies/${academy.value.id}/events`, {
      title: newEvent.value.title,
      description: newEvent.value.description,
      event_type: newEvent.value.event_type,
      start_datetime: newEvent.value.start_datetime,
      end_datetime: newEvent.value.end_datetime,
      location: newEvent.value.location,
      location_type: newEvent.value.location_type,
      max_participants: newEvent.value.max_participants,
      requires_registration: newEvent.value.requires_registration,
      status: 'published',
    })
    
    if (response.success) {
      // Add new event to list
      if (response.data) {
        events.value.unshift(JSON.parse(JSON.stringify(response.data)))
      } else {
        await fetchEvents()
      }
      showCreateEventModal.value = false
      newEvent.value = {
        title: '',
        description: '',
        event_type: 'activity',
        start_datetime: '',
        end_datetime: '',
        location: '',
        location_type: 'onsite',
        max_participants: null,
        requires_registration: false,
      }
      
      Swal.fire({
        icon: 'success',
        title: 'สร้างกิจกรรมสำเร็จ',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err?.data?.message || 'ไม่สามารถสร้างกิจกรรมได้',
    })
  } finally {
    isCreatingEvent.value = false
  }
}

// Register for event
const registerForEvent = async (event: any) => {
  if (!academy.value) return
  
  try {
    const response: any = await api.post(`/api/academies/${academy.value.id}/events/${event.id}/register`, {})
    if (response.success) {
      event.registration_status = 'pending'
      event.registered_count = (event.registered_count || 0) + 1
      Swal.fire({
        icon: 'success',
        title: 'ลงทะเบียนสำเร็จ',
        text: 'คุณได้ลงทะเบียนเข้าร่วมกิจกรรมแล้ว',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err?.data?.message || 'ไม่สามารถลงทะเบียนได้',
    })
  }
}

// Cancel event registration
const cancelEventRegistration = async (event: any) => {
  if (!academy.value) return
  
  const result = await Swal.fire({
    title: 'ยกเลิกการลงทะเบียน?',
    text: 'ต้องการยกเลิกการเข้าร่วมกิจกรรมนี้หรือไม่?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'ยกเลิก',
    cancelButtonText: 'ไม่ใช่'
  })
  
  if (!result.isConfirmed) return
  
  try {
    const response: any = await api.post(`/api/academies/${academy.value.id}/events/${event.id}/cancel-registration`, {})
    if (response.success) {
      event.registration_status = null
      event.registered_count = Math.max(0, (event.registered_count || 1) - 1)
      Swal.fire({
        icon: 'success',
        title: 'ยกเลิกสำเร็จ',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err?.data?.message || 'ไม่สามารถยกเลิกได้',
    })
  }
}

// Event type info helper
const getEventTypeInfo = (type: string) => {
  const types: Record<string, { label: string; icon: string; color: string }> = {
    'meeting': { label: 'การประชุม', icon: 'fluent:people-team-24-regular', color: 'from-blue-400 to-blue-600' },
    'holiday': { label: 'วันหยุด', icon: 'fluent:beach-24-regular', color: 'from-yellow-400 to-yellow-600' },
    'exam': { label: 'การสอบ', icon: 'fluent:document-text-24-regular', color: 'from-red-400 to-red-600' },
    'activity': { label: 'กิจกรรม', icon: 'fluent:calendar-star-24-regular', color: 'from-green-400 to-green-600' },
    'sports': { label: 'กีฬา', icon: 'fluent:sport-24-regular', color: 'from-orange-400 to-orange-600' },
    'ceremony': { label: 'พิธีการ', icon: 'fluent:hat-graduation-24-regular', color: 'from-purple-400 to-purple-600' },
    'other': { label: 'อื่นๆ', icon: 'fluent:calendar-24-regular', color: 'from-gray-400 to-gray-600' },
  }
  return types[type] || types['activity']
}

const formatEventDate = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('th-TH', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const switchTab = async (tabId: string) => {
  currentTab.value = tabId
  
  switch (tabId) {
    case 'feed':
      if (pinnedAnnouncements.value.length === 0) await loadPinnedAnnouncements()
      if (activities.value.length === 0) await fetchActivities()
      break
    case 'courses':
      if (courses.value.length === 0) await fetchCourses()
      break
    case 'members':
      if (members.value.length === 0) await fetchMembers()
      // Also fetch pending requests if admin
      if (academy.value?.authIsAcademyAdmin && pendingRequests.value.length === 0) {
        await fetchPendingRequests()
      }
      break
    case 'classrooms':
      if (classrooms.value.length === 0) await fetchClassrooms()
      break
    case 'events':
      if (events.value.length === 0) await fetchEvents()
      break
    case 'groups':
      if (groups.value.length === 0) await fetchGroups()
      break
    case 'about':
      break
  }
}

// Fetch pending member requests (for admin)
const fetchPendingRequests = async () => {
  if (!academy.value || !academy.value.authIsAcademyAdmin) return
  
  isLoadingPendingRequests.value = true
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/pending-requests`)
    if (response.success) {
      pendingRequests.value = JSON.parse(JSON.stringify(response.pendingRequests || []))
    }
  } catch (err) {
    console.error('Failed to fetch pending requests:', err)
  } finally {
    isLoadingPendingRequests.value = false
  }
}

// Accept member request
const acceptMemberRequest = async (request: any) => {
  try {
    const response: any = await api.post(`/api/academies/${academy.value.id}/members/${request.id}/accept`, {})
    if (response.success) {
      // Remove from pending and add to members
      pendingRequests.value = pendingRequests.value.filter(r => r.id !== request.id)
      await fetchMembers() // Refresh members list
      
      Swal.fire({
        icon: 'success',
        title: 'อนุมัติสำเร็จ',
        text: `${request.user?.name} เป็นสมาชิกแล้ว`,
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: 'ไม่สามารถอนุมัติได้',
    })
  }
}

// Reject member request
const rejectMemberRequest = async (request: any) => {
  const result = await Swal.fire({
    title: 'ยืนยันการปฏิเสธ?',
    text: `ต้องการปฏิเสธคำขอจาก ${request.user?.name} หรือไม่?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'ปฏิเสธ',
    cancelButtonText: 'ยกเลิก'
  })
  
  if (!result.isConfirmed) return
  
  try {
    const response: any = await api.post(`/api/academies/${academy.value.id}/members/${request.id}/reject`, {})
    if (response.success) {
      pendingRequests.value = pendingRequests.value.filter(r => r.id !== request.id)
      
      Swal.fire({
        icon: 'success',
        title: 'ปฏิเสธเรียบร้อย',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: 'ไม่สามารถปฏิเสธได้',
    })
  }
}

// Handle member invited callback
const onMemberInvited = () => {
  // Could refresh members or pending invitations here if needed
}

// Handle academy post created
const handleAcademyPostCreated = async (post: any) => {
  // Add the new post to the beginning of activities
  if (post) {
    activities.value.unshift(JSON.parse(JSON.stringify(post)))
  } else {
    // Refresh activities if post data not returned
    await fetchActivities()
  }
}

// Handle post deleted from feed
const handlePostDeleted = (deletedId: any) => {
  // FeedPost emits the activity id directly (props.post.id)
  if (deletedId) {
    activities.value = activities.value.filter((activity: any) => activity.id !== deletedId)
  }
}

// Handle post updated in feed
const handlePostUpdated = (updatedPost: any) => {
  const postId = updatedPost?.id || updatedPost?.target_resource?.id
  if (postId) {
    const index = activities.value.findIndex((activity: any) => {
      const activityPostId = activity.target_resource?.id || activity.id
      return activityPostId === postId
    })
    if (index !== -1) {
      // Update the activity with new data
      if (activities.value[index].target_resource) {
        activities.value[index].target_resource = {
          ...activities.value[index].target_resource,
          ...updatedPost
        }
      } else {
        activities.value[index] = { ...activities.value[index], ...updatedPost }
      }
    }
  }
}

const requestMembership = async () => {
  if (!academy.value || isMemberActionLoading.value) return
  
  isMemberActionLoading.value = true
  try {
    const response: any = await api.post(`/api/academies/${academy.value.id}/members`, {})
    if (response.success) {
      academy.value.memberStatus = response.memberStatus
      academy.value.total_students = response.totalStudents
      
      Swal.fire({
        icon: 'success',
        title: 'สำเร็จ',
        text: 'ขอเป็นสมาชิกเรียบร้อยแล้ว',
      })
    } else {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: response.msg || 'ไม่สามารถขอเป็นสมาชิกได้',
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err?.data?.message || 'ไม่สามารถขอเป็นสมาชิกได้',
    })
  } finally {
    isMemberActionLoading.value = false
  }
}

const cancelMembership = async () => {
  if (!academy.value || isMemberActionLoading.value) return
  
  const result = await Swal.fire({
    title: 'ยืนยันการออกจากโรงเรียน?',
    text: 'คุณต้องการออกจากการเป็นสมาชิกหรือไม่?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'ออกจากการเป็นสมาชิก',
    cancelButtonText: 'ยกเลิก'
  })
  
  if (!result.isConfirmed) return
  
  isMemberActionLoading.value = true
  try {
    const response: any = await api.post(`/api/academies/${academy.value.id}/unmembers`, {})
    if (response.success) {
      if (academy.value.memberStatus === 2) {
        academy.value.total_students--
      }
      academy.value.memberStatus = null
      
      Swal.fire({
        icon: 'success',
        title: 'สำเร็จ',
        text: 'ออกจากการเป็นสมาชิกเรียบร้อยแล้ว',
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: 'ไม่สามารถออกจากการเป็นสมาชิกได้',
    })
  } finally {
    isMemberActionLoading.value = false
  }
}

const getAcademyTypeInfo = (type: string | null) => {
  const typeMap: Record<string, { label: string; icon: string; color: string }> = {
    'public': { label: 'รัฐบาล', icon: 'fluent:building-government-24-regular', color: 'text-blue-500' },
    'private': { label: 'เอกชน', icon: 'fluent:building-bank-24-regular', color: 'text-purple-500' },
    'foundation': { label: 'มูลนิธิ', icon: 'fluent:heart-24-regular', color: 'text-pink-500' },
    'international': { label: 'นานาชาติ', icon: 'fluent:globe-24-regular', color: 'text-green-500' },
  }
  return typeMap[type || ''] || { label: 'ทั่วไป', icon: 'fluent:building-24-regular', color: 'text-gray-500' }
}



const getGroupTypeInfo = (type: string) => {
  const types: Record<string, { label: string; icon: string; color: string }> = {
    'department': { label: 'แผนก', icon: 'fluent:building-24-regular', color: 'from-blue-400 to-blue-600' },
    'classroom': { label: 'ห้องเรียน', icon: 'fluent:board-24-regular', color: 'from-green-400 to-green-600' },
    'club': { label: 'ชมรม', icon: 'fluent:star-24-regular', color: 'from-orange-400 to-orange-600' },
  }
  return types[type] || types['classroom']
}

const formatDate = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('th-TH', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Lifecycle
onMounted(() => {
  if (user.value) {
    fetchAcademy()
  }
  const hash = route.hash.replace('#', '')
  if (tabs.some(t => t.id === hash)) {
    switchTab(hash)
  }
})

watch(() => route.hash, (newHash) => {
  const hash = newHash.replace('#', '')
  if (tabs.some(t => t.id === hash)) {
    switchTab(hash)
  }
})
</script>

<template>
  <div>
    <!-- Child Route Content (admin, dashboard, etc.) -->
    <NuxtPage v-if="isChildRoute" />
    
    <!-- Main Academy Page Content -->
    <div v-else class="min-h-screen bg-gray-200 dark:bg-vikinger-dark-300">
    <!-- Loading State -->
    <div v-if="isLoading" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <Icon icon="svg-spinners:ring-resize" class="w-12 h-12 text-vikinger-purple mx-auto mb-4" />
        <p class="text-gray-600 dark:text-gray-400">กำลังโหลดข้อมูลโรงเรียน...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="flex items-center justify-center min-h-screen">
      <div class="text-center p-8 max-w-md">
        <Icon icon="fluent:warning-24-regular" class="w-16 h-16 text-yellow-500 mx-auto mb-4" />
        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-2">ไม่พบข้อมูลโรงเรียน</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">{{ error }}</p>
        <NuxtLink 
          to="/newsfeed" 
          class="inline-flex items-center gap-2 px-6 py-3 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/90 transition-colors"
        >
          <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
          กลับหน้าหลัก
        </NuxtLink>
      </div>
    </div>

    <!-- Academy Content -->
    <div v-else-if="academy" class="max-w-7xl mx-auto px-4 py-6">
      <!-- Emergency Alerts -->
      <SchoolEmergencyAlertBanner :academy-id="academy.id" />

      <!-- School Attendance Widget (role-aware) -->
      <SchoolAttendanceWidget
        v-if="!isChildRoute"
        :academy-id="academy.id"
        :academy-name="academyName"
      />

      <!-- Cover & Profile Section -->
      <div class="relative mb-6 overflow-hidden rounded-[28px] border border-white/60 bg-white shadow-lg dark:border-gray-700 dark:bg-vikinger-dark-200">
        <!-- Cover Image -->
        <div 
          class="relative h-48 bg-gray-300 bg-cover bg-center dark:bg-gray-700 md:h-[180px]"
          :style="{ backgroundImage: `url(${coverUrl})` }"
        >
          <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.18),transparent_30%),linear-gradient(180deg,rgba(17,24,39,0.05),rgba(17,24,39,0.35)_72%,rgba(17,24,39,0.72))]"></div>
          <div class="absolute inset-0 opacity-20 mix-blend-soft-light" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 20px 20px;"></div>
        </div>
        
        <!-- Profile Info -->
        <div class="relative px-4 pb-6 md:px-8">
          <!-- Logo -->
          <div class="absolute -top-14 left-4 md:left-8">
            <div class="h-28 w-28 overflow-hidden rounded-2xl border-[6px] border-white bg-white shadow-xl dark:border-vikinger-dark-200 md:h-32 md:w-32">
              <img 
                :src="logoUrl" 
                :alt="academy.name"
                class="w-full h-full object-cover"
              />
            </div>
          </div>
          
          <!-- Info & Actions -->
          <div class="flex flex-col gap-5 pt-16 md:flex-row md:items-end md:justify-between md:pt-5 md:pl-40">
            <!-- Academy Info -->
            <div class="mb-4 md:mb-0">
              <div class="mb-2 flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white md:text-3xl">
                  {{ academy.name }}
                </h1>
                <Icon
                  v-if="academyVerified"
                  icon="heroicons:check-badge-solid"
                  class="h-6 w-6 text-vikinger-cyan"
                />
                <span
                  v-if="academy.level"
                  class="inline-flex items-center rounded-full bg-vikinger-purple/10 px-3 py-1 text-xs font-bold text-vikinger-purple"
                >
                  เลเวล {{ academy.level }}
                </span>
              </div>
              <p v-if="academy.slogan" class="text-gray-600 dark:text-gray-400 mb-3">
                {{ academy.slogan }}
              </p>
              <div class="mb-3 text-sm text-gray-400 dark:text-gray-500">
                @{{ academyHandle }}
              </div>
              
              <!-- Stats -->
              <div class="flex flex-wrap gap-2.5">
                <div class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white/90 px-3 py-2 text-sm text-gray-600 shadow-sm dark:border-gray-700 dark:bg-vikinger-dark-100 dark:text-gray-300">
                  <Icon :icon="getAcademyTypeInfo(academy.type).icon" :class="['h-4 w-4', getAcademyTypeInfo(academy.type).color]" />
                  <span class="font-medium">{{ getAcademyTypeInfo(academy.type).label }}</span>
                </div>
                <div
                  v-for="stat in heroStats"
                  :key="stat.id"
                  class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white/90 px-3 py-2 text-sm text-gray-600 shadow-sm dark:border-gray-700 dark:bg-vikinger-dark-100 dark:text-gray-300"
                >
                  <Icon :icon="stat.icon" class="h-4 w-4 text-vikinger-purple" />
                  <span class="font-semibold text-gray-900 dark:text-white">{{ stat.value }}</span>
                  <span>{{ stat.label }}</span>
                </div>
              </div>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center gap-3">
              <!-- Member Status Badge -->
              <span 
                v-if="memberStatusText" 
                :class="['px-3 py-1.5 rounded-full text-sm font-medium text-white', memberStatusText.color]"
              >
                {{ memberStatusText.text }}
              </span>
              
              <!-- Admin Badge & Button -->
              <NuxtLink 
                v-if="academy.authIsAcademyAdmin" 
                :to="`/academies/${academyName}/admin`"
                class="px-4 py-2 rounded-lg text-sm font-medium bg-vikinger-purple text-white flex items-center gap-2 hover:bg-vikinger-purple/90 transition-colors"
              >
                <Icon icon="fluent:settings-24-regular" class="w-4 h-4" />
                จัดการโรงเรียน
              </NuxtLink>

              <button
                type="button"
                class="px-4 py-2 rounded-lg text-sm font-medium bg-white dark:bg-vikinger-dark-100 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 flex items-center gap-2 hover:bg-gray-50 dark:hover:bg-vikinger-dark-300 transition-colors"
                @click="shareAcademy"
              >
                <Icon icon="heroicons:share" class="w-4 h-4" />
                แชร์
              </button>
              
              <!-- Join Button -->
              <button
                v-if="canJoin"
                @click="requestMembership"
                :disabled="isMemberActionLoading"
                class="px-5 py-2.5 bg-vikinger-purple text-white rounded-lg font-medium hover:bg-vikinger-purple/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <Icon v-if="isMemberActionLoading" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                <Icon v-else icon="fluent:person-add-24-regular" class="w-4 h-4" />
                เข้าร่วมโรงเรียน
              </button>
              
              <!-- Member Settings Button (replaces Leave button) -->
              <NuxtLink
                v-if="canLeave"
                :to="`/academies/${academy.name}/my-settings`"
                class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors flex items-center gap-2"
              >
                <Icon icon="fluent:settings-24-regular" class="w-4 h-4" />
                ตั้งค่าการเป็นสมาชิก
              </NuxtLink>
            </div>
          </div>
        </div>
        
        <!-- Tabs -->
        <div class="border-t border-gray-200 dark:border-gray-700">
          <div class="flex overflow-x-auto px-2 md:px-4">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="switchTab(tab.id)"
              :class="[
                'flex items-center gap-2 px-6 py-4 text-sm font-medium transition-colors whitespace-nowrap',
                currentTab === tab.id 
                  ? 'text-vikinger-purple border-b-2 border-vikinger-purple' 
                  : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
              ]"
            >
              <Icon :icon="tab.icon" class="w-5 h-5" />
              {{ tab.label }}
              <span
                v-if="getTabCount(tab.id)"
                class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-300"
              >
                {{ formatCompactNumber(getTabCount(tab.id)) }}
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Drawer Triggers -->
      <div class="flex gap-3 mb-6 lg:hidden">
        <button
          type="button"
          class="flex-1 px-4 py-3 bg-white dark:bg-vikinger-dark-200 text-gray-800 dark:text-gray-200 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-bold flex items-center justify-center gap-2 shadow-sm active:bg-gray-50 dark:active:bg-vikinger-dark-100"
          @click="showMobileLeftDrawer = true"
        >
          <Icon icon="heroicons:bars-3" class="w-4 h-4 text-vikinger-purple" />
          เมนูลัด
        </button>
        <button
          type="button"
          class="flex-1 px-4 py-3 bg-white dark:bg-vikinger-dark-200 text-gray-800 dark:text-gray-200 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-bold flex items-center justify-center gap-2 shadow-sm active:bg-gray-50 dark:active:bg-vikinger-dark-100"
          @click="showMobileRightDrawer = true"
        >
          <Icon icon="heroicons:chart-bar" class="w-4 h-4 text-vikinger-purple" />
          สถิติและกิจกรรม
        </button>
      </div>
      
      <!-- Tab Content -->
      <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-[260px_minmax(0,1fr)] lg:gap-5 xl:grid-cols-[260px_minmax(0,1fr)_320px]">
        <!-- Left Sidebar -->
        <aside class="hidden lg:block lg:sticky lg:top-[86px] lg:space-y-5">
          <SchoolQuickMenu
            v-if="academy"
            :academy="academy"
            :is-pending="academy?.memberStatus === 1 || academy?.memberStatus === 'pending'"
            @join="requestMembership"
            @navigate="switchTab"
          />

          <SchoolLevelCard
            v-if="gamificationSummary"
            :level="gamificationSummary.level"
            :total-xp="gamificationSummary.total_xp"
            :xp-to-next="gamificationSummary.xp_to_next"
            :progress-pct="gamificationSummary.progress_pct"
          />
        </aside>

        <!-- Main Content -->
        <main ref="mainContentRef" class="min-w-0">
          <!-- Loading Tab Content -->
          <div v-if="isLoadingTab" class="space-y-3">
            <template v-if="currentTab === 'feed'">
              <PlayFeedPostSkeleton v-for="i in 3" :key="i" />
            </template>
            <template v-else-if="currentTab === 'groups'">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <AcademyGroupsGroupCardSkeleton v-for="i in 4" :key="i" />
              </div>
            </template>
            <template v-else-if="currentTab === 'members'">
              <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden divide-y divide-gray-100 dark:divide-gray-700">
                <AcademyGroupsMemberRowSkeleton v-for="i in 5" :key="i" />
              </div>
            </template>
            <template v-else>
              <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">
                <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-vikinger-purple mx-auto" />
              </div>
            </template>
          </div>
          
          <!-- Feed Tab -->
          <div v-else-if="currentTab === 'feed'" class="space-y-1.5 sm:space-y-2 md:space-y-3">
            <!-- Post Composer (for members & admins only) - Using CreatePostBox -->
            <PlayFeedCreatePostBox 
              v-if="academy.memberStatus === 2 || academy.authIsAcademyAdmin"
              context="academy"
              :context-id="academy.id"
              :context-name="academy.name"
              @post-created="handleAcademyPostCreated"
            />

            <SchoolPinnedAnnouncement
              v-for="announcement in pinnedAnnouncements"
              :key="announcement.id"
              :announcement="announcement"
              @open="openPinnedAnnouncement"
            />
            
            <div v-if="activities.length === 0" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">
              <Icon icon="fluent:feed-24-regular" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
              <p class="text-gray-500 dark:text-gray-400">ยังไม่มีกิจกรรม</p>
              <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">เริ่มโพสต์เพื่อแชร์ข่าวสารให้กับสมาชิก</p>
            </div>
            
            <!-- Activity/Post Cards - Using FeedPost Component -->
            <FeedPost 
              v-for="activity in activities" 
              :key="activity.id"
              :post="activity"
              @delete-success="handlePostDeleted"
              @post-updated="handlePostUpdated"
            />
            
            <!-- Load More Button -->
            <div v-if="hasMoreActivities" class="text-center py-4">
              <button
                @click="loadMoreActivities"
                :disabled="isLoadingMoreActivities"
                class="px-6 py-2.5 bg-white dark:bg-vikinger-dark-200 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors shadow-sm border border-gray-200 dark:border-gray-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 mx-auto"
              >
                <Icon v-if="isLoadingMoreActivities" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                <Icon v-else icon="fluent:arrow-download-24-regular" class="w-4 h-4" />
                {{ isLoadingMoreActivities ? 'กำลังโหลด...' : 'โหลดเพิ่มเติม' }}
              </button>
            </div>
          </div>
          
          <!-- Courses Tab -->
          <div v-else-if="currentTab === 'courses'" class="space-y-4">
            <!-- Header with Create Button -->
            <div v-if="academy.authIsAcademyAdmin" class="flex justify-end">
              <NuxtLink
                to="/Learn/Courses/create"
                class="px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium text-sm hover:bg-vikinger-purple/90 transition-colors flex items-center gap-2"
              >
                <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
                สร้างรายวิชาใหม่
              </NuxtLink>
            </div>
            
            <div v-if="courses.length === 0" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">
              <Icon icon="fluent:book-24-regular" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
              <p class="text-gray-500 dark:text-gray-400">ยังไม่มีรายวิชา</p>
              <p v-if="academy.authIsAcademyAdmin" class="text-sm text-gray-400 dark:text-gray-500 mt-2">คลิก "สร้างรายวิชาใหม่" เพื่อเริ่มต้น</p>
            </div>
            
            <!-- Courses Grid -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <NuxtLink
                v-for="course in courses"
                :key="course.id"
                :to="`/Learn/Courses/${course.id}`"
                class="block bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm hover:shadow-md transition-all overflow-hidden group"
              >
                <!-- Course Cover -->
                <div class="h-32 bg-gradient-to-br from-vikinger-purple to-vikinger-cyan relative">
                  <img 
                    v-if="course.cover" 
                    :src="course.cover"
                    class="w-full h-full object-cover"
                  />
                  <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </div>
                
                <div class="p-4">
                  <h3 class="font-semibold text-gray-900 dark:text-white mb-1 line-clamp-1 group-hover:text-vikinger-purple transition-colors">
                    {{ course.name }}
                  </h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">{{ course.description }}</p>
                  
                  <!-- Course Info -->
                  <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-3">
                      <span class="flex items-center gap-1">
                        <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
                        {{ course.students_count || 0 }}
                      </span>
                      <span class="flex items-center gap-1">
                        <Icon icon="fluent:book-open-24-regular" class="w-4 h-4" />
                        {{ course.course_lessons_count ?? course.lessons_count ?? course.lessons ?? 0 }} บท
                      </span>
                    </div>
                    <span 
                      :class="[
                        'px-2 py-0.5 rounded-full text-xs',
                        course.status === 'published' ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' :
                        course.status === 'draft' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400' :
                        'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                      ]"
                    >
                      {{ course.status === 'published' ? 'เผยแพร่' : course.status === 'draft' ? 'แบบร่าง' : course.status }}
                    </span>
                  </div>
                </div>
              </NuxtLink>
            </div>
          </div>
          
          <!-- Members Tab -->
          <div v-else-if="currentTab === 'members'" class="space-y-4">
            <!-- Header with Invite Button -->
            <div v-if="academy.authIsAcademyAdmin" class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span v-if="pendingRequests.length > 0" class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full text-sm font-medium">
                  {{ pendingRequests.length }} คำขอรอดำเนินการ
                </span>
              </div>
              <button
                @click="showInviteMemberModal = true"
                class="px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium text-sm hover:bg-vikinger-purple/90 transition-colors flex items-center gap-2"
              >
                <Icon icon="fluent:person-add-24-regular" class="w-5 h-5" />
                เชิญสมาชิก
              </button>
            </div>
            
            <!-- Pending Requests Section (Admin Only) -->
            <div v-if="academy.authIsAcademyAdmin && pendingRequests.length > 0" class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 border border-yellow-200 dark:border-yellow-800">
              <div class="flex items-center gap-2 mb-4">
                <Icon icon="fluent:clock-24-regular" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                <h3 class="font-semibold text-yellow-800 dark:text-yellow-200">คำขอเข้าร่วมที่รอดำเนินการ</h3>
              </div>
              <div class="space-y-3">
                <div
                  v-for="request in pendingRequests"
                  :key="request.id"
                  class="flex items-center justify-between p-3 bg-white dark:bg-vikinger-dark-200 rounded-lg"
                >
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                      <img 
                        v-if="request.user?.profile_photo_url" 
                        :src="request.user.profile_photo_url"
                        :alt="request.user?.name"
                        class="w-full h-full object-cover"
                      />
                      <Icon v-else icon="fluent:person-24-regular" class="w-full h-full p-2 text-gray-400" />
                    </div>
                    <div>
                      <h4 class="font-medium text-gray-900 dark:text-white">{{ request.user?.name }}</h4>
                      <p class="text-sm text-gray-500">@{{ request.user?.reference_code }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <button
                      @click="acceptMemberRequest(request)"
                      class="px-3 py-1.5 bg-green-500 text-white rounded-lg text-sm font-medium hover:bg-green-600 transition-colors flex items-center gap-1"
                    >
                      <Icon icon="fluent:checkmark-24-regular" class="w-4 h-4" />
                      อนุมัติ
                    </button>
                    <button
                      @click="rejectMemberRequest(request)"
                      class="px-3 py-1.5 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600 transition-colors flex items-center gap-1"
                    >
                      <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
                      ปฏิเสธ
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
              <div class="relative">
                <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                <input
                  type="text"
                  placeholder="ค้นหาสมาชิก..."
                  class="w-full pl-10 pr-4 py-2.5 rounded-lg bg-gray-50 dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"
                />
              </div>
            </div>
            
            <CommonEmptyState
              v-if="members.length === 0"
              icon="heroicons:users"
              title="ยังไม่มีสมาชิก"
              description="ยังไม่มีนักเรียนหรือครูเข้าร่วมโรงเรียนนี้"
              :cta-label="academy.authIsAcademyAdmin ? 'เชิญสมาชิก' : undefined"
              cta-icon="fluent:person-add-24-regular"
              @action="showInviteMemberModal = true"
            />
            
            <!-- Members List -->
            <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden">
              <div class="divide-y divide-gray-100 dark:divide-gray-700">
                <div 
                  v-for="member in members"
                  :key="member.id"
                  class="p-4 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"
                >
                  <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                      <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden ring-2 ring-white dark:ring-vikinger-dark-200">
                        <img 
                          v-if="member.member_avatar || member.user?.profile_photo_url" 
                          :src="member.member_avatar || member.user?.profile_photo_url" 
                          :alt="member.member_name"
                          class="w-full h-full object-cover"
                        />
                        <Icon v-else icon="fluent:person-24-regular" class="w-full h-full p-2 text-gray-400" />
                      </div>
                      <div>
                        <h4 class="font-medium text-gray-900 dark:text-white">{{ member.member_name || member.user?.name || 'ไม่ทราบชื่อ' }}</h4>
                        <div class="flex items-center gap-2 text-sm">
                          <span 
                            :class="[
                              'px-2 py-0.5 rounded-full text-xs font-medium',
                              member.status == 1 ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400' :
                              member.status == 3 ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' :
                              member.is_admin ? 'bg-vikinger-purple/10 text-vikinger-purple' :
                              member.is_teacher ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' :
                              'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400'
                            ]"
                          >
                            {{ member.status == 1 ? 'รออนุมัติ' : member.status == 3 ? 'ถูกปฏิเสธ' : member.role_display_name || 'นักเรียน' }}
                          </span>
                          <span v-if="member.student?.class_level" class="text-gray-400">{{ member.student.class_level }}{{ member.student.class_section ? '/' + member.student.class_section : '' }}</span>
                          <span v-else-if="member.enrollment_date" class="text-gray-400">เข้าร่วมเมื่อ {{ formatDate(member.enrollment_date) }}</span>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                      <NuxtLink
                        :to="`/profile/${member.user?.id || member.id}`"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-vikinger-purple"
                        title="ดูโปรไฟล์"
                      >
                        <Icon icon="fluent:person-24-regular" class="w-5 h-5" />
                      </NuxtLink>
                      <button
                        v-if="academy.authIsAcademyAdmin && member.role !== 'admin'"
                        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-500 hover:text-vikinger-purple"
                        title="จัดการสมาชิก"
                      >
                        <Icon icon="fluent:settings-24-regular" class="w-5 h-5" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Load More -->
            <div v-if="membersPagination.current_page < membersPagination.last_page" class="text-center mt-4 pb-4">
              <button 
                @click="loadMoreMembers"
                :disabled="isLoadingMoreMembers"
                class="text-vikinger-purple hover:text-vikinger-purple/80 font-medium text-sm flex items-center justify-center gap-2 mx-auto disabled:opacity-50"
              >
                <Icon v-if="isLoadingMoreMembers" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                <span>{{ isLoadingMoreMembers ? 'กำลังโหลด...' : 'โหลดเพิ่มเติม' }}</span>
              </button>
            </div>
          </div>
          
          <!-- Classrooms Tab (ห้องเรียน) -->
          <div v-else-if="currentTab === 'classrooms'" class="space-y-4">
            <!-- Classroom Detail View -->
            <div v-if="selectedClassroom" class="space-y-4">
              <!-- Back Button & Header -->
              <div class="flex items-center justify-between">
                <button
                  @click="closeClassroomDetail"
                  class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-vikinger-purple transition-colors"
                >
                  <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
                  <span class="font-medium">กลับ</span>
                </button>
                <NuxtLink
                  v-if="academy.authIsAcademyAdmin"
                  :to="`/academies/${academyName}/admin/classrooms`"
                  class="px-3 py-1.5 text-sm text-vikinger-purple hover:bg-vikinger-purple/10 rounded-lg transition-colors flex items-center gap-1"
                >
                  <Icon icon="fluent:settings-24-regular" class="w-4 h-4" />
                  จัดการ
                </NuxtLink>
              </div>

              <!-- Classroom Info Card -->
              <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden">
                <div class="h-20 bg-gradient-to-br from-green-400 to-green-600 flex items-center px-6">
                  <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-lg bg-white/20 backdrop-blur-sm flex items-center justify-center">
                      <Icon icon="fluent:board-24-regular" class="w-7 h-7 text-white" />
                    </div>
                    <div class="text-white">
                      <h3 class="text-xl font-bold">{{ selectedClassroom.name || `${selectedClassroom.grade_level}/${selectedClassroom.section}` }}</h3>
                      <div class="flex items-center gap-3 text-sm text-white/80">
                        <span v-if="selectedClassroom.grade_level">ชั้น {{ selectedClassroom.grade_level }}</span>
                        <span v-if="selectedClassroom.section">ห้อง {{ selectedClassroom.section }}</span>
                        <span v-if="selectedClassroom.classroom_code" class="bg-white/20 rounded px-2 py-0.5 text-xs font-mono">
                          รหัส: {{ selectedClassroom.classroom_code }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="p-4 flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                  <span v-if="selectedClassroom.homeroom_teacher" class="flex items-center gap-1.5">
                    <Icon icon="fluent:person-24-regular" class="w-4 h-4 text-vikinger-purple" />
                    ครูประจำชั้น: {{ selectedClassroom.homeroom_teacher.name }}
                  </span>
                  <span class="flex items-center gap-1.5">
                    <Icon icon="fluent:people-24-regular" class="w-4 h-4 text-green-500" />
                    {{ selectedClassroom.student_count || classroomStudents.length || 0 }} นักเรียน
                  </span>
                  <span v-if="classroomMembers.length > 0" class="flex items-center gap-1.5">
                    <Icon icon="fluent:people-community-24-regular" class="w-4 h-4 text-blue-500" />
                    {{ classroomMembers.length }} สมาชิก (ระบบใหม่)
                  </span>
                  <span v-if="selectedClassroom.room_location" class="flex items-center gap-1.5">
                    <Icon icon="fluent:location-24-regular" class="w-4 h-4 text-blue-500" />
                    {{ selectedClassroom.room_location }}
                  </span>
                  <span v-if="selectedClassroom.academic_year" class="flex items-center gap-1.5">
                    <Icon icon="fluent:calendar-24-regular" class="w-4 h-4 text-orange-500" />
                    ปีการศึกษา {{ typeof selectedClassroom.academic_year === 'object' ? (selectedClassroom.academic_year?.name || selectedClassroom.academic_year?.year) : selectedClassroom.academic_year }}
                    <template v-if="selectedClassroom.semester"> / เทอม {{ selectedClassroom.semester }}</template>
                  </span>
                  <span v-if="selectedClassroom.capacity" class="flex items-center gap-1.5">
                    <Icon icon="fluent:people-queue-24-regular" class="w-4 h-4 text-amber-500" />
                    ความจุ {{ selectedClassroom.capacity }} คน
                  </span>
                </div>
              </div>

              <!-- Students List (จากตาราง classroom_students → students) -->
              <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                  <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <Icon icon="fluent:people-24-regular" class="w-5 h-5 text-green-500" />
                    นักเรียนในห้อง ({{ classroomStudents.length }})
                  </h4>
                </div>

                <div v-if="isLoadingClassroomDetail" class="p-8 text-center">
                  <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-vikinger-purple mx-auto" />
                  <p class="text-sm text-gray-500 mt-2">กำลังโหลดรายชื่อ...</p>
                </div>

                <div v-else-if="classroomStudents.length === 0" class="p-8 text-center">
                  <Icon icon="fluent:people-24-regular" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                  <p class="text-gray-500 dark:text-gray-400">ยังไม่มีนักเรียนในห้องนี้</p>
                  <p v-if="academy.authIsAcademyAdmin" class="text-sm text-gray-400 mt-1">ไปที่ "จัดการห้องเรียน" เพื่อเพิ่มนักเรียน</p>
                </div>

                <!-- Student Table (Desktop) -->
                <div v-else class="hidden sm:block overflow-x-auto">
                  <table class="w-full text-sm">
                    <thead>
                      <tr class="bg-gray-50 dark:bg-vikinger-dark-100 text-gray-600 dark:text-gray-400">
                        <th class="px-4 py-3 text-left font-medium w-16">เลขที่</th>
                        <th class="px-4 py-3 text-left font-medium">รหัสนักเรียน</th>
                        <th class="px-4 py-3 text-left font-medium">ชื่อ-สกุล</th>
                        <th class="px-4 py-3 text-center font-medium w-20">เพศ</th>
                        <th class="px-4 py-3 text-center font-medium w-20">สถานะ</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                      <tr
                        v-for="cs in classroomStudents"
                        :key="cs.id"
                        class="hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"
                      >
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-medium">
                          {{ cs.student_number || '-' }}
                        </td>
                        <td class="px-4 py-3 text-xs font-mono text-gray-500 dark:text-gray-400">
                          {{ cs.student_id || '-' }}
                        </td>
                        <td class="px-4 py-3">
                          <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
                              <img
                                v-if="cs.profile_image_url"
                                :src="cs.profile_image_url"
                                :alt="cs.first_name_th"
                                class="w-full h-full object-cover"
                              />
                              <Icon v-else icon="fluent:person-24-regular" class="w-full h-full p-1.5 text-gray-400" />
                            </div>
                            <div>
                              <span class="font-medium text-gray-900 dark:text-white">
                                {{ cs.title_prefix_th || '' }} {{ cs.first_name_th || '' }} {{ cs.last_name_th || '' }}
                              </span>
                              <span v-if="cs.nickname" class="text-xs text-gray-400 ml-1">({{ cs.nickname }})</span>
                            </div>
                          </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                          <span :class="[
                            'text-xs px-2 py-0.5 rounded-full',
                            cs.gender === 1 ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' :
                            cs.gender === 0 ? 'bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400' :
                            'bg-gray-100 dark:bg-gray-700 text-gray-500'
                          ]">
                            {{ getStudentGender(cs.gender).label }}
                          </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                          <span :class="[
                            'text-xs px-2 py-0.5 rounded-full',
                            cs.status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                          ]">
                            {{ cs.status === 'active' ? 'ปกติ' : cs.status === 'transferred' ? 'ย้าย' : cs.status === 'graduated' ? 'จบ' : cs.status }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Student Cards (Mobile) -->
                <div v-if="classroomStudents.length > 0" class="sm:hidden divide-y divide-gray-100 dark:divide-gray-700">
                  <div
                    v-for="cs in classroomStudents"
                    :key="'s-' + cs.id"
                    class="p-4 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"
                  >
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
                        <img
                          v-if="cs.profile_image_url"
                          :src="cs.profile_image_url"
                          :alt="cs.first_name_th"
                          class="w-full h-full object-cover"
                        />
                        <Icon v-else icon="fluent:person-24-regular" class="w-full h-full p-2 text-gray-400" />
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                          <span v-if="cs.student_number" class="text-sm font-bold text-vikinger-purple">{{ cs.student_number }}</span>
                          <span class="font-medium text-gray-900 dark:text-white truncate">
                            {{ cs.first_name_th || '' }} {{ cs.last_name_th || '' }}
                          </span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                          <span v-if="cs.student_id" class="font-mono">{{ cs.student_id }}</span>
                          <span :class="[
                            'px-1.5 py-0.5 rounded-full',
                            cs.gender === 1 ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600' :
                            cs.gender === 0 ? 'bg-pink-100 dark:bg-pink-900/30 text-pink-600' : ''
                          ]">
                            {{ getStudentGender(cs.gender).label }}
                          </span>
                        </div>
                      </div>
                      <span :class="[
                        'text-xs px-2 py-0.5 rounded-full flex-shrink-0',
                        cs.status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                      ]">
                        {{ cs.status === 'active' ? 'ปกติ' : cs.status }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Members List (ระบบสมาชิกใหม่ - ครู/ผู้ช่วย/ผู้สังเกตการณ์) -->
              <div v-if="classroomMembers.length > 0" class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                  <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <Icon icon="fluent:people-community-24-regular" class="w-5 h-5 text-vikinger-purple" />
                    บุคลากร/สมาชิกอื่น ({{ classroomMembers.length }})
                  </h4>
                </div>

                <!-- Member Table (Desktop) -->
                <div class="hidden sm:block overflow-x-auto">
                  <table class="w-full text-sm">
                    <thead>
                      <tr class="bg-gray-50 dark:bg-vikinger-dark-100 text-gray-600 dark:text-gray-400">
                        <th class="px-4 py-3 text-left font-medium w-16">เลขที่</th>
                        <th class="px-4 py-3 text-left font-medium">ชื่อ</th>
                        <th class="px-4 py-3 text-center font-medium w-28">บทบาท</th>
                        <th class="px-4 py-3 text-center font-medium w-28">วิธีเข้าร่วม</th>
                        <th class="px-4 py-3 text-center font-medium w-20">สถานะ</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                      <tr
                        v-for="member in classroomMembers"
                        :key="member.id"
                        class="hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"
                      >
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-medium">
                          {{ member.student_no || '-' }}
                        </td>
                        <td class="px-4 py-3">
                          <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
                              <img
                                v-if="member.user?.profile_photo_path"
                                :src="member.user.profile_photo_path"
                                :alt="member.user?.name"
                                class="w-full h-full object-cover"
                              />
                              <Icon v-else icon="fluent:person-24-regular" class="w-full h-full p-1.5 text-gray-400" />
                            </div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ member.user?.name || 'ไม่ทราบชื่อ' }}</span>
                          </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                          <span :class="['text-xs px-2 py-0.5 rounded-full', getMemberRoleInfo(member.role).color]">
                            {{ getMemberRoleInfo(member.role).label }}
                          </span>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500 dark:text-gray-400">
                          {{ member.join_method === 'admin_assigned' ? 'แอดมินเพิ่ม' : member.join_method === 'invitation' ? 'คำเชิญ' : member.join_method === 'classroom_code' ? 'ใช้รหัส' : member.join_method }}
                        </td>
                        <td class="px-4 py-3 text-center">
                          <span :class="[
                            'text-xs px-2 py-0.5 rounded-full',
                            member.is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                          ]">
                            {{ member.is_active ? 'ใช้งาน' : 'ออกแล้ว' }}
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- Member Cards (Mobile) -->
                <div class="sm:hidden divide-y divide-gray-100 dark:divide-gray-700">
                  <div
                    v-for="member in classroomMembers"
                    :key="'m-' + member.id"
                    class="p-4 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"
                  >
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden flex-shrink-0">
                        <img
                          v-if="member.user?.profile_photo_path"
                          :src="member.user.profile_photo_path"
                          :alt="member.user?.name"
                          class="w-full h-full object-cover"
                        />
                        <Icon v-else icon="fluent:person-24-regular" class="w-full h-full p-2 text-gray-400" />
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                          <span v-if="member.student_no" class="text-sm font-bold text-vikinger-purple">{{ member.student_no }}</span>
                          <span class="font-medium text-gray-900 dark:text-white truncate">{{ member.user?.name || 'ไม่ทราบชื่อ' }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                          <span :class="['px-1.5 py-0.5 rounded-full', getMemberRoleInfo(member.role).color]">
                            {{ getMemberRoleInfo(member.role).label }}
                          </span>
                        </div>
                      </div>
                      <span :class="[
                        'text-xs px-2 py-0.5 rounded-full flex-shrink-0',
                        member.is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                      ]">
                        {{ member.is_active ? 'ใช้งาน' : 'ออกแล้ว' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Classroom List View -->
            <div v-else>
              <!-- Header with Admin Link -->
              <div v-if="academy.authIsAcademyAdmin" class="flex justify-end mb-4">
                <NuxtLink
                  :to="`/academies/${academyName}/admin/classrooms`"
                  class="px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium text-sm hover:bg-vikinger-purple/90 transition-colors flex items-center gap-2"
                >
                  <Icon icon="fluent:settings-24-regular" class="w-5 h-5" />
                  จัดการห้องเรียน
                </NuxtLink>
              </div>

              <div v-if="classrooms.length === 0" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">
                <Icon icon="fluent:board-24-regular" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400">ยังไม่มีห้องเรียน</p>
                <p v-if="academy.authIsAcademyAdmin" class="text-sm text-gray-400 dark:text-gray-500 mt-2">ไปที่ "จัดการห้องเรียน" เพื่อสร้างห้องเรียนใหม่</p>
              </div>

              <!-- Classrooms Grid -->
              <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div
                  v-for="classroom in classrooms"
                  :key="classroom.id"
                  @click="fetchClassroomDetail(classroom)"
                  class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow cursor-pointer"
                >
                  <!-- Classroom Header -->
                  <div class="h-16 bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
                    <Icon icon="fluent:board-24-regular" class="w-8 h-8 text-white/80" />
                  </div>
                  
                  <!-- Classroom Info -->
                  <div class="p-4">
                    <div class="flex items-start gap-3">
                      <div class="w-12 h-12 -mt-8 rounded-lg bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center border-2 border-white dark:border-vikinger-dark-200 shadow-md">
                        <span class="text-white font-bold text-sm">{{ classroom.grade_level || '' }}</span>
                      </div>
                      <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 dark:text-white">
                          {{ classroom.name || `${classroom.grade_level}/${classroom.section}` }}
                        </h4>
                        <div class="flex items-center gap-2 mt-1">
                          <span v-if="classroom.grade_level" class="text-xs px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                            ชั้น {{ classroom.grade_level }}
                          </span>
                          <span v-if="classroom.section" class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                            ห้อง {{ classroom.section }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <!-- Teacher -->
                    <div v-if="classroom.homeroom_teacher" class="mt-3 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                      <Icon icon="fluent:person-24-regular" class="w-4 h-4" />
                      <span>ครูประจำชั้น: {{ classroom.homeroom_teacher.name }}</span>
                    </div>

                    <!-- Member Count -->
                    <div class="mt-3 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                      <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
                      <span>{{ classroom.student_count || 0 }} นักเรียน</span>
                      <span v-if="classroom.member_teacher_count" class="text-xs text-gray-400">
                        ({{ classroom.member_teacher_count }} ครู)
                      </span>
                    </div>

                    <!-- View Detail -->
                    <div class="mt-3 flex items-center justify-between text-sm">
                      <div class="flex items-center gap-3">
                        <span v-if="classroom.room_location" class="flex items-center gap-1 text-gray-400 dark:text-gray-500">
                          <Icon icon="fluent:location-24-regular" class="w-4 h-4" />
                          {{ classroom.room_location }}
                        </span>
                        <span v-if="classroom.classroom_code" class="text-xs font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-500 dark:text-gray-400">
                          {{ classroom.classroom_code }}
                        </span>
                      </div>
                      <span class="text-vikinger-purple hover:text-vikinger-purple/80 font-medium">
                        ดูรายละเอียด
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Events Tab (กิจกรรม) -->
          <div v-else-if="currentTab === 'events'" class="space-y-4">
            <!-- Header with Create Button -->
            <div v-if="academy.authIsAcademyAdmin" class="flex justify-end">
              <button
                @click="showCreateEventModal = true"
                class="px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium text-sm hover:bg-vikinger-purple/90 transition-colors flex items-center gap-2"
              >
                <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
                สร้างกิจกรรมใหม่
              </button>
            </div>

            <div v-if="events.length === 0" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">
              <Icon icon="fluent:calendar-star-24-regular" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
              <p class="text-gray-500 dark:text-gray-400">ยังไม่มีกิจกรรม</p>
              <p v-if="academy.authIsAcademyAdmin" class="text-sm text-gray-400 dark:text-gray-500 mt-2">คลิก "สร้างกิจกรรมใหม่" เพื่อเริ่มต้น</p>
            </div>

            <!-- Events List -->
            <div v-else class="space-y-4">
              <div
                v-for="event in events"
                :key="event.id"
                class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow"
              >
                <div class="flex flex-col sm:flex-row">
                  <!-- Date Badge -->
                  <div :class="['flex-shrink-0 w-full sm:w-24 p-4 bg-gradient-to-br flex flex-row sm:flex-col items-center justify-center gap-2 sm:gap-0 text-white', getEventTypeInfo(event.event_type).color]">
                    <div class="text-2xl sm:text-3xl font-bold">{{ new Date(event.start_datetime).getDate() }}</div>
                    <div class="text-xs sm:text-sm opacity-90">{{ new Date(event.start_datetime).toLocaleDateString('th-TH', { month: 'short', year: '2-digit' }) }}</div>
                    <Icon :icon="getEventTypeInfo(event.event_type).icon" class="w-5 h-5 sm:mt-2 opacity-80" />
                  </div>

                  <!-- Event Info -->
                  <div class="flex-1 p-4">
                    <div class="flex items-start justify-between gap-3">
                      <div class="flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                          <h4 class="font-semibold text-gray-900 dark:text-white">{{ event.title }}</h4>
                          <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                            {{ getEventTypeInfo(event.event_type).label }}
                          </span>
                          <span v-if="event.registration_status" :class="[
                            'text-xs px-2 py-0.5 rounded-full',
                            event.registration_status === 'confirmed' ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' :
                            event.registration_status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400' :
                            'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                          ]">
                            {{ event.registration_status === 'confirmed' ? 'ยืนยันแล้ว' : event.registration_status === 'pending' ? 'รอยืนยัน' : event.registration_status }}
                          </span>
                        </div>
                        <p v-if="event.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ event.description }}</p>
                      </div>
                    </div>

                    <!-- Event Details -->
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-500 dark:text-gray-400">
                      <span class="flex items-center gap-1">
                        <Icon icon="fluent:clock-24-regular" class="w-4 h-4" />
                        {{ formatEventDate(event.start_datetime) }}
                      </span>
                      <span v-if="event.end_datetime" class="flex items-center gap-1">
                        <Icon icon="fluent:arrow-right-24-regular" class="w-3 h-3" />
                        {{ formatEventDate(event.end_datetime) }}
                      </span>
                      <span v-if="event.location" class="flex items-center gap-1">
                        <Icon icon="fluent:location-24-regular" class="w-4 h-4" />
                        {{ event.location }}
                      </span>
                      <span v-if="event.registered_count !== undefined" class="flex items-center gap-1">
                        <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
                        {{ event.registered_count }}{{ event.max_participants ? `/${event.max_participants}` : '' }} คน
                      </span>
                    </div>

                    <!-- Actions -->
                    <div class="mt-3 flex items-center gap-2">
                      <button
                        v-if="event.requires_registration && !event.registration_status && !academy.authIsAcademyAdmin"
                        @click="registerForEvent(event)"
                        class="px-3 py-1.5 bg-vikinger-purple text-white rounded-lg text-sm font-medium hover:bg-vikinger-purple/90 transition-colors flex items-center gap-1"
                      >
                        <Icon icon="fluent:person-add-24-regular" class="w-4 h-4" />
                        ลงทะเบียน
                      </button>
                      <button
                        v-if="event.registration_status && event.registration_status !== 'cancelled'"
                        @click="cancelEventRegistration(event)"
                        class="px-3 py-1.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg text-sm font-medium hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors flex items-center gap-1"
                      >
                        <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
                        ยกเลิกลงทะเบียน
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Load More Events -->
            <div v-if="hasMoreEvents" class="text-center py-4">
              <button
                @click="loadMoreEvents"
                :disabled="isLoadingMoreEvents"
                class="px-6 py-2.5 bg-white dark:bg-vikinger-dark-200 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors shadow-sm border border-gray-200 dark:border-gray-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2 mx-auto"
              >
                <Icon v-if="isLoadingMoreEvents" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                <Icon v-else icon="fluent:arrow-download-24-regular" class="w-4 h-4" />
                {{ isLoadingMoreEvents ? 'กำลังโหลด...' : 'โหลดเพิ่มเติม' }}
              </button>
            </div>
          </div>

          <!-- Groups Tab -->
          <div v-else-if="currentTab === 'groups'" class="space-y-4">
            <!-- Create Group Button (Admin only) -->
            <div v-if="academy.authIsAcademyAdmin" class="flex justify-end">
              <button
                @click="showCreateGroupModal = true"
                class="px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium text-sm hover:bg-vikinger-purple/90 transition-colors flex items-center gap-2"
              >
                <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
                สร้างกลุ่มใหม่
              </button>
            </div>
            
            <CommonEmptyState
              v-if="groups.length === 0"
              icon="heroicons:building-office"
              title="ยังไม่มีส่วนงาน"
              description="เริ่มสร้างฝ่าย กลุ่มสาระ หรือชมรมใหม่ของโรงเรียน"
              :cta-label="academy.authIsAcademyAdmin ? 'เปิดส่วนงานใหม่' : undefined"
              cta-icon="fluent:add-24-regular"
              @action="showCreateGroupModal = true"
            />
            
            <!-- Groups Grid -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div 
                v-for="group in groups"
                :key="group.id"
                class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow cursor-pointer"
              >
                <!-- Group Header -->
                <div :class="['h-16 bg-gradient-to-br', getGroupTypeInfo(group.type).color]"></div>
                
                <!-- Group Info -->
                <div class="p-4">
                  <div class="flex items-start gap-3">
                    <div :class="['w-12 h-12 -mt-8 rounded-lg bg-gradient-to-br flex items-center justify-center border-2 border-white dark:border-vikinger-dark-200 shadow-md', getGroupTypeInfo(group.type).color]">
                      <Icon :icon="getGroupTypeInfo(group.type).icon" class="w-6 h-6 text-white" />
                    </div>
                    <div class="flex-1 pt-0">
                      <div class="flex items-center gap-2">
                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ group.name }}</h4>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                          {{ getGroupTypeInfo(group.type).label }}
                        </span>
                      </div>
                      <p v-if="group.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ group.description }}</p>
                    </div>
                  </div>
                  
                  <!-- Group Stats -->
                  <div class="mt-4 flex items-center justify-between text-sm">
                    <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400">
                      <span class="flex items-center gap-1">
                        <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
                        {{ group.members_count || 0 }} สมาชิก
                      </span>
                    </div>
                    <button class="text-vikinger-purple hover:text-vikinger-purple/80 font-medium">
                      ดูรายละเอียด
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="currentTab === 'about'" class="space-y-4">
            <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-5 shadow-sm">
              <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <Icon icon="fluent:info-24-regular" class="w-5 h-5 text-vikinger-purple" />
                About this school
              </h3>

              <div class="space-y-3 text-sm">
                <div v-if="academy.address" class="flex items-start gap-3">
                  <Icon icon="fluent:location-24-regular" class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" />
                  <span class="text-gray-600 dark:text-gray-400">{{ academy.address }}</span>
                </div>

                <div v-if="academy.email" class="flex items-center gap-3">
                  <Icon icon="fluent:mail-24-regular" class="w-5 h-5 text-gray-400" />
                  <a :href="`mailto:${academy.email}`" class="text-vikinger-purple hover:underline">{{ academy.email }}</a>
                </div>

                <div v-if="academy.phone" class="flex items-center gap-3">
                  <Icon icon="fluent:call-24-regular" class="w-5 h-5 text-gray-400" />
                  <a :href="`tel:${academy.phone}`" class="text-vikinger-purple hover:underline">{{ academy.phone }}</a>
                </div>

                <div v-if="academy.established_year" class="flex items-center gap-3">
                  <Icon icon="fluent:calendar-24-regular" class="w-5 h-5 text-gray-400" />
                  <span class="text-gray-600 dark:text-gray-400">Established {{ academy.established_year }}</span>
                </div>
              </div>
            </div>

            <div v-if="academy.director" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-5 shadow-sm">
              <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <Icon icon="fluent:person-star-24-regular" class="w-5 h-5 text-vikinger-purple" />
                Director
              </h3>

              <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                  <img 
                    v-if="academy.director.avatar" 
                    :src="academy.director.avatar" 
                    :alt="academy.director.name"
                    class="w-full h-full object-cover"
                  />
                  <Icon v-else icon="fluent:person-24-regular" class="w-full h-full p-2 text-gray-400" />
                </div>
                <div>
                  <h4 class="font-medium text-gray-900 dark:text-white">{{ academy.director.name }}</h4>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Director</p>
                </div>
              </div>
            </div>
          </div>
        </main>
        
        <!-- Right Sidebar -->
        <aside class="hidden xl:block xl:sticky xl:top-[86px] xl:space-y-6">
          <!-- Quick Stats -->
          <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-5 shadow-sm">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="fluent:data-bar-horizontal-24-regular" class="w-5 h-5 text-vikinger-purple" />
              Stats
            </h3>
            <SchoolStatGrid :academy="{ ...academy, total_classrooms: classrooms.length }" />
          </div>

          <!-- Student Card Widget -->
          <LearnAcademyStudentCardWidget
            v-if="academy && (academy.memberStatus === 2 || academy.authIsAcademyAdmin)"
            :academy-id="academy.id"
            :academy-name="academyName"
          />

          <!-- Upcoming Events -->
          <SchoolUpcomingEvents :academy-id="academy.id" @view-all="switchTab('events')" />

          <!-- Classroom Leaderboard -->
          <SchoolClassroomLeaderboard :academy-id="academy.id" cycle="month" />

          <!-- About Card -->
          <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-5 shadow-sm">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="fluent:info-24-regular" class="w-5 h-5 text-vikinger-purple" />
              About this school
            </h3>
            
            <div class="space-y-3 text-sm">
              <div v-if="academy.address" class="flex items-start gap-3">
                <Icon icon="fluent:location-24-regular" class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" />
                <span class="text-gray-600 dark:text-gray-400">{{ academy.address }}</span>
              </div>
              
              <div v-if="academy.email" class="flex items-center gap-3">
                <Icon icon="fluent:mail-24-regular" class="w-5 h-5 text-gray-400" />
                <a :href="`mailto:${academy.email}`" class="text-vikinger-purple hover:underline">{{ academy.email }}</a>
              </div>
              
              <div v-if="academy.phone" class="flex items-center gap-3">
                <Icon icon="fluent:call-24-regular" class="w-5 h-5 text-gray-400" />
                <a :href="`tel:${academy.phone}`" class="text-vikinger-purple hover:underline">{{ academy.phone }}</a>
              </div>
              
              <div v-if="academy.established_year" class="flex items-center gap-3">
                <Icon icon="fluent:calendar-24-regular" class="w-5 h-5 text-gray-400" />
                <span class="text-gray-600 dark:text-gray-400">Established {{ academy.established_year }}</span>
              </div>
            </div>
          </div>
          
          <!-- Director Card -->
          <div v-if="academy.director" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-5 shadow-sm">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="fluent:person-star-24-regular" class="w-5 h-5 text-vikinger-purple" />
              Director
            </h3>
            
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <img 
                  v-if="academy.director.avatar" 
                  :src="academy.director.avatar" 
                  :alt="academy.director.name"
                  class="w-full h-full object-cover"
                />
                <Icon v-else icon="fluent:person-24-regular" class="w-full h-full p-2 text-gray-400" />
              </div>
              <div>
                <h4 class="font-medium text-gray-900 dark:text-white">{{ academy.director.name }}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Director</p>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>
    
    <!-- Create Group Modal -->
    <!-- Create Group Modal -->
    <AcademyGroupsGroupCreateModal
      v-if="academy"
      v-model:open="showCreateGroupModal"
      :academy-id="academy.id"
      default-type="classroom"
      @created="(g) => groups.push(g)"
    />

    <!-- Invite Member Modal -->
    <LazyLearnAcademyInviteMemberModal
      v-if="academy"
      :is-open="showInviteMemberModal"
      :academy-id="academy.id"
      @close="showInviteMemberModal = false"
      @invited="onMemberInvited"
    />

    <!-- Left Mobile Drawer -->
    <CommonSidebarDrawer v-model:open="showMobileLeftDrawer" side="left" title="เมนูลัด">
      <div v-if="academy" class="space-y-4">
        <SchoolQuickMenu
          :academy="academy"
          :is-pending="academy?.memberStatus === 1 || academy?.memberStatus === 'pending'"
          @join="() => { requestMembership(); showMobileLeftDrawer = false; }"
          @navigate="(t) => { switchTab(t); showMobileLeftDrawer = false; }"
        />
        <SchoolLevelCard
          v-if="gamificationSummary"
          :level="gamificationSummary.level"
          :total-xp="gamificationSummary.total_xp"
          :xp-to-next="gamificationSummary.xp_to_next"
          :progress-pct="gamificationSummary.progress_pct"
        />
      </div>
    </CommonSidebarDrawer>

    <!-- Right Mobile Drawer -->
    <CommonSidebarDrawer v-model:open="showMobileRightDrawer" side="right" title="Stats & activity">
      <div v-if="academy" class="space-y-6">
        <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <Icon icon="fluent:data-bar-horizontal-24-regular" class="w-5 h-5 text-vikinger-purple" />
            Stats
          </h3>
          <SchoolStatGrid :academy="{ ...academy, total_classrooms: classrooms.length }" />
        </div>
        <LearnAcademyStudentCardWidget
          v-if="academy.memberStatus === 2 || academy.authIsAcademyAdmin"
          :academy-id="academy.id"
          :academy-name="academyName"
        />
        <SchoolUpcomingEvents :academy-id="academy.id" @view-all="(() => { switchTab('events'); showMobileRightDrawer = false; })" />
        <SchoolClassroomLeaderboard :academy-id="academy.id" cycle="month" />
      </div>
    </CommonSidebarDrawer>

    <!-- Create Event Modal -->
    <Teleport to="body">
      <div 
        v-if="showCreateEventModal" 
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="showCreateEventModal = false"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 max-h-[90vh] overflow-y-auto">
          <!-- Modal Header -->
          <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between sticky top-0 bg-white dark:bg-vikinger-dark-200 z-10">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="fluent:calendar-add-24-regular" class="w-6 h-6 text-vikinger-purple" />
              สร้างกิจกรรมใหม่
            </h3>
            <button
              @click="showCreateEventModal = false"
              class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            >
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <!-- Modal Body -->
          <div class="p-6 space-y-4">
            <!-- Event Title -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ชื่อกิจกรรม <span class="text-red-500">*</span>
              </label>
              <input
                v-model="newEvent.title"
                type="text"
                placeholder="เช่น กีฬาสี, ค่ายวิทยาศาสตร์, ประชุมผู้ปกครอง"
                class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"
              />
            </div>

            <!-- Event Type -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ประเภทกิจกรรม
              </label>
              <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                <button
                  v-for="etype in [
                    { value: 'activity', label: 'กิจกรรม', icon: 'fluent:calendar-star-24-regular' },
                    { value: 'meeting', label: 'ประชุม', icon: 'fluent:people-team-24-regular' },
                    { value: 'sports', label: 'กีฬา', icon: 'fluent:sport-24-regular' },
                    { value: 'ceremony', label: 'พิธีการ', icon: 'fluent:hat-graduation-24-regular' },
                    { value: 'exam', label: 'สอบ', icon: 'fluent:document-text-24-regular' },
                    { value: 'holiday', label: 'วันหยุด', icon: 'fluent:beach-24-regular' },
                    { value: 'other', label: 'อื่นๆ', icon: 'fluent:calendar-24-regular' },
                  ]"
                  :key="etype.value"
                  @click="newEvent.event_type = etype.value"
                  :class="[
                    'p-2 rounded-lg border-2 flex flex-col items-center gap-1 transition-all text-center',
                    newEvent.event_type === etype.value
                      ? 'border-vikinger-purple bg-vikinger-purple/10 text-vikinger-purple'
                      : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-vikinger-purple/50'
                  ]"
                >
                  <Icon :icon="etype.icon" class="w-5 h-5" />
                  <span class="text-xs font-medium">{{ etype.label }}</span>
                </button>
              </div>
            </div>

            <!-- Date/Time -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  วันเวลาเริ่ม <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="newEvent.start_datetime"
                  type="datetime-local"
                  class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  วันเวลาสิ้นสุด
                </label>
                <input
                  v-model="newEvent.end_datetime"
                  type="datetime-local"
                  class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"
                />
              </div>
            </div>

            <!-- Location -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                สถานที่
              </label>
              <div class="flex gap-2 mb-2">
                <button
                  v-for="loc in [{ value: 'onsite', label: 'สถานที่จริง' }, { value: 'online', label: 'ออนไลน์' }]"
                  :key="loc.value"
                  @click="newEvent.location_type = loc.value"
                  :class="[
                    'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                    newEvent.location_type === loc.value
                      ? 'bg-vikinger-purple text-white'
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                  ]"
                >
                  {{ loc.label }}
                </button>
              </div>
              <input
                v-model="newEvent.location"
                type="text"
                :placeholder="newEvent.location_type === 'online' ? 'ลิงก์ห้องประชุมออนไลน์' : 'ห้องประชุม, หอประชุม, สนามกีฬา'"
                class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"
              />
            </div>

            <!-- Max Participants & Registration -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  จำนวนรับสูงสุด
                </label>
                <input
                  v-model.number="newEvent.max_participants"
                  type="number"
                  min="0"
                  placeholder="ไม่จำกัด"
                  class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"
                />
              </div>
              <div class="flex items-end">
                <label class="flex items-center gap-3 cursor-pointer pb-3">
                  <input
                    v-model="newEvent.requires_registration"
                    type="checkbox"
                    class="w-5 h-5 rounded border-gray-300 text-vikinger-purple focus:ring-vikinger-purple/50"
                  />
                  <span class="text-sm text-gray-700 dark:text-gray-300">ต้องลงทะเบียนก่อน</span>
                </label>
              </div>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                รายละเอียด
              </label>
              <textarea
                v-model="newEvent.description"
                rows="3"
                placeholder="อธิบายรายละเอียดเกี่ยวกับกิจกรรมนี้..."
                class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50 resize-none"
              ></textarea>
            </div>
          </div>
          
          <!-- Modal Footer -->
          <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-end gap-3 sticky bottom-0 bg-white dark:bg-vikinger-dark-200">
            <button
              @click="showCreateEventModal = false"
              class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            >
              ยกเลิก
            </button>
            <button
              @click="createEvent"
              :disabled="!newEvent.title.trim() || !newEvent.start_datetime || isCreatingEvent"
              class="px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium hover:bg-vikinger-purple/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <Icon v-if="isCreatingEvent" icon="svg-spinners:ring-resize" class="w-4 h-4" />
              <Icon v-else icon="fluent:add-24-regular" class="w-4 h-4" />
              สร้างกิจกรรม
            </button>
          </div>
        </div>
      </div>
    </Teleport>
    </div>
  </div>
</template>

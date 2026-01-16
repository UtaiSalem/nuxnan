<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

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
const isLoading = ref(true)
const isLoadingTab = ref(false)
const error = ref<string | null>(null)
const currentTab = ref('feed')
const isAcademyAdmin = ref(false)
const isMemberActionLoading = ref(false)

// Group creation state
const showCreateGroupModal = ref(false)
const newGroup = ref({ name: '', description: '', type: 'classroom' })
const isCreatingGroup = ref(false)

// Invite member modal state
const showInviteMemberModal = ref(false)
const pendingRequests = ref<any[]>([])
const isLoadingPendingRequests = ref(false)

// Computed
const academyName = computed(() => route.params.name as string)

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

const memberStatusText = computed(() => {
  if (!academy.value) return null
  const status = academy.value.memberStatus
  if (status === null || status === undefined) return null
  if (status === 0 || status === 'pending') return { text: 'รอการอนุมัติ', color: 'bg-yellow-500' }
  if (status === 1 || status === 'approved') return { text: 'รอการอนุมัติ', color: 'bg-yellow-500' }
  if (status === 2 || status === 'member') return { text: 'สมาชิก', color: 'bg-green-500' }
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
  { id: 'feed', label: 'ฟีด', icon: 'fluent:feed-24-regular' },
  { id: 'courses', label: 'รายวิชา', icon: 'fluent:book-24-regular' },
  { id: 'members', label: 'สมาชิก', icon: 'fluent:people-24-regular' },
  { id: 'groups', label: 'กลุ่ม', icon: 'fluent:people-community-24-regular' },
]

// Methods
const fetchAcademy = async () => {
  if (!user.value) return
  
  isLoading.value = true
  error.value = null
  
  try {
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
    
    if (response.success) {
      academy.value = JSON.parse(JSON.stringify(response.academy))
      isAcademyAdmin.value = response.isAcademyAdmin || false
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

const fetchMembers = async () => {
  if (!academy.value) return
  
  isLoadingTab.value = true
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/members`)
    if (response.success) {
      members.value = JSON.parse(JSON.stringify(response.members || []))
    }
  } catch (err) {
    console.error('Failed to fetch members:', err)
  } finally {
    isLoadingTab.value = false
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

const fetchActivities = async () => {
  if (!academy.value) return
  
  isLoadingTab.value = true
  try {
    const response: any = await api.get(`/api/academies/${academy.value.id}/activities`)
    if (response.success) {
      activities.value = JSON.parse(JSON.stringify(response.activities || []))
    }
  } catch (err) {
    console.error('Failed to fetch activities:', err)
  } finally {
    isLoadingTab.value = false
  }
}

const switchTab = async (tabId: string) => {
  currentTab.value = tabId
  
  switch (tabId) {
    case 'feed':
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
    case 'groups':
      if (groups.value.length === 0) await fetchGroups()
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
    const response: any = await api.post(`/api/academies/${academy.value.id}/members/${request.id}/accept`)
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
    const response: any = await api.post(`/api/academies/${academy.value.id}/members/${request.id}/reject`)
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

const requestMembership = async () => {
  if (!academy.value || isMemberActionLoading.value) return
  
  isMemberActionLoading.value = true
  try {
    const response: any = await api.post(`/api/academies/${academy.value.id}/members`)
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
    const response: any = await api.post(`/api/academies/${academy.value.id}/unmembers`)
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

// Group creation
const createGroup = async () => {
  if (!academy.value || !newGroup.value.name.trim() || isCreatingGroup.value) return
  
  isCreatingGroup.value = true
  try {
    const response: any = await api.post(`/api/academies/${academy.value.id}/groups`, {
      name: newGroup.value.name,
      description: newGroup.value.description,
      type: newGroup.value.type
    })
    
    if (response.success) {
      groups.value.push(JSON.parse(JSON.stringify(response.group)))
      showCreateGroupModal.value = false
      newGroup.value = { name: '', description: '', type: 'classroom' }
      
      Swal.fire({
        icon: 'success',
        title: 'สร้างกลุ่มสำเร็จ',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: err?.data?.message || 'ไม่สามารถสร้างกลุ่มได้',
    })
  } finally {
    isCreatingGroup.value = false
  }
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
})
</script>

<template>
  <div class="min-h-screen bg-gray-200 dark:bg-vikinger-dark-300">
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
      <!-- Cover & Profile Section -->
      <div class="relative bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-lg overflow-hidden mb-6">
        <!-- Cover Image -->
        <div 
          class="h-48 md:h-64 bg-gray-300 dark:bg-gray-700 bg-cover bg-center relative"
          :style="{ backgroundImage: `url(${coverUrl})` }"
        >
          <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
        </div>
        
        <!-- Profile Info -->
        <div class="relative px-4 md:px-8 pb-6">
          <!-- Logo -->
          <div class="absolute -top-16 left-4 md:left-8">
            <div class="w-28 h-28 md:w-36 md:h-36 rounded-xl border-4 border-white dark:border-vikinger-dark-200 shadow-lg overflow-hidden bg-white">
              <img 
                :src="logoUrl" 
                :alt="academy.name"
                class="w-full h-full object-cover"
              />
            </div>
          </div>
          
          <!-- Info & Actions -->
          <div class="flex flex-col md:flex-row md:items-end md:justify-between pt-16 md:pt-6 md:pl-44">
            <!-- Academy Info -->
            <div class="mb-4 md:mb-0">
              <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                {{ academy.name }}
              </h1>
              <p v-if="academy.slogan" class="text-gray-600 dark:text-gray-400 mb-3">
                {{ academy.slogan }}
              </p>
              
              <!-- Stats -->
              <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                <div class="flex items-center gap-1.5">
                  <Icon :icon="getAcademyTypeInfo(academy.type).icon" :class="['w-4 h-4', getAcademyTypeInfo(academy.type).color]" />
                  <span>{{ getAcademyTypeInfo(academy.type).label }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
                  <span>{{ academy.total_students || 0 }} สมาชิก</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <Icon icon="fluent:book-24-regular" class="w-4 h-4" />
                  <span>{{ academy.courses_offered || 0 }} รายวิชา</span>
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
              
              <!-- Admin Badge -->
              <span 
                v-if="academy.authIsAcademyAdmin" 
                class="px-3 py-1.5 rounded-full text-sm font-medium bg-vikinger-purple text-white flex items-center gap-1.5"
              >
                <Icon icon="fluent:shield-checkmark-24-regular" class="w-4 h-4" />
                ผู้ดูแล
              </span>
              
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
              
              <!-- Leave Button -->
              <button
                v-if="canLeave"
                @click="cancelMembership"
                :disabled="isMemberActionLoading"
                class="px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <Icon v-if="isMemberActionLoading" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                <Icon v-else icon="fluent:person-subtract-24-regular" class="w-4 h-4" />
                ออกจากโรงเรียน
              </button>
            </div>
          </div>
        </div>
        
        <!-- Tabs -->
        <div class="border-t border-gray-200 dark:border-gray-700">
          <div class="flex overflow-x-auto">
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
            </button>
          </div>
        </div>
      </div>
      
      <!-- Tab Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2">
          <!-- Loading Tab Content -->
          <div v-if="isLoadingTab" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">
            <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-vikinger-purple mx-auto" />
          </div>
          
          <!-- Feed Tab -->
          <div v-else-if="currentTab === 'feed'" class="space-y-4">
            <!-- Post Composer (for members & admins only) - Using CreatePostBox -->
            <PlayFeedCreatePostBox 
              v-if="academy.memberStatus === 2 || academy.authIsAcademyAdmin"
              context="academy"
              :context-id="academy.id"
              :context-name="academy.name"
              @post-created="handleAcademyPostCreated"
            />
            
            <div v-if="activities.length === 0" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">
              <Icon icon="fluent:feed-24-regular" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
              <p class="text-gray-500 dark:text-gray-400">ยังไม่มีกิจกรรม</p>
            </div>
            
            <!-- Activity/Post Cards -->
            <div 
              v-for="activity in activities" 
              :key="activity.id"
              class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden"
            >
              <!-- Post Header -->
              <div class="p-4 flex items-start gap-3">
                <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex-shrink-0">
                  <img 
                    v-if="activity.user?.avatar" 
                    :src="activity.user.avatar" 
                    :alt="activity.user?.name"
                    class="w-full h-full object-cover"
                  />
                  <Icon v-else icon="fluent:person-24-regular" class="w-full h-full p-2 text-gray-400" />
                </div>
                <div class="flex-1">
                  <div class="flex items-center justify-between">
                    <div>
                      <h4 class="font-medium text-gray-900 dark:text-white">{{ activity.user?.name || 'ผู้ใช้' }}</h4>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(activity.created_at) }}</p>
                    </div>
                    <span 
                      v-if="activity.type" 
                      class="text-xs px-2 py-1 rounded-full bg-vikinger-purple/10 text-vikinger-purple"
                    >
                      {{ activity.type }}
                    </span>
                  </div>
                  
                  <!-- Content -->
                  <div class="mt-3">
                    <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ activity.description || activity.content }}</p>
                  </div>
                  
                  <!-- Images if any -->
                  <div v-if="activity.images?.length" class="mt-3 grid gap-2" :class="activity.images.length === 1 ? 'grid-cols-1' : 'grid-cols-2'">
                    <img 
                      v-for="(img, idx) in activity.images.slice(0, 4)" 
                      :key="idx"
                      :src="img"
                      class="rounded-lg w-full h-48 object-cover"
                    />
                  </div>
                </div>
              </div>
              
              <!-- Post Actions -->
              <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700/50 flex items-center gap-4">
                <button class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 hover:text-vikinger-purple transition-colors">
                  <Icon icon="fluent:heart-24-regular" class="w-5 h-5" />
                  <span class="text-sm">{{ activity.likes_count || 0 }}</span>
                </button>
                <button class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 hover:text-vikinger-purple transition-colors">
                  <Icon icon="fluent:comment-24-regular" class="w-5 h-5" />
                  <span class="text-sm">{{ activity.comments_count || 0 }}</span>
                </button>
                <button class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 hover:text-vikinger-purple transition-colors ml-auto">
                  <Icon icon="fluent:share-24-regular" class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
          
          <!-- Courses Tab -->
          <div v-else-if="currentTab === 'courses'" class="space-y-4">
            <!-- Header with Create Button -->
            <div v-if="academy.authIsAcademyAdmin" class="flex justify-end">
              <NuxtLink
                to="/courses/create"
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
                :to="`/courses/${course.id}`"
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
                        {{ course.lessons_count || 0 }} บท
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
            
            <div v-if="members.length === 0" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">
              <Icon icon="fluent:people-24-regular" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
              <p class="text-gray-500 dark:text-gray-400">ยังไม่มีสมาชิก</p>
              <p v-if="academy.authIsAcademyAdmin" class="text-sm text-gray-400 dark:text-gray-500 mt-2">คลิก "เชิญสมาชิก" เพื่อเชิญผู้ใช้เข้าร่วม</p>
            </div>
            
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
                          v-if="member.user?.avatar || member.avatar" 
                          :src="member.user?.avatar || member.avatar" 
                          :alt="member.user?.name || member.name"
                          class="w-full h-full object-cover"
                        />
                        <Icon v-else icon="fluent:person-24-regular" class="w-full h-full p-2 text-gray-400" />
                      </div>
                      <div>
                        <h4 class="font-medium text-gray-900 dark:text-white">{{ member.user?.name || member.name }}</h4>
                        <div class="flex items-center gap-2 text-sm">
                          <span 
                            :class="[
                              'px-2 py-0.5 rounded-full text-xs font-medium',
                              member.role === 'admin' ? 'bg-vikinger-purple/10 text-vikinger-purple' :
                              member.role === 'teacher' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' :
                              'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                            ]"
                          >
                            {{ member.role === 'admin' ? 'ผู้ดูแล' : member.role === 'teacher' ? 'ครูผู้สอน' : 'นักเรียน' }}
                          </span>
                          <span v-if="member.joined_at" class="text-gray-400">เข้าร่วมเมื่อ {{ formatDate(member.joined_at) }}</span>
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
            <div v-if="members.length >= 10" class="text-center">
              <button class="text-vikinger-purple hover:text-vikinger-purple/80 font-medium text-sm">
                โหลดเพิ่มเติม
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
            
            <div v-if="groups.length === 0" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">
              <Icon icon="fluent:people-community-24-regular" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
              <p class="text-gray-500 dark:text-gray-400">ยังไม่มีกลุ่ม</p>
              <p v-if="academy.authIsAcademyAdmin" class="text-sm text-gray-400 dark:text-gray-500 mt-2">คลิก "สร้างกลุ่มใหม่" เพื่อเริ่มต้น</p>
            </div>
            
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
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- About Card -->
          <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-5 shadow-sm">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="fluent:info-24-regular" class="w-5 h-5 text-vikinger-purple" />
              เกี่ยวกับโรงเรียน
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
                <span class="text-gray-600 dark:text-gray-400">ก่อตั้งเมื่อ {{ academy.established_year }}</span>
              </div>
            </div>
          </div>
          
          <!-- Director Card -->
          <div v-if="academy.director" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-5 shadow-sm">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="fluent:person-star-24-regular" class="w-5 h-5 text-vikinger-purple" />
              ผู้อำนวยการ
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
                <p class="text-sm text-gray-500 dark:text-gray-400">ผู้อำนวยการ</p>
              </div>
            </div>
          </div>
          
          <!-- Quick Stats -->
          <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-5 shadow-sm">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
              <Icon icon="fluent:data-bar-horizontal-24-regular" class="w-5 h-5 text-vikinger-purple" />
              สถิติ
            </h3>
            
            <div class="grid grid-cols-2 gap-4">
              <div class="text-center p-3 bg-gray-50 dark:bg-vikinger-dark-100 rounded-lg">
                <div class="text-2xl font-bold text-vikinger-purple">{{ academy.total_students || 0 }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">นักเรียน</div>
              </div>
              <div class="text-center p-3 bg-gray-50 dark:bg-vikinger-dark-100 rounded-lg">
                <div class="text-2xl font-bold text-vikinger-cyan">{{ academy.total_teachers || 0 }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">ครู</div>
              </div>
              <div class="text-center p-3 bg-gray-50 dark:bg-vikinger-dark-100 rounded-lg">
                <div class="text-2xl font-bold text-green-500">{{ academy.courses_offered || 0 }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">รายวิชา</div>
              </div>
              <div class="text-center p-3 bg-gray-50 dark:bg-vikinger-dark-100 rounded-lg">
                <div class="text-2xl font-bold text-orange-500">{{ groups.length || 0 }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">กลุ่ม</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Create Group Modal -->
    <Teleport to="body">
      <div 
        v-if="showCreateGroupModal" 
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @click.self="showCreateGroupModal = false"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95">
          <!-- Modal Header -->
          <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="fluent:people-community-add-24-regular" class="w-6 h-6 text-vikinger-purple" />
              สร้างกลุ่มใหม่
            </h3>
            <button
              @click="showCreateGroupModal = false"
              class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            >
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <!-- Modal Body -->
          <div class="p-6 space-y-4">
            <!-- Group Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ชื่อกลุ่ม <span class="text-red-500">*</span>
              </label>
              <input
                v-model="newGroup.name"
                type="text"
                placeholder="เช่น ห้อง ม.1/1, แผนกวิทยาศาสตร์, ชมรมดนตรี"
                class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50"
              />
            </div>
            
            <!-- Group Type -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ประเภทกลุ่ม
              </label>
              <div class="grid grid-cols-3 gap-3">
                <button
                  v-for="gtype in [
                    { value: 'department', label: 'แผนก', icon: 'fluent:building-24-regular' },
                    { value: 'classroom', label: 'ห้องเรียน', icon: 'fluent:board-24-regular' },
                    { value: 'club', label: 'ชมรม', icon: 'fluent:star-24-regular' }
                  ]"
                  :key="gtype.value"
                  @click="newGroup.type = gtype.value"
                  :class="[
                    'p-3 rounded-lg border-2 flex flex-col items-center gap-2 transition-all',
                    newGroup.type === gtype.value
                      ? 'border-vikinger-purple bg-vikinger-purple/10 text-vikinger-purple'
                      : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-vikinger-purple/50'
                  ]"
                >
                  <Icon :icon="gtype.icon" class="w-6 h-6" />
                  <span class="text-xs font-medium">{{ gtype.label }}</span>
                </button>
              </div>
            </div>
            
            <!-- Group Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                รายละเอียด (ไม่บังคับ)
              </label>
              <textarea
                v-model="newGroup.description"
                rows="3"
                placeholder="อธิบายเกี่ยวกับกลุ่มนี้..."
                class="w-full px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-vikinger-dark-100 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-vikinger-purple/50 resize-none"
              ></textarea>
            </div>
          </div>
          
          <!-- Modal Footer -->
          <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-end gap-3">
            <button
              @click="showCreateGroupModal = false"
              class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            >
              ยกเลิก
            </button>
            <button
              @click="createGroup"
              :disabled="!newGroup.name.trim() || isCreatingGroup"
              class="px-4 py-2 bg-vikinger-purple text-white rounded-lg font-medium hover:bg-vikinger-purple/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <Icon v-if="isCreatingGroup" icon="svg-spinners:ring-resize" class="w-4 h-4" />
              <Icon v-else icon="fluent:add-24-regular" class="w-4 h-4" />
              สร้างกลุ่ม
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Invite Member Modal -->
    <LazyLearnAcademyInviteMemberModal
      v-if="academy"
      :is-open="showInviteMemberModal"
      :academy-id="academy.id"
      @close="showInviteMemberModal = false"
      @invited="onMemberInvited"
    />
  </div>
</template>

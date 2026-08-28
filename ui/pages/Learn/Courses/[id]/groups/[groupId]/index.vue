<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseFeedsList from '~/components/learn/course/CourseFeedsList.vue'
import CourseGroupResources from '~/components/learn/course/groups/CourseGroupResources.vue'
import SyncClassroomModal from '~/components/learn/course/groups/SyncClassroomModal.vue'

// Inject course data
const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')

// Routes and API
const route = useRoute()
const api = useApi()
const config = useRuntimeConfig()

// Group ID from route
const groupId = computed(() => Array.isArray(route.params.groupId) ? route.params.groupId[0] : route.params.groupId)

// State
const group = ref<any>(null)
const members = ref<any[]>([])
const requesters = ref<any[]>([])
const isLoading = ref(true)
const isJoining = ref(false)
const isLeaving = ref(false)

const showEditModal = ref(false)
const showSyncModal = ref(false)
const activeTab = ref('feed')

const tabs = computed(() => {
  const baseTabs = [
    { id: 'feed', label: 'ฟีดกลุ่ม', icon: 'heroicons:chat-bubble-left-right' },
    { id: 'resources', label: 'ไฟล์/เอกสาร', icon: 'heroicons:document-duplicate' },
    { id: 'members', label: 'สมาชิก', icon: 'heroicons:users' },
    { id: 'about', label: 'เกี่ยวกับ', icon: 'heroicons:information-circle' },
  ]
  return baseTabs
})

// Check if user is member
const isMember = computed(() => !!group.value?.groupMemberOfAuth)

// Load group details
const loadGroup = async () => {
  if (!course?.value?.id || !groupId.value) return
  
  isLoading.value = true
  try {
    const response = await api.get(`/api/courses/${course.value.id}/groups/${groupId.value}`) as any
    group.value = response.group || response.data?.group || response
    members.value = (group.value.members || []).sort((a: any, b: any) => (a.order_number || 9999) - (b.order_number || 9999))
    
    // Load requesters if admin
    if (isCourseAdmin.value || group.value.groupMemberOfAuth?.role === 'admin') {
      await loadRequesters()
    }
  } catch (error) {
    console.error('Failed to load group:', error)
  } finally {
    isLoading.value = false
  }
}

// Load requesters
const loadRequesters = async () => {
  try {
    const response = await api.get(`/api/courses/${course.value.id}/groups/${groupId.value}/members/requesters`) as any
    requesters.value = response.data || []
  } catch (error) {
    console.error('Failed to load requesters:', error)
  }
}

// Approve member
const approveMember = async (memberId: number) => {
  try {
    await api.post(`/api/courses/${course.value.id}/groups/${groupId.value}/members/${memberId}/approve`, {})
    // Reload lists
    await loadGroup()
    await loadRequesters()
  } catch (error: any) {
    alert(error.data?.message || 'ไม่สามารถอนุมัติได้')
  }
}

// Reject member
const rejectMember = async (memberId: number) => {
  if (!confirm('ยืนยันการปฏิเสธคำขอ?')) return
  try {
    await api.post(`/api/courses/${course.value.id}/groups/${groupId.value}/members/${memberId}/reject`, {})
    // Reload lists
    await loadRequesters()
  } catch (error: any) {
    alert(error.data?.message || 'ไม่สามารถปฏิเสธได้')
  }
}

// Join group
const joinGroup = async () => {
  if (isJoining.value) return
  
  isJoining.value = true
  try {
    await api.post(`/api/courses/${course.value.id}/groups/${groupId.value}/members/join`, {})
    await loadGroup() // Reload to get updated data
  } catch (error: any) {
    alert(error.data?.message || 'ไม่สามารถเข้าร่วมกลุ่มได้')
  } finally {
    isJoining.value = false
  }
}

// Leave group
const leaveGroup = async () => {
  if (isLeaving.value) return
  if (!confirm('คุณต้องการออกจากกลุ่มนี้ใช่หรือไม่?')) return
  
  isLeaving.value = true
  try {
    await api.post(`/api/courses/${course.value.id}/groups/${groupId.value}/members/leave`, {})
    await loadGroup()
  } catch (error: any) {
    alert(error.data?.message || 'ไม่สามารถออกจากกลุ่มได้')
  } finally {
    isLeaving.value = false
  }
}



// Delete group (admin only)
const deleteGroup = async () => {
  if (!confirm('ยืนยันการลบกลุ่มนี้หรือไม่? สมาชิกในกลุ่มจะถูกย้ายไปยังกลุ่มหลัก')) return
  
  try {
    await api.delete(`/api/courses/${course.value.id}/groups/${groupId.value}`)
    navigateTo(`/courses/${course.value.id}/groups`)
  } catch (error: any) {
    alert(error.data?.message || 'ไม่สามารถลบกลุ่มได้')
  }
}

// Member avatar
const getMemberAvatar = (member: any) => {
  const user = member.user || member.member
  return user?.avatar || '/images/default-avatar.png'
}

// Member name
const getMemberName = (member: any) => {
  const user = member.user || member.member
  return user?.name || member.member_name || 'Unknown User'
}

// Member email
const getMemberEmail = (member: any) => {
  const user = member.user || member.member
  return user?.email || member.member_email || ''
}

// Member code (student ID)
const getMemberCode = (member: any) => {
  return member.member_code || member.order_number || '-'
}

// Member role label
const getMemberRoleLabel = (member: any) => {
  const role = member.role
  if (role === 4) return 'ผู้ดูแล'
  if (role === 3) return 'ครูผู้สอน'
  if (role === 2) return 'หัวหน้ากลุ่ม'
  return 'นักเรียน'
}

// Member role badge color
const getMemberRoleBadge = (member: any) => {
  const role = member.role
  if (role === 4) return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
  if (role === 3) return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
  if (role === 2) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
  return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
}

// Grade display
const getMemberGrade = (member: any) => {
  return member.draft_grade || member.grade_progress || '-'
}

// Score display
const getMemberScore = (member: any) => {
  const score = (member.achieved_score || 0) + (member.bonus_points || 0)
  return score > 0 ? score : '-'
}

// Completion status label
const getCompletionLabel = (status: string) => {
  const map: Record<string, string> = {
    'in_progress': 'กำลังเรียน',
    'pending_exam': 'รอสอบ',
    'pending_grade': 'รอเกรด',
    'pending_acceptance': 'รอยืนยัน',
    'completed': 'เรียนจบ',
    'failed': 'ไม่ผ่าน',
    'remediation': 'ซ่อม',
    'withdrawn': 'ถอนรายวิชา'
  }
  return map[status] || 'กำลังเรียน'
}

const getCompletionBadge = (status: string) => {
  const map: Record<string, string> = {
    'completed': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'failed': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    'remediation': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    'withdrawn': 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
  }
  return map[status] || 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
}

// Remove member from group (set group_id to null, keep as course member)
const isRemovingFromGroup = ref<number | null>(null)
const removeFromGroup = async (member: any) => {
  const memberName = getMemberName(member)
  if (!confirm(`ยืนยันการนำ "${memberName}" ออกจากกลุ่มนี้? (สมาชิกยังคงอยู่ในรายวิชา)`)) return

  // The remove endpoint keys on user_id (matches the members list payload)
  if (!member.user_id) return

  isRemovingFromGroup.value = member.user_id
  try {
    await api.delete(`/api/courses/${course.value.id}/groups/${groupId.value}/members/${member.user_id}`)
    await loadGroup()
  } catch (error: any) {
    alert(error.data?.message || 'ไม่สามารถนำสมาชิกออกจากกลุ่มได้')
  } finally {
    isRemovingFromGroup.value = null
  }
}

// View mode: 'list' or 'card'
const viewMode = ref<'list' | 'card'>('card')

// Search/filter members
const memberSearch = ref('')
const filteredMembers = computed(() => {
  let list = members.value
  if (memberSearch.value.trim()) {
    const q = memberSearch.value.toLowerCase()
    list = list.filter((m: any) => {
      const name = getMemberName(m).toLowerCase()
      const code = String(getMemberCode(m)).toLowerCase()
      const email = getMemberEmail(m).toLowerCase()
      return name.includes(q) || code.includes(q) || email.includes(q)
    })
  }
  return list.slice().sort((a: any, b: any) => (a.order_number || 9999) - (b.order_number || 9999))
})

// Inline order_number editing
const editingOrderId = ref<number | null>(null)
const editingOrderValue = ref<string>('')
const savingOrderId = ref<number | null>(null)

const startEditOrder = (member: any) => {
  editingOrderId.value = member.id
  editingOrderValue.value = String(member.order_number || '')
}

const cancelEditOrder = () => {
  editingOrderId.value = null
  editingOrderValue.value = ''
}

const saveOrderNumber = async (member: any) => {
  const newOrder = parseInt(editingOrderValue.value) || 0
  if (newOrder === (member.order_number || 0)) {
    cancelEditOrder()
    return
  }

  savingOrderId.value = member.id
  try {
    await api.patch(`/api/courses/${course.value.id}/members/${member.id}/order-number`, {
      order_number: newOrder
    })
    member.order_number = newOrder
    // Re-sort members
    members.value = members.value.slice().sort((a: any, b: any) => (a.order_number || 9999) - (b.order_number || 9999))
    cancelEditOrder()
  } catch (error: any) {
    alert(error.data?.message || 'ไม่สามารถบันทึกเลขที่ได้')
  } finally {
    savingOrderId.value = null
  }
}

// Stats for admin quick view
const completedCount = computed(() => members.value.filter((m: any) => m.completion_status === 'completed').length)
const inProgressCount = computed(() => members.value.filter((m: any) => !m.completion_status || m.completion_status === 'in_progress').length)

// Load on mount
onMounted(() => {
  loadGroup()
})
</script>

<template>
  <div>
    <!-- Loading State -->
    <div v-if="isLoading" class="flex items-center justify-center py-12">
      <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-blue-500" />
    </div>

    <!-- Group Details -->
    <div v-else-if="group" class="space-y-6">
      <!-- Header Card -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-lg">
        <!-- Cover -->
        <div 
          class="h-32 bg-gradient-to-br from-blue-400/80 via-indigo-400/80 to-violet-400/80"
          :style="group.cover ? { backgroundImage: `url(${config.public.apiBase}/storage/images/courses/groups/covers/${group.cover})`, backgroundSize: 'cover' } : {}"
        ></div>
        
        <!-- Content -->
        <div class="p-6">
          <div class="flex items-start justify-between gap-4">
            <!-- Group Info -->
            <div class="flex items-start gap-4 flex-1">
              <!-- Avatar -->
              <div 
                class="w-20 h-20 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg"
                :style="{ backgroundColor: group.color || '#3B82F6' }"
              >
                <Icon icon="heroicons:user-group" class="w-10 h-10 text-white" />
              </div>
              
              <!-- Details -->
              <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ group.name }}</h1>
                <p v-if="group.description" class="text-gray-600 dark:text-gray-400 mb-3">{{ group.description }}</p>
                
                <!-- Stats -->
                <div class="flex items-center gap-4 text-sm">
                  <span class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                    <Icon icon="heroicons:users" class="w-5 h-5" />
                    {{ group.members_count || 0 }} สมาชิก
                  </span>
                  <span v-if="group.max_members" class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                    <Icon icon="heroicons:user-plus" class="w-5 h-5" />
                    สูงสุด {{ group.max_members }} คน
                  </span>
                </div>
              </div>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center gap-2">
              <!-- Admin Actions -->
              <template v-if="isCourseAdmin">
                <button
                  v-if="course?.academy?.id"
                  @click="showSyncModal = true"
                  class="p-2 min-h-[44px] min-w-[44px] flex items-center justify-center text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors"
                  title="ซิงค์กับห้องเรียน"
                >
                  <Icon icon="fluent:arrow-sync-24-filled" class="w-5 h-5" />
                </button>
                <NuxtLink
                  :to="`/courses/${course.id}/groups/${groupId}/edit`"
                  class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                  title="แก้ไข"
                >
                  <Icon icon="fluent:edit-24-filled" class="w-5 h-5" />
                </NuxtLink>
                <button
                  @click="deleteGroup"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                  title="ลบ"
                >
                  <Icon icon="fluent:delete-24-filled" class="w-5 h-5" />
                </button>
              </template>
              
              <!-- Member Actions -->
              <template v-else>
                <button
                  v-if="!isMember"
                  @click="joinGroup"
                  :disabled="isJoining"
                  class="min-h-[44px] sm:min-h-0 flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
                >
                  <Icon v-if="isJoining" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                  <Icon v-else icon="heroicons:user-plus" class="w-5 h-5" />
                  <span>{{ isJoining ? 'กำลังเข้าร่วม...' : 'เข้าร่วมกลุ่ม' }}</span>
                </button>
                <button
                  v-else
                  @click="leaveGroup"
                  :disabled="isLeaving"
                  class="min-h-[44px] sm:min-h-0 flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
                >
                  <Icon v-if="isLeaving" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                  <Icon v-else icon="heroicons:arrow-left-on-rectangle" class="w-5 h-5" />
                  <span>{{ isLeaving ? 'กำลังออก...' : 'ออกจากกลุ่ม' }}</span>
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 mb-6 overflow-x-auto">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          class="flex items-center gap-2 px-4 py-3 border-b-2 font-medium transition-colors whitespace-nowrap"
          :class="activeTab === tab.id ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
        >
          <Icon :icon="tab.icon" class="w-5 h-5" />
          {{ tab.label }}
        </button>
      </div>



      <!-- Group Feed Tab -->
       <div v-if="activeTab === 'feed'">
            <CourseFeedsList
               :course-id="course.id"
               :group-id="groupId"
               :is-course-admin="isCourseAdmin"
            />
       </div>

      <!-- Resources Tab -->
       <div v-if="activeTab === 'resources'">
            <CourseGroupResources
               :course-id="course.id"
               :group-id="groupId"
               :is-course-admin="isCourseAdmin"
            />
       </div>

      <!-- About Tab -->
      <div v-if="activeTab === 'about'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
          <h2 class="text-xl font-bold mb-4">เกี่ยวกับกลุ่ม</h2>
          <p class="text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ group.description || 'ไม่มีคำอธิบาย' }}</p>
          <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                  <div class="text-sm text-gray-500">วันที่สร้าง</div>
                  <div class="font-medium">
                    {{ group.created_at ? new Date(group.created_at).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }) : '-' }}
                  </div>
              </div>
               <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                  <div class="text-sm text-gray-500">สถานะ</div>
                  <div class="font-medium">
                      {{ group.privacy === 'private' ? 'กลุ่มส่วนตัว' : 'กลุ่มสาธารณะ' }}
                  </div>
              </div>
          </div>
      </div>

      <!-- Members List -->
      <div v-if="activeTab === 'members'" class="space-y-4">
        <!-- Header + Search -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
              <Icon icon="heroicons:users" class="w-6 h-6" />
              สมาชิกกลุ่ม ({{ members.length }})
            </h2>
            <div class="flex items-center gap-2 w-full sm:w-auto">
              <!-- Search -->
              <div class="relative flex-1 sm:w-56">
                <Icon icon="heroicons:magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input
                  v-model="memberSearch"
                  type="text"
                  placeholder="ค้นหาสมาชิก..."
                  class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                />
              </div>
              <!-- View Toggle -->
              <div class="flex items-center bg-gray-100 dark:bg-gray-900 rounded-lg p-0.5">
                <button
                  @click="viewMode = 'list'"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1.5 rounded-md transition-colors"
                  :class="viewMode === 'list' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                  title="มุมมองรายการ"
                >
                  <Icon icon="heroicons:list-bullet" class="w-4 h-4" />
                </button>
                <button
                  @click="viewMode = 'card'"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1.5 rounded-md transition-colors"
                  :class="viewMode === 'card' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                  title="มุมมองการ์ด"
                >
                  <Icon icon="heroicons:squares-2x2" class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          <!-- Quick Stats (admin only) -->
          <div v-if="isCourseAdmin" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl text-center">
              <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ members.length }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">ทั้งหมด</div>
            </div>
            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl text-center">
              <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ completedCount }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">เรียนจบ</div>
            </div>
            <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl text-center">
              <div class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ inProgressCount }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">กำลังเรียน</div>
            </div>
            <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded-xl text-center">
              <div class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ requesters.length }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">รออนุมัติ</div>
            </div>
          </div>
        </div>

        <!-- Pending Requests (admin only) -->
        <div v-if="isCourseAdmin && requesters.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-orange-200 dark:border-orange-900/30 p-4 sm:p-6">
          <h3 class="text-lg font-semibold text-orange-600 dark:text-orange-400 mb-4 flex items-center gap-2">
            <Icon icon="heroicons:user-plus" class="w-5 h-5" />
            คำขอเข้าร่วม ({{ requesters.length }})
          </h3>
          <div class="space-y-3">
            <div 
              v-for="req in requesters" 
              :key="req.id"
              class="flex items-center gap-3 p-3 bg-orange-50 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-900/30 rounded-xl"
            >
              <img 
                :src="getMemberAvatar(req)" 
                :alt="getMemberName(req)"
                class="w-10 h-10 rounded-full object-cover flex-shrink-0"
              >
              <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 dark:text-white truncate">{{ getMemberName(req) }}</p>
                <p class="text-xs text-orange-500">รออนุมัติ</p>
              </div>
              <div class="flex items-center gap-2">
                <button 
                  @click="approveMember(req.id)"
                  class="min-h-[44px] sm:min-h-0 px-3 py-1.5 text-sm font-medium text-white bg-green-500 hover:bg-green-600 rounded-lg transition-colors flex items-center gap-1"
                >
                  <Icon icon="fluent:checkmark-24-filled" class="w-4 h-4" />
                  <span class="hidden sm:inline">อนุมัติ</span>
                </button>
                <button 
                  @click="rejectMember(req.id)"
                  class="min-h-[44px] sm:min-h-0 px-3 py-1.5 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors flex items-center gap-1"
                >
                  <Icon icon="fluent:dismiss-24-filled" class="w-4 h-4" />
                  <span class="hidden sm:inline">ปฏิเสธ</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Members List -->
        <div v-if="filteredMembers.length > 0">

          <!-- === LIST VIEW === -->
          <div v-if="viewMode === 'list'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Table Header (desktop) -->
            <div class="hidden lg:grid lg:grid-cols-12 gap-2 px-4 sm:px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
              <div class="col-span-1 text-center">#</div>
              <div class="col-span-4">ชื่อสมาชิก</div>
              <div class="col-span-2 text-center">บทบาท</div>
              <div class="col-span-1 text-center">คะแนน</div>
              <div class="col-span-2 text-center">สถานะ</div>
              <div class="col-span-1 text-center">โปรไฟล์</div>
              <div v-if="isCourseAdmin" class="col-span-1 text-center">จัดการ</div>
            </div>

            <!-- Member Rows -->
            <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
              <div 
                v-for="member in filteredMembers" 
                :key="member.id"
                class="group/row hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
              >
                <!-- Desktop List Row -->
                <div class="hidden lg:grid lg:grid-cols-12 gap-2 items-center px-4 sm:px-6 py-3">
                  <!-- # (editable for admin) -->
                  <div class="col-span-1 text-center">
                    <template v-if="isCourseAdmin && editingOrderId === member.id">
                      <div class="flex items-center gap-1">
                        <input
                          v-model="editingOrderValue"
                          type="number"
                          min="0"
                          class="w-12 px-1 py-0.5 text-sm text-center bg-white dark:bg-gray-900 border border-blue-400 rounded focus:ring-1 focus:ring-blue-500 outline-none"
                          @keyup.enter="saveOrderNumber(member)"
                          @keyup.escape="cancelEditOrder"
                          :disabled="savingOrderId === member.id"
                        />
                        <button @click="saveOrderNumber(member)" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-0.5 text-green-500 hover:text-green-600" :disabled="savingOrderId === member.id">
                          <Icon v-if="savingOrderId === member.id" icon="svg-spinners:ring-resize" class="w-3.5 h-3.5" />
                          <Icon v-else icon="heroicons:check-20-solid" class="w-3.5 h-3.5" />
                        </button>
                        <button @click="cancelEditOrder" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-0.5 text-gray-400 hover:text-gray-600">
                          <Icon icon="heroicons:x-mark-20-solid" class="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </template>
                    <template v-else>
                      <span 
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                        :class="{ 'cursor-pointer hover:text-blue-500 hover:underline': isCourseAdmin }"
                        @click="isCourseAdmin && startEditOrder(member)"
                        :title="isCourseAdmin ? 'คลิกเพื่อแก้ไขเลขที่' : ''"
                      >
                        {{ member.order_number || '-' }}
                      </span>
                    </template>
                  </div>
                  <!-- Name + Email -->
                  <div class="col-span-4 flex items-center gap-3 min-w-0">
                    <img 
                      :src="getMemberAvatar(member)" 
                      :alt="getMemberName(member)"
                      class="w-10 h-10 rounded-full object-cover flex-shrink-0"
                    >
                    <div class="min-w-0">
                      <p class="font-medium text-gray-900 dark:text-white truncate">{{ getMemberName(member) }}</p>
                      <p class="text-xs text-gray-400 dark:text-gray-500 truncate">
                        {{ getMemberEmail(member) || `รหัส: ${getMemberCode(member)}` }}
                      </p>
                    </div>
                  </div>
                  <!-- Role -->
                  <div class="col-span-2 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getMemberRoleBadge(member)">
                      {{ getMemberRoleLabel(member) }}
                    </span>
                  </div>
                  <!-- Score -->
                  <div class="col-span-1 text-center">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ getMemberScore(member) }}</span>
                  </div>
                  <!-- Completion Status -->
                  <div class="col-span-2 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getCompletionBadge(member.completion_status)">
                      {{ getCompletionLabel(member.completion_status) }}
                    </span>
                  </div>
                  <!-- Profile Link -->
                  <div class="col-span-1 text-center">
                    <NuxtLink
                      :to="`/Learn/Courses/${course?.id}/members/${member.id}`"
                      class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                      title="ดูรายละเอียด"
                    >
                      <Icon icon="heroicons:arrow-top-right-on-square-20-solid" class="w-3.5 h-3.5" />
                      <span class="hidden xl:inline">ดูข้อมูล</span>
                    </NuxtLink>
                  </div>
                  <!-- Actions (admin) -->
                  <div v-if="isCourseAdmin" class="col-span-1 text-center">
                    <button
                      @click.stop="removeFromGroup(member)"
                      :disabled="isRemovingFromGroup === member.user_id"
                      class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                      title="นำออกจากกลุ่ม"
                    >
                      <Icon v-if="isRemovingFromGroup === member.user_id" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                      <Icon v-else icon="heroicons:user-minus" class="w-4 h-4" />
                    </button>
                  </div>
                </div>

                <!-- Mobile List Row -->
                <div class="lg:hidden p-4">
                  <div class="flex items-start gap-3">
                    <!-- Order + Avatar -->
                    <div class="flex items-center gap-2">
                      <template v-if="isCourseAdmin && editingOrderId === member.id">
                        <div class="flex flex-col items-center gap-1">
                          <input
                            v-model="editingOrderValue"
                            type="number"
                            min="0"
                            class="w-10 px-1 py-0.5 text-xs text-center bg-white dark:bg-gray-900 border border-blue-400 rounded focus:ring-1 focus:ring-blue-500 outline-none"
                            @keyup.enter="saveOrderNumber(member)"
                            @keyup.escape="cancelEditOrder"
                            :disabled="savingOrderId === member.id"
                          />
                          <div class="flex gap-0.5">
                            <button @click="saveOrderNumber(member)" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-0.5 text-green-500" :disabled="savingOrderId === member.id">
                              <Icon v-if="savingOrderId === member.id" icon="svg-spinners:ring-resize" class="w-3 h-3" />
                              <Icon v-else icon="heroicons:check-20-solid" class="w-3 h-3" />
                            </button>
                            <button @click="cancelEditOrder" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-0.5 text-gray-400">
                              <Icon icon="heroicons:x-mark-20-solid" class="w-3 h-3" />
                            </button>
                          </div>
                        </div>
                      </template>
                      <span 
                        v-else
                        class="text-xs font-medium text-gray-400 w-5 text-right"
                        :class="{ 'cursor-pointer hover:text-blue-500': isCourseAdmin }"
                        @click="isCourseAdmin && startEditOrder(member)"
                      >
                        {{ member.order_number || '-' }}
                      </span>
                      <img 
                        :src="getMemberAvatar(member)" 
                        :alt="getMemberName(member)"
                        class="w-10 h-10 rounded-full object-cover flex-shrink-0"
                      >
                    </div>
                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ getMemberName(member) }}</p>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium" :class="getMemberRoleBadge(member)">
                          {{ getMemberRoleLabel(member) }}
                        </span>
                      </div>
                      <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <span v-if="getMemberScore(member) !== '-'" class="text-[11px] text-gray-500 dark:text-gray-400">
                          คะแนน: <span class="font-semibold">{{ getMemberScore(member) }}</span>
                        </span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium" :class="getCompletionBadge(member.completion_status)">
                          {{ getCompletionLabel(member.completion_status) }}
                        </span>
                        <NuxtLink
                          :to="`/Learn/Courses/${course?.id}/members/${member.id}`"
                          class="inline-flex items-center gap-0.5 text-[11px] text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                          <Icon icon="heroicons:arrow-top-right-on-square-20-solid" class="w-3 h-3" />
                          ดูข้อมูล
                        </NuxtLink>
                      </div>
                    </div>
                    <!-- Admin Actions (always visible) -->
                    <button
                      v-if="isCourseAdmin"
                      @click.stop="removeFromGroup(member)"
                      :disabled="isRemovingFromGroup === member.user_id"
                      class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors flex-shrink-0"
                      title="นำออกจากกลุ่ม"
                    >
                      <Icon v-if="isRemovingFromGroup === member.user_id" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                      <Icon v-else icon="heroicons:user-minus" class="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- === CARD VIEW === -->
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            <div
              v-for="member in filteredMembers"
              :key="member.id"
              class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow relative"
            >
              <!-- Admin action (top-right, always visible) -->
              <button
                v-if="isCourseAdmin"
                @click.stop="removeFromGroup(member)"
                :disabled="isRemovingFromGroup === member.user_id"
                class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center absolute top-3 right-3 p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                title="นำออกจากกลุ่ม"
              >
                <Icon v-if="isRemovingFromGroup === member.user_id" icon="svg-spinners:ring-resize" class="w-4 h-4" />
                <Icon v-else icon="heroicons:user-minus" class="w-4 h-4" />
              </button>

              <!-- Avatar + Name -->
              <div class="flex flex-col items-center text-center mb-3">
                <div class="relative">
                  <img 
                    :src="getMemberAvatar(member)" 
                    :alt="getMemberName(member)"
                    class="w-16 h-16 rounded-full object-cover border-2 border-gray-100 dark:border-gray-700"
                  >
                  <!-- Order number badge -->
                  <template v-if="isCourseAdmin && editingOrderId === member.id">
                    <div class="absolute -top-1 -left-1 flex items-center gap-0.5">
                      <input
                        v-model="editingOrderValue"
                        type="number"
                        min="0"
                        class="w-10 px-1 py-0.5 text-[10px] text-center bg-white dark:bg-gray-900 border border-blue-400 rounded focus:ring-1 focus:ring-blue-500 outline-none"
                        @keyup.enter="saveOrderNumber(member)"
                        @keyup.escape="cancelEditOrder"
                        :disabled="savingOrderId === member.id"
                      />
                      <button @click="saveOrderNumber(member)" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-0.5 text-green-500" :disabled="savingOrderId === member.id">
                        <Icon v-if="savingOrderId === member.id" icon="svg-spinners:ring-resize" class="w-3 h-3" />
                        <Icon v-else icon="heroicons:check-20-solid" class="w-3 h-3" />
                      </button>
                      <button @click="cancelEditOrder" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-0.5 text-gray-400">
                        <Icon icon="heroicons:x-mark-20-solid" class="w-3 h-3" />
                      </button>
                    </div>
                  </template>
                  <span
                    v-else-if="member.order_number"
                    class="absolute -top-1 -left-1 min-w-[20px] h-5 flex items-center justify-center px-1 text-[10px] font-bold text-white bg-gray-500 dark:bg-gray-600 rounded-full"
                    :class="{ 'cursor-pointer hover:bg-blue-500': isCourseAdmin }"
                    @click="isCourseAdmin && startEditOrder(member)"
                  >
                    {{ member.order_number }}
                  </span>
                </div>
                <p class="font-semibold text-gray-900 dark:text-white text-sm mt-2 truncate max-w-full">{{ getMemberName(member) }}</p>
                <p v-if="getMemberEmail(member)" class="text-[11px] text-gray-400 dark:text-gray-500 truncate max-w-full">{{ getMemberEmail(member) }}</p>
                <p v-else-if="getMemberCode(member) !== '-'" class="text-[11px] text-gray-400 truncate">รหัส: {{ getMemberCode(member) }}</p>
              </div>

              <!-- Role Badge -->
              <div class="flex justify-center mb-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium" :class="getMemberRoleBadge(member)">
                  {{ getMemberRoleLabel(member) }}
                </span>
              </div>

              <!-- Stats Grid -->
              <div class="grid grid-cols-2 gap-2 text-center border-t border-gray-100 dark:border-gray-700 pt-3">
                <div>
                  <div class="text-sm font-bold text-gray-900 dark:text-white">{{ getMemberScore(member) }}</div>
                  <div class="text-[10px] text-gray-400">คะแนน</div>
                </div>
                <div>
                  <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium" :class="getCompletionBadge(member.completion_status)">
                    {{ getCompletionLabel(member.completion_status) }}
                  </span>
                  <div class="text-[10px] text-gray-400 mt-0.5">สถานะ</div>
                </div>
              </div>

              <!-- Profile Link -->
              <NuxtLink
                :to="`/Learn/Courses/${course?.id}/members/${member.id}`"
                class="flex items-center justify-center gap-1.5 mt-3 py-2 text-xs font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-900/30 transition-colors"
              >
                <Icon icon="heroicons:arrow-top-right-on-square-20-solid" class="w-3.5 h-3.5" />
                ดูรายละเอียด
              </NuxtLink>
            </div>
          </div>

        </div>
        
        <!-- No search results -->
        <div v-else-if="memberSearch && members.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 sm:p-8 text-center">
          <Icon icon="heroicons:magnifying-glass" class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
          <p class="text-gray-500 dark:text-gray-400">ไม่พบสมาชิกที่ค้นหา "{{ memberSearch }}"</p>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 sm:p-8 text-center">
          <Icon icon="heroicons:user-group" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
          <p class="text-gray-500 dark:text-gray-400">ยังไม่มีสมาชิกในกลุ่มนี้</p>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="text-center py-12">
      <Icon icon="heroicons:exclamation-circle" class="w-16 h-16 text-red-500 mx-auto mb-4" />
      <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบข้อมูลกลุ่ม</h3>
      <NuxtLink 
        :to="`/courses/${course?.id}/groups`"
        class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700"
      >
        <Icon icon="heroicons:arrow-left" class="w-5 h-5" />
        กลับไปหน้ากลุ่ม
      </NuxtLink>
    </div>

    <SyncClassroomModal
      :show="showSyncModal"
      :course-id="course?.id"
      :group-id="groupId"
      :group-name="group?.name"
      @close="showSyncModal = false"
      @synced="loadGroup"
    />
  </div>
</template>

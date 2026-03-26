<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseFeedsList from '~/components/learn/course/CourseFeedsList.vue'
import CourseGroupResources from '~/components/learn/course/groups/CourseGroupResources.vue'
import CourseGroupAttendance from '@/components/learn/course/attendances/CourseGroupAttendance.vue'

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
const activeTab = ref('about')

const tabs = computed(() => {
  const baseTabs = [
    { id: 'attendance', label: 'การเข้าเรียน', icon: 'heroicons:calendar-days' },
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
    members.value = group.value.members || []
    
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

// Remove member from group (admin only)
const isRemovingMember = ref<number | null>(null)

const removeMember = async (member: any) => {
  const memberName = getMemberName(member)
  if (!confirm(`ยืนยันการลบ "${memberName}" ออกจากกลุ่มนี้?`)) return

  const userId = member.user_id || member.user?.id
  if (!userId) return

  isRemovingMember.value = member.id
  try {
    await api.delete(`/api/courses/${course.value.id}/groups/${groupId.value}/members/${userId}`)
    await loadGroup()
  } catch (error: any) {
    alert(error.data?.message || 'ไม่สามารถลบสมาชิกได้')
  } finally {
    isRemovingMember.value = null
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
  return user?.name || 'Unknown User'
}

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
          class="h-32 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500"
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
                <NuxtLink
                  :to="`/courses/${course.id}/groups/${groupId}/edit`"
                  class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                  title="แก้ไข"
                >
                  <Icon icon="fluent:edit-24-filled" class="w-5 h-5" />
                </NuxtLink>
                <button
                  @click="deleteGroup"
                  class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
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
                  class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-blue-500 hover:from-purple-600 hover:to-blue-600 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
                >
                  <Icon v-if="isJoining" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                  <Icon v-else icon="heroicons:user-plus" class="w-5 h-5" />
                  <span>{{ isJoining ? 'กำลังเข้าร่วม...' : 'เข้าร่วมกลุ่ม' }}</span>
                </button>
                <button
                  v-else
                  @click="leaveGroup"
                  :disabled="isLeaving"
                  class="flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition-colors disabled:opacity-50"
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



      <!-- Attendance Tab -->
       <div v-if="activeTab === 'attendance'">
        <CourseGroupAttendance 
            v-if="group"
            :groups="[group]"
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
      <div v-if="activeTab === 'about'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
          <h2 class="text-xl font-bold mb-4">เกี่ยวกับกลุ่ม</h2>
          <p class="text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ group.description || 'ไม่มีคำอธิบาย' }}</p>
          <div class="mt-6 grid grid-cols-2 gap-4">
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
      <div v-if="activeTab === 'members'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <Icon icon="heroicons:users" class="w-6 h-6" />
          สมาชิกกลุ่ม ({{ members.length }})
        </h2>

        <!-- Pending Requests -->
        <div v-if="requesters.length > 0" class="mb-8">
          <h3 class="text-lg font-semibold text-orange-500 mb-3 flex items-center gap-2">
            <Icon icon="heroicons:user-plus" class="w-5 h-5" />
            คำขอเข้าร่วม ({{ requesters.length }})
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div 
              v-for="req in requesters" 
              :key="req.id"
              class="flex items-center gap-3 p-3 bg-orange-50 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-900/30 rounded-lg"
            >
              <img 
                :src="getMemberAvatar(req)" 
                :alt="getMemberName(req)"
                class="w-10 h-10 rounded-full object-cover"
              >
              <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 dark:text-white truncate">{{ getMemberName(req) }}</p>
                <p class="text-xs text-orange-500">รออนุมัติ</p>
              </div>
              <div class="flex items-center gap-1">
                <button 
                  @click="approveMember(req.id)"
                  class="p-1 text-green-600 hover:bg-green-100 rounded"
                  title="อนุมัติ"
                >
                  <Icon icon="fluent:checkmark-24-filled" class="w-5 h-5" />
                </button>
                <button 
                  @click="rejectMember(req.id)"
                  class="p-1 text-red-600 hover:bg-red-100 rounded"
                  title="ปฏิเสธ"
                >
                  <Icon icon="fluent:dismiss-24-filled" class="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Members Grid -->
        <div v-if="members.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div 
            v-for="member in members" 
            :key="member.id"
            class="group flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors"
          >
            <img 
              :src="getMemberAvatar(member)" 
              :alt="getMemberName(member)"
              class="w-10 h-10 rounded-full object-cover"
            >
            <div class="flex-1 min-w-0">
              <p class="font-medium text-gray-900 dark:text-white truncate">{{ getMemberName(member) }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">สมาชิก</p>
            </div>
            <!-- Remove button (admin only) -->
            <button
              v-if="isCourseAdmin"
              @click.stop="removeMember(member)"
              :disabled="isRemovingMember === member.id"
              class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors opacity-0 group-hover:opacity-100 disabled:opacity-50"
              title="ลบสมาชิกออกจากกลุ่ม"
            >
              <Icon v-if="isRemovingMember === member.id" icon="svg-spinners:ring-resize" class="w-4 h-4" />
              <Icon v-else icon="heroicons:x-mark" class="w-4 h-4" />
            </button>
          </div>
        </div>
        
        <!-- Empty State -->
        <div v-else class="text-center py-8">
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
  </div>
</template>

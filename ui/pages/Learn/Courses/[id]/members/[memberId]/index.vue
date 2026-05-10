<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useMemberProgress } from '~/composables/useMemberProgress'
import { useCourseGroupStore } from '~/stores/courseGroup'

const route = useRoute()
const api = useApi()
const swal = useSweetAlert()
const courseGroupStore = useCourseGroupStore()

const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')

const courseId = computed(() => route.params.id as string)
const memberId = computed(() => route.params.memberId as string)

const loading = ref(true)
const member = ref<any>(null)
const groups = ref<any[]>([])
const lessons = ref<any[]>([])
const assignments = ref<any[]>([])
const quizzes = ref<any[]>([])

const config = useRuntimeConfig()

// ✅ Instant preview: use cached member data from store while API loads
const cachedMember = computed(() => {
  const mid = Number(memberId.value)
  // Search in all groups
  for (const g of courseGroupStore.groups) {
    const found = g.members?.find((m: any) => m.id === mid)
    if (found) return found
  }
  // Search ungrouped
  return courseGroupStore.ungroupedMembers?.find((m: any) => m.id === mid) || null
})

// Use API member if loaded, otherwise show cached preview
const displayMember = computed(() => member.value || cachedMember.value)
const hasFullData = computed(() => !!member.value)

const memberAvatar = computed(() => {
  const m = displayMember.value
  if (!m) return '/images/default-avatar.png'
  // From cached store data
  if (m.avatar) return m.avatar
  // From full API data
  const user = m.user
  if (!user) return '/images/default-avatar.png'
  if (user.profile_photo_url) return user.profile_photo_url
  if (user.avatar) return user.avatar
  if (user.profile_photo_path) return `${config.public.apiBase}/storage/${user.profile_photo_path}`
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || 'U')}&background=random`
})

const memberDisplayName = computed(() => {
  const m = displayMember.value
  return m?.member_name || m?.user?.name || m?.name || '-'
})

const memberCode = computed(() => {
  const m = displayMember.value
  return m?.member_code || m?.user?.email || '-'
})

const memberGroup = computed(() => {
  const m = displayMember.value
  // From cached data
  if (m?.group) return m.group
  // From API data
  if (!m?.group_id || !groups.value.length) return null
  return groups.value.find((g: any) => g.id === m.group_id)
})

const memberOrderNumber = computed(() => displayMember.value?.order_number || '-')

const courseTotalScoreRef = computed(() => course?.value?.total_score || 100)
const { 
  percentage, 
  progressStatus, 
  progressColor, 
  statusMessage,
  statusIcon,
  totalAchievedScore,
  remainingScore
} = useMemberProgress(displayMember, courseTotalScoreRef)

const getRoleBadge = (role?: number) => {
  const roles: Record<number, { label: string; color: string; icon: string }> = {
    1: { label: 'นักเรียน', color: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300', icon: 'fluent:hat-graduation-24-regular' },
    2: { label: 'ผู้ช่วยสอน', color: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300', icon: 'fluent:person-support-24-regular' },
    4: { label: 'ผู้สอน', color: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', icon: 'fluent:person-board-24-regular' },
  }
  return roles[role || 1] || roles[1]
}

const getStatusLabel = (status?: number) => {
  const statuses: Record<number, { label: string; color: string }> = {
    1: { label: 'ปกติ', color: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' },
    0: { label: 'พักการเรียน', color: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' },
  }
  return statuses[status ?? 1] || statuses[1]
}

const formatDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' })
}

const formatLastActivity = (dateStr?: string) => {
  if (!dateStr) return 'ไม่เคยเข้าใช้งาน'
  const date = new Date(dateStr)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  if (days === 0) return 'วันนี้'
  if (days === 1) return 'เมื่อวาน'
  if (days < 7) return `${days} วันที่แล้ว`
  return formatDate(dateStr)
}

const fetchMember = async () => {
  loading.value = true
  try {
    const response: any = await api.get(`/api/courses/${courseId.value}/members/${memberId.value}/member-settings`)
    
    member.value = response.member?.data || response.member
    groups.value = response.groups?.data || response.groups || []
    lessons.value = response.lessons?.data || response.lessons || []
    assignments.value = response.assignments?.data || response.assignments || []
    quizzes.value = response.quizzes?.data || response.quizzes || []
  } catch (error: any) {
    console.error('Error fetching member:', error)
    swal.error('ไม่สามารถโหลดข้อมูลสมาชิกได้')
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  navigateTo(`/Learn/Courses/${courseId.value}/members`)
}

const goEdit = () => {
  navigateTo(`/Learn/Courses/${courseId.value}/members/${memberId.value}/edit`)
}

onMounted(() => {
  fetchMember()
})
</script>

<template>
  <div class="container mx-auto px-3 sm:px-4 py-4 sm:py-8 max-w-4xl pb-24 lg:pb-8">
    <!-- Back Button -->
    <button 
      @click="goBack"
      class="flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-6 transition-colors"
    >
      <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
      <span>กลับไปรายชื่อสมาชิก</span>
    </button>

    <!-- Loading State (only if no cached data available) -->
    <div v-if="loading && !displayMember" class="flex justify-center items-center py-20">
      <Icon icon="svg-spinners:ring-resize" class="w-10 h-10 text-blue-500" />
    </div>

    <!-- Content (show immediately with cached data, enrich with API data) -->
    <div v-else-if="displayMember" class="space-y-6">
      <!-- Header Card -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Gradient Banner -->
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-24 relative">
          <!-- Edit Button -->
          <button 
            v-if="isCourseAdmin"
            @click="goEdit"
            class="absolute top-4 right-4 flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-lg transition-colors text-sm font-medium"
          >
            <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
            แก้ไขข้อมูล
          </button>
        </div>

        <div class="px-6 pb-6 -mt-12">
          <div class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
            <!-- Avatar -->
            <div class="relative flex-shrink-0">
              <img 
                :src="memberAvatar" 
                :alt="memberDisplayName"
                class="w-24 h-24 rounded-xl border-4 border-white dark:border-gray-800 shadow-lg object-cover ring-2"
                :class="progressColor.ring"
              >
              <!-- Progress Badge -->
              <div 
                class="absolute -bottom-2 -right-2 px-2.5 py-1 rounded-full text-xs font-bold text-white shadow-lg"
                :class="progressColor.bg"
              >
                {{ percentage }}%
              </div>
            </div>

            <!-- Name & Badges -->
            <div class="flex-1 min-w-0 pt-2">
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white break-words">
                {{ memberDisplayName }}
              </h1>
              <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ memberCode }}</p>
              <div class="flex items-center flex-wrap gap-2 mt-2">
                <!-- Role Badge -->
                <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium', getRoleBadge(displayMember.role).color]">
                  <Icon :icon="getRoleBadge(displayMember.role).icon" class="w-4 h-4" />
                  {{ getRoleBadge(displayMember.role).label }}
                </span>
                <!-- Status Badge -->
                <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium', getStatusLabel(displayMember.course_member_status).color]">
                  {{ getStatusLabel(displayMember.course_member_status).label }}
                </span>
                <!-- Progress Status -->
                <span class="inline-flex items-center gap-1 text-xs font-semibold" :class="progressColor.text">
                  <Icon :icon="statusIcon" class="w-4 h-4" />
                  {{ statusMessage }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <!-- Order Number -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
          <Icon icon="fluent:number-symbol-square-20-filled" class="w-8 h-8 text-blue-500 mx-auto mb-2" />
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ memberOrderNumber }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เลขที่</p>
        </div>

        <!-- Group -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
          <Icon icon="heroicons:user-group-solid" class="w-8 h-8 text-indigo-500 mx-auto mb-2" />
          <p class="text-lg font-bold text-gray-900 dark:text-white truncate">{{ memberGroup?.name || 'ไม่มีกลุ่ม' }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">กลุ่มเรียน</p>
        </div>

        <!-- Score -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
          <Icon icon="heroicons:academic-cap-solid" class="w-8 h-8 mx-auto mb-2" :class="progressColor.text" />
          <p class="text-2xl font-bold" :class="progressColor.text">{{ totalAchievedScore }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">คะแนน / {{ courseTotalScoreRef }}</p>
        </div>

        <!-- Remaining -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
          <Icon icon="fluent:target-arrow-24-filled" class="w-8 h-8 text-amber-500 mx-auto mb-2" />
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ remainingScore }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">คะแนนที่เหลือ</p>
        </div>
      </div>

      <!-- Progress Bar -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
            <Icon icon="heroicons:chart-bar-solid" class="w-5 h-5 text-indigo-500" />
            ความคืบหน้าโดยรวม
          </h3>
          <span class="text-sm font-bold" :class="progressColor.text">{{ percentage }}%</span>
        </div>
        <div class="relative w-full bg-gray-100 dark:bg-gray-700 rounded-full h-5 overflow-hidden">
          <div 
            class="h-full rounded-full transition-all duration-700 ease-out bg-gradient-to-r relative overflow-hidden"
            :class="progressColor.gradient"
            :style="{ width: `${percentage}%` }"
          >
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/25 to-transparent animate-pulse"></div>
          </div>
        </div>
        <div class="flex items-center justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
          <span>{{ totalAchievedScore }} คะแนน</span>
          <span>เป้าหมาย {{ courseTotalScoreRef }} คะแนน</span>
        </div>
      </div>

      <!-- Detail Info -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
            <Icon icon="fluent:info-24-regular" class="w-5 h-5 text-blue-500" />
            ข้อมูลทั่วไป
          </h3>
          <Icon v-if="loading" icon="svg-spinners:ring-resize" class="w-4 h-4 text-blue-400" />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Lessons Completed -->
          <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div class="p-2 bg-blue-100 dark:bg-blue-900/40 rounded-lg">
              <Icon icon="fluent:book-24-regular" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">บทเรียนที่เสร็จ</p>
              <p class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ displayMember.lessons_completed || 0 }} / {{ lessons.length || '-' }} บท
              </p>
            </div>
          </div>

          <!-- Assignments Submitted -->
          <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div class="p-2 bg-purple-100 dark:bg-purple-900/40 rounded-lg">
              <Icon icon="fluent:clipboard-task-24-regular" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">งานที่ส่ง</p>
              <p class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ displayMember.assignments_submitted || 0 }} / {{ assignments.length || '-' }} งาน
              </p>
            </div>
          </div>

          <!-- Attendance -->
          <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div class="p-2 bg-green-100 dark:bg-green-900/40 rounded-lg">
              <Icon icon="fluent:calendar-checkmark-24-regular" class="w-5 h-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">อัตราการเข้าเรียน</p>
              <p class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ displayMember.attendance_rate || 0 }}%
              </p>
            </div>
          </div>

          <!-- Last Activity -->
          <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div class="p-2 bg-amber-100 dark:bg-amber-900/40 rounded-lg">
              <Icon icon="fluent:clock-24-regular" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400">ใช้งานล่าสุด</p>
              <p class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ formatLastActivity(displayMember.last_activity_at) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Admin Actions -->
      <div v-if="isCourseAdmin" class="flex flex-wrap items-center gap-3">
        <button
          @click="goEdit"
          class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-sm"
        >
          <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
          แก้ไขข้อมูลสมาชิก
        </button>
        <button
          @click="goBack"
          class="flex items-center gap-2 px-5 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium text-sm"
        >
          กลับไปรายชื่อสมาชิก
        </button>
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="text-center py-20">
      <Icon icon="fluent:person-warning-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
      <p class="text-gray-500 dark:text-gray-400 text-lg">ไม่พบข้อมูลสมาชิก</p>
      <button @click="goBack" class="mt-4 text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">
        กลับไปรายชื่อสมาชิก
      </button>
    </div>
  </div>
</template>

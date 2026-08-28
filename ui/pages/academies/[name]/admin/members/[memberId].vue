<script setup lang="ts">
/**
 * Academy Member Profile Page
 * หน้าแสดงรายละเอียดสมาชิกแบบครบถ้วน
 */
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()

const academyName = computed(() => route.params.name as string)
const memberId = computed(() => route.params.memberId as string)

// State
const academy = ref<any>(null)
const member = ref<any>(null)
const isLoading = ref(true)
const activeTab = ref('overview')

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Tabs
const tabs = [
  { id: 'overview', name: 'ภาพรวม', icon: 'fluent:person-24-regular' },
  { id: 'courses', name: 'คอร์สเรียน', icon: 'fluent:book-24-regular' },
  { id: 'activity', name: 'กิจกรรม', icon: 'fluent:history-24-regular' },
  { id: 'settings', name: 'ตั้งค่า', icon: 'fluent:settings-24-regular' },
]

// Member Courses
const memberCourses = ref<any[]>([])
const isLoadingCourses = ref(false)

// Activity Log
const activityLog = ref<any[]>([])
const isLoadingActivity = ref(false)

onMounted(async () => {
  try {
    // Load academy info
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!can('members.view')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await loadMemberProfile()
    }
  } catch (err) {
    console.error('Failed to load:', err)
    navigateTo(`/academies/${academyName.value}/admin/members`)
  } finally {
    isLoading.value = false
  }
})

const loadMemberProfile = async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/members/${memberId.value}/profile`)
    if (response.success) {
      member.value = response.member
      // Redirect to master profile when student record is linked
      if (response.member?.student?.id) {
        navigateTo(`/academies/${academyName.value}/students/${response.member.student.id}/profile`, { replace: true })
        return
      }
    }
  } catch (err) {
    console.error('Failed to load member profile:', err)
    Swal.fire('เกิดข้อผิดพลาด', 'ไม่พบข้อมูลสมาชิก', 'error')
    navigateTo(`/academies/${academyName.value}/admin/members`)
  }
}

const loadMemberCourses = async () => {
  if (memberCourses.value.length > 0) return
  
  isLoadingCourses.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/members/${memberId.value}/courses`)
    if (response.success) {
      memberCourses.value = response.courses || []
    }
  } catch (err) {
    console.error('Failed to load courses:', err)
  } finally {
    isLoadingCourses.value = false
  }
}

const loadActivityLog = async () => {
  if (activityLog.value.length > 0) return
  
  isLoadingActivity.value = true
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/members/${memberId.value}/activity`)
    if (response.success) {
      activityLog.value = response.activities || []
    }
  } catch (err) {
    console.error('Failed to load activity:', err)
  } finally {
    isLoadingActivity.value = false
  }
}

// Watch for tab changes
watch(activeTab, async (tab) => {
  if (tab === 'courses') await loadMemberCourses()
  if (tab === 'activity') await loadActivityLog()
})

// Helper functions
const getMemberName = () => member.value?.member_name || member.value?.user?.name || 'ไม่ระบุ'

const getMemberAvatar = () => 
  member.value?.member_avatar || 
  member.value?.user?.avatar || 
  member.value?.user?.profile_photo_url || 
  '/images/default-avatar.png'

const getStatusBadge = (status: number) => {
  const statuses: Record<number, { label: string; color: string; bgColor: string; icon: string }> = {
    1: { label: 'รอการอนุมัติ', color: 'text-yellow-700', bgColor: 'bg-yellow-100 dark:bg-yellow-900/40', icon: 'fluent:clock-24-regular' },
    2: { label: 'สมาชิก', color: 'text-green-700', bgColor: 'bg-green-100 dark:bg-green-900/40', icon: 'fluent:checkmark-circle-24-regular' },
    3: { label: 'ถูกปฏิเสธ', color: 'text-red-700', bgColor: 'bg-red-100 dark:bg-red-900/40', icon: 'fluent:dismiss-circle-24-regular' },
    4: { label: 'ได้รับเชิญ', color: 'text-blue-700', bgColor: 'bg-blue-100 dark:bg-blue-900/40', icon: 'fluent:mail-24-regular' },
    5: { label: 'ถูกระงับ', color: 'text-orange-700', bgColor: 'bg-orange-100 dark:bg-orange-900/40', icon: 'fluent:person-prohibited-24-regular' },
  }
  return statuses[status] || { label: 'ไม่ทราบ', color: 'text-gray-700', bgColor: 'bg-gray-100', icon: 'fluent:question-24-regular' }
}

const formatDate = (dateStr?: string) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' })
}

const formatDateTime = (dateStr?: string) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('th-TH', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Actions
const goBack = () => {
  navigateTo(`/academies/${academyName.value}/admin/members`)
}
</script>

<template>
  <div class="px-4 sm:px-0">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else-if="member" class="space-y-6">
      <!-- Back Button -->
      <button 
        @click="goBack"
        class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        <span>กลับไปรายการสมาชิก</span>
      </button>

      <!-- Profile Header Card -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <!-- Cover/Background -->
        <div class="h-24 sm:h-32 bg-gradient-to-r from-primary-500 to-indigo-600"></div>
        
        <!-- Profile Info -->
        <div class="px-6 pb-6">
          <div class="flex flex-col sm:flex-row sm:items-end gap-4 -mt-12 sm:-mt-16">
            <!-- Avatar -->
            <div class="relative">
              <img
                :src="getMemberAvatar()"
                :alt="getMemberName()"
                class="w-24 h-24 sm:w-32 sm:h-32 rounded-2xl object-cover ring-4 ring-white dark:ring-gray-800 bg-gray-100"
              />
              <!-- Status Badge -->
              <div 
                class="absolute -bottom-1 -right-1 w-8 h-8 flex items-center justify-center rounded-full shadow-lg"
                :class="getStatusBadge(member.status).bgColor"
              >
                <Icon 
                  :icon="getStatusBadge(member.status).icon" 
                  class="w-4 h-4"
                  :class="getStatusBadge(member.status).color"
                />
              </div>
            </div>

            <!-- Info -->
            <div class="flex-1 pb-2">
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                  <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ getMemberName() }}
                  </h1>
                  <p class="text-gray-500 dark:text-gray-400">
                    {{ member.member_code || member.user?.reference_code || 'ไม่มีรหัสสมาชิก' }}
                  </p>
                </div>

                <!-- Role Badge -->
                <div v-if="member.academy_role" class="flex items-center gap-2">
                  <span 
                    :class="[
                      'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium',
                      `bg-${member.academy_role.color}-100 text-${member.academy_role.color}-700 dark:bg-${member.academy_role.color}-900/40 dark:text-${member.academy_role.color}-400`
                    ]"
                  >
                    <Icon :icon="member.academy_role.icon || 'fluent:person-24-regular'" class="w-4 h-4" />
                    {{ member.academy_role.display_name_th }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Stats -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
            <div class="text-center">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ member.enrolled_courses_count || 0 }}</p>
              <p class="text-sm text-gray-500">คอร์สที่ลงทะเบียน</p>
            </div>
            <div class="text-center">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ member.completed_courses_count || 0 }}</p>
              <p class="text-sm text-gray-500">คอร์สที่จบแล้ว</p>
            </div>
            <div class="text-center">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ member.attendance_rate || '-' }}%</p>
              <p class="text-sm text-gray-500">อัตราการเข้าเรียน</p>
            </div>
            <div class="text-center">
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ member.gpa || '-' }}</p>
              <p class="text-sm text-gray-500">เกรดเฉลี่ย</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <nav class="flex overflow-x-auto">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400',
              'flex items-center gap-2 px-4 py-3 border-b-2 font-medium text-sm whitespace-nowrap transition-colors'
            ]"
          >
            <Icon :icon="tab.icon" class="h-5 w-5" />
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
        <!-- Overview Tab -->
        <div v-if="activeTab === 'overview'" class="space-y-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ข้อมูลพื้นฐาน</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <div>
                <label class="text-sm text-gray-500 dark:text-gray-400">อีเมล</label>
                <p class="font-medium text-gray-900 dark:text-white">{{ member.user?.email || '-' }}</p>
              </div>
              <div>
                <label class="text-sm text-gray-500 dark:text-gray-400">รหัสสมาชิก</label>
                <p class="font-medium text-gray-900 dark:text-white">{{ member.member_code || '-' }}</p>
              </div>
              <div>
                <label class="text-sm text-gray-500 dark:text-gray-400">รหัสอ้างอิง</label>
                <p class="font-medium text-gray-900 dark:text-white">{{ member.user?.reference_code || '-' }}</p>
              </div>
            </div>
            
            <div class="space-y-4">
              <div>
                <label class="text-sm text-gray-500 dark:text-gray-400">วันที่สมัครสมาชิก</label>
                <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(member.enrollment_date || member.created_at) }}</p>
              </div>
              <div>
                <label class="text-sm text-gray-500 dark:text-gray-400">สถานะ</label>
                <span :class="[getStatusBadge(member.status).bgColor, getStatusBadge(member.status).color, 'inline-flex items-center gap-1 px-2 py-1 rounded-lg text-sm']">
                  <Icon :icon="getStatusBadge(member.status).icon" class="w-4 h-4" />
                  {{ getStatusBadge(member.status).label }}
                </span>
              </div>
              <div v-if="member.note_comment">
                <label class="text-sm text-gray-500 dark:text-gray-400">หมายเหตุ</label>
                <p class="font-medium text-gray-900 dark:text-white">{{ member.note_comment }}</p>
              </div>
            </div>
          </div>

          <!-- Inviter Info -->
          <div v-if="member.inviter" class="pt-4 border-t border-gray-100 dark:border-gray-700">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">ถูกเชิญโดย</h4>
            <div class="flex items-center gap-3">
              <img 
                :src="member.inviter.profile_photo_url || '/images/default-avatar.png'" 
                :alt="member.inviter.name"
                class="w-10 h-10 rounded-full object-cover"
              />
              <div>
                <p class="font-medium text-gray-900 dark:text-white">{{ member.inviter.name }}</p>
                <p class="text-sm text-gray-500">{{ formatDate(member.invited_at) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Courses Tab -->
        <div v-else-if="activeTab === 'courses'">
          <div v-if="isLoadingCourses" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
          </div>
          
          <div v-else-if="memberCourses.length === 0" class="text-center py-12 text-gray-500">
            <Icon icon="fluent:book-24-regular" class="w-12 h-12 mx-auto mb-3 text-gray-300" />
            <p>ยังไม่มีคอร์สเรียนที่ลงทะเบียน</p>
          </div>

          <div v-else class="space-y-4">
            <div 
              v-for="course in memberCourses"
              :key="course.id"
              class="flex items-center gap-4 p-4 rounded-xl border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            >
              <img 
                :src="course.cover_image || '/images/default-course.png'"
                :alt="course.name"
                class="w-16 h-16 rounded-lg object-cover"
              />
              <div class="flex-1 min-w-0">
                <h4 class="font-medium text-gray-900 dark:text-white truncate">{{ course.name }}</h4>
                <p class="text-sm text-gray-500">ความคืบหน้า: {{ course.progress || 0 }}%</p>
              </div>
              <div class="text-right">
                <span 
                  :class="[
                    'px-2 py-1 rounded-full text-xs font-medium',
                    course.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'
                  ]"
                >
                  {{ course.status === 'completed' ? 'จบแล้ว' : 'กำลังเรียน' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Activity Tab -->
        <div v-else-if="activeTab === 'activity'">
          <div v-if="isLoadingActivity" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
          </div>
          
          <div v-else-if="activityLog.length === 0" class="text-center py-12 text-gray-500">
            <Icon icon="fluent:history-24-regular" class="w-12 h-12 mx-auto mb-3 text-gray-300" />
            <p>ยังไม่มีกิจกรรม</p>
          </div>

          <div v-else class="space-y-4">
            <div 
              v-for="activity in activityLog"
              :key="activity.id"
              class="flex gap-4"
            >
              <div class="flex flex-col items-center">
                <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                  <Icon :icon="activity.icon || 'fluent:checkmark-24-regular'" class="w-5 h-5 text-gray-500" />
                </div>
                <div class="w-px flex-1 bg-gray-200 dark:bg-gray-700 my-2"></div>
              </div>
              <div class="flex-1 pb-6">
                <p class="font-medium text-gray-900 dark:text-white">{{ activity.description }}</p>
                <p class="text-sm text-gray-500">{{ formatDateTime(activity.created_at) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Settings Tab -->
        <div v-else-if="activeTab === 'settings'" class="space-y-6">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">จัดการสมาชิก</h3>
          
          <div class="space-y-4">
            <div class="flex items-center justify-between p-4 rounded-lg border border-gray-200 dark:border-gray-700">
              <div>
                <h4 class="font-medium text-gray-900 dark:text-white">เปลี่ยนบทบาท</h4>
                <p class="text-sm text-gray-500">กำหนดบทบาทใหม่ให้สมาชิก</p>
              </div>
              <button class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                เปลี่ยนบทบาท
              </button>
            </div>

            <div class="flex items-center justify-between p-4 rounded-lg border border-orange-200 dark:border-orange-900/50 bg-orange-50 dark:bg-orange-900/20">
              <div>
                <h4 class="font-medium text-orange-700 dark:text-orange-400">ระงับสมาชิก</h4>
                <p class="text-sm text-orange-600 dark:text-orange-500">ระงับการเข้าใช้งานของสมาชิกชั่วคราว</p>
              </div>
              <button class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                ระงับ
              </button>
            </div>

            <div class="flex items-center justify-between p-4 rounded-lg border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-900/20">
              <div>
                <h4 class="font-medium text-red-700 dark:text-red-400">ลบสมาชิก</h4>
                <p class="text-sm text-red-600 dark:text-red-500">ลบสมาชิกออกจากโรงเรียนอย่างถาวร</p>
              </div>
              <button class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                ลบ
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Not Found -->
    <div v-else class="text-center py-20">
      <Icon icon="fluent:person-24-regular" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">ไม่พบข้อมูลสมาชิก</h2>
      <p class="text-gray-500 mb-4">สมาชิกที่คุณกำลังค้นหาอาจถูกลบหรือไม่มีอยู่ในระบบ</p>
      <button 
        @click="goBack"
        class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
      >
        กลับไปรายการสมาชิก
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Academy Admin - School Management System
 * หน้าระบบบริหารจัดการโรงเรียน
 */
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const isLoading = ref(true)
const activeTab = ref('members')

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Stats
const stats = ref({
  totalMembers: 0,
  totalStudents: 0,
  totalTeachers: 0,
  totalStaff: 0,
  pendingRequests: 0,
})

// Tabs
const tabs = [
  { id: 'members', name: 'สมาชิก', icon: 'fluent:people-24-regular' },
  { id: 'academic', name: 'วิชาการ', icon: 'fluent:hat-graduation-24-regular' },
  { id: 'finance', name: 'การเงิน', icon: 'fluent:money-24-regular' },
  { id: 'staff', name: 'บุคลากร', icon: 'fluent:people-team-24-regular' },
  { id: 'communication', name: 'สื่อสาร', icon: 'fluent:chat-24-regular' },
  { id: 'reports', name: 'รายงาน', icon: 'fluent:data-bar-horizontal-24-regular' },
]

// Quick Actions for Members
const memberQuickActions = computed(() => [
  {
    title: 'จัดการสมาชิก',
    description: 'ดูและจัดการสมาชิกทั้งหมด',
    icon: 'fluent:people-24-filled',
    to: `/academies/${academyName.value}/admin/members`,
    color: 'from-blue-500 to-blue-600',
  },
  {
    title: 'ลิงก์เชิญสมาชิก',
    description: 'สร้างลิงก์เชิญหรือ QR Code',
    icon: 'fluent:link-24-filled',
    to: `/academies/${academyName.value}/admin/invite-links`,
    color: 'from-green-500 to-green-600',
  },
  {
    title: 'แท็กสมาชิก',
    description: 'จัดกลุ่มสมาชิกด้วยแท็ก',
    icon: 'fluent:tag-24-filled',
    to: `/academies/${academyName.value}/admin/member-tags`,
    color: 'from-purple-500 to-purple-600',
  },
  {
    title: 'บทบาทและสิทธิ์',
    description: 'กำหนดบทบาทให้สมาชิก',
    icon: 'fluent:shield-person-24-filled',
    to: `/academies/${academyName.value}/admin/roles`,
    color: 'from-amber-500 to-amber-600',
  },
  {
    title: 'ผู้ปกครอง',
    description: 'จัดการข้อมูลผู้ปกครอง',
    icon: 'fluent:people-community-24-filled',
    to: `/academies/${academyName.value}/admin/guardians`,
    color: 'from-cyan-500 to-cyan-600',
  },
  {
    title: 'ประวัติกิจกรรม',
    description: 'ดูประวัติกิจกรรมของสมาชิก',
    icon: 'fluent:history-24-filled',
    to: `/academies/${academyName.value}/admin/activity-log`,
    color: 'from-rose-500 to-rose-600',
  },
])

// Quick Actions for Academic
const academicQuickActions = computed(() => [
  {
    title: 'หลักสูตร',
    description: 'จัดการหลักสูตรการเรียน',
    icon: 'fluent:book-globe-24-filled',
    to: `/academies/${academyName.value}/admin/curriculums`,
    color: 'from-indigo-500 to-indigo-600',
  },
  {
    title: 'ห้องเรียน',
    description: 'จัดการห้องเรียนและกลุ่ม',
    icon: 'fluent:people-team-24-filled',
    to: `/academies/${academyName.value}/admin/classrooms`,
    color: 'from-teal-500 to-teal-600',
  },
  {
    title: 'ฝ่าย/แผนก',
    description: 'จัดการโครงสร้างองค์กร',
    icon: 'fluent:organization-24-filled',
    to: `/academies/${academyName.value}/admin/departments`,
    color: 'from-orange-500 to-orange-600',
  },
  {
    title: 'ตารางเรียน',
    description: 'จัดตารางเรียนและกิจกรรม',
    icon: 'fluent:calendar-24-filled',
    to: `/academies/${academyName.value}/admin/schedule`,
    color: 'from-pink-500 to-pink-600',
  },
])

// Quick Actions for Students
const studentQuickActions = computed(() => [
  {
    title: 'ทะเบียนนักเรียน',
    description: 'ข้อมูลนักเรียนทั้งหมด',
    icon: 'fluent:hat-graduation-24-filled',
    to: `/academies/${academyName.value}/admin/students`,
    color: 'from-blue-500 to-blue-600',
  },
  {
    title: 'เยี่ยมบ้าน',
    description: 'บันทึกการเยี่ยมบ้าน',
    icon: 'fluent:home-person-24-filled',
    to: `/academies/${academyName.value}/admin/home-visits`,
    color: 'from-green-500 to-green-600',
  },
  {
    title: 'ร้านค้าโรงเรียน',
    description: 'จัดการร้านค้าและสินค้า',
    icon: 'fluent:store-microsoft-24-filled',
    to: `/academies/${academyName.value}/admin/store`,
    color: 'from-emerald-500 to-emerald-600',
  },
])

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
      
      // Fetch stats
      await fetchStats()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchStats = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/members/stats`)
    if (response.success) {
      stats.value.totalMembers = response.stats.total || 0
      stats.value.totalStudents = response.stats.approved || 0
      stats.value.pendingRequests = response.stats.pending || 0
    }
  } catch (err) {
    console.error('Failed to fetch stats:', err)
  }
}
</script>

<template>
  <div class="px-4 sm:px-0">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ระบบบริหารโรงเรียน</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการวิชาการ การเงิน บุคลากร สื่อสาร และรายงาน</p>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex flex-wrap gap-2 sm:gap-4" aria-label="Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
              'flex items-center gap-2 py-3 px-3 border-b-2 font-medium text-sm whitespace-nowrap transition-colors'
            ]"
          >
            <Icon :icon="tab.icon" class="h-5 w-5" />
            <span class="hidden sm:inline">{{ tab.name }}</span>
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="tab-content">
        <!-- Members Tab -->
        <div v-if="activeTab === 'members'" class="space-y-6">
          <!-- Stats Cards -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                  <Icon icon="fluent:people-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalMembers }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">สมาชิกทั้งหมด</p>
                </div>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                  <Icon icon="fluent:hat-graduation-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalStudents }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">นักเรียน</p>
                </div>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                  <Icon icon="fluent:clock-24-filled" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.pendingRequests }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">รออนุมัติ</p>
                </div>
              </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                  <Icon icon="fluent:person-board-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                  <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalTeachers }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">ครู/อาจารย์</p>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Quick Actions -->
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">จัดการสมาชิก</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <NuxtLink
                v-for="action in memberQuickActions"
                :key="action.to"
                :to="action.to"
                class="group bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all hover:border-primary-300 dark:hover:border-primary-600"
              >
                <div class="flex items-start gap-4">
                  <div :class="['p-3 rounded-xl bg-gradient-to-br', action.color]">
                    <Icon :icon="action.icon" class="w-6 h-6 text-white" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                      {{ action.title }}
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ action.description }}</p>
                  </div>
                  <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5 text-gray-400 group-hover:text-primary-500 transition-colors" />
                </div>
              </NuxtLink>
            </div>
          </div>
          
          <!-- Student Actions -->
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลนักเรียน</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <NuxtLink
                v-for="action in studentQuickActions"
                :key="action.to"
                :to="action.to"
                class="group bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all hover:border-primary-300 dark:hover:border-primary-600"
              >
                <div class="flex items-start gap-4">
                  <div :class="['p-3 rounded-xl bg-gradient-to-br', action.color]">
                    <Icon :icon="action.icon" class="w-6 h-6 text-white" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                      {{ action.title }}
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ action.description }}</p>
                  </div>
                  <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5 text-gray-400 group-hover:text-primary-500 transition-colors" />
                </div>
              </NuxtLink>
            </div>
          </div>
        </div>

        <!-- Academic Tab -->
        <div v-else-if="activeTab === 'academic'" class="space-y-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <NuxtLink
              v-for="action in academicQuickActions"
              :key="action.to"
              :to="action.to"
              class="group bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all hover:border-primary-300 dark:hover:border-primary-600"
            >
              <div class="flex items-start gap-4">
                <div :class="['p-3 rounded-xl bg-gradient-to-br', action.color]">
                  <Icon :icon="action.icon" class="w-6 h-6 text-white" />
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                    {{ action.title }}
                  </h4>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ action.description }}</p>
                </div>
                <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5 text-gray-400 group-hover:text-primary-500 transition-colors" />
              </div>
            </NuxtLink>
          </div>
          
          <!-- Academic Tab Content -->
          <div v-if="academyId">
            <SchoolAcademicTab :academy-id="academyId" />
          </div>
        </div>

        <!-- Finance Tab -->
        <div v-else-if="activeTab === 'finance' && academyId">
          <SchoolFinanceTab :academy-id="academyId" />
        </div>

        <!-- Staff Tab -->
        <div v-else-if="activeTab === 'staff' && academyId">
          <SchoolStaffTab :academy-id="academyId" />
        </div>

        <!-- Communication Tab -->
        <div v-else-if="activeTab === 'communication' && academyId">
          <SchoolCommunicationTab :academy-id="academyId" />
        </div>

        <!-- Reports Tab -->
        <div v-else-if="activeTab === 'reports' && academyId">
          <SchoolReportsTab :academy-id="academyId" />
        </div>
      </div>
    </div>
  </div>
</template>

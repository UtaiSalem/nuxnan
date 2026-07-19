<script setup lang="ts">
/**
 * Academy Admin Layout
 * เลย์เอาต์สำหรับหน้า Admin ของโรงเรียน
 */
import { Icon } from '@iconify/vue'
import { useResponsiveSidebar } from '~/composables/useResponsiveSidebar'

const route = useRoute()
const api = useApi()

// Get academy name from route params
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const isLoading = ref(true)
const { 
  isCollapsed: isSidebarCollapsed, 
  isMobileOpen: isMobileSidebarOpen,
  toggleSidebar
} = useResponsiveSidebar()

const isSidebarOpen = computed({
  get: () => !isSidebarCollapsed.value,
  set: (val) => isSidebarCollapsed.value = !val
})

const toggleMobileSidebar = () => {
  isMobileSidebarOpen.value = !isMobileSidebarOpen.value
}

// Academy Role
const academyId = ref<number | null>(null)
const { can, isOwner, isAdmin, isTeacher, fetchMyRole } = useAcademyRole(academyId)

// Revenue badge
const revenuePendingCount = ref(0)
const { revenueDashboard, fetchRevenueDashboard } = useAcademyRevenue(academyId)

const loadRevenueBadge = async () => {
  if (!academyId.value || !can('finance.view')) return
  try {
    await fetchRevenueDashboard()
    if (revenueDashboard.value?.donations?.pending_count) {
      revenuePendingCount.value = revenueDashboard.value.donations.pending_count
    }
  } catch (e) {
    // silently ignore badge load failure
  }
}

watch(academyId, (newId) => {
  if (newId) {
    loadRevenueBadge()
  }
})

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      // Check if user has admin access
      if (!can('academy.view') && !isOwner.value && !isAdmin.value) {
        navigateTo(`/academies/${academyName.value}`)
      }
    }
  } catch (err) {
    console.error('Failed to load academy:', err)
    navigateTo('/academies')
  } finally {
    isLoading.value = false
  }
})

// Menu items
const menuGroups = computed(() => [
  {
    title: 'ภาพรวม',
    items: [
      { 
        icon: 'fluent:grid-24-regular', 
        label: 'แดชบอร์ด', 
        to: `/academies/${academyName.value}/admin`, 
        permission: 'academy.view',
        exact: true
      },
    ]
  },
  {
    title: 'จัดการผู้ใช้',
    items: [
      { 
        icon: 'fluent:people-24-regular', 
        label: 'สมาชิก', 
        to: `/academies/${academyName.value}/admin/members`, 
        permission: 'members.view' 
      },
      { 
        icon: 'fluent:person-clock-24-regular', 
        label: 'คำขอเข้าร่วม', 
        to: `/academies/${academyName.value}/admin/requests`, 
        permission: 'members.manage' 
      },
      { 
        icon: 'fluent:shield-person-24-regular', 
        label: 'บทบาทและสิทธิ์', 
        to: `/academies/${academyName.value}/admin/roles`, 
        permission: 'roles.view' 
      },
      { 
        icon: 'fluent:link-24-regular', 
        label: 'ลิงก์เชิญสมาชิก', 
        to: `/academies/${academyName.value}/admin/invite-links`, 
        permission: 'members.manage' 
      },
      { 
        icon: 'fluent:tag-24-regular', 
        label: 'แท็กสมาชิก', 
        to: `/academies/${academyName.value}/admin/member-tags`, 
        permission: 'members.manage' 
      },
      { 
        icon: 'fluent:people-community-24-regular', 
        label: 'ผู้ปกครอง', 
        to: `/academies/${academyName.value}/admin/guardians`, 
        permission: 'members.view' 
      },
    ]
  },
  {
    title: 'การเรียนการสอน',
    items: [
      { 
        icon: 'fluent:book-24-regular', 
        label: 'คอร์สเรียน', 
        to: `/academies/${academyName.value}/admin/courses`, 
        permission: 'courses.view' 
      },
      { 
        icon: 'fluent:book-globe-24-regular', 
        label: 'หลักสูตร', 
        to: `/academies/${academyName.value}/admin/curriculums`, 
        permission: 'courses.view' 
      },
      { 
        icon: 'fluent:people-team-24-regular', 
        label: 'ห้องเรียน', 
        to: `/academies/${academyName.value}/admin/classrooms`, 
        permission: 'groups.view' 
      },
      { 
        icon: 'fluent:organization-24-regular', 
        label: 'ฝ่าย/แผนก', 
        to: `/academies/${academyName.value}/admin/departments`, 
        permission: 'groups.view' 
      },
      { 
        icon: 'fluent:calendar-24-regular', 
        label: 'ตารางเรียน', 
        to: `/academies/${academyName.value}/admin/schedule`, 
        permission: 'schedule.view' 
      },
    ]
  },
  {
    title: 'ข้อมูลนักเรียน',
    items: [
      { 
        icon: 'fluent:hat-graduation-24-regular', 
        label: 'ทะเบียนนักเรียน', 
        to: `/academies/${academyName.value}/admin/students`, 
        permission: 'students.view' 
      },
      { 
        icon: 'fluent:person-card-24-regular', 
        label: 'บัตรนักเรียน', 
        to: `/academies/${academyName.value}/admin/student-cards`, 
        permission: 'students.view' 
      },
      { 
        icon: 'fluent:clipboard-task-24-regular', 
        label: 'การเข้าเรียน', 
        to: `/academies/${academyName.value}/admin/attendance`, 
        permission: 'attendance.view' 
      },
      { 
        icon: 'fluent:star-24-regular', 
        label: 'ผลการเรียน', 
        to: `/academies/${academyName.value}/admin/grades`, 
        permission: 'grades.view' 
      },
      { 
        icon: 'fluent:home-person-24-regular', 
        label: 'เยี่ยมบ้าน', 
        to: `/academies/${academyName.value}/admin/home-visits`, 
        permission: 'home_visits.view' 
      },
    ]
  },
  {
    title: 'บุคลากร',
    items: [
      { 
        icon: 'fluent:person-board-24-regular', 
        label: 'ข้อมูลบุคลากร', 
        to: `/academies/${academyName.value}/admin/staff`, 
        permission: 'staff.view' 
      },
    ]
  },
  {
    title: 'การสื่อสาร',
    items: [
      { 
        icon: 'fluent:megaphone-24-regular', 
        label: 'ประกาศ', 
        to: `/academies/${academyName.value}/admin/announcements`, 
        permission: 'announcements.manage' 
      },
    ]
  },
  {
    title: 'รายงาน',
    items: [
      { 
        icon: 'fluent:data-bar-horizontal-24-regular', 
        label: 'สถิติและรายงาน', 
        to: `/academies/${academyName.value}/admin/reports`, 
        permission: 'reports.view' 
      },
      { 
        icon: 'fluent:history-24-regular', 
        label: 'ประวัติกิจกรรม', 
        to: `/academies/${academyName.value}/admin/activity-log`, 
        permission: 'reports.view' 
      },
    ]
  },
  {
    title: 'การเงิน',
    items: [
      {
        icon: 'fluent:money-hand-24-regular',
        label: 'รายได้',
        to: `/academies/${academyName.value}/admin/revenue`,
        permission: 'finance.view',
        badge: computed(() => revenuePendingCount.value > 0 ? revenuePendingCount.value : null)
      },
    ]
  },
  {
    title: 'ตั้งค่า',
    items: [
      { 
        icon: 'fluent:building-24-regular', 
        label: 'ระบบบริหารโรงเรียน', 
        to: `/academies/${academyName.value}/admin/school-management`, 
        permission: 'settings.manage' 
      },
      { 
        icon: 'fluent:settings-24-regular', 
        label: 'ตั้งค่าโรงเรียน', 
        to: `/academies/${academyName.value}/admin/settings`, 
        permission: 'settings.manage' 
      },
    ]
  }
])

const filteredMenuGroups = computed(() => {
  return menuGroups.value
    .map(group => ({
      ...group,
      items: group.items.filter(item => 
        !item.permission || can(item.permission) || isOwner.value || isAdmin.value
      )
    }))
    .filter(group => group.items.length > 0)
})

const isActiveRoute = (to: string, exact = false) => {
  if (exact) {
    return route.path === to
  }
  return route.path.startsWith(to)
}

// Close mobile sidebar on route change
watch(
  () => route.fullPath,
  () => {
    isMobileSidebarOpen.value = false
  }
)
</script>

<template>
  <NuxtLayout name="main">
    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent mx-auto mb-4"></div>
        <p class="text-gray-500 dark:text-gray-400">กำลังโหลด...</p>
      </div>
    </div>

    <div v-else class="w-full">
      <!-- Academy Header Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="p-4 flex items-center justify-between">
          <NuxtLink :to="`/academies/${academyName}`" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
            <img 
              :src="academy?.avatar || '/images/default-academy.png'" 
              :alt="academy?.name"
              class="w-12 h-12 rounded-lg object-cover"
            />
            <div>
              <h1 class="font-bold text-lg text-gray-900 dark:text-white">{{ academy?.name }}</h1>
              <p class="text-sm text-gray-500 dark:text-gray-400">Admin Panel</p>
            </div>
          </NuxtLink>
        </div>
      </div>

      <!-- Mobile Menu Toggle -->
      <button 
        @click="toggleMobileSidebar"
        class="lg:hidden w-full mb-4 flex items-center justify-center gap-2 px-4 py-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700"
      >
        <Icon name="fluent:navigation-24-regular" class="w-5 h-5" />
        <span>{{ isMobileSidebarOpen ? 'ซ่อนเมนู' : 'แสดงเมนู' }}</span>
      </button>

      <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar Navigation (Desktop) -->
        <aside 
          :class="[
            'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-300',
            'hidden lg:block lg:flex-shrink-0',
            isSidebarOpen ? 'lg:w-64' : 'lg:w-20'
          ]"
        >
          <!-- Toggle Button -->
          <div class="p-3 border-b border-gray-200 dark:border-gray-700 flex justify-end">
            <button 
              @click="toggleSidebar"
              class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
            >
              <Icon :name="isSidebarOpen ? 'fluent:panel-left-contract-24-regular' : 'fluent:panel-left-expand-24-regular'" class="w-5 h-5" />
            </button>
          </div>

          <!-- Menu -->
          <nav class="p-3 space-y-4 max-h-[70vh] overflow-y-auto">
            <div v-for="group in filteredMenuGroups" :key="group.title">
              <p v-if="isSidebarOpen" class="px-3 mb-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                {{ group.title }}
              </p>
              <div class="space-y-1">
                <NuxtLink
                  v-for="item in group.items"
                  :key="item.to"
                  :to="item.to"
                  :class="[
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors',
                    isActiveRoute(item.to, item.exact)
                      ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400'
                      : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                    !isSidebarOpen && 'justify-center'
                  ]"
                  :title="!isSidebarOpen ? item.label : ''"
                >
                  <div class="relative">
                    <Icon :name="item.icon" class="w-5 h-5 shrink-0" />
                    <span v-if="item.badge && isSidebarOpen" class="absolute -top-1.5 -right-2 inline-flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                      {{ item.badge }}
                    </span>
                  </div>
                  <span v-if="isSidebarOpen" class="text-sm font-medium">{{ item.label }}</span>
                </NuxtLink>
              </div>
            </div>
          </nav>

          <!-- Sidebar Footer -->
          <div class="p-3 border-t border-gray-200 dark:border-gray-700">
            <NuxtLink 
              :to="`/academies/${academyName}`"
              :class="[
                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors',
                !isSidebarOpen && 'justify-center'
              ]"
            >
              <Icon name="fluent:arrow-left-24-regular" class="w-5 h-5 shrink-0" />
              <span v-if="isSidebarOpen" class="text-sm font-medium">กลับหน้าโรงเรียน</span>
            </NuxtLink>
          </div>
        </aside>

        <!-- Mobile Sidebar -->
        <aside 
          :class="[
            'lg:hidden bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-300 mb-4',
            isMobileSidebarOpen ? 'block' : 'hidden'
          ]"
        >
          <nav class="p-3 space-y-4 max-h-[60vh] overflow-y-auto">
            <div v-for="group in filteredMenuGroups" :key="group.title">
              <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ group.title }}</p>
              <div class="space-y-1">
                <NuxtLink
                  v-for="item in group.items"
                  :key="item.to"
                  :to="item.to"
                  @click="isMobileSidebarOpen = false"
                  :class="[
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors',
                    isActiveRoute(item.to, item.exact)
                      ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400'
                      : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                  ]"
                >
                  <div class="relative">
                    <Icon :name="item.icon" class="w-5 h-5" />
                    <span v-if="item.badge" class="absolute -top-1.5 -right-2 inline-flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                      {{ item.badge }}
                    </span>
                  </div>
                  <span class="text-sm font-medium">{{ item.label }}</span>
                </NuxtLink>
              </div>
            </div>
          </nav>

          <div class="p-3 border-t border-gray-200 dark:border-gray-700">
            <NuxtLink 
              :to="`/academies/${academyName}`"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            >
              <Icon name="fluent:arrow-left-24-regular" class="w-5 h-5" />
              <span class="text-sm font-medium">กลับหน้าโรงเรียน</span>
            </NuxtLink>
          </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 min-w-0">
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 lg:p-6">
            <slot />
          </div>
        </main>
      </div>
    </div>
  </NuxtLayout>
</template>


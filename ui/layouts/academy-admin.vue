<script setup lang="ts">
/**
 * Academy Admin Layout
 * เลย์เอาต์สำหรับหน้า Admin ของโรงเรียน
 */
import { Icon } from '@iconify/vue'

interface Props {
  academyName: string
}

const props = defineProps<Props>()

const route = useRoute()
const api = useApi()

// State
const academy = ref<any>(null)
const isLoading = ref(true)
const isSidebarOpen = ref(true)
const isMobileSidebarOpen = ref(false)

// Academy Role
const academyId = ref<number | null>(null)
const { can, isOwner, isAdmin, isTeacher, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${encodeURIComponent(props.academyName)}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      // Check if user has admin access
      if (!can('academy.view') && !isOwner.value && !isAdmin.value) {
        navigateTo(`/academies/${props.academyName}`)
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
        to: `/academies/${props.academyName}/admin`, 
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
        to: `/academies/${props.academyName}/admin/members`, 
        permission: 'members.view' 
      },
      { 
        icon: 'fluent:shield-person-24-regular', 
        label: 'บทบาทและสิทธิ์', 
        to: `/academies/${props.academyName}/admin/roles`, 
        permission: 'roles.view' 
      },
    ]
  },
  {
    title: 'การเรียนการสอน',
    items: [
      { 
        icon: 'fluent:book-24-regular', 
        label: 'คอร์สเรียน', 
        to: `/academies/${props.academyName}/admin/courses`, 
        permission: 'courses.view' 
      },
      { 
        icon: 'fluent:people-team-24-regular', 
        label: 'กลุ่มเรียน', 
        to: `/academies/${props.academyName}/admin/groups`, 
        permission: 'groups.view' 
      },
      { 
        icon: 'fluent:calendar-24-regular', 
        label: 'ตารางเรียน', 
        to: `/academies/${props.academyName}/admin/schedule`, 
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
        to: `/academies/${props.academyName}/admin/students`, 
        permission: 'students.view' 
      },
      { 
        icon: 'fluent:clipboard-task-24-regular', 
        label: 'การเข้าเรียน', 
        to: `/academies/${props.academyName}/admin/attendance`, 
        permission: 'attendance.view' 
      },
      { 
        icon: 'fluent:star-24-regular', 
        label: 'ผลการเรียน', 
        to: `/academies/${props.academyName}/admin/grades`, 
        permission: 'grades.view' 
      },
    ]
  },
  {
    title: 'การสื่อสาร',
    items: [
      { 
        icon: 'fluent:megaphone-24-regular', 
        label: 'ประกาศ', 
        to: `/academies/${props.academyName}/admin/announcements`, 
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
        to: `/academies/${props.academyName}/admin/reports`, 
        permission: 'reports.view' 
      },
    ]
  },
  {
    title: 'ตั้งค่า',
    items: [
      { 
        icon: 'fluent:settings-24-regular', 
        label: 'ตั้งค่าโรงเรียน', 
        to: `/academies/${props.academyName}/admin/settings`, 
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

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

const toggleMobileSidebar = () => {
  isMobileSidebarOpen.value = !isMobileSidebarOpen.value
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center min-h-screen">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent mx-auto mb-4"></div>
        <p class="text-gray-500 dark:text-gray-400">กำลังโหลด...</p>
      </div>
    </div>

    <div v-else class="flex">
      <!-- Desktop Sidebar -->
      <aside 
        :class="[
          'hidden lg:flex flex-col h-screen sticky top-0 border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 transition-all duration-300',
          isSidebarOpen ? 'w-64' : 'w-20'
        ]"
      >
        <!-- Sidebar Header -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
          <NuxtLink :to="`/academies/${academyName}`" class="flex items-center gap-3 flex-1 min-w-0">
            <img 
              :src="academy?.avatar || '/images/default-academy.png'" 
              :alt="academy?.name"
              class="w-10 h-10 rounded-lg object-cover shrink-0"
            />
            <div v-if="isSidebarOpen" class="min-w-0">
              <p class="font-semibold text-gray-900 dark:text-white truncate text-sm">{{ academy?.name }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">แอดมิน</p>
            </div>
          </NuxtLink>
          <button 
            @click="toggleSidebar"
            class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <Icon :name="isSidebarOpen ? 'fluent:panel-left-contract-24-regular' : 'fluent:panel-left-expand-24-regular'" class="w-5 h-5" />
          </button>
        </div>

        <!-- Menu -->
        <nav class="flex-1 overflow-y-auto p-3 space-y-6">
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
                <Icon :name="item.icon" class="w-5 h-5 shrink-0" />
                <span v-if="isSidebarOpen" class="text-sm font-medium">{{ item.label }}</span>
              </NuxtLink>
            </div>
          </div>
        </nav>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
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

      <!-- Mobile Sidebar Overlay -->
      <div 
        v-if="isMobileSidebarOpen"
        @click="toggleMobileSidebar"
        class="fixed inset-0 bg-black/50 z-40 lg:hidden"
      />

      <!-- Mobile Sidebar -->
      <aside 
        :class="[
          'fixed left-0 top-0 h-full w-72 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 z-50 lg:hidden transition-transform duration-300',
          isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'
        ]"
      >
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <img 
              :src="academy?.avatar || '/images/default-academy.png'" 
              :alt="academy?.name"
              class="w-10 h-10 rounded-lg object-cover"
            />
            <div>
              <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ academy?.name }}</p>
              <p class="text-xs text-gray-500">แอดมิน</p>
            </div>
          </div>
          <button @click="toggleMobileSidebar" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <Icon name="fluent:dismiss-24-regular" class="w-5 h-5" />
          </button>
        </div>

        <nav class="overflow-y-auto p-3 space-y-6" style="height: calc(100% - 140px)">
          <div v-for="group in filteredMenuGroups" :key="group.title">
            <p class="px-3 mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ group.title }}</p>
            <div class="space-y-1">
              <NuxtLink
                v-for="item in group.items"
                :key="item.to"
                :to="item.to"
                @click="toggleMobileSidebar"
                :class="[
                  'flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors',
                  isActiveRoute(item.to, item.exact)
                    ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400'
                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                ]"
              >
                <Icon :name="item.icon" class="w-5 h-5" />
                <span class="text-sm font-medium">{{ item.label }}</span>
              </NuxtLink>
            </div>
          </div>
        </nav>

        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 dark:border-gray-700">
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
      <div class="flex-1 min-w-0">
        <!-- Mobile Header -->
        <header class="lg:hidden sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
          <div class="flex items-center gap-3">
            <button @click="toggleMobileSidebar" class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon name="fluent:navigation-24-regular" class="w-5 h-5" />
            </button>
            <span class="font-medium text-gray-900 dark:text-white">แอดมิน</span>
          </div>
        </header>

        <!-- Page Content -->
        <main class="p-4 lg:p-6">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>

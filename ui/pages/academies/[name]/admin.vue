<script setup lang="ts">
/**
 * Academy Admin Layout
 * หน้า Layout หลักสำหรับ Admin Panel ของโรงเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main',
  ssr: false,
  middleware: ['auth']
})

const route = useRoute()
const api = useApi()

const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const isLoading = ref(true)
const isSidebarOpen = ref(false) // Default to closed on mobile

// Academy Role
const academyId = ref<number | null>(null)
const {
  myRole,
  roleDisplayName,
  roleColor,
  roleIcon,
  can,
  isOwner,
  isAdmin,
  isTeacher,
  fetchMyRole
} = useAcademyRole(academyId)

// Provide to child pages so they don't need to resolve academy themselves
provide('academyId', academyId)
provide('academyName', academyName)
provide('academy', academy)

// Menu items
const menuItems = computed(() => [
  {
    group: 'ภาพรวม',
    items: [
      {
        name: 'แดชบอร์ด',
        icon: 'fluent:grid-24-regular',
        to: `/academies/${academyName.value}/admin`,
        show: true,
        exact: true,
      },
    ]
  },
  {
    group: 'จัดการผู้ใช้',
    items: [
      {
        name: 'สมาชิก',
        icon: 'fluent:people-24-regular',
        to: `/academies/${academyName.value}/admin/members`,
        show: can('members.view') || can('members.manage'),
      },
      {
        name: 'คำขอเข้าร่วม',
        icon: 'fluent:person-clock-24-regular',
        to: `/academies/${academyName.value}/admin/requests`,
        show: can('members.manage'),
      },
      {
        name: 'บทบาทและสิทธิ์',
        icon: 'fluent:shield-person-24-regular',
        to: `/academies/${academyName.value}/admin/roles`,
        show: can('members.manage') || can('roles.view'),
      },
      {
        name: 'ลิงก์เชิญสมาชิก',
        icon: 'fluent:link-24-regular',
        to: `/academies/${academyName.value}/admin/invite-links`,
        show: can('members.manage'),
      },
      {
        name: 'แท็กสมาชิก',
        icon: 'fluent:tag-24-regular',
        to: `/academies/${academyName.value}/admin/member-tags`,
        show: can('members.manage'),
      },
      {
        name: 'ผู้ปกครอง',
        icon: 'fluent:people-community-24-regular',
        to: `/academies/${academyName.value}/admin/guardians`,
        show: can('members.view'),
      },
    ]
  },
  {
    group: 'การเรียนการสอน',
    items: [
      {
        name: 'คอร์สเรียน',
        icon: 'fluent:book-24-regular',
        to: `/academies/${academyName.value}/admin/courses`,
        show: can('courses.view') || can('courses.manage'),
      },
      {
        name: 'หลักสูตร',
        icon: 'fluent:book-globe-24-regular',
        to: `/academies/${academyName.value}/admin/curriculums`,
        show: can('courses.view'),
      },
      {
        name: 'ห้องเรียน',
        icon: 'fluent:people-team-24-regular',
        to: `/academies/${academyName.value}/admin/classrooms`,
        show: can('groups.view') || can('academy.view'),
      },
      {
        name: 'ฝ่าย/แผนก',
        icon: 'fluent:organization-24-regular',
        to: `/academies/${academyName.value}/admin/departments`,
        show: can('groups.view') || can('academy.view'),
      },
      {
        name: 'ตารางเรียน',
        icon: 'fluent:calendar-24-regular',
        to: `/academies/${academyName.value}/admin/schedule`,
        show: can('schedule.view') || can('academy.view'),
      },
    ]
  },
  {
    group: 'ข้อมูลนักเรียน',
    items: [
      {
        name: 'ทะเบียนนักเรียน',
        icon: 'fluent:hat-graduation-24-regular',
        to: `/academies/${academyName.value}/admin/students`,
        show: can('students.view') || can('students.manage'),
      },
      {
        name: 'บัตรนักเรียน',
        icon: 'fluent:person-card-24-regular',
        to: `/academies/${academyName.value}/admin/student-cards`,
        show: can('students.view'),
      },
      {
        name: 'การเข้าเรียน',
        icon: 'fluent:clipboard-task-24-regular',
        to: `/academies/${academyName.value}/admin/school-attendance`,
        show: can('attendance.view') || can('academy.view'),
      },
      {
        name: 'ผลการเรียน',
        icon: 'fluent:star-24-regular',
        to: `/academies/${academyName.value}/admin/gradebook`,
        show: can('grades.view') || can('academy.view'),
      },
      {
        name: 'เยี่ยมบ้าน',
        icon: 'fluent:home-person-24-regular',
        to: `/academies/${academyName.value}/admin/home-visits`,
        show: can('home_visits.view') || can('home_visits.manage'),
      },
      {
        name: 'นักเรียนกลุ่มเสี่ยง',
        icon: 'fluent:warning-24-regular',
        to: `/academies/${academyName.value}/admin/at-risk`,
        show: can('students.view'),
      },
    ]
  },
  {
    group: 'บุคลากร',
    items: [
      {
        name: 'ข้อมูลบุคลากร',
        icon: 'fluent:person-board-24-regular',
        to: `/academies/${academyName.value}/admin/staff`,
        show: can('staff.view') || can('academy.view'),
      },
    ]
  },
  {
    group: 'กิจกรรม & การสื่อสาร',
    items: [
      {
        name: 'ประกาศ',
        icon: 'fluent:megaphone-24-regular',
        to: `/academies/${academyName.value}/admin/announcements`,
        show: can('announcements.view') || can('announcements.manage'),
      },
      {
        name: 'กิจกรรม',
        icon: 'fluent:calendar-star-24-regular',
        to: `/academies/${academyName.value}/admin/events`,
        show: can('announcements.manage') || can('academy.view'),
      },
    ]
  },
  {
    group: 'ร้านค้า',
    items: [
      {
        name: 'ร้านค้าโรงเรียน',
        icon: 'fluent:store-24-regular',
        to: `/academies/${academyName.value}/admin/store`,
        show: can('settings.manage') || can('academy.view'),
      },
    ]
  },
  {
    group: 'รายงาน & สถิติ',
    items: [
      {
        name: 'สถิติและรายงาน',
        icon: 'fluent:data-bar-horizontal-24-regular',
        to: `/academies/${academyName.value}/admin/reports`,
        show: can('reports.view'),
      },
      {
        name: 'ประวัติกิจกรรม',
        icon: 'fluent:history-24-regular',
        to: `/academies/${academyName.value}/admin/activity-log`,
        show: can('reports.view'),
      },
    ]
  },
  {
    group: 'ตั้งค่า',
    items: [
      {
        name: 'ระบบบริหารโรงเรียน',
        icon: 'fluent:building-24-regular',
        to: `/academies/${academyName.value}/admin/school-management`,
        show: can('settings.manage') || can('academy.settings.edit'),
      },
      {
        name: 'ตั้งค่าโรงเรียน',
        icon: 'fluent:settings-24-regular',
        to: `/academies/${academyName.value}/admin/settings`,
        show: can('academy.settings.view') || can('academy.settings.edit'),
      },
    ]
  },
])

// Fetch data
onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      // Check if user has admin access
      if (!isAdmin.value && !isTeacher.value) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
    }
  } catch (err) {
    console.error('Failed to load academy:', err)
    navigateTo('/academies')
  } finally {
    isLoading.value = false
  }
})

const isActiveRoute = (path: string, exact = false) => {
  if (exact) return route.path === path
  return route.path === path || route.path.startsWith(path + '/')
}

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}
</script>

<template>
  <div class="w-full">
    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else>
      <!-- Academy Header Card -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="p-4 flex items-center justify-between">
          <NuxtLink 
            :to="`/academies/${academyName}`"
            class="flex items-center gap-3 hover:opacity-80 transition-opacity"
          >
            <img 
              :src="academy?.logo || `https://ui-avatars.com/api/?name=${encodeURIComponent(academy?.name || 'Academy')}&background=6366f1&color=fff&size=96`" 
              :alt="academy?.name"
              class="w-12 h-12 rounded-lg object-cover flex-shrink-0"
            />
            <div>
              <h1 class="font-bold text-lg text-gray-900 dark:text-white">
                {{ academy?.name }}
              </h1>
              <p class="text-sm text-gray-500 dark:text-gray-400">Admin Panel</p>
            </div>
          </NuxtLink>
          
          <!-- Role Badge -->
          <div :class="['flex items-center gap-2 px-3 py-2 rounded-lg', roleColor]">
            <Icon :name="roleIcon" class="w-5 h-5" />
            <span class="font-medium text-sm hidden sm:inline">{{ roleDisplayName }}</span>
          </div>
        </div>
      </div>

      <!-- Mobile Menu Toggle -->
      <button 
        @click="toggleSidebar"
        class="lg:hidden w-full mb-4 flex items-center justify-center gap-2 px-4 py-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700"
      >
        <Icon name="fluent:navigation-24-regular" class="w-5 h-5" />
        <span>{{ isSidebarOpen ? 'ซ่อนเมนู' : 'แสดงเมนู' }}</span>
      </button>

      <div class="flex flex-col lg:flex-row gap-6">
      <!-- Sidebar Navigation -->
      <aside 
        :class="[
          'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transition-all duration-300',
          isSidebarOpen ? 'block' : 'hidden lg:block',
          'lg:w-64 lg:flex-shrink-0'
        ]"
      >
        <!-- Navigation -->
        <nav class="p-4 space-y-4 max-h-[70vh] lg:max-h-none overflow-y-auto">
          <div v-for="group in menuItems" :key="group.group" v-show="group.items.some(item => item.show)">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 px-3">
              {{ group.group }}
            </h3>
            <ul class="space-y-1">
              <li v-for="item in group.items" :key="item.name" v-show="item.show">
                <NuxtLink
                  :to="item.to"
                  :class="[
                    'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors',
                    isActiveRoute(item.to, item.exact)
                      ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300'
                      : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                  ]"
                >
                  <Icon :name="item.icon" class="w-5 h-5 flex-shrink-0" />
                  <span class="text-sm">{{ item.name }}</span>
                  <span 
                    v-if="item.badge" 
                    class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"
                  >
                    {{ item.badge }}
                  </span>
                </NuxtLink>
              </li>
            </ul>
          </div>
        </nav>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
          <NuxtLink
            :to="`/academies/${academyName}`"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <Icon name="fluent:arrow-left-24-regular" class="w-5 h-5" />
            <span class="text-sm">กลับหน้าโรงเรียน</span>
          </NuxtLink>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="flex-1 min-w-0">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 sm:p-6">
          <NuxtPage />
        </div>
      </main>
    </div>
  </div>
</div>
</template>

<style scoped>
/* Hide scrollbar for sidebar */
nav::-webkit-scrollbar {
  width: 4px;
}
nav::-webkit-scrollbar-track {
  background: transparent;
}
nav::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 2px;
}
.dark nav::-webkit-scrollbar-thumb {
  background: #475569;
}
</style>

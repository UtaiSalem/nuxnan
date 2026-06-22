<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import { useResponsiveSidebar } from '~/composables/useResponsiveSidebar'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const { 
  isCollapsed: isSidebarCollapsed, 
  isMobileOpen: isMobileSidebarOpen,
  toggleSidebar
} = useResponsiveSidebar()

// User dropdown
const isUserDropdownOpen = ref(false)

// Current user
const currentUser = computed(() => authStore.user)

// Check if user is super admin
const isSuperAdmin = computed(() => authStore.user?.is_super_admin === true)

// Navigation items
const allNavItems = [
  {
    name: 'แดชบอร์ด',
    icon: 'fluent:grid-24-regular',
    href: '/nuxnan-admin',
    exact: true
  },
  {
    name: 'จัดการผู้ใช้',
    icon: 'fluent:people-24-regular',
    href: '/nuxnan-admin/users'
  },
  {
    name: 'จัดการบทบาท',
    icon: 'fluent:shield-24-regular',
    href: '/nuxnan-admin/roles',
    superAdminOnly: true
  },
  {
    name: 'Permission Matrix',
    icon: 'fluent:key-multiple-24-regular',
    href: '/nuxnan-admin/permissions',
    superAdminOnly: true
  },
  {
    name: 'จัดการคอร์ส',
    icon: 'fluent:hat-graduation-24-regular',
    href: '/nuxnan-admin/courses'
  },
  {
    name: 'จัดการอะคาเดมี',
    icon: 'fluent:building-24-regular',
    href: '/nuxnan-admin/academies'
  },
  {
    name: 'จัดการเนื้อหา',
    icon: 'fluent:document-24-regular',
    href: '/nuxnan-admin/content'
  },
  {
    name: 'จัดการ Points',
    icon: 'fluent:coin-stack-24-regular',
    href: '/nuxnan-admin/points'
  },
  {
    name: 'จัดการ Wallet',
    icon: 'fluent:wallet-24-regular',
    href: '/nuxnan-admin/wallet'
  },
  {
    name: 'จัดการการสนับสนุน',
    icon: 'fluent:gift-24-regular',
    href: '/nuxnan-admin/supports'
  },
  {
    name: 'จัดการคูปอง',
    icon: 'fluent:ticket-diagonal-24-regular',
    href: '/nuxnan-admin/coupons'
  },
  {
    name: 'รายงาน',
    icon: 'fluent:data-pie-24-regular',
    href: '/nuxnan-admin/reports'
  },
  {
    name: 'ตั้งค่าระบบ',
    icon: 'fluent:settings-24-regular',
    href: '/nuxnan-admin/settings',
    superAdminOnly: true
  }
]

// Filter navigation items based on user role
const navItems = computed(() => {
  return allNavItems.filter(item => {
    // If item requires super admin and user is not super admin, hide it
    if (item.superAdminOnly && !isSuperAdmin.value) {
      return false
    }
    return true
  })
})

// Check if route is active
const isActiveRoute = (item: any) => {
  if (item.exact) {
    return route.path === item.href
  }
  return route.path.startsWith(item.href)
}

// Handle logout
const handleLogout = async () => {
  await authStore.logout()
  navigateTo('/nuxnan-admin/login')
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
  <div class="min-h-screen bg-hopeui-container dark:bg-[#0e1118]">
    <!-- Mobile Sidebar Overlay -->
    <Transition name="fade">
      <div
        v-if="isMobileSidebarOpen"
        class="fixed inset-0 bg-black/50 z-40 lg:hidden"
        @click="isMobileSidebarOpen = false"
      />
    </Transition>

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed top-0 left-0 z-50 h-full bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 transition-all duration-300 shadow-hopeui',
        isSidebarCollapsed ? 'lg:w-20' : 'w-64',
        isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
    >
      <!-- Logo Section -->
      <div class="flex items-center justify-between h-16 px-4 border-b border-slate-100 dark:border-slate-700">
        <NuxtLink to="/nuxnan-admin" class="flex items-center gap-3">
          <div class="w-10 h-10 bg-hopeui-primary-500 rounded-xl flex items-center justify-center shadow-hopeui-sm">
            <img src="/images/plearnd-logo.png" alt="Logo" class="w-6 h-6" />
          </div>
          <span v-if="!isSidebarCollapsed" class="text-slate-800 dark:text-white font-bold text-lg">
            Nuxnan Admin
          </span>
        </NuxtLink>
        <button
          @click="isMobileSidebarOpen = false"
          class="lg:hidden text-slate-400 hover:text-slate-600"
        >
          <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-4 space-y-1 overflow-y-auto max-h-[calc(100vh-8rem)]">
        <NuxtLink
          v-for="item in navItems"
          :key="item.href"
          :to="item.href"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 text-sm font-medium',
            isActiveRoute(item)
              ? 'bg-hopeui-primary-100 text-hopeui-primary-600 dark:bg-hopeui-primary-900 dark:text-hopeui-primary-300'
              : 'text-slate-500 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-800 dark:hover:text-white'
          ]"
        >
          <Icon :icon="item.icon" class="w-5 h-5 flex-shrink-0" />
          <span v-if="!isSidebarCollapsed">{{ item.name }}</span>
        </NuxtLink>
      </nav>

      <!-- Bottom Section -->
      <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-100 dark:border-slate-700">
        <NuxtLink
          to="/"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-500 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-800 dark:hover:text-white transition-all"
        >
          <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
          <span v-if="!isSidebarCollapsed">กลับหน้าหลัก</span>
        </NuxtLink>
      </div>
    </aside>

    <!-- Main Content -->
    <div
      :class="[
        'transition-all duration-300',
        isSidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'
      ]"
    >
      <!-- Top Header -->
      <header class="sticky top-0 z-30 bg-white dark:bg-slate-800 shadow-hopeui-lg dark:shadow-none dark:border-b dark:border-slate-700">
        <div class="flex items-center justify-between h-16 px-4 lg:px-6">
          <!-- Left side -->
          <div class="flex items-center gap-4">
            <button
              @click="toggleSidebar"
              class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
            >
              <Icon icon="fluent:navigation-24-regular" class="w-6 h-6" />
            </button>
            <h1 class="text-lg font-semibold text-slate-800 dark:text-white hidden sm:block">
              ระบบจัดการ Nuxnan
            </h1>
          </div>

          <!-- Right side -->
          <div class="flex items-center gap-3">
            <!-- Notifications -->
            <button class="relative p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
              <Icon icon="fluent:alert-24-regular" class="w-6 h-6" />
              <span class="absolute top-1 right-1 w-2 h-2 bg-hopeui-danger rounded-full"></span>
            </button>

            <!-- User Dropdown -->
            <div class="relative">
              <button
                @click="isUserDropdownOpen = !isUserDropdownOpen"
                class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
              >
                <img
                  :src="currentUser?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(currentUser?.name || 'Admin')}&background=3a57e8&color=fff`"
                  :alt="currentUser?.name"
                  class="w-8 h-8 rounded-full object-cover"
                />
                <span class="hidden sm:block text-sm font-medium text-slate-700 dark:text-slate-200">
                  {{ currentUser?.name || 'Admin' }}
                </span>
                <Icon icon="fluent:chevron-down-24-regular" class="w-4 h-4 text-slate-500" />
              </button>

              <!-- Dropdown Menu -->
              <Transition name="dropdown">
                <div
                  v-if="isUserDropdownOpen"
                  class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-hopeui-dropdown border border-slate-200 dark:border-slate-700 py-1 z-50"
                >
                  <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-700">
                    <p class="text-sm font-medium text-slate-800 dark:text-white">
                      {{ currentUser?.name }}
                    </p>
                    <p class="text-xs text-slate-500">{{ currentUser?.email }}</p>
                  </div>
                  <NuxtLink
                    to="/nuxnan-admin/profile"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700"
                    @click="isUserDropdownOpen = false"
                  >
                    <Icon icon="fluent:person-24-regular" class="w-4 h-4" />
                    โปรไฟล์
                  </NuxtLink>
                  <NuxtLink
                    to="/nuxnan-admin/settings"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700"
                    @click="isUserDropdownOpen = false"
                  >
                    <Icon icon="fluent:settings-24-regular" class="w-4 h-4" />
                    ตั้งค่า
                  </NuxtLink>
                  <hr class="my-1 border-slate-100 dark:border-slate-700" />
                  <button
                    @click="handleLogout"
                    class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                  >
                    <Icon icon="fluent:sign-out-24-regular" class="w-4 h-4" />
                    ออกจากระบบ
                  </button>
                </div>
              </Transition>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="p-4 lg:p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>

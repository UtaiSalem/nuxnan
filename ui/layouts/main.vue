<script setup>
import { ref, computed, onBeforeUnmount, onMounted, watch, provide } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import { useUIStore } from '~/stores/ui'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const uiStore = useUIStore()

// Drawer states
const isLeftDrawerOpen = ref(false)
const isRightDrawerOpen = ref(false)
const isMobileSidebarOpen = ref(false)
const isSettingsOpen = ref(false)

// Theme state
const isDarkMode = ref(false)

// User data
const authUser = computed(() => {
  const user = authStore.user
  if (!user) {
    return {
      name: 'Guest',
      username: 'guest',
      email: '',
      avatar: '/images/default-avatar.png',
      profile_photo_url: '/images/default-avatar.png',
      pp: 0,
      wallet: 0,
      level: 1,
      posts: 0,
      friends: 0,
      visits: 0,
    }
  }

  return {
    name: user.username || user.name || 'User',
    username: user.username || user.name,
    email: user.email || '',
    avatar: user.avatar || user.profile_photo_url || '/images/default-avatar.png',
    profile_photo_url: user.avatar || user.profile_photo_url || '/images/default-avatar.png',
    pp: authStore.points, // ใช้ authStore.points (computed reactive)
    wallet: Number(user.wallet) || 0,
    level: user.level || 24,
    posts: user.posts || 930,
    friends: user.friends || 82,
    visits: user.visits || '5.7K',
  }
})

// Navigation
const navigation = [
  { name: 'กระดานข่าว', href: '/newsfeed', icon: 'fluent:feed-24-regular' },
  { name: 'รายวิชา', href: '/courses', icon: 'fluent-mdl2:publish-course' },
  { name: 'สะสมแต้ม', href: '/donates', icon: 'mdi:hand-coin-outline' },
  { name: 'ดูสินค้า', href: '/supports', icon: 'eos-icons:product-subscriptions-outlined' },
]

// Toggle functions
const toggleLeftDrawer = () => {
  isLeftDrawerOpen.value = !isLeftDrawerOpen.value
}

const toggleRightDrawer = () => {
  isRightDrawerOpen.value = !isRightDrawerOpen.value
}

const toggleMobileSidebar = () => {
  isMobileSidebarOpen.value = !isMobileSidebarOpen.value
}

const toggleSettings = () => {
  isSettingsOpen.value = !isSettingsOpen.value
}

const closeSettings = () => {
  isSettingsOpen.value = false
}

const toggleTheme = () => {
  isDarkMode.value = !isDarkMode.value
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
    document.documentElement.classList.remove('light')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('dark')
    document.documentElement.classList.add('light')
    localStorage.setItem('theme', 'light')
  }
}

// Close mobile sidebar on route change
watch(
  () => route.fullPath,
  () => {
    isMobileSidebarOpen.value = false
  }
)

// Logout
const logout = async () => {
  await authStore.logout()
}

// Fetch user on mount
onMounted(async () => {
  // Load theme from localStorage
  const savedTheme = localStorage.getItem('theme')
  if (savedTheme === 'dark') {
    isDarkMode.value = true
    document.documentElement.classList.add('dark')
    document.documentElement.classList.remove('light')
  } else {
    isDarkMode.value = false
    document.documentElement.classList.remove('dark')
    document.documentElement.classList.add('light')
  }

  if (authStore.isAuthenticated && !authStore.user) {
    try {
      await authStore.fetchUser()
    } catch (error) {
      console.error('Failed to fetch user:', error)
    }
  }
})

// Provide theme to child components
provide('isDarkMode', isDarkMode)
provide('toggleTheme', toggleTheme)


// For testing point changes
const handleTestChangePoints = () => {
  authStore.addPoints(100)
}

</script>

<template>
  <div
    class="min-h-screen transition-colors duration-300"
    :class="isDarkMode ? 'bg-vikinger-dark dark' : 'bg-gray-50 light'"
  >
    <!-- ========================================
             HEADER (Fixed Top)
    ======================================== -->
    <header
      class="fixed top-0 left-0 right-0 h-16 z-50 shadow-lg transition-colors duration-300"
      :class="
        isDarkMode
          ? 'bg-vikinger-dark-100 border-b border-vikinger-dark-50/30'
          : 'bg-white border-b border-gray-200'
      "
    >
      <div class="h-full px-4 flex items-center justify-between gap-4">
        <!-- Left: Logo + App Name -->
        <div class="flex items-center gap-3">
          <!-- Left Drawer Toggle (Desktop) -->
          <button
            @click="toggleLeftDrawer"
            class="hidden lg:flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-vikinger-purple to-vikinger-cyan shadow-vikinger hover:shadow-vikinger-lg transition-all duration-300 hover:scale-110 group relative overflow-hidden"
          >
            <div
              class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
            ></div>
            <div class="relative flex flex-col gap-1 w-5">
              <span
                class="h-0.5 bg-white rounded-full transition-all duration-300"
                :class="isLeftDrawerOpen ? 'rotate-45 translate-y-1.5' : ''"
              ></span>
              <span
                class="h-0.5 bg-white rounded-full transition-all duration-300"
                :class="isLeftDrawerOpen ? 'opacity-0 scale-0' : ''"
              ></span>
              <span
                class="h-0.5 bg-white rounded-full transition-all duration-300"
                :class="isLeftDrawerOpen ? '-rotate-45 -translate-y-1.5' : ''"
              ></span>
            </div>
          </button>

          <!-- Mobile Menu Toggle -->
          <button
            @click="toggleMobileSidebar"
            class="lg:hidden flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-vikinger-purple to-vikinger-cyan shadow-md hover:shadow-lg transition-all duration-300 hover:scale-110 group relative overflow-hidden"
          >
            <div
              class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
            ></div>
            <div class="relative flex flex-col gap-1 w-5">
              <span
                class="h-0.5 bg-white rounded-full transition-all duration-300"
                :class="isMobileSidebarOpen ? 'rotate-45 translate-y-1.5' : ''"
              ></span>
              <span
                class="h-0.5 bg-white rounded-full transition-all duration-300"
                :class="isMobileSidebarOpen ? 'opacity-0 scale-0' : ''"
              ></span>
              <span
                class="h-0.5 bg-white rounded-full transition-all duration-300"
                :class="isMobileSidebarOpen ? '-rotate-45 -translate-y-1.5' : ''"
              ></span>
            </div>
          </button>

          <!-- Logo + App Name -->
          <NuxtLink to="/" class="flex items-center gap-3">
            <img src="/storage/images/plearnd-logo.png" alt="Plearnd Logo" class="w-10 h-10" />
            <span
              class="hidden md:inline-block px-3 py-1 text-lg font-semibold text-white rounded-lg bg-gradient-vikinger shadow-lg"
              >Plearnd</span
            >
          </NuxtLink>
        </div>

        <!-- Center: Navigation (Desktop) -->
        <div class="hidden md:flex items-center gap-2">
          <NuxtLink
            v-for="item in navigation"
            :key="item.href"
            :to="item.href"
            class="flex items-center gap-2 px-4 py-2 rounded-lg transition-all duration-300"
            :class="
              route.path.startsWith(item.href)
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
          >
            <Icon :icon="item.icon" class="w-5 h-5" />
            <span class="hidden xl:inline">{{ item.name }}</span>
          </NuxtLink>
        </div>

        

        <!-- Right: Points + Wallet + Avatar + Settings -->
        <div class="flex items-center gap-3">
          <!-- Points -->
          <NuxtLink
            to="/donates"
            class="hidden sm:flex items-center gap-1 px-3 py-1 rounded-lg transition-colors"
            :class="
              isDarkMode
                ? 'bg-vikinger-dark-200 hover:bg-vikinger-purple/10'
                : 'bg-gray-100 hover:bg-gray-200'
            "
          >
            <img src="/storage/images/badge/completedq.png" alt="points" class="w-6 h-6" />
            <span class="font-semibold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">
              {{ authUser.pp.toLocaleString() }}
            </span>
          </NuxtLink>

          <!-- Wallet -->
          <div
            class="hidden sm:flex items-center gap-1 px-3 py-1 rounded-lg transition-colors"
            :class="isDarkMode ? 'bg-vikinger-dark-200' : 'bg-gray-100'"
          >
            <img src="/storage/images/badge/goldc.png" alt="wallet" class="w-6 h-6" />
            <span class="font-semibold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">
              {{ authUser.wallet.toLocaleString() }}
            </span>
          </div>

          <!-- Avatar -->
          <NuxtLink to="/profile">
            <div
              class="w-10 h-10 rounded-full overflow-hidden border-2 border-vikinger-purple shadow-lg"
            >
              <img
                :src="authUser.profile_photo_url"
                :alt="authUser.name"
                class="w-full h-full object-cover"
              />
            </div>
          </NuxtLink>

          <!-- Theme Toggle -->
          <button
            @click="toggleTheme"
            class="hidden sm:flex items-center justify-center w-10 h-10 rounded-lg transition-all duration-300"
            :class="
              isDarkMode
                ? 'bg-vikinger-dark-200 hover:bg-vikinger-purple/20'
                : 'bg-gray-100 hover:bg-gray-200'
            "
            :title="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
          >
            <Icon
              v-if="isDarkMode"
              icon="fluent:weather-sunny-24-filled"
              class="w-5 h-5 text-yellow-400"
            />
            <Icon v-else icon="fluent:weather-moon-24-filled" class="w-5 h-5 text-blue-500" />
          </button>

          <!-- Settings Dropdown -->
          <div class="relative">
            <button
              @click="toggleSettings"
              class="flex items-center justify-center w-10 h-10 rounded-lg transition-all duration-300"
              :class="
                isSettingsOpen
                  ? 'bg-gradient-to-br from-vikinger-purple to-vikinger-cyan text-white shadow-vikinger'
                  : isDarkMode
                  ? 'bg-vikinger-dark-200 hover:bg-vikinger-purple/20 text-gray-300'
                  : 'bg-gray-100 hover:bg-gray-200 text-gray-700'
              "
            >
              <Icon icon="fluent:settings-24-regular" class="w-5 h-5" />
            </button>

            <!-- Dropdown Menu -->
            <div
              v-if="isSettingsOpen"
              class="absolute right-0 top-12 w-56 rounded-xl shadow-xl border overflow-hidden z-50"
              :class="
                isDarkMode
                  ? 'bg-vikinger-dark-100 border-vikinger-dark-50/30'
                  : 'bg-white border-gray-200'
              "
            >
              <div class="py-2">
                <NuxtLink
                  to="/profile"
                  @click="closeSettings"
                  class="flex items-center gap-3 px-4 py-3 transition-colors"
                  :class="isDarkMode ? 'hover:bg-vikinger-dark-200 text-gray-300' : 'hover:bg-gray-100 text-gray-700'"
                >
                  <Icon icon="fluent:person-24-regular" class="w-5 h-5" />
                  <span>โปรไฟล์</span>
                </NuxtLink>
                <NuxtLink
                  to="/settings"
                  @click="closeSettings"
                  class="flex items-center gap-3 px-4 py-3 transition-colors"
                  :class="isDarkMode ? 'hover:bg-vikinger-dark-200 text-gray-300' : 'hover:bg-gray-100 text-gray-700'"
                >
                  <Icon icon="fluent:settings-24-regular" class="w-5 h-5" />
                  <span>ตั้งค่า</span>
                </NuxtLink>
                <NuxtLink
                  to="/notifications"
                  @click="closeSettings"
                  class="flex items-center gap-3 px-4 py-3 transition-colors"
                  :class="isDarkMode ? 'hover:bg-vikinger-dark-200 text-gray-300' : 'hover:bg-gray-100 text-gray-700'"
                >
                  <Icon icon="fluent:alert-24-regular" class="w-5 h-5" />
                  <span>การแจ้งเตือน</span>
                </NuxtLink>
                <div class="border-t my-1" :class="isDarkMode ? 'border-vikinger-dark-50/30' : 'border-gray-200'"></div>
                <button
                  @click="authStore.logout(); closeSettings()"
                  class="w-full flex items-center gap-3 px-4 py-3 transition-colors text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20"
                >
                  <Icon icon="fluent:sign-out-24-regular" class="w-5 h-5" />
                  <span>ออกจากระบบ</span>
                </button>
              </div>
            </div>

            <!-- Backdrop to close dropdown -->
            <div
              v-if="isSettingsOpen"
              class="fixed inset-0 z-40"
              @click="closeSettings"
            ></div>
          </div>

          <!-- Right Drawer Toggle (Desktop) -->
          <button
            @click="toggleRightDrawer"
            class="hidden lg:flex items-center justify-center w-10 h-10 rounded-lg transition-all duration-300 relative overflow-hidden group"
            :class="
              isRightDrawerOpen
                ? 'bg-gradient-to-br from-vikinger-purple to-vikinger-cyan shadow-vikinger text-white'
                : isDarkMode
                ? 'hover:bg-vikinger-purple/10 text-gray-300'
                : 'hover:bg-gray-100 text-gray-700'
            "
          >
            <div
              v-if="!isRightDrawerOpen"
              class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
            ></div>
            <Icon 
              :icon="isRightDrawerOpen ? 'fluent:chat-24-filled' : 'fluent:chat-24-regular'" 
              class="w-6 h-6 relative z-10" 
            />
          </button>
        </div>
      </div>
    </header>

    <!-- ========================================
             MAIN LAYOUT (Below Header)
             ======================================== -->
    <div class="pt-16 flex min-h-screen">
      <!-- ========================================
                 LEFT DRAWER (Profile + Navigation)
        ======================================== -->
      <aside
        :class="[
          'fixed left-0 top-16 h-[calc(100vh-4rem)] overflow-y-auto transition-all duration-300 z-40',
          'hidden lg:block',
          isLeftDrawerOpen ? 'w-80' : 'w-20',
          isDarkMode
            ? 'bg-vikinger-dark-100 border-r border-vikinger-dark-50/30'
            : 'bg-white border-r border-gray-200',
        ]"
      >
        <!-- Expanded Content -->
        <div v-if="isLeftDrawerOpen" class="p-6 space-y-6">
          <!-- Profile Card -->
          <div class="text-center">
            <div class="relative inline-block mb-4">
              <img
                :src="authUser.profile_photo_url"
                class="w-24 h-24 rounded-full border-4 border-vikinger-purple shadow-lg"
                :alt="authUser.name"
              />
              <div
                class="absolute -bottom-2 -right-2 w-10 h-10 bg-gradient-vikinger rounded-full flex items-center justify-center text-white font-bold border-4 transition-colors duration-300"
                :class="isDarkMode ? 'border-vikinger-dark-100' : 'border-white'"
              >
                {{ authUser.level }}
              </div>
            </div>
            <h3 class="text-xl font-bold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">
              {{ authUser.name }}
            </h3>
            <p class="text-sm" :class="isDarkMode ? 'text-gray-400' : 'text-gray-600'">
              {{ authUser.email }}
            </p>
          </div>

          <!-- Badge Icons -->
          <div class="flex justify-center gap-2">
            <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
              <Icon icon="fluent:trophy-24-filled" class="w-6 h-6 text-white" />
            </div>
            <div class="w-10 h-10 bg-vikinger-purple rounded-lg flex items-center justify-center">
              <Icon icon="fluent:shield-checkmark-24-filled" class="w-6 h-6 text-white" />
            </div>
            <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
              <Icon icon="fluent:checkmark-circle-24-filled" class="w-6 h-6 text-white" />
            </div>
            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
              <Icon icon="fluent:star-24-filled" class="w-6 h-6 text-white" />
            </div>
          </div>

          <!-- Stats -->
          <div
            class="grid grid-cols-3 gap-4 text-center py-4 border-y transition-colors duration-300"
            :class="isDarkMode ? 'border-vikinger-dark-50/30' : 'border-gray-200'"
          >
            <div>
              <div class="text-2xl font-bold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">
                {{ authUser.posts }}
              </div>
              <div
                class="text-xs uppercase"
                :class="isDarkMode ? 'text-gray-400' : 'text-gray-600'"
              >
                Posts
              </div>
            </div>
            <div>
              <div class="text-2xl font-bold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">
                {{ authUser.friends }}
              </div>
              <div
                class="text-xs uppercase"
                :class="isDarkMode ? 'text-gray-400' : 'text-gray-600'"
              >
                Friends
              </div>
            </div>
            <div>
              <div class="text-2xl font-bold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">
                {{ authUser.visits }}
              </div>
              <div
                class="text-xs uppercase"
                :class="isDarkMode ? 'text-gray-400' : 'text-gray-600'"
              >
                Visits
              </div>
            </div>
          </div>

          <!-- Navigation Menu -->
          <div class="space-y-1">
            <NuxtLink
              to="/newsfeed"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300"
              :class="
                route.path === '/newsfeed'
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
              "
            >
              <Icon icon="fluent:chat-bubbles-question-24-regular" class="w-5 h-5" />
              <span class="font-semibold">Newsfeed</span>
            </NuxtLink>
            <NuxtLink
              to="/overview"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300"
              :class="
                route.path === '/overview'
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
              "
            >
              <Icon icon="fluent:data-histogram-24-regular" class="w-5 h-5" />
              <span class="font-semibold">Overview</span>
            </NuxtLink>
            <NuxtLink
              to="/groups"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300"
              :class="
                route.path === '/groups'
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
              "
            >
              <Icon icon="fluent:people-community-24-regular" class="w-5 h-5" />
              <span class="font-semibold">Groups</span>
            </NuxtLink>
            <NuxtLink
              to="/members"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300"
              :class="
                route.path === '/members'
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
              "
            >
              <Icon icon="fluent:person-24-regular" class="w-5 h-5" />
              <span class="font-semibold">Members</span>
            </NuxtLink>
            <NuxtLink
              to="/badges"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300"
              :class="
                route.path === '/badges'
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
              "
            >
              <Icon icon="fluent:shield-checkmark-24-regular" class="w-5 h-5" />
              <span class="font-semibold">Badges</span>
            </NuxtLink>
            <NuxtLink
              to="/quests"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300"
              :class="
                route.path === '/quests'
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
              "
            >
              <Icon icon="fluent:star-24-regular" class="w-5 h-5" />
              <span class="font-semibold">Quests</span>
            </NuxtLink>
            <NuxtLink
              to="/streams"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300"
              :class="
                route.path === '/streams'
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
              "
            >
              <Icon icon="fluent:video-clip-24-regular" class="w-5 h-5" />
              <span class="font-semibold">Streams</span>
            </NuxtLink>
            <NuxtLink
              to="/events"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300"
              :class="
                route.path === '/events'
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
              "
            >
              <Icon icon="fluent:calendar-24-regular" class="w-5 h-5" />
              <span class="font-semibold">Events</span>
            </NuxtLink>
          </div>
        </div>

        <!-- Collapsed Content -->
        <div v-else class="p-3 space-y-2 flex flex-col items-center">
          <!-- Profile Avatar (Collapsed) -->
          <NuxtLink to="/profile" class="mb-4">
            <img
              :src="authUser.profile_photo_url"
              class="w-12 h-12 rounded-full border-2 border-vikinger-purple shadow-lg"
              :alt="authUser.name"
            />
          </NuxtLink>

          <!-- Navigation Icons (Collapsed) -->
          <NuxtLink
            to="/newsfeed"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path === '/newsfeed'
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'Newsfeed'"
          >
            <Icon icon="fluent:chat-bubbles-question-24-regular" class="w-6 h-6" />
          </NuxtLink>
          <NuxtLink
            to="/overview"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path === '/overview'
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'Overview'"
          >
            <Icon icon="fluent:data-histogram-24-regular" class="w-6 h-6" />
          </NuxtLink>
          <NuxtLink
            to="/groups"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path === '/groups'
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'Groups'"
          >
            <Icon icon="fluent:people-community-24-regular" class="w-6 h-6" />
          </NuxtLink>
          <NuxtLink
            to="/members"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path === '/members'
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'Members'"
          >
            <Icon icon="fluent:person-24-regular" class="w-6 h-6" />
          </NuxtLink>
          <NuxtLink
            to="/badges"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path === '/badges'
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'Badges'"
          >
            <Icon icon="fluent:shield-checkmark-24-regular" class="w-6 h-6" />
          </NuxtLink>
          <NuxtLink
            to="/quests"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path === '/quests'
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'Quests'"
          >
            <Icon icon="fluent:star-24-regular" class="w-6 h-6" />
          </NuxtLink>
          <NuxtLink
            to="/streams"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path === '/streams'
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'Streams'"
          >
            <Icon icon="fluent:video-clip-24-regular" class="w-6 h-6" />
          </NuxtLink>
          <NuxtLink
            to="/events"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path === '/events'
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'Events'"
          >
            <Icon icon="fluent:calendar-24-regular" class="w-6 h-6" />
          </NuxtLink>
        </div>
      </aside>

      <!-- ========================================
                 TOP NAVIGATION BAR (Optional Slot)
                 ======================================== -->
      <!-- <div v-if="$slots.topNav" class="w-full">
        <slot name="topNav" />
      </div> -->

      <!-- ========================================
                 MAIN CONTENT AREA (3 Columns)
       ======================================== -->
      <main
        :class="[
          'flex-1 min-h-screen transition-all duration-300',
          isLeftDrawerOpen ? 'lg:pl-80' : 'lg:pl-20',
          isRightDrawerOpen ? 'lg:pr-80' : 'lg:pr-20',
        ]"
      >
        <div class="max-w-6xl mx-auto px-4 py-6">
          <!-- Hero Banner Slot (Full Width) -->
          <div v-if="$slots.hero" class="w-full mb-6">
            <slot name="hero" />
          </div>

          <!-- 12 Column Grid Layout -->
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Widgets (3/12) -->
            <div v-if="$slots.leftWidgets" class="hidden lg:block lg:col-span-3 space-y-4">
              <slot name="leftWidgets" />
            </div>

            <!-- Center Content (dynamic span based on widgets) -->
            <div
              :class="[
                'w-full',
                $slots.leftWidgets && $slots.rightWidgets
                  ? 'lg:col-span-6'
                  : $slots.leftWidgets || $slots.rightWidgets
                  ? 'lg:col-span-9'
                  : 'lg:col-span-12',
              ]"
            >
              <slot />
            </div>

            <!-- Right Widgets (3/12) -->
            <div v-if="$slots.rightWidgets" class="hidden lg:block lg:col-span-3 space-y-4">
              <slot name="rightWidgets" />
            </div>
          </div>
        </div>
      </main>

      <!-- ========================================
                 RIGHT DRAWER (Chat + Activity)
      ======================================== -->
      <aside
        :class="[
          'fixed right-0 top-16 h-[calc(100vh-4rem)] overflow-y-auto transition-all duration-300 z-40',
          'hidden lg:block',
          isRightDrawerOpen ? 'w-80' : 'w-20',
          isDarkMode
            ? 'bg-vikinger-dark-100 border-l border-vikinger-dark-50/30'
            : 'bg-white border-l border-gray-200',
        ]"
      >
        <!-- Expanded Content -->
        <div v-if="isRightDrawerOpen" key="expanded-right" class="p-6 space-y-6">
          <h3 class="text-lg font-bold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">
            Messages / Chat
          </h3>

          <!-- Search -->
          <div class="relative">
            <input
              type="text"
              placeholder="Search Messages..."
              class="w-full px-4 py-2 pl-10 rounded-lg border transition-colors duration-300 focus:ring-2 focus:ring-vikinger-purple/20"
              :class="
                isDarkMode
                  ? 'bg-vikinger-dark-200 border-vikinger-dark-50/30 text-white placeholder-gray-400 focus:border-vikinger-purple'
                  : 'bg-gray-50 border-gray-300 text-gray-900 placeholder-gray-500 focus:border-vikinger-purple'
              "
            />
            <Icon
              icon="fluent:search-24-regular"
              class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5"
              :class="isDarkMode ? 'text-gray-400' : 'text-gray-500'"
            />
          </div>

          <!-- Online Friends -->
          <div class="space-y-3">
            <div
              v-for="i in 8"
              :key="i"
              class="flex items-center gap-3 p-2 rounded-lg cursor-pointer transition-colors"
              :class="isDarkMode ? 'hover:bg-vikinger-dark-200' : 'hover:bg-gray-100'"
            >
              <div class="relative">
                <img :src="`https://i.pravatar.cc/150?img=${i}`" class="w-10 h-10 rounded-full" />
                <div
                  class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2"
                  :class="isDarkMode ? 'border-vikinger-dark-100' : 'border-white'"
                ></div>
              </div>
              <div class="flex-1">
                <div
                  class="text-sm font-semibold"
                  :class="isDarkMode ? 'text-white' : 'text-gray-900'"
                >
                  User {{ i }}
                </div>
                <div class="text-xs" :class="isDarkMode ? 'text-gray-400' : 'text-gray-600'">
                  Online
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Collapsed Content -->
        <div v-else key="collapsed-right" class="p-2 pt-6 space-y-3 flex flex-col items-center">
          <!-- Online Friends Icons (Collapsed) -->
          <div
            v-for="i in 6"
            :key="i"
            class="relative cursor-pointer transition-transform hover:scale-110"
            :title="`User ${i}`"
          >
            <img :src="`https://i.pravatar.cc/150?img=${i}`" class="w-11 h-11 rounded-full" />
            <div
              class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2"
              :class="isDarkMode ? 'border-vikinger-dark-100' : 'border-white'"
            ></div>
          </div>
        </div>
      </aside>

    </div>

    <!-- Mobile Sidebar Overlay -->
    <div v-if="isMobileSidebarOpen" class="fixed inset-0 z-50 lg:hidden">
      <div class="absolute inset-0 bg-black/50" @click="toggleMobileSidebar"></div>
      <aside
        class="absolute left-0 top-0 h-full w-80 max-w-[85vw] overflow-y-auto transition-colors duration-300"
        :class="isDarkMode ? 'bg-vikinger-dark-100' : 'bg-white'"
      >
        <div class="p-6 space-y-6">
          <!-- Same content as left drawer -->
          <div class="text-center">
            <img
              :src="authUser.profile_photo_url"
              class="w-20 h-20 rounded-full mx-auto mb-3 border-4 border-vikinger-purple"
            />
            <h3 class="text-lg font-bold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">
              {{ authUser.name }}
            </h3>
          </div>
          <nav class="space-y-1">
            <NuxtLink
              v-for="item in navigation"
              :key="item.href"
              :to="item.href"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors"
              :class="
                isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10'
                  : 'text-gray-700 hover:bg-gray-100'
              "
            >
              <Icon :icon="item.icon" class="w-5 h-5" />
              <span>{{ item.name }}</span>
            </NuxtLink>
          </nav>
        </div>
      </aside>
    </div>
  </div>
</template>

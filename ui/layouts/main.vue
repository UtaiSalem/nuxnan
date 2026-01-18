<script setup>
import { ref, computed, onBeforeUnmount, onMounted, watch, provide } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import { useUIStore } from '~/stores/ui'
import { useGamification } from '~/composables/useGamification'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const uiStore = useUIStore()
const { getPointsLeaderboard, isLoading: isGamificationLoading } = useGamification()

// Leaderboard data
const leaderboard = ref([])
const fetchLeaderboard = async () => {
  try {
    const data = await getPointsLeaderboard({ limit: 10 })
    leaderboard.value = data.leaderboard
  } catch (error) {
    console.error('Failed to fetch leaderboard:', error)
  }
}

// สีนุ่มนวลสบายตาสำหรับอันดับต่างๆ (Muted/Pastel colors)
const getRankColor = (index) => {
  const colors = [
    'bg-amber-500',      // 1st - ทองนุ่มนวล
    'bg-slate-400',      // 2nd - เงินนุ่มนวล
    'bg-orange-400',     // 3rd - ทองแดงนุ่มนวล
    'bg-sky-400',        // 4th - ฟ้านุ่มนวล
    'bg-emerald-400',    // 5th - เขียวนุ่มนวล
    'bg-violet-400',     // 6th - ม่วงนุ่มนวล
    'bg-rose-400',       // 7th - ชมพูนุ่มนวล
    'bg-teal-400',       // 8th - เขียวน้ำทะเลนุ่มนวล
    'bg-indigo-400',     // 9th - คราม นุ่มนวล
    'bg-cyan-400'        // 10th - ฟ้าอมเขียวนุ่มนวล
  ]
  return colors[index] || 'bg-slate-400'
}

// สีพื้นหลัง avatar นุ่มนวลสบายตา (hex without #)
const getAvatarUrl = (user, index = 0) => {
  if (user.avatar) return user.avatar
  const bgColors = [
    '94a3b8', // slate-400
    '64748b', // slate-500
    '78716c', // stone-500
    '6b7280', // gray-500
    '71717a', // zinc-500
    '737373', // neutral-500
    'a3a3a3', // neutral-400
    '9ca3af', // gray-400
    'a1a1aa', // zinc-400
    'a8a29e', // stone-400
  ]
  const bgColor = bgColors[index % bgColors.length]
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=${bgColor}&color=fff`
}

// Drawer states
const isLeftDrawerOpen = ref(false)
const isRightDrawerOpen = ref(false)
const isMobileSidebarOpen = ref(false)
const isSettingsOpen = ref(false)
const isEarnMenuOpen = ref(false)

// Set sidebar and Earn menu state based on screen size
const updateSidebarState = () => {
  if (typeof window !== 'undefined') {
    // lg breakpoint = 1024px, expand on large screens
    const isLargeScreen = window.innerWidth >= 1024
    isLeftDrawerOpen.value = isLargeScreen
    isEarnMenuOpen.value = isLargeScreen
  }
}

// Toggle Earn submenu
const toggleEarnMenu = () => {
  isEarnMenuOpen.value = !isEarnMenuOpen.value
}

// Earn submenu items
const earnSubmenu = [
  { name: 'คะแนน', href: '/earn/points', icon: 'fluent:coin-stack-24-regular' },
  { name: 'กระเป๋าเงิน', href: '/earn/wallet', icon: 'fluent:wallet-24-regular' },
  { name: 'คูปอง', href: '/earn/coupons', icon: 'fluent:ticket-diagonal-24-regular' },
  { name: 'รางวัล', href: '/earn/rewards', icon: 'fluent:gift-24-regular' },
  { name: 'ความสำเร็จ', href: '/earn/gamification', icon: 'fluent:trophy-24-regular' },
]

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
    is_plearnd_admin: user.is_plearnd_admin || false,
  }
})

// Navigation
const navigation = [
  { name: 'กระดานข่าว', href: '/play/newsfeed', icon: 'fluent:feed-24-regular' },
  { name: 'โรงเรียน', href: '/academies', icon: 'mdi:school-outline' },
  { name: 'รายวิชา', href: '/learn/courses', icon: 'fluent-mdl2:publish-course' },
  { name: 'สะสมแต้ม', href: '/earn/donates', icon: 'mdi:hand-coin-outline' },
  { name: 'ดูสินค้า', href: '/earn/advertise', icon: 'eos-icons:product-subscriptions-outlined' },
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

  // Set initial sidebar state based on screen size
  updateSidebarState()
  
  // Add resize listener to auto expand/collapse sidebar
  window.addEventListener('resize', updateSidebarState)

  if (authStore.isAuthenticated && !authStore.user) {
    try {
      await authStore.fetchUser()
    } catch (error) {
      console.error('Failed to fetch user:', error)
    }
  }

  fetchLeaderboard()
})

// Cleanup resize listener
onBeforeUnmount(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', updateSidebarState)
  }
})

// Provide theme to child components
provide('isDarkMode', isDarkMode)

// Settings URL - dynamic based on user reference_code
const settingsUrl = computed(() => {
  if (authStore.user?.reference_code) {
    return `/profile/${authStore.user.reference_code}/settings`
  }
  return '/settings' // Fallback that will redirect
})
provide('toggleTheme', toggleTheme)

// Format number to K/M format
const formatNumber = (num) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M'
  }
  if (num >= 1000) {
    return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K'
  }
  return num.toLocaleString()
}

// For testing point changes
const handleTestChangePoints = () => {
  authStore.addPoints(100)
}

// Universal QR Scanner Modal
const isQRScannerOpen = ref(false)

const openQRScanner = () => {
  isQRScannerOpen.value = true
}

const onQRActionComplete = (result) => {
  // Refresh leaderboard after any QR action
  if (result.success) {
    fetchLeaderboard()
  }
}

</script>

<template>
  <div
    class="min-h-screen transition-colors duration-300"
    :class="isDarkMode ? 'bg-vikinger-dark dark' : 'bg-gray-200 light'"
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
              class="hidden md:inline-block px-3 py-1 text-lg font-audiowide text-white rounded-lg bg-gradient-vikinger shadow-lg"
              >NUXNAN</span
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
        <div class="flex items-center gap-2">
          <!-- Points -->
          <NuxtLink
            to="/earn/points"
            class="hidden sm:flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl transition-all hover:scale-105"
            :class="
              isDarkMode
                ? 'bg-gradient-to-r from-amber-900/40 to-orange-900/30 hover:from-amber-900/60 hover:to-orange-900/50 border border-amber-500/30'
                : 'bg-gradient-to-r from-amber-50 to-orange-50 hover:from-amber-100 hover:to-orange-100 border border-amber-200'
            "
          >
            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center">
              <Icon icon="fluent:star-24-filled" class="w-3.5 h-3.5 text-white" />
            </div>
            <span class="font-bold text-sm" :class="isDarkMode ? 'text-amber-400' : 'text-amber-600'">
              {{ formatNumber(authUser.pp) }}
            </span>
          </NuxtLink>

          <!-- Wallet -->
          <NuxtLink
            to="/earn/wallet"
            class="hidden sm:flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl transition-all hover:scale-105"
            :class="
              isDarkMode
                ? 'bg-gradient-to-r from-emerald-900/40 to-green-900/30 hover:from-emerald-900/60 hover:to-green-900/50 border border-emerald-500/30'
                : 'bg-gradient-to-r from-emerald-50 to-green-50 hover:from-emerald-100 hover:to-green-100 border border-emerald-200'
            "
          >
            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center">
              <Icon icon="fluent:money-24-filled" class="w-3.5 h-3.5 text-white" />
            </div>
            <span class="font-bold text-sm" :class="isDarkMode ? 'text-emerald-400' : 'text-emerald-600'">
              ฿{{ formatNumber(authUser.wallet) }}
            </span>
          </NuxtLink>

          <!-- Scan QR Button -->
          <button
            @click="openQRScanner"
            class="flex items-center justify-center w-10 h-10 rounded-xl transition-all duration-300 hover:scale-110"
            :class="
              isDarkMode
                ? 'bg-gradient-to-br from-violet-600 to-purple-600 hover:from-violet-500 hover:to-purple-500 shadow-lg shadow-purple-500/30'
                : 'bg-gradient-to-br from-violet-500 to-purple-500 hover:from-violet-400 hover:to-purple-400 shadow-lg shadow-purple-500/30'
            "
            title="สแกน QR Code"
          >
            <Icon icon="fluent:qr-code-24-filled" class="w-5 h-5 text-white" />
          </button>

          <!-- Avatar -->
          <NuxtLink to="/profile" class="group">
            <div
              class="w-10 h-10 rounded-full overflow-hidden border-2 border-vikinger-cyan shadow-lg group-hover:border-vikinger-purple group-hover:scale-110 transition-all"
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
                  :to="settingsUrl"
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
                
                <!-- Admin Menu Section -->
                <div v-if="authUser.is_plearnd_admin" class="border-t my-1" :class="isDarkMode ? 'border-vikinger-dark-50/30' : 'border-gray-200'"></div>
                <div v-if="authUser.is_plearnd_admin" class="px-4 py-2 text-xs font-semibold uppercase tracking-wider" :class="isDarkMode ? 'text-gray-500' : 'text-gray-400'">
                  เมนูผู้ดูแลระบบ
                </div>
                <NuxtLink
                  v-if="authUser.is_plearnd_admin"
                  to="/PlearndAdmin/Donation/ApproveDonation"
                  @click="closeSettings"
                  class="flex items-center gap-3 px-4 py-3 transition-colors"
                  :class="isDarkMode ? 'hover:bg-vikinger-dark-200 text-gray-300' : 'hover:bg-gray-100 text-gray-700'"
                >
                  <Icon icon="mdi:hand-coin" class="w-5 h-5 text-purple-500" />
                  <span>อนุมัติการสนับสนุน</span>
                </NuxtLink>
                <NuxtLink
                  v-if="authUser.is_plearnd_admin"
                  to="/Admin/Resetpassword"
                  @click="closeSettings"
                  class="flex items-center gap-3 px-4 py-3 transition-colors"
                  :class="isDarkMode ? 'hover:bg-vikinger-dark-200 text-gray-300' : 'hover:bg-gray-100 text-gray-700'"
                >
                  <Icon icon="mdi:lock-reset" class="w-5 h-5 text-orange-500" />
                  <span>รีเซ็ตรหัสผ่าน</span>
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
              :icon="isRightDrawerOpen ? 'fluent:trophy-24-filled' : 'fluent:trophy-24-regular'" 
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
                โพสต์
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
                เพื่อน
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
                เยี่ยมชม
              </div>
            </div>
          </div>

          <!-- Navigation Menu -->
          <div class="space-y-1">
            <NuxtLink
              to="/dashboard"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300"
              :class="
                route.path === '/dashboard'
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
              "
            >
              <Icon icon="fluent:grid-24-regular" class="w-5 h-5" />
              <span class="font-semibold">แดชบอร์ด</span>
            </NuxtLink>
            <NuxtLink
              to="/play/newsfeed"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300"
              :class="
                route.path === '/play/newsfeed'
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
              "
            >
              <Icon icon="fluent:chat-bubbles-question-24-regular" class="w-5 h-5" />
              <span class="font-semibold">ฟีดข่าว</span>
            </NuxtLink>
            <NuxtLink
              to="/academies"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300"
              :class="
                route.path.startsWith('/academies')
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
              "
            >
              <Icon icon="mdi:school-outline" class="w-5 h-5" />
              <span class="font-semibold">โรงเรียน</span>
            </NuxtLink>
            <NuxtLink
              to="/Learn/Courses"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300"
              :class="
                route.path.startsWith('/Learn/Courses') || route.path.startsWith('/learn/courses') || route.path.startsWith('/courses')
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
              "
            >
              <Icon icon="fluent:book-24-regular" class="w-5 h-5" />
              <span class="font-semibold">คอร์สเรียน</span>
            </NuxtLink>
            <!-- Earn Menu with Submenu -->
            <div>
              <button
                @click="toggleEarnMenu"
                class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-lg transition-all duration-300"
                :class="
                  route.path.startsWith('/earn')
                    ? 'bg-gradient-vikinger text-white shadow-vikinger'
                    : isDarkMode
                    ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
                "
              >
                <div class="flex items-center gap-3">
                  <Icon icon="fluent:wallet-24-regular" class="w-5 h-5" />
                  <span class="font-semibold">รายได้</span>
                </div>
                <Icon 
                  :icon="isEarnMenuOpen ? 'fluent:chevron-up-24-regular' : 'fluent:chevron-down-24-regular'" 
                  class="w-4 h-4 transition-transform duration-200"
                />
              </button>
              <!-- Submenu -->
              <transition name="expand">
                <div v-if="isEarnMenuOpen" class="ml-4 mt-1 space-y-1 border-l-2 border-vikinger-purple/30 pl-3">
                  <NuxtLink
                    v-for="sub in earnSubmenu"
                    :key="sub.href"
                    :to="sub.href"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-sm"
                    :class="
                      route.path === sub.href
                        ? 'bg-vikinger-purple/20 text-vikinger-purple dark:text-vikinger-cyan font-medium'
                        : isDarkMode
                        ? 'text-gray-400 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                        : 'text-gray-600 hover:bg-gray-100 hover:text-vikinger-purple'
                    "
                  >
                    <Icon :icon="sub.icon" class="w-4 h-4" />
                    <span>{{ sub.name }}</span>
                  </NuxtLink>
                </div>
              </transition>
            </div>
            <NuxtLink
              to="/notifications"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300"
              :class="
                route.path === '/notifications'
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
              "
            >
              <Icon icon="fluent:alert-24-regular" class="w-5 h-5" />
              <span class="font-semibold">การแจ้งเตือน</span>
            </NuxtLink>
            <NuxtLink
              :to="settingsUrl"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300"
              :class="
                route.path.includes('/settings')
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                  : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
              "
            >
              <Icon icon="fluent:settings-24-regular" class="w-5 h-5" />
              <span class="font-semibold">ตั้งค่า</span>
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
            to="/dashboard"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path === '/dashboard'
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'แดชบอร์ด'"
          >
            <Icon icon="fluent:grid-24-regular" class="w-6 h-6" />
          </NuxtLink>
          <NuxtLink
            to="/play/newsfeed"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path === '/play/newsfeed'
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'ฟีดข่าว'"
          >
            <Icon icon="fluent:chat-bubbles-question-24-regular" class="w-6 h-6" />
          </NuxtLink>
          <NuxtLink
            to="/academies"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path.startsWith('/academies')
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'โรงเรียน'"
          >
            <Icon icon="mdi:school-outline" class="w-6 h-6" />
          </NuxtLink>
          <NuxtLink
            to="/Learn/Courses"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path.startsWith('/Learn/Courses') || route.path.startsWith('/learn/courses') || route.path.startsWith('/courses')
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'คอร์สเรียน'"
          >
            <Icon icon="fluent:book-24-regular" class="w-6 h-6" />
          </NuxtLink>
          <NuxtLink
            to="/earn/points"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path.startsWith('/earn')
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'รายได้'"
          >
            <Icon icon="fluent:wallet-24-regular" class="w-6 h-6" />
          </NuxtLink>
          <NuxtLink
            to="/notifications"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path === '/notifications'
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'การแจ้งเตือน'"
          >
            <Icon icon="fluent:alert-24-regular" class="w-6 h-6" />
          </NuxtLink>
          <NuxtLink
            :to="settingsUrl"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path.includes('/settings')
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'ตั้งค่า'"
          >
            <Icon icon="fluent:settings-24-regular" class="w-6 h-6" />
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
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">
              กระดานผู้นำ
            </h3>
            <span class="px-2 py-1 rounded-lg bg-vikinger-purple text-white text-[10px] font-bold uppercase tracking-wider">นักเรียนยอดเยี่ยม</span>
          </div>

          <!-- Search -->
          <div class="relative">
            <input
              type="text"
              placeholder="ค้นหาสมาชิก..."
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

          <!-- Online Friends (Ranked) -->
          <div v-if="isGamificationLoading && !leaderboard.length" class="space-y-3 animate-pulse">
            <div v-for="i in 10" :key="i" class="flex items-center gap-3 p-2">
              <div class="w-10 h-10 bg-gray-200 dark:bg-vikinger-dark-200 rounded-full"></div>
              <div class="flex-1 space-y-2">
                <div class="h-3 bg-gray-200 dark:bg-vikinger-dark-200 rounded w-2/3"></div>
                <div class="h-2 bg-gray-200 dark:bg-vikinger-dark-200 rounded w-1/2"></div>
              </div>
            </div>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="(user, index) in leaderboard"
              :key="user.id"
              class="flex items-center gap-3 p-2 rounded-lg cursor-pointer transition-colors group"
              :class="isDarkMode ? 'hover:bg-vikinger-dark-200' : 'hover:bg-gray-100'"
            >
              <div class="relative">
                <img :src="getAvatarUrl(user, index)" class="w-10 h-10 rounded-full border-2 border-transparent group-hover:border-vikinger-purple transition-colors bg-white object-cover" />
                <div
                  class="absolute -top-1 -left-1 w-5 h-5 rounded-full border-2 flex items-center justify-center text-[10px] font-bold text-white shadow-sm"
                  :class="[
                    getRankColor(index),
                    isDarkMode ? 'border-vikinger-dark-100' : 'border-white'
                  ]"
                >
                  {{ index + 1 }}
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div
                  class="text-sm font-semibold truncate"
                  :class="isDarkMode ? 'text-white' : 'text-gray-900'"
                >
                  {{ user.name }}
                </div>
                <div class="flex items-center gap-3 mt-1">
                  <div class="flex items-center gap-1 text-[10px] font-bold text-vikinger-purple" title="แต้มสะสม (PP)">
                    <img src="/storage/images/badge/completedq.png" class="w-3.5 h-3.5" alt="pp" />
                    {{ formatNumber(user.points || 0) }}
                  </div>
                </div>
              </div>
              <div class="shrink-0 group-hover:scale-110 transition-transform">
                <Icon icon="fluent:star-24-filled" class="w-4 h-4 text-vikinger-yellow" />
              </div>
            </div>
            
            <div v-if="!leaderboard.length" class="text-center py-4 text-gray-500 dark:text-gray-400 text-xs">
              ยังไม่มีข้อมูลลำดับ
            </div>
          </div>
        </div>

        <!-- Collapsed Content -->
        <div v-if="!isRightDrawerOpen" key="collapsed-right" class="p-2 pt-6 space-y-3 flex flex-col items-center">
          <!-- Top Ranked Icons (Collapsed) -->
          <div
            v-for="(user, index) in leaderboard.slice(0, 10)"
            :key="user.id"
            class="relative cursor-pointer transition-transform hover:scale-110 group"
            :title="`Rank ${index + 1}: ${user.name}`"
          >
            <img :src="getAvatarUrl(user, index)" class="w-11 h-11 rounded-full border-2 border-transparent group-hover:border-vikinger-purple transition-colors bg-white object-cover" />
            <div
              class="absolute -top-1 -left-1 w-5 h-5 rounded-full border-2 flex items-center justify-center text-[9px] font-bold text-white shadow-sm"
              :class="[
                getRankColor(index),
                isDarkMode ? 'border-vikinger-dark-100' : 'border-white'
              ]"
            >
              {{ index + 1 }}
            </div>
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
          <!-- Close Button -->
          <button
            @click="toggleMobileSidebar"
            class="absolute top-4 right-4 p-2 rounded-full transition-colors"
            :class="isDarkMode ? 'hover:bg-vikinger-purple/10' : 'hover:bg-gray-100'"
          >
            <Icon icon="mdi:close" class="w-6 h-6" :class="isDarkMode ? 'text-gray-300' : 'text-gray-700'" />
          </button>

          <!-- Profile Card -->
          <div class="text-center">
            <div class="relative inline-block mb-3">
              <img
                :src="authUser.profile_photo_url"
                class="w-20 h-20 rounded-full mx-auto border-4 border-vikinger-purple"
              />
              <div
                class="absolute -bottom-1 -right-1 w-8 h-8 bg-gradient-vikinger rounded-full flex items-center justify-center text-white text-xs font-bold border-2"
                :class="isDarkMode ? 'border-vikinger-dark-100' : 'border-white'"
              >
                {{ authUser.level }}
              </div>
            </div>
            <h3 class="text-lg font-bold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">
              {{ authUser.name }}
            </h3>
            <p class="text-sm" :class="isDarkMode ? 'text-gray-400' : 'text-gray-500'">
              {{ authUser.email }}
            </p>
          </div>

          <!-- Stats -->
          <div class="flex justify-center gap-6 py-3 border-y" :class="isDarkMode ? 'border-vikinger-dark-50/30' : 'border-gray-200'">
            <div class="text-center">
              <div class="font-bold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">{{ authUser.posts }}</div>
              <div class="text-xs uppercase" :class="isDarkMode ? 'text-gray-400' : 'text-gray-500'">โพสต์</div>
            </div>
            <div class="text-center">
              <div class="font-bold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">{{ authUser.friends }}</div>
              <div class="text-xs uppercase" :class="isDarkMode ? 'text-gray-400' : 'text-gray-500'">เพื่อน</div>
            </div>
            <div class="text-center">
              <div class="font-bold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">{{ authUser.visits }}</div>
              <div class="text-xs uppercase" :class="isDarkMode ? 'text-gray-400' : 'text-gray-500'">เยี่ยมชม</div>
            </div>
          </div>

          <!-- Navigation Menu -->
          <nav class="space-y-1">
            <NuxtLink
              to="/dashboard"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors"
              :class="
                route.path === '/dashboard'
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10'
                  : 'text-gray-700 hover:bg-gray-100'
              "
            >
              <Icon icon="fluent:grid-24-regular" class="w-5 h-5" />
              <span class="font-semibold">แดชบอร์ด</span>
            </NuxtLink>
            <NuxtLink
              to="/play/newsfeed"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors"
              :class="
                route.path === '/play/newsfeed'
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10'
                  : 'text-gray-700 hover:bg-gray-100'
              "
            >
              <Icon icon="fluent:chat-bubbles-question-24-regular" class="w-5 h-5" />
              <span class="font-semibold">ฟีดข่าว</span>
            </NuxtLink>
            <NuxtLink
              to="/academies"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors"
              :class="
                route.path.startsWith('/academies')
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10'
                  : 'text-gray-700 hover:bg-gray-100'
              "
            >
              <Icon icon="mdi:school-outline" class="w-5 h-5" />
              <span class="font-semibold">โรงเรียน</span>
            </NuxtLink>
            <NuxtLink
              to="/Learn/Courses"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors"
              :class="
                route.path.startsWith('/Learn/Courses') || route.path.startsWith('/learn/courses')
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10'
                  : 'text-gray-700 hover:bg-gray-100'
              "
            >
              <Icon icon="fluent:book-24-regular" class="w-5 h-5" />
              <span class="font-semibold">คอร์สเรียน</span>
            </NuxtLink>

            <!-- Earn with Submenu -->
            <div>
              <button
                @click="toggleEarnMenu"
                class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-lg transition-colors"
                :class="
                  route.path.startsWith('/earn')
                    ? 'bg-gradient-vikinger text-white shadow-vikinger'
                    : isDarkMode
                    ? 'text-gray-300 hover:bg-vikinger-purple/10'
                    : 'text-gray-700 hover:bg-gray-100'
                "
              >
                <div class="flex items-center gap-3">
                  <Icon icon="fluent:wallet-24-regular" class="w-5 h-5" />
                  <span class="font-semibold">รายได้</span>
                </div>
                <Icon 
                  :icon="isEarnMenuOpen ? 'fluent:chevron-up-24-regular' : 'fluent:chevron-down-24-regular'" 
                  class="w-4 h-4"
                />
              </button>
              <!-- Submenu -->
              <transition name="expand">
                <div v-if="isEarnMenuOpen" class="ml-4 mt-1 space-y-1 border-l-2 border-vikinger-purple/30 pl-3">
                  <NuxtLink
                    v-for="sub in earnSubmenu"
                    :key="sub.href"
                    :to="sub.href"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors text-sm"
                    :class="
                      route.path === sub.href
                        ? 'bg-vikinger-purple/20 text-vikinger-purple dark:text-vikinger-cyan font-medium'
                        : isDarkMode
                        ? 'text-gray-400 hover:bg-vikinger-purple/10'
                        : 'text-gray-600 hover:bg-gray-100'
                    "
                  >
                    <Icon :icon="sub.icon" class="w-4 h-4" />
                    <span>{{ sub.name }}</span>
                  </NuxtLink>
                </div>
              </transition>
            </div>

            <NuxtLink
              to="/notifications"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors"
              :class="
                route.path === '/notifications'
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10'
                  : 'text-gray-700 hover:bg-gray-100'
              "
            >
              <Icon icon="fluent:alert-24-regular" class="w-5 h-5" />
              <span class="font-semibold">การแจ้งเตือน</span>
            </NuxtLink>
            <NuxtLink
              :to="settingsUrl"
              class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors"
              :class="
                route.path.includes('/settings')
                  ? 'bg-gradient-vikinger text-white shadow-vikinger'
                  : isDarkMode
                  ? 'text-gray-300 hover:bg-vikinger-purple/10'
                  : 'text-gray-700 hover:bg-gray-100'
              "
            >
              <Icon icon="fluent:settings-24-regular" class="w-5 h-5" />
              <span class="font-semibold">ตั้งค่า</span>
            </NuxtLink>
          </nav>
        </div>
      </aside>
    </div>
    
    <!-- Universal QR Scanner Modal -->
    <QrUniversalQRModal 
      v-model="isQRScannerOpen" 
      @action-complete="onQRActionComplete"
    />
  </div>
</template>

<style scoped>
/* Expand transition for submenu */
.expand-enter-active,
.expand-leave-active {
  transition: all 0.2s ease;
  overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-10px);
}

.expand-enter-to,
.expand-leave-from {
  opacity: 1;
  max-height: 200px;
  transform: translateY(0);
}
</style>
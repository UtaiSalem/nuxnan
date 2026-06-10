<script setup>
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import { useUIStore } from '~/stores/ui'
import { useGamificationStore } from '~/stores/gamification'
import { useGamification } from '~/composables/useGamification'
import { useResponsiveSidebar } from '~/composables/useResponsiveSidebar'
import QrUniversalQRModal from '~/components/qr/UniversalQRModal.vue'
import LayoutBottomNav from '~/components/layout/BottomNav.vue'
import PersonalCodeCard from '~/components/user/PersonalCodeCard.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const uiStore = useUIStore()
const gamificationStore = useGamificationStore()
const { isLoading: isGamificationLoading } = useGamification()

// Leaderboard data from store
const leaderboard = computed(() => gamificationStore.leaderboard)
const fetchLeaderboard = async () => {
  await gamificationStore.fetchLeaderboard({ limit: 10 })
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
const {
  isCollapsed: isLeftDrawerCollapsed,
  isMobileOpen: isMobileSidebarOpen,
  isDesktop,
  isWideDesktop,
  toggleSidebar: toggleLeftDrawer
} = useResponsiveSidebar()

const isLeftDrawerOpen = computed(() => !isLeftDrawerCollapsed.value)

const enableRightSidebar = ref(false)
const isRightDrawerOpen = ref(false)
const isSettingsOpen = ref(false)
const isEarnMenuOpen = ref(false)
const isGamesMenuOpen = ref(false)

// Set menus state based on screen size
watch(isDesktop, (val) => {
  isEarnMenuOpen.value = val
  isGamesMenuOpen.value = val
}, { immediate: true })

// Auto-expand when in games section
watch(() => route.path, (path) => {
  if (path.startsWith('/play/games')) isGamesMenuOpen.value = true
}, { immediate: true })

// Toggle Earn submenu
const toggleEarnMenu = () => {
  isEarnMenuOpen.value = !isEarnMenuOpen.value
}

// Toggle Games submenu
const toggleGamesMenu = () => {
  isGamesMenuOpen.value = !isGamesMenuOpen.value
}

// Earn submenu items
const earnSubmenu = [
  { name: 'คะแนน', href: '/earn/points', icon: 'fluent:coin-stack-24-regular' },
  { name: 'ประวัติการซื้อ', href: '/Earn/PurchaseHistory', icon: 'fluent:history-24-regular' },
  { name: 'รายได้ของฉัน', href: '/Earn/SalesAnalytics', icon: 'fluent:data-line-24-regular' },
  { name: 'กระเป๋าเงิน', href: '/earn/wallet', icon: 'fluent:wallet-24-regular' },
  { name: 'คูปอง', href: '/earn/coupons', icon: 'fluent:ticket-diagonal-24-regular' },
  { name: 'รางวัล', href: '/earn/rewards', icon: 'fluent:gift-24-regular' },
  { name: 'ความสำเร็จ', href: '/earn/gamification', icon: 'fluent:trophy-24-regular' },
]

// Games submenu items
const gamesSubmenu = [
  { name: 'หน้าหลักเกม', href: '/play/games', icon: 'fluent:home-24-regular' },
  { name: 'Cross Math', href: '/play/games/cross-math-game', icon: 'fluent:calculator-24-regular' },
  { name: 'Vocab Match', href: '/play/games/english-vocab-game', icon: 'fluent:book-open-24-regular' },
  { name: 'ทายตัวเลข', href: '/play/games/guessing-number-game', icon: 'fluent:number-symbol-24-regular' },
  { name: 'XO', href: '/play/games/xo-game', icon: 'fluent:grid-24-regular' },
  { name: 'งู', href: '/play/games/snake-game', icon: 'fluent:animal-turtle-24-regular' },
  { name: 'Mental Math', href: '/play/games/mental-math-game', icon: 'fluent:brain-circuit-24-filled' },
  { name: 'จับคู่', href: '/play/games/mental-match', icon: 'fluent:brain-circuit-24-regular' },
  { name: 'พิมพ์ดีด', href: '/play/games/typing', icon: 'fluent:keyboard-24-regular' },
]

// Theme state
const isDarkMode = ref(false)

const toNumber = (value, fallback = 0) => {
  const number = Number(value)
  return Number.isFinite(number) ? number : fallback
}

const firstNumber = (...values) => {
  for (const value of values) {
    if (value === null || value === undefined || value === '') continue
    const number = Number(value)
    if (Number.isFinite(number)) return number
  }
  return 0
}

const formatCompactCount = (value) => {
  const number = Math.max(0, firstNumber(value))

  if (number >= 1000) {
    return new Intl.NumberFormat('en-US', {
      notation: 'compact',
      maximumFractionDigits: 1,
    }).format(number)
  }

  return new Intl.NumberFormat('en-US').format(number)
}

// User data
const authUser = computed(() => {
  const user = authStore.user
  if (!user) {
    return {
      name: 'Guest',
      username: 'guest',
      email: '',
      personalCode: '',
      avatar: '/images/default-avatar.png',
      pp: 0,
      wallet: 0,
      level: 1,
      posts: '0',
      friends: '0',
      visits: '0',
      is_super_admin: false,
    }
  }

  // Use avatar first (always set by backend with fallback to UI Avatars)
  const avatarUrl = user.avatar || user.profile_photo_url || '/images/default-avatar.png'
  const level = firstNumber(user.xp_level, user.level) || 1
  const postsCount = firstNumber(user.posts_count, user.posts)
  const friendsCount = firstNumber(user.friends_count, user.friends)
  const visitsCount = firstNumber(user.visits_count, user.visits)

  return {
    name: user.username || user.name || 'User',
    username: user.username || user.name,
    email: user.email || '',
    personalCode: user.personal_code || '',
    avatar: avatarUrl,
    pp: authStore.points,
    wallet: toNumber(user.wallet),
    level,
    xp: firstNumber(user.xp),
    currentXp: firstNumber(user.current_xp),
    xpForNextLevel: firstNumber(user.xp_for_next_level),
    levelProgress: firstNumber(user.level_progress),
    posts: formatCompactCount(postsCount),
    friends: formatCompactCount(friendsCount),
    visits: formatCompactCount(visitsCount),
    is_plearnd_admin: user.is_plearnd_admin || false,
    is_super_admin: user.is_super_admin || false,
  }
})

const xpProgressPercent = computed(() => {
  const p = authUser.value.levelProgress
  if (p > 0) return Math.min(100, p)
  const storeProgress = gamificationStore.levelProgress
  if (storeProgress > 0) return Math.min(100, storeProgress)
  const cur = authUser.value.currentXp
  const max = authUser.value.xpForNextLevel
  if (max > 0) return Math.min(100, Math.round((cur / max) * 100))
  return 0
})


// Navigation
const navigation = [
  { name: 'กระดานข่าว', href: '/play/newsfeed', icon: 'fluent:feed-24-regular' },
  { name: 'โรงเรียน', href: '/academies', icon: 'mdi:school-outline' },
  { name: 'รายวิชา', href: '/Learn/Courses', icon: 'fluent-mdl2:publish-course' },
  { name: 'สะสมแต้ม', href: '/earn/donates', icon: 'mdi:hand-coin-outline' },
  { name: 'ดูสินค้า', href: '/earn/advertise', icon: 'eos-icons:product-subscriptions-outlined' },
]

// Toggle functions
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

const handleLogoutFromSettings = async () => {
  closeSettings()
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

  fetchLeaderboard()
  gamificationStore.fetchProgress()

  // Reset login transition flag once layout is mounted and data is ready
  if (authStore.isLoginTransitioning) {
    await nextTick()
    authStore.isLoginTransitioning = false
  }
})

// Cleanup
onBeforeUnmount(() => {
  // Logic handled by useResponsiveSidebar
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

// Layout widget signals (set by pages that use Teleport to fill widget columns)
const layoutWidgets = useLayoutWidgets()
const slots = useSlots()

// Detect both Teleport-based flags and traditional NuxtLayout slots
const hasLeft = computed(() => layoutWidgets.value.hasLeftWidgets || !!slots.leftWidgets)
const hasRight = computed(() => layoutWidgets.value.hasRightWidgets || !!slots.rightWidgets)

// Dynamic grid classes for center content
const centerGridClass = computed(() => {
  // Case: Both exist
  if (hasLeft.value && hasRight.value) {
    // lg: left(3) + center(9). Right is slide-out.
    // xl: left(3) + center(6) + right(3).
    return 'lg:col-span-9 xl:col-span-6'
  }

  // Case: Only Left exists
  if (hasLeft.value) {
    // lg+: left(3) + center(9).
    return 'lg:col-span-9'
  }

  // Case: Only Right exists
  if (hasRight.value) {
    // lg: center(12). Right is slide-out.
    // xl: center(9) + right(3).
    return 'lg:col-span-12 xl:col-span-9'
  }

  // Case: No widgets
  return 'lg:col-span-12'
})

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
    class="min-h-screen transition-colors duration-300 overflow-x-hidden"
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
            <img src="/images/plearnd-logo.png" alt="Plearnd Logo" class="w-10 h-10" />
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
            <span class="font-bold text-sm whitespace-nowrap" :class="isDarkMode ? 'text-amber-400' : 'text-amber-600'">
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
            <span class="font-bold text-sm whitespace-nowrap" :class="isDarkMode ? 'text-emerald-400' : 'text-emerald-600'">
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
                :src="authUser.avatar"
                :alt="authUser.name"
                class="w-full h-full object-cover"
                @error="(e) => e.target.src = '/images/default-avatar.png'"
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
                  to="/nuxnan-admin/supports"
                  @click="closeSettings"
                  class="flex items-center gap-3 px-4 py-3 transition-colors"
                  :class="isDarkMode ? 'hover:bg-vikinger-dark-200 text-gray-300' : 'hover:bg-gray-100 text-gray-700'"
                >
                  <Icon icon="fluent:heart-handshake-24-regular" class="w-5 h-5 text-indigo-500" />
                  <span>จัดการการสนับสนุน</span>
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
                <!-- Super Admin only - Nuxnan Admin Dashboard -->
                <NuxtLink
                  v-if="authUser.is_super_admin"
                  to="/nuxnan-admin"
                  target="_blank"
                  rel="noopener noreferrer"
                  @click="closeSettings"
                  class="flex items-center gap-3 px-4 py-3 transition-colors"
                  :class="isDarkMode ? 'hover:bg-vikinger-dark-200 text-indigo-400' : 'hover:bg-indigo-50 text-indigo-600'"
                >
                  <Icon icon="fluent:shield-person-24-regular" class="w-5 h-5 text-indigo-500" />
                  <span>Nuxnan Admin</span>
                  <Icon icon="fluent:open-24-regular" class="w-3.5 h-3.5 ml-auto opacity-50" />
                </NuxtLink>
                
                <div class="border-t my-1" :class="isDarkMode ? 'border-vikinger-dark-50/30' : 'border-gray-200'"></div>
                <button
                  @click="handleLogoutFromSettings"
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
            v-if="enableRightSidebar"
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
            <div class="relative w-28 h-28 flex items-center justify-center mx-auto mb-4">
              <!-- XP Progress Ring -->
              <svg class="absolute inset-0 w-28 h-28 -rotate-90" viewBox="0 0 112 112">
                <defs>
                  <linearGradient id="xp-ring-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#8b5cf6"/>
                    <stop offset="100%" stop-color="#06b6d4"/>
                  </linearGradient>
                </defs>
                <circle cx="56" cy="56" r="50" fill="none" stroke-width="4" stroke="rgba(139,92,246,0.18)"/>
                <circle
                  cx="56" cy="56" r="50" fill="none" stroke-width="4"
                  stroke="url(#xp-ring-gradient)"
                  stroke-linecap="round"
                  :style="{
                    strokeDasharray: 314.16,
                    strokeDashoffset: 314.16 * (1 - xpProgressPercent / 100),
                    transition: 'stroke-dashoffset 0.8s ease'
                  }"
                />
              </svg>
              <!-- Avatar -->
              <img
                :src="authUser.avatar"
                class="w-24 h-24 rounded-full object-cover shadow-lg"
                :alt="authUser.name"
                @error="(e) => e.target.src = '/images/default-avatar.png'"
              />
              <!-- Level badge -->
              <div
                class="absolute -bottom-2 -right-2 w-10 h-10 bg-gradient-vikinger rounded-full flex items-center justify-center text-white font-bold border-4 transition-colors duration-300 z-10"
                :class="isDarkMode ? 'border-vikinger-dark-100' : 'border-white'"
              >
                {{ authUser.level }}
              </div>
            </div>
            <h3 class="text-xl font-bold" :class="isDarkMode ? 'text-white' : 'text-gray-900'">
              {{ authUser.name }}
            </h3>
            <p class="text-xs font-bold text-vikinger-cyan mb-1">
              @{{ authUser.username }}
            </p>
            <p class="text-sm" :class="isDarkMode ? 'text-gray-400' : 'text-gray-600'">
              {{ authUser.email }}
            </p>

            <!-- XP Progress Bar -->
            <div class="mt-2 mb-1 px-2">
              <div class="flex justify-between text-xs mb-1">
                <span class="font-semibold" :class="isDarkMode ? 'text-vikinger-cyan' : 'text-vikinger-purple'">
                  Lv.{{ authUser.level }}
                </span>
                <span :class="isDarkMode ? 'text-gray-400' : 'text-gray-500'">{{ xpProgressPercent }}%</span>
              </div>
              <div class="w-full h-1.5 rounded-full overflow-hidden" :class="isDarkMode ? 'bg-gray-700' : 'bg-gray-200'">
                <div
                  class="h-full rounded-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan transition-all duration-700"
                  :style="{ width: `${xpProgressPercent}%` }"
                />
              </div>
              <div v-if="authUser.xpForNextLevel > 0" class="text-[10px] mt-0.5 text-center" :class="isDarkMode ? 'text-gray-500' : 'text-gray-400'">
                {{ authUser.currentXp }} / {{ authUser.xpForNextLevel }} XP
              </div>
            </div>

            <!-- รหัสส่วนตัว -->
            <PersonalCodeCard :code="authUser.personalCode" :is-dark-mode="isDarkMode" />
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
            <!-- Games Menu with Submenu -->
            <div>
              <button
                @click="toggleGamesMenu"
                class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-lg transition-all duration-300"
                :class="
                  route.path.startsWith('/play/games')
                    ? 'bg-gradient-vikinger text-white shadow-vikinger'
                    : isDarkMode
                    ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                    : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
                "
              >
                <div class="flex items-center gap-3">
                  <Icon icon="fluent:games-24-regular" class="w-5 h-5" />
                  <span class="font-semibold">เกมส์</span>
                </div>
                <Icon 
                  :icon="isGamesMenuOpen ? 'fluent:chevron-up-24-regular' : 'fluent:chevron-down-24-regular'" 
                  class="w-4 h-4 transition-transform duration-200"
                />
              </button>
              <!-- Submenu -->
              <transition name="expand">
                <div v-if="isGamesMenuOpen" class="ml-4 mt-1 space-y-1 border-l-2 border-vikinger-purple/30 pl-3">
                  <NuxtLink
                    v-for="sub in gamesSubmenu"
                    :key="sub.href"
                    :to="sub.href"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-sm"
                    :class="
                      route.path === sub.href || (sub.href !== '/play/games' && route.path.startsWith(sub.href))
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
            
            <!-- Logout Button -->
            <button
              @click="logout"
              class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-300 mt-4 border-t pt-4"
              :class="
                isDarkMode
                  ? 'text-red-400 hover:bg-red-900/20 hover:text-red-300 border-vikinger-dark-50/30'
                  : 'text-red-500 hover:bg-red-50 hover:text-red-600 border-gray-200'
              "
            >
              <Icon icon="fluent:sign-out-24-regular" class="w-5 h-5" />
              <span class="font-semibold">ออกจากระบบ</span>
            </button>
          </div>
        </div>

        <!-- Collapsed Content -->
        <div v-else class="p-3 space-y-2 flex flex-col items-center">
          <!-- Profile Avatar (Collapsed) -->
          <NuxtLink to="/profile" class="mb-2 flex flex-col items-center"
            :title="`${authUser.name} • Lv.${authUser.level} • ${xpProgressPercent}% XP${authUser.personalCode ? ' • ' + authUser.personalCode : ''}`">
            <div class="relative w-14 h-14 flex items-center justify-center">
              <svg class="absolute inset-0 w-14 h-14 -rotate-90" viewBox="0 0 56 56">
                <defs>
                  <linearGradient id="xp-ring-gradient-sm" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#8b5cf6"/>
                    <stop offset="100%" stop-color="#06b6d4"/>
                  </linearGradient>
                </defs>
                <circle cx="28" cy="28" r="25" fill="none" stroke-width="3" stroke="rgba(139,92,246,0.18)"/>
                <circle
                  cx="28" cy="28" r="25" fill="none" stroke-width="3"
                  stroke="url(#xp-ring-gradient-sm)"
                  stroke-linecap="round"
                  :style="{
                    strokeDasharray: 157.08,
                    strokeDashoffset: 157.08 * (1 - xpProgressPercent / 100),
                    transition: 'stroke-dashoffset 0.8s ease'
                  }"
                />
              </svg>
              <img
                :src="authUser.avatar"
                class="w-12 h-12 rounded-full object-cover shadow-lg"
                :alt="authUser.name"
                @error="(e) => e.target.src = '/images/default-avatar.png'"
              />
            </div>
            <span 
              class="mt-1 text-xs font-medium truncate max-w-[60px] text-center"
              :class="isDarkMode ? 'text-gray-300' : 'text-gray-700'"
            >{{ authUser.name }}</span>
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
            to="/play/games"
            class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
            :class="
              route.path.startsWith('/play/games')
                ? 'bg-gradient-vikinger text-white shadow-vikinger'
                : isDarkMode
                ? 'text-gray-300 hover:bg-vikinger-purple/10 hover:text-vikinger-cyan'
                : 'text-gray-700 hover:bg-gray-100 hover:text-vikinger-purple'
            "
            :title="'เกมส์'"
          >
            <Icon icon="fluent:games-24-regular" class="w-6 h-6" />
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
          
          <!-- Logout Button (Collapsed) -->
          <div 
            class="w-full mt-4 pt-4 flex justify-center"
            :class="isDarkMode ? 'border-t border-vikinger-dark-50/30' : 'border-t border-gray-200'"
          >
            <button
              @click="logout"
              class="w-12 h-12 flex items-center justify-center rounded-lg transition-all duration-300"
              :class="
                isDarkMode
                  ? 'text-red-400 hover:bg-red-900/20 hover:text-red-300'
                  : 'text-red-500 hover:bg-red-50 hover:text-red-600'
              "
              title="ออกจากระบบ"
            >
              <Icon icon="fluent:sign-out-24-regular" class="w-6 h-6" />
            </button>
          </div>
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
          'flex-1 min-w-0 min-h-screen transition-all duration-300 overflow-x-hidden',
          isLeftDrawerOpen ? 'lg:pl-80' : 'lg:pl-20',
          hasRight ? 'lg:pr-14 xl:pr-0' : '',
          enableRightSidebar && isRightDrawerOpen ? 'lg:pr-80' : 'lg:pr-20',
        ]"
      >
        <!-- Hero: with responsive margins matching grid padding -->
        <div id="hero-slot" :class="['w-full min-w-0 mx-auto max-w-[1440px] 2xl:max-w-[1600px] py-4 empty:hidden px-4 sm:px-6', isLeftDrawerOpen ? 'lg:px-8' : 'lg:px-4']">
          <slot name="hero" />
        </div>

        <!-- Tabs: sticky below header, matching grid padding -->
        <div id="tabs-slot" :class="['w-full min-w-0 mx-auto max-w-[1440px] 2xl:max-w-[1600px] sticky top-16 z-30 empty:hidden px-4 sm:px-6', isLeftDrawerOpen ? 'lg:px-8' : 'lg:px-4']">
          <slot name="tabs" />
        </div>

        <div :class="['mx-auto py-6 pb-24 lg:pb-8 w-full min-w-0 max-w-[1440px] 2xl:max-w-[1600px] px-4 sm:px-6', isLeftDrawerOpen ? 'lg:px-8' : 'lg:px-4']">
          <!-- 12 Column Grid Layout -->
          <div class="grid min-w-0 grid-cols-1 lg:grid-cols-12 xl:grid-cols-12 gap-6">
            <!-- Left Widgets (3/12) - visible lg+, slide-out on mobile -->
            <div
              v-show="hasLeft"
              :class="[
                'min-w-0 lg:col-span-3 transition-all duration-300',
                'fixed top-16 bottom-0 left-0 w-80 max-w-[85vw] z-40 p-6 bg-white dark:bg-vikinger-dark-100 shadow-xl overflow-y-auto transform',
                'lg:relative lg:top-auto lg:bottom-auto lg:left-auto lg:w-auto lg:max-w-none lg:z-0 lg:p-0 lg:bg-transparent lg:dark:bg-transparent lg:shadow-none lg:overflow-visible lg:translate-x-0',
                layoutWidgets.isLeftPanelOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
              ]"
            >
              <!-- Close button for mobile -->
              <div class="lg:hidden flex justify-end mb-4">
                <button @click="layoutWidgets.isLeftPanelOpen = false" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-vikinger-dark-200">
                  <Icon icon="mdi:close" class="w-6 h-6 text-gray-500" />
                </button>
              </div>

              <!-- Inner target for widgets (Teleport Target) -->
              <div id="left-widgets-slot" class="flex min-w-0 flex-col gap-6">
                <slot name="leftWidgets" />
              </div>
            </div>

            <!-- Center Content (dynamic span based on widgets + breakpoint) -->
            <div :class="['w-full min-w-0 transition-all duration-300', centerGridClass]">
              <div class="flex min-w-0 flex-col gap-6">
                <slot />
              </div>
            </div>

            <!-- Right Widgets (3/12) - visible xl+, slide-out on tablet/mobile -->
            <div
              v-show="hasRight"
              :class="[
                'min-w-0 xl:col-span-3 transition-all duration-300',
                'fixed top-16 bottom-0 right-0 w-80 max-w-[85vw] z-40 p-6 bg-white dark:bg-vikinger-dark-100 shadow-xl overflow-y-auto transform',
                'xl:relative xl:top-auto xl:bottom-auto xl:right-auto xl:w-auto xl:max-w-none xl:z-0 xl:p-0 xl:bg-transparent xl:dark:bg-transparent xl:shadow-none xl:overflow-visible xl:translate-x-0',
                layoutWidgets.isRightPanelOpen ? 'translate-x-0' : 'translate-x-full xl:translate-x-0'
              ]"
            >
              <!-- Close button for mobile/tablet -->
              <div class="xl:hidden flex justify-end mb-4">
                <button @click="layoutWidgets.isRightPanelOpen = false" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-vikinger-dark-200">
                  <Icon icon="mdi:close" class="w-6 h-6 text-gray-500" />
                </button>
              </div>

              <!-- Inner target for widgets (Teleport Target) -->
              <div id="right-widgets-slot" class="flex min-w-0 flex-col gap-6">
                <slot name="rightWidgets" />
              </div>
            </div>
          </div>
        </div>
      </main>

      <!-- ========================================
                 RIGHT DRAWER (Chat + Activity)
      ======================================== -->
      <aside
        v-if="enableRightSidebar"
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
                <img :src="getAvatarUrl(user, index)" class="w-10 h-10 rounded-full border-2 border-transparent group-hover:border-vikinger-purple transition-colors bg-white object-cover" @error="(e) => e.target.src = '/images/default-avatar.png'" />
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
                    <img src="~/assets/images/badge/completedq-s.png" class="w-3.5 h-3.5" alt="pp" />
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
            <img :src="getAvatarUrl(user, index)" class="w-11 h-11 rounded-full border-2 border-transparent group-hover:border-vikinger-purple transition-colors bg-white object-cover" @error="(e) => e.target.src = '/images/default-avatar.png'" />
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
            <div class="relative w-24 h-24 flex items-center justify-center mx-auto mb-3">
              <!-- XP Progress Ring -->
              <svg class="absolute inset-0 w-24 h-24 -rotate-90" viewBox="0 0 96 96">
                <defs>
                  <linearGradient id="xp-ring-gradient-mobile" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#8b5cf6"/>
                    <stop offset="100%" stop-color="#06b6d4"/>
                  </linearGradient>
                </defs>
                <circle cx="48" cy="48" r="44" fill="none" stroke-width="3" stroke="rgba(139,92,246,0.18)"/>
                <circle
                  cx="48" cy="48" r="44" fill="none" stroke-width="3"
                  stroke="url(#xp-ring-gradient-mobile)"
                  stroke-linecap="round"
                  :style="{
                    strokeDasharray: 276.46,
                    strokeDashoffset: 276.46 * (1 - xpProgressPercent / 100),
                    transition: 'stroke-dashoffset 0.8s ease'
                  }"
                />
              </svg>
              <!-- Avatar -->
              <img
                :src="authUser.avatar"
                class="w-20 h-20 rounded-full object-cover shadow-lg"
                :alt="authUser.name"
                @error="(e) => e.target.src = '/images/default-avatar.png'"
              />
              <!-- Level badge -->
              <div
                class="absolute -bottom-1 -right-1 w-8 h-8 bg-gradient-vikinger rounded-full flex items-center justify-center text-white text-xs font-bold border-2 transition-colors duration-300 z-10"
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

            <!-- XP Progress Bar -->
            <div class="mt-2 mb-1 px-4">
              <div class="flex justify-between text-[10px] mb-1">
                <span class="font-semibold" :class="isDarkMode ? 'text-vikinger-cyan' : 'text-vikinger-purple'">
                  Lv.{{ authUser.level }}
                </span>
                <span :class="isDarkMode ? 'text-gray-400' : 'text-gray-500'">{{ xpProgressPercent }}%</span>
              </div>
              <div class="w-full h-1 rounded-full overflow-hidden" :class="isDarkMode ? 'bg-gray-700' : 'bg-gray-200'">
                <div
                  class="h-full rounded-full bg-gradient-to-r from-vikinger-purple to-vikinger-cyan transition-all duration-700"
                  :style="{ width: `${xpProgressPercent}%` }"
                />
              </div>
            </div>

            <!-- รหัสส่วนตัว -->
            <PersonalCodeCard :code="authUser.personalCode" :is-dark-mode="isDarkMode" />
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
            <!-- Games Menu with Submenu (Mobile) -->
            <div>
              <button
                @click="toggleGamesMenu"
                class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-lg transition-colors"
                :class="
                  route.path.startsWith('/play/games')
                    ? 'bg-gradient-vikinger text-white shadow-vikinger'
                    : isDarkMode
                    ? 'text-gray-300 hover:bg-vikinger-purple/10'
                    : 'text-gray-700 hover:bg-gray-100'
                "
              >
                <div class="flex items-center gap-3">
                  <Icon icon="fluent:games-24-regular" class="w-5 h-5" />
                  <span class="font-semibold">เกมส์</span>
                </div>
                <Icon 
                  :icon="isGamesMenuOpen ? 'fluent:chevron-up-24-regular' : 'fluent:chevron-down-24-regular'" 
                  class="w-4 h-4"
                />
              </button>
              <!-- Submenu -->
              <transition name="expand">
                <div v-if="isGamesMenuOpen" class="ml-4 mt-1 space-y-1 border-l-2 border-vikinger-purple/30 pl-3">
                  <NuxtLink
                    v-for="sub in gamesSubmenu"
                    :key="sub.href"
                    :to="sub.href"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors text-sm"
                    :class="
                      route.path === sub.href || (sub.href !== '/play/games' && route.path.startsWith(sub.href))
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

    <!-- Slide-out Panel Backdrop -->
    <Transition name="fade">
      <div
        v-if="(layoutWidgets.isLeftPanelOpen && !isDesktop) || (layoutWidgets.isRightPanelOpen && !isWideDesktop)"
        class="fixed inset-0 top-16 bg-black/50 z-[35]"
        @click="layoutWidgets.isLeftPanelOpen = false; layoutWidgets.isRightPanelOpen = false"
      ></div>
    </Transition>

    <!-- Left Panel Toggle (visible < lg when left widgets exist) -->
    <div
      class="fixed left-0 top-16 z-30 transition-all duration-300"
      :class="hasLeft && !layoutWidgets.isLeftPanelOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <button
        @click="layoutWidgets.isLeftPanelOpen = true"
        class="lg:hidden bg-gradient-vikinger text-white p-3 rounded-r-2xl shadow-vikinger hover:scale-110 active:scale-95 transition-transform"
        title="เปิดแถบเครื่องมือด้านซ้าย"
      >
        <Icon icon="fluent:panel-left-24-filled" class="w-5 h-5" />
      </button>
    </div>

    <!-- Right Panel Toggle (visible < xl when right widgets exist) -->
    <div
      class="fixed right-0 top-16 z-30 transition-all duration-300"
      :class="hasRight && !layoutWidgets.isRightPanelOpen ? 'translate-x-0' : 'translate-x-full'"
    >
      <button
        @click="layoutWidgets.isRightPanelOpen = true"
        class="xl:hidden bg-gradient-vikinger text-white p-3 rounded-l-2xl shadow-vikinger hover:scale-110 active:scale-95 transition-transform"
        title="เปิดแถบเครื่องมือด้านขวา"
      >
        <Icon icon="fluent:panel-right-24-filled" class="w-5 h-5" />
      </button>
    </div>

    <!-- Universal QR Scanner Modal -->
    <QrUniversalQRModal
      v-model="isQRScannerOpen"
      @action-complete="onQRActionComplete"
    />

    <!-- Bottom Mobile Navigation -->
    <LayoutBottomNav />
  </div>
</template>

<style scoped>
/* Fade transition for backdrop */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

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

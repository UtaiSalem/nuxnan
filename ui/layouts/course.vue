<script setup lang="ts">
import { ref, computed, onMounted, provide } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import { useUIStore } from '~/stores/ui'
import { useResponsiveSidebar } from '~/composables/useResponsiveSidebar'
import LayoutBottomNav from '~/components/layout/BottomNav.vue'

const route = useRoute()
const authStore = useAuthStore()
const uiStore = useUIStore()

// Theme state
const isDarkMode = ref(false)
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

provide('isDarkMode', isDarkMode)
provide('toggleTheme', toggleTheme)

// Mobile Sidebar state (for course-specific drawer if needed)
const isMobileSidebarOpen = ref(false)
const toggleMobileSidebar = () => {
  isMobileSidebarOpen.value = !isMobileSidebarOpen.value
}

// User data
const authUser = computed(() => {
  const user = authStore.user
  if (!user) {
    return {
      name: 'Guest',
      avatar: '/images/default-avatar.png',
      pp: 0,
      wallet: 0,
    }
  }
  return {
    name: user.username || user.name || 'User',
    avatar: user.avatar || user.profile_photo_url || '/images/default-avatar.png',
    pp: authStore.points,
    wallet: Number(user.wallet) || 0,
  }
})

const formatNumber = (num: number) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K'
  return num.toLocaleString()
}

onMounted(() => {
  const savedTheme = localStorage.getItem('theme')
  if (savedTheme === 'dark') {
    isDarkMode.value = true
    document.documentElement.classList.add('dark')
  } else {
    isDarkMode.value = false
    document.documentElement.classList.add('light')
  }
})

// Navigation
const navigation = [
  { name: 'กระดานข่าว', href: '/play/newsfeed', icon: 'fluent:feed-24-regular' },
  { name: 'โรงเรียน', href: '/academies', icon: 'mdi:school-outline' },
  { name: 'รายวิชา', href: '/Learn/Courses', icon: 'fluent-mdl2:publish-course' },
  { name: 'สะสมแต้ม', href: '/earn/donates', icon: 'mdi:hand-coin-outline' },
  { name: 'ดูสินค้า', href: '/earn/advertise', icon: 'eos-icons:product-subscriptions-outlined' },
]

const isSettingsOpen = ref(false)
const toggleSettings = () => isSettingsOpen.value = !isSettingsOpen.value
const closeSettings = () => isSettingsOpen.value = false

const settingsUrl = computed(() => {
  if (authStore.user?.reference_code) {
    return `/profile/${authStore.user.reference_code}/settings`
  }
  return '/settings'
})

const logout = async () => {
  await authStore.logout()
}
</script>

<template>
  <div
    class="min-h-screen transition-colors duration-300 overflow-x-hidden"
    :class="isDarkMode ? 'bg-vikinger-dark dark' : 'bg-gray-200 light'"
  >
    <!-- HEADER -->
    <header
      class="fixed top-0 left-0 right-0 h-16 z-50 shadow-lg transition-colors duration-300"
      :class="isDarkMode ? 'bg-vikinger-dark-100 border-b border-vikinger-dark-50/30' : 'bg-white border-b border-gray-200'"
    >
      <div class="h-full px-4 flex items-center justify-between gap-4">
        <!-- Left: Logo -->
        <div class="flex items-center gap-3">
          <!-- Mobile Menu Toggle (only for course sidebar on mobile) -->
          <button
            @click="toggleMobileSidebar"
            class="lg:hidden flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-vikinger-purple to-vikinger-cyan shadow-md hover:shadow-lg transition-all"
          >
            <Icon icon="fluent:line-horizontal-3-24-regular" class="w-6 h-6 text-white" />
          </button>

          <NuxtLink to="/" class="flex items-center gap-3">
            <img src="/images/plearnd-logo.png" alt="Plearnd Logo" class="w-10 h-10" />
            <span class="hidden md:inline-block px-3 py-1 text-lg font-audiowide text-white rounded-lg bg-gradient-vikinger shadow-lg">NUXNAN</span>
          </NuxtLink>
        </div>

        <!-- Center: Nav -->
        <div class="hidden md:flex items-center gap-2">
          <NuxtLink
            v-for="item in navigation"
            :key="item.href"
            :to="item.href"
            class="flex items-center gap-2 px-4 py-2 rounded-lg transition-all"
            :class="route.path.startsWith(item.href) ? 'bg-gradient-vikinger text-white shadow-vikinger' : (isDarkMode ? 'text-gray-300 hover:bg-vikinger-purple/10' : 'text-gray-700 hover:bg-gray-100')"
          >
            <Icon :icon="item.icon" class="w-5 h-5" />
            <span class="hidden xl:inline">{{ item.name }}</span>
          </NuxtLink>
        </div>

        <!-- Right: Auth/Stats -->
        <div class="flex items-center gap-2">
          <NuxtLink to="/earn/points" class="hidden sm:flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border" :class="isDarkMode ? 'bg-amber-900/40 border-amber-500/30 text-amber-400' : 'bg-amber-50 border-amber-200 text-amber-600'">
            <Icon icon="fluent:star-24-filled" class="w-4 h-4" />
            <span class="font-bold text-sm">{{ formatNumber(authUser.pp) }}</span>
          </NuxtLink>
          <NuxtLink to="/earn/wallet" class="hidden sm:flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border" :class="isDarkMode ? 'bg-emerald-900/40 border-emerald-500/30 text-emerald-400' : 'bg-emerald-50 border-emerald-200 text-emerald-600'">
            <Icon icon="fluent:money-24-filled" class="w-4 h-4" />
            <span class="font-bold text-sm">฿{{ formatNumber(authUser.wallet) }}</span>
          </NuxtLink>

          <NuxtLink to="/profile" class="w-10 h-10 rounded-full overflow-hidden border-2 border-vikinger-cyan">
            <img :src="authUser.avatar" class="w-full h-full object-cover" />
          </NuxtLink>

          <div class="relative">
            <button @click="toggleSettings" class="w-10 h-10 rounded-lg flex items-center justify-center transition-all" :class="isDarkMode ? 'bg-vikinger-dark-200 text-gray-300' : 'bg-gray-100 text-gray-700'">
              <Icon icon="fluent:settings-24-regular" class="w-5 h-5" />
            </button>
            <div v-if="isSettingsOpen" class="absolute right-0 top-12 w-56 rounded-xl shadow-xl border overflow-hidden z-50" :class="isDarkMode ? 'bg-vikinger-dark-100 border-vikinger-dark-50/30 text-gray-300' : 'bg-white border-gray-200 text-gray-700'">
              <NuxtLink to="/profile" @click="closeSettings" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200">
                <Icon icon="fluent:person-24-regular" class="w-5 h-5" />
                <span>โปรไฟล์</span>
              </NuxtLink>
              <NuxtLink :to="settingsUrl" @click="closeSettings" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200">
                <Icon icon="fluent:settings-24-regular" class="w-5 h-5" />
                <span>ตั้งค่า</span>
              </NuxtLink>
              <div class="border-t dark:border-vikinger-dark-50/30 my-1"></div>
              <button @click="logout" class="w-full flex items-center gap-3 px-4 py-3 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
                <Icon icon="fluent:sign-out-24-regular" class="w-5 h-5" />
                <span>ออกจากระบบ</span>
              </button>
            </div>
            <div v-if="isSettingsOpen" class="fixed inset-0 z-40" @click="closeSettings"></div>
          </div>
        </div>
      </div>
    </header>

    <!-- CONTENT AREA -->
    <main class="pt-16 min-h-screen">
      <!-- Full Width Hero Slot -->
      <section id="hero-slot" class="w-full">
        <slot name="hero" />
      </section>

      <!-- Sticky TabBar Slot -->
      <nav id="tabs-slot" class="sticky top-16 z-30 shadow-md" :class="isDarkMode ? 'bg-vikinger-dark-100 border-b border-vikinger-dark-50/30' : 'bg-white border-b border-gray-200'">
        <div class="max-w-screen-2xl mx-auto">
          <slot name="tabs" />
        </div>
      </nav>

      <!-- 3-Column Body -->
      <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">
          <!-- Left Sidebar (3/12) -->
          <aside class="lg:col-span-3">
            <!-- Mobile Drawer Backdrop -->
            <div v-if="isMobileSidebarOpen" class="fixed inset-0 bg-black/50 z-40 lg:hidden" @click="toggleMobileSidebar"></div>

            <div
              class="lg:block lg:sticky lg:top-36"
              :class="[
                isMobileSidebarOpen
                  ? 'fixed left-0 top-0 h-screen w-72 z-50 p-6 overflow-y-auto transform translate-x-0 transition-transform duration-300'
                  : 'fixed left-0 top-0 h-screen w-72 z-50 p-6 transform -translate-x-full transition-transform duration-300 lg:relative lg:h-auto lg:w-full lg:p-0 lg:transform-none'
              , isDarkMode ? 'bg-vikinger-dark-100' : 'bg-white lg:bg-transparent']"
            >
              <div class="lg:hidden flex justify-between items-center mb-6">
                <span class="font-bold text-lg" :class="isDarkMode ? 'text-white' : 'text-gray-900'">เมนูรายวิชา</span>
                <button @click="toggleMobileSidebar"><Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" /></button>
              </div>

              <div id="left-widgets-slot" class="flex flex-col gap-6">
                <slot name="sidebar" />
              </div>
            </div>
          </aside>

          <!-- Center Content (6/12) -->
          <div class="lg:col-span-6 min-w-0">
            <slot />
          </div>

          <!-- Right Sidebar (3/12) -->
          <aside class="hidden lg:block lg:col-span-3">
            <div class="lg:sticky lg:top-36">
              <div id="right-widgets-slot" class="flex flex-col gap-6">
                <slot name="rightSidebar" />
              </div>
            </div>
          </aside>
        </div>
      </div>
    </main>

    <!-- Bottom Nav for Mobile -->
    <div class="lg:hidden">
      <LayoutBottomNav />
    </div>
  </div>
</template>

<style scoped>
.font-audiowide {
  font-family: 'Audiowide', cursive;
}
.bg-gradient-vikinger {
  background: linear-gradient(to right, #615dfa, #23d2e2);
}
</style>

<template>
  <header class="fixed top-0 left-0 right-0 bg-slate-700 dark:bg-slate-800 text-white shadow-lg z-50 transition-colors duration-300">
    <div class="max-w-full px-4 py-3">
      <div class="flex items-center justify-between">
        
        <!-- Left Section - Logo & Search -->
        <div class="flex items-center gap-4 flex-1">
          <!-- Logo -->
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-orange-400 via-pink-400 to-cyan-400 rounded-lg flex items-center justify-center">
              <div class="w-6 h-6 bg-white rounded opacity-30"></div>
            </div>
            <span class="text-xl font-bold">pitnik</span>
          </div>
          
          <!-- Search Bar -->
          <div class="relative max-w-md w-full">
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="$t('common.search')"
              class="w-full bg-slate-600 text-white placeholder-slate-400 rounded-full py-2 px-4 pr-10 focus:outline-none focus:ring-2 focus:ring-slate-500"
              @keyup.enter="handleSearch"
            />
            <svg 
              class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.35-4.35"></path>
            </svg>
          </div>
        </div>

        <!-- Center Section - Newsfeed -->
        <div class="flex-1 flex justify-center">
          <button 
            @click="goToNewsfeed"
            class="text-white font-bold text-sm tracking-wider hover:text-slate-300 transition"
          >
            {{ $t('nav.newsfeed').toUpperCase() }}
          </button>
        </div>

        <!-- Right Section - Icons & Profile -->
        <div class="flex items-center gap-3 flex-1 justify-end">
          <!-- Home Icon -->
          <button 
            @click="goToHome"
            class="p-2 hover:bg-slate-600 rounded-lg transition relative"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
          </button>
          
          <!-- Friends Icon with Badge -->
          <button 
            @click="goToFriends"
            class="p-2 hover:bg-slate-600 rounded-lg transition relative"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <span 
              v-if="friendRequests > 0"
              class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"
            >
              {{ friendRequests }}
            </span>
          </button>
          
          <!-- Messages Icon -->
          <button 
            @click="goToMessages"
            class="p-2 hover:bg-slate-600 rounded-lg transition relative"
          >
            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
          </button>
          
          <!-- Notifications Icon with Badge -->
          <button 
            @click="goToNotifications"
            class="p-2 hover:bg-slate-600 rounded-lg transition relative"
          >
            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span 
              v-if="notifications >= 0"
              class="absolute -top-1 -right-1 bg-cyan-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"
            >
              {{ notifications }}
            </span>
          </button>
          
          <!-- Theme Toggle -->
          <button 
            @click="uiStore.toggleTheme()"
            class="p-2 hover:bg-slate-600 rounded-lg transition relative group"
            :title="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
          >
            <!-- Sun Icon (Light Mode) -->
            <svg 
              v-if="!isDarkMode"
              class="w-5 h-5 text-yellow-300 transition-transform duration-300 group-hover:rotate-180" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <circle cx="12" cy="12" r="5"></circle>
              <line x1="12" y1="1" x2="12" y2="3"></line>
              <line x1="12" y1="21" x2="12" y2="23"></line>
              <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
              <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
              <line x1="1" y1="12" x2="3" y2="12"></line>
              <line x1="21" y1="12" x2="23" y2="12"></line>
              <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
              <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
            <!-- Moon Icon (Dark Mode) -->
            <svg 
              v-else
              class="w-5 h-5 text-indigo-300 transition-transform duration-300 group-hover:-rotate-12" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
          </button>
          
          <!-- Settings Icon -->
          <button 
            @click="toggleSettings"
            class="p-2 hover:bg-slate-600 rounded-lg transition"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M12 1v6m0 6v6m5.66-13l-3 5.2M9.34 17.8l-3 5.2m11.32-15.4-5.2 3M6.8 14.66l-5.2 3m15.4-11.32-5.2 3M14.66 17.8l3 5.2"></path>
            </svg>
          </button>
          
          <!-- Help Icon -->
          <button 
            @click="openHelp"
            class="p-2 hover:bg-slate-600 rounded-lg transition"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10"></circle>
              <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
          </button>

          <!-- Language Switcher -->
          <LanguageSwitcher />

          <!-- Divider -->
          <div class="h-8 w-px bg-slate-600"></div>

          <!-- QR Code Button -->
          <button 
            @click="openQRCode"
            class="p-2 hover:bg-slate-600 rounded-lg transition"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <rect x="3" y="3" width="8" height="8" rx="1"></rect>
              <rect x="13" y="3" width="8" height="8" rx="1"></rect>
              <rect x="3" y="13" width="8" height="8" rx="1"></rect>
              <rect x="13" y="13" width="8" height="8" rx="1"></rect>
            </svg>
          </button>

          <!-- Profile -->
          <button 
            @click="openProfile"
            class="flex items-center gap-2 hover:bg-slate-600 rounded-full pl-1 pr-3 py-1 transition"
          >
            <div class="w-8 h-8 bg-slate-500 rounded-full flex items-center justify-center overflow-hidden">
              <img 
                :src="userAvatar" 
                :alt="userName"
                class="w-full h-full object-cover"
              />
            </div>
            <span class="text-sm font-medium">{{ userName }}</span>
          </button>

          <!-- Settings Icon (Right) -->
          <div class="relative settings-dropdown-container">
            <button 
              @click="toggleSettings"
              class="p-2 hover:bg-slate-600 rounded-lg transition"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </button>
            <div 
              v-if="isSettingsOpen"
              class="absolute right-0 mt-2 w-48 bg-slate-600 rounded-md shadow-lg py-1 z-50"
            >
              <a href="#" class="block px-4 py-2 text-sm text-white hover:bg-slate-500">{{ $t('common.profile') }} {{ $t('common.settings') }}</a>
              <a href="#" class="block px-4 py-2 text-sm text-white hover:bg-slate-500">{{ $t('nav.privacy') }} {{ $t('common.settings') }}</a>
              <a href="#" class="block px-4 py-2 text-sm text-white hover:bg-slate-500">{{ $t('nav.help') }}</a>
              <button 
                @click="handleLogout"
                class="block w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-slate-500"
              >
                {{ $t('common.logout') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useUIStore } from '~/stores/ui'
import { useRouter } from 'vue-router'

// Stores
const authStore = useAuthStore()
const uiStore = useUIStore()
const router = useRouter()

// Theme
const isDarkMode = computed(() => uiStore.isDarkMode)

// Computed properties for user data from auth store
const { t } = useI18n()
const userName = computed(() => authStore.user?.username || authStore.user?.name || t('user.guest'))
const userAvatar = computed(() => authStore.user?.avatar || 'https://api.dicebear.com/7.x/avataaars/svg?seed=Guest')

// State
const searchQuery = ref('')
const friendRequests = ref(3)
const notifications = ref(0)

const isSettingsOpen = ref(false)

// Methods
const handleSearch = () => {
  // Add your search logic here
}

const goToNewsfeed = () => {
  // Add navigation logic
}

const goToHome = () => {
  // Navigate to home
}

const goToFriends = () => {
  // Navigate to friends
}

const goToMessages = () => {
  // Navigate to messages
}

const goToNotifications = () => {
  // Navigate to notifications
}

const toggleSettings = () => {
  isSettingsOpen.value = !isSettingsOpen.value
}

const handleLogout = () => {
  authStore.logout()
  const token = useCookie('token')
  token.value = null
  router.push('/auth')
  isSettingsOpen.value = false
}

const openHelp = () => {
  // Open help
}

const openQRCode = () => {
  // Open QR code
}

const openProfile = () => {
  // Open profile
}

// Click outside to close dropdown
const closeDropdown = (e) => {
  if (isSettingsOpen.value && !e.target.closest('.settings-dropdown-container')) {
    isSettingsOpen.value = false
  }
}

onMounted(async () => {
  document.addEventListener('click', closeDropdown)
  
  // Fetch user data if authenticated but user data not loaded
  if (authStore.isAuthenticated && !authStore.user) {
    try {
      await authStore.fetchUser()
    } catch (error) {
      console.error('Failed to load user data in navbar:', error)
    }
  }
})

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown)
})
</script>

<style scoped>
/* Optional: Add any additional custom styles here */
</style>
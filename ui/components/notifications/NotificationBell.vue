<script setup lang="ts">
/**
 * NotificationBell Component
 * แสดง icon bell พร้อม dropdown รายการแจ้งเตือน
 */
import { Icon } from '@iconify/vue'

const router = useRouter()
const {
  notifications,
  unreadCount,
  isLoading,
  fetchRecent,
  markAsRead,
  markAllAsRead,
  getColorClass,
  formatRelativeTime,
} = useNotifications()

const isOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

// Fetch on mount
onMounted(() => {
  fetchRecent()
  
  // Poll every 60 seconds
  const interval = setInterval(() => {
    fetchRecent()
  }, 60000)
  
  onUnmounted(() => clearInterval(interval))
})

// Close on click outside
onClickOutside(dropdownRef, () => {
  isOpen.value = false
})

// Handle notification click
const handleClick = async (notification: any) => {
  if (!notification.read_status) {
    await markAsRead(notification.id)
  }
  
  if (notification.action_url) {
    isOpen.value = false
    await router.push(notification.action_url)
  }
}

// Handle mark all as read
const handleMarkAllRead = async () => {
  await markAllAsRead()
}
</script>

<template>
  <div ref="dropdownRef" class="relative">
    <!-- Bell Button -->
    <button
      @click="isOpen = !isOpen"
      class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors"
    >
      <Icon icon="heroicons:bell" class="w-6 h-6" />
      
      <!-- Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 flex items-center justify-center min-w-5 h-5 px-1.5 text-xs font-bold text-white bg-red-500 rounded-full"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-gray-800 rounded-xl shadow-lg ring-1 ring-black/5 dark:ring-white/10 z-50 overflow-hidden"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
          <h3 class="font-semibold text-gray-900 dark:text-white">
            การแจ้งเตือน
            <span v-if="unreadCount > 0" class="ml-2 text-sm font-normal text-gray-500">
              ({{ unreadCount }} ยังไม่อ่าน)
            </span>
          </h3>
          <button
            v-if="unreadCount > 0"
            @click="handleMarkAllRead"
            class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400"
          >
            อ่านทั้งหมด
          </button>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
          <!-- Loading -->
          <div v-if="isLoading && notifications.length === 0" class="p-8 text-center">
            <Icon icon="heroicons:arrow-path" class="w-6 h-6 mx-auto text-gray-400 animate-spin" />
          </div>

          <!-- Empty State -->
          <div v-else-if="notifications.length === 0" class="p-8 text-center">
            <Icon icon="heroicons:bell-slash" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600" />
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">ไม่มีการแจ้งเตือน</p>
          </div>

          <!-- List -->
          <div v-else>
            <button
              v-for="notification in notifications"
              :key="notification.id"
              @click="handleClick(notification)"
              class="w-full flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-left"
              :class="{ 'bg-blue-50/50 dark:bg-blue-900/10': !notification.read_status }"
            >
              <!-- Icon -->
              <div
                class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full"
                :class="getColorClass(notification.color)"
              >
                <Icon :icon="notification.icon" class="w-5 h-5" />
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <p
                  class="text-sm text-gray-900 dark:text-white"
                  :class="{ 'font-medium': !notification.read_status }"
                >
                  {{ notification.content }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  {{ formatRelativeTime(notification.created_at) }}
                </p>
              </div>

              <!-- Unread indicator -->
              <div
                v-if="!notification.read_status"
                class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full mt-2"
              />
            </button>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <NuxtLink
            to="/notifications"
            @click="isOpen = false"
            class="block text-center text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium"
          >
            ดูทั้งหมด
          </NuxtLink>
        </div>
      </div>
    </Transition>
  </div>
</template>

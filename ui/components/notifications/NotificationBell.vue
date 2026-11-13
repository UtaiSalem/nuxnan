<script setup lang="ts">
/**
 * NotificationBell Component
 * แสดง icon bell พร้อม dropdown รายการแจ้งเตือน
 */
import { Icon } from '@iconify/vue'
import { onClickOutside } from '@vueuse/core'

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
  initEcho,
} = useNotifications()

const isOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)
const buttonRef = ref<HTMLElement | null>(null)
const panelRef = ref<HTMLElement | null>(null)

// The bell sits in the narrow left rail (overflow-y-auto), which would clip an
// absolute dropdown and push it off the left edge. Teleport the panel to <body>
// and place it to the right of the bell, clamped inside the viewport.
const GAP = 8
const panelStyle = ref<Record<string, string>>({})

const positionPanel = () => {
  const btn = buttonRef.value
  if (!btn) return
  const rect = btn.getBoundingClientRect()
  const vw = window.innerWidth
  const vh = window.innerHeight
  const width = Math.min(vw >= 640 ? 384 : 320, vw - GAP * 2)

  // Open beside the rail (not on top of it) when the bell lives inside one.
  const railRight = btn.closest('aside')?.getBoundingClientRect().right ?? rect.right
  let left = Math.max(rect.right, railRight) + GAP
  if (left + width > vw - GAP) {
    // Not enough room on the right: align the panel's right edge with the bell.
    left = Math.max(GAP, rect.right - width)
  }

  const panelHeight = panelRef.value?.offsetHeight ?? 0
  const minTop = 64 + GAP // below the fixed header
  let top = rect.top
  if (panelHeight && top + panelHeight > vh - GAP) {
    top = vh - GAP - panelHeight
  }
  top = Math.max(minTop, top)

  panelStyle.value = {
    left: `${left}px`,
    top: `${top}px`,
    width: `${width}px`,
    maxHeight: `${vh - minTop - GAP}px`,
  }
}

watch(isOpen, async (open) => {
  if (!open) return
  positionPanel()
  await nextTick()
  positionPanel() // re-run once the panel has a real height
})

const onViewportChange = () => {
  if (isOpen.value) positionPanel()
}

// Fetch on mount
let pollInterval: any = null

const startPolling = () => {
  pollInterval = setInterval(() => {
    if (!document.hidden) {
      fetchRecent()
    }
  }, 60000)
}

const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

const handleVisibilityChange = () => {
  if (!document.hidden) {
    fetchRecent()
  }
}

onMounted(() => {
  fetchRecent()
  initEcho()
  startPolling()
  document.addEventListener('visibilitychange', handleVisibilityChange)
  window.addEventListener('resize', onViewportChange)
  window.addEventListener('scroll', onViewportChange, true)
})

onUnmounted(() => {
  stopPolling()
  document.removeEventListener('visibilitychange', handleVisibilityChange)
  window.removeEventListener('resize', onViewportChange)
  window.removeEventListener('scroll', onViewportChange, true)
})

// Close on click outside (the panel is teleported, so ignore it explicitly)
onClickOutside(dropdownRef, () => {
  isOpen.value = false
}, { ignore: [panelRef] })

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

const activeIndex = ref(-1)

const onKeydown = (e: KeyboardEvent) => {
  if (!isOpen.value) {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') {
      isOpen.value = true
      e.preventDefault()
    }
    return
  }
  switch (e.key) {
    case 'ArrowDown':
      e.preventDefault()
      activeIndex.value = Math.min(activeIndex.value + 1, notifications.value.length - 1)
      break
    case 'ArrowUp':
      e.preventDefault()
      activeIndex.value = Math.max(activeIndex.value - 1, 0)
      break
    case 'Enter':
      e.preventDefault()
      if (activeIndex.value >= 0 && activeIndex.value < notifications.value.length) {
        handleClick(notifications.value[activeIndex.value])
      }
      break
    case 'Escape':
      e.preventDefault()
      isOpen.value = false
      break
  }
}

watch(isOpen, (open) => {
  if (open) {
    activeIndex.value = notifications.value.length > 0 ? 0 : -1
  } else {
    activeIndex.value = -1
  }
})
</script>

<template>
  <div ref="dropdownRef" class="relative" @keydown="onKeydown">
    <!-- Bell Button -->
    <button
      ref="buttonRef"
      @click="isOpen = !isOpen"
      aria-haspopup="listbox"
      :aria-expanded="isOpen"
      class="min-h-[44px] sm:min-h-0 relative p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors"
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
    <Teleport to="body">
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
        ref="panelRef"
        role="listbox"
        :style="panelStyle"
        @keydown="onKeydown"
        class="fixed flex flex-col bg-white dark:bg-gray-800 rounded-xl shadow-lg ring-1 ring-black/5 dark:ring-white/10 z-[60] overflow-hidden"
      >
        <!-- Header -->
        <div class="flex items-center gap-2 pl-4 pr-2 py-2 border-b border-gray-200 dark:border-gray-700">
          <h3 class="min-w-0 flex-1 break-words font-semibold text-gray-900 dark:text-white">
            การแจ้งเตือน
            <span v-if="unreadCount > 0" class="ml-2 text-sm font-normal text-gray-500">
              ({{ unreadCount }} ยังไม่อ่าน)
            </span>
          </h3>
          <button
            v-if="unreadCount > 0"
            @click="handleMarkAllRead"
            class="flex-shrink-0 whitespace-nowrap text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400"
          >
            อ่านทั้งหมด
          </button>
          <button
            type="button"
            @click="isOpen = false"
            aria-label="ปิด"
            title="ปิด"
            class="flex-shrink-0 flex items-center justify-center w-11 h-11 sm:w-8 sm:h-8 rounded-full text-gray-500 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 transition-colors"
          >
            <Icon icon="heroicons:x-mark" class="w-5 h-5" />
          </button>
        </div>

        <!-- Notifications List -->
        <div class="flex-1 min-h-0 max-h-96 overflow-y-auto">
          <!-- Loading -->
          <div v-if="isLoading && notifications.length === 0" class="p-8 text-center">
            <Icon icon="heroicons:arrow-path" class="w-6 h-6 mx-auto text-gray-400 animate-spin" />
          </div>

          <CommonEmptyState
            v-else-if="notifications.length === 0"
            icon="heroicons:bell-slash"
            title="ไม่มีการแจ้งเตือน"
            description="คุณได้อ่านข้อมูลทั้งหมดหรือยังไม่มีการแจ้งเตือนใหม่เข้ามา"
            compact
          />

          <!-- List -->
          <div v-else>
            <button
              v-for="(notification, index) in notifications"
              :key="notification.id"
              role="option"
              :aria-selected="activeIndex === index"
              @click="handleClick(notification)"
              class="w-full flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-left"
              :class="[
                !notification.read_status ? 'bg-blue-50/50 dark:bg-blue-900/10' : '',
                activeIndex === index ? 'bg-gray-100 dark:bg-gray-700/60' : ''
              ]"
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
    </Teleport>
  </div>
</template>

<script setup lang="ts">
/**
 * Notifications Page
 * หน้ารายการแจ้งเตือนทั้งหมด
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  middleware: ['auth']
})

useHead({
  title: 'การแจ้งเตือน',
})

const api = useApi()
const router = useRouter()

// State
const notifications = ref<any[]>([])
const pagination = ref<any>(null)
const isLoading = ref(true)
const activeTab = ref('all')
const tabs = [
  { id: 'all', label: 'ทั้งหมด' },
  { id: 'unread', label: 'ยังไม่อ่าน' },
  { id: 'grade', label: 'ผลการเรียน' },
  { id: 'certificate', label: 'ใบประกาศ' },
]

// Fetch notifications
const fetchNotifications = async (page = 1) => {
  isLoading.value = true
  try {
    const params = new URLSearchParams({
      page: page.toString(),
      per_page: '20',
    })
    
    if (activeTab.value === 'unread') {
      params.append('unread_only', 'true')
    } else if (activeTab.value === 'grade') {
      params.append('type', 'grade_published')
    } else if (activeTab.value === 'certificate') {
      params.append('type', 'certificate_issued')
    }
    
    const res: any = await api.get(`/api/notifications?${params.toString()}`)
    if (res.success) {
      notifications.value = res.data.data || []
      pagination.value = {
        current_page: res.data.current_page,
        last_page: res.data.last_page,
        total: res.data.total,
      }
    }
  } catch (err) {
    console.error('Failed to fetch notifications:', err)
  } finally {
    isLoading.value = false
  }
}

// Actions
const markAsRead = async (notification: any) => {
  if (notification.read_status) return
  
  try {
    await api.post(`/api/notifications/${notification.id}/read`)
    notification.read_status = true
  } catch (err) {
    console.error('Failed to mark as read:', err)
  }
}

const markAllAsRead = async () => {
  try {
    await api.post('/api/notifications/mark-all-read')
    notifications.value.forEach(n => {
      n.read_status = true
    })
    useToast().success('อ่านทั้งหมดแล้ว')
  } catch (err) {
    console.error('Failed to mark all as read:', err)
  }
}

const deleteNotification = async (notification: any) => {
  try {
    await api.delete(`/api/notifications/${notification.id}`)
    const index = notifications.value.indexOf(notification)
    if (index !== -1) {
      notifications.value.splice(index, 1)
    }
  } catch (err) {
    console.error('Failed to delete:', err)
  }
}

const deleteAllRead = async () => {
  if (!confirm('ต้องการลบการแจ้งเตือนที่อ่านแล้วทั้งหมด?')) return
  
  try {
    const res: any = await api.delete('/api/notifications/read')
    if (res.success) {
      notifications.value = notifications.value.filter(n => !n.read_status)
      useToast().success(res.message)
    }
  } catch (err) {
    console.error('Failed to delete read:', err)
  }
}

const handleClick = async (notification: any) => {
  await markAsRead(notification)
  
  if (notification.action_url) {
    await router.push(notification.action_url)
  }
}

// Filter change
watch(activeTab, () => {
  fetchNotifications(1)
})

// Helpers
const getColorClass = (color: string): string => {
  const classes: Record<string, string> = {
    blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400',
    green: 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400',
    yellow: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400',
    purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-400',
    emerald: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900 dark:text-emerald-400',
    cyan: 'bg-cyan-100 text-cyan-600 dark:bg-cyan-900 dark:text-cyan-400',
    orange: 'bg-orange-100 text-orange-600 dark:bg-orange-900 dark:text-orange-400',
    teal: 'bg-teal-100 text-teal-600 dark:bg-teal-900 dark:text-teal-400',
    red: 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400',
    gray: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
  }
  return classes[color] || classes.gray
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffMins < 1) return 'เมื่อสักครู่'
  if (diffMins < 60) return `${diffMins} นาทีที่แล้ว`
  if (diffHours < 24) return `${diffHours} ชั่วโมงที่แล้ว`
  if (diffDays < 7) return `${diffDays} วันที่แล้ว`
  
  return date.toLocaleDateString('th-TH', { 
    day: 'numeric', 
    month: 'long',
    year: 'numeric'
  })
}

onMounted(() => {
  fetchNotifications()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-20 lg:pb-0">
    <!-- Page Header -->
    <section class="bg-gradient-to-r from-primary-500 to-primary-600 text-white py-6 mb-4">
      <div class="container mx-auto px-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold mb-1">การแจ้งเตือน</h1>
            <div class="flex items-center gap-2 text-sm text-primary-100">
              <NuxtLink to="/" class="hover:text-white transition-colors">หน้าแรก</NuxtLink>
              <Icon icon="mdi:chevron-right" class="w-4 h-4" />
              <span>การแจ้งเตือน</span>
            </div>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <button
              @click="markAllAsRead"
              class="px-3 py-2 text-xs sm:text-sm font-medium bg-white/10 hover:bg-white/20 rounded-lg transition-colors"
            >
              อ่านทั้งหมด
            </button>
            <button
              @click="deleteAllRead"
              class="px-3 py-2 text-xs sm:text-sm font-medium bg-white/10 hover:bg-white/20 rounded-lg transition-colors"
            >
              ลบที่อ่านแล้ว
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Notifications List -->
    <div class="container mx-auto px-4 pb-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
        <!-- Tabs -->
        <div class="flex border-b border-gray-200 dark:border-gray-700 mobile-scroll-x">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            class="flex-1 px-4 py-3 font-medium transition-colors relative"
            :class="activeTab === tab.id ? 'text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400'"
          >
            {{ tab.label }}
            <div
              v-if="activeTab === tab.id"
              class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary-600"
            ></div>
          </button>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="py-12 text-center">
          <Icon icon="heroicons:arrow-path" class="w-8 h-8 mx-auto text-gray-400 animate-spin" />
        </div>

        <!-- Empty State -->
        <div v-else-if="notifications.length === 0" class="py-16 text-center">
          <Icon icon="heroicons:bell-slash" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" />
          <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">ไม่มีการแจ้งเตือน</h3>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ activeTab === 'unread' ? 'คุณอ่านการแจ้งเตือนทั้งหมดแล้ว' : 'ยังไม่มีการแจ้งเตือนใดๆ' }}
          </p>
        </div>

        <!-- Notification Items -->
        <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            @click="handleClick(notification)"
            class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
            :class="{ 'bg-blue-50/50 dark:bg-blue-900/10': !notification.read_status }"
          >
            <div class="flex items-start gap-3">
              <!-- Icon -->
              <div
                class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0"
                :class="getColorClass(notification.color || 'gray')"
              >
                <Icon :icon="notification.icon || 'heroicons:bell'" class="w-6 h-6" />
              </div>

              <div class="flex-1 min-w-0">
                <p 
                  class="text-sm text-gray-900 dark:text-white"
                  :class="{ 'font-semibold': !notification.read_status }"
                >
                  {{ notification.content }}
                </p>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ formatDate(notification.created_at) }}
                </span>
              </div>

              <div class="flex items-center gap-2">
                <!-- Unread indicator -->
                <div
                  v-if="!notification.read_status"
                  class="w-2.5 h-2.5 bg-blue-500 rounded-full"
                />
                
                <!-- Delete button -->
                <button
                  @click.stop="deleteNotification(notification)"
                  class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                >
                  <Icon icon="heroicons:trash" class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div
          v-if="pagination && pagination.last_page > 1"
          class="flex justify-center gap-2 p-4 border-t border-gray-200 dark:border-gray-700"
        >
          <button
            v-for="page in pagination.last_page"
            :key="page"
            @click="fetchNotifications(page)"
            :class="[
              'px-3 py-1 text-sm font-medium rounded-lg transition-colors',
              page === pagination.current_page
                ? 'bg-primary-600 text-white'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

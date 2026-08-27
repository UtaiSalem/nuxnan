<script setup lang="ts">
/**
 * Notifications Page
 * หน้ารายการแจ้งเตือนทั้งหมด
 */
import { Icon } from '@iconify/vue'
import type { ApiNotification } from '~/composables/useNotifications'
import { ENROLLMENT_NOTIFICATION_TYPES, ROLLOVER_NOTIFICATION_TYPES } from '~/composables/useNotifications'

definePageMeta({
  middleware: ['auth'],
})

useHead({
  title: 'การแจ้งเตือน',
})

const api = useApi()
const router = useRouter()
const {
  getTypeLabel,
  getColorClass,
  formatRelativeTime,
} = useNotifications()

type NotificationTabId = 'all' | 'unread' | 'grade' | 'certificate' | 'enrollment' | 'rollover'

const notifications = ref<ApiNotification[]>([])
const pagination = ref<{ current_page: number, last_page: number, total: number } | null>(null)
const isLoading = ref(true)
const activeTab = ref<NotificationTabId>('all')
const tabs: Array<{ id: NotificationTabId, label: string }> = [
  { id: 'all', label: 'ทั้งหมด' },
  { id: 'unread', label: 'ยังไม่อ่าน' },
  { id: 'grade', label: 'ผลการเรียน' },
  { id: 'certificate', label: 'ใบประกาศ' },
  { id: 'enrollment', label: 'สถานะนักเรียน' },
  { id: 'rollover', label: 'Rollover' },
]

const activeTypeFilters = computed<string[]>(() => {
  switch (activeTab.value) {
    case 'grade':
      return [
        'grade_published',
        'grade_finalized',
        'appeal_submitted',
        'appeal_responded',
        'eligibility_unlocked',
        'remediation_opened',
        'remediation_completed',
      ]
    case 'certificate':
      return ['certificate_issued']
    case 'enrollment':
      return [...ENROLLMENT_NOTIFICATION_TYPES]
    case 'rollover':
      return [...ROLLOVER_NOTIFICATION_TYPES]
    default:
      return []
  }
})

const activeTabDescription = computed(() => {
  switch (activeTab.value) {
    case 'unread':
      return 'รายการที่ยังไม่ได้เปิดอ่าน'
    case 'grade':
      return 'เกรด อุทธรณ์ สิทธิ์สอบ และซ่อมเสริม'
    case 'certificate':
      return 'การออกใบประกาศนียบัตร'
    case 'enrollment':
      return 'การลงทะเบียน ย้ายห้อง เลื่อนชั้น ซ้ำชั้น จบ และพ้นสภาพ'
    case 'rollover':
      return 'การ commit และ undo academic year rollover'
    default:
      return 'รวมการแจ้งเตือนทุกประเภท'
  }
})

const activeTabLabel = computed(() => {
  return tabs.find(tab => tab.id === activeTab.value)?.label ?? 'ทั้งหมด'
})

const fetchNotifications = async (page = 1) => {
  isLoading.value = true
  try {
    const params = new URLSearchParams({
      page: page.toString(),
      per_page: '20',
    })

    if (activeTab.value === 'unread') {
      params.append('unread_only', 'true')
    }

    for (const type of activeTypeFilters.value) {
      params.append('types[]', type)
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

const markAsRead = async (notification: ApiNotification) => {
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
    notifications.value.forEach(notification => {
      notification.read_status = true
    })
    useToast().success('อ่านทั้งหมดแล้ว')
  } catch (err) {
    console.error('Failed to mark all as read:', err)
  }
}

const deleteNotification = async (notification: ApiNotification) => {
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
      notifications.value = notifications.value.filter(notification => !notification.read_status)
      useToast().success(res.message)
    }
  } catch (err) {
    console.error('Failed to delete read:', err)
  }
}

const handleClick = async (notification: ApiNotification) => {
  await markAsRead(notification)

  if (notification.action_url) {
    await router.push(notification.action_url)
  }
}

watch(activeTab, () => {
  fetchNotifications(1)
})

onMounted(() => {
  fetchNotifications()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-20 lg:pb-0">
    <section class="bg-gradient-to-r from-primary-500 to-primary-600 text-white py-6 mb-4">
      <div class="container mx-auto px-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
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

    <div class="container mx-auto px-4 pb-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
        <div class="flex border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            class="min-w-max flex-1 px-4 py-3 font-medium transition-colors relative whitespace-nowrap"
            :class="activeTab === tab.id ? 'text-primary-600 dark:text-primary-400' : 'text-gray-600 dark:text-gray-400'"
          >
            {{ tab.label }}
            <div
              v-if="activeTab === tab.id"
              class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary-600"
            />
          </button>
        </div>

        <div class="border-b border-gray-100 px-4 py-3 text-sm text-gray-500 dark:border-gray-700/70 dark:text-gray-400">
          {{ activeTabDescription }}
        </div>

        <div v-if="isLoading" class="py-12 text-center">
          <Icon icon="heroicons:arrow-path" class="w-8 h-8 mx-auto text-gray-400 animate-spin" />
        </div>

        <div v-else-if="notifications.length === 0" class="py-16 text-center">
          <Icon icon="heroicons:bell-slash" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" />
          <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">ไม่มีการแจ้งเตือน</h3>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ activeTab === 'unread' ? 'คุณอ่านการแจ้งเตือนทั้งหมดแล้ว' : `ยังไม่มีการแจ้งเตือนในหมวด "${activeTabLabel}"` }}
          </p>
        </div>

        <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            @click="handleClick(notification)"
            class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
            :class="{ 'bg-blue-50/50 dark:bg-blue-900/10': !notification.read_status }"
          >
            <div class="flex items-start gap-3">
              <div
                class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0"
                :class="getColorClass(notification.color || 'gray')"
              >
                <Icon :icon="notification.icon || 'heroicons:bell'" class="w-6 h-6" />
              </div>

              <div class="flex-1 min-w-0">
                <div class="mb-1 flex flex-wrap items-center gap-2">
                  <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    {{ getTypeLabel(notification.type) }}
                  </span>
                  <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatRelativeTime(notification.created_at) }}
                  </span>
                </div>

                <p
                  class="text-sm text-gray-900 dark:text-white"
                  :class="{ 'font-semibold': !notification.read_status }"
                >
                  {{ notification.content }}
                </p>

                <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                  {{ new Date(notification.created_at).toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                </span>
              </div>

              <div class="flex items-center gap-2">
                <div
                  v-if="!notification.read_status"
                  class="w-2.5 h-2.5 bg-blue-500 rounded-full"
                />

                <button
                  @click.stop="deleteNotification(notification)"
                  class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1 text-gray-400 hover:text-red-500 transition-colors"
                >
                  <Icon icon="heroicons:trash" class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

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
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200',
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

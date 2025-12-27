<template>
  <div>
    <!-- Page Header -->
    <section class="bg-gradient-to-r from-primary-500 to-primary-600 text-white py-6 mb-4">
      <div class="container mx-auto px-4">
        <h1 class="text-2xl font-bold mb-1">Notifications</h1>
        <div class="flex items-center gap-2 text-sm text-primary-100">
          <NuxtLink to="/" class="hover:text-white transition-colors">Home</NuxtLink>
          <Icon icon="mdi:chevron-right" class="w-4 h-4" />
          <span>Notifications</span>
        </div>
      </div>
    </section>

    <!-- Notifications List -->
    <div class="container mx-auto px-4 pb-6">
      <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <!-- Tabs -->
        <div class="flex border-b border-secondary-200">
          <button
            v-for="tab in tabs"
            :key="tab"
            @click="activeTab = tab"
            class="flex-1 px-4 py-3 font-medium transition-colors relative"
            :class="activeTab === tab ? 'text-primary-600' : 'text-secondary-600'"
          >
            {{ tab }}
            <div
              v-if="activeTab === tab"
              class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary-600"
            ></div>
          </button>
        </div>

        <!-- Notification Items -->
        <div class="divide-y divide-secondary-200">
          <div
            v-for="notification in filteredNotifications"
            :key="notification.id"
            class="p-4 hover:bg-gray-50 transition-colors"
            :class="{ 'bg-primary-50/50': !notification.read }"
          >
            <div class="flex items-start gap-3">
              <img
                :src="notification.avatar"
                :alt="notification.user"
                class="w-12 h-12 rounded-full object-cover flex-shrink-0"
              />

              <div class="flex-1 min-w-0">
                <p class="text-sm text-secondary-700 mb-1">
                  <span class="font-semibold">{{ notification.user }}</span>
                  {{ notification.message }}
                </p>
                <span class="text-xs text-secondary-500">{{ notification.time }}</span>
              </div>

              <Icon
                :name="getNotificationIcon(notification.type)"
                class="w-6 h-6 flex-shrink-0"
                :class="getNotificationColor(notification.type)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

const activeTab = ref('All')
const tabs = ['All', 'Likes', 'Comments', 'Follows']

const notifications = ref([
  {
    id: 1,
    type: 'like',
    user: 'Sarah Anderson',
    avatar: 'https://i.pravatar.cc/150?img=1',
    message: 'liked your post',
    time: '5m ago',
    read: false,
  },
  {
    id: 2,
    type: 'comment',
    user: 'John Smith',
    avatar: 'https://i.pravatar.cc/150?img=2',
    message: 'commented on your post',
    time: '1h ago',
    read: false,
  },
  {
    id: 3,
    type: 'follow',
    user: 'Emily Chen',
    avatar: 'https://i.pravatar.cc/150?img=3',
    message: 'started following you',
    time: '2h ago',
    read: false,
  },
  {
    id: 4,
    type: 'like',
    user: 'Michael Brown',
    avatar: 'https://i.pravatar.cc/150?img=4',
    message: 'liked your photo',
    time: '1d ago',
    read: true,
  },
  {
    id: 5,
    type: 'comment',
    user: 'Jessica Lee',
    avatar: 'https://i.pravatar.cc/150?img=5',
    message: 'replied to your comment',
    time: '2d ago',
    read: true,
  },
])

const filteredNotifications = computed(() => {
  if (activeTab.value === 'All') return notifications.value
  return notifications.value.filter((n) => {
    if (activeTab.value === 'Likes') return n.type === 'like'
    if (activeTab.value === 'Comments') return n.type === 'comment'
    if (activeTab.value === 'Follows') return n.type === 'follow'
    return true
  })
})

const getNotificationIcon = (type: string) => {
  const icons: Record<string, string> = {
    like: 'mdi:heart',
    comment: 'mdi:comment',
    follow: 'mdi:account-plus',
  }
  return icons[type] || 'mdi:bell'
}

const getNotificationColor = (type: string) => {
  const colors: Record<string, string> = {
    like: 'text-red-500',
    comment: 'text-blue-500',
    follow: 'text-green-500',
  }
  return colors[type] || 'text-gray-500'
}

useHead({
  title: 'Notifications',
})
</script>

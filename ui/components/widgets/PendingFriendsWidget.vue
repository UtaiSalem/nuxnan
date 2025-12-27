<script setup>
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const api = useApi()

const requests = ref([])
const isLoading = ref(true)
const error = ref(null)

const fetchPendingRequests = async () => {
  isLoading.value = true
  error.value = null
  try {
    const data = await api.get('/api/friends/pending')
    if (data?.requests) {
      requests.value = data.requests
    }
  } catch (err) {
    console.error('Error fetching pending requests:', err)
    error.value = 'ไม่สามารถโหลดข้อมูลได้'
  } finally {
    isLoading.value = false
  }
}

const acceptRequest = async (friendId) => {
  try {
    await api.patch(`/api/friends/${friendId}/accept`)
    requests.value = requests.value.filter(r => r.sender?.id !== friendId)
  } catch (err) {
    console.error('Error accepting friend request:', err)
  }
}

const denyRequest = async (friendId) => {
  try {
    await api.post(`/api/friends/${friendId}/deny`)
    requests.value = requests.value.filter(r => r.sender?.id !== friendId)
  } catch (err) {
    console.error('Error denying friend request:', err)
  }
}

onMounted(() => {
  fetchPendingRequests()
})
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900 dark:text-white">คำขอเป็นเพื่อน</h3>
      <span 
        v-if="requests.length > 0" 
        class="px-2 py-0.5 text-xs font-medium text-white bg-vikinger-purple rounded-full"
      >
        {{ requests.length }}
      </span>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 2" :key="i" class="flex items-center gap-3 animate-pulse">
        <div class="w-10 h-10 bg-gray-200 dark:bg-vikinger-dark-100 rounded-full"></div>
        <div class="flex-1 space-y-2">
          <div class="h-3 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-3/4"></div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-4 text-gray-500 dark:text-gray-400">
      <Icon icon="fluent:error-circle-24-regular" class="w-8 h-8 mx-auto mb-2 text-red-400" />
      <p class="text-sm">{{ error }}</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="requests.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
      <Icon icon="fluent:people-checkmark-24-regular" class="w-8 h-8 mx-auto mb-2 opacity-50" />
      <p class="text-sm">ไม่มีคำขอเป็นเพื่อนใหม่</p>
    </div>

    <!-- Requests List -->
    <div v-else class="space-y-3">
      <div 
        v-for="request in requests" 
        :key="request.id" 
        class="p-3 rounded-lg bg-gray-50 dark:bg-vikinger-dark-100"
      >
        <div class="flex items-center gap-3 mb-3">
          <img 
            :src="request.sender?.avatar || '/storage/images/default-avatar.png'" 
            :alt="request.sender?.name"
            class="w-10 h-10 rounded-full object-cover ring-2 ring-vikinger-purple/20"
          />
          <div class="flex-1 min-w-0">
            <p class="font-medium text-gray-900 dark:text-white truncate">
              {{ request.sender?.name }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ request.diff_humans_created_at || 'เมื่อสักครู่' }}
            </p>
          </div>
        </div>
        <div class="flex gap-2">
          <button 
            @click="acceptRequest(request.sender?.id)"
            class="flex-1 py-2 text-sm font-medium text-white bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-lg hover:opacity-90 transition-opacity"
          >
            ยอมรับ
          </button>
          <button 
            @click="denyRequest(request.sender?.id)"
            class="flex-1 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-200 dark:bg-vikinger-dark-200 rounded-lg hover:bg-gray-300 dark:hover:bg-vikinger-dark-50 transition-colors"
          >
            ปฏิเสธ
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

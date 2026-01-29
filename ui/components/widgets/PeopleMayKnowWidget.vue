<script setup>
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const api = useApi()

const users = ref([])
const isLoading = ref(true)
const error = ref(null)

const fetchSuggestions = async () => {
  isLoading.value = true
  error.value = null
  try {
    const data = await api.get('/api/friends/suggestions')
    if (data?.users) {
      users.value = data.users
    }
  } catch (err) {
    console.error('Error fetching suggestions:', err)
    error.value = 'ไม่สามารถโหลดข้อมูลได้'
  } finally {
    isLoading.value = false
  }
}

const sendFriendRequest = async (userId) => {
  try {
    await api.post(`/api/friends/${userId}`)
    // Remove user from list after sending request
    users.value = users.value.filter(u => u.id !== userId)
  } catch (err) {
    console.error('Error sending friend request:', err)
  }
}

onMounted(() => {
  fetchSuggestions()
})
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900 dark:text-white">คนที่คุณอาจรู้จัก</h3>
      <button 
        @click="fetchSuggestions" 
        class="text-vikinger-purple hover:text-vikinger-purple/80 transition-colors"
        :disabled="isLoading"
      >
        <Icon 
          icon="fluent:arrow-sync-24-regular" 
          class="w-4 h-4" 
          :class="{ 'animate-spin': isLoading }"
        />
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="flex items-center gap-3 animate-pulse">
        <div class="w-10 h-10 bg-gray-200 dark:bg-vikinger-dark-100 rounded-full"></div>
        <div class="flex-1 space-y-2">
          <div class="h-3 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-3/4"></div>
          <div class="h-2 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-1/2"></div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-4 text-gray-500 dark:text-gray-400">
      <Icon icon="fluent:error-circle-24-regular" class="w-8 h-8 mx-auto mb-2 text-red-400" />
      <p class="text-sm">{{ error }}</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="users.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
      <Icon icon="fluent:people-24-regular" class="w-8 h-8 mx-auto mb-2 opacity-50" />
      <p class="text-sm">ไม่มีคำแนะนำในขณะนี้</p>
    </div>

    <!-- User List -->
    <div v-else class="space-y-3">
      <div 
        v-for="user in users" 
        :key="user.id" 
        class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"
      >
        <NuxtLink :to="`/profile/${user.reference_code}`">
          <img 
            :src="user.avatar || '/images/default-avatar.png'" 
            :alt="user.name"
            class="w-10 h-10 rounded-full object-cover ring-2 ring-vikinger-purple/20"
          />
        </NuxtLink>
        <div class="flex-1 min-w-0">
          <NuxtLink 
            :to="`/profile/${user.reference_code}`"
            class="block font-medium text-gray-900 dark:text-white truncate hover:text-vikinger-purple transition-colors"
          >
            {{ user.name }}
          </NuxtLink>
          <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
            {{ user.email }}
          </p>
        </div>
        <button 
          @click="sendFriendRequest(user.id)"
          class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-full hover:opacity-90 transition-opacity"
        >
          <Icon icon="fluent:person-add-24-regular" class="w-4 h-4" />
          <span class="hidden sm:inline">เพิ่มเพื่อน</span>
        </button>
      </div>
    </div>
  </div>
</template>

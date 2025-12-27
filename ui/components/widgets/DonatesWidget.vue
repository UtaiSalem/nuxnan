<script setup>
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const api = useApi()

const donates = ref([])
const isLoading = ref(true)
const error = ref(null)

const fetchDonates = async () => {
  isLoading.value = true
  error.value = null
  try {
    const data = await api.get('/api/donates/widget')
    if (data?.donates) {
      donates.value = data.donates
    }
  } catch (err) {
    console.error('Error fetching donates:', err)
    error.value = 'ไม่สามารถโหลดข้อมูลได้'
  } finally {
    isLoading.value = false
  }
}

const getDonate = async (donateId) => {
  try {
    const data = await api.get(`/api/donates/${donateId}/get-donate`)
    if (data?.success) {
      // Refresh the list after getting points
      fetchDonates()
    }
  } catch (err) {
    console.error('Error getting donate:', err)
  }
}

onMounted(() => {
  fetchDonates()
})
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900 dark:text-white">สะสมแต้ม</h3>
      <NuxtLink 
        to="/donates" 
        class="text-xs text-vikinger-purple hover:text-vikinger-purple/80 transition-colors"
      >
        ดูทั้งหมด
      </NuxtLink>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="flex items-center gap-3 animate-pulse">
        <div class="w-10 h-10 bg-gray-200 dark:bg-vikinger-dark-100 rounded-lg"></div>
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
    <div v-else-if="donates.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
      <Icon icon="mdi:hand-coin-outline" class="w-8 h-8 mx-auto mb-2 opacity-50" />
      <p class="text-sm">ไม่มีการสนับสนุนในขณะนี้</p>
    </div>

    <!-- Donates List -->
    <div v-else class="space-y-3">
      <div 
        v-for="donate in donates" 
        :key="donate.id" 
        class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"
      >
        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center">
          <Icon icon="mdi:hand-coin" class="w-5 h-5 text-white" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-medium text-gray-900 dark:text-white text-sm truncate">
            {{ donate.donor_name || 'ผู้สนับสนุน' }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            เหลือ {{ donate.remaining_points?.toLocaleString() || 0 }} แต้ม
          </p>
        </div>
        <button 
          @click="getDonate(donate.id)"
          :disabled="donate.remaining_points < 270"
          class="px-3 py-1.5 text-xs font-medium text-white bg-gradient-to-r from-yellow-500 to-orange-500 rounded-full hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
        >
          รับแต้ม
        </button>
      </div>
    </div>
  </div>
</template>

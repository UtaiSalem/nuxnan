<script setup>
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const api = useApi()

const advertises = ref([])
const isLoading = ref(true)
const error = ref(null)

const fetchAdvertises = async () => {
  isLoading.value = true
  error.value = null
  try {
    const data = await api.get('/api/advertises/widget')
    if (data?.advertises) {
      advertises.value = data.advertises
    }
  } catch (err) {
    console.error('Error fetching advertises:', err)
    error.value = 'ไม่สามารถโหลดข้อมูลได้'
  } finally {
    isLoading.value = false
  }
}

async function handleAdView(advertId) {
  try {
    const data = await api.post(`/api/advertises/${advertId}/view`)
    if (data) {
      // Handle success, e.g., refresh the list if needed, or just log
      // For now, we'll just log and not refresh, as per the instruction's new handleAdView
      console.log('Advertise viewed successfully:', data);
    }
  } catch (err) {
    console.error('Error viewing advertise:', err)
  }
}

onMounted(() => {
  fetchAdvertises()
})
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-lg dark:text-white">สินค้าแนะนำ</h3>
      <NuxtLink 
        to="/earn/advertise" 
        class="text-sm text-blue-500 hover:underline"
      >
        ดูทั้งหมด
      </NuxtLink>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 2" :key="i" class="animate-pulse">
        <div class="h-24 bg-gray-200 dark:bg-vikinger-dark-100 rounded-lg mb-2"></div>
        <div class="h-3 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-3/4"></div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-4 text-gray-500 dark:text-gray-400">
      <Icon icon="fluent:error-circle-24-regular" class="w-8 h-8 mx-auto mb-2 text-red-400" />
      <p class="text-sm">{{ error }}</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="advertises.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
      <Icon icon="eos-icons:product-subscriptions-outlined" class="w-8 h-8 mx-auto mb-2 opacity-50" />
      <p class="text-sm">ไม่มีโฆษณาในขณะนี้</p>
    </div>

    <!-- Advertises List -->
    <div v-else class="space-y-3">
      <div 
        v-for="advert in advertises" 
        :key="advert.id" 
        class="rounded-lg overflow-hidden bg-gray-50 dark:bg-vikinger-dark-100 hover:shadow-md transition-shadow cursor-pointer"
        @click="viewAdvertise(advert.id)"
      >
        <img 
          v-if="advert.media_image"
          :src="advert.media_image" 
          :alt="advert.supporter"
          class="w-full h-24 object-cover"
        />
        <div v-else class="w-full h-24 bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center">
          <Icon icon="fluent:megaphone-24-regular" class="w-8 h-8 text-white/60" />
        </div>
        <div class="p-2">
          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
            {{ advert.supporter || 'ผู้สนับสนุน' }}
          </p>
          <div class="flex items-center justify-between mt-1">
            <span class="text-xs text-gray-500 dark:text-gray-400">
              เหลือ {{ advert.remaining_views?.toLocaleString() || 0 }} ครั้ง
            </span>
            <span class="text-xs font-medium text-green-600 dark:text-green-400">
              +{{ (advert.duration * 0.04).toFixed(2) }} บาท
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

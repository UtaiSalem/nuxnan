<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'

const api = useApi()
const config = useRuntimeConfig()
const { user } = storeToRefs(useAuthStore())

const academies = ref<any[]>([])
const isLoading = ref(true)

const fetchAllAcademies = async () => {
  if (!user.value) {
    return
  }
  
  try {
    const response: any = await api.get('/api/academies/all-academies', {
      params: { per_page: 5 }
    })

    if (response.success) {
      const allAcademies = response.academies?.data || response.academies || []
      // Deep clone to fix prototype chain for Pinia serialization
      academies.value = JSON.parse(JSON.stringify(allAcademies.slice(0, 5)))
    }
  } catch (error: any) {
    // Error handled silently
  } finally {
    isLoading.value = false
  }
}

const getLogoUrl = (academy: any) => {
  if (academy.logo) {
    if (academy.logo.startsWith('http')) return academy.logo
    return academy.logo
  }
  return `${config.public.apiBase}/storage/images/academies/logos/default_logo.png`
}

const getAcademyType = (type: string | null) => {
  const typeMap: Record<string, { label: string; icon: string; color: string }> = {
    'public': { label: 'รัฐบาล', icon: 'fluent:building-government-24-regular', color: 'text-blue-500' },
    'private': { label: 'เอกชน', icon: 'fluent:building-bank-24-regular', color: 'text-purple-500' },
    'foundation': { label: 'มูลนิธิ', icon: 'fluent:heart-24-regular', color: 'text-pink-500' },
    'international': { label: 'นานาชาติ', icon: 'fluent:globe-24-regular', color: 'text-green-500' },
  }
  return typeMap[type || ''] || { label: 'ทั่วไป', icon: 'fluent:building-24-regular', color: 'text-gray-500' }
}

// Only fetch on client side to avoid SSR serialization issues
onMounted(() => {
  if (user.value) {
    fetchAllAcademies()
  }
})

// Watch for user changes (client-side only)
watch(user, (newUser) => {
  if (import.meta.client && newUser && academies.value.length === 0) {
    fetchAllAcademies()
  }
})
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-400 to-pink-500 flex items-center justify-center">
          <Icon icon="fluent:building-multiple-24-regular" class="w-4 h-4 text-white" />
        </div>
        <h3 class="font-bold text-gray-800 dark:text-white">โรงเรียนทั้งหมด</h3>
      </div>
      <NuxtLink 
        to="/academies" 
        class="text-xs text-vikinger-purple hover:text-vikinger-cyan transition-colors"
      >
        ดูทั้งหมด
      </NuxtLink>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-700">
      <!-- Loading State -->
      <template v-if="isLoading">
        <div v-for="i in 3" :key="i" class="p-4 flex gap-3 animate-pulse">
          <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
          <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
          </div>
        </div>
      </template>

      <!-- Academies List -->
      <template v-else-if="academies.length > 0">
        <NuxtLink 
          v-for="academy in academies" 
          :key="academy.id"
          :to="`/academies/${encodeURIComponent(academy.name)}`"
          class="p-4 flex items-start gap-4 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors group"
        >
          <!-- Logo -->
          <div class="relative w-12 h-12 flex-shrink-0 mt-1">
            <img 
              :src="getLogoUrl(academy)" 
              :alt="academy.name"
              class="w-full h-full object-cover rounded-lg shadow-sm"
            />
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-white line-clamp-2 leading-tight group-hover:text-vikinger-purple transition-colors mb-1">
              {{ academy.name }}
            </h4>
            
            <div class="flex items-center gap-2 flex-wrap">
              <!-- Type Badge -->
              <span 
                v-if="academy.type"
                class="inline-flex items-center gap-1 text-xs"
                :class="getAcademyType(academy.type).color"
              >
                <Icon :icon="getAcademyType(academy.type).icon" class="w-3 h-3" />
                {{ getAcademyType(academy.type).label }}
              </span>

              <!-- Location -->
              <span v-if="academy.address" class="text-xs text-gray-500 dark:text-gray-400 truncate">
                <Icon icon="fluent:location-24-regular" class="w-3 h-3 inline" />
                {{ academy.address }}
              </span>
            </div>

            <!-- Stats -->
            <div class="flex items-center gap-3 mt-2 text-xs text-gray-400 dark:text-gray-500">
              <span v-if="academy.total_students" class="flex items-center gap-1">
                <Icon icon="fluent:people-24-regular" class="w-3 h-3" />
                {{ academy.total_students }} นักเรียน
              </span>
              <span v-if="academy.total_teachers" class="flex items-center gap-1">
                <Icon icon="fluent:person-24-regular" class="w-3 h-3" />
                {{ academy.total_teachers }} ครู
              </span>
            </div>
          </div>

          <!-- Arrow -->
          <Icon 
            icon="fluent:chevron-right-24-regular" 
            class="w-4 h-4 text-gray-400 group-hover:text-vikinger-purple transition-colors flex-shrink-0 mt-2" 
          />
        </NuxtLink>
      </template>

      <!-- Empty State -->
      <div v-else class="p-6 text-center">
        <Icon icon="fluent:building-multiple-24-regular" class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" />
        <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีโรงเรียนในระบบ</p>
      </div>
    </div>

    <!-- View All Footer -->
    <div v-if="academies.length > 0" class="p-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-vikinger-dark-100">
      <NuxtLink 
        to="/academies"
        class="w-full flex items-center justify-center gap-2 py-2 text-sm font-medium text-vikinger-purple hover:text-vikinger-cyan transition-colors"
      >
        <Icon icon="fluent:grid-24-regular" class="w-4 h-4" />
        ดูโรงเรียนทั้งหมด
      </NuxtLink>
    </div>
  </div>
</template>

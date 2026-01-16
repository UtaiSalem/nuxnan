<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'

const api = useApi()
const { user } = storeToRefs(useAuthStore())
const config = useRuntimeConfig()

const academies = ref<any[]>([])
const isLoading = ref(true)
const page = ref(1)
const hasMore = ref(false)

const fetchMemberedAcademies = async (append = false) => {
  if (!user.value) return

  try {
    const response: any = await api.get(`/api/academies/users/${user.value.id}/membered-academies`, {
      params: { page: page.value, per_page: 5 }
    })

    if (response.success) {
      const newAcademies = response.academies?.data || response.academies || []
      // Deep clone to fix prototype chain for Pinia serialization
      if (append) {
        academies.value = [...academies.value, ...JSON.parse(JSON.stringify(newAcademies))]
      } else {
        academies.value = JSON.parse(JSON.stringify(newAcademies.slice(0, 5)))
      }
      
      // Check if there's pagination info
      if (response.academies?.current_page && response.academies?.last_page) {
        hasMore.value = response.academies.current_page < response.academies.last_page
      }
    }
  } catch (error) {
    console.error('Failed to fetch membered academies:', error)
  } finally {
    isLoading.value = false
  }
}

const loadMore = () => {
  page.value++
  fetchMemberedAcademies(true)
}

const getLogoUrl = (academy: any) => {
  if (academy.logo) {
    if (academy.logo.startsWith('http')) return academy.logo
    return academy.logo
  }
  return `${config.public.apiBase}/storage/images/academies/logos/default_logo.png`
}

const getMemberStatusLabel = (status: string | number) => {
  const statusMap: Record<string | number, { label: string; color: string }> = {
    1: { label: 'สมาชิก', color: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' },
    0: { label: 'รอการอนุมัติ', color: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400' },
    'pending': { label: 'รอการอนุมัติ', color: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400' },
    'approved': { label: 'สมาชิก', color: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' },
  }
  return statusMap[status] || { label: 'สมาชิก', color: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }
}

// Only fetch on client side to avoid SSR serialization issues
onMounted(() => {
  if (user.value) {
    fetchMemberedAcademies()
  }
})

// Watch for user changes (client-side only)
watch(user, (newUser) => {
  if (import.meta.client && newUser && academies.value.length === 0) {
    fetchMemberedAcademies()
  }
})
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center">
          <Icon icon="fluent:building-24-regular" class="w-4 h-4 text-white" />
        </div>
        <h3 class="font-bold text-gray-800 dark:text-white">โรงเรียนที่สังกัด</h3>
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
      <template v-if="isLoading && academies.length === 0">
        <div v-for="i in 3" :key="i" class="p-4 flex gap-3 animate-pulse">
          <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
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
          class="p-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors group"
        >
          <!-- Logo -->
          <div class="relative w-12 h-12 flex-shrink-0">
            <div class="absolute inset-0 rounded-full border-2 border-vikinger-purple"></div>
            <div class="absolute inset-[2px] rounded-full overflow-hidden bg-gray-100">
              <img 
                :src="getLogoUrl(academy)" 
                :alt="academy.name"
                class="w-full h-full object-cover"
              />
            </div>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-white line-clamp-1 group-hover:text-vikinger-purple transition-colors">
              {{ academy.name }}
            </h4>
            
            <div class="flex items-center gap-2 mt-1">
              <span 
                class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full"
                :class="getMemberStatusLabel(academy.memberStatus).color"
              >
                {{ getMemberStatusLabel(academy.memberStatus).label }}
              </span>
              <span v-if="academy.address" class="text-xs text-gray-500 dark:text-gray-400 truncate">
                {{ academy.address }}
              </span>
            </div>
          </div>

          <!-- Arrow -->
          <Icon 
            icon="fluent:chevron-right-24-regular" 
            class="w-4 h-4 text-gray-400 group-hover:text-vikinger-purple transition-colors flex-shrink-0" 
          />
        </NuxtLink>
      </template>

      <!-- Empty State -->
      <div v-else class="p-6 text-center">
        <Icon icon="fluent:building-multiple-24-regular" class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" />
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">ยังไม่ได้สังกัดโรงเรียนใด</p>
        <NuxtLink 
          to="/academies" 
          class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-vikinger-purple to-vikinger-cyan rounded-full hover:opacity-90 transition-opacity"
        >
          <Icon icon="fluent:search-24-regular" class="w-4 h-4" />
          ค้นหาโรงเรียน
        </NuxtLink>
      </div>
    </div>

    <!-- Load More -->
    <div v-if="hasMore && academies.length > 0" class="p-3 border-t border-gray-100 dark:border-gray-700">
      <button 
        @click="loadMore"
        class="w-full py-2 text-sm text-vikinger-purple hover:bg-vikinger-purple/10 rounded-lg transition-colors"
      >
        โหลดเพิ่มเติม
      </button>
    </div>
  </div>
</template>

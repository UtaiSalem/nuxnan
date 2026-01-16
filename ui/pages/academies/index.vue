<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

const api = useApi()
const config = useRuntimeConfig()
const { user } = storeToRefs(useAuthStore())

// State
const allAcademies = ref<any[]>([])
const myAcademies = ref<any[]>([])
const isLoading = ref(true)
const searchQuery = ref('')
const currentView = ref<'all' | 'my'>('all')
const page = ref(1)
const hasMore = ref(false)

// Computed
const filteredAcademies = computed(() => {
  const list = currentView.value === 'all' ? allAcademies.value : myAcademies.value
  if (!searchQuery.value.trim()) return list
  
  const query = searchQuery.value.toLowerCase()
  return list.filter(academy => 
    academy.name?.toLowerCase().includes(query) ||
    academy.slogan?.toLowerCase().includes(query) ||
    academy.address?.toLowerCase().includes(query)
  )
})

// Methods
const fetchAllAcademies = async () => {
  if (!user.value) return
  
  isLoading.value = true
  try {
    const response: any = await api.get('/api/academies/all-academies', {
      params: { per_page: 20, page: page.value }
    })
    
    if (response.success) {
      const academies = response.academies?.data || response.academies || []
      allAcademies.value = JSON.parse(JSON.stringify(academies))
      
      if (response.academies?.current_page && response.academies?.last_page) {
        hasMore.value = response.academies.current_page < response.academies.last_page
      }
    }
  } catch (error) {
    console.error('Failed to fetch academies:', error)
  } finally {
    isLoading.value = false
  }
}

const fetchMyAcademies = async () => {
  if (!user.value) return
  
  try {
    const response: any = await api.get(`/api/academies/users/${user.value.id}/membered-academies`, {
      params: { per_page: 20 }
    })
    
    if (response.success) {
      const academies = response.academies?.data || response.academies || []
      myAcademies.value = JSON.parse(JSON.stringify(academies))
    }
  } catch (error) {
    console.error('Failed to fetch my academies:', error)
  }
}

const getLogoUrl = (academy: any) => {
  if (!academy.logo) {
    return `${config.public.apiBase}/storage/images/academies/logos/default_logo.png`
  }
  if (academy.logo.startsWith('http')) {
    return academy.logo
  }
  return academy.logo
}

const getAcademyTypeInfo = (type: string | null) => {
  const typeMap: Record<string, { label: string; icon: string; color: string; bg: string }> = {
    'public': { label: 'รัฐบาล', icon: 'fluent:building-government-24-regular', color: 'text-blue-600', bg: 'bg-blue-100 dark:bg-blue-900/30' },
    'private': { label: 'เอกชน', icon: 'fluent:building-bank-24-regular', color: 'text-purple-600', bg: 'bg-purple-100 dark:bg-purple-900/30' },
    'foundation': { label: 'มูลนิธิ', icon: 'fluent:heart-24-regular', color: 'text-pink-600', bg: 'bg-pink-100 dark:bg-pink-900/30' },
    'international': { label: 'นานาชาติ', icon: 'fluent:globe-24-regular', color: 'text-green-600', bg: 'bg-green-100 dark:bg-green-900/30' },
  }
  return typeMap[type || ''] || { label: 'ทั่วไป', icon: 'fluent:building-24-regular', color: 'text-gray-600', bg: 'bg-gray-100 dark:bg-gray-700' }
}

const switchView = (view: 'all' | 'my') => {
  currentView.value = view
  if (view === 'my' && myAcademies.value.length === 0) {
    fetchMyAcademies()
  }
}

// Lifecycle
onMounted(() => {
  if (user.value) {
    fetchAllAcademies()
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-200 dark:bg-vikinger-dark-300">
    <div class="max-w-7xl mx-auto px-4 py-6">
      <!-- Header -->
      <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center">
                <Icon icon="fluent:building-multiple-24-regular" class="w-5 h-5 text-white" />
              </div>
              โรงเรียนทั้งหมด
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">ค้นหาและเข้าร่วมโรงเรียนที่คุณสนใจ</p>
          </div>
          
          <!-- Search -->
          <div class="relative w-full md:w-80">
            <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input 
              v-model="searchQuery"
              type="text"
              placeholder="ค้นหาโรงเรียน..."
              class="w-full pl-10 pr-4 py-2.5 bg-gray-100 dark:bg-vikinger-dark-100 border-0 rounded-lg focus:ring-2 focus:ring-vikinger-purple text-gray-900 dark:text-white"
            />
          </div>
        </div>
        
        <!-- Tabs -->
        <div class="flex gap-2 mt-6">
          <button
            @click="switchView('all')"
            :class="[
              'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
              currentView === 'all' 
                ? 'bg-vikinger-purple text-white' 
                : 'bg-gray-100 dark:bg-vikinger-dark-100 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-vikinger-dark-300'
            ]"
          >
            <Icon icon="fluent:building-multiple-24-regular" class="w-4 h-4 inline mr-1.5" />
            โรงเรียนทั้งหมด
          </button>
          <button
            @click="switchView('my')"
            :class="[
              'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
              currentView === 'my' 
                ? 'bg-vikinger-purple text-white' 
                : 'bg-gray-100 dark:bg-vikinger-dark-100 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-vikinger-dark-300'
            ]"
          >
            <Icon icon="fluent:building-24-regular" class="w-4 h-4 inline mr-1.5" />
            โรงเรียนที่สังกัด
          </button>
        </div>
      </div>
      
      <!-- Loading -->
      <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="i in 6" :key="i" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-5 animate-pulse">
          <div class="flex items-start gap-4">
            <div class="w-16 h-16 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
            <div class="flex-1 space-y-3">
              <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
              <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Academy Grid -->
      <div v-else-if="filteredAcademies.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <NuxtLink
          v-for="academy in filteredAcademies"
          :key="academy.id"
          :to="`/academies/${encodeURIComponent(academy.name)}`"
          class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm hover:shadow-lg transition-all group overflow-hidden"
        >
          <!-- Cover -->
          <div 
            class="h-24 bg-gray-200 dark:bg-gray-700 bg-cover bg-center"
            :style="{ backgroundImage: academy.cover ? `url(${academy.cover})` : 'none' }"
          >
            <div class="w-full h-full bg-gradient-to-br from-vikinger-purple/30 to-vikinger-cyan/30"></div>
          </div>
          
          <!-- Content -->
          <div class="p-5 -mt-8 relative">
            <!-- Logo -->
            <div class="w-14 h-14 rounded-xl border-2 border-white dark:border-vikinger-dark-200 shadow-lg overflow-hidden bg-white mb-3">
              <img 
                :src="getLogoUrl(academy)" 
                :alt="academy.name"
                class="w-full h-full object-cover"
              />
            </div>
            
            <!-- Info -->
            <h3 class="font-bold text-gray-900 dark:text-white group-hover:text-vikinger-purple transition-colors line-clamp-1 mb-1">
              {{ academy.name }}
            </h3>
            
            <p v-if="academy.slogan" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 mb-3">
              {{ academy.slogan }}
            </p>
            
            <!-- Meta -->
            <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
              <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded-full', getAcademyTypeInfo(academy.type).bg, getAcademyTypeInfo(academy.type).color]">
                <Icon :icon="getAcademyTypeInfo(academy.type).icon" class="w-3.5 h-3.5" />
                {{ getAcademyTypeInfo(academy.type).label }}
              </span>
              <span class="flex items-center gap-1">
                <Icon icon="fluent:people-24-regular" class="w-3.5 h-3.5" />
                {{ academy.total_students || 0 }}
              </span>
            </div>
          </div>
        </NuxtLink>
      </div>
      
      <!-- Empty State -->
      <div v-else class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-12 text-center">
        <Icon icon="fluent:building-multiple-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
          {{ currentView === 'my' ? 'ยังไม่ได้สังกัดโรงเรียน' : 'ไม่พบโรงเรียน' }}
        </h3>
        <p class="text-gray-600 dark:text-gray-400">
          {{ currentView === 'my' ? 'เข้าร่วมโรงเรียนเพื่อเริ่มต้นการเรียนรู้' : 'ลองค้นหาด้วยคำค้นอื่น' }}
        </p>
      </div>
    </div>
  </div>
</template>

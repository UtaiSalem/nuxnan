<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

useHead({
  title: 'โรงเรียนทั้งหมด - Nuxnan',
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
  return `${config.public.apiBase}/storage/images/academies/logos/${academy.logo}`
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
  <NuxtLayout name="main">
    <!-- Header Hero Section -->
    <template #hero>
      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-vikinger-purple to-vikinger-cyan p-6 md:p-10 text-white shadow-xl">
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <h1 class="text-2xl md:text-4xl font-black mb-2 flex items-center gap-3">
              <Icon icon="fluent:building-multiple-24-filled" class="w-8 h-8 md:w-10 md:h-10 text-white/90" />
              สถาบันการศึกษา
            </h1>
            <p class="text-white/80 font-medium text-sm md:text-base">
              ค้นหาโรงเรียน สถาบัน หรือแหล่งเรียนรู้คุณภาพที่เข้าร่วมกับเรา
            </p>
          </div>
          
          <div class="relative w-full md:w-80 group">
            <Icon icon="fluent:search-24-regular" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/60 group-focus-within:text-white transition-colors" />
            <input 
              v-model="searchQuery"
              type="text"
              placeholder="ค้นหาชื่อโรงเรียน..."
              class="w-full pl-11 pr-4 py-3 bg-white/10 backdrop-blur-md border border-white/20 rounded-xl focus:ring-2 focus:ring-white/50 text-white placeholder-white/50 focus:bg-white/20 outline-none transition-all"
            />
          </div>
        </div>
      </div>
    </template>

    <!-- Main Content -->
    <div class="space-y-6">
      <!-- Filter Tabs -->
      <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 p-1.5 flex gap-2 w-full md:w-fit">
        <button
          @click="switchView('all')"
          :class="[
            'flex-1 md:flex-none px-6 py-2.5 rounded-lg text-sm font-black transition-all flex items-center justify-center gap-2',
            currentView === 'all' 
              ? 'bg-vikinger-purple text-white shadow-md' 
              : 'text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-vikinger-dark-100'
          ]"
        >
          <Icon icon="fluent:building-multiple-24-filled" class="w-4 h-4" />
          โรงเรียนทั้งหมด
        </button>
        <button
          @click="switchView('my')"
          :class="[
            'flex-1 md:flex-none px-6 py-2.5 rounded-lg text-sm font-black transition-all flex items-center justify-center gap-2',
            currentView === 'my' 
              ? 'bg-vikinger-purple text-white shadow-md' 
              : 'text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-vikinger-dark-100'
          ]"
        >
          <Icon icon="fluent:building-24-filled" class="w-4 h-4" />
          โรงเรียนของฉัน
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <div v-for="i in 6" :key="i" class="bg-white dark:bg-vikinger-dark-200 rounded-2xl overflow-hidden shadow-sm animate-pulse h-64">
           <div class="h-24 bg-gray-100 dark:bg-vikinger-dark-100"></div>
           <div class="p-5 space-y-3">
              <div class="w-14 h-14 rounded-xl bg-gray-200 dark:bg-vikinger-dark-50 -mt-12 border-2 border-white dark:border-vikinger-dark-200"></div>
              <div class="h-4 bg-gray-100 dark:bg-vikinger-dark-50 rounded w-3/4"></div>
              <div class="h-3 bg-gray-100 dark:bg-vikinger-dark-50 rounded w-1/2"></div>
           </div>
        </div>
      </div>

      <!-- Grid Content -->
      <div v-else-if="filteredAcademies.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <NuxtLink
          v-for="academy in filteredAcademies"
          :key="academy.id"
          :to="`/academies/${encodeURIComponent(academy.name)}`"
          class="bg-white dark:bg-vikinger-dark-200 rounded-2xl shadow-sm hover:shadow-xl transition-all group overflow-hidden border border-gray-100 dark:border-vikinger-dark-100"
        >
          <!-- Cover Image -->
          <div 
            class="h-24 bg-gray-100 dark:bg-vikinger-dark-100 bg-cover bg-center relative"
            :style="{ backgroundImage: academy.cover ? `url(${academy.cover})` : 'none' }"
          >
            <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
          </div>
          
          <!-- Card Body -->
          <div class="p-5 pt-0 relative">
            <!-- Logo Overlap -->
            <div class="w-14 h-14 rounded-xl border-4 border-white dark:border-vikinger-dark-200 shadow-lg overflow-hidden bg-white mb-3 -mt-7 relative z-10">
              <img 
                :src="getLogoUrl(academy)" 
                :alt="academy.name"
                class="w-full h-full object-cover"
              />
            </div>
            
            <!-- Academy Info -->
            <h3 class="font-black text-gray-900 dark:text-white group-hover:text-vikinger-purple transition-colors truncate mb-1">
              {{ academy.name }}
            </h3>
            
            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 min-h-[2rem] mb-4">
              {{ academy.slogan || 'ไม่มีคำขวัญ' }}
            </p>
            
            <!-- Footer Meta -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-50 dark:border-vikinger-dark-50">
              <div :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider', getAcademyTypeInfo(academy.type).bg, getAcademyTypeInfo(academy.type).color]">
                <Icon :icon="getAcademyTypeInfo(academy.type).icon" class="w-3.5 h-3.5" />
                {{ getAcademyTypeInfo(academy.type).label }}
              </div>
              
              <div class="flex items-center gap-1 text-[11px] font-bold text-gray-400 dark:text-gray-500">
                <Icon icon="fluent:people-community-24-filled" class="w-4 h-4" />
                {{ formatNumber(academy.total_students || 0) }}
              </div>
            </div>
          </div>
        </NuxtLink>
      </div>

      <!-- Empty State -->
      <div v-else class="bg-white dark:bg-vikinger-dark-200 rounded-2xl p-16 text-center border border-dashed border-gray-200 dark:border-vikinger-dark-100">
        <Icon icon="fluent:building-search-24-regular" class="w-20 h-20 text-gray-200 dark:text-gray-700 mx-auto mb-4" />
        <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">
          {{ currentView === 'my' ? 'ยังไม่ได้เข้าเป็นสมาชิกสถาบันใด' : 'ไม่พบข้อมูลสถาบัน' }}
        </h3>
        <p class="text-gray-500 dark:text-gray-400">
          {{ currentView === 'my' ? 'ลองค้นหาและเข้าร่วมสถาบันเพื่อเริ่มต้นการเรียนรู้' : 'ลองเปลี่ยนคำค้นหาหรือดูในหมวดหมู่อื่น' }}
        </p>
      </div>
    </div>
  </NuxtLayout>
</template>

<style scoped>
.shadow-vikinger {
  box-shadow: 0 10px 25px -5px rgba(111, 66, 193, 0.3), 0 8px 10px -6px rgba(111, 66, 193, 0.1);
}
</style>

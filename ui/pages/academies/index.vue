<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import MyCoursesWidget from '~/components/widgets/MyCoursesWidget.vue'
import MemberedCoursesWidget from '~/components/widgets/MemberedCoursesWidget.vue'

definePageMeta({
  layout: false,
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

// Academy Types for filter
const academyTypes = [
  { value: 'all', label: 'ทุกประเภท' },
  { value: 'public', label: 'รัฐบาล' },
  { value: 'private', label: 'เอกชน' },
  { value: 'foundation', label: 'มูลนิธิ' },
  { value: 'international', label: 'นานาชาติ' },
]
const selectedType = ref('all')

// Computed
const filteredAcademies = computed(() => {
  const list = currentView.value === 'all' ? allAcademies.value : myAcademies.value
  
  return list.filter(academy => {
    const matchesSearch = !searchQuery.value.trim() || 
      academy.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      academy.slogan?.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const matchesType = selectedType.value === 'all' || academy.type === selectedType.value
    
    return matchesSearch && matchesType
  })
})

// Methods
const formatNumber = (n?: number) => (n ?? 0).toLocaleString('th-TH')

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
    fetchMyAcademies()
  }
})
</script>

<template>
  <div>
    <NuxtLayout name="main">
    <!-- Hero Banner -->
    <template #hero>
      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-500 shadow-xl">
        <div class="absolute inset-0 opacity-20">
          <div class="absolute inset-0" style="background-image: url('/images/resources/animate-bg.png'); background-size: cover;"></div>
        </div>
        
        <div class="absolute left-6 top-1/2 -translate-y-1/2 hidden md:block">
          <Icon icon="fluent:building-multiple-24-filled" class="w-20 h-20 text-white/20 animate-pulse" />
        </div>
        
        <div class="relative z-10 px-8 py-10 md:ml-32">
          <h1 class="text-2xl md:text-3xl font-black text-white mb-2 flex items-center gap-3">
             <Icon icon="mdi:school" class="w-8 h-8 md:hidden" />
             สถาบันการศึกษา
          </h1>
          <p class="text-blue-50 font-medium text-sm md:text-base">
            ค้นหาโรงเรียน สถาบัน หรือแหล่งเรียนรู้คุณภาพที่เข้าร่วมกับเรา
          </p>
        </div>

        <div class="hidden md:flex absolute right-10 top-1/2 -translate-y-1/2 items-center gap-6">
           <div class="p-3 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-lg animate-bounce" style="animation-duration: 3s;">
             <Icon icon="fluent:hat-graduation-24-filled" class="w-10 h-10 text-white" />
           </div>
           <div class="p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-lg animate-bounce" style="animation-duration: 2.5s; animation-delay: 0.2s;">
             <Icon icon="fluent:building-24-filled" class="w-12 h-12 text-white" />
           </div>
        </div>
      </div>
    </template>

    <!-- Left Sidebar Slots -->
    <template #leftWidgets>
      <!-- Search & Filter Widget -->
      <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-vikinger-dark-100 bg-gray-50/50 dark:bg-vikinger-dark-50/50">
          <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:filter-24-filled" class="w-5 h-5 text-vikinger-purple" />
            ค้นหาและตัวกรอง
          </h3>
        </div>
        <div class="p-4 space-y-4">
          <!-- Search -->
          <div class="space-y-2">
            <label class="text-xs font-black text-gray-500 uppercase tracking-wider">ชื่อสถาบัน</label>
            <div class="relative">
              <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
              <input 
                v-model="searchQuery"
                type="text"
                placeholder="ค้นหา..."
                class="w-full pl-9 pr-3 py-2 bg-gray-50 dark:bg-vikinger-dark-100 border border-gray-100 dark:border-vikinger-dark-50 rounded-lg text-sm focus:ring-2 focus:ring-vikinger-purple/20 focus:border-vikinger-purple outline-none transition-all dark:text-white"
              />
            </div>
          </div>

          <!-- Type Filter -->
          <div class="space-y-2">
            <label class="text-xs font-black text-gray-500 uppercase tracking-wider">ประเภทสถาบัน</label>
            <select 
              v-model="selectedType"
              class="w-full px-3 py-2 bg-gray-50 dark:bg-vikinger-dark-100 border border-gray-100 dark:border-vikinger-dark-50 rounded-lg text-sm focus:ring-2 focus:ring-vikinger-purple/20 focus:border-vikinger-purple outline-none transition-all dark:text-white appearance-none cursor-pointer"
            >
              <option v-for="type in academyTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
            </select>
          </div>

          <!-- View Switcher (My vs All) -->
          <div class="pt-2">
             <div class="flex p-1 bg-gray-100 dark:bg-vikinger-dark-100 rounded-lg">
                <button 
                  @click="switchView('all')"
                  class="flex-1 px-2 py-1.5 rounded-md text-[11px] font-bold transition-all"
                  :class="currentView === 'all' ? 'bg-white dark:bg-vikinger-dark-50 shadow-sm text-vikinger-purple' : 'text-gray-500'"
                >
                  ทั้งหมด
                </button>
                <button 
                  @click="switchView('my')"
                  class="flex-1 px-2 py-1.5 rounded-md text-[11px] font-bold transition-all"
                  :class="currentView === 'my' ? 'bg-white dark:bg-vikinger-dark-50 shadow-sm text-vikinger-purple' : 'text-gray-500'"
                >
                  ของฉัน
                </button>
             </div>
          </div>
        </div>
      </div>

      <!-- Membered Academies Mini Widget -->
      <div v-if="myAcademies.length > 0" class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-vikinger-dark-100">
          <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2 text-sm">
            <Icon icon="fluent:building-24-filled" class="w-4 h-4 text-blue-500" />
            โรงเรียนที่ฉันเป็นสมาชิก
          </h3>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-vikinger-dark-50">
          <NuxtLink
            v-for="academy in myAcademies.slice(0, 5)"
            :key="academy.id"
            :to="`/academies/${encodeURIComponent(academy.name)}`"
            class="p-3 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"
          >
            <img :src="getLogoUrl(academy)" class="w-8 h-8 rounded-lg object-cover" />
            <div class="min-w-0 flex-1">
              <h4 class="text-xs font-bold truncate dark:text-white">{{ academy.name }}</h4>
              <p class="text-[10px] text-gray-500">{{ getAcademyTypeInfo(academy.type).label }}</p>
            </div>
          </NuxtLink>
        </div>
      </div>
    </template>

    <!-- Right Sidebar Slots -->
    <template #rightWidgets>
      <MemberedCoursesWidget />
      <MyCoursesWidget />
    </template>

    <!-- Main Content -->
    <div class="min-w-0">
      <!-- Sorting & Info Bar -->
      <div class="flex items-center justify-between mb-6 px-1">
        <div class="flex items-center gap-2">
          <div class="bg-vikinger-purple/10 text-vikinger-purple px-3 py-1 rounded-full text-xs font-black">
            {{ formatNumber(filteredAcademies.length) }} สถาบัน
          </div>
          <div v-if="searchQuery" class="text-xs font-bold text-gray-400">
            ผลการค้นหา: "{{ searchQuery }}"
          </div>
        </div>
        <div class="flex items-center gap-2">
          <!-- Optional secondary actions -->
        </div>
      </div>

      <!-- Loading State -->
      <template v-if="isLoading">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
          <div v-for="i in 6" :key="i" class="bg-white dark:bg-vikinger-dark-200 rounded-2xl overflow-hidden shadow-sm animate-pulse h-64">
             <div class="h-24 bg-gray-100 dark:bg-vikinger-dark-100"></div>
             <div class="p-5 space-y-3">
                <div class="w-14 h-14 rounded-xl bg-gray-200 dark:bg-vikinger-dark-50 -mt-12 border-2 border-white dark:border-vikinger-dark-200"></div>
                <div class="h-4 bg-gray-100 dark:bg-vikinger-dark-50 rounded w-3/4"></div>
                <div class="h-3 bg-gray-100 dark:bg-vikinger-dark-50 rounded w-1/2"></div>
             </div>
          </div>
        </div>
      </template>

      <!-- Grid Content -->
      <div v-else-if="filteredAcademies.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
        <NuxtLink
          v-for="academy in filteredAcademies"
          :key="academy.id"
          :to="`/academies/${encodeURIComponent(academy.name)}`"
          class="bg-white dark:bg-vikinger-dark-200 rounded-2xl shadow-sm hover:shadow-xl transition-all group overflow-hidden border border-gray-100 dark:border-vikinger-dark-100 flex flex-col"
        >
          <!-- Cover Image -->
          <div 
            class="h-28 bg-gray-100 dark:bg-vikinger-dark-100 bg-cover bg-center relative"
            :style="{ backgroundImage: academy.cover ? `url(${academy.cover})` : 'none' }"
          >
            <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
            <!-- Type Badge Overlap -->
            <div class="absolute top-3 right-3">
               <div :class="['inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider shadow-lg backdrop-blur-md', getAcademyTypeInfo(academy.type).bg, getAcademyTypeInfo(academy.type).color]">
                <Icon :icon="getAcademyTypeInfo(academy.type).icon" class="w-3.5 h-3.5" />
                {{ getAcademyTypeInfo(academy.type).label }}
              </div>
            </div>
          </div>
          
          <!-- Card Body -->
          <div class="p-5 pt-0 relative flex-1 flex flex-col">
            <!-- Logo Overlap -->
            <div class="w-16 h-16 rounded-2xl border-4 border-white dark:border-vikinger-dark-200 shadow-xl overflow-hidden bg-white mb-3 -mt-8 relative z-10 transition-transform group-hover:scale-105">
              <img 
                :src="getLogoUrl(academy)" 
                :alt="academy.name"
                class="w-full h-full object-cover"
              />
            </div>
            
            <!-- Academy Info -->
            <h3 class="font-black text-gray-900 dark:text-white group-hover:text-vikinger-purple transition-colors text-lg mb-1 leading-tight">
              {{ academy.name }}
            </h3>
            
            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 min-h-[2.5rem] mb-4 leading-relaxed">
              {{ academy.slogan || 'สถาบันการศึกษาคุณภาพที่มุ่งเน้นการพัฒนาผู้เรียนอย่างรอบด้านในยุคดิจิทัล' }}
            </p>
            
            <!-- Footer Meta -->
            <div class="mt-auto pt-4 border-t border-gray-50 dark:border-vikinger-dark-50 flex items-center justify-between">
              <div class="flex items-center gap-3">
                 <div class="flex flex-col">
                   <span class="text-[10px] font-black text-gray-400 uppercase tracking-tighter">นักเรียน</span>
                   <span class="text-sm font-black text-vikinger-purple flex items-center gap-1">
                     <Icon icon="fluent:people-community-24-filled" class="w-4 h-4" />
                     {{ formatNumber(academy.total_students || 0) }}
                   </span>
                 </div>
                 <div class="w-px h-8 bg-gray-100 dark:bg-vikinger-dark-50"></div>
                 <div class="flex flex-col">
                   <span class="text-[10px] font-black text-gray-400 uppercase tracking-tighter">รายวิชา</span>
                   <span class="text-sm font-black text-blue-500 flex items-center gap-1">
                     <Icon icon="fluent:book-24-filled" class="w-4 h-4" />
                     {{ formatNumber(academy.courses_count || 0) }}
                   </span>
                 </div>
              </div>

              <div class="w-8 h-8 rounded-full bg-gray-50 dark:bg-vikinger-dark-100 flex items-center justify-center text-gray-300 group-hover:bg-vikinger-purple group-hover:text-white transition-all">
                <Icon icon="fluent:chevron-right-24-filled" class="w-4 h-4" />
              </div>
            </div>
          </div>
        </NuxtLink>
      </div>

      <!-- Empty State -->
      <div v-else class="bg-white dark:bg-vikinger-dark-200 rounded-2xl p-16 text-center border border-dashed border-gray-200 dark:border-vikinger-dark-100 shadow-sm">
        <Icon icon="fluent:building-search-24-regular" class="w-20 h-20 text-gray-200 dark:text-gray-700 mx-auto mb-4" />
        <h3 class="text-xl font-black text-gray-900 dark:text-white mb-2">
          {{ currentView === 'my' ? 'ยังไม่ได้เข้าเป็นสมาชิกสถาบันใด' : 'ไม่พบข้อมูลสถาบัน' }}
        </h3>
        <p class="text-gray-500 dark:text-gray-400 text-sm">
          {{ currentView === 'my' ? 'ลองค้นหาและเข้าร่วมสถาบันเพื่อเริ่มต้นการเรียนรู้' : 'ลองเปลี่ยนคำค้นหาหรือปรับเปลี่ยนตัวกรอง' }}
        </p>
        <button 
          @click="searchQuery = ''; selectedType = 'all'; currentView = 'all'"
          class="mt-6 px-6 py-2 bg-gradient-vikinger text-white rounded-xl font-bold text-sm shadow-vikinger hover:scale-105 transition-all"
        >
          ล้างตัวกรองทั้งหมด
        </button>
      </div>
    </div>
  </NuxtLayout>
  </div>
  </template>
<style scoped>
.bg-gradient-vikinger {
  background: linear-gradient(135deg, #8B5CF6 0%, #06B6D4 100%);
}
.shadow-vikinger {
  box-shadow: 0 10px 25px -5px rgba(111, 66, 193, 0.3), 0 8px 10px -6px rgba(111, 66, 193, 0.1);
}
</style>


<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'

const props = defineProps<{
  userId?: string | number
  isOwnProfile?: boolean
}>()

const api = useApi()

// Types
interface Event {
  id: number
  title: string
  slug: string
  description?: string
  cover_image?: string
  start_date: string
  end_date?: string
  location?: string
  is_online: boolean
  attendees_count: number
  status: 'going' | 'interested' | 'not_going' | 'hosting'
  category: string
  price?: number
}

// State
const events = ref<Event[]>([])
const isLoading = ref(false)
const selectedFilter = ref<'upcoming' | 'past' | 'hosting'>('upcoming')

// Computed
const filteredEvents = computed(() => {
  const now = new Date()
  
  if (selectedFilter.value === 'upcoming') {
    return events.value.filter(e => new Date(e.start_date) >= now)
  }
  if (selectedFilter.value === 'past') {
    return events.value.filter(e => new Date(e.start_date) < now)
  }
  return events.value.filter(e => e.status === 'hosting')
})

// Fetch events
const fetchEvents = async () => {
  isLoading.value = true
  
  try {
    const endpoint = props.userId 
      ? `/api/users/${props.userId}/events`
      : `/api/profile/events`
    
    const response = await api.get(endpoint) as {
      success: boolean
      data?: Event[]
      events?: Event[]
    }
    
    if (response.success) {
      events.value = response.data || response.events || []
    } else {
      events.value = getMockEvents()
    }
  } catch (error) {
    console.error('Error fetching events:', error)
    events.value = getMockEvents()
  } finally {
    isLoading.value = false
  }
}

// Mock data
const getMockEvents = (): Event[] => {
  const now = new Date()
  const future1 = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000)
  const future2 = new Date(now.getTime() + 14 * 24 * 60 * 60 * 1000)
  const past1 = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)
  
  return [
    {
      id: 1,
      title: 'Nuxt.js Workshop Thailand 2024',
      slug: 'nuxtjs-workshop-2024',
      description: 'เวิร์คช็อปสร้างเว็บไซต์ด้วย Nuxt.js 3',
      cover_image: 'https://picsum.photos/400/200?random=10',
      start_date: future1.toISOString(),
      location: 'Online via Zoom',
      is_online: true,
      attendees_count: 128,
      status: 'going',
      category: 'Technology',
      price: 0
    },
    {
      id: 2,
      title: 'Tech Meetup Bangkok',
      slug: 'tech-meetup-bangkok',
      description: 'พบปะนักพัฒนาจากทั่วประเทศ',
      cover_image: 'https://picsum.photos/400/200?random=11',
      start_date: future2.toISOString(),
      location: 'True Digital Park, กรุงเทพฯ',
      is_online: false,
      attendees_count: 256,
      status: 'interested',
      category: 'Networking'
    },
    {
      id: 3,
      title: 'Web Development Basics',
      slug: 'web-dev-basics',
      description: 'คอร์สพื้นฐานการพัฒนาเว็บไซต์',
      cover_image: 'https://picsum.photos/400/200?random=12',
      start_date: past1.toISOString(),
      location: 'Online',
      is_online: true,
      attendees_count: 89,
      status: 'hosting',
      category: 'Education'
    }
  ]
}

// Format date
const formatEventDate = (dateStr: string) => {
  const date = new Date(dateStr)
  const options: Intl.DateTimeFormatOptions = {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }
  return date.toLocaleDateString('th-TH', options)
}

// Status badges
const statusConfig = {
  going: { label: 'จะไป', color: 'bg-green-500/20 text-green-400 border-green-500/30' },
  interested: { label: 'สนใจ', color: 'bg-amber-500/20 text-amber-400 border-amber-500/30' },
  not_going: { label: 'ไม่ไป', color: 'bg-gray-500/20 text-gray-400 border-gray-500/30' },
  hosting: { label: 'เป็นผู้จัด', color: 'bg-vikinger-purple/20 text-vikinger-purple border-vikinger-purple/30' }
}

// Check if past
const isPastEvent = (dateStr: string) => {
  return new Date(dateStr) < new Date()
}

// Navigate to event
const goToEvent = (event: Event) => {
  navigateTo(`/events/${event.slug}`)
}

// Initialize
onMounted(() => {
  fetchEvents()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <BaseCard class="bg-gray-800 border-gray-700 p-5">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div>
          <h3 class="text-xl font-bold text-white flex items-center gap-2">
            <Icon icon="fluent:calendar-24-filled" class="w-6 h-6 text-vikinger-cyan" />
            กิจกรรม
            <span class="text-vikinger-cyan font-normal ml-1">({{ events.length }})</span>
          </h3>
          <p class="text-sm text-gray-400 mt-1">กิจกรรมที่เข้าร่วมและสนใจ</p>
        </div>
        
        <!-- Create Event Button (Own Profile) -->
        <NuxtLink
          v-if="isOwnProfile"
          to="/events/create"
          class="px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2"
        >
          <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
          สร้างกิจกรรม
        </NuxtLink>
      </div>

      <!-- Filter Tabs -->
      <div class="flex gap-2 border-b border-gray-700 pb-3">
        <button
          v-for="filter in [
            { key: 'upcoming', label: 'กำลังจะมา', icon: 'fluent:arrow-trending-24-regular' },
            { key: 'past', label: 'ที่ผ่านมา', icon: 'fluent:history-24-regular' },
            { key: 'hosting', label: 'ที่จัด', icon: 'fluent:star-24-regular' },
          ]"
          :key="filter.key"
          @click="selectedFilter = filter.key as typeof selectedFilter"
          :class="[
            'px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2',
            selectedFilter === filter.key
              ? 'bg-vikinger-purple/20 text-vikinger-purple border border-vikinger-purple/30'
              : 'text-gray-400 hover:text-white hover:bg-gray-700'
          ]"
        >
          <Icon :icon="filter.icon" class="w-4 h-4" />
          {{ filter.label }}
        </button>
      </div>
    </BaseCard>

    <!-- Loading State -->
    <div v-if="isLoading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="animate-pulse">
        <BaseCard class="bg-gray-800 border-gray-700">
          <div class="flex gap-4 p-4">
            <div class="w-32 h-24 bg-gray-700 rounded-lg shrink-0"></div>
            <div class="flex-1 space-y-3">
              <div class="h-5 bg-gray-700 rounded w-3/4"></div>
              <div class="h-3 bg-gray-700 rounded w-1/2"></div>
              <div class="h-3 bg-gray-700 rounded w-1/3"></div>
            </div>
          </div>
        </BaseCard>
      </div>
    </div>

    <!-- Events List -->
    <div v-else-if="filteredEvents.length > 0" class="space-y-4">
      <BaseCard 
        v-for="event in filteredEvents" 
        :key="event.id"
        class="bg-gray-800 border-gray-700 overflow-hidden hover:border-vikinger-purple/50 transition-all cursor-pointer group"
        @click="goToEvent(event)"
      >
        <div class="flex flex-col sm:flex-row gap-4 p-4">
          <!-- Cover Image -->
          <div class="w-full sm:w-40 h-32 sm:h-28 bg-gradient-to-br from-vikinger-purple/30 to-vikinger-cyan/30 rounded-lg overflow-hidden shrink-0 relative">
            <img 
              v-if="event.cover_image" 
              :src="event.cover_image" 
              :alt="event.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
            
            <!-- Date Badge -->
            <div class="absolute top-2 left-2 bg-black/80 rounded-lg p-2 text-center min-w-[50px]">
              <div class="text-xs text-vikinger-cyan uppercase">
                {{ new Date(event.start_date).toLocaleDateString('th-TH', { month: 'short' }) }}
              </div>
              <div class="text-xl font-bold text-white">
                {{ new Date(event.start_date).getDate() }}
              </div>
            </div>

            <!-- Past Event Overlay -->
            <div 
              v-if="isPastEvent(event.start_date)"
              class="absolute inset-0 bg-black/60 flex items-center justify-center"
            >
              <span class="text-gray-300 text-sm font-medium">จบแล้ว</span>
            </div>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
              <h4 class="font-semibold text-white truncate group-hover:text-vikinger-cyan transition-colors">
                {{ event.title }}
              </h4>
              
              <!-- Status Badge -->
              <span 
                :class="[
                  'px-2 py-1 text-xs rounded-full border shrink-0',
                  statusConfig[event.status].color
                ]"
              >
                {{ statusConfig[event.status].label }}
              </span>
            </div>

            <p v-if="event.description" class="text-sm text-gray-400 line-clamp-2 mt-1">
              {{ event.description }}
            </p>
            
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 text-sm text-gray-500">
              <!-- Date & Time -->
              <span class="flex items-center gap-1">
                <Icon icon="fluent:clock-24-regular" class="w-4 h-4" />
                {{ formatEventDate(event.start_date) }}
              </span>
              
              <!-- Location -->
              <span class="flex items-center gap-1">
                <Icon 
                  :icon="event.is_online ? 'fluent:video-24-regular' : 'fluent:location-24-regular'" 
                  class="w-4 h-4" 
                />
                <span :class="event.is_online ? 'text-vikinger-cyan' : ''">
                  {{ event.location || (event.is_online ? 'Online' : 'ไม่ระบุ') }}
                </span>
              </span>
              
              <!-- Attendees -->
              <span class="flex items-center gap-1">
                <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
                {{ event.attendees_count }} คนเข้าร่วม
              </span>

              <!-- Price -->
              <span v-if="event.price !== undefined" class="flex items-center gap-1">
                <Icon icon="fluent:ticket-24-regular" class="w-4 h-4" />
                <span :class="event.price === 0 ? 'text-green-400' : 'text-vikinger-cyan'">
                  {{ event.price === 0 ? 'ฟรี' : `฿${event.price.toLocaleString()}` }}
                </span>
              </span>
            </div>

            <!-- Category -->
            <div class="mt-3">
              <span class="px-2 py-1 bg-gray-700 text-gray-300 text-xs rounded-full">
                {{ event.category }}
              </span>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Empty State -->
    <BaseCard v-else class="bg-gray-800 border-gray-700 text-center py-12">
      <Icon icon="fluent:calendar-24-regular" class="w-16 h-16 text-gray-600 mx-auto mb-4" />
      <p class="text-gray-400">
        {{ selectedFilter === 'upcoming' ? 'ไม่มีกิจกรรมที่กำลังจะมา' : 
           selectedFilter === 'past' ? 'ไม่มีกิจกรรมที่ผ่านมา' : 
           'ยังไม่เคยจัดกิจกรรม' }}
      </p>
      <p v-if="isOwnProfile" class="text-sm text-gray-500 mt-2">
        เริ่มเข้าร่วมกิจกรรมเพื่อพบปะผู้คนใหม่ๆ!
      </p>
      <NuxtLink 
        v-if="isOwnProfile" 
        to="/events"
        class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors"
      >
        <Icon icon="fluent:search-24-regular" class="w-5 h-5" />
        ค้นหากิจกรรม
      </NuxtLink>
    </BaseCard>
  </div>
</template>

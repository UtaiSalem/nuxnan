<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'

const props = defineProps<{
  userId?: string | number
  isOwnProfile?: boolean
}>()

const api = useApi()

// Types
interface ForumTopic {
  id: number
  title: string
  slug: string
  category: {
    id: number
    name: string
    color: string
  }
  author: {
    id: number
    name: string
    avatar?: string
  }
  replies_count: number
  views_count: number
  last_activity: string
  is_pinned: boolean
  is_solved: boolean
}

// State
const topics = ref<ForumTopic[]>([])
const isLoading = ref(false)
const selectedFilter = ref<'started' | 'replied'>('started')

// Filtered topics
const filteredTopics = computed(() => {
  // In a real app we'd probably filter by API request
  return topics.value
})

// Fetch topics
const fetchTopics = async () => {
  isLoading.value = true
  
  try {
    const endpoint = '/api/posts'
    const params = {
      post_type: 'DISCUSSION',
      user_id: props.userId,
      sort: selectedFilter.value === 'replied' ? 'replied' : 'latest'
    }
    
    // Attempt to fetch from API with fallback
    const response = await api.get(endpoint, { params }).catch(() => ({ success: false })) as any
    
    if (response && response.data) {
      topics.value = Array.isArray(response.data) ? response.data : (response.data.data || [])
    } else {
      topics.value = []
    }
  } catch (error) {
    console.error('Error fetching forum topics:', error)
    topics.value = []
  } finally {
    isLoading.value = false
  }
}

// Mock data
const getMockTopics = (): ForumTopic[] => [
  {
    id: 1,
    title: 'How to optimize Nuxt 3 performance?',
    slug: 'optimize-nuxt-3-performance',
    category: {
      id: 1,
      name: 'Help & Support',
      color: 'bg-blue-500'
    },
    author: {
      id: 1,
      name: 'User', 
      avatar: 'https://i.pravatar.cc/150?u=1'
    },
    replies_count: 14,
    views_count: 342,
    last_activity: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString(),
    is_pinned: false,
    is_solved: true
  },
  {
    id: 2,
    title: 'Showcase your latest projects here! (February 2025)',
    slug: 'showcase-feb-2025',
    category: {
      id: 2,
      name: 'General',
      color: 'bg-purple-500'
    },
    author: {
      id: 1,
      name: 'User', 
      avatar: 'https://i.pravatar.cc/150?u=1'
    },
    replies_count: 56,
    views_count: 1205,
    last_activity: new Date(Date.now() - 5 * 60 * 1000).toISOString(),
    is_pinned: true,
    is_solved: false
  },
  {
    id: 3,
    title: 'Best practices for state management',
    slug: 'state-management-best-practices',
    category: {
      id: 3,
      name: 'Development',
      color: 'bg-green-500'
    },
    author: {
      id: 1,
      name: 'User', 
      avatar: 'https://i.pravatar.cc/150?u=1'
    },
    replies_count: 8,
    views_count: 156,
    last_activity: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
    is_pinned: false,
    is_solved: false
  }
]

// Format time relative
const formatTimeRelative = (dateStr: string) => {
  const date = new Date(dateStr)
  const now = new Date()
  const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000)
  
  if (diffInSeconds < 60) return 'Just now'
  if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`
  if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`
  if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`
  return date.toLocaleDateString()
}

// Navigate
const goToTopic = (topic: ForumTopic) => {
  navigateTo(`/forum/topic/${topic.slug}`)
}

onMounted(() => {
  fetchTopics()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <BaseCard class="bg-gray-800 border-gray-700 p-5">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div>
          <h3 class="text-xl font-bold text-white flex items-center gap-2">
            <Icon icon="fluent:chat-multiple-24-filled" class="w-6 h-6 text-vikinger-cyan" />
            Forum Discussions
            <span class="text-vikinger-cyan font-normal ml-1">({{ topics.length }})</span>
          </h3>
          <p class="text-sm text-gray-400 mt-1">กระทู้และการพูดคุยในเว็บบอร์ด</p>
        </div>
        
        <!-- Action Button -->
        <NuxtLink
          v-if="isOwnProfile"
          to="/forum/create"
          class="px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2"
        >
          <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
          ตั้งกระทู้ใหม่
        </NuxtLink>
      </div>

      <!-- Filter Tabs -->
      <div class="flex gap-2 border-b border-gray-700 pb-3">
        <button class="min-h-[44px] sm:min-h-0"
          v-for="filter in [
            { key: 'started', label: 'กระทู้ที่ตั้ง', icon: 'fluent:edit-24-regular' },
            { key: 'replied', label: 'ที่ตอบกลับ', icon: 'fluent:chat-24-regular' },
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
        <BaseCard class="bg-gray-800 border-gray-700 p-4">
          <div class="flex gap-4">
            <div class="h-12 w-12 bg-gray-700 rounded-lg"></div>
            <div class="flex-1 space-y-2">
              <div class="h-5 bg-gray-700 rounded w-3/4"></div>
              <div class="h-3 bg-gray-700 rounded w-1/2"></div>
            </div>
          </div>
        </BaseCard>
      </div>
    </div>

    <!-- Topics List -->
    <div v-else-if="filteredTopics.length > 0" class="space-y-4">
      <BaseCard 
        v-for="topic in filteredTopics" 
        :key="topic.id"
        class="bg-gray-800 border-gray-700 p-0 overflow-hidden hover:border-vikinger-purple/50 transition-all cursor-pointer group"
        @click="goToTopic(topic)"
      >
        <div class="p-5 flex flex-col md:flex-row gap-4 items-start md:items-center">
          <!-- Icon/Status -->
          <div class="shrink-0 relative">
            <div class="w-12 h-12 rounded-xl bg-gray-700 flex items-center justify-center group-hover:bg-vikinger-purple group-hover:text-white transition-colors text-gray-400">
              <Icon :icon="topic.is_pinned ? 'fluent:pin-24-filled' : 'fluent:chat-24-filled'" class="w-6 h-6" />
            </div>
            <div v-if="topic.is_solved" class="absolute -bottom-1 -right-1 bg-green-500 text-white rounded-full p-0.5 border-2 border-gray-800" title="Solved">
              <Icon icon="fluent:checkmark-12-filled" class="w-3 h-3" />
            </div>
          </div>

          <!-- Main Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span 
                :class="[
                  'px-2 py-0.5 text-[10px] uppercase font-bold rounded shadow-sm',
                  topic.category.color.replace('bg-', 'bg-').replace('500', '500/20').replace('bg-', 'text-')
                ]"
              >
                {{ topic.category.name }}
              </span>
              <span class="text-xs text-gray-500 flex items-center gap-1">
                <Icon icon="fluent:clock-24-regular" class="w-3 h-3" />
                {{ formatTimeRelative(topic.last_activity) }}
              </span>
            </div>
            
            <h4 class="text-base font-bold text-white truncate group-hover:text-vikinger-cyan transition-colors">
              {{ topic.title }}
            </h4>
          </div>

          <!-- Stats -->
          <div class="flex items-center gap-6 text-sm text-gray-500 md:pl-4 md:border-l md:border-gray-700 self-end md:self-center w-full md:w-auto justify-between md:justify-start">
            <div class="flex flex-col items-center">
              <span class="font-bold text-white group-hover:text-blue-400 transition-colors">{{ topic.replies_count }}</span>
              <span class="text-[10px] uppercase">Replies</span>
            </div>
            <div class="flex flex-col items-center">
              <span class="font-bold text-white group-hover:text-green-400 transition-colors">{{ topic.views_count }}</span>
              <span class="text-[10px] uppercase">Views</span>
            </div>
            <div class="flex -space-x-2">
              <img v-for="i in 3" :key="i" :src="`https://i.pravatar.cc/150?u=${topic.id + i}`" class="w-8 h-8 rounded-full border-2 border-gray-800" alt="User" />
            </div>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Empty State -->
    <BaseCard v-else class="bg-gray-800 border-gray-700 text-center py-16">
      <div class="w-20 h-20 mx-auto bg-gray-700/50 rounded-full flex items-center justify-center mb-4">
        <Icon icon="fluent:chat-bubbles-Question-24-regular" class="w-10 h-10 text-gray-500" />
      </div>
      <h3 class="text-lg font-bold text-white mb-2">ไม่มีการสนทนา</h3>
      <p class="text-gray-400 max-w-sm mx-auto">
        {{ isOwnProfile ? 'เริ่มตั้งกระทู้แรกของคุณเพื่อพูดคุยกับชุมชน' : 'ผู้ใช้นี้ยังไม่ได้ตั้งกระทู้ใดๆ' }}
      </p>
      
      <NuxtLink 
        v-if="isOwnProfile" 
        to="/forum/create"
        class="inline-flex items-center gap-2 mt-6 px-6 py-3 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl hover:shadow-lg hover:shadow-vikinger-purple/20 transition-all font-bold"
      >
        <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
        ตั้งกระทู้
      </NuxtLink>
    </BaseCard>
  </div>
</template>

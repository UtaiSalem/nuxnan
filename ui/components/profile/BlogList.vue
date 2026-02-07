<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'

const props = defineProps<{
  userId?: string | number
  isOwnProfile?: boolean
}>()

const api = useApi()

// Types
interface BlogPost {
  id: number
  title: string
  slug: string
  excerpt: string
  cover_image?: string
  author: {
    id: number
    name: string
    avatar?: string
  }
  category: string
  published_at: string
  read_time: number // minutes
  views_count: number
  likes_count: number
  comments_count: number
}

// State
const posts = ref<BlogPost[]>([])
const isLoading = ref(false)
const selectedFilter = ref<'all' | 'popular' | 'latest'>('latest')

// Computed
const filteredPosts = computed(() => {
  let result = [...posts.value]
  
  if (selectedFilter.value === 'popular') {
    return result.sort((a, b) => b.views_count - a.views_count)
  }
  
  // Default to latest
  return result.sort((a, b) => new Date(b.published_at).getTime() - new Date(a.published_at).getTime())
})

// Fetch posts
const fetchPosts = async () => {
  isLoading.value = true
  
  try {
    const endpoint = '/api/posts'
    const params = {
      post_type: 'ARTICLE',
      user_id: props.userId,
      sort: selectedFilter.value === 'popular' ? 'popular' : 'latest'
    }
    
    // Attempt to fetch from API
    const response = await api.get(endpoint, { params }).catch(() => ({ success: false })) as any
    
    if (response && response.data) {
      // Handle both paginated and non-paginated responses
      posts.value = Array.isArray(response.data) ? response.data : (response.data.data || [])
    } else {
      posts.value = []
    }
  } catch (error) {
    console.error('Error fetching blog posts:', error)
    posts.value = []
  } finally {
    isLoading.value = false
  }
}

// Mock data
const getMockPosts = (): BlogPost[] => [
  {
    id: 1,
    title: 'The Future of Web Development with Vue 3',
    slug: 'future-vue-3',
    excerpt: 'Exploring the new features in Vue 3, including Composition API, Teleport, and Suspense. Why these changes matter for modern web apps.',
    cover_image: 'https://picsum.photos/800/400?random=20',
    author: {
      id: 1,
      name: 'John Doe',
      avatar: 'https://i.pravatar.cc/150?u=1'
    },
    category: 'Technology',
    published_at: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
    read_time: 5,
    views_count: 1250,
    likes_count: 45,
    comments_count: 12
  },
  {
    id: 2,
    title: 'Getting Started with Tailwind CSS',
    slug: 'start-tailwind-css',
    excerpt: 'A comprehensive guide to setting up and using Tailwind CSS in your next project. Learn about utility-first CSS and how it speeds up development.',
    cover_image: 'https://picsum.photos/800/400?random=21',
    author: {
      id: 1,
      name: 'John Doe',
      avatar: 'https://i.pravatar.cc/150?u=1'
    },
    category: 'Design',
    published_at: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString(),
    read_time: 8,
    views_count: 980,
    likes_count: 32,
    comments_count: 8
  },
  {
    id: 3,
    title: 'Understanding TypeScript Generics',
    slug: 'typescript-generics',
    excerpt: 'Deep dive into TypeScript Generics. How to write reusable and type-safe code using generic components and functions.',
    cover_image: 'https://picsum.photos/800/400?random=22',
    author: {
      id: 1,
      name: 'John Doe',
      avatar: 'https://i.pravatar.cc/150?u=1'
    },
    category: 'Programming',
    published_at: new Date(Date.now() - 10 * 24 * 60 * 60 * 1000).toISOString(),
    read_time: 12,
    views_count: 2100,
    likes_count: 89,
    comments_count: 24
  }
]

// Format date
const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

// Navigate to post
const goToPost = (post: BlogPost) => {
  navigateTo(`/blog/${post.slug}`)
}

onMounted(() => {
  fetchPosts()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <BaseCard class="bg-gray-800 border-gray-700 p-5">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div>
          <h3 class="text-xl font-bold text-white flex items-center gap-2">
            <Icon icon="fluent:document-text-24-filled" class="w-6 h-6 text-vikinger-cyan" />
            Blog
            <span class="text-vikinger-cyan font-normal ml-1">({{ posts.length }})</span>
          </h3>
          <p class="text-sm text-gray-400 mt-1">บทความและเรื่องราวที่แบ่งปัน</p>
        </div>
        
        <!-- Create Post Button (Own Profile) -->
        <NuxtLink
          v-if="isOwnProfile"
          to="/blog/create"
          class="px-4 py-2 bg-vikinger-purple text-white rounded-lg hover:bg-vikinger-purple/80 transition-colors flex items-center gap-2"
        >
          <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
          เขียนบทความ
        </NuxtLink>
      </div>

      <!-- Filter Tabs -->
      <div class="flex gap-2 border-b border-gray-700 pb-3">
        <button
          v-for="filter in [
            { key: 'latest', label: 'ล่าสุด', icon: 'fluent:clock-24-regular' },
            { key: 'popular', label: 'ยอดนิยม', icon: 'fluent:arrow-trending-24-regular' },
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
    <div v-if="isLoading" class="grid grid-cols-1 gap-6">
      <div v-for="i in 2" :key="i" class="animate-pulse">
        <BaseCard class="bg-gray-800 border-gray-700 overflow-hidden">
          <div class="h-48 bg-gray-700"></div>
          <div class="p-6 space-y-4">
            <div class="h-6 bg-gray-700 rounded w-3/4"></div>
            <div class="h-4 bg-gray-700 rounded w-full"></div>
            <div class="h-4 bg-gray-700 rounded w-2/3"></div>
          </div>
        </BaseCard>
      </div>
    </div>

    <!-- Blog Posts Grid -->
    <div v-else-if="filteredPosts.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <BaseCard 
        v-for="post in filteredPosts" 
        :key="post.id"
        class="bg-gray-800 border-gray-700 overflow-hidden hover:border-vikinger-purple/50 transition-all cursor-pointer group flex flex-col h-full"
        @click="goToPost(post)"
      >
        <!-- Cover Image -->
        <div class="h-48 relative overflow-hidden">
          <img 
            v-if="post.cover_image" 
            :src="post.cover_image" 
            :alt="post.title"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          />
          <div v-else class="w-full h-full bg-gradient-to-br from-gray-700 to-gray-600 flex items-center justify-center">
            <Icon icon="fluent:image-24-regular" class="w-12 h-12 text-gray-500" />
          </div>
          
          <!-- Category Badge -->
          <div class="absolute top-4 left-4 px-3 py-1 bg-black/60 backdrop-blur-sm text-white text-xs font-bold rounded-lg uppercase tracking-wider">
            {{ post.category }}
          </div>
        </div>

        <!-- Content -->
        <div class="p-5 flex-1 flex flex-col">
          <div class="flex items-center gap-2 mb-3 text-xs text-gray-400">
            <span class="flex items-center gap-1">
              <Icon icon="fluent:calendar-24-regular" class="w-3.5 h-3.5" />
              {{ formatDate(post.published_at) }}
            </span>
            <span class="w-1 h-1 bg-gray-600 rounded-full"></span>
            <span class="flex items-center gap-1">
              <Icon icon="fluent:clock-24-regular" class="w-3.5 h-3.5" />
              {{ post.read_time }} min read
            </span>
          </div>

          <h3 class="text-xl font-bold text-white mb-3 group-hover:text-vikinger-cyan transition-colors line-clamp-2">
            {{ post.title }}
          </h3>
          
          <p class="text-gray-400 text-sm leading-relaxed mb-4 line-clamp-3">
            {{ post.excerpt }}
          </p>
          
          <div class="mt-auto pt-4 border-t border-gray-700 flex items-center justify-between text-sm text-gray-500">
            <div class="flex items-center gap-4">
              <span class="flex items-center gap-1.5 hover:text-white transition-colors">
                <Icon icon="fluent:eye-24-regular" class="w-4 h-4" />
                {{ post.views_count.toLocaleString() }}
              </span>
              <span class="flex items-center gap-1.5 hover:text-red-400 transition-colors">
                <Icon icon="fluent:heart-24-regular" class="w-4 h-4" />
                {{ post.likes_count }}
              </span>
              <span class="flex items-center gap-1.5 hover:text-blue-400 transition-colors">
                <Icon icon="fluent:comment-24-regular" class="w-4 h-4" />
                {{ post.comments_count }}
              </span>
            </div>
            
            <div class="text-vikinger-cyan font-medium group-hover:translate-x-1 transition-transform flex items-center gap-1">
              Read More <Icon icon="fluent:arrow-right-24-regular" class="w-4 h-4" />
            </div>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Empty State -->
    <BaseCard v-else class="bg-gray-800 border-gray-700 text-center py-16">
      <div class="w-20 h-20 mx-auto bg-gray-700/50 rounded-full flex items-center justify-center mb-4">
        <Icon icon="fluent:document-text-24-regular" class="w-10 h-10 text-gray-500" />
      </div>
      <h3 class="text-lg font-bold text-white mb-2">ยังไม่มีบทความ</h3>
      <p class="text-gray-400 max-w-sm mx-auto">
        {{ isOwnProfile ? 'เริ่มต้นเขียนบทความแรกของคุณเพื่อแบ่งปันความรู้และประสบการณ์' : 'ผู้ใช้นี้ยังไม่ได้เขียนบทความใดๆ' }}
      </p>
      
      <NuxtLink 
        v-if="isOwnProfile" 
        to="/blog/create"
        class="inline-flex items-center gap-2 mt-6 px-6 py-3 bg-gradient-to-r from-vikinger-purple to-vikinger-cyan text-white rounded-xl hover:shadow-lg hover:shadow-vikinger-purple/20 transition-all font-bold"
      >
        <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
        เขียนบทความ
      </NuxtLink>
    </BaseCard>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  post: any
  currentUserId?: number
  isCourseAdmin?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const emit = defineEmits<{
  'edit': [post: any]
  'delete': [postId: number]
  'like': [post: any]
  'comment': [post: any]
  'share': [post: any]
}>()

// Check if current user is author
const isAuthor = computed(() => props.post.user?.id === props.currentUserId)

// Format date
const formatDate = (date: string) => {
  if (!date) return ''
  
  const postDate = new Date(date)
  const now = new Date()
  const diffMs = now.getTime() - postDate.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)
  
  if (diffMins < 1) return 'เมื่อสักครู่'
  if (diffMins < 60) return `${diffMins} นาทีที่แล้ว`
  if (diffHours < 24) return `${diffHours} ชั่วโมงที่แล้ว`
  if (diffDays < 7) return `${diffDays} วันที่แล้ว`
  
  return postDate.toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// State
const showMenu = ref(false)
const showComments = ref(false)
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="p-4 flex items-start gap-3">
      <NuxtLink :to="`/profile/${post.user?.username}`">
        <img
          :src="post.user?.avatar || '/images/default-avatar.png'"
          :alt="post.user?.name"
          class="w-10 h-10 rounded-full object-cover"
        />
      </NuxtLink>
      
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <NuxtLink 
            :to="`/profile/${post.user?.username}`"
            class="font-medium text-gray-900 dark:text-white hover:underline"
          >
            {{ post.user?.name }}
          </NuxtLink>
          <span 
            v-if="post.user?.role === 'teacher' || post.user?.role === 'admin'"
            class="px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400"
          >
            ผู้สอน
          </span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ formatDate(post.created_at) }}
          <span v-if="post.is_edited" class="text-gray-400"> · แก้ไขแล้ว</span>
        </p>
      </div>
      
      <!-- Menu -->
      <div v-if="isAuthor || isCourseAdmin" class="relative">
        <button
          @click="showMenu = !showMenu"
          class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400"
        >
          <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5" />
        </button>
        
        <div 
          v-if="showMenu"
          class="absolute right-0 top-full mt-1 w-36 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-10"
        >
          <button
            v-if="isAuthor"
            @click="emit('edit', post); showMenu = false"
            class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2"
          >
            <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
            แก้ไข
          </button>
          <button
            @click="emit('delete', post.id); showMenu = false"
            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2"
          >
            <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
            ลบ
          </button>
        </div>
      </div>
    </div>
    
    <!-- Content -->
    <div class="px-4 pb-3">
      <div 
        v-if="post.content"
        class="prose dark:prose-invert prose-sm max-w-none"
        v-html="post.content"
      ></div>
    </div>
    
    <!-- Media -->
    <div v-if="post.media && post.media.length > 0" class="relative">
      <!-- Single image -->
      <img
        v-if="post.media.length === 1"
        :src="post.media[0].url"
        :alt="post.media[0].alt || 'Post image'"
        class="w-full max-h-96 object-cover"
      />
      
      <!-- Multiple images grid -->
      <div 
        v-else
        class="grid gap-1"
        :class="[
          post.media.length === 2 ? 'grid-cols-2' : '',
          post.media.length === 3 ? 'grid-cols-3' : '',
          post.media.length >= 4 ? 'grid-cols-2' : ''
        ]"
      >
        <div 
          v-for="(media, index) in post.media.slice(0, 4)" 
          :key="index"
          class="relative aspect-square"
        >
          <img
            :src="media.url"
            :alt="media.alt || `Image ${index + 1}`"
            class="w-full h-full object-cover"
          />
          <div 
            v-if="index === 3 && post.media.length > 4"
            class="absolute inset-0 bg-black/50 flex items-center justify-center"
          >
            <span class="text-2xl font-bold text-white">+{{ post.media.length - 4 }}</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Attachments -->
    <div v-if="post.attachments && post.attachments.length > 0" class="px-4 py-2">
      <div class="space-y-2">
        <a
          v-for="attachment in post.attachments"
          :key="attachment.id"
          :href="attachment.url"
          target="_blank"
          class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
        >
          <Icon icon="fluent:document-24-regular" class="w-5 h-5 text-blue-500" />
          <span class="flex-1 text-sm text-gray-700 dark:text-gray-300 truncate">
            {{ attachment.name }}
          </span>
          <span class="text-xs text-gray-400">{{ attachment.size }}</span>
        </a>
      </div>
    </div>
    
    <!-- Stats -->
    <div class="px-4 py-2 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
      <div v-if="post.likes_count > 0" class="flex items-center gap-1">
        <div class="w-5 h-5 rounded-full bg-blue-500 flex items-center justify-center">
          <Icon icon="fluent:thumb-like-24-filled" class="w-3 h-3 text-white" />
        </div>
        <span>{{ post.likes_count }}</span>
      </div>
      
      <div class="flex items-center gap-4">
        <button 
          v-if="post.comments_count > 0"
          @click="showComments = !showComments"
          class="hover:underline"
        >
          {{ post.comments_count }} ความคิดเห็น
        </button>
        <span v-if="post.shares_count > 0">{{ post.shares_count }} แชร์</span>
      </div>
    </div>
    
    <!-- Actions -->
    <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 flex items-center">
      <button
        @click="emit('like', post)"
        :class="[
          'flex-1 flex items-center justify-center gap-2 py-2 rounded-lg transition-colors',
          post.is_liked 
            ? 'text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20' 
            : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
        ]"
      >
        <Icon 
          :icon="post.is_liked ? 'fluent:thumb-like-24-filled' : 'fluent:thumb-like-24-regular'" 
          class="w-5 h-5"
        />
        <span>ถูกใจ</span>
      </button>
      
      <button
        @click="emit('comment', post); showComments = true"
        class="flex-1 flex items-center justify-center gap-2 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
      >
        <Icon icon="fluent:chat-24-regular" class="w-5 h-5" />
        <span>แสดงความคิดเห็น</span>
      </button>
      
      <button
        @click="emit('share', post)"
        class="flex-1 flex items-center justify-center gap-2 py-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
      >
        <Icon icon="fluent:share-24-regular" class="w-5 h-5" />
        <span>แชร์</span>
      </button>
    </div>
    
    <!-- Comments Section -->
    <div v-if="showComments && post.comments && post.comments.length > 0" class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700">
      <div class="space-y-3">
        <div 
          v-for="comment in post.comments.slice(0, 3)" 
          :key="comment.id"
          class="flex gap-2"
        >
          <img
            :src="comment.user?.avatar || '/images/default-avatar.png'"
            :alt="comment.user?.name"
            class="w-8 h-8 rounded-full"
          />
          <div class="flex-1">
            <div class="bg-white dark:bg-gray-800 rounded-xl px-3 py-2">
              <p class="font-medium text-sm text-gray-900 dark:text-white">
                {{ comment.user?.name }}
              </p>
              <p class="text-sm text-gray-700 dark:text-gray-300">
                {{ comment.content }}
              </p>
            </div>
            <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
              <button class="hover:underline">ถูกใจ</button>
              <button class="hover:underline">ตอบกลับ</button>
              <span>{{ formatDate(comment.created_at) }}</span>
            </div>
          </div>
        </div>
        
        <button 
          v-if="post.comments.length > 3"
          class="text-sm text-blue-600 hover:underline"
        >
          ดูความคิดเห็นทั้งหมด {{ post.comments_count }} รายการ
        </button>
      </div>
    </div>
  </div>
</template>

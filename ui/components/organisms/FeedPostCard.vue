<script setup lang="ts">
import BaseCard from '../atoms/BaseCard.vue'
import BaseAvatar from '../atoms/BaseAvatar.vue'
import BaseIcon from '../atoms/BaseIcon.vue'
import PostAction from '../molecules/PostAction.vue'
import { computed } from 'vue'

const props = defineProps({
  post: {
    type: Object,
    required: true,
  },
})

// Normalize data - support both Activity and Post structures
const normalizedPost = computed(() => {
  const p = props.post
  
  // Check if this is an Activity (has target_resource) or direct Post
  const isActivity = !!p.target_resource || !!p.action_by
  
  if (isActivity) {
    const targetPost = p.target_resource || {}
    const author = targetPost.author || p.action_by || {}
    
    return {
      id: p.id,
      author: {
        id: author.id,
        name: author.name || author.username || 'Unknown',
        avatar: author.avatar || author.profile_photo_url || '/images/default-avatar.png',
      },
      content: targetPost.content || p.description || '',
      time: p.diff_humans_created_at || targetPost.diff_humans_created_at || '',
      images: targetPost.images || targetPost.postImages || [],
      image: targetPost.images?.[0]?.url || targetPost.postImages?.[0]?.url || null,
      likes: targetPost.likes || 0,
      dislikes: targetPost.dislikes || 0,
      comments: targetPost.comments || targetPost.comments_count || 0,
      shares: targetPost.shares || 0,
      privacy_settings: targetPost.privacy_settings,
      privacy_label: targetPost.privacy_label,
      is_owner: targetPost.is_owner || false,
      isLikedByAuth: targetPost.isLikedByAuth || false,
      isDislikedByAuth: targetPost.isDislikedByAuth || false,
      has_background: targetPost.has_background || false,
      background: targetPost.background || null,
      feeling_text: targetPost.feeling_text || null,
      location: targetPost.location || null,
    }
  }
  
  // Direct Post structure
  const author = p.author || {}
  return {
    id: p.id,
    author: {
      id: author.id,
      name: author.name || author.username || 'Unknown',
      avatar: author.avatar || author.profile_photo_url || '/images/default-avatar.png',
    },
    content: p.content || '',
    time: p.time || p.diff_humans_created_at || '',
    images: p.images || p.postImages || [],
    image: p.image || p.images?.[0]?.url || null,
    likes: p.likes || 0,
    dislikes: p.dislikes || 0,
    comments: p.comments || p.comments_count || 0,
    shares: p.shares || 0,
    privacy_settings: p.privacy_settings,
    privacy_label: p.privacy_label,
    is_owner: p.is_owner || false,
    isLikedByAuth: p.isLikedByAuth || false,
    isDislikedByAuth: p.isDislikedByAuth || false,
    has_background: p.has_background || false,
    background: p.background || null,
    feeling_text: p.feeling_text || null,
    location: p.location || null,
  }
})

// Get privacy icon
const privacyIcon = computed(() => {
  const icons: Record<string, string> = {
    lock: 'fluent:lock-closed-24-regular',
    users: 'fluent:people-24-regular',
    globe: 'fluent:globe-24-regular',
  }
  return icons[normalizedPost.value.privacy_label?.icon] || 'fluent:globe-24-regular'
})
</script>

<template>
  <BaseCard class="mb-6 bg-gray-800 border-gray-700">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <BaseAvatar :src="normalizedPost.author.avatar" size="md" />
        <div>
          <div class="flex items-center gap-2">
            <NuxtLink 
              :to="`/profile/${normalizedPost.author.id}`"
              class="font-bold text-white hover:text-vikinger-cyan transition-colors"
            >
              {{ normalizedPost.author.name }}
            </NuxtLink>
            <!-- Feeling/Activity -->
            <span v-if="normalizedPost.feeling_text" class="text-gray-400 text-sm">
              {{ normalizedPost.feeling_text }}
            </span>
          </div>
          <div class="flex items-center gap-2 text-xs text-gray-500">
            <span>{{ normalizedPost.time }}</span>
            <span>•</span>
            <Icon :icon="privacyIcon" class="w-3.5 h-3.5" />
          </div>
        </div>
      </div>
      <button class="text-gray-400 hover:text-white p-2 rounded-lg hover:bg-gray-700 transition-colors">
        <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5" />
      </button>
    </div>

    <!-- Content -->
    <div class="mb-4">
      <!-- Background Post (Status with color/gradient) -->
      <div 
        v-if="normalizedPost.has_background && normalizedPost.background"
        class="rounded-lg p-8 text-center min-h-[200px] flex items-center justify-center"
        :style="{
          backgroundColor: normalizedPost.background.color,
          backgroundImage: normalizedPost.background.gradient || (normalizedPost.background.image ? `url(${normalizedPost.background.image})` : ''),
          backgroundSize: 'cover',
          backgroundPosition: 'center',
          color: normalizedPost.background.text_color || '#ffffff'
        }"
      >
        <p :style="{ fontSize: normalizedPost.background.font_size || '1.5rem' }" class="font-semibold">
          {{ normalizedPost.content }}
        </p>
      </div>
      
      <!-- Regular Post -->
      <template v-else>
        <p v-if="normalizedPost.content" class="text-gray-200 mb-3 whitespace-pre-wrap">{{ normalizedPost.content }}</p>
        
        <!-- Single Image -->
        <img
          v-if="normalizedPost.image && normalizedPost.images.length <= 1"
          :src="normalizedPost.image"
          alt="Post image"
          class="w-full rounded-lg object-cover max-h-[500px]"
        />
        
        <!-- Multiple Images Grid -->
        <div 
          v-else-if="normalizedPost.images.length > 1" 
          class="grid gap-1 rounded-lg overflow-hidden"
          :class="{
            'grid-cols-2': normalizedPost.images.length === 2,
            'grid-cols-2': normalizedPost.images.length === 3,
            'grid-cols-2': normalizedPost.images.length >= 4,
          }"
        >
          <div 
            v-for="(image, index) in normalizedPost.images.slice(0, 4)" 
            :key="index"
            :class="{
              'col-span-2': normalizedPost.images.length === 3 && index === 0,
              'relative': normalizedPost.images.length > 4 && index === 3
            }"
          >
            <img 
              :src="image.url || image" 
              :alt="`Image ${index + 1}`"
              class="w-full h-48 object-cover"
            />
            <!-- More images overlay -->
            <div 
              v-if="normalizedPost.images.length > 4 && index === 3"
              class="absolute inset-0 bg-black/60 flex items-center justify-center"
            >
              <span class="text-white text-2xl font-bold">+{{ normalizedPost.images.length - 4 }}</span>
            </div>
          </div>
        </div>
      </template>
      
      <!-- Location -->
      <div v-if="normalizedPost.location" class="flex items-center gap-1 mt-2 text-gray-500 text-sm">
        <Icon icon="fluent:location-24-regular" class="w-4 h-4" />
        <span>{{ normalizedPost.location }}</span>
      </div>
    </div>

    <!-- Actions -->
    <PostAction 
      :post-id="normalizedPost.id"
      :likes="normalizedPost.likes" 
      :dislikes="normalizedPost.dislikes"
      :comments="normalizedPost.comments" 
      :shares="normalizedPost.shares"
      :is-liked="normalizedPost.isLikedByAuth"
      :is-disliked="normalizedPost.isDislikedByAuth"
    />
  </BaseCard>
</template>

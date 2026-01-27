<template>
  <button
    class="follow-btn"
    :class="{ 'following': isFollowing }"
    @click="handleToggleFollow"
    :disabled="isLoading"
  >
    <i v-if="isLoading" class="lni lni-spinner lni-spin"></i>
    <i v-else-if="isFollowing" class="lni lni-checkmark"></i>
    <i v-else class="lni lni-plus"></i>
    {{ buttonText }}
  </button>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useFollow } from '~/composables/useFollow'

interface Props {
  userId: number
  initialFollowing?: boolean
  initialFollowersCount?: number
}

const props = withDefaults(defineProps<Props>(), {
  initialFollowing: false,
  initialFollowersCount: 0
})

const emit = defineEmits<{
  followChanged: [following: boolean, followersCount: number]
}>()

const { toggleFollow, isFollowing: checkIsFollowing } = useFollow()

const isFollowing = ref(props.initialFollowing)
const followersCount = ref(props.initialFollowersCount)
const isLoading = ref(false)

const buttonText = computed(() => {
  if (isLoading.value) return 'กำลังโหลด...'
  return isFollowing.value ? 'กำลังติดตาม' : 'ติดตาม'
})

// Handle follow/unfollow
const handleToggleFollow = async () => {
  if (isLoading.value) return

  try {
    isLoading.value = true
    const result = await toggleFollow(props.userId)

    isFollowing.value = result.following
    followersCount.value = result.followers_count

    emit('followChanged', result.following, result.followers_count)
  } catch (error) {
    console.error('Failed to toggle follow:', error)
  } finally {
    isLoading.value = false
  }
}

// Watch for prop changes
watch(() => props.initialFollowing, (newVal) => {
  isFollowing.value = newVal
})

watch(() => props.initialFollowersCount, (newVal) => {
  followersCount.value = newVal
})
</script>

<style scoped>
.follow-btn {
  padding: 8px 16px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: white;
  color: #374151;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
  min-width: 100px;
  justify-content: center;
}

.follow-btn:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #9ca3af;
}

.follow-btn.following {
  background: #4f46e5;
  color: white;
  border-color: #4f46e5;
}

.follow-btn.following:hover:not(:disabled) {
  background: #4338ca;
  border-color: #4338ca;
}

.follow-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.lni-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
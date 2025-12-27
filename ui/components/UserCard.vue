<template>
  <div class="bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition-shadow">
    <div class="flex flex-col items-center text-center">
      <img 
        :src="user.avatar" 
        :alt="user.username"
        class="w-20 h-20 rounded-full object-cover mb-3"
      />
      
      <h4 class="font-semibold text-secondary-900 mb-1">{{ user.username }}</h4>
      
      <p v-if="user.mutualFriends" class="text-xs text-secondary-500 mb-3">
        {{ user.mutualFriends }} mutual friends
      </p>
      
      <button class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors">
        Add Friend
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  user: {
    id: number
    username: string
    avatar: string
    mutualFriends?: number
  }
}

defineProps<Props>()
</script>

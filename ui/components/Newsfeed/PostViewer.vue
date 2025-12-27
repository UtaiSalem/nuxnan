<script setup>
const props = defineProps({
  post: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4">
    <div class="flex items-center mb-4">
      <!-- User Avatar -->
      <img 
        :src="post.user?.avatar || 'https://via.placeholder.com/40'" 
        alt="User Avatar" 
        class="w-10 h-10 rounded-full mr-3"
      >
      <div>
        <h3 class="font-bold text-gray-900 dark:text-white">{{ post.user?.name || 'Unknown User' }}</h3>
        <p class="text-xs text-gray-500">{{ new Date(post.created_at).toLocaleDateString() }}</p>
      </div>
    </div>

    <!-- Post Content -->
    <div class="text-gray-800 dark:text-gray-200 mb-4">
      {{ post.content || post.body || 'No content' }} 
      <!-- Adjust property name based on actual Post model -->
    </div>

    <!-- Post Actions (Like, Comment, Share) - Placeholder -->
    <div class="flex items-center justify-between text-gray-500 text-sm border-t pt-2 dark:border-gray-700">
      <button class="flex items-center hover:text-blue-500">
        <span>Like</span>
      </button>
      <button class="flex items-center hover:text-blue-500">
        <span>Comment</span>
      </button>
      <button class="flex items-center hover:text-blue-500">
        <span>Share</span>
      </button>
    </div>
  </div>
</template>

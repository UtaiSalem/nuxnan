<script setup>
import PostViewer from './PostViewer.vue';

const props = defineProps({
  activity: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <div>
    <div v-if="activity.action_to === 'Post' && activity.object">
      <PostViewer :post="activity.object" />
    </div>
    <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4">
      <p class="text-gray-500">
        Unsupported activity type: {{ activity.action_to }}
      </p>
    </div>
  </div>
</template>

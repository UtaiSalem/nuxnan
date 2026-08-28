<template>
  <div class="bg-white p-4 sm:p-6 shadow rounded-lg">
      <h2 class="text-xl font-bold mb-4">Edit Post</h2>
      <textarea class="w-full border rounded p-2" v-model="content"></textarea>
      <button class="min-h-[44px] sm:min-h-0 bg-blue-600 text-white px-4 py-2 rounded mt-2">Update Post</button>
  </div>
</template>
<script setup>
import { ref } from 'vue';
const props = defineProps(['activity']);
const content = ref(props.activity?.data?.content || '');
</script>

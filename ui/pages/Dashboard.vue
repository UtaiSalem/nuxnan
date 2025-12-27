<script setup>
// import { onMounted } from 'vue';
// import Welcome from '@/components/Welcome.vue';
// import { route } from 'vendor/tightenco/ziggy/src/js';

definePageMeta({
  layout: 'main',
  middleware: 'auth',
})

useHead({
  title: 'Dashboard',
})

// onMounted(() => {
//     window.location.href = '/newsfeed';
// });
</script>

<template>
  <div>
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight mb-6">Dashboard</h2>

      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
        <!-- <Welcome /> -->
        <!-- <a href="/newsfeed">Newsfeed</a> -->
        <p class="text-gray-600 dark:text-gray-400">Welcome to your dashboard!</p>
      </div>
    </div>
  </div>
</template>

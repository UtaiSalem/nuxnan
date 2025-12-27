<script setup lang="ts">
import YourPageWidget from './YourPageWidget.vue'
import EventsWidget from './EventsWidget.vue'
</script>

<template>
  <div class="hidden lg:block w-80 flex-shrink-0">
    <YourPageWidget />
    
    <div class="flex items-center justify-between mb-4 px-1">
      <h3 class="font-bold text-gray-900 dark:text-white">Explor Events</h3>
      <a href="#" class="text-blue-500 text-sm hover:underline">See All</a>
    </div>
    <EventsWidget />
  </div>
</template>

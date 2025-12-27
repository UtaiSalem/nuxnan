<script setup>
import { Icon } from '@iconify/vue'

defineProps({
  imageUrl: {
    type: String,
    default: '/images/ad-widget.gif'
  },
  link: {
    type: String,
    default: '#'
  }
})
</script>

<template>
  <div class="vikinger-card">
    <h4 class="text-sm font-semibold mb-3 flex items-center gap-2 text-gray-700 dark:text-gray-300">
      <Icon icon="fluent:info-24-regular" class="w-4 h-4 text-vikinger-purple" />
      Advertisement
    </h4>
    <figure class="rounded-lg overflow-hidden">
      <a :href="link" target="_blank" class="block hover:opacity-90 transition-opacity">
        <img :src="imageUrl" alt="Advertisement" class="w-full h-auto" />
      </a>
    </figure>
  </div>
</template>

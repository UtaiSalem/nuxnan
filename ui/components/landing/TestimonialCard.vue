<template>
  <div class="flex-shrink-0 w-[280px] sm:w-[350px] lg:w-[400px] glass-card-hover p-4 sm:p-6 cursor-pointer">
    <div class="flex items-start gap-3 sm:gap-4 mb-3 sm:mb-4">
      <!-- Avatar -->
      <div class="w-10 sm:w-14 h-10 sm:h-14 rounded-full p-0.5" :class="`bg-gradient-to-br ${testimonial.gradientFrom} ${testimonial.gradientTo}`">
        <div class="w-full h-full rounded-full bg-vikinger-dark-100 flex items-center justify-center">
          <span class="text-sm sm:text-lg font-bold text-white">{{ testimonial.avatar }}</span>
        </div>
      </div>
      <!-- Info -->
      <div>
        <h4 class="font-semibold text-white text-sm sm:text-base">{{ testimonial.name }}</h4>
        <p class="text-xs sm:text-sm text-gray-400">{{ testimonial.role }}</p>
      </div>
    </div>
    
    <!-- Rating -->
    <div class="flex gap-0.5 sm:gap-1 mb-2 sm:mb-3">
      <Icon 
        v-for="i in 5" 
        :key="i" 
        name="heroicons:star-solid" 
        class="w-3 sm:w-4 h-3 sm:h-4"
        :class="i <= testimonial.rating ? 'text-vikinger-yellow' : 'text-gray-600'"
      />
    </div>
    
    <!-- Content -->
    <p class="text-xs sm:text-sm lg:text-base text-gray-300 leading-relaxed">
      "{{ testimonial.content }}"
    </p>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
interface Testimonial {
  name: string
  role: string
  avatar: string
  content: string
  rating: number
  gradientFrom: string
  gradientTo: string
}

defineProps<{
  testimonial: Testimonial
}>()
</script>

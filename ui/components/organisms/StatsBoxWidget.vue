<script setup>
import { Icon } from '@iconify/vue'

defineProps({
  postsCreated: {
    type: Number,
    default: 294
  },
  growth: {
    type: Number,
    default: 0.4
  }
})
</script>

<template>
  <div class="vikinger-card bg-gradient-to-br from-vikinger-cyan to-vikinger-purple text-white relative overflow-hidden">
    <!-- Decorative circles -->
    <div class="absolute top-4 right-4 w-16 h-16 bg-white/10 rounded-full"></div>
    <div class="absolute bottom-0 right-0 w-24 h-24 bg-white/10 rounded-full translate-x-8 translate-y-8"></div>
    
    <div class="relative z-10">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold">Stats Box</h3>
        <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5" />
      </div>

      <div class="flex items-end gap-4">
        <div class="flex-1">
          <div class="text-4xl font-bold mb-1">{{ postsCreated }}</div>
          <div class="flex items-center gap-1 text-xs">
            <Icon icon="fluent:arrow-trending-up-24-filled" class="w-4 h-4" />
            <span>{{ growth }}%</span>
          </div>
          <div class="text-sm font-semibold mt-2">Posts Created</div>
          <div class="text-xs opacity-80">in the last month</div>
        </div>

        <!-- Mini chart visualization -->
        <div class="flex items-end gap-1 h-16 pb-2">
          <div class="w-2 bg-white/40 rounded-t" style="height: 40%"></div>
          <div class="w-2 bg-white/50 rounded-t" style="height: 60%"></div>
          <div class="w-2 bg-white/60 rounded-t" style="height: 45%"></div>
          <div class="w-2 bg-white/70 rounded-t" style="height: 75%"></div>
          <div class="w-2 bg-white/90 rounded-t" style="height: 90%"></div>
          <div class="w-2 bg-white rounded-t" style="height: 100%"></div>
        </div>
      </div>
    </div>
  </div>
</template>

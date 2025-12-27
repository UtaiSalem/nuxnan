<script setup lang="ts">
import BaseCard from '../atoms/BaseCard.vue'
import BaseIcon from '../atoms/BaseIcon.vue'
</script>

<template>
  <BaseCard no-padding class="mb-6 bg-gradient-to-br from-purple-500 to-indigo-600 text-white overflow-hidden">
    <div class="p-6 text-center">
      <div class="mb-4">
        <BaseIcon name="lni:gift" class="text-5xl text-white/90" />
      </div>
      <h3 class="text-lg font-bold mb-1">Ocean Motel good night</h3>
      <p class="text-sm text-white/80 mb-4">event in columbia</p>
      
      <div class="text-xs opacity-70">
        2016 The 3rd International Conference
      </div>
    </div>
  </BaseCard>
  
  <BaseCard no-padding class="mb-6 bg-gradient-to-br from-cyan-400 to-cyan-500 text-white overflow-hidden">
     <div class="p-6 text-center">
      <div class="mb-4">
        <BaseIcon name="lni:mic" class="text-5xl text-white/90" />
      </div>
      <h3 class="text-lg font-bold mb-1">2016 The 3rd International</h3>
      <p class="text-sm text-white/80">Conference</p>
    </div>
  </BaseCard>
</template>

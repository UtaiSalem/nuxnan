<script setup lang="ts">
import BaseCard from '../atoms/BaseCard.vue'
import BaseIcon from '../atoms/BaseIcon.vue'
</script>

<template>
  <BaseCard class="mb-6">
    <div class="bg-purple-500 text-white p-4 -m-4 mb-4 rounded-t-lg flex justify-between items-center">
      <div class="text-sm font-medium">22nd Birthday</div>
      <div class="text-right">
        <div class="text-xl font-bold">19</div>
        <div class="text-xs uppercase">September</div>
      </div>
    </div>
    
    <div class="text-center py-4">
      <div class="relative inline-block mb-3">
        <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mx-auto">
          <BaseIcon name="lni:cake" size="xl" class="text-pink-500" />
        </div>
      </div>
      
      <h4 class="text-lg font-bold text-gray-800 dark:text-white">Lucy Carbel</h4>
      <p class="text-sm text-gray-500 mb-4">valentine's birthday</p>
      
      <p class="text-xs text-gray-400">leave a message with your best wishes on his profile.</p>
    </div>
  </BaseCard>
</template>

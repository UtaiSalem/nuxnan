<script setup lang="ts">
import BaseCard from '../atoms/BaseCard.vue'
import BaseIcon from '../atoms/BaseIcon.vue'

const date = new Date()
const dayName = date.toLocaleDateString('en-US', { weekday: 'short' })
const monthName = date.toLocaleDateString('en-US', { month: 'short' })
const dayNum = date.getDate()
</script>

<template>
  <BaseCard no-padding class="mb-6 bg-gradient-to-br from-blue-400 to-blue-500 text-white overflow-hidden relative">
    <div class="p-6 relative z-10">
      <div class="flex justify-between items-start mb-8">
        <div>
          <span class="text-6xl font-light">25°</span>
          <div class="text-xl font-medium mt-1">Cloudy Skyes</div>
          <div class="text-sm opacity-80 mt-1">Sicklerville, New Jersey</div>
        </div>
        <div class="bg-white/20 backdrop-blur-sm rounded-lg p-2 text-center min-w-[60px]">
          <div class="text-xs uppercase font-bold">{{ monthName }}</div>
          <div class="text-2xl font-bold">{{ dayNum }}</div>
        </div>
      </div>

      <div class="flex justify-between text-sm opacity-90 border-t border-white/20 pt-4 mt-4">
        <div class="flex flex-col items-center">
          <BaseIcon name="lni:sun" class="mb-1" />
          <span>40°</span>
        </div>
        <div class="flex flex-col items-center">
          <BaseIcon name="lni:cloud" class="mb-1" />
          <span>10°</span>
        </div>
        <div class="flex flex-col items-center">
          <BaseIcon name="lni:thunder" class="mb-1" />
          <span>20°</span>
        </div>
        <div class="flex flex-col items-center">
          <BaseIcon name="lni:rain" class="mb-1" />
          <span>34°</span>
        </div>
        <div class="flex flex-col items-center">
          <BaseIcon name="lni:night" class="mb-1" />
          <span>22°</span>
        </div>
        <div class="flex flex-col items-center">
          <BaseIcon name="lni:sun" class="mb-1" />
          <span>26°</span>
        </div>
      </div>
    </div>
    
    <!-- Decorative Icon -->
    <BaseIcon name="lni:cloud" class="absolute -right-4 -top-4 text-9xl text-white/10" />
  </BaseCard>
</template>

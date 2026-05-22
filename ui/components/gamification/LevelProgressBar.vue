<script setup lang="ts">
interface Props {
  level: number
  currentXp: number
  xpForNextLevel: number
  progressPercentage: number
  showDetails?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showDetails: true,
})

const formattedXp = (val: number) => {
  return new Intl.NumberFormat().format(val)
}
</script>

<template>
  <div class="level-progress-bar">
    <div class="flex items-center justify-between mb-2">
      <div class="flex items-center gap-2">
        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-100 text-primary-600 font-bold text-lg">
          {{ level }}
        </div>
        <div>
          <h4 class="font-bold text-gray-900 dark:text-white leading-tight">เลเวล {{ level }}</h4>
          <p v-if="showDetails" class="text-xs text-gray-500">
            {{ formattedXp(currentXp) }} / {{ formattedXp(xpForNextLevel) }} XP
          </p>
        </div>
      </div>
      <div v-if="showDetails" class="text-xs font-medium text-primary-600">
        {{ Math.round(progressPercentage) }}%
      </div>
    </div>

    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
      <div 
        class="bg-primary-600 h-full rounded-full transition-all duration-500 ease-out"
        :style="{ width: `${progressPercentage}%` }"
      ></div>
    </div>
  </div>
</template>

<style scoped>
.level-progress-bar {
  @apply w-full;
}
</style>

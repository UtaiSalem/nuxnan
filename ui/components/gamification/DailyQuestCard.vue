<script setup lang="ts">
interface Quest {
  id: number
  title: string
  description: string
  progress: number
  target: number
  is_completed: boolean
  reward_claimed: boolean
  reward: {
    points: number
    xp: number
  }
}

const props = defineProps<{
  quest: Quest
}>()

const emit = defineEmits(['claim'])

const progressPercentage = computed(() => {
  return Math.min(Math.round((props.quest.progress / props.quest.target) * 100), 100)
})

const handleClaim = () => {
  if (props.quest.is_completed && !props.quest.reward_claimed) {
    emit('claim', props.quest.id)
  }
}
</script>

<template>
  <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm transition-all hover:shadow-md">
    <div class="flex items-start justify-between mb-4">
      <div class="flex gap-3">
        <div class="p-2 rounded-lg bg-yellow-50 text-yellow-500">
          <i class="pi pi-bolt text-xl"></i>
        </div>
        <div>
          <h4 class="font-bold text-gray-900 dark:text-white leading-tight">{{ quest.title }}</h4>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ quest.description }}</p>
        </div>
      </div>
      
      <div class="text-right">
        <div class="flex flex-col items-end gap-1">
          <span v-if="quest.reward.points > 0" class="text-xs font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">
            +{{ quest.reward.points }} แต้ม
          </span>
          <span v-if="quest.reward.xp > 0" class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">
            +{{ quest.reward.xp }} XP
          </span>
        </div>
      </div>
    </div>

    <div class="space-y-3">
      <div class="flex items-center justify-between text-xs">
        <span class="text-gray-500 font-medium">ความคืบหน้า</span>
        <span class="font-bold" :class="quest.is_completed ? 'text-green-600' : 'text-gray-700 dark:text-gray-200'">
          {{ quest.progress }} / {{ quest.target }}
        </span>
      </div>
      
      <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
        <div 
          class="h-full rounded-full transition-all duration-500 ease-out"
          :class="quest.is_completed ? 'bg-green-500' : 'bg-yellow-400'"
          :style="{ width: `${progressPercentage}%` }"
        ></div>
      </div>

      <button
        v-if="quest.is_completed && !quest.reward_claimed"
        @click="handleClaim"
        class="w-full mt-2 py-2 px-4 bg-green-600 hover:bg-green-700 text-white text-sm font-bold rounded-lg shadow-sm transition-colors flex items-center justify-center gap-2"
      >
        <i class="pi pi-gift"></i>
        รับรางวัล
      </button>

      <div 
        v-else-if="quest.reward_claimed"
        class="w-full mt-2 py-2 px-4 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm font-bold rounded-lg flex items-center justify-center gap-2"
      >
        <i class="pi pi-check-circle"></i>
        รับแล้ว
      </div>
    </div>
  </div>
</template>

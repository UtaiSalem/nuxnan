<script setup lang="ts">
import { ref, watch } from 'vue'
import { Icon } from '@iconify/vue'

interface Achievement {
  key: string; name: string; icon: string; xp_reward: number; description: string
}
const props = defineProps<{ achievements: Achievement[] }>()
const visible = ref(false)
const current = ref<Achievement | null>(null)
let queue: Achievement[] = []

watch(() => props.achievements, (list) => {
  if (list && list.length > 0) {
    queue = [...queue, ...list]
    if (!visible.value) {
      showNext()
    }
  }
}, { deep: true, immediate: true })

function showNext() {
  if (queue.length === 0) { 
    visible.value = false
    current.value = null
    return 
  }
  current.value = queue.shift()!
  visible.value = true
  
  // Show for 3.5 seconds then transition to next
  setTimeout(() => {
    visible.value = false
    setTimeout(showNext, 500) // Small gap between multiple achievements
  }, 3500)
}
</script>

<template>
  <Teleport to="body">
    <Transition name="achievement-slide">
      <div v-if="visible && current"
        class="fixed bottom-8 right-8 z-[100] bg-gradient-to-r from-yellow-500 to-amber-500 rounded-2xl p-5 shadow-2xl flex items-center gap-4 min-w-[320px] border border-yellow-400/30 overflow-hidden">
        
        <!-- Shine effect -->
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full animate-[shine_2s_infinite]"></div>

        <div class="relative flex items-center gap-4">
          <span class="text-5xl drop-shadow-lg">{{ current.icon }}</span>
          <div>
            <p class="text-yellow-900 font-black text-[10px] uppercase tracking-widest leading-none mb-1">Achievement Unlocked!</p>
            <p class="text-slate-900 font-black text-xl leading-tight">{{ current.name }}</p>
            <p class="text-yellow-800 text-sm font-bold flex items-center gap-1">
              <Icon icon="heroicons:sparkles" class="text-yellow-700" />
              +{{ current.xp_reward }} XP
            </p>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
@keyframes shine {
  0% { transform: translateX(-100%) skewX(-15deg); }
  50%, 100% { transform: translateX(200%) skewX(-15deg); }
}

.achievement-slide-enter-active {
  transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.achievement-slide-leave-active {
  transition: all 0.4s cubic-bezier(0.36, 0, 0.66, -0.56);
}
.achievement-slide-enter-from {
  transform: translateY(100px) scale(0.8);
  opacity: 0;
}
.achievement-slide-leave-to {
  transform: translateX(100px);
  opacity: 0;
}
</style>

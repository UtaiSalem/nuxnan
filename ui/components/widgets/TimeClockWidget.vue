<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const hours = ref('00')
const minutes = ref('00')
const seconds = ref('00')
const date = ref('')

const updateTime = () => {
  const now = new Date()
  hours.value = String(now.getHours()).padStart(2, '0')
  minutes.value = String(now.getMinutes()).padStart(2, '0')
  seconds.value = String(now.getSeconds()).padStart(2, '0')
  
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
  date.value = now.toLocaleDateString('en-US', options)
}

let interval = null

onMounted(() => {
  updateTime()
  interval = setInterval(updateTime, 1000)
})

onBeforeUnmount(() => {
  if (interval) clearInterval(interval)
})
</script>

<template>
  <div class="relative overflow-hidden rounded-lg bg-gradient-to-br from-vikinger-purple to-vikinger-cyan p-6 shadow-lg">
    <!-- Background Image -->
    <div class="absolute inset-0 opacity-10">
      <div class="absolute top-4 right-4 w-20 h-20 bg-white rounded-full animate-pulse"></div>
      <div class="absolute bottom-4 left-4 w-16 h-16 bg-white rounded-full animate-pulse" style="animation-delay: 0.5s;"></div>
    </div>
    
    <!-- Clock Icon -->
    <div class="absolute top-4 right-4 w-12 h-12 opacity-20">
      <svg viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg">
        <circle cx="12" cy="12" r="10" stroke="white" stroke-width="2" fill="none"/>
        <path d="M12 6v6l4 2" stroke="white" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </div>

    <!-- Time Display -->
    <div class="relative z-10 text-center text-white">
      <div class="text-5xl font-bold mb-2 font-mono tracking-wider">
        {{ hours }}<span class="animate-pulse">:</span>{{ minutes }}
      </div>
      <div class="text-sm opacity-90 font-medium">{{ date }}</div>
    </div>
  </div>
</template>

<style scoped>
@keyframes pulse {
  0%, 100% {
    opacity: 0.1;
    transform: scale(1);
  }
  50% {
    opacity: 0.3;
    transform: scale(1.1);
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

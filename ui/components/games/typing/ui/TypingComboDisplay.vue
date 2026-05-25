<script setup lang="ts">
import { gsap } from 'gsap'

interface Props {
  combo: number
}

const props = defineProps<Props>()
const comboRef = ref<HTMLElement | null>(null)

watch(() => props.combo, (newVal) => {
  if (newVal > 0) {
    nextTick(() => {
      if (comboRef.value) {
        gsap.fromTo(comboRef.value, 
          { scale: 1.5, opacity: 0, y: -20 },
          { scale: 1, opacity: 1, y: 0, duration: 0.3, ease: 'back.out(2)' }
        )
      }
    })
  }
})

const comboColorClass = computed(() => {
  if (props.combo >= 50) return 'text-purple-500'
  if (props.combo >= 30) return 'text-red-500'
  if (props.combo >= 20) return 'text-orange-500'
  if (props.combo >= 10) return 'text-blue-500'
  return 'text-primary-500'
})

const comboLabel = computed(() => {
  if (props.combo >= 100) return 'GODLIKE!'
  if (props.combo >= 50) return 'UNSTOPPABLE!'
  if (props.combo >= 30) return 'AMAZING!'
  if (props.combo >= 20) return 'GREAT!'
  if (props.combo >= 10) return 'GOOD!'
  return 'COMBO'
})
</script>

<template>
  <div class="h-16 flex items-center justify-center overflow-hidden">
    <div 
      v-if="combo >= 5" 
      ref="comboRef"
      class="flex flex-col items-center"
    >
      <span class="text-xs font-black uppercase tracking-[0.2em]" :class="comboColorClass">
        {{ comboLabel }}
      </span>
      <span class="text-4xl font-black italic" :class="comboColorClass">
        {{ combo }}x
      </span>
    </div>
  </div>
</template>

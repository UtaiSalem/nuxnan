<script setup lang="ts">
import BaseBadge from '../atoms/BaseBadge.vue'

defineProps({
  badges: {
    type: Array as PropType<string[]>,
    default: () => []
  }
})
</script>

<template>
  <div class="flex flex-wrap gap-2">
    <BaseBadge 
      v-for="(badge, index) in badges" 
      :key="index" 
      variant="secondary" 
      rounded
    >
      {{ badge }}
    </BaseBadge>
  </div>
</template>

<template>
  <section class="bg-gradient-to-r from-amber-50 to-orange-50 border-t-4 border-amber-400 px-6 py-4 mb-1 relative">
    <div class="flex items-center justify-between">
      <div class="flex-1 pr-4">
        <h4 class="text-lg font-bold text-secondary-900 mb-1">
          Good {{ timeOfDay }}, {{ userName }}
        </h4>
        <p class="text-sm text-secondary-600">
          May this {{ timeOfDay.toLowerCase() }} be light, blessed, productive and happy for you.
        </p>
      </div>
      
      <!-- Day Icon -->
      <div class="flex-shrink-0">
        <img 
          src="/images/resources/good-noon.png" 
          alt="Good day" 
          class="w-20 h-20 object-contain animate-bounce-in"
        />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
defineProps<{
  userName?: string
}>()

const timeOfDay = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Morning'
  if (hour < 17) return 'Afternoon'
  return 'Evening'
})
</script>

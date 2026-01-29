<template>
  <div class="bg-white shadow rounded-lg overflow-hidden">
      <div class="h-40 bg-gray-300 w-full bg-cover bg-center" :style="{ backgroundImage: `url(${academy.cover || '/images/default-cover.jpg'})` }"></div>
      <div class="p-4">
          <div class="flex items-center -mt-12">
              <img :src="academy.logo || '/images/plearnd-logo.png'" class="w-16 h-16 rounded-full border-4 border-white bg-white" />
              <h3 class="ml-2 font-bold text-lg mt-8">{{ academy.name }}</h3>
          </div>
          <p class="text-gray-600 mt-2 text-sm">{{ academy.slogan }}</p>
      </div>
  </div>
</template>
<script setup>
defineProps({
    academy: Object
})
</script>

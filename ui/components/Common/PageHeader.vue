<template>
  <section class="relative bg-gradient-to-r from-primary-500 to-primary-600 text-white py-8 mb-1">
    <!-- Animated Background -->
    <div 
      class="absolute inset-0 opacity-20 bg-cover bg-center"
      :style="{ backgroundImage: 'url(/images/resources/animate-bg.png)' }"
    ></div>
    
    <div class="container mx-auto px-4 relative z-10">
      <div class="flex flex-col space-y-2">
        <!-- Title -->
        <h5 class="text-2xl font-bold">{{ title }}</h5>
        
        <!-- Breadcrumb -->
        <ul class="flex items-center space-x-2 text-sm text-primary-50">
          <li v-for="(item, index) in breadcrumbs" :key="index" class="flex items-center">
            <NuxtLink 
              v-if="item.path"
              :to="item.path"
              class="hover:text-white transition-colors duration-200"
            >
              {{ item.label }}
            </NuxtLink>
            <span v-else>{{ item.label }}</span>
            <Icon 
              v-if="index < breadcrumbs.length - 1"
              name="mdi:chevron-right" 
              class="w-4 h-4 mx-1"
            />
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
interface Breadcrumb {
  label: string
  path?: string
}

defineProps<{
  title: string
  breadcrumbs?: Breadcrumb[]
}>()
</script>

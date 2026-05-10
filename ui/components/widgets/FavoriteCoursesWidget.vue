<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const api = useApi()
const config = useRuntimeConfig()
const favoriteCourses = ref([])
const isLoading = ref(true)

const fetchFavoriteCourses = async () => {
  isLoading.value = true
  try {
    const res = (await api.get('/api/courses/favorites?limit=5')) as any
    if (res.data) {
      favoriteCourses.value = res.data.slice(0, 5)
    }
  } catch (error) {
    console.error('Failed to fetch favorite courses', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchFavoriteCourses()
})
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm mb-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900 dark:text-white">รายการโปรด</h3>
      <NuxtLink to="/favourite" class="text-xs text-blue-500 hover:underline">ดูทั้งหมด</NuxtLink>
    </div>

    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="flex gap-3 animate-pulse">
        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg shrink-0"></div>
        <div class="flex-1 space-y-2 py-1">
          <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
          <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
        </div>
      </div>
    </div>

    <div v-else-if="favoriteCourses.length > 0" class="space-y-3">
      <NuxtLink
        v-for="course in favoriteCourses"
        :key="course.id"
        :to="`/Learn/Courses/${course.id}`"
        class="flex gap-3 group hover:bg-gray-50 dark:hover:bg-gray-700/50 p-2 -mx-2 rounded-lg transition-colors"
      >
        <div class="relative shrink-0 w-12 h-12">
          <img
            :src="course.cover && !course.cover.startsWith('http') 
              ? `${config.public.apiBase}/storage/images/courses/covers/${course.cover}` 
              : (course.cover || `${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg`)"
            :alt="course.name"
            class="w-full h-full object-cover rounded-lg shadow-sm"
          />
        </div>
        <div class="flex-1 min-w-0">
          <h4
            class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-blue-500 transition-colors"
          >
            {{ course.name }}
          </h4>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-xs text-gray-500 dark:text-gray-400">
               {{ course.category || 'General' }}
            </span>
          </div>
        </div>
        <div
          class="flex items-center justify-center text-gray-400 group-hover:text-blue-500 group-hover:translate-x-1 transition-all"
        >
          <Icon icon="fluent:chevron-right-20-regular" class="w-5 h-5" />
        </div>
      </NuxtLink>
    </div>

    <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400 text-sm">
      <div class="inline-flex items-center justify-center w-10 h-10 bg-red-50 dark:bg-red-900/10 rounded-full mb-3">
          <Icon icon="fluent:heart-24-regular" class="w-5 h-5 text-red-400" />
      </div>
      <p>ยังไม่มีคอร์สโปรด</p>
    </div>
  </div>
</template>

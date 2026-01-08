<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const api = useApi()
const config = useRuntimeConfig()
const recentCourses = ref([])
const isLoading = ref(true)

const fetchRecentCourses = async () => {
  isLoading.value = true
  try {
    const res = (await api.get('/api/me/recent-courses')) as any // Cast to any to bypass unknown type error or define interface
    if (res.success) {
      recentCourses.value = res.courses
    }
  } catch (error) {
    console.error('Failed to fetch recent courses', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchRecentCourses()
})
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm mb-6">
    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">เรียนต่อจากเดิม</h3>

    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="flex gap-3 animate-pulse">
        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg shrink-0"></div>
        <div class="flex-1 space-y-2 py-1">
          <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
          <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
        </div>
      </div>
    </div>

    <div v-else-if="recentCourses.length > 0" class="space-y-3">
      <NuxtLink
        v-for="course in recentCourses"
        :key="course.id"
        :to="`/courses/${course.id}`"
        class="flex gap-3 group hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 p-2 -mx-2 rounded-lg transition-colors"
      >
        <div class="relative shrink-0 w-12 h-12">
          <img
            :src="course.cover ? course.cover : `${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg`"
            :alt="course.name"
            class="w-full h-full object-cover rounded-lg shadow-sm"
          />
        </div>
        <div class="flex-1 min-w-0">
          <h4
            class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-vikinger-purple transition-colors"
          >
            {{ course.name }}
          </h4>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ course.code ? course.code : 'Course' }}
            </span>
          </div>
        </div>
        <div
          class="flex items-center justify-center text-gray-400 group-hover:text-vikinger-purple group-hover:translate-x-1 transition-all"
        >
          <Icon icon="fluent:chevron-right-20-regular" class="w-5 h-5" />
        </div>
      </NuxtLink>
    </div>

    <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
      <Icon icon="fluent:history-20-regular" class="w-8 h-8 mx-auto mb-2 opacity-50" />
      <p>ยังไม่มีรายการที่ดูเร็วๆ นี้</p>
    </div>
  </div>
</template>

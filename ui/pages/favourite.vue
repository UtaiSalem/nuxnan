<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-24 lg:pb-0">
    <div class="container mx-auto px-0 sm:px-4 py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
             <Icon icon="fluent:heart-24-filled" class="text-red-500 w-6 h-6" />
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">รายการโปรดของฉัน</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">คอร์สที่คุณบันทึกไว้เพื่อดูภายหลัง</p>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="pending" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div v-for="i in 4" :key="i" class="bg-white dark:bg-gray-800 rounded-xl h-[340px] animate-pulse shadow-sm p-4">
             <div class="h-40 bg-gray-200 dark:bg-gray-700 rounded-lg mb-4"></div>
             <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
             <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
        </div>
      </div>

      <!-- Content -->
      <div v-else-if="courses.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <CourseCard 
          v-for="(course, index) in courses" 
          :key="course.id" 
          :course="course"
          :index="index"
          class="h-full"
        />
      </div>

      <!-- Empty State -->
      <div v-else class="flex flex-col items-center justify-center py-20 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-red-50 dark:bg-red-900/10 rounded-full mb-4">
            <Icon icon="fluent:heart-broken-24-regular" class="w-8 h-8 text-red-500/50" />
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">ยังไม่มีคอร์สในรายการโปรด</h3>
        <p class="text-gray-500 text-sm mb-6 text-center max-w-sm">
          กดหัวใจที่คอร์สที่คุณสนใจเพื่อเก็บไว้ดูภายหลัง
        </p>
        <NuxtLink 
          to="/courses" 
          class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm font-medium shadow-lg shadow-blue-500/30"
        >
          <Icon icon="fluent:compass-northwest-20-filled" />
          สำรวจคอร์สเรียน
        </NuxtLink>
      </div>

    </div>
  </div>
</template>

<script setup>
import { courseService } from '~/services/courseService'
import { Icon } from '@iconify/vue'

definePageMeta({
  title: 'My Favorites',
  middleware: 'auth'
})

// Fetch favorites
const { data, pending, refresh } = await useAsyncData('favoriteCourses', () => 
  courseService.getFavoriteCourses()
)

const courses = computed(() => data.value?.data || [])

// Watch for changes (if navigated back)
onActivated(() => {
    refresh()
})
</script>

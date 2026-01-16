<template>
  <div class="recent-courses bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <Icon icon="fluent:book-open-24-filled" class="w-5 h-5 text-blue-500" />
        คอร์สล่าสุด
      </h2>
      <NuxtLink to="/Learn/Courses/UserCourses" class="text-primary-500 hover:text-primary-600 text-sm font-medium">
        ดูทั้งหมด →
      </NuxtLink>
    </div>

    <div v-if="courses.length > 0" class="space-y-4">
      <div 
        v-for="course in courses" 
        :key="course.id"
        class="course-item flex items-start gap-4 p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border border-gray-100 dark:border-gray-700"
      >
        <div class="w-16 h-16 rounded-xl overflow-hidden flex-shrink-0">
          <div class="w-full h-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center">
            <Icon icon="fluent:book-open-24-filled" class="w-8 h-8 text-white" />
          </div>
        </div>
        
        <div class="flex-grow min-w-0">
          <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1">
            {{ course.name }}
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
            {{ course.category }}
          </p>
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
              <Icon icon="fluent:book-open-24-regular" class="w-4 h-4" />
              <span>{{ course.lessons_count }} บทเรียน</span>
            </div>
            <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
              <Icon icon="fluent:clock-24-regular" class="w-4 h-4" />
              <span>{{ course.hours }} ชม.</span>
            </div>
          </div>
          <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mt-2">
            <div 
              class="h-full bg-gradient-to-r from-blue-400 to-indigo-500 rounded-full transition-all duration-500"
              :style="{ width: `${course.progress}%` }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
      <Icon icon="fluent:book-open-24-regular" class="w-12 h-12 mx-auto mb-3" />
      <p>ยังไม่มีคอร์ส</p>
      <p class="text-sm mt-1">เริ่มลงทะเบียนคอร์ส</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Course {
  id: number
  name: string
  category: string
  lessons_count: number
  hours: number
  progress: number
}

interface Props {
  courses: Course[]
}

defineProps<Props>()
</script>

<style scoped>
.course-item {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}
</style>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

const api = useApi()
const { user } = storeToRefs(useAuthStore())
const config = useRuntimeConfig()

const courses = ref<any[]>([])
const isLoading = ref(true)
const page = ref(1)
const hasMore = ref(false)

const fetchMemberedCourses = async (append = false) => {
  if (!user.value) return

  try {
    const response: any = await api.get(`/api/courses/users/${user.value.id}/membered`, {
      params: { page: page.value, per_page: 5 }
    })

    if (response.success) {
      const newCourses = response.courses || []
      if (append) {
        courses.value = [...courses.value, ...newCourses]
      } else {
        courses.value = newCourses
      }
      
      const pagination = response.pagination
      hasMore.value = pagination.current_page < pagination.last_page
    }
  } catch (error) {
    console.error('Failed to fetch membered courses:', error)
  } finally {
    isLoading.value = false
  }
}

const loadMore = () => {
  page.value++
  fetchMemberedCourses(true)
}

const getCoverUrl = (course: any) => {
  if (course.cover) {
    if (course.cover.startsWith('http')) return course.cover
    return `${config.public.apiBase}/storage/images/courses/covers/${course.cover}`
  }
  return `${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg`
}

const getProgressColor = (progress: number) => {
  if (progress >= 80) return 'text-green-500'
  if (progress >= 50) return 'text-blue-500'
  return 'text-orange-500'
}

onMounted(() => {
  fetchMemberedCourses()
})
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
    <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
      <h3 class="font-bold text-gray-800 dark:text-white">รายวิชาที่เป็นสมาชิก</h3>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-700">
      <template v-if="isLoading && courses.length === 0">
        <div v-for="i in 3" :key="i" class="p-4 flex gap-3 animate-pulse">
          <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
          <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
          </div>
        </div>
      </template>

      <template v-else-if="courses.length > 0">
        <NuxtLink 
          v-for="course in courses" 
          :key="course.id"
          :to="`/courses/${course.id}`"
          class="p-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
        >
          <!-- Radial Progress / Avatar -->
          <div class="relative w-14 h-14 flex-shrink-0">
            <!-- Student: Show Progress Ring -->
            <template v-if="!course.isCourseAdmin">
               <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                <!-- Background Ring -->
                <circle
                  cx="18" cy="18" r="16"
                  fill="none"
                  stroke-width="2"
                  class="stroke-gray-200 dark:stroke-gray-700"
                />
                <!-- Progress Ring -->
                <circle
                  cx="18" cy="18" r="16"
                  fill="none"
                  stroke-width="2"
                  stroke-linecap="round"
                  :class="getProgressColor(course.auth_progress || 0)"
                  :stroke-dasharray="`${(course.auth_progress || 0) * 100 / 100} 100`" 
                />
              </svg>
            </template>
            
            <!-- Admin: Simple Border Ring -->
            <template v-else>
               <div class="absolute inset-0 rounded-full border-2 border-blue-500"></div>
            </template>

            <!-- Cover Image (Centered) -->
            <div class="absolute inset-[4px] rounded-full overflow-hidden bg-gray-100">
              <img 
                :src="getCoverUrl(course)" 
                :alt="course.name"
                class="w-full h-full object-cover"
              />
            </div>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-white line-clamp-1 group-hover:text-blue-500 transition-colors">
              {{ course.name }}
            </h4>
            
            <div class="flex items-center gap-2 mt-1">
              <!-- Admin Badge -->
              <span 
                v-if="course.isCourseAdmin"
                class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
              >
                Admin
              </span>
              
              <!-- Student Progress Text -->
              <span 
                v-else 
                class="text-xs font-medium"
                :class="getProgressColor(course.auth_progress || 0)"
              >
                {{ (course.auth_progress).toFixed(0) || 0 }}% Completed
              </span>
            </div>
          </div>
        </NuxtLink>

        <!-- Load More -->
        <div v-if="hasMore" class="p-2 text-center">
            <button 
              @click="loadMore"
              class="text-xs text-blue-500 hover:underline py-2"
            >
              โหลดเพิ่มเติม
            </button>
        </div>
      </template>

      <div v-else class="p-8 text-center text-gray-500">
        <Icon icon="fluent:hat-graduation-24-regular" class="w-12 h-12 mx-auto mb-2 opacity-50" />
        <p class="text-sm">คุณยังไม่ได้เป็นสมาชิกรายวิชาใด</p>
      </div>
    </div>
  </div>
</template>

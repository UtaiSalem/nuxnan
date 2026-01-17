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
          <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg shrink-0"></div>
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
          <!-- Course Cover Image -->
          <div class="relative shrink-0 w-12 h-12">
            <img 
              :src="getCoverUrl(course)" 
              :alt="course.name"
              class="w-full h-full object-cover rounded-lg shadow-sm"
            />
            <!-- Admin Badge Overlay -->
            <div 
              v-if="course.isCourseAdmin"
              class="absolute -top-1 -right-1 w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center"
            >
              <Icon icon="fluent:shield-checkmark-16-filled" class="w-3 h-3 text-white" />
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

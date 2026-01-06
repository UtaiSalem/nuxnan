<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const api = useApi()
const { user } = useAuth()
const myCourses = ref([])
const isLoading = ref(true)

const fetchMyCourses = async () => {
  if (!user.value) return

  isLoading.value = true
  try {
    // Note: getAuthMemberedCourses ignores the ID parameter but requires one in the route
    const res = (await api.get(`/api/courses/users/${user.value.id}/membered`)) as any
    if (res.success) {
      myCourses.value = res.courses.data || res.courses
    }
  } catch (error) {
    console.error('Failed to fetch my courses', error)
  } finally {
    isLoading.value = false
  }
}

const getCoverUrl = (course: any) => {
  if (course.cover) {
    if (course.cover.startsWith('http')) {
      return course.cover
    }
    return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/${course.cover}`
  }
  return 'https://via.placeholder.com/150'
}

onMounted(() => {
  if (user.value) {
    fetchMyCourses()
  }
})

// Watch for user changes (e.g. reload)
watch(user, (newUser) => {
  if (newUser) {
    fetchMyCourses()
  }
})
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm mb-6">
    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">คอร์สของฉัน</h3>

    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="flex gap-3 animate-pulse">
        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg shrink-0"></div>
        <div class="flex-1 space-y-2 py-1">
          <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
          <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
        </div>
      </div>
    </div>

    <div v-else-if="myCourses.length > 0" class="space-y-4">
      <NuxtLink
        v-for="course in myCourses"
        :key="course.id"
        :to="`/courses/${course.id}`"
        class="flex items-start gap-3 group hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 p-2 -mx-2 rounded-lg transition-colors"
      >
        <div class="relative shrink-0 w-12 h-12 mt-1">
          <img
            :src="getCoverUrl(course)"
            :alt="course.name"
            class="w-full h-full object-cover rounded-lg shadow-sm"
            @error="($event.target as HTMLImageElement).src = 'https://via.placeholder.com/150'"
          />
        </div>
        <div class="flex-1 min-w-0">
          <h4
            class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2 leading-tight group-hover:text-vikinger-purple transition-colors mb-1"
          >
            {{ course.name }}
          </h4>
          <div class="flex items-center justify-between">
            <span class="text-xs text-gray-500 dark:text-gray-400 truncate">
              {{
                course.user
                  ? course.user.name
                  : course.instructor
                  ? course.instructor.name
                  : 'Unknown'
              }}
            </span>
          </div>
        </div>
        <div
          class="flex items-center justify-center text-gray-400 group-hover:text-vikinger-purple transition-colors mt-2"
        >
          <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5" />
        </div>
      </NuxtLink>
    </div>

    <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
      <Icon icon="fluent:hat-graduation-24-regular" class="w-8 h-8 mx-auto mb-2 opacity-50" />
      <p>คุณยังไม่ได้ลงทะเบียนเรียน</p>
    </div>
  </div>
</template>

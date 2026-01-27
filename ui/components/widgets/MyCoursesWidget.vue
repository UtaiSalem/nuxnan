<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

const api = useApi()
const { user } = useAuth()
const authStore = useAuthStore()
const points = computed(() => Number(authStore.points))
const createCourseThreshold = ref(100) // Default
const canCreateCourse = computed(() => points.value >= Number(createCourseThreshold.value))
const myCourses = ref([])
const isLoading = ref(true)

const page = ref(1)
const lastPage = ref(1)
const isLoadingMore = ref(false)
const invitations = ref<any[]>([])

const fetchInvitations = async () => {
  if (!user.value) return
  try {
    const res: any = await api.get('/api/me/course-invitations')
    if (res.success) {
      invitations.value = res.invitations
    }
  } catch (error) {
    console.error('Failed to fetch invitations', error)
  }
}

const respondToInvitation = async (invitation: any, accept: boolean) => {
  try {
    const action = accept ? 'accept' : 'decline'
    const res: any = await api.post(`/api/courses/${invitation.course.id}/admins/invitations/${invitation.id}/${action}`)
    if (res.success) {
      // Remove from list
      invitations.value = invitations.value.filter(i => i.id !== invitation.id)
      // Refresh my courses if accepted
      if (accept) {
        fetchMyCourses()
      }
    }
  } catch (error: any) {
    alert(error.response?.data?.message || 'ทำรายการไม่สำเร็จ')
  }
}

const fetchMyCourses = async (isLoadMore = false) => {
  if (!user.value) return

  if (isLoadMore) {
    isLoadingMore.value = true
  } else {
    isLoading.value = true
  }
  
  // Fetch invitations only on initial load
  if (!isLoadMore) {
    fetchInvitations()
  }

  try {
    const res = (await api.get(`/api/courses/users/${user.value.id}/my-courses`, {
      params: {
        page: page.value,
        per_page: 5
      }
    })) as any

    if (res.success) {
      // Update threshold from API
      if (res.create_course_threshold !== undefined) {
        createCourseThreshold.value = res.create_course_threshold
      }

      const newCourses = res.courses.data || res.courses
      
      if (isLoadMore) {
        myCourses.value.push(...newCourses)
      } else {
        myCourses.value = newCourses
      }

      // Update pagination info
      if (res.pagination && res.pagination.last_page) {
        lastPage.value = res.pagination.last_page
      } else if (res.courses.last_page) {
        // Fallback for older responses if needed
        lastPage.value = res.courses.last_page
      }
    }
  } catch (error) {
    console.error('Failed to fetch my courses', error)
  } finally {
    isLoading.value = false
    isLoadingMore.value = false
  }
}

const loadMore = () => {
  if (page.value < lastPage.value) {
    page.value++
    fetchMyCourses(true)
  }
}


const getCoverUrl = (course: any) => {
  if (course.cover) {
    if (course.cover.startsWith('http')) {
      return course.cover
    }
    return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/${course.cover}`
  }
  return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/default_cover.jpg`
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

const formatPoints = (num: number) => {
  return new Intl.NumberFormat().format(num)
}
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm mb-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900 dark:text-white">คอร์สของฉัน</h3>
      
      <!-- Top Right Plus Icon Button -->
      <NuxtLink 
        v-if="canCreateCourse"
        to="/courses/create" 
        class="text-xs font-medium text-white bg-vikinger-purple hover:bg-vikinger-purple-dark p-1.5 rounded-lg transition-colors shadow-sm shadow-vikinger-purple/50"
        title="สร้างคอร์สใหม่"
      >
        <Icon icon="mdi:plus" class="w-4 h-4" />
      </NuxtLink>
      
      <div v-else class="group relative">
         <button disabled class="text-xs font-medium text-gray-400 bg-gray-100 dark:bg-gray-700 dark:text-gray-500 p-1.5 rounded-lg cursor-not-allowed">
            <Icon icon="mdi:lock" class="w-4 h-4" />
         </button>
         <!-- Tooltip -->
         <div class="absolute bottom-full right-0 mb-2 w-48 bg-gray-900 text-white text-xs rounded-lg py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 text-center">
            ต้องมีคะแนนสะสม {{ formatPoints(createCourseThreshold) }} คะแนน
         </div>
      </div>
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

    <div v-else class="space-y-4">
      <!-- Invitations Section -->
      <div v-if="invitations.length > 0" class="mb-4">
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">คำเชิญผู้ดูแล</h4>
        <div class="space-y-2">
          <div v-for="invitation in invitations" :key="invitation.id" class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-100 dark:border-blue-900/30">
            <div class="flex items-start gap-3">
               <img
                  :src="getCoverUrl({ cover: invitation.course.cover })"
                  class="w-10 h-10 object-cover rounded-md flex-shrink-0"
                />
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ invitation.course.name }}</p>
                  <p class="text-xs text-blue-600 dark:text-blue-400">
                    เชิญเป็น: {{ invitation.role_name }}
                  </p>
                  <div class="flex gap-2 mt-2">
                    <button @click="respondToInvitation(invitation, true)" class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700">ตอบรับ</button>
                    <button @click="respondToInvitation(invitation, false)" class="text-xs text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 px-2 py-1 rounded">ปฏิเสธ</button>
                  </div>
                </div>
            </div>
          </div>
        </div>
        <div class="h-px bg-gray-100 dark:bg-gray-700 my-4"></div>
      </div>

      <template v-if="myCourses.length > 0">
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
              @error="($event.target as HTMLImageElement).src = `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/default_cover.jpg`"
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
      </template>

      <div v-else class="text-center py-4 text-gray-500 dark:text-gray-400 text-sm">
        <Icon icon="fluent:hat-graduation-24-regular" class="w-8 h-8 mx-auto mb-2 opacity-50" />
        <p>คุณยังไม่ได้สร้างรายวิชา</p>
      </div>

      <!-- Footer Buttons: Load More & Create Course -->
      <div class="space-y-2 pt-2">
        <button 
            v-if="page < lastPage" 
            @click="loadMore" 
            :disabled="isLoadingMore"
            class="w-full py-2 text-xs font-medium text-vikinger-purple bg-vikinger-purple/10 hover:bg-vikinger-purple/20 rounded-lg transition-colors flex items-center justify-center gap-2"
        >
            <Icon v-if="isLoadingMore" icon="svg-spinners:ring-resize" class="w-4 h-4" />
            <span v-else>โหลดเพิ่ม</span>
        </button>

        <!-- Big Create Course Button at Bottom -->
        <div v-if="points >= createCourseThreshold">
            <NuxtLink 
                to="/courses/create" 
                class="w-full py-2 text-xs font-medium text-white bg-vikinger-purple hover:bg-vikinger-purple-dark rounded-lg transition-colors flex items-center justify-center gap-2 shadow-sm shadow-vikinger-purple/50"
            >
                <Icon icon="mdi:plus" class="w-4 h-4" />
                สร้างคอร์สใหม่
            </NuxtLink>
        </div>
        <div v-else class="group relative">
            <button disabled class="w-full py-2 text-xs font-medium text-gray-400 bg-gray-100 dark:bg-gray-700 dark:text-gray-500 rounded-lg cursor-not-allowed flex items-center justify-center gap-2">
                <Icon icon="mdi:lock" class="w-4 h-4" />
                สร้างคอร์สใหม่
            </button>
             <!-- Tooltip -->
            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-max bg-gray-900 text-white text-xs rounded-lg py-1 px-3 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10 text-center whitespace-nowrap">
                ต้องมีคะแนนสะสม {{ formatPoints(createCourseThreshold) }} คะแนน เพื่อสร้างคอร์สใหม่
            </div>
        </div>
      </div>
    </div>
  </div>
</template>

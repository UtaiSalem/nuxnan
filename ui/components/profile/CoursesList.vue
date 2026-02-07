<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseCard from '~/components/learn/course/CourseCard.vue'

const props = defineProps<{
  userId: number | string
}>()

const activeTab = ref<'enrolled' | 'completed'>('enrolled')
const courses = ref<any[]>([])
const loading = ref(false)
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
const config = useRuntimeConfig()

const fetchCourses = async (refresh = false) => {
  if (refresh) {
    page.value = 1
    courses.value = []
  }
  
  if (loading.value) return

  loading.value = true
  try {
    const { data }: any = await useFetch(`${config.public.apiBase}/api/users/${props.userId}/membered-courses`, {
      query: {
        page: page.value,
        status: activeTab.value,
        per_page: 8
      },
      headers: {
        Authorization: `Bearer ${useCookie('auth_token').value}`
      }
    })

    if (data.value && data.value.success) {
      if (refresh) {
        courses.value = data.value.courses
      } else {
        courses.value = [...courses.value, ...data.value.courses]
      }
      lastPage.value = data.value.pagination.last_page
      total.value = data.value.pagination.total
    }
  } catch (error) {
    console.error('Failed to fetch courses:', error)
  } finally {
    loading.value = false
  }
}

// Watch tab change
watch(activeTab, () => {
  fetchCourses(true)
})

// Initial fetch
onMounted(() => {
  fetchCourses(true)
})

const loadMore = () => {
  if (page.value < lastPage.value) {
    page.value++
    fetchCourses()
  }
}

const switchTab = (tab: 'enrolled' | 'completed') => {
  activeTab.value = tab
}
</script>

<template>
  <div class="space-y-6">
    <!-- Filter Tabs -->
    <div class="flex items-center gap-4 border-b border-gray-700 pb-1">
      <button
        @click="switchTab('enrolled')"
        class="pb-2 px-2 text-sm font-medium transition-colors relative"
        :class="activeTab === 'enrolled' ? 'text-vikinger-cyan' : 'text-gray-400 hover:text-white'"
      >
        <span class="flex items-center gap-2">
          <Icon icon="fluent:book-open-24-regular" class="w-5 h-5" />
          กำลังเรียน (Enrolled)
        </span>
        <div 
          v-if="activeTab === 'enrolled'" 
          class="absolute bottom-[-1px] left-0 w-full h-0.5 bg-vikinger-cyan rounded-t-full shadow-[0_0_10px_rgba(33,212,253,0.5)]"
        ></div>
      </button>

      <button
        @click="switchTab('completed')"
        class="pb-2 px-2 text-sm font-medium transition-colors relative"
        :class="activeTab === 'completed' ? 'text-vikinger-cyan' : 'text-gray-400 hover:text-white'"
      >
        <span class="flex items-center gap-2">
          <Icon icon="fluent:certificate-24-regular" class="w-5 h-5" />
          เรียนจบแล้ว (Completed)
        </span>
        <div 
          v-if="activeTab === 'completed'" 
          class="absolute bottom-[-1px] left-0 w-full h-0.5 bg-vikinger-cyan rounded-t-full shadow-[0_0_10px_rgba(33,212,253,0.5)]"
        ></div>
      </button>
    </div>

    <!-- Content -->
    <div v-if="loading && courses.length === 0" class="flex justify-center py-12">
       <Icon icon="svg-spinners:3-dots-fade" class="w-12 h-12 text-gray-400" />
    </div>

    <div v-else-if="courses.length > 0">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <div v-for="course in courses" :key="course.id" class="h-full">
            <CourseCard :course="course" />
        </div>
      </div>
      
      <!-- Load More -->
      <div v-if="page < lastPage" class="mt-8 text-center">
        <button 
           @click="loadMore"
           :disabled="loading"
           class="px-6 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-full text-sm font-bold transition-colors disabled:opacity-50"
        >
           <span v-if="loading" class="flex items-center gap-2">
             <Icon icon="svg-spinners:ring-resize" /> Loading...
           </span>
           <span v-else>Download More</span>
        </button>
      </div>
    </div>

    <div v-else class="text-center py-16 bg-gray-800/30 rounded-xl border border-gray-700/50">
      <div class="w-16 h-16 bg-gray-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
        <Icon 
          :icon="activeTab === 'enrolled' ? 'fluent:book-off-24-regular' : 'fluent:certificate-off-24-regular'" 
          class="w-8 h-8 text-gray-500" 
        />
      </div>
      <h3 class="text-white font-bold text-lg mb-1">
        {{ activeTab === 'enrolled' ? 'ไม่มีคอร์สที่กำลังเรียน' : 'ยังเรียนไม่จบคอร์สใดๆ' }}
      </h3>
      <p class="text-gray-400 text-sm">
        {{ activeTab === 'enrolled' ? 'ผู้ใช้นี้ยังไม่ได้ลงทะเบียนเรียนในคอร์สใดๆ' : 'เมื่อเรียนจบคอร์ส คอร์สจะปรากฏที่นี่' }}
      </p>
    </div>
  </div>
</template>

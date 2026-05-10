<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'
import MyCoursesWidget from '~/components/widgets/MyCoursesWidget.vue'
import MemberedCoursesWidget from '~/components/widgets/MemberedCoursesWidget.vue'
import RecentlyViewedCoursesWidget from '~/components/widgets/RecentlyViewedCoursesWidget.vue'
import FavoriteCoursesWidget from '~/components/widgets/FavoriteCoursesWidget.vue'
import CourseCard from '~/components/CourseCard.vue'
import CourseSearchFilterWidget from '~/components/widgets/CourseSearchFilterWidget.vue'


definePageMeta({
  layout: false,
  middleware: 'auth',
})

useHead({
  title: 'รายวิชาทั้งหมด - Nuxnan',
})

const api = useApi()
const router = useRouter()

// State
const courses = ref<any[]>([])
const popularCourses = ref<any[]>([])
const isLoading = ref(true)
const isLoadingMore = ref(false)
const error = ref<string | null>(null)
const searchQuery = ref('')
const selectedCategory = ref('all')
const selectedLevel = ref('all')
const sortBy = ref('latest')
const selectedSemester = ref('all')
const selectedYear = ref('all')

// Semesters
const semesters = ref([
  { value: 'all', label: 'ทุกภาคเรียน' },
  { value: '1', label: 'ภาคเรียนที่ 1' },
  { value: '2', label: 'ภาคเรียนที่ 2' },
  { value: '3', label: 'ภาคเรียนที่ 3' },
  { value: 'summer', label: 'Summer' }
])

// Years
const years = ref([
  { value: 'all', label: 'ทุกปีการศึกษา' }
])

// Pagination
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  perPage: 8,
})
const hasMorePages = computed(() => pagination.value.currentPage < pagination.value.lastPage)

// Categories
const categories = ref([
  { value: 'all', label: 'ทุกหมวดหมู่' }
])

// Levels
const levels = ref([
  { value: 'all', label: 'ทุกระดับ' }
])

// Sort options
const sortOptions = [
  { value: 'latest', label: 'ล่าสุด' },
  { value: 'popular', label: 'ยอดนิยม' },
  { value: 'price-low', label: 'ราคาต่ำ-สูง' },
  { value: 'price-high', label: 'ราคาสูง-ต่ำ' },
  { value: 'rating', label: 'คะแนนสูงสุด' },
]

// Fetch courses
const fetchCourses = async (page = 1, append = false) => {
  if (page === 1) {
    isLoading.value = true
  } else {
    isLoadingMore.value = true
  }
  error.value = null

  try {
    const params = new URLSearchParams({
      page: String(page),
      per_page: String(pagination.value.perPage),
    })

    if (searchQuery.value) {
      params.append('search', searchQuery.value)
    }
    if (selectedCategory.value !== 'all') {
      params.append('category', selectedCategory.value)
    }
    if (selectedLevel.value !== 'all') {
      params.append('level', selectedLevel.value)
    }
    if (sortBy.value) {
      params.append('sort', sortBy.value)
    }
    if (selectedSemester.value !== 'all') {
      params.append('semester', selectedSemester.value)
    }
    if (selectedYear.value !== 'all') {
      params.append('academic_year', selectedYear.value)
    }

    const response: any = await api.get(`/api/courses?${params.toString()}`)

    // Response could be { courses: {...} } or { success: true, data: {...} }
    // Handle both response formats for backward compatibility
    const coursesData = response.courses || response.data
    
    if (coursesData) {
      const newCourses = Array.isArray(coursesData)
        ? coursesData
        : coursesData.data || []

      if (append) {
        courses.value = [...courses.value, ...newCourses]
      } else {
        courses.value = newCourses
        // Set first 3 as popular for sidebar
        popularCourses.value = newCourses.slice(0, 3)
      }

      // Update pagination
      if (coursesData.current_page !== undefined) {
        pagination.value = {
          currentPage: coursesData.current_page || page,
          lastPage: coursesData.last_page || 1,
          total: coursesData.total || 0,
          perPage: coursesData.per_page || 8,
        }
      } else {
        pagination.value.currentPage = page
        if (newCourses.length < pagination.value.perPage) {
          pagination.value.lastPage = page
        } else {
          pagination.value.lastPage = page + 1
        }
      }
    }
  } catch (err: any) {
    console.error('Error fetching courses:', err)
    error.value = 'ไม่สามารถโหลดรายวิชาได้ กรุณาลองใหม่อีกครั้ง'
  } finally {
    isLoading.value = false
    isLoadingMore.value = false
  }
}

// Load more courses
const loadMore = async () => {
  if (isLoadingMore.value || !hasMorePages.value) return
  await fetchCourses(pagination.value.currentPage + 1, true)
}

// Search handler with debounce
let searchTimeout: ReturnType<typeof setTimeout>
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchCourses(1)
  }, 300)
}

// Helpers for Sidebar (Popular Courses)
const getCoverUrl = (course: any) => {
  if (course.cover) {
    if (course.cover.startsWith('http')) {
      return course.cover
    }
    return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/${course.cover}`
  }
  return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/default_cover.jpg`
}

const goToCourse = (courseId: number) => {
  router.push(`/Learn/Courses/${courseId}`)
}
const fetchFilterOptions = async () => {
  try {
    const res: any = await api.get('/api/courses/filters')
    if (res.success) {
      // Semesters
      if (res.semesters && res.semesters.length > 0) {
         semesters.value = [
            { value: 'all', label: 'ทุกภาคเรียน' },
            ...res.semesters.map((s: string) => ({ value: s, label: `ภาคเรียนที่ ${s}` }))
         ]
      }

      // Years
      if (res.years && res.years.length > 0) {
         years.value = [
             { value: 'all', label: 'ทุกปีการศึกษา' },
              ...res.years.map((y: string) => ({ value: y, label: y }))
          ]
      } else {
        // Fallback to default years if API doesn't return any
        years.value = [
          { value: 'all', label: 'ทุกปีการศึกษา' },
          { value: '2567', label: '2567' },
          { value: '2568', label: '2568' },
          { value: '2569', label: '2569' }
        ]
      }

      // Categories
      if (res.categories && res.categories.length > 0) {
         categories.value = [
            { value: 'all', label: 'ทุกหมวดหมู่' },
             ...res.categories.map((c: string) => ({ value: c, label: c }))
         ]
      }
      
      // Levels
      if (res.levels && res.levels.length > 0) {
         levels.value = [
            { value: 'all', label: 'ทุกระดับ' },
             ...res.levels.map((l: string) => ({ value: l, label: l }))
         ]
      }
    }
  } catch (error) {
    console.error('Failed to fetch filter options', error)
  }
}

onMounted(() => {
  fetchFilterOptions()
  fetchCourses()
})

// Watch for filter changes
watch([selectedCategory, selectedLevel, sortBy, selectedSemester, selectedYear], () => {
  fetchCourses(1)
})
</script>

<template>
  <NuxtLayout name="main">
    <!-- Hero Banner -->
    <template #hero>
      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-400 shadow-xl">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-20">
          <div class="absolute inset-0" style="background-image: url('/images/resources/animate-bg.png'); background-size: cover;"></div>
        </div>
        
        <!-- Left side badges decoration -->
        <div class="absolute left-4 top-1/2 -translate-y-1/2 flex items-center gap-2">
          <!-- Badge 1 -->
          <img 
            :src="`${$config.public.apiBase}/storage/images/badge/gold-b.png`" 
            alt="badge" 
            class="w-16 h-16 md:w-20 md:h-20 drop-shadow-lg animate-bounce"
            style="animation-duration: 2s;"
          />
          <!-- Badge 2 -->
          <img 
            :src="`${$config.public.apiBase}/storage/images/badge/scientist-b.png`" 
            alt="badge" 
            class="w-12 h-12 md:w-16 md:h-16 drop-shadow-lg animate-bounce hidden sm:block"
            style="animation-duration: 2.5s; animation-delay: 0.3s;"
          />
        </div>
        
        <!-- Content -->
        <div class="relative z-10 px-6 py-8 md:py-10 ml-24 sm:ml-36 md:ml-44">
          <h1 class="text-2xl md:text-3xl font-black text-white mb-2">
            รายวิชาทั้งหมด
          </h1>
          <p class="text-blue-50 font-medium text-sm md:text-base">
            ค้นหาและเข้าร่วมเรียนรู้ในรายวิชาที่คุณสนใจ
          </p>
        </div>
        
        <!-- Right side floating badges (hidden on mobile) -->
        <div class="hidden md:flex absolute right-4 top-1/2 -translate-y-1/2 items-center gap-3">
          <img 
            :src="`${$config.public.apiBase}/storage/images/badge/globet-b.png`" 
            alt="badge" 
            class="w-10 h-10 drop-shadow-lg opacity-70 animate-pulse"
          />
          <img 
            :src="`${$config.public.apiBase}/storage/images/badge/collector-b.png`" 
            alt="badge" 
            class="w-14 h-14 drop-shadow-lg opacity-80 animate-bounce"
            style="animation-duration: 3s;"
          />
          <img 
            :src="`${$config.public.apiBase}/storage/images/badge/platinum-b.png`" 
            alt="badge" 
            class="w-12 h-12 drop-shadow-lg opacity-70 animate-pulse"
            style="animation-delay: 0.5s;"
          />
          <img 
            :src="`${$config.public.apiBase}/storage/images/badge/tycoon.png`" 
            alt="badge" 
            class="w-16 h-16 drop-shadow-lg opacity-90 animate-bounce"
            style="animation-duration: 2.8s; animation-delay: 0.2s;"
          />
        </div>
      </div>
    </template>

    <!-- Left Sidebar Slots -->
    <template #leftWidgets>
      <CourseSearchFilterWidget
        v-model:searchQuery="searchQuery"
        v-model:selectedCategory="selectedCategory"
        v-model:selectedLevel="selectedLevel"
        v-model:selectedSemester="selectedSemester"
        v-model:selectedYear="selectedYear"
        v-model:sortBy="sortBy"
        :categories="categories"
        :levels="levels"
        :semesters="semesters"
        :years="years"
        :sortOptions="sortOptions"
        @handleSearch="handleSearch"
      />
      <RecentlyViewedCoursesWidget />
      <FavoriteCoursesWidget />
    </template>

    <!-- Right Sidebar Slots -->
    <template #rightWidgets>
      <MemberedCoursesWidget />
      <MyCoursesWidget />
      <!-- Popular Courses Widget -->
      <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-vikinger-dark-100">
          <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:star-24-filled" class="w-5 h-5 text-amber-500" />
            รายวิชายอดนิยม
          </h3>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-vikinger-dark-100">
          <div
            v-for="course in popularCourses"
            :key="course.id"
            class="p-4 flex items-start gap-3 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors cursor-pointer"
            @click="goToCourse(course.id)"
          >
            <img
              :src="getCoverUrl(course)"
              :alt="course.name"
              class="w-14 h-14 rounded-lg object-cover flex-shrink-0 border border-gray-100 dark:border-vikinger-dark-50"
            />
            <div class="flex-1 min-w-0">
              <h4 class="text-xs font-bold text-gray-800 dark:text-white line-clamp-2 mb-1">
                {{ course.name }}
              </h4>
              <p class="text-[10px] text-blue-500 font-bold uppercase tracking-wider">{{ course.user?.name || 'Unknown' }}</p>
            </div>
          </div>

          <!-- Empty state -->
          <div
            v-if="popularCourses.length === 0 && !isLoading"
            class="p-4 text-center text-gray-500 text-xs italic"
          >
            ไม่มีข้อมูล
          </div>
        </div>
      </div>
    </template>

    <!-- Main Content (Center Slot) -->
    <div class="min-w-0">
      <!-- Sorting & Info Bar -->
      <div class="flex items-center justify-between mb-6 px-1">
        <div class="flex items-center gap-2">
          <div class="bg-vikinger-purple/10 text-vikinger-purple px-3 py-1 rounded-full text-xs font-black">
            {{ formatNumber(pagination.total) }} รายวิชา
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Icon icon="fluent:arrow-sort-24-regular" class="text-gray-400 w-4 h-4" />
          <select v-model="sortBy" class="bg-white dark:bg-vikinger-dark-200 border-none rounded-lg py-1.5 px-3 text-xs font-bold focus:ring-2 focus:ring-vikinger-purple dark:text-white focus:outline-none shadow-sm">
            <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <template v-if="isLoading">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
          <div
            v-for="i in 6"
            :key="i"
            class="bg-white dark:bg-vikinger-dark-200 rounded-2xl overflow-hidden animate-pulse shadow-sm h-80"
          >
            <div class="h-44 bg-gray-100 dark:bg-vikinger-dark-100"></div>
            <div class="p-5 space-y-3">
              <div class="h-4 bg-gray-100 dark:bg-vikinger-dark-100 rounded w-3/4"></div>
              <div class="h-3 bg-gray-100 dark:bg-vikinger-dark-100 rounded w-1/2"></div>
              <div class="h-8 bg-gray-100 dark:bg-vikinger-dark-100 rounded mt-4"></div>
            </div>
          </div>
        </div>
      </template>

      <!-- Error State -->
      <div
        v-else-if="error"
        class="bg-white dark:bg-vikinger-dark-200 rounded-2xl p-12 text-center shadow-sm border border-gray-100 dark:border-vikinger-dark-100"
      >
        <Icon icon="fluent:error-circle-24-regular" class="w-16 h-16 text-red-500 mx-auto mb-4" />
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">เกิดข้อผิดพลาด</h3>
        <p class="text-sm text-gray-500 mb-6">{{ error }}</p>
        <button
          @click="fetchCourses(1)"
          class="px-8 py-2.5 bg-gradient-vikinger text-white rounded-xl font-bold shadow-vikinger transition-all hover:scale-105"
        >
          ลองใหม่อีกครั้ง
        </button>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="courses.length === 0"
        class="bg-white dark:bg-vikinger-dark-200 rounded-2xl p-16 text-center shadow-sm border border-dashed border-gray-300 dark:border-vikinger-dark-100"
      >
        <Icon icon="fluent:book-search-24-regular" class="w-20 h-20 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">ไม่พบรายวิชาที่ค้นหา</h3>
        <p class="text-gray-500">ลองใช้คำค้นอื่น หรือปรับเปลี่ยนตัวกรอง</p>
      </div>

      <!-- Course Grid -->
      <template v-else>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
          <CourseCard
            v-for="(course, index) in courses"
            :key="course.id"
            :course="course"
            :index="index"
          />
        </div>

        <!-- Load More -->
        <div v-if="hasMorePages" class="mt-10 text-center">
          <button
            @click="loadMore"
            :disabled="isLoadingMore"
            class="group relative inline-flex items-center gap-2 px-8 py-3.5 bg-white dark:bg-vikinger-dark-200 text-vikinger-purple dark:text-vikinger-cyan font-black rounded-2xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 transition-all hover:shadow-md hover:-translate-y-1 active:scale-95 disabled:opacity-50"
          >
            <Icon
              :icon="isLoadingMore ? 'svg-spinners:ring-resize' : 'fluent:arrow-down-24-filled'"
              class="w-5 h-5 transition-transform group-hover:translate-y-1"
            />
            {{ isLoadingMore ? 'กำลังโหลดข้อมูล...' : 'ดูรายวิชาเพิ่มเติม' }}
          </button>
        </div>
      </template>
    </div>
  </NuxtLayout>
</template>

<style scoped>
.bg-gradient-vikinger {
  background: linear-gradient(135deg, #8B5CF6 0%, #06B6D4 100%);
}
.shadow-vikinger {
  box-shadow: 0 10px 25px -5px rgba(111, 66, 193, 0.3), 0 8px 10px -6px rgba(111, 66, 193, 0.1);
}
</style>

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
  title: 'Courses - Marketplace',
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

    // Response could be { courses: [...] } or { success: true, courses: [...] }
    if (response.courses) {
      const newCourses = Array.isArray(response.courses)
        ? response.courses
        : response.courses.data || []

      if (append) {
        courses.value = [...courses.value, ...newCourses]
      } else {
        courses.value = newCourses
        // Set first 3 as popular for sidebar
        popularCourses.value = newCourses.slice(0, 3)
      }

      // Update pagination
      if (response.courses.current_page !== undefined) {
        pagination.value = {
          currentPage: response.courses.current_page || page,
          lastPage: response.courses.last_page || 1,
          total: response.courses.total || 0,
          perPage: response.courses.per_page || 8,
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
  router.push(`/courses/${courseId}`)
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
      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-400">
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
          <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">
            รายวิชาทั้งหมด
          </h1>
          <p class="text-blue-100 text-sm md:text-base">
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
        
        <!-- Sparkle effects -->
        <div class="absolute top-4 left-1/3 w-2 h-2 bg-white rounded-full animate-ping opacity-75"></div>
        <div class="absolute bottom-6 left-1/2 w-1.5 h-1.5 bg-yellow-200 rounded-full animate-ping opacity-60" style="animation-delay: 0.5s;"></div>
        <div class="absolute top-6 right-1/3 w-1 h-1 bg-white rounded-full animate-ping opacity-50" style="animation-delay: 1s;"></div>
      </div>
    </template>

    <!-- Main Content (Default Slot) -->
    <div class="max-w-7xl mx-auto px-4 py-6">
      <!-- Main Layout: 3 Columns Grid (1:2:1) -->
      <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        
        <!-- Left Sidebar (Search & Filters) -->
        <div class="col-span-1 order-2 xl:order-1 space-y-6">
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
      </div>

      <!-- Main Content (Center) -->
      <div class="col-span-1 xl:col-span-2 min-w-0 order-1 xl:order-2">
        <!-- Loading State -->
        <template v-if="isLoading">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div
              v-for="i in 6"
              :key="i"
              class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden animate-pulse shadow-lg"
            >
              <div class="h-44 bg-gray-200 dark:bg-gray-700"></div>
              <div class="p-4 space-y-3">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
              </div>
            </div>
          </div>
        </template>

        <!-- Error State -->
        <div
          v-else-if="error"
          class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-lg"
        >
          <Icon icon="fluent:error-circle-24-regular" class="w-20 h-20 text-red-500 mx-auto mb-4" />
          <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">เกิดข้อผิดพลาด</h3>
          <p class="text-gray-500 mb-4">{{ error }}</p>
          <button
            @click="fetchCourses(1)"
            class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
          >
            ลองใหม่
          </button>
        </div>

        <!-- Empty State -->
        <div
          v-else-if="courses.length === 0"
          class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-lg"
        >
          <Icon icon="fluent:book-24-regular" class="w-20 h-20 text-gray-400 mx-auto mb-4" />
          <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">ไม่พบรายวิชา</h3>
          <p class="text-gray-500">ลองค้นหาด้วยคำค้นอื่น หรือเปลี่ยนตัวกรอง</p>
        </div>

        <!-- Course Grid - 2 Columns -->
        <template v-else>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <CourseCard
              v-for="(course, index) in courses"
              :key="course.id"
              :course="course"
              :index="index"
            />
          </div>

          <!-- Load More -->
          <div v-if="hasMorePages" class="mt-8 text-center">
            <button
              @click="loadMore"
              :disabled="isLoadingMore"
              class="px-8 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50 flex items-center gap-2 mx-auto font-medium"
            >
              <Icon
                :icon="
                  isLoadingMore ? 'fluent:spinner-ios-20-regular' : 'fluent:arrow-down-24-regular'
                "
                :class="['w-5 h-5', { 'animate-spin': isLoadingMore }]"
              />
              {{ isLoadingMore ? 'กำลังโหลด...' : 'โหลดเพิ่มเติม' }}
            </button>
          </div>
        </template>
      </div>

      <!-- Right Sidebar -->
      <div class="col-span-1 space-y-6 order-3">
        <MemberedCoursesWidget />
        <MyCoursesWidget />
        <!-- Popular Courses Widget -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
          <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Popular Courses</h3>
          </div>
          <div class="divide-y divide-gray-100 dark:divide-gray-700">
            <div
              v-for="course in popularCourses"
              :key="course.id"
              class="p-4 flex items-start gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
              @click="goToCourse(course.id)"
            >
              <img
                :src="getCoverUrl(course)"
                :alt="course.name"
                class="w-16 h-16 rounded-lg object-cover flex-shrink-0"
              />
              <div class="flex-1 min-w-0">
                <h4 class="text-sm font-medium text-gray-800 dark:text-white line-clamp-2 mb-1">
                  {{ course.name }}
                </h4>
                <p class="text-xs text-blue-500">{{ course.user?.name || 'Unknown' }}</p>
              </div>
              <button class="p-1 text-gray-400 hover:text-blue-500 transition-colors flex-shrink-0">
                <Icon icon="fluent:bookmark-24-regular" class="w-5 h-5" />
              </button>
            </div>

            <!-- Empty state -->
            <div
              v-if="popularCourses.length === 0 && !isLoading"
              class="p-4 text-center text-gray-500 text-sm"
            >
              ไม่มีข้อมูล
            </div>
          </div>
        </div>

        <!-- Ask Question Widget -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
          <h3 class="font-bold text-gray-800 dark:text-white mb-3">Ask Research Question?</h3>
          <div class="flex items-start gap-3 mb-4">
            <div
              class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0"
            >
              <Icon icon="fluent:question-circle-24-regular" class="w-5 h-5 text-blue-500" />
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Ask questions in Q&A to get help from experts in your field.
            </p>
          </div>
          <button
            class="w-full py-2.5 border-2 border-blue-500 text-blue-500 rounded-lg font-medium hover:bg-blue-500 hover:text-white transition-colors"
          >
            Ask a question
          </button>
        </div>

        <!-- Explore Events Widget -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
          <div
            class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"
          >
            <h3 class="font-bold text-gray-800 dark:text-white">Explore Events</h3>
            <NuxtLink to="/events" class="text-sm text-blue-500 hover:underline">See All</NuxtLink>
          </div>
          <div class="p-4 space-y-3">
            <!-- Event Card 1 -->
            <div
              class="relative h-24 rounded-lg overflow-hidden bg-gradient-to-r from-cyan-500 to-blue-500 p-3 flex items-end cursor-pointer hover:opacity-90 transition-opacity"
            >
              <div class="absolute inset-0 bg-black/20"></div>
              <div class="relative">
                <Icon icon="fluent:building-24-regular" class="w-6 h-6 text-white mb-1" />
                <p class="text-white text-sm font-medium line-clamp-2">
                  University good night event in columbia
                </p>
              </div>
            </div>

            <!-- Event Card 2 -->
            <div
              class="relative h-24 rounded-lg overflow-hidden bg-gradient-to-r from-green-500 to-teal-500 p-3 flex items-end cursor-pointer hover:opacity-90 transition-opacity"
            >
              <div class="absolute inset-0 bg-black/20"></div>
              <div class="relative">
                <Icon icon="fluent:people-audience-24-regular" class="w-6 h-6 text-white mb-1" />
                <p class="text-white text-sm font-medium">The 3rd International Conference 2020</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Who's Following Widget -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
          <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Who's Following</h3>
          </div>
          <div class="divide-y divide-gray-100 dark:divide-gray-700">
            <div class="p-4 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="relative">
                  <img
                    src="/images/default-avatar.png"
                    alt="User"
                    class="w-10 h-10 rounded-full object-cover"
                  />
                  <div
                    class="absolute -bottom-1 -right-1 w-4 h-4 bg-yellow-400 rounded text-[8px] flex items-center justify-center font-bold text-gray-900"
                  >
                    5
                  </div>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-800 dark:text-white">Kelly Bill</p>
                  <p class="text-xs text-gray-500">Dept colleague</p>
                </div>
              </div>
              <button class="text-blue-500 text-sm font-medium hover:underline">Follow</button>
            </div>

            <div class="p-4 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="relative">
                  <img
                    src="/images/default-avatar.png"
                    alt="User"
                    class="w-10 h-10 rounded-full object-cover"
                  />
                  <div
                    class="absolute -bottom-1 -right-1 w-4 h-4 bg-yellow-400 rounded text-[8px] flex items-center justify-center font-bold text-gray-900"
                  >
                    5
                  </div>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-800 dark:text-white">Issabel</p>
                  <p class="text-xs text-gray-500">Dept colleague</p>
                </div>
              </div>
              <button class="text-blue-500 text-sm font-medium hover:underline">Follow</button>
            </div>
          </div>
        </div>
      </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

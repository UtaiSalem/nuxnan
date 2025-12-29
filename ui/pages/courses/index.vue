<script setup lang="ts">
import { Icon } from '@iconify/vue'
import BaseCard from '~/components/atoms/BaseCard.vue'

definePageMeta({
  layout: 'main',
  middleware: 'auth'
})

useHead({
  title: 'Courses - Marketplace'
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

// Pagination
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
  perPage: 8,
})
const hasMorePages = computed(() => pagination.value.currentPage < pagination.value.lastPage)

// Categories
const categories = [
  { value: 'all', label: 'ทุกหมวดหมู่' },
  { value: 'programming', label: 'การเขียนโปรแกรม' },
  { value: 'design', label: 'การออกแบบ' },
  { value: 'business', label: 'ธุรกิจ' },
  { value: 'marketing', label: 'การตลาด' },
  { value: 'math', label: 'คณิตศาสตร์' },
  { value: 'science', label: 'วิทยาศาสตร์' },
  { value: 'language', label: 'ภาษา' },
  { value: 'other', label: 'อื่นๆ' },
]

// Levels
const levels = [
  { value: 'all', label: 'ทุกระดับ' },
  { value: 'beginner', label: 'เริ่มต้น' },
  { value: 'intermediate', label: 'กลาง' },
  { value: 'advanced', label: 'สูง' },
]

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

    const response = await api.get(`/api/courses?${params.toString()}`)

    // Response could be { courses: [...] } or { success: true, courses: [...] }
    if (response.courses) {
      const newCourses = Array.isArray(response.courses) 
        ? response.courses 
        : (response.courses.data || [])
      
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

// Course card helpers
const getCoverUrl = (course: any) => {
  if (course.cover) {
    if (course.cover.startsWith('http')) {
      return course.cover
    }
    return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/${course.cover}`
  }
  return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/default_cover.jpg`
}

const getInstructorAvatar = (course: any) => {
  return course.user?.avatar || '/images/default-avatar.png'
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('th-TH').format(price || 0)
}

// Get badge type based on course data
const getBadgeType = (course: any, index: number) => {
  if (course.enrolled_students > 50) return 'bestseller'
  if (index < 3) return 'trending'
  return null
}

// Navigate to course detail
const goToCourse = (courseId: number) => {
  router.push(`/courses/${courseId}`)
}

// Enroll in course
const enrollCourse = async (course: any) => {
  try {
    const response = await api.post(`/courses/${course.id}/members`)
    if (response.success) {
      course.isMember = true
      course.member_status = response.memberStatus
      course.enrolled_students++
    }
  } catch (err: any) {
    console.error('Error enrolling:', err)
    alert(err.data?.msg || 'ไม่สามารถสมัครเรียนได้')
  }
}

// On mount
onMounted(() => {
  fetchCourses()
})

// Watch for filter changes
watch([selectedCategory, selectedLevel, sortBy], () => {
  fetchCourses(1)
})
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 py-6">
    <!-- Header with accent bar -->
    <div class="mb-6 flex items-center gap-3">
      <div class="w-1 h-8 bg-blue-500 rounded-full"></div>
      <h1 class="text-2xl font-bold text-white">Courses</h1>
    </div>

    <!-- Main Layout: Content + Sidebar -->
    <div class="flex flex-col lg:flex-row gap-6">
      <!-- Main Content -->
      <div class="flex-1 min-w-0">
        <!-- Filters Row -->
        <div class="flex flex-wrap gap-3 mb-6">
          <!-- Search -->
          <div class="relative flex-1 min-w-[200px]">
            <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              v-model="searchQuery"
              @input="handleSearch"
              type="text"
              placeholder="ค้นหารายวิชา..."
              class="w-full bg-gray-800 border border-gray-700 rounded-lg pl-10 pr-4 py-2.5 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <!-- Category -->
          <select
            v-model="selectedCategory"
            class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option v-for="cat in categories" :key="cat.value" :value="cat.value">
              {{ cat.label }}
            </option>
          </select>

          <!-- Level -->
          <select
            v-model="selectedLevel"
            class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option v-for="level in levels" :key="level.value" :value="level.value">
              {{ level.label }}
            </option>
          </select>

          <!-- Sort -->
          <select
            v-model="sortBy"
            class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>

        <!-- Loading State -->
        <template v-if="isLoading">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div v-for="i in 6" :key="i" class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden animate-pulse shadow-lg">
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
        <div v-else-if="error" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-lg">
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
        <div v-else-if="courses.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center shadow-lg">
          <Icon icon="fluent:book-24-regular" class="w-20 h-20 text-gray-400 mx-auto mb-4" />
          <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">ไม่พบรายวิชา</h3>
          <p class="text-gray-500">ลองค้นหาด้วยคำค้นอื่น หรือเปลี่ยนตัวกรอง</p>
        </div>

        <!-- Course Grid - 2 Columns -->
        <template v-else>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div
              v-for="(course, index) in courses"
              :key="course.id"
              class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all cursor-pointer group"
              @click="goToCourse(course.id)"
            >
              <!-- Cover -->
              <div class="relative h-44 overflow-hidden bg-gray-100 dark:bg-gray-700">
                <img
                  :src="getCoverUrl(course)"
                  :alt="course.name"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
                
                <!-- Badge (Best Seller / Trending) -->
                <div 
                  v-if="getBadgeType(course, index)"
                  :class="[
                    'absolute top-3 left-3 px-3 py-1 text-white text-xs font-bold rounded shadow-lg',
                    getBadgeType(course, index) === 'bestseller' ? 'bg-blue-500' : 'bg-orange-500'
                  ]"
                >
                  {{ getBadgeType(course, index) === 'bestseller' ? 'Best Seller' : 'Trending' }}
                </div>

                <!-- Rating Badge -->
                <div class="absolute bottom-3 left-3 px-2 py-1 bg-yellow-500 text-gray-900 rounded text-xs font-bold flex items-center gap-1">
                  <Icon icon="fluent:star-16-filled" class="w-3 h-3" />
                  <span>{{ course.rating || '4.5' }}</span>
                </div>
              </div>

              <!-- Content -->
              <div class="p-4">
                <!-- Instructor & Price Row -->
                <div class="flex items-center justify-between mb-3">
                  <div v-if="course.user" class="flex items-center gap-2">
                    <img 
                      :src="getInstructorAvatar(course)" 
                      :alt="course.user.name"
                      class="w-8 h-8 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600"
                    />
                    <div class="min-w-0">
                      <p class="text-xs text-gray-500 dark:text-gray-400">By: {{ course.user.name }}</p>
                      <p class="text-xs text-blue-500 truncate">{{ course.category || 'General' }}</p>
                    </div>
                  </div>
                  
                  <!-- Price -->
                  <div class="flex items-center gap-1 text-gray-700 dark:text-gray-300 font-bold">
                    <Icon icon="ri:bit-coin-line" class="w-5 h-5 text-yellow-500" />
                    <span>{{ formatPrice(course.price) }}</span>
                  </div>
                </div>

                <!-- Title -->
                <h3 class="text-gray-800 dark:text-white font-bold mb-3 line-clamp-2 group-hover:text-blue-500 transition-colors">
                  {{ course.name }}
                </h3>

                <!-- Stats Row -->
                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 pt-3 border-t border-gray-100 dark:border-gray-700">
                  <div class="flex items-center gap-1">
                    <Icon icon="fluent:book-open-16-regular" class="w-4 h-4" />
                    <span>{{ course.lessons_count || 20 }} Lectures</span>
                  </div>
                  <div class="flex items-center gap-1">
                    <Icon icon="fluent:clock-16-regular" class="w-4 h-4" />
                    <span>{{ course.hours || 20 }}Hrs</span>
                  </div>
                </div>

                <!-- Member Badge -->
                <div v-if="course.isMember" class="mt-3 flex items-center justify-center gap-2 py-2 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg text-sm font-medium">
                  <Icon icon="fluent:checkmark-circle-16-filled" class="w-4 h-4" />
                  เป็นสมาชิกแล้ว
                </div>
              </div>
            </div>
          </div>

          <!-- Load More -->
          <div v-if="hasMorePages" class="mt-8 text-center">
            <button
              @click="loadMore"
              :disabled="isLoadingMore"
              class="px-8 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50 flex items-center gap-2 mx-auto font-medium"
            >
              <Icon 
                :icon="isLoadingMore ? 'fluent:spinner-ios-20-regular' : 'fluent:arrow-down-24-regular'" 
                :class="['w-5 h-5', { 'animate-spin': isLoadingMore }]" 
              />
              {{ isLoadingMore ? 'กำลังโหลด...' : 'โหลดเพิ่มเติม' }}
            </button>
          </div>
        </template>
      </div>

      <!-- Sidebar -->
      <div class="w-full lg:w-80 space-y-6 flex-shrink-0">
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
                <h4 class="text-sm font-medium text-gray-800 dark:text-white line-clamp-2 mb-1">{{ course.name }}</h4>
                <p class="text-xs text-blue-500">{{ course.user?.name || 'Unknown' }}</p>
              </div>
              <button class="p-1 text-gray-400 hover:text-blue-500 transition-colors flex-shrink-0">
                <Icon icon="fluent:bookmark-24-regular" class="w-5 h-5" />
              </button>
            </div>
            
            <!-- Empty state -->
            <div v-if="popularCourses.length === 0 && !isLoading" class="p-4 text-center text-gray-500 text-sm">
              ไม่มีข้อมูล
            </div>
          </div>
        </div>

        <!-- Ask Question Widget -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
          <h3 class="font-bold text-gray-800 dark:text-white mb-3">Ask Research Question?</h3>
          <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
              <Icon icon="fluent:question-circle-24-regular" class="w-5 h-5 text-blue-500" />
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Ask questions in Q&A to get help from experts in your field.
            </p>
          </div>
          <button class="w-full py-2.5 border-2 border-blue-500 text-blue-500 rounded-lg font-medium hover:bg-blue-500 hover:text-white transition-colors">
            Ask a question
          </button>
        </div>

        <!-- Explore Events Widget -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
          <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-bold text-gray-800 dark:text-white">Explore Events</h3>
            <NuxtLink to="/events" class="text-sm text-blue-500 hover:underline">See All</NuxtLink>
          </div>
          <div class="p-4 space-y-3">
            <!-- Event Card 1 -->
            <div class="relative h-24 rounded-lg overflow-hidden bg-gradient-to-r from-cyan-500 to-blue-500 p-3 flex items-end cursor-pointer hover:opacity-90 transition-opacity">
              <div class="absolute inset-0 bg-black/20"></div>
              <div class="relative">
                <Icon icon="fluent:building-24-regular" class="w-6 h-6 text-white mb-1" />
                <p class="text-white text-sm font-medium line-clamp-2">University good night event in columbia</p>
              </div>
            </div>
            
            <!-- Event Card 2 -->
            <div class="relative h-24 rounded-lg overflow-hidden bg-gradient-to-r from-green-500 to-teal-500 p-3 flex items-end cursor-pointer hover:opacity-90 transition-opacity">
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
                  <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-yellow-400 rounded text-[8px] flex items-center justify-center font-bold text-gray-900">
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
                  <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-yellow-400 rounded text-[8px] flex items-center justify-center font-bold text-gray-900">
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
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

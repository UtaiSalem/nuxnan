<script setup lang="ts">
import { Icon } from '@iconify/vue'
import MyCoursesWidget from '~/components/widgets/MyCoursesWidget.vue'
import MemberedCoursesWidget from '~/components/widgets/MemberedCoursesWidget.vue'
import RecentlyViewedCoursesWidget from '~/components/widgets/RecentlyViewedCoursesWidget.vue'
import FavoriteCoursesWidget from '~/components/widgets/FavoriteCoursesWidget.vue'
import CourseMarketCard from '~/components/academy/CourseMarketCard.vue'
import CourseSearchFilterWidget from '~/components/widgets/CourseSearchFilterWidget.vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'main',
  middleware: 'auth',
})

useHead({
  title: 'รายวิชา - Nuxnan',
})

const api = useApi()
const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const { threshold: createCourseThreshold, canCreate: canCreateCourse, fetchThreshold } = useCourseCreateGate()

// ── Helpers ────────────────────────────────────────────────────────────────
const parseBooleanQuery = (value: unknown) => {
  if (Array.isArray(value)) return value.some(parseBooleanQuery)
  if (typeof value === 'boolean') return value
  if (typeof value === 'number') return value === 1
  if (typeof value !== 'string') return false

  const normalized = value.trim().toLowerCase()
  return ['1', 'true', 'yes', 'on'].includes(normalized)
}

// ── Tab ────────────────────────────────────────────────────────────────────
const activeTab = ref<'all' | 'my' | 'enrolled'>('enrolled')

// ── All Courses (tab: ทั้งหมด) ─────────────────────────────────────────────
const courses = ref<any[]>([])
const popularCourses = ref<any[]>([])
const isLoading = ref(true)
const isLoadingMore = ref(false)
const error = ref<string | null>(null)
const searchQuery = ref('')
const selectedCategory = ref('all')
const selectedLevel = ref('all')
const selectedEducationLevel = ref('all')
const selectedEducationYear = ref('all')
const sortBy = ref('latest')
const selectedSemester = ref('all')
const selectedYear = ref('all')
const marketplaceOnly = ref(parseBooleanQuery(route.query.marketplace_only))
const enrollableOnly = ref(false)
const isFree = ref(false)
const isFilterDrawerOpen = ref(false)

const semesters = ref([
  { value: 'all', label: 'ทุกภาคเรียน' },
  { value: '1', label: 'ภาคเรียนที่ 1' },
  { value: '2', label: 'ภาคเรียนที่ 2' },
  { value: '3', label: 'ภาคเรียนที่ 3' },
  { value: 'summer', label: 'ภาคฤดูร้อน' },
  { value: 'weekend', label: 'เสา-อาทิตย์' }
])
const years = ref([{ value: 'all', label: 'ทุกปีการศึกษา' }])
const categories = ref([{ value: 'all', label: 'ทุกหมวดหมู่' }])
const levels = ref([{ value: 'all', label: 'ทุกระดับ' }])
const educationLevels = ref([{ value: 'all', label: 'ทุกระดับการศึกษา' }])
const sortOptions = [
  { value: 'latest', label: 'ล่าสุด' },
  { value: 'popular', label: 'ยอดนิยม' },
  { value: 'price-low', label: 'ราคาต่ำ-สูง' },
  { value: 'price-high', label: 'ราคาสูง-ต่ำ' },
  { value: 'rating', label: 'คะแนนสูงสุด' },
]

const pagination = ref({ currentPage: 1, lastPage: 1, total: 0, perPage: 8 })
const hasMorePages = computed(() => pagination.value.currentPage < pagination.value.lastPage)

const academyMarketplaceContext = computed(() => {
  const context = route.query.context
  if (context !== 'academy-admin') return null

  const academyName = typeof route.query.academy_name === 'string' ? route.query.academy_name : ''
  const returnTo = typeof route.query.return_to === 'string' ? route.query.return_to : ''

  return {
    academyName,
    returnTo: returnTo || '/academies',
  }
})

const hydrateFiltersFromQuery = () => {
  const tabParam = typeof route.query.tab === 'string' ? route.query.tab : ''
  const marketplaceFromQuery = parseBooleanQuery(route.query.marketplace_only)
  const searchFromQuery = typeof route.query.search === 'string' ? route.query.search : ''

  if (tabParam === 'my' || tabParam === 'enrolled' || tabParam === 'all') {
    activeTab.value = tabParam
  } else if (marketplaceFromQuery) {
    activeTab.value = 'all'
  }

  marketplaceOnly.value = marketplaceFromQuery

  if (searchFromQuery) {
    searchQuery.value = searchFromQuery
  }
}

const fetchCourses = async (page = 1, append = false) => {
  page === 1 ? (isLoading.value = true) : (isLoadingMore.value = true)
  error.value = null
  try {
    const params = new URLSearchParams({ page: String(page), per_page: String(pagination.value.perPage) })
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (selectedCategory.value !== 'all') params.append('category', selectedCategory.value)
    if (selectedEducationLevel.value !== 'all') params.append('education_level', selectedEducationLevel.value)
    if (selectedEducationYear.value !== 'all') params.append('education_year', selectedEducationYear.value)
    if (selectedLevel.value !== 'all') params.append('level', selectedLevel.value)
    if (sortBy.value) params.append('sort', sortBy.value)
    if (selectedSemester.value !== 'all') params.append('semester', selectedSemester.value)
    if (selectedYear.value !== 'all') params.append('academic_year', selectedYear.value)
    if (marketplaceOnly.value) params.append('marketplace_only', '1')
    if (enrollableOnly.value) params.append('enrollable_only', '1')
    if (isFree.value) params.append('is_free', '1')

    const response: any = await api.get(`/api/courses?${params.toString()}`)
    const coursesData = response.courses || response.data
    if (coursesData) {
      const newCourses = Array.isArray(coursesData) ? coursesData : (coursesData.data || [])
      if (append) courses.value = [...courses.value, ...newCourses]
      else {
        courses.value = newCourses
        popularCourses.value = newCourses.slice(0, 3)
      }
      if (coursesData.current_page !== undefined) {
        pagination.value = {
          currentPage: coursesData.current_page || page,
          lastPage: coursesData.last_page || 1,
          total: coursesData.total || 0,
          perPage: coursesData.per_page || 8,
        }
      } else {
        pagination.value.currentPage = page
        pagination.value.lastPage = newCourses.length < pagination.value.perPage ? page : page + 1
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

const loadMore = async () => {
  if (isLoadingMore.value || !hasMorePages.value) return
  await fetchCourses(pagination.value.currentPage + 1, true)
}

let searchTimeout: ReturnType<typeof setTimeout>
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchCourses(1), 300)
}

// ── My Courses (tab: วิชาของฉัน) ───────────────────────────────────────────
const myCourses = ref<any[]>([])
const myCoursesLoading = ref(false)
const myCoursesError = ref<string | null>(null)

const fetchMyCourses = async () => {
  const userId = authStore.user?.id
  if (!userId) return
  
  myCoursesLoading.value = true
  myCoursesError.value = null
  try {
    const res: any = await api.get(`/api/courses/users/${userId}/my-courses`)
    myCourses.value = res.courses?.data || res.courses || res.data || []
  } catch (e) {
    myCoursesError.value = 'ไม่สามารถโหลดรายวิชาของฉันได้'
    myCourses.value = []
  } finally {
    myCoursesLoading.value = false
  }
}

// ── Enrolled Courses (tab: กำลังเรียน) ─────────────────────────────────────
const enrolledCourses = ref<any[]>([])
const enrolledLoading = ref(false)
const enrolledError = ref<string | null>(null)

const fetchEnrolledCourses = async () => {
  const userId = authStore.user?.id
  if (!userId) return

  enrolledLoading.value = true
  enrolledError.value = null
  try {
    const res: any = await api.get(`/api/courses/users/${userId}/membered`)
    const data = res.courses?.data || res.courses || res.data || []
    enrolledCourses.value = Array.isArray(data) ? data : []
  } catch (e) {
    enrolledError.value = 'ไม่สามารถโหลดรายวิชาที่กำลังเรียนได้'
    enrolledCourses.value = []
  } finally {
    enrolledLoading.value = false
  }
}

// ── Active computed ────────────────────────────────────────────────────────
const activeCourses = computed(() => {
  if (activeTab.value === 'my') return myCourses.value
  if (activeTab.value === 'enrolled') return enrolledCourses.value
  return courses.value
})

const activeLoading = computed(() => {
  if (activeTab.value === 'my') return myCoursesLoading.value
  if (activeTab.value === 'enrolled') return enrolledLoading.value
  return isLoading.value
})

const activeError = computed(() => {
  if (activeTab.value === 'my') return myCoursesError.value
  if (activeTab.value === 'enrolled') return enrolledError.value
  return error.value
})

// ── Filter options ─────────────────────────────────────────────────────────
const normalizeOption = (item: any) => {
  if (typeof item === 'string' || typeof item === 'number') {
    return { value: String(item), label: String(item) }
  }
  if (item && typeof item === 'object') {
    const val = item.value ?? item.id ?? JSON.stringify(item)
    const lbl = item.label ?? item.name ?? val
    return { value: String(val), label: String(lbl) }
  }
  return { value: String(item), label: String(item) }
}

const fetchFilterOptions = async () => {
  try {
    const res: any = await api.get('/api/courses/filters')
    if (res.success) {
      if (res.semesters?.length) semesters.value = [{ value: 'all', label: 'ทุกภาคเรียน' }, ...res.semesters.map(normalizeOption)]
      if (res.years?.length) years.value = [{ value: 'all', label: 'ทุกปีการศึกษา' }, ...res.years.map(normalizeOption)]
      if (res.categories?.length) categories.value = [{ value: 'all', label: 'ทุกหมวดหมู่' }, ...res.categories.map((c: string) => ({ value: c, label: c }))]
      if (res.levels?.length) levels.value = [{ value: 'all', label: 'ทุกระดับ' }, ...res.levels.map((l: string) => ({ value: l, label: l }))]
      if (res.education_levels?.length) educationLevels.value = [{ value: 'all', label: 'ทุกระดับการศึกษา' }, ...res.education_levels.map((l: string) => ({ value: l, label: l }))]
    }
  } catch (e) {
    console.error('Failed to fetch filter options', e)
  }
}

// ── General Helpers ────────────────────────────────────────────────────────
const getCoverUrl = (course: any) => {
  if (course.cover) {
    if (course.cover.startsWith('http')) return course.cover
    return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/${course.cover}`
  }
  return `${useRuntimeConfig().public.apiBase}/storage/images/courses/covers/default_cover.jpg`
}

const goToCourse = (courseId: number) => router.push(`/Learn/Courses/${courseId}`)
const formatNumber = (num: number) => num?.toLocaleString('th-TH') ?? '0'

const activeFiltersCount = computed(() => {
  let count = 0
  if (selectedCategory.value !== 'all') count++
  if (selectedLevel.value !== 'all') count++
  if (selectedEducationLevel.value !== 'all') count++
  if (selectedSemester.value !== 'all') count++
  if (selectedYear.value !== 'all') count++
  if (marketplaceOnly.value) count++
  if (enrollableOnly.value) count++
  if (isFree.value) count++
  return count
})

// ── Signal main.vue ────────────────────────────────────────────────────────
usePageLayoutWidgets({ left: true, right: true })

onMounted(() => {
  fetchThreshold()
  hydrateFiltersFromQuery()
  fetchFilterOptions()
  if (activeTab.value === 'my') fetchMyCourses()
  else if (activeTab.value === 'all') fetchCourses()
  else fetchEnrolledCourses()
})

// ── Watch ──────────────────────────────────────────────────────────
watch(activeTab, (tab) => {
  if (tab === 'all') fetchCourses(1)
  else if (tab === 'my') fetchMyCourses()
  else fetchEnrolledCourses()
})

watch(selectedEducationLevel, () => { selectedEducationYear.value = 'all' })

watch([selectedCategory, selectedLevel, selectedEducationLevel, selectedEducationYear, sortBy, selectedSemester, selectedYear, marketplaceOnly, enrollableOnly, isFree], () => {
  if (activeTab.value === 'all') fetchCourses(1)
})

const resetFilters = () => {
  searchQuery.value = ''
  selectedCategory.value = 'all'
  selectedLevel.value = 'all'
  selectedEducationLevel.value = 'all'
  selectedEducationYear.value = 'all'
  selectedSemester.value = 'all'
  selectedYear.value = 'all'
  sortBy.value = 'latest'
  marketplaceOnly.value = false
  enrollableOnly.value = false
  isFree.value = false
  fetchCourses(1)
}

watch(() => authStore.user?.id, (id) => {
  if (!id) return
  if (activeTab.value === 'my') fetchMyCourses()
  else if (activeTab.value === 'enrolled') fetchEnrolledCourses()
}, { immediate: false })
</script>

<template>
  <div>
    <!-- Hero Banner -->
    <ClientOnly><Teleport to="#hero-slot">
      <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-400 shadow-xl">
        <div
          class="absolute inset-0 opacity-20"
          aria-hidden="true"
          style="background-image: radial-gradient(circle, #ffffff 1px, transparent 1px); background-size: 18px 18px;"
        ></div>

        <div class="relative z-10 flex items-center gap-3 p-4 sm:gap-4 sm:p-6 md:gap-6 md:p-8">
          <div class="flex flex-shrink-0 items-center gap-2">
            <img :src="`${$config.public.apiBase}/storage/images/badge/gold-b.png`" alt="badge" class="w-14 h-14 sm:w-16 sm:h-16 md:w-20 md:h-20 drop-shadow-lg animate-bounce" style="animation-duration: 2s;" />
            <img :src="`${$config.public.apiBase}/storage/images/badge/scientist-b.png`" alt="badge" class="hidden sm:block w-12 h-12 md:w-16 md:h-16 drop-shadow-lg animate-bounce" style="animation-duration: 2.5s; animation-delay: 0.3s;" />
          </div>

          <div class="min-w-0 flex-1">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-black text-white mb-1 sm:mb-2 break-words">รายวิชา</h1>
            <p class="text-blue-50 font-medium text-xs sm:text-sm md:text-base break-words">ค้นหาวิชาเพื่อเรียน หรือซื้อ Master Copy สำหรับสถาบันของคุณ</p>
          </div>

          <div class="hidden md:flex flex-shrink-0 items-center gap-3">
            <img :src="`${$config.public.apiBase}/storage/images/badge/globet-b.png`" alt="badge" class="w-10 h-10 drop-shadow-lg opacity-70 animate-pulse" />
            <img :src="`${$config.public.apiBase}/storage/images/badge/collector-b.png`" alt="badge" class="w-14 h-14 drop-shadow-lg opacity-80 animate-bounce" style="animation-duration: 3s;" />
            <img :src="`${$config.public.apiBase}/storage/images/badge/platinum-b.png`" alt="badge" class="w-12 h-12 drop-shadow-lg opacity-70 animate-pulse" style="animation-delay: 0.5s;" />
            <img :src="`${$config.public.apiBase}/storage/images/badge/tycoon.png`" alt="badge" class="w-16 h-16 drop-shadow-lg opacity-90 animate-bounce" style="animation-duration: 2.8s; animation-delay: 0.2s;" />
          </div>
        </div>
      </div>
    </Teleport></ClientOnly>

    <!-- Left Sidebar -->
    <ClientOnly><Teleport to="#left-widgets-slot">
      <div class="space-y-4">
        <CourseSearchFilterWidget
          v-if="activeTab === 'all'"
          v-model:searchQuery="searchQuery"
          v-model:selectedCategory="selectedCategory"
          v-model:selectedLevel="selectedLevel"
          v-model:selectedEducationLevel="selectedEducationLevel"
          v-model:selectedEducationYear="selectedEducationYear"
          v-model:selectedSemester="selectedSemester"
          v-model:selectedYear="selectedYear"
          v-model:sortBy="sortBy"
          v-model:marketplaceOnly="marketplaceOnly"
          v-model:enrollableOnly="enrollableOnly"
          v-model:isFree="isFree"
          :categories="categories"
          :levels="levels"
          :educationLevels="educationLevels"
          :semesters="semesters"
          :years="years"
          :sortOptions="sortOptions"
          @handleSearch="handleSearch"
          @clearFilters="resetFilters"
        />
        <RecentlyViewedCoursesWidget />
        <FavoriteCoursesWidget />
      </div>
    </Teleport></ClientOnly>

    <!-- Right Sidebar -->
    <ClientOnly><Teleport to="#right-widgets-slot">
      <div class="space-y-4">
        <MemberedCoursesWidget />
        <MyCoursesWidget />
        <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 overflow-hidden">
          <div class="p-4 border-b border-gray-100 dark:border-vikinger-dark-100">
            <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
              <Icon icon="fluent:star-24-filled" class="w-5 h-5 text-amber-500" />
              รายวิชายอดนิยม
            </h3>
          </div>
          <div class="divide-y divide-gray-100 dark:divide-vikinger-dark-100">
            <div v-for="course in popularCourses" :key="course.id" class="p-4 flex items-start gap-3 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors cursor-pointer" @click="goToCourse(course.id)">
              <img :src="getCoverUrl(course)" :alt="course.name" class="w-14 h-14 rounded-lg object-cover flex-shrink-0 border border-gray-100 dark:border-vikinger-dark-50" />
              <div class="flex-1 min-w-0">
                <h4 class="text-xs font-bold text-gray-800 dark:text-white line-clamp-2 mb-1">{{ course.name }}</h4>
                <p class="text-[10px] text-blue-50 font-bold uppercase tracking-wider">{{ course.user?.name || 'Unknown' }}</p>
              </div>
            </div>
            <div v-if="popularCourses.length === 0 && !isLoading" class="p-4 text-center text-gray-500 text-xs italic">ไม่มีข้อมูล</div>
          </div>
        </div>
      </div>
    </Teleport></ClientOnly>

    <!-- Main Content -->
    <div class="min-w-0">
      <div
        v-if="academyMarketplaceContext"
        class="mb-5 rounded-2xl border border-violet-100 bg-violet-50/90 p-4 shadow-sm dark:border-vikinger-dark-100 dark:bg-vikinger-dark-200"
      >
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <p class="text-sm font-black text-violet-700 dark:text-vikinger-cyan">
              ตลาด Master Copy สำหรับโรงเรียน {{ academyMarketplaceContext.academyName || 'นี้' }}
            </p>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
              เลือกซื้อรายวิชาต้นฉบับ แล้วกลับไปจัดการคลังรายวิชาของโรงเรียนต่อได้ทันที
            </p>
          </div>
          <NuxtLink
            :to="academyMarketplaceContext.returnTo"
            class="inline-flex items-center gap-2 self-start rounded-xl bg-white px-4 py-2 text-sm font-bold text-violet-700 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md dark:bg-vikinger-dark-100 dark:text-vikinger-cyan"
          >
            <Icon icon="fluent:arrow-left-24-regular" class="w-4 h-4" />
            กลับหน้ารายวิชาโรงเรียน
          </NuxtLink>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 p-1 flex gap-1 mb-5">
        <button
          @click="activeTab = 'enrolled'"
          class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold transition-all whitespace-nowrap"
          :class="activeTab === 'enrolled' ? 'bg-vikinger-purple/10 text-vikinger-purple dark:text-vikinger-cyan' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 dark:text-gray-400'"
        >
          <Icon icon="fluent:hat-graduation-24-regular" class="w-4 h-4" />
          กำลังเรียน
        </button>
        <button
          @click="activeTab = 'my'"
          class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold transition-all whitespace-nowrap"
          :class="activeTab === 'my' ? 'bg-vikinger-purple/10 text-vikinger-purple dark:text-vikinger-cyan' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 dark:text-gray-400'"
        >
          <Icon icon="fluent:crown-24-regular" class="w-4 h-4" />
          วิชาของฉัน
        </button>
        <button
          @click="activeTab = 'all'"
          class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold transition-all whitespace-nowrap"
          :class="activeTab === 'all' ? 'bg-vikinger-purple/10 text-vikinger-purple dark:text-vikinger-cyan' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 dark:text-gray-400'"
        >
          <Icon icon="fluent:globe-24-regular" class="w-4 h-4" />
          ทั้งหมด
        </button>
      </div>

      <!-- Sort bar (tab: ทั้งหมด เท่านั้น) -->
      <div v-if="activeTab === 'all'" class="mb-4 space-y-3">
        <div class="flex items-center justify-between px-1">
          <div class="bg-vikinger-purple/10 text-vikinger-purple px-3 py-1 rounded-full text-xs font-black">
            {{ formatNumber(pagination.total) }} รายวิชา
          </div>
          <div class="flex items-center gap-2">
            <!-- Mobile Filter Button -->
            <button 
              @click="isFilterDrawerOpen = true"
              class="lg:hidden flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-vikinger-dark-200 text-gray-700 dark:text-white rounded-lg text-xs font-bold border border-gray-100 dark:border-vikinger-dark-100 shadow-sm"
            >
              <Icon icon="fluent:filter-24-regular" class="w-4 h-4 text-blue-500" />
              ตัวกรอง
              <span v-if="activeFiltersCount > 0" class="flex items-center justify-center w-4 h-4 bg-blue-600 text-white text-[9px] rounded-full">
                {{ activeFiltersCount }}
              </span>
            </button>

            <Icon icon="fluent:arrow-sort-24-regular" class="text-gray-400 w-4 h-4" />
            <select v-model="sortBy" class="bg-white dark:bg-vikinger-dark-200 border-none rounded-lg py-1.5 px-3 text-xs font-bold focus:ring-2 focus:ring-vikinger-purple dark:text-white focus:outline-none shadow-sm">
              <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
        </div>

        <!-- Active Filter Chips -->
        <div v-if="selectedCategory !== 'all' || selectedLevel !== 'all' || selectedEducationLevel !== 'all' || selectedSemester !== 'all' || selectedYear !== 'all' || marketplaceOnly || enrollableOnly || isFree" 
             class="flex flex-wrap gap-2 px-1">
          <div v-if="selectedCategory !== 'all'" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 rounded-lg text-xs font-bold border border-blue-100 dark:border-blue-800">
            หมวดหมู่: {{ categories.find(c => c.value === selectedCategory)?.label || selectedCategory }}
            <button @click="selectedCategory = 'all'" class="hover:text-blue-800"><Icon icon="fluent:dismiss-12-filled" /></button>
          </div>
          <div v-if="selectedEducationLevel !== 'all'" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-300 rounded-lg text-xs font-bold border border-indigo-100 dark:border-indigo-800">
            ระดับการศึกษา: {{ educationLevels.find(e => e.value === selectedEducationLevel)?.label || selectedEducationLevel }}
            <button @click="selectedEducationLevel = 'all'" class="hover:text-indigo-800"><Icon icon="fluent:dismiss-12-filled" /></button>
          </div>
          <div v-if="selectedLevel !== 'all'" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-300 rounded-lg text-xs font-bold border border-cyan-100 dark:border-cyan-800">
            ระดับชั้น/ชั้นปี: {{ levels.find(l => l.value === selectedLevel)?.label || selectedLevel }}
            <button @click="selectedLevel = 'all'" class="hover:text-cyan-800"><Icon icon="fluent:dismiss-12-filled" /></button>
          </div>
          <div v-if="selectedSemester !== 'all'" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-300 rounded-lg text-xs font-bold border border-amber-100 dark:border-amber-800">
            ภาคเรียน: {{ semesters.find(s => s.value === selectedSemester)?.label || selectedSemester }}
            <button @click="selectedSemester = 'all'" class="hover:text-amber-800"><Icon icon="fluent:dismiss-12-filled" /></button>
          </div>
          <div v-if="selectedYear !== 'all'" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-300 rounded-lg text-xs font-bold border border-orange-100 dark:border-orange-800">
            ปีการศึกษา: {{ years.find(y => y.value === selectedYear)?.label || selectedYear }}
            <button @click="selectedYear = 'all'" class="hover:text-orange-800"><Icon icon="fluent:dismiss-12-filled" /></button>
          </div>
          <div v-if="enrollableOnly" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-300 rounded-lg text-xs font-bold border border-emerald-100 dark:border-emerald-800">
            เปิดรับสมัคร
            <button @click="enrollableOnly = false" class="hover:text-emerald-800"><Icon icon="fluent:dismiss-12-filled" /></button>
          </div>
          <div v-if="marketplaceOnly" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-300 rounded-lg text-xs font-bold border border-violet-100 dark:border-violet-800">
            มี Master Copy
            <button @click="marketplaceOnly = false" class="hover:text-violet-800"><Icon icon="fluent:dismiss-12-filled" /></button>
          </div>
          <div v-if="isFree" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-300 rounded-lg text-xs font-bold border border-green-100 dark:border-green-800">
            รายวิชาฟรี
            <button @click="isFree = false" class="hover:text-green-800"><Icon icon="fluent:dismiss-12-filled" /></button>
          </div>
          <button @click="resetFilters" class="text-xs font-bold text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 underline underline-offset-4 px-1">
            ล้างทั้งหมด
          </button>
        </div>
      </div>

      <!-- Count bar (tab: วิชาของฉัน / กำลังเรียน) -->
      <div v-else class="mb-4 px-1">
        <div class="bg-vikinger-purple/10 text-vikinger-purple px-3 py-1 rounded-full text-xs font-black inline-block">
          {{ formatNumber(activeCourses.length) }} รายวิชา
        </div>
      </div>

      <!-- Loading Skeleton -->
      <template v-if="activeLoading">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
          <div v-for="i in 6" :key="i" class="bg-white dark:bg-vikinger-dark-200 rounded-2xl overflow-hidden animate-pulse shadow-sm h-80">
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
      <div v-else-if="activeError" class="bg-white dark:bg-vikinger-dark-200 rounded-2xl p-12 text-center shadow-sm border border-gray-100 dark:border-vikinger-dark-100">
        <Icon icon="fluent:error-circle-24-regular" class="w-16 h-16 text-red-500 mx-auto mb-4" />
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">เกิดข้อผิดพลาด</h3>
        <p class="text-sm text-gray-500 mb-6">{{ activeError }}</p>
        <button @click="activeTab === 'all' ? fetchCourses(1) : activeTab === 'my' ? fetchMyCourses() : fetchEnrolledCourses()" class="px-8 py-2.5 bg-gradient-vikinger text-white rounded-xl font-bold shadow-vikinger transition-all hover:scale-105">
          ลองใหม่อีกครั้ง
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="activeCourses.length === 0" class="bg-white dark:bg-vikinger-dark-200 rounded-2xl p-16 text-center shadow-sm border border-dashed border-gray-300 dark:border-vikinger-dark-100">
        <Icon icon="fluent:book-search-24-regular" class="w-20 h-20 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">
          {{ activeTab === 'my' ? 'ยังไม่มีรายวิชาของคุณ' : activeTab === 'enrolled' ? 'ยังไม่ได้เรียนวิชาใด' : 'ไม่พบรายวิชาที่ค้นหา' }}
        </h3>
        <p class="text-gray-500 text-sm">
          {{ activeTab === 'my' ? 'เริ่มสร้างรายวิชาแรกของคุณได้เลย' : activeTab === 'enrolled' ? 'สำรวจรายวิชาในแท็บ "ทั้งหมด" แล้วสมัครเรียน' : 'ลองใช้คำค้นอื่น หรือปรับเปลี่ยนตัวกรอง' }}
        </p>
        <NuxtLink v-if="activeTab === 'my' && canCreateCourse" to="/Learn/Courses/create" class="inline-flex items-center gap-2 mt-4 px-6 py-2.5 bg-gradient-vikinger text-white rounded-xl font-bold shadow-vikinger transition-all hover:scale-105">
          <Icon icon="fluent:add-24-filled" class="w-4 h-4" />
          สร้างรายวิชา
        </NuxtLink>
        <template v-else-if="activeTab === 'my'">
          <button disabled class="inline-flex items-center gap-2 mt-4 px-6 py-2.5 bg-gradient-vikinger text-white rounded-xl font-bold shadow-vikinger opacity-50 cursor-not-allowed">
            <Icon icon="mdi:lock" class="w-4 h-4" />
            สร้างรายวิชา
          </button>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            ต้องมีคะแนนสะสม {{ formatNumber(createCourseThreshold) }} แต้ม จึงจะสร้างรายวิชาได้
          </p>
        </template>
        <button v-else-if="activeTab === 'enrolled'" @click="activeTab = 'all'" class="inline-flex items-center gap-2 mt-4 px-6 py-2.5 bg-gradient-vikinger text-white rounded-xl font-bold shadow-vikinger transition-all hover:scale-105">
          <Icon icon="fluent:globe-24-regular" class="w-4 h-4" />
          ดูรายวิชาทั้งหมด
        </button>
      </div>

      <!-- Course Grid -->
      <template v-else>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
          <CourseMarketCard
            v-for="course in activeCourses"
            :key="course.id"
            :course="course"
          />
        </div>

        <!-- Load More (tab: ทั้งหมด เท่านั้น) -->
        <div v-if="hasMorePages && activeTab === 'all'" class="mt-10 text-center">
          <button
            @click="loadMore"
            :disabled="isLoadingMore"
            class="group relative inline-flex items-center gap-2 px-8 py-3.5 bg-white dark:bg-vikinger-dark-200 text-vikinger-purple dark:text-vikinger-cyan font-black rounded-2xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 transition-all hover:shadow-md hover:-translate-y-1 active:scale-95 disabled:opacity-50"
          >
            <Icon :icon="isLoadingMore ? 'svg-spinners:ring-resize' : 'fluent:arrow-down-24-filled'" class="w-5 h-5 transition-transform group-hover:translate-y-1" />
            {{ isLoadingMore ? 'กำลังโหลดข้อมูล...' : 'ดูรายวิชาเพิ่มเติม' }}
          </button>
        </div>
      </template>
    </div>

    <!-- Mobile Filter Modal -->
    <Modal :show="isFilterDrawerOpen" @close="isFilterDrawerOpen = false" maxWidth="lg">
      <div class="p-0">
        <CourseSearchFilterWidget
          v-model:searchQuery="searchQuery"
          v-model:selectedCategory="selectedCategory"
          v-model:selectedLevel="selectedLevel"
          v-model:selectedEducationLevel="selectedEducationLevel"
          v-model:selectedEducationYear="selectedEducationYear"
          v-model:selectedSemester="selectedSemester"
          v-model:selectedYear="selectedYear"
          v-model:sortBy="sortBy"
          v-model:marketplaceOnly="marketplaceOnly"
          v-model:enrollableOnly="enrollableOnly"
          v-model:isFree="isFree"
          :categories="categories"
          :levels="levels"
          :educationLevels="educationLevels"
          :semesters="semesters"
          :years="years"
          :sortOptions="sortOptions"
          @handleSearch="handleSearch"
          @clearFilters="resetFilters"
        />
        <div class="p-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 flex justify-end">
          <button 
            @click="isFilterDrawerOpen = false"
            class="px-6 py-2 bg-blue-600 text-white font-bold rounded-lg shadow-md hover:bg-blue-700 transition-all"
          >
            ตกลง
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<style scoped>
.bg-gradient-vikinger {
  background: linear-gradient(135deg, #8B5CF6 0%, #06B6D4 100%);
}
.shadow-vikinger {
  box-shadow: 0 10px 25px -5px rgba(111, 66, 193, 0.3), 0 8px 10px -6px rgba(111, 66, 193, 0.1);
}
</style>

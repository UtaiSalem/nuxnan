<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import CourseMarketCard from '~/components/academy/CourseMarketCard.vue'
import CourseMarketCardSkeleton from '~/components/academy/CourseMarketCardSkeleton.vue'
import CoursePurchaseModal from '~/components/academy/CoursePurchaseModal.vue'
import RecentlyViewedCoursesWidget from '~/components/widgets/RecentlyViewedCoursesWidget.vue'
import PopularCoursesWidget from '~/components/widgets/PopularCoursesWidget.vue'
import MemberedAcademiesWidget from '~/components/widgets/MemberedAcademiesWidget.vue'
import AllAcademiesWidget from '~/components/widgets/AllAcademiesWidget.vue'
import MarketplaceFilterWidget from '~/components/marketplace/MarketplaceFilterWidget.vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: false, // We use <NuxtLayout name="main"> manually in template
})

const authStore = useAuthStore()
const api = useApi()

const activeTab = ref('browse')
const courses = ref([])
const history = ref([])
const analytics = ref(null)
const loading = ref(false)
const totalCourses = ref(0)
const currentPage = ref(1)
const totalPages = ref(1)

const defaultFilters = () => ({
  search: '',
  category: '',
  level: '',
  language: '',
  semester: '',
  academic_year: '',
  credit_units_min: null,
  credit_units_max: null,
  hours_per_week_min: null,
  hours_per_week_max: null,
  price_min: null,
  price_max: null,
  is_free: false,
  rating_min: null,
  sort: 'newest',
})

const filters = ref(defaultFilters())

const resetFilters = () => {
  filters.value = defaultFilters()
  currentPage.value = 1
  fetchCourses()
}

const onSortChange = () => {
  currentPage.value = 1
  fetchCourses()
}

const fetchCourses = async () => {
  loading.value = true
  try {
    const response: any = await api.get('/api/courses/marketplace', {
      params: {
        ...filters.value,
        page: currentPage.value
      }
    })
    
    // Response data mapping based on backend structure
    // Backend returns { success: true, data: [...], meta: {...} }
    // CourseResource::collection might wrap data in 'data' key if paginated
    const responseData = response.data?.data || response.data || []
    courses.value = Array.isArray(responseData) ? responseData : []
    
    // Meta is at the top level in the controller's response
    totalCourses.value = response.meta?.total || courses.value.length || 0
    totalPages.value = response.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch marketplace courses:', error)
    courses.value = []
  } finally {
    loading.value = false
  }
}

const fetchHistory = async () => {
  loading.value = true
  try {
    const response = await api.get('/api/courses/purchases/history')
    history.value = response.data?.purchases || response.purchases || []
  } catch (error) {
    console.error('Failed to fetch purchase history:', error)
  } finally {
    loading.value = false
  }
}

const fetchSalesAnalytics = async () => {
  loading.value = true
  try {
    const response = await api.get('/api/courses/sales/analytics')
    analytics.value = response.data?.analytics || response.analytics || null
  } catch (error) {
    console.error('Failed to fetch sales analytics:', error)
  } finally {
    loading.value = false
  }
}

const changePage = (page: number) => {
  currentPage.value = page
  fetchCourses()
}

const selectedCourse = ref(null)
const showPurchaseModal = ref(false)

const openCloneModal = (course: any) => {
  selectedCourse.value = course
  showPurchaseModal.value = true
}

const openEnrollModal = (course: any) => {
  // TODO: open enrollment modal or navigate to course page
  navigateTo(`/Learn/Courses/${course.id}`)
}

const handlePurchaseSuccess = (result: any) => {
  fetchCourses()
  if (authStore.fetchUser) {
    authStore.fetchUser()
  }
}

const formatNumber = (num: number) => {
  return new Intl.NumberFormat().format(num || 0)
}

const formatDate = (date: string) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

watch(activeTab, (newTab) => {
  if (newTab === 'browse') fetchCourses()
  if (newTab === 'history') fetchHistory()
  if (newTab === 'analytics') fetchSalesAnalytics()
})

onMounted(() => {
  fetchCourses()
})

useHead({
  title: 'ตลาดลิขสิทธิ์รายวิชา - Marketplace',
})
</script>

<template>
  <NuxtLayout name="main">
    <!-- Left Widgets Slot -->
    <template #leftWidgets>
      <MarketplaceFilterWidget 
        v-if="activeTab === 'browse'"
        v-model="filters"
        :loading="loading"
        @apply="fetchCourses"
        @reset="resetFilters"
        class="mb-4"
      />
      <RecentlyViewedCoursesWidget class="mb-4" />
      <PopularCoursesWidget class="mb-4" />
    </template>

    <!-- Right Widgets Slot -->
    <template #rightWidgets>
      <!-- Standard right widgets from newsfeed for consistency -->
      <MemberedAcademiesWidget class="mb-4" />
      <AllAcademiesWidget class="mb-4" />
    </template>

    <!-- Main Center Content -->
    <div class="space-y-6">
      
      <!-- Hero Banner — Responsive -->
      <div class="relative overflow-hidden rounded-2xl shadow-vikinger">
        <!-- Background layers -->
        <div class="absolute inset-0 bg-gradient-to-br from-violet-600 via-purple-600 to-cyan-500"></div>
        <div class="absolute inset-0 bg-[url('/images/noise.png')] opacity-10 mix-blend-overlay pointer-events-none"></div>
        <div class="absolute -top-32 -right-20 w-72 h-72 bg-white/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-32 -left-20 w-72 h-72 bg-cyan-300/40 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Content -->
        <div class="relative z-10 p-5 sm:p-6 md:p-8 lg:p-10">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            
            <!-- Title block -->
            <div class="flex-1 min-w-0">
              <!-- Badge -->
              <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-white/15 backdrop-blur-md rounded-full text-white/90 text-xs font-bold uppercase tracking-wider border border-white/20 mb-3">
                <Icon icon="fluent:sparkle-24-filled" class="w-3.5 h-3.5" />
                Master Copy Marketplace
              </div>

              <h1 class="text-white text-2xl sm:text-3xl md:text-4xl font-black leading-tight mb-2 flex items-start gap-2 sm:gap-3">
                <Icon icon="fluent:shopping-bag-24-filled" class="w-7 h-7 sm:w-9 sm:h-9 md:w-10 md:h-10 flex-shrink-0 mt-0.5 text-amber-300 drop-shadow-lg" />
                <span class="break-words">ตลาดลิขสิทธิ์รายวิชา</span>
              </h1>

              <p class="text-white/85 text-sm sm:text-base max-w-2xl font-medium leading-relaxed">
                เลือกซื้อสำเนาต้นฉบับรายวิชา (Master Copy) เพื่อนำไปจัดการเรียนการสอนในสถาบันของคุณได้ทันที
              </p>

              <!-- Quick stats (visible on tablet+) -->
              <div class="hidden sm:flex items-center gap-4 mt-4">
                <div class="flex items-center gap-1.5 text-white/80 text-xs">
                  <Icon icon="fluent:checkmark-circle-24-filled" class="w-4 h-4 text-emerald-300" />
                  <span>คัดลอกทันที</span>
                </div>
                <div class="w-1 h-1 rounded-full bg-white/30"></div>
                <div class="flex items-center gap-1.5 text-white/80 text-xs">
                  <Icon icon="fluent:shield-checkmark-24-filled" class="w-4 h-4 text-emerald-300" />
                  <span>ลิขสิทธิ์ถูกต้อง</span>
                </div>
                <div class="w-1 h-1 rounded-full bg-white/30"></div>
                <div class="flex items-center gap-1.5 text-white/80 text-xs">
                  <Icon icon="fluent:money-24-filled" class="w-4 h-4 text-emerald-300" />
                  <span>จ่ายได้หลายวิธี</span>
                </div>
              </div>
            </div>
            
            <!-- Balance Cards — Stack on mobile, row on lg -->
            <div class="grid grid-cols-2 lg:flex lg:flex-col xl:flex-row gap-2 sm:gap-3 lg:min-w-[280px] xl:min-w-[420px]">
              <!-- Wallet Card -->
              <div class="bg-white/15 backdrop-blur-md border border-white/25 rounded-xl p-3 sm:p-4 hover:bg-white/20 transition-colors">
                <div class="flex items-center gap-2 mb-1">
                  <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-emerald-400/30 flex items-center justify-center">
                    <Icon icon="fluent:wallet-24-filled" class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-200" />
                  </div>
                  <span class="text-[10px] sm:text-xs text-white/80 uppercase font-bold tracking-wider">เงินคงเหลือ</span>
                </div>
                <div class="font-black text-white text-lg sm:text-xl md:text-2xl">
                  ฿{{ formatNumber(authStore.user?.wallet || 0) }}
                </div>
              </div>

              <!-- Points Card -->
              <div class="bg-white/15 backdrop-blur-md border border-white/25 rounded-xl p-3 sm:p-4 hover:bg-white/20 transition-colors">
                <div class="flex items-center gap-2 mb-1">
                  <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-amber-400/30 flex items-center justify-center">
                    <Icon icon="fluent:star-24-filled" class="w-4 h-4 sm:w-5 sm:h-5 text-amber-200" />
                  </div>
                  <span class="text-[10px] sm:text-xs text-white/80 uppercase font-bold tracking-wider">แต้มสะสม</span>
                </div>
                <div class="font-black text-white text-lg sm:text-xl md:text-2xl">
                  {{ formatNumber(authStore.points || 0) }}
                  <span class="text-xs sm:text-sm font-bold opacity-70">P</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom decorative wave (visible md+) -->
        <svg class="hidden md:block absolute bottom-0 left-0 right-0 w-full" viewBox="0 0 1440 60" preserveAspectRatio="none" style="height: 30px;">
          <path d="M0,30 C320,60 640,0 960,30 C1280,60 1440,30 1440,30 L1440,60 L0,60 Z" fill="rgba(255,255,255,0.08)"/>
        </svg>
      </div>

      <!-- Tab Navigation -->
      <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 p-1 flex gap-1 overflow-x-auto no-scrollbar">
        <button 
          @click="activeTab = 'browse'" 
          class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold transition-all whitespace-nowrap"
          :class="activeTab === 'browse' ? 'bg-vikinger-purple/10 text-vikinger-purple dark:text-vikinger-cyan' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 dark:text-gray-400'"
        >
          <Icon icon="fluent:search-24-filled" class="w-4 h-4" />
          เลือกซื้อลิขสิทธิ์
        </button>
        <button 
          @click="activeTab = 'history'" 
          class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold transition-all whitespace-nowrap"
          :class="activeTab === 'history' ? 'bg-vikinger-purple/10 text-vikinger-purple dark:text-vikinger-cyan' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 dark:text-gray-400'"
        >
          <Icon icon="fluent:history-24-filled" class="w-4 h-4" />
          ประวัติการซื้อต้นฉบับ
        </button>
        <button 
          @click="activeTab = 'analytics'" 
          class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold transition-all whitespace-nowrap"
          :class="activeTab === 'analytics' ? 'bg-vikinger-purple/10 text-vikinger-purple dark:text-vikinger-cyan' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 dark:text-gray-400'"
        >
          <Icon icon="fluent:data-line-24-filled" class="w-4 h-4" />
          รายได้จากการขายลิขสิทธิ์
        </button>
      </div>

      <!-- Content Area -->
      
      <!-- BROWSE TAB -->
      <div v-if="activeTab === 'browse'">
        <div class="flex items-center justify-between mb-4 px-1">
          <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
            พบ <span class="text-vikinger-purple bg-vikinger-purple/10 px-2 py-0.5 rounded-md">{{ totalCourses }}</span> รายการ
          </h3>
          <div class="flex items-center gap-2">
            <Icon icon="fluent:arrow-sort-24-regular" class="text-gray-400 w-4 h-4" />
            <select v-model="filters.sort" @change="onSortChange" class="bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-100 rounded-lg py-1.5 px-3 text-sm focus:ring-2 focus:ring-vikinger-purple dark:text-white focus:outline-none">
              <option value="newest">ใหม่ล่าสุด</option>
              <option value="popular">ยอดนิยม</option>
              <option value="price_asc">ราคา: ต่ำ-สูง</option>
              <option value="price_desc">ราคา: สูง-ต่ำ</option>
            </select>
          </div>
        </div>

        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <CourseMarketCardSkeleton v-for="i in 4" :key="i" />
        </div>

        <div v-else-if="courses.length === 0" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-12 text-center border-2 border-dashed border-gray-200 dark:border-vikinger-dark-100">
          <Icon icon="fluent:box-search-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
          <h4 class="text-lg font-bold text-gray-800 dark:text-white mb-2">ไม่พบรายวิชาต้นฉบับ</h4>
          <p class="text-sm text-gray-500">ลองเปลี่ยนตัวกรองหรือคำค้นหาใหม่</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <CourseMarketCard
            v-for="course in courses"
            :key="course.id"
            :course="course"
            @clone="openCloneModal"
            @enroll="openEnrollModal"
          />
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="mt-8 flex justify-center gap-2">
          <button 
            @click="changePage(currentPage - 1)" 
            :disabled="currentPage === 1"
            class="p-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-100 disabled:opacity-50 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"
          >
            <Icon icon="fluent:chevron-left-24-regular" class="w-5 h-5 dark:text-white" />
          </button>
          <button 
            v-for="page in totalPages" 
            :key="page"
            @click="changePage(page)"
            class="w-10 h-10 rounded-lg font-bold transition-all"
            :class="page === currentPage ? 'bg-gradient-vikinger text-white shadow-md' : 'bg-white dark:bg-vikinger-dark-200 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-vikinger-dark-100 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100'"
          >
            {{ page }}
          </button>
          <button 
            @click="changePage(currentPage + 1)" 
            :disabled="currentPage === totalPages"
            class="p-2 rounded-lg bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-100 disabled:opacity-50 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"
          >
            <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5 dark:text-white" />
          </button>
        </div>
      </div>

      <!-- HISTORY TAB -->
      <div v-if="activeTab === 'history'">
        <div v-if="loading" class="space-y-3">
          <div v-for="i in 3" :key="i" class="h-24 bg-gray-200 dark:bg-vikinger-dark-200 rounded-xl animate-pulse"></div>
        </div>
        <div v-else-if="history.length === 0" class="text-center py-16 bg-white dark:bg-vikinger-dark-200 rounded-xl border border-gray-100 dark:border-vikinger-dark-100">
          <Icon icon="fluent:history-dismiss-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
          <h4 class="text-lg font-bold text-gray-800 dark:text-white">ไม่พบประวัติการซื้อลิขสิทธิ์</h4>
        </div>
        <div v-else class="space-y-3">
          <div v-for="item in history" :key="item.id" class="bg-white dark:bg-vikinger-dark-200 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 flex items-center gap-4 transition-transform hover:-translate-y-0.5">
            <img :src="item.course?.cover || '/images/course-placeholder.jpg'" class="w-16 h-16 rounded-lg object-cover border border-gray-100 dark:border-vikinger-dark-50" />
            <div class="flex-1">
              <h4 class="font-bold text-gray-800 dark:text-white text-sm line-clamp-1">{{ item.course?.name }}</h4>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
                <Icon icon="fluent:calendar-ltr-24-regular" class="w-3.5 h-3.5" />
                {{ formatDate(item.created_at) }}
              </p>
            </div>
            <div class="text-right">
              <div class="font-black text-sm md:text-base flex items-center justify-end gap-1" :class="item.amount > 0 ? 'text-vikinger-purple dark:text-vikinger-cyan' : 'text-gray-400'">
                <Icon :icon="item.currency === 'THB' ? 'fluent:wallet-24-filled' : 'fluent:star-24-filled'" class="w-4 h-4" />
                {{ formatNumber(item.amount) }}{{ item.currency !== 'THB' ? ' P' : '' }}
              </div>
              <span class="text-[10px] bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-2 py-0.5 rounded-full uppercase font-bold mt-1 inline-block">สำเร็จ</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ANALYTICS TAB -->
      <div v-if="activeTab === 'analytics'">
        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <div v-for="i in 2" :key="i" class="h-32 bg-gray-200 dark:bg-vikinger-dark-200 rounded-xl animate-pulse"></div>
        </div>
        <div v-else-if="!analytics" class="text-center py-16 bg-white dark:bg-vikinger-dark-200 rounded-xl border border-gray-100 dark:border-gray-100">
           <Icon icon="fluent:data-line-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
           <h4 class="text-lg font-bold text-gray-800 dark:text-white">ยังไม่มีข้อมูลรายได้จากลิขสิทธิ์</h4>
        </div>
        <div v-else class="space-y-6">
           <!-- Summary Cards -->
           <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="bg-gradient-vikinger p-5 rounded-xl text-white shadow-vikinger relative overflow-hidden">
                 <Icon icon="fluent:money-24-filled" class="absolute -right-4 -bottom-4 w-24 h-24 text-white/10" />
                 <div class="relative z-10">
                   <div class="text-xs font-bold uppercase opacity-80 mb-1 flex items-center gap-1">
                     <Icon icon="fluent:arrow-trending-up-24-filled" class="w-4 h-4" /> รายได้ลิขสิทธิ์ทั้งหมด
                   </div>
                   <div class="text-3xl font-black">฿{{ formatNumber(analytics.total_revenue) }}</div>
                 </div>
              </div>
              <div class="bg-white dark:bg-vikinger-dark-200 p-5 rounded-xl border border-gray-100 dark:border-vikinger-dark-100 shadow-sm relative overflow-hidden">
                 <Icon icon="fluent:receipt-24-filled" class="absolute -right-4 -bottom-4 w-24 h-24 text-gray-100 dark:text-vikinger-dark-50" />
                 <div class="relative z-10">
                   <div class="text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-1">จำนวนที่ถูก Clone ไปสอน</div>
                   <div class="text-3xl font-black text-gray-800 dark:text-white">{{ formatNumber(analytics.total_sales) }} <span class="text-sm font-normal text-gray-500">ครั้ง</span></div>
                 </div>
              </div>
           </div>

           <!-- Sales By Course -->
           <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 overflow-hidden">
              <div class="px-5 py-4 border-b border-gray-100 dark:border-vikinger-dark-100 flex items-center gap-2">
                <Icon icon="fluent:table-24-regular" class="w-5 h-5 text-vikinger-purple" />
                <h4 class="font-bold text-gray-800 dark:text-white">ยอดขายลิขสิทธิ์แยกตามรายวิชา</h4>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-left whitespace-nowrap">
                  <thead class="bg-gray-50 dark:bg-vikinger-dark-100/50 text-gray-500 dark:text-gray-400">
                      <tr>
                        <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider">รายวิชาต้นฉบับ</th>
                        <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider text-center">จำนวนครั้งที่ขายได้</th>
                        <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider text-right">รายได้</th>
                      </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 dark:divide-vikinger-dark-100">
                      <tr v-for="item in analytics.sales_by_course" :key="item.course_id" class="hover:bg-gray-50/50 dark:hover:bg-vikinger-dark-100/50 transition-colors">
                        <td class="px-5 py-4 font-bold text-gray-800 dark:text-gray-200 text-sm max-w-[200px] truncate" :title="item.course_name">{{ item.course_name }}</td>
                        <td class="px-5 py-4 text-center font-bold text-gray-600 dark:text-gray-300">
                          <span class="bg-gray-100 dark:bg-vikinger-dark-50 px-2.5 py-1 rounded-md text-xs">{{ item.total_sales }}</span>
                        </td>
                        <td class="px-5 py-4 text-right font-black text-emerald-600 dark:text-emerald-400">฿{{ formatNumber(item.total_revenue) }}</td>
                      </tr>
                      <tr v-if="!analytics.sales_by_course?.length">
                        <td colspan="3" class="px-5 py-8 text-center text-gray-500 text-sm">ยังไม่มีข้อมูลการขายลิขสิทธิ์ต้นฉบับ</td>
                      </tr>
                  </tbody>
                </table>
              </div>
           </div>
        </div>
      </div>
    </div>

    <!-- Purchase Modal -->
    <CoursePurchaseModal 
      v-if="selectedCourse" 
      :course="selectedCourse" 
      :visible="showPurchaseModal"
      @close="showPurchaseModal = false"
      @success="handlePurchaseSuccess"
    />
  </NuxtLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

/* Base styles to align with vikinger theme */
.shadow-vikinger {
  box-shadow: 0 10px 25px -5px rgba(111, 66, 193, 0.3), 0 8px 10px -6px rgba(111, 66, 193, 0.1);
}
.bg-gradient-vikinger {
  background: linear-gradient(135deg, #8B5CF6 0%, #06B6D4 100%);
}
</style>

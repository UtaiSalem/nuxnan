<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import CourseMarketCard from '~/components/academy/CourseMarketCard.vue'
import CoursePurchaseModal from '~/components/academy/CoursePurchaseModal.vue'
import RecentlyViewedCoursesWidget from '~/components/widgets/RecentlyViewedCoursesWidget.vue'
import PopularCoursesWidget from '~/components/widgets/PopularCoursesWidget.vue'
import MemberedAcademiesWidget from '~/components/widgets/MemberedAcademiesWidget.vue'
import AllAcademiesWidget from '~/components/widgets/AllAcademiesWidget.vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: false, // We use <NuxtLayout name="main"> manually in template
})

const authStore = useAuthStore()
const { api } = useApi()

const activeTab = ref('browse')
const courses = ref([])
const history = ref([])
const analytics = ref(null)
const loading = ref(false)
const totalCourses = ref(0)
const currentPage = ref(1)
const totalPages = ref(1)

const filters = ref({
  search: '',
  price_type: 'all',
  category: '',
  sort: 'newest'
})

const priceTypes = [
  { label: 'ทั้งหมด', value: 'all' },
  { label: 'ฟรี (Clone ฟรี)', value: 'free' },
  { label: 'ใช้แต้ม (Points)', value: 'points' },
  { label: 'ใช้เงิน (Wallet)', value: 'wallet' },
  { label: 'ใช้ได้ทั้งสองอย่าง', value: 'both' }
]

const categories = ref(['การเขียนโปรแกรม', 'การออกแบบ', 'ธุรกิจ', 'ภาษา', 'ดนตรี', 'สุขภาพ'])

const fetchCourses = async () => {
  loading.value = true
  try {
    const response = await api.get('/api/courses/marketplace', {
      params: {
        ...filters.value,
        page: currentPage.value
      }
    })
    courses.value = response.data?.data || response.data || []
    totalCourses.value = response.data?.meta?.total || courses.value.length || 0
    totalPages.value = response.data?.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch marketplace courses:', error)
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

const openPurchaseModal = (course: any) => {
  selectedCourse.value = course
  showPurchaseModal.value = true
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
      <!-- Filter Widget specifically for Marketplace -->
      <div v-if="activeTab === 'browse'" class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 p-5 sticky top-24 mb-4">
        <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <Icon icon="fluent:filter-24-regular" class="w-5 h-5 text-vikinger-purple" />
          ตัวกรองตลาดลิขสิทธิ์
        </h3>

        <!-- Search -->
        <div class="mb-5">
          <label class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase mb-2 block">ค้นหาต้นฉบับ</label>
          <div class="relative">
            <input 
              v-model="filters.search" 
              type="text" 
              placeholder="ชื่อวิชา..." 
              class="w-full bg-gray-50 dark:bg-vikinger-dark-100 border-none rounded-lg py-2 pl-9 pr-3 text-sm focus:ring-2 focus:ring-vikinger-purple text-gray-800 dark:text-white"
              @keyup.enter="fetchCourses"
            />
            <Icon icon="fluent:search-24-regular" class="absolute left-3 top-2.5 text-gray-400 w-4 h-4" />
          </div>
        </div>

        <!-- Price Type -->
        <div class="mb-5">
          <label class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase mb-2 block">เงื่อนไขการ Clone</label>
          <div class="flex flex-col gap-2">
            <label v-for="type in priceTypes" :key="type.value" class="flex items-center gap-2 cursor-pointer group">
              <input type="radio" v-model="filters.price_type" :value="type.value" class="text-vikinger-purple focus:ring-vikinger-purple border-gray-300 dark:border-vikinger-dark-50" />
              <span class="text-sm text-gray-600 dark:text-gray-300 group-hover:text-vikinger-purple transition-colors">{{ type.label }}</span>
            </label>
          </div>
        </div>

        <!-- Categories -->
        <div class="mb-5">
          <label class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase mb-2 block">หมวดหมู่</label>
          <select v-model="filters.category" class="w-full bg-gray-50 dark:bg-vikinger-dark-100 border-none rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-vikinger-purple text-gray-800 dark:text-white">
            <option value="">ทั้งหมด</option>
            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
          </select>
        </div>

        <button 
          @click="fetchCourses" 
          class="w-full bg-gradient-vikinger hover:shadow-vikinger text-white font-bold py-2.5 rounded-lg transition-all flex items-center justify-center gap-2"
        >
          <Icon icon="fluent:search-24-filled" class="w-4 h-4" />
          ค้นหา
        </button>
      </div>
      
      <!-- Standard left widgets -->
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
      
      <!-- Hero Header -->
      <div class="bg-gradient-vikinger rounded-2xl p-6 md:p-8 text-white relative overflow-hidden shadow-vikinger">
        <div class="absolute inset-0 bg-[url('/images/noise.png')] opacity-10 mix-blend-overlay pointer-events-none"></div>
        <div class="absolute -top-24 -right-24 w-48 h-48 bg-white/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-vikinger-cyan/40 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <h1 class="text-2xl md:text-3xl font-black mb-2 flex items-center gap-2">
              <Icon icon="fluent:shopping-bag-24-filled" class="w-8 h-8 text-white/90" />
              ตลาดลิขสิทธิ์รายวิชา
            </h1>
            <p class="text-white/80 text-sm md:text-base max-w-xl font-medium">
              เลือกซื้อสำเนาต้นฉบับรายวิชา (Master Copy) เพื่อนำไปจัดการเรียนการสอนในสถาบันของคุณได้ทันที
            </p>
          </div>
          
          <!-- Balance Cards -->
          <div class="flex items-center gap-3 shrink-0">
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-3 flex gap-4">
              <div class="flex flex-col">
                <span class="text-[10px] text-white/70 uppercase font-bold tracking-wider">เงินคงเหลือ</span>
                <span class="font-black text-white flex items-center gap-1 text-lg">
                  <Icon icon="fluent:wallet-24-filled" class="w-4 h-4 text-emerald-400" />
                  ฿{{ formatNumber(authStore.user?.wallet || 0) }}
                </span>
              </div>
              <div class="w-px bg-white/20"></div>
              <div class="flex flex-col">
                <span class="text-[10px] text-white/70 uppercase font-bold tracking-wider">แต้มของคุณ</span>
                <span class="font-black text-white flex items-center gap-1 text-lg">
                  <Icon icon="fluent:star-24-filled" class="w-4 h-4 text-amber-400" />
                  {{ formatNumber(authStore.points || 0) }} P
                </span>
              </div>
            </div>
          </div>
        </div>
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
            <select v-model="filters.sort" @change="fetchCourses" class="bg-white dark:bg-vikinger-dark-200 border border-gray-200 dark:border-vikinger-dark-100 rounded-lg py-1.5 px-3 text-sm focus:ring-2 focus:ring-vikinger-purple dark:text-white focus:outline-none">
              <option value="newest">ใหม่ล่าสุด</option>
              <option value="popular">ยอดนิยม</option>
              <option value="price_asc">ราคา: ต่ำ-สูง</option>
              <option value="price_desc">ราคา: สูง-ต่ำ</option>
            </select>
          </div>
        </div>

        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div v-for="i in 4" :key="i" class="h-64 bg-gray-200 dark:bg-vikinger-dark-200 rounded-xl animate-pulse"></div>
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
            @buy="openPurchaseModal"
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

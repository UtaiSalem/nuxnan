<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 pb-20">
    <!-- Page Header -->
    <section class="bg-gradient-to-r from-primary-600 to-primary-700 text-white py-12 mb-8 relative overflow-hidden">
      <div class="absolute inset-0 bg-grid-white/[0.05] pointer-events-none"></div>
      <div class="container mx-auto px-4 relative z-10">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <h1 class="text-3xl md:text-4xl font-black mb-2">Marketplace</h1>
            <p class="text-primary-100 max-w-xl">
              ซื้อ-ขาย รายวิชาคุณภาพสูง เพื่อนำไปใช้สอนหรือศึกษาต่อในแบบของคุณเอง
            </p>
          </div>
          <div class="flex items-center gap-3">
            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 flex items-center gap-4 border border-white/20">
              <div class="flex flex-col">
                <span class="text-[10px] text-primary-200 uppercase font-bold">ยอดเงินคงเหลือ</span>
                <span class="text-xl font-black">฿ {{ formatNumber(user?.wallet || 0) }}</span>
              </div>
              <div class="w-px h-8 bg-white/20"></div>
              <div class="flex flex-col">
                <span class="text-[10px] text-primary-200 uppercase font-bold">แต้มของคุณ</span>
                <span class="text-xl font-black">{{ formatNumber(user?.pp || 0) }} P</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Tab Bar -->
    <div class="container mx-auto px-4 mb-8">
      <div class="flex gap-2 border-b border-slate-200 dark:border-slate-700 overflow-x-auto no-scrollbar">
        <button 
          @click="activeTab = 'browse'" 
          :class="activeTab === 'browse' ? 'border-b-2 border-primary-600 text-primary-600 font-bold' : 'text-slate-500'"
          class="px-6 py-3 text-sm transition-all whitespace-nowrap"
        >
          <Icon icon="mdi:magnify" class="inline-block w-4 h-4 mr-2" />
          เลือกดูรายวิชา
        </button>
        <button 
          @click="activeTab = 'history'" 
          :class="activeTab === 'history' ? 'border-b-2 border-primary-600 text-primary-600 font-bold' : 'text-slate-500'"
          class="px-6 py-3 text-sm transition-all whitespace-nowrap"
        >
          <Icon icon="mdi:history" class="inline-block w-4 h-4 mr-2" />
          ประวัติการซื้อ
        </button>
        <button 
          @click="activeTab = 'analytics'" 
          :class="activeTab === 'analytics' ? 'border-b-2 border-primary-600 text-primary-600 font-bold' : 'text-slate-500'"
          class="px-6 py-3 text-sm transition-all whitespace-nowrap"
        >
          <Icon icon="mdi:chart-line" class="inline-block w-4 h-4 mr-2" />
          รายได้จากการขาย
        </button>
      </div>
    </div>

    <div class="container mx-auto px-4">
      <!-- BROWSE TAB -->
      <div v-if="activeTab === 'browse'" class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Filters -->
        <aside class="w-full lg:w-64 flex-shrink-0">
          <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 sticky top-24">
            <h2 class="font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
              <Icon icon="mdi:filter-variant" class="w-5 h-5" />
              ตัวกรอง
            </h2>

            <!-- Search -->
            <div class="mb-6">
              <label class="text-xs font-bold text-slate-400 uppercase mb-2 block">ค้นหา</label>
              <div class="relative">
                <input 
                  v-model="filters.search" 
                  type="text" 
                  placeholder="ชื่อวิชา..." 
                  class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-xl py-2 pl-10 pr-4 focus:ring-2 focus:ring-primary-500 text-sm dark:text-white"
                />
                <Icon icon="mdi:magnify" class="absolute left-3 top-2.5 text-slate-400 w-4 h-4" />
              </div>
            </div>

            <!-- Price Type -->
            <div class="mb-6">
              <label class="text-xs font-bold text-slate-400 uppercase mb-2 block">ประเภทราคา</label>
              <div class="flex flex-col gap-2">
                <label v-for="type in priceTypes" :key="type.value" class="flex items-center gap-2 cursor-pointer group">
                  <input type="radio" v-model="filters.price_type" :value="type.value" class="text-primary-600 focus:ring-primary-500" />
                  <span class="text-sm text-slate-600 dark:text-slate-300 group-hover:text-primary-600 transition-colors">{{ type.label }}</span>
                </label>
              </div>
            </div>

            <!-- Categories (Dynamic?) -->
            <div class="mb-6">
              <label class="text-xs font-bold text-slate-400 uppercase mb-2 block">หมวดหมู่</label>
              <select v-model="filters.category" class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-xl py-2 px-3 text-sm focus:ring-2 focus:ring-primary-500 dark:text-white">
                <option value="">ทั้งหมด</option>
                <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
              </select>
            </div>

            <button 
              @click="fetchCourses" 
              class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-primary-500/30 transition-all flex items-center justify-center gap-2"
            >
              ค้นหา
            </button>
          </div>
        </aside>

        <!-- Course Grid -->
        <main class="flex-1">
          <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-slate-800 dark:text-white">
              พบ {{ totalCourses }} รายการ
            </h3>
            <div class="flex items-center gap-2">
              <span class="text-sm text-slate-400">เรียงตาม:</span>
              <select v-model="filters.sort" @change="fetchCourses" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg py-1 px-3 text-sm focus:ring-2 focus:ring-primary-500 dark:text-white">
                <option value="newest">ใหม่ล่าสุด</option>
                <option value="popular">ยอดนิยม</option>
                <option value="price_asc">ราคา: ต่ำ-สูง</option>
                <option value="price_desc">ราคา: สูง-ต่ำ</option>
              </select>
            </div>
          </div>

          <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <div v-for="i in 6" :key="i" class="h-64 bg-white dark:bg-slate-800 rounded-2xl animate-pulse"></div>
          </div>

          <div v-else-if="courses.length === 0" class="bg-white dark:bg-slate-800 rounded-3xl p-12 text-center border-2 border-dashed border-slate-200 dark:border-slate-700">
            <Icon icon="mdi:shopping-search" class="w-16 h-16 text-slate-300 mx-auto mb-4" />
            <h4 class="text-xl font-bold text-slate-800 dark:text-white mb-2">ไม่พบรายวิชาที่ต้องการ</h4>
            <p class="text-slate-500">ลองเปลี่ยนตัวกรองหรือคำค้นหาใหม่</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <CourseMarketCard 
              v-for="course in courses" 
              :key="course.id" 
              :course="course" 
              @buy="openPurchaseModal"
            />
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="mt-12 flex justify-center gap-2">
            <button 
              @click="changePage(currentPage - 1)" 
              :disabled="currentPage === 1"
              class="p-2 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 disabled:opacity-50"
            >
              <Icon icon="mdi:chevron-left" class="w-5 h-5 dark:text-white" />
            </button>
            <button 
              v-for="page in totalPages" 
              :key="page"
              @click="changePage(page)"
              class="w-10 h-10 rounded-lg font-bold transition-colors"
              :class="page === currentPage ? 'bg-primary-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700'"
            >
              {{ page }}
            </button>
            <button 
              @click="changePage(currentPage + 1)" 
              :disabled="currentPage === totalPages"
              class="p-2 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 disabled:opacity-50"
            >
              <Icon icon="mdi:chevron-right" class="w-5 h-5 dark:text-white" />
            </button>
          </div>
        </main>
      </div>

      <!-- HISTORY TAB -->
      <div v-if="activeTab === 'history'">
        <div v-if="loading" class="space-y-4">
          <div v-for="i in 4" :key="i" class="h-24 bg-white dark:bg-slate-800 rounded-2xl animate-pulse"></div>
        </div>
        <div v-else-if="history.length === 0" class="text-center py-20 bg-white dark:bg-slate-800 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-700">
          <Icon icon="mdi:history" class="w-16 h-16 text-slate-300 mx-auto mb-4" />
          <h4 class="text-xl font-bold text-slate-800 dark:text-white">ไม่พบประวัติการซื้อ</h4>
        </div>
        <div v-else class="space-y-4">
          <div v-for="item in history" :key="item.id" class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 flex items-center gap-4">
            <img :src="item.course?.cover || '/images/course-placeholder.jpg'" class="w-16 h-16 rounded-xl object-cover" />
            <div class="flex-1">
              <h4 class="font-bold text-slate-800 dark:text-white text-sm">{{ item.course?.name }}</h4>
              <p class="text-xs text-slate-500 mt-1">วันที่ซื้อ: {{ formatDate(item.created_at) }}</p>
            </div>
            <div class="text-right">
              <div class="font-black" :class="item.amount > 0 ? 'text-primary-600' : 'text-slate-400'">
                {{ item.currency === 'THB' ? '฿' : '' }}{{ formatNumber(item.amount) }}{{ item.currency !== 'THB' ? ' P' : '' }}
              </div>
              <span class="text-[10px] bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded text-slate-500 uppercase font-bold">COMPLETED</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ANALYTICS TAB -->
      <div v-if="activeTab === 'analytics'">
        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="i in 3" :key="i" class="h-32 bg-white dark:bg-slate-800 rounded-2xl animate-pulse"></div>
        </div>
        <div v-else-if="!analytics" class="text-center py-20 bg-white dark:bg-slate-800 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-700">
           <Icon icon="mdi:chart-off" class="w-16 h-16 text-slate-300 mx-auto mb-4" />
           <h4 class="text-xl font-bold text-slate-800 dark:text-white">ยังไม่มีข้อมูลรายได้</h4>
        </div>
        <div v-else class="space-y-8">
           <!-- Summary Cards -->
           <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="bg-gradient-to-br from-primary-600 to-primary-700 p-6 rounded-3xl text-white shadow-xl">
                 <div class="text-xs font-bold uppercase opacity-80 mb-2">รายได้ทั้งหมด (รวม)</div>
                 <div class="text-4xl font-black">฿ {{ formatNumber(analytics.total_revenue) }}</div>
              </div>
              <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm">
                 <div class="text-xs font-bold uppercase text-slate-400 mb-2">ยอดขายรวม</div>
                 <div class="text-4xl font-black text-slate-800 dark:text-white">{{ formatNumber(analytics.total_sales) }} <span class="text-sm font-normal text-slate-500">ครั้ง</span></div>
              </div>
           </div>

           <!-- Sales By Course -->
           <div>
              <h4 class="font-bold text-slate-800 dark:text-white mb-4">ยอดขายแยกตามรายวิชา</h4>
              <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 overflow-hidden">
                 <table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                       <tr>
                          <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase">รายวิชา</th>
                          <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase text-center">ยอดขาย</th>
                          <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase text-right">รายได้</th>
                       </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                       <tr v-for="item in analytics.sales_by_course" :key="item.course_id">
                          <td class="px-6 py-4 font-bold text-slate-700 dark:text-slate-300 text-sm">{{ item.course_name }}</td>
                          <td class="px-6 py-4 text-center font-black text-slate-800 dark:text-white">{{ item.total_sales }}</td>
                          <td class="px-6 py-4 text-right font-black text-primary-600">฿ {{ formatNumber(item.total_revenue) }}</td>
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
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseMarketCard from '~/components/academy/CourseMarketCard.vue'
import CoursePurchaseModal from '~/components/academy/CoursePurchaseModal.vue'

const { user } = useAuth()
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
  { label: 'ฟรี', value: 'free' },
  { label: 'ใช้แต้ม', value: 'points' },
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
    courses.value = response.data.data
    totalCourses.value = response.data.meta.total
    totalPages.value = response.data.meta.last_page
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
    history.value = response.data.purchases
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
    analytics.value = response.data.analytics
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
  // Update user balance in auth store if needed
  // Or just refresh page
  fetchCourses()
}

const formatNumber = (num: number) => {
  return new Intl.NumberFormat().format(num)
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
  title: 'คลังวิชาชุมชน - Marketplace',
})
</script>

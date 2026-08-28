<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import MemberedCoursesWidget from '~/components/widgets/MemberedCoursesWidget.vue'
import MyCoursesWidget from '~/components/widgets/MyCoursesWidget.vue'

definePageMeta({
  layout: 'main',
  middleware: 'auth',
})

useHead({
  title: 'รายได้จากการขายลิขสิทธิ์ - Nuxnan',
})

const api = useApi()
const analytics = ref(null)
const loading = ref(true)

usePageLayoutWidgets({ left: true })

onMounted(() => {
  fetchSalesAnalytics()
})

const fetchSalesAnalytics = async () => {
  loading.value = true
  try {
    const response: any = await api.get('/api/courses/sales/analytics')
    analytics.value = response.data?.analytics || response.analytics || null
  } catch (error) {
    console.error('Failed to fetch sales analytics:', error)
  } finally {
    loading.value = false
  }
}

const formatNumber = (num: number) => {
  return new Intl.NumberFormat().format(num || 0)
}
</script>

<template>
  <div class="space-y-6">
    <ClientOnly><Teleport to="#left-widgets-slot">
      <MemberedCoursesWidget />
      <MyCoursesWidget />
    </Teleport></ClientOnly>

    <!-- Header -->
    <div class="bg-white dark:bg-vikinger-dark-200 p-4 sm:p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center text-emerald-600 dark:text-emerald-400">
          <Icon icon="fluent:data-line-24-filled" class="w-7 h-7" />
        </div>
        <div>
          <h1 class="text-xl font-black text-gray-800 dark:text-white">รายได้จากการขายลิขสิทธิ์</h1>
          <p class="text-xs text-gray-500 dark:text-gray-400">วิเคราะห์ยอดขายและรายได้จากต้นฉบับรายวิชาของคุณ</p>
        </div>
      </div>
      
      <NuxtLink to="/Learn/Courses" class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-vikinger-dark-100 hover:bg-gray-200 dark:hover:bg-vikinger-dark-50 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-300 transition-all">
        <Icon icon="fluent:arrow-left-24-filled" class="w-4 h-4" />
        กลับไปหน้าหลัก
      </NuxtLink>
    </div>

    <!-- Content -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
      <div v-for="i in 2" :key="i" class="h-32 bg-gray-200 dark:bg-vikinger-dark-200 rounded-2xl animate-pulse"></div>
    </div>

    <div v-else-if="!analytics" class="text-center py-16 bg-white dark:bg-vikinger-dark-200 rounded-2xl border border-gray-100 dark:border-vikinger-dark-100">
       <Icon icon="fluent:data-line-24-regular" class="w-20 h-20 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
       <h4 class="text-xl font-bold text-gray-800 dark:text-white">ยังไม่มีข้อมูลรายได้</h4>
       <p class="text-gray-500">รายวิชาที่คุณเปิดเป็นต้นฉบับยังไม่มียอดขายในขณะนี้</p>
    </div>

    <div v-else class="space-y-6">
       <!-- Summary Cards -->
       <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="bg-gradient-vikinger p-6 rounded-2xl text-white shadow-vikinger relative overflow-hidden">
             <Icon icon="fluent:money-24-filled" class="absolute -right-4 -bottom-4 w-32 h-32 text-white/10" />
             <div class="relative z-10">
               <div class="text-xs font-bold uppercase opacity-80 mb-2 flex items-center gap-1">
                 <Icon icon="fluent:arrow-trending-up-24-filled" class="w-4 h-4" /> รายได้รวมทั้งหมด
               </div>
               <div class="text-4xl font-black">฿{{ formatNumber(analytics.total_revenue) }}</div>
             </div>
          </div>
          
          <div class="bg-white dark:bg-vikinger-dark-200 p-4 sm:p-6 rounded-2xl border border-gray-100 dark:border-vikinger-dark-100 shadow-sm relative overflow-hidden">
             <Icon icon="fluent:receipt-24-filled" class="absolute -right-4 -bottom-4 w-32 h-32 text-gray-100 dark:text-vikinger-dark-50" />
             <div class="relative z-10">
               <div class="text-xs font-bold uppercase text-gray-500 dark:text-gray-400 mb-2">จำนวนการ Clone ไปสอน</div>
               <div class="text-4xl font-black text-gray-800 dark:text-white">
                 {{ formatNumber(analytics.total_sales) }}
                 <span class="text-sm font-normal text-gray-500 ml-1 uppercase">ครั้ง</span>
               </div>
             </div>
          </div>
       </div>

       <!-- Sales By Course Table -->
       <div class="bg-white dark:bg-vikinger-dark-200 rounded-2xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-vikinger-dark-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Icon icon="fluent:table-24-regular" class="w-5 h-5 text-vikinger-purple" />
              <h4 class="font-bold text-gray-800 dark:text-white">รายได้แยกตามรายวิชา</h4>
            </div>
          </div>
          
          <div class="overflow-x-auto">
            <table class="w-full text-left whitespace-nowrap">
              <thead class="bg-gray-50 dark:bg-vikinger-dark-100/50 text-gray-400">
                  <tr>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-wider">รายวิชาต้นฉบับ</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-wider text-center">ยอดขาย (ครั้ง)</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-wider text-right">รายได้สุทธิ</th>
                  </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-vikinger-dark-100">
                  <tr v-for="item in analytics.sales_by_course" :key="item.course_id" class="hover:bg-gray-50/50 dark:hover:bg-vikinger-dark-100/50 transition-colors">
                    <td class="px-6 py-5 font-bold text-gray-800 dark:text-gray-200 text-sm">
                      {{ item.course_name }}
                    </td>
                    <td class="px-6 py-5 text-center">
                      <span class="bg-gray-100 dark:bg-vikinger-dark-50 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-lg text-xs font-black">
                        {{ formatNumber(item.total_sales) }}
                      </span>
                    </td>
                    <td class="px-6 py-5 text-right font-black text-emerald-600 dark:text-emerald-400">
                      ฿{{ formatNumber(item.total_revenue) }}
                    </td>
                  </tr>
                  <tr v-if="!analytics.sales_by_course?.length">
                    <td colspan="3" class="px-6 py-12 text-center text-gray-400 text-sm italic">
                      ยังไม่มีข้อมูลการขายลิขสิทธิ์ต้นฉบับ
                    </td>
                  </tr>
              </tbody>
            </table>
          </div>
       </div>
    </div>
  </div>
</template>

<style scoped>
.bg-gradient-vikinger {
  background: linear-gradient(135deg, #8B5CF6 0%, #06B6D4 100%);
}
.shadow-vikinger {
  box-shadow: 0 10px 25px -5px rgba(111, 66, 193, 0.3);
}
</style>

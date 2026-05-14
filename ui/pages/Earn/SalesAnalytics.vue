<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({
  middleware: 'auth',
})

useHead({
  title: 'รายได้จากการขายลิขสิทธิ์ - Nuxnan',
})

const api = useApi()
const analytics = ref<any>(null)
const isLoading = ref(true)

const fetchAnalytics = async () => {
  isLoading.value = true
  try {
    const res: any = await api.get('/api/courses/sales/analytics')
    analytics.value = res.data || null
  } catch (error) {
    console.error('Failed to fetch sales analytics', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchAnalytics()
})

const formatNumber = (num: number) => new Intl.NumberFormat().format(num || 0)
</script>

<template>
  <NuxtLayout name="main">
    <template #hero>
      <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl p-8 text-white shadow-lg">
        <h1 class="text-3xl font-black mb-2 flex items-center gap-3">
          <Icon icon="fluent:data-line-24-filled" />
          รายได้จากการขายลิขสิทธิ์
        </h1>
        <p class="text-emerald-100">สรุปรายได้และสถิติการขาย Master Copy ของคุณ</p>
      </div>
    </template>

    <div class="space-y-6">
      <div v-if="isLoading" class="flex flex-col items-center justify-center py-20 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700">
        <Icon icon="svg-spinners:ring-resize" class="w-12 h-12 text-emerald-600 mb-4" />
        <p class="text-slate-500">กำลังโหลดข้อมูล...</p>
      </div>

      <template v-else>
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center text-emerald-600 mb-4">
              <Icon icon="fluent:money-24-filled" class="w-6 h-6" />
            </div>
            <div class="text-2xl font-black text-slate-800 dark:text-white">฿{{ formatNumber(analytics?.total_revenue_thb) }}</div>
            <div class="text-sm text-slate-500">รายได้รวม (บาท)</div>
          </div>
          
          <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center text-amber-600 mb-4">
              <Icon icon="fluent:star-24-filled" class="w-6 h-6" />
            </div>
            <div class="text-2xl font-black text-slate-800 dark:text-white">{{ formatNumber(analytics?.total_revenue_points) }} P</div>
            <div class="text-sm text-slate-500">รายได้รวม (แต้ม)</div>
          </div>

          <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center text-blue-600 mb-4">
              <Icon icon="fluent:cart-24-filled" class="w-6 h-6" />
            </div>
            <div class="text-2xl font-black text-slate-800 dark:text-white">{{ formatNumber(analytics?.total_sales_count) }}</div>
            <div class="text-sm text-slate-500">จำนวนการขายทั้งหมด</div>
          </div>
        </div>

        <!-- Placeholder for chart or detailed list -->
        <div class="bg-white dark:bg-slate-800 p-8 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm text-center">
          <Icon icon="fluent:document-chart-24-regular" class="w-16 h-16 text-slate-200 mx-auto mb-4" />
          <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-2">อยู่ระหว่างการพัฒนา</h3>
          <p class="text-slate-500">กราฟสถิติและรายละเอียดการขายเชิงลึกกำลังจะมาเร็วๆ นี้</p>
        </div>
      </template>
    </div>
  </NuxtLayout>
</template>

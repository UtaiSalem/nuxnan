<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main',
  middleware: 'auth',
})

useHead({
  title: 'ประวัติการซื้อ Master Copy - Nuxnan',
})

const api = useApi()
const purchases = ref([])
const isLoading = ref(true)

const fetchHistory = async () => {
  isLoading.value = true
  try {
    const res: any = await api.get('/api/courses/purchases/history')
    purchases.value = res.data || []
  } catch (error) {
    console.error('Failed to fetch purchase history', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchHistory()
})

const formatNumber = (num: number) => new Intl.NumberFormat().format(num || 0)
const formatDate = (date: string) => new Date(date).toLocaleDateString('th-TH', {
  year: 'numeric',
  month: 'long',
  day: 'numeric',
  hour: '2-digit',
  minute: '2-digit'
})
</script>

<template>
  <div class="space-y-4">
    <ClientOnly><Teleport to="#hero-slot">
      <div class="bg-gradient-to-r from-violet-600 to-indigo-600 rounded-2xl p-8 text-white shadow-lg">
        <h1 class="text-3xl font-black mb-2 flex items-center gap-3">
          <Icon icon="fluent:history-24-filled" />
          ประวัติการซื้อ Master Copy
        </h1>
        <p class="text-violet-100">รายการลิขสิทธิ์รายวิชาที่คุณเคยซื้อทั้งหมด</p>
      </div>
    </Teleport></ClientOnly>

    <div v-if="isLoading" class="flex flex-col items-center justify-center py-20 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700">
      <Icon icon="svg-spinners:ring-resize" class="w-12 h-12 text-violet-600 mb-4" />
      <p class="text-slate-500">กำลังโหลดข้อมูล...</p>
    </div>

    <div v-else-if="purchases.length === 0" class="text-center py-20 bg-white dark:bg-slate-800 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700">
      <Icon icon="fluent:cart-24-regular" class="w-16 h-16 text-slate-300 mx-auto mb-4" />
      <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-2">ยังไม่มีประวัติการซื้อ</h3>
      <p class="text-slate-500 mb-6">คุณยังไม่เคยซื้อลิขสิทธิ์รายวิชาใดๆ</p>
      <NuxtLink to="/Learn/Courses?marketplace_only=1" class="px-6 py-2.5 bg-violet-600 text-white rounded-xl font-bold hover:bg-violet-700 transition-all">ไปที่ตลาดลิขสิทธิ์</NuxtLink>
    </div>

    <div v-else class="grid grid-cols-1 gap-4">
      <div v-for="purchase in purchases" :key="purchase.id" class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm flex flex-col md:flex-row gap-4 items-center">
        <img :src="purchase.course?.cover_url" class="w-24 h-24 rounded-xl object-cover" />
        <div class="flex-1 text-center md:text-left">
          <h4 class="font-bold text-slate-800 dark:text-white">{{ purchase.course?.name }}</h4>
          <p class="text-xs text-slate-500">ซื้อเมื่อ: {{ formatDate(purchase.created_at) }}</p>
          <div class="mt-2 flex flex-wrap justify-center md:justify-start gap-2">
            <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-700 rounded text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase">
              ID: {{ purchase.order_number }}
            </span>
          </div>
        </div>
        <div class="text-center md:text-right px-4">
          <div class="font-black text-violet-600">
            {{ purchase.payment_method === 'points' ? `${formatNumber(purchase.amount_points)} P` : `฿${formatNumber(purchase.amount_thb)}` }}
          </div>
          <div class="text-[10px] text-slate-400 font-bold uppercase">{{ purchase.payment_method }}</div>
        </div>
        <NuxtLink :to="`/Learn/Courses/${purchase.new_course_id}/settings`" class="w-full md:w-auto px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-violet-600 hover:text-white rounded-xl text-sm font-bold transition-all text-center">
          ไปที่รายวิชา
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

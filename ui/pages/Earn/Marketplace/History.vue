<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import MemberedCoursesWidget from '~/components/widgets/MemberedCoursesWidget.vue'
import MyCoursesWidget from '~/components/widgets/MyCoursesWidget.vue'

definePageMeta({
  layout: false,
  middleware: 'auth',
})

useHead({
  title: 'ประวัติการซื้อลิขสิทธิ์รายวิชา - Nuxnan',
})

const api = useApi()
const history = ref([])
const loading = ref(true)

const fetchHistory = async () => {
  loading.value = true
  try {
    const response: any = await api.get('/api/courses/purchases/history')
    history.value = response.data?.purchases || response.purchases || []
  } catch (error) {
    console.error('Failed to fetch purchase history:', error)
  } finally {
    loading.value = false
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
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  fetchHistory()
})
</script>

<template>
  <NuxtLayout name="main">
    <template #leftWidgets>
      <MemberedCoursesWidget />
      <MyCoursesWidget />
    </template>

    <div class="space-y-6">
      <!-- Header -->
      <div class="bg-white dark:bg-vikinger-dark-200 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-400">
            <Icon icon="fluent:history-24-filled" class="w-7 h-7" />
          </div>
          <div>
            <h1 class="text-xl font-black text-gray-800 dark:text-white">ประวัติการซื้อลิขสิทธิ์</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400">รายการรายวิชาต้นฉบับที่คุณได้สั่งซื้อไว้ทั้งหมด</p>
          </div>
        </div>
        
        <NuxtLink to="/Learn/Courses" class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-vikinger-dark-100 hover:bg-gray-200 dark:hover:bg-vikinger-dark-50 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-300 transition-all">
          <Icon icon="fluent:arrow-left-24-filled" class="w-4 h-4" />
          กลับไปหน้าหลัก
        </NuxtLink>
      </div>

      <!-- Content -->
      <div v-if="loading" class="space-y-3">
        <div v-for="i in 5" :key="i" class="h-24 bg-gray-200 dark:bg-vikinger-dark-200 rounded-xl animate-pulse"></div>
      </div>

      <div v-else-if="history.length === 0" class="bg-white dark:bg-vikinger-dark-200 rounded-2xl p-16 text-center border border-dashed border-gray-300 dark:border-vikinger-dark-100">
        <Icon icon="fluent:history-dismiss-24-regular" class="w-20 h-20 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-2">ยังไม่มีประวัติการสั่งซื้อ</h3>
        <p class="text-gray-500 mb-6">คุณยังไม่เคยซื้อลิขสิทธิ์รายวิชาใดๆ มาก่อน</p>
        <NuxtLink to="/Learn/Courses?marketplace_only=1" class="px-8 py-3 bg-gradient-vikinger text-white rounded-xl font-bold shadow-vikinger transition-all hover:scale-105 inline-block">
          ไปเลือกซื้อลิขสิทธิ์
        </NuxtLink>
      </div>

      <div v-else class="space-y-3">
        <div v-for="item in history" :key="item.id" class="bg-white dark:bg-vikinger-dark-200 p-4 rounded-2xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 flex items-center gap-4 transition-all hover:shadow-md hover:-translate-y-0.5">
          <img :src="item.course?.cover || '/images/course-placeholder.jpg'" class="w-16 h-16 rounded-xl object-cover border border-gray-100 dark:border-vikinger-dark-50" />
          <div class="flex-1 min-w-0">
            <h4 class="font-black text-gray-800 dark:text-white text-sm md:text-base line-clamp-1">{{ item.course?.name }}</h4>
            <div class="flex items-center gap-3 mt-1.5">
              <p class="text-[10px] md:text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                <Icon icon="fluent:calendar-ltr-24-regular" class="w-3.5 h-3.5" />
                {{ formatDate(item.created_at) }}
              </p>
              <p class="text-[10px] md:text-xs text-blue-500 font-bold flex items-center gap-1">
                <Icon icon="fluent:person-24-regular" class="w-3.5 h-3.5" />
                {{ item.course?.user?.name || 'Instructor' }}
              </p>
            </div>
          </div>
          <div class="text-right shrink-0">
            <div class="font-black text-sm md:text-lg flex items-center justify-end gap-1" :class="item.amount > 0 ? 'text-vikinger-purple dark:text-vikinger-cyan' : 'text-gray-400'">
              <Icon :icon="item.currency === 'THB' ? 'fluent:wallet-24-filled' : 'fluent:star-24-filled'" class="w-4 h-4" />
              {{ formatNumber(item.amount) }}{{ item.currency !== 'THB' ? ' P' : '' }}
            </div>
            <span class="text-[10px] bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-2 py-0.5 rounded-full uppercase font-black mt-1 inline-block">สำเร็จ</span>
          </div>
        </div>
      </div>
    </div>
  </NuxtLayout>
</template>

<style scoped>
.bg-gradient-vikinger {
  background: linear-gradient(135deg, #8B5CF6 0%, #06B6D4 100%);
}
.shadow-vikinger {
  box-shadow: 0 10px 25px -5px rgba(111, 66, 193, 0.3);
}
</style>

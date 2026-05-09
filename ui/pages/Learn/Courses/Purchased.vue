<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 pb-20">
    <!-- Header -->
    <section class="bg-gradient-to-r from-emerald-600 to-teal-600 text-white py-12 mb-8 relative overflow-hidden">
      <div class="absolute inset-0 bg-grid-white/[0.05] pointer-events-none"></div>
      <div class="container mx-auto px-4 relative z-10">
        <h1 class="text-3xl md:text-4xl font-black mb-2">วิชาที่ซื้อมา</h1>
        <p class="text-emerald-100">รายการวิชาที่คุณซื้อมาจาก Marketplace และเป็นเจ้าของสำเนา</p>
      </div>
    </section>

    <div class="container mx-auto px-4">
      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div v-for="i in 8" :key="i" class="h-64 bg-white dark:bg-slate-800 rounded-2xl animate-pulse"></div>
      </div>

      <div v-else-if="courses.length === 0" class="bg-white dark:bg-slate-800 rounded-3xl p-16 text-center shadow-sm border border-slate-200 dark:border-slate-700">
        <Icon icon="mdi:shopping-outline" class="w-20 h-20 text-slate-200 mx-auto mb-6" />
        <h2 class="text-2xl font-bold text-slate-800 dark:text-white mb-2">คุณยังไม่มีวิชาที่ซื้อมา</h2>
        <p class="text-slate-500 mb-8">ไปลองเลือกวิชาเจ๋งๆ ใน Marketplace กันเถอะ!</p>
        <NuxtLink to="/Earn/Marketplace" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-8 py-3 rounded-xl transition-all shadow-lg shadow-emerald-500/30">
          ไปที่ Marketplace
        </NuxtLink>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div 
          v-for="course in courses" 
          :key="course.id"
          @click="goToSettings(course.id)"
          class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden group cursor-pointer hover:shadow-xl transition-all hover:-translate-y-1"
        >
          <div class="relative h-40 overflow-hidden">
            <img :src="course.cover_url || '/images/course-placeholder.jpg'" class="w-full h-full object-cover transition-transform group-hover:scale-110" />
            <div class="absolute top-2 right-2">
              <span class="bg-emerald-500 text-white text-[10px] px-2 py-0.5 rounded font-bold">PURCHASED</span>
            </div>
          </div>
          <div class="p-4">
            <h3 class="font-bold text-slate-800 dark:text-white mb-2 line-clamp-2">{{ course.name }}</h3>
            <div class="flex items-center justify-between text-xs text-slate-500">
              <span class="flex items-center gap-1">
                <Icon icon="mdi:book-open-variant" class="w-4 h-4" />
                {{ course.lessons_count || 0 }} บทเรียน
              </span>
              <span class="flex items-center gap-1">
                <Icon icon="mdi:calendar" class="w-4 h-4" />
                {{ formatDate(course.created_at) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({
  middleware: 'auth',
  layout: 'main'
})

const { user } = useAuth()
const { api } = useApi()

const courses = ref([])
const loading = ref(true)

const fetchPurchasedCourses = async () => {
  if (!user.value) return
  loading.value = true
  try {
    const response: any = await api.get(`/api/courses/users/${user.value.id}/my-courses`, {
      params: {
        cloned: '1',
        per_page: 50
      }
    })
    courses.value = response.courses.data || response.courses
  } catch (error) {
    console.error('Failed to fetch purchased courses:', error)
  } finally {
    loading.value = false
  }
}

const goToSettings = (id: number) => {
  navigateTo(`/Learn/Courses/${id}/settings`)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

onMounted(() => {
  fetchPurchasedCourses()
})
</script>

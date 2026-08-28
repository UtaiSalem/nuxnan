<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CreateNewCourse from '~/pages/Learn/Course/CreateNewCourse.vue'

const { threshold, points, canCreate, remaining, fetchThreshold } = useCourseCreateGate()
const isThresholdLoading = ref(true)

const formatPoints = (value: number) => value.toLocaleString('th-TH')

onMounted(async () => {
  await fetchThreshold()
  isThresholdLoading.value = false
})

definePageMeta({
  middleware: 'auth',
  layout: false
})
</script>

<template>
  <div v-if="isThresholdLoading" class="min-h-screen flex items-center justify-center px-0 py-4 sm:px-4 text-sm sm:text-base text-gray-500 dark:text-gray-400">
    กำลังตรวจสอบคะแนนสะสม...
  </div>
  <CreateNewCourse v-else-if="canCreate" />
  <div v-else class="min-h-screen flex items-center justify-center p-4 sm:p-8">
    <div class="w-full max-w-lg rounded-2xl border border-gray-100 dark:border-vikinger-dark-100 bg-white dark:bg-vikinger-dark-200 p-4 sm:p-8 text-center shadow-sm">
      <Icon icon="mdi:lock" class="w-12 h-12 mx-auto mb-4 text-vikinger-purple" />
      <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">ยังสร้างรายวิชาไม่ได้</h1>
      <p class="mt-3 text-sm sm:text-base text-gray-600 dark:text-gray-300">
        ต้องมีคะแนนสะสมอย่างน้อย {{ formatPoints(threshold) }} แต้ม เพื่อสร้างรายวิชา
      </p>
      <p class="mt-2 text-sm sm:text-base text-gray-500 dark:text-gray-400">
        คะแนนสะสมของคุณ {{ formatPoints(points) }} แต้ม · ขาดอีก {{ formatPoints(remaining) }} แต้ม
      </p>
      <NuxtLink to="/Learn/Courses" class="inline-flex min-h-[44px] items-center justify-center mt-6 px-4 py-3 rounded-xl bg-gradient-vikinger text-white text-sm sm:text-base font-bold shadow-vikinger">
        กลับไปหน้ารายวิชา
      </NuxtLink>
    </div>
  </div>
</template>

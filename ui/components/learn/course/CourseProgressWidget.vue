<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  member: any
  courseId: string | number
}

const props = defineProps<Props>()

const overallProgress = computed(() => {
  if (props.member?.overall_progress !== undefined) {
    return Number(props.member.overall_progress)
  }
  return 0
})

const lessonsCompleted = computed(() => props.member?.lessons_completed || 0)
const totalLessons = computed(() => props.member?.total_lessons || 0)
const lessonsProgress = computed(() => {
  if (totalLessons.value === 0) return 0
  return Math.round((lessonsCompleted.value / totalLessons.value) * 100)
})

const getProgressColor = (progress: number) => {
  if (progress >= 80) return 'bg-emerald-500'
  if (progress >= 50) return 'bg-blue-500'
  if (progress >= 20) return 'bg-amber-500'
  return 'bg-rose-500'
}

const getProgressTextColor = (progress: number) => {
  if (progress >= 80) return 'text-emerald-600 dark:text-emerald-400'
  if (progress >= 50) return 'text-blue-600 dark:text-blue-400'
  if (progress >= 20) return 'text-amber-600 dark:text-amber-400'
  return 'text-rose-600 dark:text-rose-400'
}
</script>

<template>
  <div class="rounded-2xl border border-gray-100 bg-white shadow-xl dark:border-vikinger-dark-100 dark:bg-vikinger-dark-200 overflow-hidden">
    <!-- Header -->
    <div class="px-5 py-4 border-b border-gray-100 dark:border-vikinger-dark-100 flex items-center justify-between bg-gray-50/50 dark:bg-vikinger-dark-100/30">
      <h3 class="font-black text-xs uppercase tracking-widest text-gray-400 dark:text-gray-500 flex items-center gap-2">
        <Icon icon="fluent:status-24-filled" class="w-4 h-4 text-emerald-500" />
        ความก้าวหน้า
      </h3>
      <span v-if="member" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 text-[10px] font-bold">
        กำลังเรียน
      </span>
    </div>

    <!-- Content -->
    <div v-if="member" class="p-5 space-y-6">
      <!-- Overall Progress -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">ความก้าวหน้าโดยรวม</span>
          <span class="text-lg font-black font-audiowide" :class="getProgressTextColor(overallProgress)">
            {{ overallProgress }}%
          </span>
        </div>
        <div class="h-2 rounded-full bg-gray-100 dark:bg-vikinger-dark-100 overflow-hidden border border-gray-200/50 dark:border-vikinger-dark-50 p-0.5">
          <div 
            class="h-full rounded-full transition-all duration-1000"
            :class="getProgressColor(overallProgress)"
            :style="{ width: `${overallProgress}%` }"
          ></div>
        </div>
      </div>

      <!-- Lesson Progress -->
      <div>
        <div class="flex items-center justify-between mb-2">
          <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">บทเรียนที่เรียนแล้ว</span>
          <span class="text-lg font-black font-audiowide text-blue-500">
            {{ lessonsProgress }}%
          </span>
        </div>
        <div class="h-2 rounded-full bg-gray-100 dark:bg-vikinger-dark-100 overflow-hidden border border-gray-200/50 dark:border-vikinger-dark-50 p-0.5">
          <div 
            class="h-full rounded-full bg-blue-500 transition-all duration-1000"
            :style="{ width: `${lessonsProgress}%` }"
          ></div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 gap-3">
        <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-vikinger-dark-100/50 border border-gray-100 dark:border-vikinger-dark-100 transition-all hover:border-blue-200 dark:hover:border-blue-900/30 group">
          <div class="flex items-center gap-2 mb-1.5">
            <Icon icon="fluent:book-24-regular" class="w-4 h-4 text-blue-500 group-hover:scale-110 transition-transform" />
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-tighter">เรียนแล้ว</span>
          </div>
          <div class="text-lg font-black text-gray-900 dark:text-white">
            {{ lessonsCompleted }} <span class="text-[10px] text-gray-400 font-bold">/ {{ totalLessons }}</span>
          </div>
        </div>

        <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-vikinger-dark-100/50 border border-gray-100 dark:border-vikinger-dark-100 transition-all hover:border-purple-200 dark:hover:border-purple-900/30 group">
          <div class="flex items-center gap-2 mb-1.5">
            <Icon icon="fluent:document-text-24-regular" class="w-4 h-4 text-purple-500 group-hover:scale-110 transition-transform" />
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-tighter">งานที่ส่ง</span>
          </div>
          <div class="text-lg font-black text-gray-900 dark:text-white">
            {{ member.assignments_completed || 0 }} <span class="text-[10px] text-gray-400 font-bold">/ {{ member.total_assignments || 0 }}</span>
          </div>
        </div>
      </div>

      <!-- Action Link -->
      <NuxtLink 
        :to="`/Learn/Courses/${courseId}/lessons`"
        class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:-translate-y-0.5 transition-all font-black text-xs uppercase tracking-widest"
      >
        <span>เรียนต่อจากเดิม</span>
        <Icon icon="fluent:chevron-right-24-filled" class="w-4 h-4" />
      </NuxtLink>

      <NuxtLink 
        :to="`/Learn/Courses/${courseId}/my-progress`"
        class="block text-center text-[10px] font-black text-gray-400 hover:text-vikinger-purple dark:hover:text-vikinger-cyan transition-colors uppercase tracking-widest"
      >
        ดูรายละเอียดความก้าวหน้าทั้งหมด
      </NuxtLink>
    </div>

    <!-- Empty State (Not Enrolled) -->
    <div v-else class="p-8 text-center space-y-4">
      <div class="w-16 h-16 bg-gray-50 dark:bg-vikinger-dark-100 rounded-full flex items-center justify-center mx-auto border-2 border-dashed border-gray-200 dark:border-gray-700">
        <Icon icon="fluent:lock-closed-24-regular" class="w-8 h-8 text-gray-300" />
      </div>
      <div>
        <h4 class="font-bold text-gray-900 dark:text-white text-sm">ยังไม่ได้สมัครเรียน</h4>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">สมัครเรียนเพื่อดูความก้าวหน้าของคุณ</p>
      </div>
    </div>
  </div>
</template>

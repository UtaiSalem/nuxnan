<script setup lang="ts">
import { Icon } from '@iconify/vue'
import RadialProgress from "vue3-radial-progress"

interface Props {
  progress: any
  courseId: string | number
  isLoading?: boolean
}

const props = defineProps<Props>()

const percentage = computed(() => props.progress?.progress_percentage ?? 0)

const getProgressColor = (progress: number) => {
  if (progress >= 80) return '#10b981' // emerald-500
  if (progress >= 50) return '#3b82f6' // blue-500
  if (progress >= 20) return '#f59e0b' // amber-500
  return '#f43f5e' // rose-500
}

const getProgressTextColor = (progress: number) => {
  if (progress >= 80) return 'text-emerald-500'
  if (progress >= 50) return 'text-blue-500'
  if (progress >= 20) return 'text-amber-500'
  return 'text-rose-500'
}
</script>

<template>
  <div class="rounded-2xl border border-gray-100 bg-white shadow-xl dark:border-vikinger-dark-100 dark:bg-vikinger-dark-200 overflow-hidden">
    <!-- Header -->
    <div class="px-5 py-4 border-b border-gray-100 dark:border-vikinger-dark-100 flex items-center justify-between bg-gray-50/50 dark:bg-vikinger-dark-100/30">
      <h3 class="font-black text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 flex items-center gap-2">
        <Icon icon="fluent:status-24-filled" class="w-4 h-4 text-emerald-500" />
        ความก้าวหน้าโดยรวม
      </h3>
      <span v-if="progress" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 text-[10px] font-bold">
        {{ progress.status_label }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-6 flex flex-col items-center">
      <div v-if="isLoading" class="w-32 h-32 rounded-full border-4 border-gray-100 border-t-emerald-500 animate-spin" />
      
      <template v-else>
        <!-- Radial Progress -->
        <div class="relative group cursor-pointer transition-transform hover:scale-105 duration-500">
          <RadialProgress
            :completed-steps="percentage"
            :total-steps="100"
            :diameter="140"
            :stroke-width="12"
            :inner-stroke-width="12"
            :start-color="getProgressColor(percentage)"
            :stop-color="getProgressColor(percentage)"
            inner-stroke-color="#e2e8f0"
            class="dark:inner-stroke-vikinger-dark-100"
          >
            <div class="flex flex-col items-center">
              <span class="text-3xl font-black font-audiowide" :class="getProgressTextColor(percentage)">
                {{ percentage }}<span class="text-sm">%</span>
              </span>
              <span class="text-[9px] font-black uppercase tracking-widest text-gray-400">Complete</span>
            </div>
          </RadialProgress>
        </div>

        <div class="mt-6 w-full space-y-4">
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
      </template>
    </div>
  </div>
</template>


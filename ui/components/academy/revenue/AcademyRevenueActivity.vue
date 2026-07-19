<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  academyId: number
}

const props = defineProps<Props>()

const { activities, isLoading, fetchRevenueActivity } = useAcademyRevenue(props.academyId)

onMounted(async () => {
  await fetchRevenueActivity()
})
</script>

<template>
  <div class="space-y-4">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white">ประวัติการจัดการรายได้</h3>

    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 5" :key="i" class="h-12 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-700" />
    </div>

    <div v-else-if="activities.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
      ยังไม่มีประวัติการจัดการ
    </div>

    <div v-else class="relative space-y-0">
      <div class="absolute left-6 top-2 h-full w-px bg-gray-200 dark:bg-gray-700" />

      <div v-for="activity in activities" :key="activity.id" class="relative flex gap-4 pb-6">
        <div class="z-10 mt-1 h-4 w-4 rounded-full border-2 border-indigo-500 bg-white dark:bg-gray-900" />
        <div class="flex-1 rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <p class="text-sm text-gray-900 dark:text-white">{{ activity.description }}</p>
          <div class="mt-1 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
            <span>{{ new Date(activity.created_at).toLocaleString('th-TH') }}</span>
            <span v-if="activity.amount !== null" class="font-medium text-indigo-600 dark:text-indigo-400">
              {{ activity.amount.toLocaleString() }} {{ activity.amount_type === 'cash' ? 'บาท' : 'แต้ม' }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

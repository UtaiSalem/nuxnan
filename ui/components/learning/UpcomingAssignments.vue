<template>
  <div class="upcoming-assignments bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <Icon icon="fluent:clipboard-task-24-filled" class="w-5 h-5 text-orange-500" />
        การบ้านที่ต้องส่ง
      </h2>
      <span class="text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 px-3 py-1 rounded-full">
        {{ assignments.length }} รายการ
      </span>
    </div>

    <div v-if="assignments.length > 0" class="space-y-3">
      <div 
        v-for="assignment in assignments" 
        :key="assignment.id"
        class="assignment-item flex items-start gap-4 p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border border-gray-100 dark:border-gray-700"
      >
        <div class="w-12 h-12 rounded-xl overflow-hidden flex-shrink-0">
          <div class="w-full h-full bg-gradient-to-br from-orange-400 to-amber-500 flex items-center justify-center">
            <Icon icon="fluent:document-text-24-filled" class="w-6 h-6 text-white" />
          </div>
        </div>
        
        <div class="flex-grow min-w-0">
          <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-1">
            {{ assignment.title }}
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
            {{ assignment.course }}
          </p>
          <div class="flex items-center gap-3">
            <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
              <Icon icon="fluent:calendar-clock-24-regular" class="w-4 h-4" />
              <span>กำหนดส่ง: {{ formatDate(assignment.due_date) }}</span>
            </div>
            <div class="flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400 font-medium">
              <Icon icon="fluent:star-24-filled" class="w-4 h-4" />
              <span>{{ assignment.points }} คะแนน</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
      <Icon icon="fluent:checkmark-circle-24-regular" class="w-12 h-12 mx-auto mb-3" />
      <p>ไม่มีการบ้านที่ต้องส่ง</p>
      <p class="text-sm mt-1">เยี่ยม! คุณทำการบ้านครบแล้ว</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Assignment {
  id: number
  title: string
  course: string
  due_date: string
  points: number
  status: 'pending' | 'submitted' | 'graded'
}

interface Props {
  assignments: Assignment[]
}

defineProps<Props>()

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('th-TH', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
.assignment-item {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}
</style>

<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  assignment: any
}

const props = defineProps<Props>()

const statusConfig = computed(() => {
  const status = props.assignment.status || 'not_started'
  
  const configs = {
    not_started: {
      icon: 'fluent:circle-24-regular',
      text: 'ยังไม่เริ่มทำ',
      color: 'text-gray-600 dark:text-gray-400',
      bgColor: 'bg-gray-100 dark:bg-gray-700',
      borderColor: 'border-gray-300 dark:border-gray-600'
    },
    in_progress: {
      icon: 'fluent:clock-24-filled',
      text: 'กำลังทำ',
      color: 'text-blue-600 dark:text-blue-400',
      bgColor: 'bg-blue-100 dark:bg-blue-900/30',
      borderColor: 'border-blue-300 dark:border-blue-700'
    },
    submitted: {
      icon: 'fluent:checkmark-circle-24-filled',
      text: 'ส่งแล้ว',
      color: 'text-green-600 dark:text-green-400',
      bgColor: 'bg-green-100 dark:bg-green-900/30',
      borderColor: 'border-green-300 dark:border-green-700'
    },
    graded: {
      icon: 'fluent:star-24-filled',
      text: 'ตรวจแล้ว',
      color: 'text-amber-600 dark:text-amber-400',
      bgColor: 'bg-amber-100 dark:bg-amber-900/30',
      borderColor: 'border-amber-300 dark:border-amber-700'
    }
  }
  
  return configs[status] || configs.not_started
})

const ctaText = computed(() => {
  const status = props.assignment.status || 'not_started'
  return {
    not_started: 'เริ่มทำ',
    in_progress: 'ทำต่อ',
    submitted: 'ดูงานที่ส่ง',
    graded: 'ดูผลการตรวจ'
  }[status] || 'เริ่มทำ'
})
</script>

<template>
  <div 
    class="p-4 rounded-xl border-2 transition-all duration-300 hover:shadow-lg hover:scale-[1.02] cursor-pointer"
    :class="[statusConfig.bgColor, statusConfig.borderColor]"
  >
    <div class="flex items-start justify-between gap-4">
      <div class="flex-1">
        <!-- Title -->
        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">
          {{ assignment.title }}
        </h4>

        <!-- Description -->
        <p v-if="assignment.description" class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
          {{ assignment.description }}
        </p>

        <!-- Meta Info -->
        <div class="flex items-center gap-4 text-xs text-gray-600 dark:text-gray-400 mb-3">
          <!-- Due Date -->
          <div v-if="assignment.due_date" class="flex items-center gap-1">
            <Icon icon="fluent:calendar-24-regular" class="w-4 h-4" />
            <span>ครบกำหนด: {{ assignment.due_date }}</span>
          </div>

          <!-- Status -->
          <div class="flex items-center gap-1" :class="statusConfig.color">
            <Icon :icon="statusConfig.icon" class="w-4 h-4" />
            <span class="font-medium">{{ statusConfig.text }}</span>
          </div>

          <!-- Score (if graded) -->
          <div v-if="assignment.score !== undefined" class="flex items-center gap-1 text-amber-600 dark:text-amber-400">
            <Icon icon="fluent:trophy-24-filled" class="w-4 h-4" />
            <span class="font-bold">{{ assignment.score }}/{{ assignment.max_score || 100 }} คะแนน</span>
          </div>
        </div>

        <!-- CTA Button -->
        <button
          class="px-4 py-2 rounded-lg font-medium text-sm transition-all duration-300 hover:scale-105"
          :class="[
            assignment.status === 'graded'
              ? 'bg-amber-500 text-white hover:bg-amber-600'
              : 'bg-green-500 text-white hover:bg-green-600'
          ]"
        >
          {{ ctaText }}
          <Icon icon="fluent:arrow-right-24-regular" class="w-4 h-4 inline ml-1" />
        </button>
      </div>

      <!-- Icon -->
      <div class="flex-shrink-0">
        <Icon 
          icon="fluent:clipboard-task-24-filled" 
          class="w-12 h-12"
          :class="statusConfig.color"
        />
      </div>
    </div>
  </div>
</template>

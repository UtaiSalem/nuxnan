<script setup lang="ts">
import { Icon } from '@iconify/vue'

const props = defineProps<{
  show: boolean
  missingSources: {
    quiz_ids?: number[]
    course_assignment_ids?: number[]
    lesson_assignment_ids?: number[]
  }
  courseId: string | number
}>()

const emit = defineEmits(['close'])

const hasMissing = computed(() => {
  return (props.missingSources.quiz_ids?.length || 0) > 0 ||
         (props.missingSources.course_assignment_ids?.length || 0) > 0 ||
         (props.missingSources.lesson_assignment_ids?.length || 0) > 0
})
</script>

<template>
  <DialogModal :show="show" @close="emit('close')">
    <template #title>
      แหล่งคะแนนที่ยังขาดหาย
    </template>

    <template #content>
      <div v-if="!hasMissing" class="py-8 text-center text-gray-500">
        <Icon icon="heroicons:check-circle" class="w-12 h-12 mx-auto text-green-500 mb-2" />
        <p>นักเรียนทำกิจกรรมครบถ้วนแล้ว</p>
      </div>

      <div v-else class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          นักเรียนคนนี้ยังไม่ได้ทำกิจกรรมในแหล่งข้อมูลต่อไปนี้:
        </p>

        <!-- Quizzes -->
        <div v-if="missingSources.quiz_ids?.length" class="space-y-2">
          <h4 class="text-sm font-semibold flex items-center gap-2 text-gray-900 dark:text-white">
            <Icon icon="healthicons:i-exam-qualification-outline" class="w-4 h-4 text-primary-500" />
            แบบทดสอบ ({{ missingSources.quiz_ids.length }})
          </h4>
          <ul class="text-xs list-disc list-inside text-gray-600 dark:text-gray-400 pl-2">
            <li v-for="id in missingSources.quiz_ids" :key="`q-${id}`">
              ID: {{ id }}
            </li>
          </ul>
        </div>

        <!-- Assignments -->
        <div v-if="missingSources.course_assignment_ids?.length" class="space-y-2">
          <h4 class="text-sm font-semibold flex items-center gap-2 text-gray-900 dark:text-white">
            <Icon icon="material-symbols:assignment-add-outline" class="w-4 h-4 text-primary-500" />
            ภาระงานในรายวิชา ({{ missingSources.course_assignment_ids.length }})
          </h4>
          <ul class="text-xs list-disc list-inside text-gray-600 dark:text-gray-400 pl-2">
            <li v-for="id in missingSources.course_assignment_ids" :key="`ca-${id}`">
              ID: {{ id }}
            </li>
          </ul>
        </div>

        <!-- Lesson Assignments -->
        <div v-if="missingSources.lesson_assignment_ids?.length" class="space-y-2">
          <h4 class="text-sm font-semibold flex items-center gap-2 text-gray-900 dark:text-white">
            <Icon icon="icon-park-outline:view-grid-detail" class="w-4 h-4 text-primary-500" />
            ภาระงานในบทเรียน ({{ missingSources.lesson_assignment_ids.length }})
          </h4>
          <ul class="text-xs list-disc list-inside text-gray-600 dark:text-gray-400 pl-2">
            <li v-for="id in missingSources.lesson_assignment_ids" :key="`la-${id}`">
              ID: {{ id }}
            </li>
          </ul>
        </div>
      </div>
    </template>

    <template #footer>
      <SecondaryButton @click="emit('close')">
        ปิด
      </SecondaryButton>
    </template>
  </DialogModal>
</template>

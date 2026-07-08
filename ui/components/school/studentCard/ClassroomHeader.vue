<script setup lang="ts">
import type { ClassroomSummary } from '~/types/studentCardRequest'

defineProps<{ classroom: ClassroomSummary | null }>()
</script>

<template>
  <div v-if="classroom" class="rounded-xl border bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
    <div class="flex flex-wrap items-center gap-4">
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-baseline gap-3">
          <h3 class="text-lg font-bold dark:text-white">{{ classroom.name }}</h3>
          <span v-if="classroom.academic_year" class="text-sm text-gray-500">ปีการศึกษา {{ classroom.academic_year.name }}</span>
        </div>
        <div class="mt-2 flex flex-wrap items-center gap-4 text-sm">
          <div v-if="classroom.homeroom_teacher" class="flex items-center gap-2">
            <img v-if="classroom.homeroom_teacher.profile_image_url" :src="classroom.homeroom_teacher.profile_image_url" alt="" class="h-8 w-8 rounded-full object-cover">
            <div v-else class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-xs dark:bg-gray-700">ครู</div>
            <span class="dark:text-gray-200">ครูประจำชั้น: <strong>{{ classroom.homeroom_teacher.name }}</strong></span>
          </div>
          <span v-else class="text-amber-600">ยังไม่ได้กำหนดครูประจำชั้น</span>
          <span v-if="classroom.student_count !== null" class="text-gray-500">นักเรียน {{ classroom.student_count }} คน</span>
        </div>
      </div>
    </div>
  </div>
</template>

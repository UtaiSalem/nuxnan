<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseSidebarInstructor from './CourseSidebarInstructor.vue'
import CourseSidebarGroup from './CourseSidebarGroup.vue'
import CourseHeroStats from './CourseHeroStats.vue'
import CourseLessonsMenu from './CourseLessonsMenu.vue'

const props = defineProps({
  course: { type: Object, required: true },
  courseMemberOfAuth: { type: Object, default: null },
  isAdmin: { type: Boolean, default: false },
})

const owner = computed(() => props.course?.user || props.course?.owner)
const memberGroup = computed(() => props.courseMemberOfAuth?.group)
const courseId = computed(() => props.course?.id)
</script>

<template>
  <div class="space-y-6">
    <!-- Lessons Menu -->
    <CourseLessonsMenu v-if="courseId" :course-id="courseId" :is-admin="isAdmin" />

    <!-- Group Card -->
    <CourseSidebarGroup v-if="memberGroup" :group="memberGroup" />

    <!-- Help/Quick Links -->
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-vikinger-dark-100 dark:bg-vikinger-dark-200">
      <h4 class="mb-2 text-sm font-black text-gray-900 dark:text-white">ต้องการความช่วยเหลือ?</h4>
      <p class="text-xs font-bold text-gray-500 dark:text-gray-400">
        หากมีข้อสงสัยเกี่ยวกับเนื้อหาหรือการบ้าน สามารถสอบถามผู้สอนได้โดยตรง
      </p>
      <button class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-gray-900 py-2.5 text-xs font-bold text-white hover:bg-black dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200 transition-colors">
        <Icon icon="fluent:question-circle-24-filled" class="w-4 h-4" />
        <span>ศูนย์ช่วยเหลือ</span>
      </button>
    </div>
  </div>
</template>

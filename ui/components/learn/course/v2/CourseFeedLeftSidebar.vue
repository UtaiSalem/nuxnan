<script setup lang="ts">
import { Icon } from '@iconify/vue'

const props = defineProps<{
  course: any
  isCourseAdmin?: boolean
}>()

const courseStats = computed(() => {
  if (!props.course) return null
  return {
    members: props.course.members_count || props.course.course_members_count || 0,
    posts: props.course.posts_count || 0,
    materials: props.course.materials_count || 0,
    assignments: props.course.assignments_count || 0
  }
})

const courseId = computed(() => props.course?.id)
</script>

<template>
  <div class="space-y-4">
    <!-- Course Quick Stats Widget -->
    <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
      <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
        <Icon icon="fluent:info-24-regular" class="w-4 h-4" />
        ข้อมูลรายวิชา
      </h3>
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
            สมาชิก
          </span>
          <span class="text-sm font-medium text-gray-900 dark:text-white">
            {{ courseStats?.members || 0 }} คน
          </span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <Icon icon="fluent:chat-24-regular" class="w-4 h-4" />
            โพสต์
          </span>
          <span class="text-sm font-medium text-gray-900 dark:text-white">
            {{ courseStats?.posts || 0 }} โพสต์
          </span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <Icon icon="fluent:document-24-regular" class="w-4 h-4" />
            เอกสาร
          </span>
          <span class="text-sm font-medium text-gray-900 dark:text-white">
            {{ courseStats?.materials || 0 }} ไฟล์
          </span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
            <Icon icon="fluent:task-list-24-regular" class="w-4 h-4" />
            ภารกิจ
          </span>
          <span class="text-sm font-medium text-gray-900 dark:text-white">
            {{ courseStats?.assignments || 0 }} งาน
          </span>
        </div>
      </div>
    </div>

    <!-- Quick Links Widget -->
    <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
      <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
        <Icon icon="fluent:link-24-regular" class="w-4 h-4" />
        ลิงก์ด่วน
      </h3>
      <div class="space-y-1">
        <NuxtLink
          :to="`/Learn/Courses/${courseId}/lessons`"
          class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors"
        >
          <Icon icon="fluent:document-24-regular" class="w-4 h-4 text-blue-500" />
          เอกสารประกอบการเรียน
        </NuxtLink>
        <NuxtLink
          :to="`/Learn/Courses/${courseId}/assignments`"
          class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors"
        >
          <Icon icon="fluent:task-list-24-regular" class="w-4 h-4 text-green-500" />
          ภารกิจ / งานที่มอบหมาย
        </NuxtLink>
        <NuxtLink
          :to="`/Learn/Courses/${courseId}/members`"
          class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors"
        >
          <Icon icon="fluent:people-24-regular" class="w-4 h-4 text-purple-500" />
          สมาชิกในรายวิชา
        </NuxtLink>
        <NuxtLink
          :to="`/Learn/Courses/${courseId}/groups`"
          class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors"
        >
          <Icon icon="fluent:people-team-24-regular" class="w-4 h-4 text-orange-500" />
          กลุ่มย่อย
        </NuxtLink>
      </div>
    </div>

    <!-- Admin Section Widget -->
    <div v-if="isCourseAdmin" class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 shadow-sm text-white">
      <h3 class="text-sm font-semibold mb-3 flex items-center gap-2">
        <Icon icon="fluent:shield-24-regular" class="w-4 h-4" />
        จัดการรายวิชา
      </h3>
      <div class="space-y-1">
        <NuxtLink
          :to="`/Learn/Courses/${courseId}/settings`"
          class="flex items-center gap-2 px-3 py-2 text-sm bg-white/10 hover:bg-white/20 rounded-lg transition-colors"
        >
          <Icon icon="fluent:settings-24-regular" class="w-4 h-4" />
          ตั้งค่ารายวิชา
        </NuxtLink>
        <NuxtLink
          :to="`/Learn/Courses/${courseId}/gradebook`"
          class="flex items-center gap-2 px-3 py-2 text-sm bg-white/10 hover:bg-white/20 rounded-lg transition-colors"
        >
          <Icon icon="fluent:data-trending-24-regular" class="w-4 h-4" />
          สรุปคะแนน
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseFeedsList from '~/components/learn/course/CourseFeedsList.vue'

definePageMeta({})

const injectedCourse = inject<Ref<any>>('course')
const injectedIsCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')

const course = computed(() => injectedCourse?.value)
const isCourseAdmin = computed(() => injectedIsCourseAdmin?.value)

useHead({
  title: computed(() => course.value?.name ? `ฟีด - ${course.value.name}` : 'ฟีดรายวิชา')
})
</script>

<template>
  <div class="space-y-6">
    <CourseFeedsList
      v-if="course?.id"
      :course-id="course.id"
      :is-course-admin="isCourseAdmin"
    />

    <div v-else class="bg-white dark:bg-vikinger-dark-200 rounded-xl px-0 py-8 sm:px-8 text-center border border-gray-100 dark:border-vikinger-dark-100 shadow-sm">
      <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 animate-spin mx-auto text-blue-500 mb-4" />
      <p class="text-gray-500 dark:text-gray-400">กำลังโหลดข้อมูลรายวิชา...</p>
    </div>

  </div>
</template>

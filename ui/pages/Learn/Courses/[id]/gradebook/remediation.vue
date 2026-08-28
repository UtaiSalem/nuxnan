<script setup lang="ts">
import { Icon } from '@iconify/vue'

const route = useRoute()
const courseId = computed(() => route.params.id as string)
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-5xl mx-auto px-0 sm:px-4 sm:px-6 lg:px-8 py-6">
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row gap-4 sm:items-start">
          <div class="w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-300 flex items-center justify-center shrink-0">
            <Icon icon="heroicons:pause-circle" class="w-7 h-7" />
          </div>

          <div class="flex-1">
            <p class="text-sm font-semibold text-orange-600 dark:text-orange-300 mb-2">
              ปิดใช้งานชั่วคราว
            </p>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
              ฟีเจอร์รอบแก้ตัวถูกพักไว้ก่อน
            </h1>
            <p class="mt-3 text-gray-600 dark:text-gray-300 leading-7">
              ตอนนี้ให้ใช้กระบวนการเรียนปกติสำหรับการแก้ตัว ได้แก่การส่งงานและการทำแบบทดสอบในรายวิชา ระบบสิทธิ์สอบ/สอบแก้ที่มีอยู่จะยังดูแลการปลดล็อกสิทธิ์สอบใหม่ตามเงื่อนไขของรายวิชา
            </p>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
              <NuxtLink
                :to="`/Learn/Courses/${courseId}/gradebook/eligibility`"
                class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg bg-primary-600 text-white font-semibold hover:bg-primary-700 transition-colors"
              >
                <Icon icon="heroicons:shield-check" class="w-5 h-5" />
                สิทธิ์สอบ
              </NuxtLink>
              <NuxtLink
                :to="`/Learn/Courses/${courseId}/assignments`"
                class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
              >
                <Icon icon="material-symbols:assignment-add-outline" class="w-5 h-5" />
                ภาระงาน
              </NuxtLink>
              <NuxtLink
                :to="`/Learn/Courses/${courseId}/quizzes`"
                class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
              >
                <Icon icon="healthicons:i-exam-qualification-outline" class="w-5 h-5" />
                แบบทดสอบ
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

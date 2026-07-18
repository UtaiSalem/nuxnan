<script setup lang="ts">
import type { PublicCourse } from '~/composables/usePublicCourses'
defineProps<{ course: PublicCourse }>()
</script>
<template>
  <article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-slate-700 dark:bg-slate-800">
    <NuxtLink :to="`/courses/${course.slug}`" class="block">
      <div class="h-44 overflow-hidden bg-slate-100 dark:bg-slate-700"><img v-if="course.cover" :src="course.cover" :alt="course.name" class="h-full w-full object-cover transition group-hover:scale-105"><div v-else class="flex h-full items-center justify-center text-4xl text-slate-300">📚</div></div>
      <div class="space-y-3 p-5"><div class="flex items-center gap-2 text-xs text-slate-500"><img v-if="course.academy?.logo" :src="course.academy.logo" class="h-6 w-6 rounded-full object-cover"><span>{{ course.academy?.name || 'Academy' }}</span></div><h2 class="line-clamp-2 text-lg font-bold text-slate-900 dark:text-white">{{ course.title || course.name }}</h2><p class="text-sm text-slate-500">{{ course.teacher_display_name || 'Teacher' }}</p><p class="line-clamp-2 min-h-10 text-sm text-slate-600 dark:text-slate-300">{{ course.description || 'Support this course and help learners grow.' }}</p></div>
    </NuxtLink>
    <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4 text-xs text-slate-500 dark:border-slate-700"><span>💎 {{ course.total_donated_points }}</span><span>👥 {{ course.total_donors }}</span><span>🎯 {{ course.active_campaign_count }}</span></div>
    <NuxtLink :to="`/courses/${course.slug}`" class="mx-5 mb-5 block rounded-xl bg-primary-600 px-4 py-2.5 text-center text-sm font-bold text-white hover:bg-primary-700">สนับสนุน / Support</NuxtLink>
  </article>
</template>

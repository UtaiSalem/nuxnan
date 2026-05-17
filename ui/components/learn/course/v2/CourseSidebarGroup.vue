<script setup lang="ts">
import { Icon } from '@iconify/vue'

const props = defineProps({
  group: { type: Object, required: true },
})
</script>

<template>
  <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <h3 class="mb-4 text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">
      กลุ่มที่คุณสังกัด
    </h3>
    
    <div class="flex items-center gap-4">
      <div class="h-14 w-14 flex items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
        <Icon icon="heroicons-outline:user-group" class="w-8 h-8" />
      </div>
      
      <div class="min-w-0 flex-1">
        <h4 class="truncate text-base font-black text-gray-900 dark:text-white">
          {{ group?.name || `กลุ่ม ${group?.id}` }}
        </h4>
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400">
          {{ group?.members_count || 0 }} สมาชิก
        </p>
      </div>
    </div>

    <NuxtLink 
      :to="`/Learn/Courses/${group?.course_id}/groups`"
      class="mt-5 w-full flex items-center justify-center gap-2 rounded-xl bg-indigo-50 py-2.5 text-xs font-bold text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-400 dark:hover:bg-indigo-900/40 transition-colors"
    >
      <span>ดูรายละเอียดกลุ่ม</span>
      <Icon icon="fluent:arrow-right-24-filled" class="w-4 h-4" />
    </NuxtLink>
  </div>
</template>

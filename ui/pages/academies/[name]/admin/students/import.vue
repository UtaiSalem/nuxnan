<template>
  <div class="max-w-5xl mx-auto space-y-6 px-4 sm:px-0">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
          <NuxtLink :to="`/academies/${academyName}/admin`" class="hover:text-gray-900 dark:hover:text-white transition-colors">Admin</NuxtLink>
          <Icon icon="fluent:chevron-right-24-regular" class="w-4 h-4" />
          <NuxtLink :to="`/academies/${academyName}/admin/students`" class="hover:text-gray-900 dark:hover:text-white transition-colors">ทะเบียนนักเรียน</NuxtLink>
          <Icon icon="fluent:chevron-right-24-regular" class="w-4 h-4" />
          <span class="text-gray-900 dark:text-gray-200 font-medium">นำเข้าข้อมูลจำนวนมาก</span>
        </div>
      </div>
      <NuxtLink :to="`/academies/${academyName}/admin/students`" class="btn-ghost flex items-center gap-2">
        <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
        ปิด
      </NuxtLink>
    </div>

    <!-- Wizard Component -->
    <ImportWizard />
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import ImportWizard from '../../../../components/academy/student-import/ImportWizard.vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth'],
})

const route = useRoute()
const academyName = computed(() => route.params.name as string)
</script>

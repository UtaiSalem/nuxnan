<script setup lang="ts">
import { Icon } from '@iconify/vue'
import IntakeWizard from '~/components/academy/student-intake/IntakeWizard.vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const academyName = computed(() => route.params.name as string)
</script>

<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400 text-sm mb-1">
          <NuxtLink :to="`/academies/${academyName}/admin/students`" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
            ทะเบียนนักเรียน
          </NuxtLink>
          <Icon icon="fluent:chevron-right-16-regular" class="w-4 h-4" />
          <span class="text-gray-900 dark:text-white font-medium">รับนักเรียนใหม่</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
          <Icon icon="fluent:person-add-24-filled" class="text-primary-600" />
          รับนักเรียนใหม่
        </h1>
      </div>
    </div>

    <!-- Wizard Component -->
    <IntakeWizard :academy-name="academyName" />
  </div>
</template>

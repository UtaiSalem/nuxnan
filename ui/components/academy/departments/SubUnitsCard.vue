<template>
  <div class="relative flex flex-col bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 rounded-2xl">
    <div class="flex items-center justify-between p-4 sm:p-6 pb-0 sm:pb-0 mb-4">
      <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-0">หน่วยงานย่อยในฝ่าย</h4>
      <span class="flex-shrink-0 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ children?.length || 0 }} หน่วย</span>
    </div>
    
    <div class="px-0 sm:px-2 pb-4 sm:pb-6">
      <div v-if="!children?.length" class="flex flex-col items-center justify-center py-8 text-gray-500">
        <Icon icon="fluent:organization-24-regular" class="mb-2 h-10 w-10 opacity-50" />
        <p>ฝ่ายนี้ยังไม่มีงาน/กลุ่มงานย่อย</p>
      </div>
      <div v-else class="divide-y divide-gray-100 dark:divide-gray-700 px-4">
        <NuxtLink v-for="child in children" :key="child.id" :to="`/academies/${academyName}/groups/${child.id}`" class="flex min-h-[44px] items-center gap-3 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 -mx-4 px-4 transition-colors">
          <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400">
            <Icon icon="fluent:organization-24-regular" class="h-5 w-5" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="break-words font-medium text-gray-900 dark:text-white">{{ child.name }}</div>
            <div class="text-xs text-gray-500">{{ formatDepartmentType(child.type) }}</div>
          </div>
          <span class="flex-shrink-0 whitespace-nowrap text-sm text-gray-500">{{ child.members_count || 0 }} คน</span>
          <Icon icon="fluent:chevron-right-24-regular" class="h-5 w-5 flex-shrink-0 text-gray-400" />
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { getAcademyGroupTypeMeta } from '~/constants/academyGroupTypes'

defineProps<{
  children: any[]
  academyName: string
}>()

const formatDepartmentType = (type: string | null | undefined) =>
  getAcademyGroupTypeMeta(type).label
</script>

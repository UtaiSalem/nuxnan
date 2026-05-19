<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  courseName: string
}

const props = defineProps<Props>()
const route = useRoute()

const tabs = [
  { name: 'สมุดคะแนน', icon: 'fluent:text-grammar-checkmark-24-filled', path: 'gradebook', exact: true },
  { name: 'สิทธิ์สอบ', icon: 'heroicons:shield-check', path: 'gradebook/eligibility' },
  { name: 'จบวิชา/เกรด', icon: 'heroicons:academic-cap', path: 'gradebook/completion' },
  { name: 'อุทธรณ์', icon: 'heroicons:chat-bubble-left-right', path: 'gradebook/appeals' },
  { name: 'แก้ตัว', icon: 'heroicons:arrow-path-rounded-square', path: 'gradebook/remediation' },
  { name: 'ใบประกาศ', icon: 'heroicons:document-check', path: 'gradebook/certificates' },
]

const isActive = (tab: typeof tabs[0]) => {
  const current = route.path
  const href = `/courses/${props.courseName}/${tab.path}`
  if (tab.exact) {
    return current === href || current === href + '/'
  }
  return current.startsWith(href)
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex overflow-x-auto gap-1 py-2 scrollbar-hide">
        <NuxtLink
          v-for="tab in tabs"
          :key="tab.path"
          :to="`/courses/${courseName}/${tab.path}`"
          class="flex-shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap"
          :class="isActive(tab)
            ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300'
            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
        >
          <Icon :icon="tab.icon" class="w-4 h-4" />
          {{ tab.name }}
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>

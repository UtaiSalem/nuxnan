<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

interface Props {
  group: any
  academy: any
  stats: any
  typeMeta: any
}
const props = defineProps<Props>()

const createdDate = computed(() => {
  if (!props.stats?.created_at && !props.group?.created_at) return '-'
  const dateStr = props.stats?.created_at || props.group?.created_at
  return new Date(dateStr).toLocaleDateString('th-TH', {
    year: 'numeric', month: 'long', day: 'numeric',
  })
})
</script>

<template>
  <div class="space-y-4">
    <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm p-5 md:p-6 border border-gray-200 dark:border-gray-700">
      <h3 class="font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 text-sm md:text-base border-b border-gray-100 dark:border-gray-700 pb-3">
        <Icon icon="heroicons:information-circle" class="w-5 h-5 text-vikinger-purple" />
        เกี่ยวกับส่วนงาน
      </h3>

      <p v-if="group.description" class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mb-6 font-medium">
        {{ group.description }}
      </p>
      <p v-else class="text-sm text-gray-400 italic mb-6 font-medium">ยังไม่มีคำอธิบายหรือประวัติเกี่ยวกับส่วนงานย่อยนี้</p>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm border-t border-gray-100 dark:border-gray-700 pt-5">
        <div class="flex items-start gap-3">
          <span class="w-9 h-9 rounded-xl bg-gray-50 dark:bg-vikinger-dark-100 text-gray-400 dark:text-gray-400 flex items-center justify-center flex-shrink-0 mt-0.5 shadow-inner border border-gray-100 dark:border-gray-800">
            <Icon :icon="typeMeta?.icon || 'heroicons:squares-2x2'" class="w-5 h-5" />
          </span>
          <div>
            <div class="text-xs text-gray-550 dark:text-gray-400 font-bold">ประเภทส่วนงาน</div>
            <div class="font-bold text-gray-900 dark:text-white mt-0.5">{{ typeMeta?.label || 'ไม่ระบุ' }}</div>
          </div>
        </div>

        <div class="flex items-start gap-3">
          <span class="w-9 h-9 rounded-xl bg-gray-50 dark:bg-vikinger-dark-100 text-gray-400 dark:text-gray-400 flex items-center justify-center flex-shrink-0 mt-0.5 shadow-inner border border-gray-100 dark:border-gray-800">
            <Icon icon="heroicons:building-library" class="w-5 h-5" />
          </span>
          <div>
            <div class="text-xs text-gray-550 dark:text-gray-400 font-bold">สังกัดโรงเรียน</div>
            <NuxtLink
              :to="`/academies/${academy?.name}`"
              class="font-bold text-vikinger-purple hover:underline mt-0.5 block"
            >
              {{ academy?.name }}
            </NuxtLink>
          </div>
        </div>

        <div class="flex items-start gap-3">
          <span class="w-9 h-9 rounded-xl bg-gray-50 dark:bg-vikinger-dark-100 text-gray-400 dark:text-gray-400 flex items-center justify-center flex-shrink-0 mt-0.5 shadow-inner border border-gray-100 dark:border-gray-800">
            <Icon icon="heroicons:calendar" class="w-5 h-5" />
          </span>
          <div>
            <div class="text-xs text-gray-550 dark:text-gray-400 font-bold">เปิดใช้งานเมื่อ</div>
            <div class="font-bold text-gray-900 dark:text-white mt-0.5">{{ createdDate }}</div>
          </div>
        </div>

        <div class="flex items-start gap-3">
          <span class="w-9 h-9 rounded-xl bg-gray-50 dark:bg-vikinger-dark-100 text-gray-400 dark:text-gray-400 flex items-center justify-center flex-shrink-0 mt-0.5 shadow-inner border border-gray-100 dark:border-gray-800">
            <Icon icon="heroicons:chart-bar" class="w-5 h-5" />
          </span>
          <div>
            <div class="text-xs text-gray-550 dark:text-gray-400 font-bold">ผลงานกิจกรรมรวม</div>
            <div class="font-bold text-gray-900 dark:text-white mt-0.5">
              {{ stats?.posts_count ?? 0 }} โพสต์ในนามส่วนงาน
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

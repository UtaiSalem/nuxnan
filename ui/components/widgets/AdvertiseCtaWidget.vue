<script setup lang="ts">
import { Icon } from '@iconify/vue'

const props = defineProps<{
  scopeType: 'academy' | 'course'
  targetId: number | string
  targetName?: string
}>()

const query = computed(() => ({
  scope: props.scopeType,
  [`${props.scopeType}_id`]: props.targetId,
}))
</script>

<template>
  <section class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-4 shadow-sm dark:border-indigo-900/40 dark:from-indigo-950/40 dark:to-gray-800">
    <div class="flex items-start gap-3">
      <div class="rounded-xl bg-indigo-600 p-2 text-white"><Icon icon="solar:megaphone-bold-duotone" class="h-6 w-6" /></div>
      <div class="min-w-0 flex-1">
        <h3 class="font-bold text-gray-900 dark:text-white">ลงโฆษณาในหน้านี้</h3>
        <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">โปรโมตสินค้าหรือสนับสนุนการเรียนรู้{{ targetName ? `ใน ${targetName}` : '' }}</p>
        <NuxtLink :to="{ path: '/earn/advertise/create', query }" class="mt-3 inline-flex items-center rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">สร้างแคมเปญ</NuxtLink>
      </div>
    </div>
  </section>
</template>

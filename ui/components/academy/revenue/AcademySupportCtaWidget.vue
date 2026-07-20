<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  academyId: number
  academyName?: string
}

defineProps<Props>()
const emit = defineEmits<{
  donate: []
}>()
</script>

<template>
  <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-vikinger-dark-200">
    <div class="flex items-start gap-3">
      <div class="rounded-xl bg-violet-50 p-2 text-violet-500 dark:bg-violet-950/40 dark:text-violet-300">
        <Icon icon="fluent:heart-24-regular" class="h-5 w-5" />
      </div>
      <div>
        <h3 class="font-bold text-gray-900 dark:text-white">ร่วมสนับสนุนโรงเรียน</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">สนับสนุนแต้มหรือลงโฆษณาได้จากทุกหน้า</p>
      </div>
    </div>

    <div class="mt-4 flex flex-col gap-2">
      <button
        type="button"
        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-violet-700"
        @click="emit('donate')"
      >
        <Icon icon="fluent:heart-24-filled" class="h-4 w-4" />
        สนับสนุนโรงเรียน
      </button>
      <NuxtLink
        :to="{ path: '/earn/advertise/create', query: { scope: 'academy', academy_id: academyId } }"
        class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-violet-200 bg-white px-4 py-2.5 text-sm font-semibold text-violet-600 transition hover:bg-violet-50 dark:border-violet-900/40 dark:bg-transparent dark:text-violet-300 dark:hover:bg-violet-950/30"
      >
        <Icon icon="solar:megaphone-bold-duotone" class="h-4 w-4" />
        ลงแคมเปญโฆษณา
      </NuxtLink>
    </div>
  </div>
</template>

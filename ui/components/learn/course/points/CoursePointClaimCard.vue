<script setup lang="ts">
import { Icon } from '@iconify/vue'
import type { CoursePointCampaign } from '~/composables/useCoursePoints'

const props = defineProps<{ campaign: CoursePointCampaign; isClaiming: boolean }>()
const emit = defineEmits<{ claim: [id: number] }>()
</script>

<template>
  <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h3 class="font-bold text-gray-900 dark:text-white">{{ campaign.title }}</h3>
        <p v-if="campaign.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ campaign.description }}</p>
      </div>
      <span class="inline-flex shrink-0 items-center gap-1 rounded bg-gradient-vikinger px-2.5 py-1 text-sm font-bold text-white shadow-vikinger">
        <Icon icon="mdi:star" class="h-4 w-4" /> +{{ campaign.points_per_claim }} แต้ม
      </span>
    </div>
    <div class="mt-4 flex items-center justify-between gap-3">
      <span v-if="campaign.remaining !== null" class="text-xs text-gray-500 dark:text-gray-400">เหลือ {{ campaign.remaining }} สิทธิ์</span>
      <span v-else class="text-xs text-gray-500 dark:text-gray-400">ไม่จำกัดสิทธิ์</span>
      <button :disabled="!campaign.can_claim || isClaiming" class="min-h-[44px] sm:min-h-0 rounded-lg px-4 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60" :class="campaign.claimed_by_auth ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : campaign.can_claim ? 'bg-vikinger-purple text-white hover:opacity-90' : 'bg-gray-100 text-gray-500 dark:bg-gray-700'" @click="emit('claim', campaign.id)">
        <Icon v-if="isClaiming" icon="svg-spinners:ring-resize" class="mr-1 inline h-4 w-4" />
        {{ isClaiming ? 'กำลังรับ...' : campaign.claimed_by_auth ? 'รับแล้ว' : campaign.remaining === 0 ? 'โควตาเต็ม' : 'รับแต้ม' }}
      </button>
    </div>
  </article>
</template>

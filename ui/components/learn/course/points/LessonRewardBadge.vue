<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  reward: {
    enabled: boolean
    points_per_claim: number
    max_claims: number | null
    claimed_count: number
    remaining_claims: number | null
    status: 'active' | 'depleted' | 'paused' | 'ended'
    claimed_by_auth: boolean
    can_claim: boolean
  } | null
  size?: 'sm' | 'md'
}

const props = withDefaults(defineProps<Props>(), { size: 'sm' })

const badgeClass = computed(() => {
  if (!props.reward) return ''
  if (props.reward.claimed_by_auth) return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
  if (props.reward.status === 'depleted') return 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
  if (props.reward.status === 'active') return 'bg-gradient-vikinger text-white shadow-vikinger'
  return 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
})

const label = computed(() => {
  if (!props.reward?.enabled) return null
  if (props.reward.claimed_by_auth)   return `รับแล้ว +${props.reward.points_per_claim} แต้ม`
  if (props.reward.status === 'depleted') return 'โควต้าหมดแล้ว'
  if (props.reward.status !== 'active')   return null
  return `+${props.reward.points_per_claim} แต้ม`
})

const quotaText = computed(() => {
  if (!props.reward || props.reward.claimed_by_auth) return null
  if (props.reward.remaining_claims === null) return null        // ไม่จำกัด
  if (props.reward.status === 'depleted') return null
  return `เหลือ ${props.reward.remaining_claims} สิทธิ์`
})
</script>

<template>
  <div v-if="reward && label" class="flex items-center gap-1.5 flex-wrap">
    <!-- Main badge -->
    <span
      :class="[
        'inline-flex items-center gap-1 font-bold rounded',
        size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-sm',
        badgeClass,
      ]"
    >
      <Icon
        :icon="reward.claimed_by_auth ? 'mdi:check-circle' : 'mdi:star'"
        class="w-3 h-3"
      />
      {{ label }}
    </span>

    <!-- Quota remaining -->
    <span
      v-if="quotaText"
      class="text-xs text-slate-400 dark:text-slate-500"
    >
      {{ quotaText }}
    </span>
  </div>
</template>

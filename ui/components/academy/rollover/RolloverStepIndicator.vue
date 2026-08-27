<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed } from 'vue'

interface StepItem {
  label: string
  icon?: string
}

interface Props {
  current: number
  unlockedThrough?: number
  steps?: StepItem[]
}

const defaultSteps: StepItem[] = [
  { label: 'เลือกปี', icon: 'mdi:calendar' },
  { label: 'ตรวจห้อง', icon: 'mdi:home-group' },
  { label: 'จัดสรร', icon: 'mdi:account-multiple' },
  { label: 'ตรวจสรุป', icon: 'mdi:eye' },
  { label: 'ยืนยัน', icon: 'mdi:check-bold' },
  { label: 'เสร็จ', icon: 'mdi:party-popper' },
]

const props = withDefaults(defineProps<Props>(), {
  unlockedThrough: undefined,
})

const emit = defineEmits<{
  select: [step: number]
}>()

const resolvedSteps = computed(() => props.steps ?? defaultSteps)

const unlocked = computed(() => Math.max(props.current, props.unlockedThrough ?? props.current))

function stateOf(idx: number): 'completed' | 'current' | 'unlocked' | 'locked' {
  const step = idx + 1
  if (step < props.current) return 'completed'
  if (step === props.current) return 'current'
  if (step <= unlocked.value) return 'unlocked'
  return 'locked'
}

function selectStep(idx: number) {
  const step = idx + 1
  if (step === props.current || step > unlocked.value) return
  emit('select', step)
}
</script>

<template>
  <nav
    class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
    role="navigation"
    aria-label="ขั้นตอนการเลื่อนชั้น"
  >
    <ol class="hidden items-center gap-2 md:flex">
      <li v-for="(step, idx) in resolvedSteps" :key="`${idx}-${step.label}`" class="flex min-w-0 flex-1 items-center">
        <button class="min-h-[44px] sm:min-h-0"
          type="button"
          :disabled="stateOf(idx) === 'locked'"
          :aria-current="stateOf(idx) === 'current' ? 'step' : undefined"
          :aria-disabled="stateOf(idx) === 'locked' ? 'true' : 'false'"
          :class="[
            'group flex min-w-0 flex-col items-center gap-2 rounded-xl px-2 py-1 text-center transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/30',
            stateOf(idx) === 'locked' ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:opacity-85',
          ]"
          @click="selectStep(idx)"
        >
          <span
            :class="[
              'flex h-10 w-10 items-center justify-center rounded-full border-2 transition',
              stateOf(idx) === 'completed'
                ? 'border-emerald-500 bg-emerald-500 text-white'
                : stateOf(idx) === 'current'
                  ? 'border-primary-500 bg-primary-500 text-white ring-4 ring-primary-500/15'
                  : stateOf(idx) === 'unlocked'
                    ? 'border-gray-300 bg-white text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300'
                    : 'border-gray-200 bg-gray-100 text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-600',
            ]"
          >
            <Icon v-if="stateOf(idx) === 'completed'" icon="mdi:check" class="h-5 w-5" />
            <Icon v-else-if="step.icon" :icon="step.icon" class="h-5 w-5" />
            <span v-else>{{ idx + 1 }}</span>
          </span>
          <span
            :class="[
              'truncate text-xs font-medium',
              stateOf(idx) === 'current'
                ? 'text-primary-600 dark:text-primary-400'
                : stateOf(idx) === 'completed'
                  ? 'text-emerald-600 dark:text-emerald-400'
                  : 'text-gray-500 dark:text-gray-400',
            ]"
          >
            {{ idx + 1 }}. {{ step.label }}
          </span>
        </button>

        <span
          v-if="idx < resolvedSteps.length - 1"
          :class="[
            'mx-1 h-0.5 flex-1 rounded-full transition',
            idx + 1 < current ? 'bg-emerald-400' : 'bg-gray-200 dark:bg-gray-700',
          ]"
        />
      </li>
    </ol>

    <ol class="flex flex-col gap-2 md:hidden">
      <li v-for="(step, idx) in resolvedSteps" :key="`mobile-${idx}-${step.label}`">
        <button
          type="button"
          class="min-h-[44px] sm:min-h-0 flex w-full items-center gap-3 rounded-xl px-1 py-1 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/30"
          :disabled="stateOf(idx) === 'locked'"
          :aria-current="stateOf(idx) === 'current' ? 'step' : undefined"
          :aria-disabled="stateOf(idx) === 'locked' ? 'true' : 'false'"
          :class="stateOf(idx) === 'locked' ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:opacity-85'"
          @click="selectStep(idx)"
        >
          <span
            :class="[
              'flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 text-xs font-semibold transition',
              stateOf(idx) === 'completed'
                ? 'border-emerald-500 bg-emerald-500 text-white'
                : stateOf(idx) === 'current'
                  ? 'border-primary-500 bg-primary-500 text-white'
                  : stateOf(idx) === 'unlocked'
                    ? 'border-gray-300 bg-white text-gray-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300'
                    : 'border-gray-200 bg-gray-100 text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-600',
            ]"
          >
            <Icon v-if="stateOf(idx) === 'completed'" icon="mdi:check" class="h-4 w-4" />
            <span v-else>{{ idx + 1 }}</span>
          </span>
          <span
            :class="[
              'text-sm',
              stateOf(idx) === 'current'
                ? 'font-semibold text-primary-600 dark:text-primary-400'
                : stateOf(idx) === 'completed'
                  ? 'font-medium text-emerald-600 dark:text-emerald-400'
                  : 'text-gray-600 dark:text-gray-300',
            ]"
          >
            {{ step.label }}
          </span>
        </button>
      </li>
    </ol>
  </nav>
</template>

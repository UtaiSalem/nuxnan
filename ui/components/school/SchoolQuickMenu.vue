<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAcademyNavigation } from '~/composables/useAcademyNavigation'
import { useAcademyRole } from '~/composables/useAcademyRole'

interface Props {
  academy: {
    id: number
    name: string
    slug?: string
    authIsAcademyAdmin?: boolean
  }
  isPending?: boolean
  canRequestJoin?: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{ navigate: [tab: string]; join: [] }>()

const academyId = computed(() => props.academy.id ?? null)
const role = useAcademyRole(academyId)
const { visibleDestinations, primaryCta, pendingHint } = useAcademyNavigation(
  computed(() => props.academy.name),
  role,
  { 
    isPending: () => !!props.isPending,
    canRequestJoin: () => props.canRequestJoin !== false
  }
)

const tabMap: Record<string, string> = {
  feed: 'feed',
  courses: 'courses',
  classrooms: 'classrooms',
  members: 'members',
  events: 'events',
  about: 'about',
}

const toneClasses = (color?: string) => {
  switch (color) {
    case 'emerald':
      return 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300'
    case 'rose':
      return 'bg-rose-500/15 text-rose-700 dark:text-rose-300'
    case 'violet':
      return 'bg-violet-500/15 text-violet-700 dark:text-violet-300'
    case 'amber':
      return 'bg-amber-500/15 text-amber-700 dark:text-amber-300'
    case 'slate':
      return 'bg-slate-500/15 text-slate-700 dark:text-slate-300'
    case 'sky':
    default:
      return 'bg-vikinger-cyan/15 text-cyan-700 dark:text-cyan-300'
  }
}

const normalizeLocalTab = (to: string) => {
  const hash = to.includes('#') ? to.split('#')[1] : ''
  return hash ? tabMap[hash] ?? null : null
}

const quickItems = computed(() => {
  return visibleDestinations.value.slice(0, 5).map((item) => ({
    ...item,
    localTab: normalizeLocalTab(item.to),
    iconTone: toneClasses(item.color),
  }))
})

const primaryAction = computed(() => {
  if (!primaryCta.value) return null

  return {
    ...primaryCta.value,
    localTab: primaryCta.value.to ? normalizeLocalTab(primaryCta.value.to) : null,
  }
})

const activateItem = (tab: string | null) => {
  if (tab) {
    emit('navigate', tab)
  }
}

onMounted(async () => {
  if (academyId.value) {
    await role.fetchMyRole()
  }
})
</script>

<template>
  <section class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-vikinger-dark-200">
    <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700">
      <div class="flex items-center gap-2 font-bold text-gray-900 dark:text-white">
        <Icon icon="fluent:navigation-24-filled" class="h-5 w-5 text-vikinger-purple" />
        <span>เมนูลัด</span>
      </div>
      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        ทางลัดตามบทบาทและสถานะของคุณในโรงเรียนนี้
      </p>
    </div>

    <div class="space-y-3 p-3">
      <div
        v-if="pendingHint"
        class="rounded-xl border border-amber-200/80 bg-amber-50 px-3 py-2.5 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200"
      >
        {{ pendingHint }}
      </div>

      <div class="space-y-2">
        <template v-for="item in quickItems" :key="item.id">
          <button
            v-if="item.localTab"
            type="button"
            class="flex w-full items-center gap-3 rounded-xl border border-gray-100 px-3 py-3 text-left transition-all hover:border-vikinger-purple/30 hover:bg-gray-50 motion-reduce:transition-none dark:border-gray-700 dark:hover:bg-vikinger-dark-100"
            @click="activateItem(item.localTab)"
          >
            <span :class="['flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl', item.iconTone]">
              <Icon :icon="item.icon" class="h-5 w-5" />
            </span>

            <span class="min-w-0 flex-1">
              <span class="block truncate text-sm font-semibold text-gray-900 dark:text-white">
                {{ item.title }}
              </span>
              <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                {{ item.destinationLabel }}
              </span>
            </span>

            <Icon icon="fluent:chevron-right-20-regular" class="h-4 w-4 flex-shrink-0 text-gray-400" />
          </button>

          <NuxtLink
            v-else
            :to="item.to"
            class="flex w-full items-center gap-3 rounded-xl border border-gray-100 px-3 py-3 text-left transition-all hover:border-vikinger-purple/30 hover:bg-gray-50 motion-reduce:transition-none dark:border-gray-700 dark:hover:bg-vikinger-dark-100"
          >
            <span :class="['flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl', item.iconTone]">
              <Icon :icon="item.icon" class="h-5 w-5" />
            </span>

            <span class="min-w-0 flex-1">
              <span class="block truncate text-sm font-semibold text-gray-900 dark:text-white">
                {{ item.title }}
              </span>
              <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                {{ item.destinationLabel }}
              </span>
            </span>

            <Icon icon="fluent:chevron-right-20-regular" class="h-4 w-4 flex-shrink-0 text-gray-400" />
          </NuxtLink>
        </template>
      </div>

      <div v-if="primaryAction" class="pt-1">
        <button
          v-if="primaryAction.id === 'join-academy'"
          type="button"
          class="min-h-[44px] sm:min-h-0 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-vikinger-purple px-4 py-2.5 text-sm font-semibold text-white hover:bg-vikinger-purple/90"
          @click="emit('join')"
        >
          <Icon :icon="primaryAction.icon" class="h-4 w-4" />
          {{ primaryAction.title }}
        </button>

        <button
          v-else-if="primaryAction.localTab"
          type="button"
          class="min-h-[44px] sm:min-h-0 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-vikinger-purple px-4 py-2.5 text-sm font-semibold text-white hover:bg-vikinger-purple/90"
          @click="activateItem(primaryAction.localTab)"
        >
          <Icon :icon="primaryAction.icon" class="h-4 w-4" />
          {{ primaryAction.title }}
        </button>

        <NuxtLink
          v-else
          :to="primaryAction.to"
          class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-vikinger-purple px-4 py-2.5 text-sm font-semibold text-white hover:bg-vikinger-purple/90"
        >
          <Icon :icon="primaryAction.icon" class="h-4 w-4" />
          {{ primaryAction.title }}
        </NuxtLink>
      </div>

      <p v-if="!quickItems.length" class="py-3 text-center text-xs text-gray-500 dark:text-gray-400">
        ยังไม่มีเมนูที่เข้าถึงได้สำหรับสถานะปัจจุบัน
      </p>
    </div>
  </section>
</template>

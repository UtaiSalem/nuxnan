<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useAcademyRole } from '~/composables/useAcademyRole'
import { useAcademyNavigation } from '~/composables/useAcademyNavigation'

const props = defineProps<{
  academy: { id: number; name: string; memberStatus?: number | null } | null
  isPending?: boolean
}>()

const emit = defineEmits<{
  (e: 'join'): void
}>()

const academyId = computed(() => props.academy?.id ?? null)
const academyName = computed(() => props.academy?.name ?? '')

const role = useAcademyRole(academyId)
const { visibleDestinations, primaryCta, pendingHint } = useAcademyNavigation(
  academyName,
  role,
  { isPending: () => !!props.isPending }
)

onMounted(async () => {
  if (academyId.value) {
    await role.fetchMyRole()
  }
})

// Color helper to return tailwind gradient classes matching the destination design system
const colorClasses = (color?: string) => {
  switch (color) {
    case 'emerald':
      return 'from-emerald-500/5 to-emerald-600/5 dark:from-emerald-950/20 dark:to-emerald-900/10 border-emerald-100 hover:border-emerald-300 dark:border-emerald-900 dark:hover:border-emerald-800 text-emerald-600 dark:text-emerald-400'
    case 'rose':
      return 'from-rose-500/5 to-rose-600/5 dark:from-rose-950/20 dark:to-rose-900/10 border-rose-100 hover:border-rose-300 dark:border-rose-900 dark:hover:border-rose-800 text-rose-600 dark:text-rose-400'
    case 'violet':
      return 'from-violet-500/5 to-violet-600/5 dark:from-violet-950/20 dark:to-violet-900/10 border-violet-100 hover:border-violet-300 dark:border-violet-900 dark:hover:border-violet-800 text-violet-600 dark:text-violet-400'
    case 'amber':
      return 'from-amber-500/5 to-amber-600/5 dark:from-amber-950/20 dark:to-amber-900/10 border-amber-100 hover:border-amber-300 dark:border-amber-900 dark:hover:border-amber-800 text-amber-600 dark:text-amber-400'
    case 'slate':
      return 'from-slate-500/5 to-slate-600/5 dark:from-slate-950/20 dark:to-slate-900/10 border-slate-100 hover:border-slate-300 dark:border-slate-900 dark:hover:border-slate-850 text-slate-600 dark:text-slate-400'
    case 'sky':
    default:
      return 'from-sky-500/5 to-sky-600/5 dark:from-sky-950/20 dark:to-sky-900/10 border-sky-100 hover:border-sky-300 dark:border-sky-900 dark:hover:border-sky-800 text-sky-600 dark:text-sky-400'
  }
}
</script>

<template>
  <section 
    v-if="academy" 
    class="bg-white dark:bg-gray-900 rounded-2xl p-4 md:p-6 shadow-sm border border-gray-100 dark:border-gray-800 motion-reduce:transition-none"
    aria-labelledby="action-guide-title"
  >
    <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div>
        <h2 id="action-guide-title" class="font-bold text-base md:text-lg text-gray-900 dark:text-white flex items-center gap-2">
          <Icon icon="fluent:navigation-24-filled" class="w-5 h-5 text-sky-600 dark:text-sky-400" />
          ศูนย์นำทางโรงเรียน
        </h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">เลือกสิ่งที่ต้องการทำเพื่อไปยังส่วนต่างๆ ของโรงเรียนได้อย่างรวดเร็ว</p>
      </div>
      
      <!-- Primary CTA button -->
      <div v-if="primaryCta">
        <button
          v-if="primaryCta.id === 'join-academy'"
          @click="emit('join')"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 active:scale-95 transition-all focus-visible:ring-2 focus-visible:ring-sky-500"
        >
          <Icon :icon="primaryCta.icon" class="w-4 h-4" />
          {{ primaryCta.title }}
        </button>
        <NuxtLink
          v-else
          :to="primaryCta.to"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 active:scale-95 transition-all focus-visible:ring-2 focus-visible:ring-sky-500"
        >
          <Icon :icon="primaryCta.icon" class="w-4 h-4" />
          {{ primaryCta.title }}
        </NuxtLink>
      </div>
    </header>

    <!-- Pending Application Banner -->
    <div 
      v-if="pendingHint" 
      class="mb-4 p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/30 text-amber-800 dark:text-amber-200 text-xs md:text-sm border border-amber-100 dark:border-amber-900/50 flex items-start gap-2.5"
    >
      <Icon icon="fluent:warning-24-regular" class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
      <span>{{ pendingHint }}</span>
    </div>

    <!-- Navigation Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 md:gap-3">
      <NuxtLink 
        v-for="dest in visibleDestinations" 
        :key="dest.id" 
        :to="dest.to"
        class="group p-3 md:p-3.5 rounded-xl border transition-all duration-200 bg-gradient-to-br from-white to-gray-50/50 dark:from-gray-900 dark:to-gray-900/50 hover:shadow-md hover:-translate-y-0.5 motion-reduce:transition-none motion-reduce:transform-none focus-visible:ring-2 focus-visible:ring-sky-500"
        :class="colorClasses(dest.color)"
      >
        <div class="flex items-center gap-2 mb-1.5">
          <div class="p-1.5 rounded-lg bg-white dark:bg-gray-800 shadow-sm shrink-0">
            <Icon :icon="dest.icon" class="w-5 h-5" />
          </div>
          <span class="font-bold text-xs md:text-sm text-gray-800 dark:text-gray-200 line-clamp-1 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">{{ dest.title }}</span>
        </div>
        <p class="text-[11px] leading-normal text-gray-500 dark:text-gray-400 line-clamp-2 min-h-[33px]">{{ dest.outcome }}</p>
        <div class="mt-2.5 pt-2 border-t border-gray-100/50 dark:border-gray-800/50 text-[10px] text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
          <Icon icon="fluent:arrow-right-16-regular" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform motion-reduce:transform-none" />
          <span>{{ dest.destinationLabel }}</span>
        </div>
      </NuxtLink>
    </div>

    <!-- Empty state for visible destinations -->
    <p v-if="!visibleDestinations.length" class="text-xs text-gray-500 dark:text-gray-400 text-center py-6">
      ไม่มีเมนูที่เข้าถึงได้สำหรับบทบาทหรือสถานะปัจจุบันของคุณ
    </p>
  </section>
</template>

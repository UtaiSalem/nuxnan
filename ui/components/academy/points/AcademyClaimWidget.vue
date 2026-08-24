<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useAcademyClaimable } from '~/composables/useAcademyPoints'
import AcademyDonorCard from './AcademyDonorCard.vue'

const props = defineProps<{ academyId: number }>()
const emit = defineEmits<{ claimed: [payload: unknown] }>()
const { items, pagination, meta, loading, error, fetchClaimable } = useAcademyClaimable(props.academyId)

const dailyRemaining = computed(() => Math.max(0, 20 - meta.value.my_claims_today_all_tiers))
const totalPool = computed(() => items.value.reduce((sum, item) => sum + item.remaining_points, 0))
const isRefreshing = ref(false)
const loadMoreSentinel = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

// IntersectionObserver ยิง callback เฉพาะตอนเปลี่ยนสถานะ ถ้า sentinel ยังค้างอยู่ในจอ
// หลังต่อรายการหน้าใหม่ มันจะเงียบไปเลย unobserve แล้ว observe ซ้ำ
// = บังคับให้มันแจ้งสถานะปัจจุบันใหม่อีกรอบ
const reobserveSentinel = () => {
  if (!observer || !loadMoreSentinel.value) return
  observer.unobserve(loadMoreSentinel.value)
  observer.observe(loadMoreSentinel.value)
}

onMounted(fetchClaimable)
onMounted(async () => {
  await nextTick()
  observer = new IntersectionObserver(([entry]) => {
    if (entry?.isIntersecting) fetchClaimable(true).catch(() => {})
  }, { rootMargin: '240px' })
  if (loadMoreSentinel.value) observer.observe(loadMoreSentinel.value)
})
onUnmounted(() => observer?.disconnect())

// รายการยาวขึ้น = เพิ่งต่อหน้าใหม่สำเร็จ จึงถามสถานะ sentinel ซ้ำ
// ถ้าหมดหน้าแล้วไม่ต้องถาม จะได้ไม่วนต่อ และถ้าโหลดมาแล้วไม่ได้รายการเพิ่ม
// ความยาวไม่เปลี่ยน watch ก็ไม่ยิง วงจรหยุดเอง
watch(() => items.value.length, () => {
  if (!pagination.value.has_more) return
  nextTick(reobserveSentinel)
})

const claim = async (result: unknown) => {
  await fetchClaimable()
  emit('claimed', result)
}

async function refresh() {
  isRefreshing.value = true
  await fetchClaimable()
  setTimeout(() => (isRefreshing.value = false), 600)
}

// SVG arc gauge for daily remaining
const gaugeRadius = 28
const gaugeCircumference = 2 * Math.PI * gaugeRadius
const gaugeFillPct = computed(() => dailyRemaining.value / 20)
const gaugeDashOffset = computed(() => gaugeCircumference * (1 - gaugeFillPct.value))
</script>

<template>
  <section class="min-w-0 rounded-2xl border border-gray-200/80 bg-white shadow-sm dark:border-gray-700/50 dark:bg-vikinger-dark-200">
    <!-- Gradient top accent -->
    <div class="h-1 rounded-t-2xl bg-gradient-to-r from-violet-500 via-indigo-500 to-blue-500" />

    <div class="p-5 sm:p-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 shadow-sm shadow-violet-500/20">
            <Icon icon="mdi:gift-outline" class="h-5 w-5 text-white" />
          </div>
          <div>
            <h2 class="text-sm font-bold text-gray-900 dark:text-white">กดรับแต้มจากกองสนับสนุน</h2>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">เลือกรายการที่ต้องการกดรับ</p>
          </div>
        </div>
        <button
          type="button"
          class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition-all hover:bg-gray-50 hover:text-indigo-600 dark:border-gray-700 dark:hover:bg-gray-800 dark:hover:text-indigo-400"
          :class="isRefreshing && 'spin-refresh'"
          :disabled="loading"
          @click="refresh"
        >
          <Icon icon="mdi:refresh" class="h-4 w-4" />
        </button>
      </div>

      <!-- Stats row -->
      <div v-if="loading && !items.length" class="mt-4 grid grid-cols-3 gap-3">
        <div v-for="i in 3" :key="i" class="h-20 rounded-xl shimmer-bg" />
      </div>

      <div v-else class="mt-4 grid grid-cols-3 gap-3">
        <!-- Pool remaining -->
        <div class="rounded-xl border border-amber-100 bg-gradient-to-br from-amber-50/70 to-white p-3 text-center dark:border-amber-900/30 dark:from-amber-950/20 dark:to-vikinger-dark-200">
          <div class="flex h-7 w-7 mx-auto items-center justify-center rounded-lg bg-gradient-to-br from-amber-400 to-orange-500 shadow-sm shadow-amber-400/20 mb-1.5">
            <Icon icon="mdi:star-four-points" class="h-3.5 w-3.5 text-white" />
          </div>
          <p class="text-base font-black text-gray-900 dark:text-white tabular-nums">{{ totalPool.toLocaleString() }}</p>
          <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">แต้มในกอง</p>
        </div>

        <!-- Daily gauge -->
        <div class="rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50/70 to-white p-3 text-center dark:border-blue-900/30 dark:from-blue-950/20 dark:to-vikinger-dark-200">
          <div class="relative mx-auto h-14 w-14 mb-0.5">
            <svg viewBox="0 0 64 64" class="h-full w-full -rotate-90">
              <circle cx="32" cy="32" :r="gaugeRadius" fill="none" stroke-width="4" class="stroke-gray-200 dark:stroke-gray-700" />
              <circle
                cx="32" cy="32" :r="gaugeRadius" fill="none"
                stroke-width="4" stroke-linecap="round"
                class="stroke-blue-500 transition-all duration-700"
                :stroke-dasharray="gaugeCircumference"
                :stroke-dashoffset="gaugeDashOffset"
              />
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-sm font-black text-blue-600 dark:text-blue-400">{{ dailyRemaining }}</span>
          </div>
          <p class="text-[10px] text-gray-500 dark:text-gray-400">เหลือวันนี้/20</p>
        </div>

        <!-- Active donations count -->
        <div class="rounded-xl border border-violet-100 bg-gradient-to-br from-violet-50/70 to-white p-3 text-center dark:border-violet-900/30 dark:from-violet-950/20 dark:to-vikinger-dark-200">
          <div class="flex h-7 w-7 mx-auto items-center justify-center rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 shadow-sm shadow-violet-500/20 mb-1.5">
            <Icon icon="mdi:layers-outline" class="h-3.5 w-3.5 text-white" />
          </div>
          <p class="text-base font-black text-gray-900 dark:text-white tabular-nums">{{ pagination.total }}</p>
          <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">รายการ</p>
        </div>
      </div>

    </div>
  </section>

  <!-- Independent donation list: cards can grow without stretching the summary card -->
  <section class="mt-4 min-w-0 rounded-2xl border border-gray-200/80 bg-white shadow-sm dark:border-gray-700/50 dark:bg-vikinger-dark-200">
    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700/50 sm:px-6">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h3 class="text-sm font-bold text-gray-900 dark:text-white">รายการบริจาคที่กดรับได้</h3>
          <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">เลือกกดรับจากแต่ละรายการ ระบบจะแบ่งแต้มให้อัตโนมัติ</p>
        </div>
        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-300">{{ pagination.total }} รายการ</span>
      </div>
    </div>
    <div class="p-5 sm:p-6">

      <!-- Donor cards list -->
      <div v-if="loading && !items.length" class="grid grid-cols-1 gap-4 2xl:grid-cols-2">
        <div v-for="i in 3" :key="i" class="h-52 rounded-2xl shimmer-bg" />
      </div>

      <p v-else-if="!items.length" class="rounded-xl bg-gray-50 py-8 text-center dark:bg-gray-800/50">
        <Icon icon="mdi:gift-off-outline" class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-2" />
        <span class="block text-sm text-gray-500 dark:text-gray-400">ยังไม่มีผู้บริจาคที่กดรับได้</span>
        <span class="block mt-0.5 text-xs text-gray-400 dark:text-gray-500">รอผู้สนับสนุนบริจาคแต้มให้โรงเรียน</span>
      </p>

      <TransitionGroup
        v-else
        tag="div"
        name="donor-list"
        class="grid grid-cols-1 gap-4 2xl:grid-cols-2"
      >
        <AcademyDonorCard
          v-for="(donation, index) in items"
          :key="donation.id"
          :donation="donation"
          :academy-id="academyId"
          :cap-reached="meta.cap_reached"
          :style="{ '--stagger-delay': `${index * 60}ms` }"
          @claimed="claim"
        />
      </TransitionGroup>

      <div ref="loadMoreSentinel" class="flex min-h-10 items-center justify-center pt-4">
        <span v-if="loading && items.length" class="text-xs text-gray-400">กำลังโหลดรายการเพิ่มเติม...</span>
        <span v-else-if="!pagination.has_more && items.length" class="text-xs text-gray-400">แสดงรายการครบแล้ว</span>
      </div>

      <!-- Error state -->
      <div v-if="error" class="mt-4 flex items-center gap-2 rounded-xl bg-red-50 px-4 py-3 dark:bg-red-950/30">
        <Icon icon="mdi:alert-circle" class="h-4 w-4 shrink-0 text-red-500" />
        <p class="flex-1 text-sm text-red-700 dark:text-red-300">ไม่สามารถโหลดรายการกดรับได้</p>
        <button
          type="button"
          class="shrink-0 rounded-lg bg-red-100 px-3 py-1 text-xs font-medium text-red-700 transition hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50"
          @click="fetchClaimable"
        >
          ลองใหม่
        </button>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Shimmer loading */
.shimmer-bg {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}
:root.dark .shimmer-bg,
.dark .shimmer-bg {
  background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
  background-size: 200% 100%;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* Refresh rotation */
.spin-refresh {
  animation: spinRefresh 0.6s ease-in-out;
}
@keyframes spinRefresh {
  0% { transform: rotate(0); }
  100% { transform: rotate(360deg); }
}

/* TransitionGroup staggered entry */
.donor-list-enter-active {
  transition: all 0.4s ease-out;
  transition-delay: var(--stagger-delay, 0ms);
}
.donor-list-leave-active {
  transition: all 0.3s ease-in;
}
.donor-list-enter-from {
  opacity: 0;
  transform: translateY(12px);
}
.donor-list-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
.donor-list-move {
  transition: transform 0.3s ease;
}
</style>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref, computed, onUnmounted } from 'vue'
import type { AcademyDonationClaimable } from '~/composables/useAcademyPoints'
import { useAcademyClaimable } from '~/composables/useAcademyPoints'
import { useAuthStore } from '~/stores/auth'

const props = defineProps<{ donation: AcademyDonationClaimable; academyId: number; capReached: boolean }>()
const emit = defineEmits<{ claimed: [payload: unknown] }>()
const { claimFromDonation } = useAcademyClaimable(props.academyId)
const authStore = useAuthStore()

const loading = ref(false)
const success = ref('')
const showSplit = ref(false)
const showReceiveModal = ref(false)
const countdownSeconds = ref(10)
const animatedPoints = ref(0)
let animationTimer: ReturnType<typeof setInterval> | null = null
let animationStartedAt = 0

const disabledReason = computed(() => {
  if (props.donation.remaining_points < 1) return 'กองนี้หมดแล้ว'
  if (props.capReached) return 'ครบเพดาน 20 ครั้ง/วัน แล้ว'
  if (props.donation.my_claims_today >= 5) return 'กดรับจากรายการนี้ครบ 5 ครั้งวันนี้แล้ว'
  return ''
})

const progressPct = computed(() => {
  if (!props.donation.points_amount) return 0
  return Math.round(((props.donation.points_amount - props.donation.remaining_points) / props.donation.points_amount) * 100)
})

// Split breakdown computed from claim_amount_preview
const splitPreview = computed(() => {
  const cost = props.donation.claim_amount_preview
  const claimer = props.donation.claimer_reward_preview
  const suggester = Math.floor(cost * 0.1111)
  const platform = Math.floor(cost * 0.0370)
  const school = cost - claimer - suggester - platform
  return { cost, claimer, suggester, school, platform }
})

const clearReceiveTimers = () => {
  if (animationTimer) clearInterval(animationTimer)
  animationTimer = null
  animationStartedAt = 0
}

const closeReceiveModal = () => {
  if (loading.value) return
  clearReceiveTimers()
  showReceiveModal.value = false
  countdownSeconds.value = 10
  animatedPoints.value = 0
}

const claim = async () => {
  loading.value = true
  try {
    const result = await claimFromDonation(props.donation.id)
    authStore.addPoints(result.wallet.delta)
    success.value = `ได้รับ ${result.wallet.delta} pp`
    emit('claimed', result)
    setTimeout(() => success.value = '', 3500)
  } finally {
    loading.value = false
  }
}

const finishClaim = async () => {
  clearReceiveTimers()
  animatedPoints.value = props.donation.claimer_reward_preview
  await claim()
  showReceiveModal.value = false
  countdownSeconds.value = 10
  animatedPoints.value = 0
}

const openReceiveModal = () => {
  if (disabledReason.value || loading.value || showReceiveModal.value) return
  showReceiveModal.value = true
  countdownSeconds.value = 10
  animatedPoints.value = 0
  const target = props.donation.claimer_reward_preview
  const durationMs = 10_000
  animationStartedAt = Date.now()
  animationTimer = setInterval(() => {
    const elapsed = Math.min(durationMs, Date.now() - animationStartedAt)
    const progress = elapsed / durationMs
    animatedPoints.value = Math.floor(target * progress)
    countdownSeconds.value = Math.max(0, Math.ceil((durationMs - elapsed) / 1000))
    if (elapsed >= durationMs) finishClaim()
  }, 50)
}

onUnmounted(clearReceiveTimers)
</script>

<template>
  <article class="donor-card group relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm transition-all duration-300 hover:shadow-lg hover:border-indigo-200 dark:border-gray-700/60 dark:bg-vikinger-dark-200 dark:hover:border-indigo-800/60">
    <!-- Top gradient accent -->
    <div class="h-1 bg-gradient-to-r from-amber-400 via-indigo-500 to-violet-500" />

    <!-- Success overlay -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition-all duration-500 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="success" class="absolute inset-0 z-20 flex flex-col items-center justify-center rounded-2xl bg-emerald-500/95 dark:bg-emerald-600/95 backdrop-blur-sm">
        <!-- Confetti particles -->
        <div class="confetti-container">
          <span v-for="i in 12" :key="i" class="confetti-dot" :style="{ '--i': i }" />
        </div>
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 mb-2 success-check">
          <Icon icon="mdi:check-bold" class="h-8 w-8 text-white" />
        </div>
        <p class="text-lg font-black text-white">{{ success }}</p>
      </div>
    </Transition>

    <div class="min-w-0 p-4 sm:p-5">
      <!-- Public-tier donor presentation adapted for academy claim logic -->
      <figure class="flex items-center gap-3 rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 p-3 dark:from-blue-950/30 dark:to-indigo-950/30">
        <!-- Avatar -->
        <img
          v-if="donation.donor_avatar"
          :src="donation.donor_avatar"
          :alt="donation.donor_display_name"
          class="h-16 w-16 rounded-full border-4 border-white object-cover shadow-md ring-2 ring-blue-200 dark:border-gray-700 dark:ring-blue-700/50"
        />
        <div
          v-else
          class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-lg font-bold text-white shadow-md"
        >
          {{ (donation.donor_display_name || '?')[0] }}
        </div>

        <div class="min-w-0 flex-1">
          <p class="truncate text-lg font-black text-indigo-700 dark:text-indigo-300">{{ donation.donor_display_name }}</p>
          <p v-if="donation.donor_personal_code" class="mt-0.5 flex items-center gap-1 text-xs font-semibold text-gray-500 dark:text-gray-400"><Icon icon="mdi:identifier" class="h-3.5 w-3.5" />{{ donation.donor_personal_code }}</p>
          <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">ผู้สนับสนุนโรงเรียน · {{ new Date(donation.created_at).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' }) }}</p>
        </div>
        <div class="shrink-0 text-right">
          <p class="text-2xl font-black text-orange-500 dark:text-orange-300">{{ donation.points_amount.toLocaleString() }}</p>
          <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">แต้มที่สนับสนุน</p>
        </div>
      </figure>

      <div class="mt-4 flex items-center justify-between rounded-xl border border-amber-100 bg-gradient-to-br from-yellow-50 to-orange-50 px-4 py-3 dark:border-amber-900/30 dark:from-amber-950/20 dark:to-orange-950/20">
        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">แต้มคงเหลือในกอง</span>
        <span class="text-xl font-black text-green-600 dark:text-green-400">{{ donation.remaining_points.toLocaleString() }} <span class="text-xs">pp</span></span>
      </div>

      <!-- Purpose quote -->
      <p v-if="donation.purpose" class="mt-3 rounded-lg border-l-2 border-indigo-300 bg-indigo-50/50 py-1.5 pl-3 pr-2 text-xs italic text-gray-600 line-clamp-2 dark:border-indigo-700 dark:bg-indigo-950/20 dark:text-gray-300">
        "{{ donation.purpose }}"
      </p>

      <!-- Progress bar -->
      <div class="mt-3">
        <div class="flex items-center justify-between text-[11px] mb-1">
          <span class="text-gray-500 dark:text-gray-400">ใช้ไปแล้ว {{ progressPct }}%</span>
          <span class="font-semibold text-gray-700 dark:text-gray-300">{{ (donation.points_amount - donation.remaining_points).toLocaleString() }} / {{ donation.points_amount.toLocaleString() }} pp</span>
        </div>
        <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
          <div
            class="h-full rounded-full bg-gradient-to-r from-amber-400 to-orange-500 transition-all duration-700 ease-out"
            :style="{ width: progressPct + '%' }"
          />
        </div>
      </div>

      <!-- Split breakdown toggle -->
      <button
        type="button"
        class="min-h-[44px] sm:min-h-0 mt-3 flex w-full items-center justify-between rounded-lg border border-gray-100 px-3 py-2 text-[11px] transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50"
        @click="showSplit = !showSplit"
      >
        <span class="flex items-center gap-1.5 font-medium text-gray-600 dark:text-gray-300">
          <Icon icon="mdi:chart-pie" class="h-3.5 w-3.5 text-indigo-500" />
          แสดงสัดส่วนการแบ่งแต้ม
        </span>
        <Icon
          icon="mdi:chevron-down"
          class="h-4 w-4 text-gray-400 transition-transform duration-200"
          :class="showSplit && 'rotate-180'"
        />
      </button>

      <!-- Split breakdown content -->
      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 max-h-0"
        enter-to-class="opacity-100 max-h-40"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 max-h-40"
        leave-to-class="opacity-0 max-h-0"
      >
        <div v-if="showSplit" class="overflow-hidden">
          <div class="mt-2 rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
            <p class="text-[10px] text-gray-500 dark:text-gray-400 mb-2 font-medium">หักจากกอง {{ splitPreview.cost.toLocaleString() }} pp</p>
            <!-- Stacked bar -->
            <div class="flex h-3 rounded-full overflow-hidden mb-2.5">
              <div class="bg-green-500 transition-all" :style="{ width: '77.78%' }" :title="`ผู้กดรับ ${splitPreview.claimer}`" />
              <div class="bg-blue-400 transition-all" :style="{ width: '11.11%' }" :title="`ผู้แนะนำ ${splitPreview.suggester}`" />
              <div class="bg-violet-400 transition-all" :style="{ width: '7.41%' }" :title="`โรงเรียน ${splitPreview.school}`" />
              <div class="bg-gray-400 transition-all" :style="{ width: '3.70%' }" :title="`Platform ${splitPreview.platform}`" />
            </div>
            <!-- Labels -->
            <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-[10px]">
              <div class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-green-500 shrink-0" />
                <span class="text-gray-600 dark:text-gray-300">ผู้กดรับ</span>
                <span class="ml-auto font-bold text-green-600 dark:text-green-400">{{ splitPreview.claimer }} pp</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-blue-400 shrink-0" />
                <span class="text-gray-600 dark:text-gray-300">ผู้แนะนำ</span>
                <span class="ml-auto font-bold text-blue-500 dark:text-blue-400">{{ splitPreview.suggester }} pp</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-violet-400 shrink-0" />
                <span class="text-gray-600 dark:text-gray-300">โรงเรียน</span>
                <span class="ml-auto font-bold text-violet-600 dark:text-violet-400">{{ splitPreview.school }} pp</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-gray-400 shrink-0" />
                <span class="text-gray-600 dark:text-gray-300">Platform</span>
                <span class="ml-auto font-bold text-gray-600 dark:text-gray-400">{{ splitPreview.platform }} pp</span>
              </div>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Claim row -->
      <div class="mt-3 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
        <div class="flex-1 min-w-0">
          <p class="text-xs text-gray-500 dark:text-gray-400">คุณจะได้รับ</p>
          <p class="text-base font-black text-green-600 dark:text-green-400">{{ donation.claimer_reward_preview.toLocaleString() }} <span class="text-xs font-semibold">pp</span></p>
        </div>
        <button
          type="button"
          :disabled="!!disabledReason || loading"
          class="inline-flex min-h-11 w-full shrink-0 items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:from-blue-600 hover:to-indigo-700 hover:shadow-md active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:shadow-sm sm:w-auto"
          :class="!disabledReason && !loading && 'claim-btn-pulse'"
          @click="openReceiveModal"
        >
          <Icon v-if="loading" icon="svg-spinners:ring-resize" class="h-4 w-4" />
          <Icon v-else icon="fluent:gift-24-filled" class="h-4 w-4" />
          <span>{{ loading ? 'กำลัง...' : 'รับแต้ม' }}</span>
        </button>
      </div>

      <!-- Bottom info row -->
      <div class="mt-2 flex items-center justify-between">
        <!-- Disabled reason -->
        <p v-if="disabledReason" class="text-[11px] text-amber-600 dark:text-amber-400">
          <Icon icon="mdi:alert-circle" class="inline h-3 w-3 mr-0.5" />{{ disabledReason }}
        </p>
        <span v-else />

        <!-- Claims today dots -->
        <div class="flex items-center gap-1" :title="`กดรับวันนี้ ${donation.my_claims_today}/5`">
          <span class="text-[10px] text-gray-400 mr-1">วันนี้</span>
          <span
            v-for="i in 5"
            :key="i"
            class="h-1.5 w-1.5 rounded-full transition-colors"
            :class="i <= donation.my_claims_today
              ? 'bg-indigo-500 dark:bg-indigo-400'
              : 'bg-gray-200 dark:bg-gray-700'"
          />
        </div>
      </div>
    </div>
  </article>

  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="showReceiveModal"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm"
        @click.self="closeReceiveModal"
      >
        <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-gray-800">
          <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-500 to-orange-400 p-6 text-center text-white">
            <div class="relative z-10">
              <div class="relative mx-auto mb-4 h-24 w-24">
                <svg class="h-24 w-24 -rotate-90">
                  <circle cx="48" cy="48" r="42" fill="transparent" stroke="rgba(255,255,255,0.3)" stroke-width="7" />
                  <circle cx="48" cy="48" r="42" fill="transparent" stroke="white" stroke-width="7" stroke-linecap="round" :stroke-dasharray="264" :stroke-dashoffset="264 * (countdownSeconds / 10)" class="transition-all duration-1000 ease-linear" />
                </svg>
                <img v-if="donation.donor_avatar" :src="donation.donor_avatar" :alt="donation.donor_display_name" class="absolute inset-3 h-[72px] w-[72px] rounded-full border-4 border-white object-cover shadow-lg" />
                <div v-else class="absolute inset-3 flex h-[72px] w-[72px] items-center justify-center rounded-full bg-white/25 text-2xl font-bold">{{ (donation.donor_display_name || '?')[0] }}</div>
              </div>
              <h2 class="text-2xl font-black">{{ donation.donor_display_name }}</h2>
              <p class="mt-1 text-sm text-white/80">ผู้ให้การสนับสนุนโรงเรียน</p>
              <p v-if="donation.purpose" class="mx-auto mt-3 max-w-sm rounded-xl bg-white/15 px-4 py-2 text-sm italic text-white/95">
                “{{ donation.purpose }}”
              </p>
            </div>
          </div>

          <div class="p-6 text-center">
            <div class="mb-5 text-6xl font-black text-transparent bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text">{{ countdownSeconds }}</div>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">วินาที</p>
            <div class="mb-5 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
              <div class="h-full rounded-full bg-gradient-to-r from-purple-600 via-pink-500 to-orange-400 transition-all duration-1000" :style="{ width: `${((10 - countdownSeconds) / 10) * 100}%` }" />
            </div>
            <div class="mx-auto mb-5 flex w-fit items-center gap-3 rounded-2xl bg-gradient-to-r from-yellow-100 to-amber-100 px-6 py-4 shadow-inner dark:from-yellow-900/30 dark:to-amber-900/30">
              <Icon icon="mdi:star" class="h-8 w-8 animate-pulse text-yellow-500" />
              <div>
                <div class="text-4xl font-black tabular-nums text-transparent bg-gradient-to-r from-yellow-500 to-orange-500 bg-clip-text">+{{ animatedPoints }}</div>
                <p class="text-xs font-medium text-yellow-700 dark:text-yellow-400">แต้ม</p>
              </div>
            </div>
            <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">ระบบกำลังเตรียมแต้มสนับสนุนให้คุณ</p>
            <button type="button" :disabled="loading" class="rounded-xl bg-gray-200 px-8 py-3 font-semibold text-gray-700 transition hover:bg-gray-300 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-300" @click="closeReceiveModal">
              {{ loading ? 'กำลังดำเนินการ...' : 'ยกเลิก' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* Subtle pulse on available claim button */
.claim-btn-pulse {
  animation: claimPulse 2.5s ease-in-out infinite;
}
@keyframes claimPulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.3); }
  50% { box-shadow: 0 0 0 6px rgba(79, 70, 229, 0); }
}

/* Success check animation */
.success-check {
  animation: successPop 0.4s ease-out forwards;
}
@keyframes successPop {
  0% { transform: scale(0); opacity: 0; }
  70% { transform: scale(1.15); }
  100% { transform: scale(1); opacity: 1; }
}

/* Confetti dots */
.confetti-container {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
}
.confetti-dot {
  position: absolute;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  left: 50%;
  top: 50%;
  animation: confettiFly 1s ease-out forwards;
  animation-delay: calc(var(--i) * 0.05s);
  opacity: 0;
}
.confetti-dot:nth-child(1) { background: #fbbf24; }
.confetti-dot:nth-child(2) { background: #f472b6; }
.confetti-dot:nth-child(3) { background: #60a5fa; }
.confetti-dot:nth-child(4) { background: #34d399; }
.confetti-dot:nth-child(5) { background: #a78bfa; }
.confetti-dot:nth-child(6) { background: #fb923c; }
.confetti-dot:nth-child(7) { background: #fbbf24; }
.confetti-dot:nth-child(8) { background: #f472b6; }
.confetti-dot:nth-child(9) { background: #60a5fa; }
.confetti-dot:nth-child(10) { background: #34d399; }
.confetti-dot:nth-child(11) { background: #a78bfa; }
.confetti-dot:nth-child(12) { background: #fb923c; }
@keyframes confettiFly {
  0% { transform: translate(0, 0) scale(0); opacity: 1; }
  100% {
    transform: translate(
      calc((var(--i) - 6) * 25px),
      calc(-60px + sin(var(--i)) * 40px)
    ) scale(1);
    opacity: 0;
  }
}
.confetti-dot:nth-child(odd) {
  animation-name: confettiFlyAlt;
}
@keyframes confettiFlyAlt {
  0% { transform: translate(0, 0) scale(0) rotate(0); opacity: 1; }
  100% {
    transform: translate(
      calc((var(--i) - 6) * -20px),
      calc(-50px + var(--i) * 8px)
    ) scale(1.2) rotate(180deg);
    opacity: 0;
  }
}
</style>

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
  <div class="support-cta group relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-600 via-indigo-600 to-blue-600 p-5 shadow-lg dark:from-violet-700 dark:via-indigo-700 dark:to-blue-700">
    <!-- Animated floating orbs -->
    <div class="pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white/10 blur-2xl cta-orb-1" />
    <div class="pointer-events-none absolute -left-6 -bottom-6 h-24 w-24 rounded-full bg-amber-400/10 blur-2xl cta-orb-2" />
    <div class="pointer-events-none absolute right-1/4 top-1/2 h-16 w-16 rounded-full bg-pink-400/10 blur-xl cta-orb-3" />

    <!-- Sparkle decoration -->
    <div class="pointer-events-none absolute right-4 top-4 text-white/20 cta-sparkle">
      <Icon icon="mdi:star-four-points" class="h-5 w-5" />
    </div>
    <div class="pointer-events-none absolute right-12 bottom-8 text-white/15 cta-sparkle-2">
      <Icon icon="mdi:star-four-points" class="h-3 w-3" />
    </div>

    <div class="relative z-10">
      <!-- Header -->
      <div class="flex items-start gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm cta-heart">
          <Icon icon="fluent:heart-24-filled" class="h-5 w-5 text-pink-300" />
        </div>
        <div class="min-w-0">
          <h3 class="font-bold text-white">ร่วมสนับสนุนโรงเรียน</h3>
          <p class="mt-0.5 text-xs text-white/60 leading-relaxed">
            ร่วมสร้างโอกาสทางการเรียนรู้ ด้วยแต้มหรือเงินบริจาค
          </p>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="mt-5 flex flex-col gap-2.5">
        <button
          type="button"
          class="cta-shine-btn relative w-full inline-flex items-center justify-center gap-2 overflow-hidden rounded-xl bg-white px-4 py-3 text-sm font-bold text-violet-700 shadow-md transition-all hover:shadow-lg hover:scale-[1.02] active:scale-[0.98]"
          @click="emit('donate')"
        >
          <Icon icon="fluent:heart-24-filled" class="h-4.5 w-4.5 text-pink-500" />
          <span>สนับสนุนโรงเรียน</span>
        </button>
        <NuxtLink
          :to="{ path: '/earn/advertise/create', query: { scope: 'academy', academy_id: academyId } }"
          class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition-all hover:bg-white/20 hover:border-white/30 hover:scale-[1.01] active:scale-[0.99]"
        >
          <Icon icon="solar:megaphone-bold-duotone" class="h-4 w-4 text-amber-300" />
          ลงแคมเปญโฆษณา
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Floating orbs animation */
.cta-orb-1 {
  animation: cta-float-1 6s ease-in-out infinite;
}
.cta-orb-2 {
  animation: cta-float-2 8s ease-in-out infinite;
}
.cta-orb-3 {
  animation: cta-float-3 7s ease-in-out infinite;
}

@keyframes cta-float-1 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(-12px, 12px) scale(1.1); }
}
@keyframes cta-float-2 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(10px, -10px) scale(1.15); }
}
@keyframes cta-float-3 {
  0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.5; }
  50% { transform: translate(-8px, -6px) scale(1.2); opacity: 0.8; }
}

/* Pulsing heart */
.cta-heart {
  animation: cta-heartbeat 2s ease-in-out infinite;
}
@keyframes cta-heartbeat {
  0%, 100% { transform: scale(1); }
  14% { transform: scale(1.1); }
  28% { transform: scale(1); }
  42% { transform: scale(1.08); }
  56% { transform: scale(1); }
}

/* Sparkle rotation */
.cta-sparkle {
  animation: cta-twinkle 3s ease-in-out infinite;
}
.cta-sparkle-2 {
  animation: cta-twinkle 4s ease-in-out infinite 1.5s;
}
@keyframes cta-twinkle {
  0%, 100% { opacity: 0.2; transform: rotate(0deg) scale(1); }
  50% { opacity: 0.5; transform: rotate(90deg) scale(1.3); }
}

/* Shine sweep on button */
.cta-shine-btn::after {
  content: '';
  position: absolute;
  top: -50%;
  left: -100%;
  width: 60%;
  height: 200%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transform: skewX(-20deg);
  animation: cta-shine 4s ease-in-out infinite;
}
@keyframes cta-shine {
  0%, 75%, 100% { left: -100%; }
  85% { left: 150%; }
}
</style>

<template>
  <Transition name="fade">
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-md">
      <div class="relative flex h-screen w-screen flex-col overflow-hidden bg-white dark:bg-gray-900 md:flex-row">
        <button v-if="canClose" @click="closeModal" class="absolute right-4 top-4 z-10 rounded-full bg-black/50 p-2 text-white">
          <Icon icon="mdi:close" class="h-6 w-6" />
        </button>
        <div class="flex h-1/2 w-full items-center justify-center bg-black md:h-full md:w-2/3">
          <video v-if="isVideo" :src="advert?.media_image" class="h-full w-full object-contain" autoplay loop muted playsinline controls />
          <img v-else :src="advert?.media_image" class="h-full w-full object-contain" alt="Ad Content" />
        </div>
        <div class="relative flex h-1/2 w-full flex-col items-center justify-center bg-white p-8 text-center dark:bg-gray-800 md:h-full md:w-1/3">
          <p v-if="totalDuration" class="mb-4 text-sm font-medium text-amber-500">
            {{ t('ad.reward_preview', { duration: totalDuration, points: expectedStudentReward }) }}
          </p>
          <h3 class="mb-2 line-clamp-2 text-xl font-bold text-gray-900 dark:text-white">{{ advert?.title || 'Product Advertisement' }}</h3>
          <p v-if="advert?.description" class="mb-4 line-clamp-3 text-sm text-gray-500">{{ advert.description }}</p>
          <a v-if="advert?.media_link" :href="advert.media_link" target="_blank" class="mb-6 text-sm font-medium text-teal-600">ชมเว็บไซต์</a>
          <div v-if="timeLeft > 0" class="relative mb-8"><div class="text-3xl font-bold text-gray-800 dark:text-white">{{ timeLeft }}</div><div class="text-xs text-gray-500">{{ t('ad.watching') }}</div></div>
          <div v-else-if="rewardClaimed" class="mb-8 text-green-600 dark:text-green-400">
            <Icon icon="mdi:check-bold" class="mx-auto mb-3 h-16 w-16" />
            <p>{{ t('ad.success', { points: awardedPoints, id: completionId }) }}</p>
            <dl v-if="rewardSplits" class="mx-auto mt-4 w-full max-w-xs space-y-1 text-left text-xs">
              <div class="flex justify-between"><dt class="text-gray-500">{{ t('ad.split_viewer') }}</dt><dd class="font-semibold">{{ rewardSplits.student }} {{ t('points') }}</dd></div>
              <div v-if="rewardSplits.course > 0" class="flex justify-between"><dt class="text-gray-500">{{ t('ad.split_course') }}</dt><dd class="font-semibold">{{ rewardSplits.course }} {{ t('points') }}</dd></div>
              <div v-if="rewardSplits.academy > 0" class="flex justify-between"><dt class="text-gray-500">{{ t('ad.split_academy') }}</dt><dd class="font-semibold">{{ rewardSplits.academy }} {{ t('points') }}</dd></div>
              <div class="flex justify-between"><dt class="text-gray-500">{{ t('ad.split_platform') }}</dt><dd class="font-semibold">{{ rewardSplits.platform }} {{ t('points') }}</dd></div>
              <div class="flex justify-between border-t pt-1 text-[10px] text-gray-400"><dt>policy #{{ rewardSplits.policy_id }} v{{ rewardSplits.policy_version }}</dt><dd>#{{ completionId }}</dd></div>
            </dl>
          </div>
          <div v-else-if="resultMessage" class="mb-8 text-sm text-red-500">{{ resultMessage }}</div>
          <div v-else-if="maxViewsReached" class="mb-8 text-gray-500">โฆษณานี้แสดงผลครบแล้ว</div>
          <div v-if="processing" class="text-teal-500">{{ t('ad.watching') }}</div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useAdDelivery } from '~/composables/useAdDelivery'

const props = defineProps<{ isOpen: boolean; advert?: any; expectedStudentReward: number }>()
const emit = defineEmits<{ close: []; completed: [advert: any] }>()
const { t } = useI18n()
const { start, heartbeat, complete } = useAdDelivery()
const timeLeft = ref(0), totalDuration = ref(0), processing = ref(false), rewardClaimed = ref(false), maxViewsReached = ref(false)
const canClose = ref(false), resultMessage = ref(''), awardedPoints = ref(0), completionId = ref<number | string>('')
const token = ref(''), deliveryId = ref(0), timer = ref<ReturnType<typeof setInterval> | null>(null), heartbeatTimer = ref<ReturnType<typeof setInterval> | null>(null), rewardSplits = ref<any>(null)
const isVideo = computed(() => ['mp4', 'webm', 'ogg'].includes((props.advert?.media_image?.split('.').pop() || '').toLowerCase()))

watch(() => props.isOpen, value => value && props.advert ? startAd() : resetAd())

async function startAd() {
  resetAd(); canClose.value = false; resultMessage.value = ''
  if (props.advert.remaining_views <= 0) { maxViewsReached.value = true; canClose.value = true; return }
  try {
    const delivery = await start(props.advert.id); token.value = delivery.token; deliveryId.value = delivery.deliveryId; totalDuration.value = delivery.requiredDuration; timeLeft.value = delivery.requiredDuration
    beginTimers()
  } catch (error: any) {
    const status = error.status || error.originalError?.status
    if ([401, 403, 404].includes(status)) { resultMessage.value = t('ad.failure_generic'); canClose.value = true }
    else { canClose.value = true; resultMessage.value = t('ad.failure_generic') }
  }
}
function beginTimers() {
  timer.value = setInterval(() => { timeLeft.value--; if (timeLeft.value <= 0) { clearTimers(); claimReward() } }, 1000)
  heartbeatTimer.value = setInterval(() => heartbeat(deliveryId.value, token.value, document.visibilityState === 'visible' ? 1 : 0).catch(() => {}), 5000)
}
async function claimReward() {
  processing.value = true
  try {
    const result = await complete(deliveryId.value, token.value); completionId.value = (result as any).delivery?.id || deliveryId.value
    if (result.valid) { awardedPoints.value = result.reward?.splits.student || 0; rewardSplits.value = result.reward?.splits || null; rewardClaimed.value = true; emit('completed', props.advert) }
    else resultMessage.value = ({ below_required_duration: t('ad.failure_below_duration'), low_visibility: t('ad.failure_low_visibility'), replayed: t('ad.failure_replayed') } as Record<string, string>)[result.reason || ''] || t('ad.failure_generic')
  } catch (error: any) { if ((error.status || error.originalError?.status) === 409) resultMessage.value = t('ad.failure_replayed'); else { resultMessage.value = t('ad.failure_generic'); Swal.fire('Error', resultMessage.value, 'error') } }
  finally { processing.value = false; canClose.value = true }
}
function clearTimers() { if (timer.value) clearInterval(timer.value); if (heartbeatTimer.value) clearInterval(heartbeatTimer.value); timer.value = null; heartbeatTimer.value = null }
function resetAd() { clearTimers(); timeLeft.value = 0; totalDuration.value = 0; token.value = ''; deliveryId.value = 0; processing.value = false; rewardClaimed.value = false; rewardSplits.value = null; canClose.value = false; maxViewsReached.value = false }
function closeModal() { if (!canClose.value) return; resetAd(); emit('close') }
onUnmounted(clearTimers)
</script>

<style scoped>.fade-enter-active,.fade-leave-active{transition:opacity .3s}.fade-enter-from,.fade-leave-to{opacity:0}</style>

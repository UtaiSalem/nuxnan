<script setup lang="ts">
import { Icon } from '@iconify/vue'
import AcademyClaimWidget from '~/components/academy/points/AcademyClaimWidget.vue'
import { useAcademyClaimable } from '~/composables/useAcademyPoints'
import type { AcademySupportSummary } from '~/composables/useAcademyRevenue'

const props = withDefaults(defineProps<{ academyId: number; summary?: AcademySupportSummary | null; loading?: boolean; canClaim?: boolean; donationEnabled?: boolean }>(), { summary: null, loading: false, canClaim: false, donationEnabled: true })
const emit = defineEmits<{ donate: []; claimed: [payload: unknown] }>()

const { claims, claimsPagination, claimsSummary, claimsLoading, claimsError, fetchClaims } = useAcademyClaimable(computed(() => props.academyId))
const claimBump = ref(false)
const historyLoaded = ref(false)

// โรงเรียนกดรับตรงจากกองบริจาค ไม่มีชั้นรอบแจกแต้มเหมือนรายวิชา
const approvedPointDonations = computed(() => (props.summary?.recent_donations ?? []).filter(d => d.donation_type === 'point' && ['approved', 'completed'].includes(d.status)))
const fundBalance = computed(() => props.summary?.point_balance ?? 0)
const supporterCount = computed(() => props.summary?.supporter_count ?? 0)
const distributed = computed(() => props.summary?.total_distributed ?? 0)

const formatClaimDate = (value: string) => value ? new Date(value).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' }) : ''
const loadHistory = async () => { historyLoaded.value = true; await fetchClaims() }
const onClaimed = (payload: unknown) => { if (historyLoaded.value) fetchClaims(); claimBump.value = true; setTimeout(() => claimBump.value = false, 800); emit('claimed', payload) }

onMounted(() => { if (props.canClaim) loadHistory() })
</script>

<template>
  <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-vikinger-purple/10 text-vikinger-purple">
          <Icon icon="mdi:hand-heart-outline" class="h-6 w-6" />
        </span>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">สนับสนุน &amp; รับแต้ม</h2>
      </div>
      <button v-if="donationEnabled" type="button" class="rounded-xl bg-vikinger-purple px-4 py-2 text-sm font-semibold text-white" @click="emit('donate')">บริจาคแต้ม</button>
    </header>

    <div class="mt-5 grid grid-cols-3 gap-3 border-y border-gray-100 py-4 dark:border-gray-700">
      <div>
        <p class="text-xs text-gray-500 dark:text-gray-400">แต้มในกองทุน</p>
        <p class="mt-1 text-xl font-bold text-gray-900 transition dark:text-white" :class="claimBump ? 'animate-pulse scale-110' : ''">{{ fundBalance.toLocaleString() }} แต้ม</p>
      </div>
      <div>
        <p class="text-xs text-gray-500 dark:text-gray-400">ผู้สนับสนุน</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ supporterCount.toLocaleString() }}</p>
      </div>
      <div>
        <p class="text-xs text-gray-500 dark:text-gray-400">แจกแล้ว</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ distributed.toLocaleString() }}</p>
      </div>
    </div>

    <section class="mt-5">
      <h3 class="font-bold text-gray-900 dark:text-white">ผู้สนับสนุนล่าสุด</h3>
      <div v-if="loading" class="mt-3 space-y-2">
        <div v-for="n in 3" :key="n" class="h-10 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-700/60" />
      </div>
      <TransitionGroup v-else name="donate-list" tag="div" class="mt-3 space-y-2">
        <div v-for="donation in approvedPointDonations" :key="donation.id" class="flex items-center justify-between rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-gray-900/40">
          <span class="text-sm text-gray-700 dark:text-gray-200">{{ donation.donor_display_name || 'ผู้สนับสนุนไม่ประสงค์ออกนาม' }}</span>
          <span class="text-sm font-bold text-vikinger-purple">{{ donation.points_amount }} แต้ม</span>
        </div>
      </TransitionGroup>
      <p v-if="!loading && !approvedPointDonations.length" class="mt-3 text-sm text-gray-500 dark:text-gray-400">ยังไม่มีคนสนับสนุน ลองแชร์โรงเรียนให้เพื่อนดูสิ 🚀</p>
    </section>

    <section v-if="canClaim" class="mt-5 rounded-2xl border border-gray-100 dark:border-gray-700">
      <div class="overflow-x-auto border-b border-gray-100 p-3 dark:border-gray-700">
        <div class="flex min-w-max gap-2">
          <span class="flex items-center gap-2 rounded-xl bg-vikinger-purple px-4 py-2 text-sm font-medium text-white">
            <Icon icon="mdi:history" class="h-4 w-4" />ประวัติการกดรับ
          </span>
        </div>
      </div>
      <div class="p-4">
        <p class="text-sm text-gray-600 dark:text-gray-300">คุณรับแต้มแล้ว {{ claimsSummary.my_claims_count }} ครั้ง รวม {{ claimsSummary.my_points_total.toLocaleString() }} แต้ม · ทั้งหมด {{ claimsSummary.total_claims }} รายการ</p>
        <div v-if="claimsLoading && !claims.length" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">กำลังโหลด...</div>
        <div v-else-if="claims.length" class="mt-3 space-y-2">
          <div
            v-for="item in claims"
            :key="item.id"
            class="flex items-center gap-3 rounded-xl border border-transparent p-3 dark:border-transparent"
            :class="item.is_mine ? 'bg-indigo-50 ring-1 ring-indigo-200 dark:bg-indigo-900/20 dark:ring-indigo-700' : 'bg-gray-50 dark:bg-gray-900/40'"
          >
            <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-vikinger-purple/10 font-bold text-vikinger-purple dark:bg-vikinger-purple/20">
              <img v-if="item.claimer_avatar" :src="item.claimer_avatar" class="h-full w-full object-cover" >
              <span v-else>{{ (item.claimer_name || '?').charAt(0).toUpperCase() }}</span>
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm text-gray-700 dark:text-gray-200">{{ item.claimer_name || 'สมาชิก' }} กดรับจาก {{ item.donor_display_name }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatClaimDate(item.claimed_at) }}</p>
            </div>
            <strong class="shrink-0 text-sm text-emerald-600 dark:text-emerald-400">+{{ item.amount_claimer }} แต้ม</strong>
          </div>
        </div>
        <p v-else class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">ยังไม่มีประวัติการรับแต้ม</p>
        <p v-if="claimsError" class="mt-2 text-xs text-gray-500 dark:text-gray-400">ไม่สามารถโหลดประวัติได้ในขณะนี้</p>
        <button
          v-if="claimsPagination.has_more"
          type="button"
          :disabled="claimsLoading"
          class="mt-3 rounded-xl bg-gray-100 px-4 py-2 text-sm text-gray-700 transition disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700/60 dark:text-gray-200"
          @click="fetchClaims(true)"
        >
          {{ claimsLoading ? 'กำลังโหลด...' : 'โหลดเพิ่ม' }}
        </button>
      </div>
    </section>

    <AcademyClaimWidget v-if="canClaim" class="mt-5 block" :academy-id="academyId" @claimed="onClaimed" />
  </section>
</template>

<style scoped>
.donate-list-enter-active,.donate-list-leave-active{transition:all .3s ease}
.donate-list-enter-from,.donate-list-leave-to{opacity:0;transform:translateY(8px)}
</style>

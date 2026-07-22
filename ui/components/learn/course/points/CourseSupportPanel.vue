<script setup lang="ts">
import CourseDonationModal from '~/components/donation/CourseDonationModal.vue'
import CoursePointClaimCard from '~/components/learn/course/points/CoursePointClaimCard.vue'
import type { CourseDonation } from '~/composables/useCourseDonations'

const props = withDefaults(defineProps<{ course: any; isCourseAdmin?: boolean }>(), {
  isCourseAdmin: false,
})

const authStore = useAuthStore()
const sweetAlert = useSweetAlert()
const showDonationModal = ref(false)
const donations = ref<CourseDonation[]>([])

const {
  account,
  campaigns,
  ownerCampaigns,
  isClaiming,
  isLoadingCampaigns,
  fetchAccount,
  fetchAvailableCampaigns,
  fetchOwnerCampaigns,
  claimCampaign,
} = useCoursePoints(computed(() => props.course.id))
const { fetchCourseDonations } = useCourseDonations()

const approvedPointDonations = computed(() => donations.value.filter((donation) => (
  donation.donation_type === 'point' && ['approved', 'completed'].includes(donation.status)
)))
const fundBalance = computed(() => account.value?.balance ?? 0)
const showClaimSection = computed(() => isLoadingCampaigns.value || campaigns.value.length > 0 || fundBalance.value > 0)

const loadSupportData = async () => {
  await Promise.all([
    fetchAccount(),
    props.isCourseAdmin ? fetchOwnerCampaigns() : fetchAvailableCampaigns(),
    fetchCourseDonations(Number(props.course.id), { per_page: 5 }).then((response: any) => {
      donations.value = response?.data || []
    }),
  ])
}

const claim = async (id: number) => {
  const result = await claimCampaign(id) as any
  if (result?.success) sweetAlert.toast(`+${result.points_received} แต้ม!`)
}

onMounted(loadSupportData)
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
      <button
        v-if="!isCourseAdmin && course.donation_enabled !== false"
        type="button"
        class="rounded-xl bg-vikinger-purple px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
        @click="showDonationModal = true"
      >
        บริจาคแต้ม
      </button>
      <NuxtLink v-else-if="isCourseAdmin" :to="`/courses/${course.id}/wallet/campaigns`" class="rounded-xl bg-vikinger-purple px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
        จัดการแคมเปญ
      </NuxtLink>
    </header>

    <div class="mt-5 grid grid-cols-2 gap-3 border-y border-gray-100 py-4 dark:border-gray-700">
      <div><p class="text-xs text-gray-500 dark:text-gray-400">แต้มในกองทุน</p><p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ fundBalance }} แต้ม</p></div>
      <div><p class="text-xs text-gray-500 dark:text-gray-400">จำนวนผู้สนับสนุน</p><p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ approvedPointDonations.length }}</p></div>
    </div>

    <section v-if="!isCourseAdmin && showClaimSection" class="mt-5 border-b border-gray-100 pb-5 dark:border-gray-700">
      <h3 class="mb-3 flex items-center gap-2 font-bold text-gray-900 dark:text-white"><Icon icon="mdi:star-circle" class="h-5 w-5 text-vikinger-purple" />รับแต้ม</h3>
      <div v-if="isLoadingCampaigns" class="h-24 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-700" />
      <div v-else-if="campaigns.length" class="grid gap-3">
        <CoursePointClaimCard v-for="campaign in campaigns" :key="campaign.id" :campaign="campaign" :is-claiming="isClaiming === campaign.id" @claim="claim" />
      </div>
      <p v-else class="text-sm text-gray-500 dark:text-gray-400">มีแต้มในกองทุน รอเจ้าของวิชาเปิดให้รับ</p>
    </section>

    <section v-if="isCourseAdmin && (ownerCampaigns.length > 0 || fundBalance > 0)" class="mt-5 border-b border-gray-100 pb-5 dark:border-gray-700">
      <template v-if="ownerCampaigns.length > 0">
        <h3 class="mb-3 font-bold text-gray-900 dark:text-white">แคมเปญของคุณ</h3>
        <div class="space-y-2">
          <div v-for="campaign in ownerCampaigns" :key="campaign.id" class="flex items-center justify-between gap-3 rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-gray-900/40">
            <span class="min-w-0 truncate text-sm text-gray-700 dark:text-gray-200">{{ campaign.title }}</span>
            <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ campaign.points_per_claim }} แต้ม · <span class="rounded-full bg-gray-200 px-2 py-0.5 dark:bg-gray-700">{{ campaign.status }}</span> · {{ campaign.total_claimed }}{{ campaign.max_claims ? `/${campaign.max_claims}` : '' }} ผู้รับ</span>
          </div>
        </div>
        <NuxtLink :to="`/courses/${course.id}/wallet/campaigns`" class="mt-3 block text-sm font-semibold text-vikinger-purple hover:underline">จัดการทั้งหมด</NuxtLink>
      </template>
      <div v-else class="rounded-xl bg-vikinger-purple/10 p-5 text-center">
        <h3 class="font-bold text-gray-900 dark:text-white">มีแต้มในกองทุนยังไม่ได้เปิดให้รับ</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">สร้างแคมเปญเพื่อให้นักเรียนกดรับแต้ม</p>
        <NuxtLink :to="`/courses/${course.id}/wallet/campaigns`" class="mt-4 inline-flex rounded-xl bg-vikinger-purple px-5 py-3 font-semibold text-white transition hover:opacity-90">สร้างแคมเปญ</NuxtLink>
      </div>
    </section>

    <section class="mt-5">
      <h3 class="font-bold text-gray-900 dark:text-white">ผู้สนับสนุนล่าสุด</h3>
      <div v-if="approvedPointDonations.length" class="mt-3 space-y-2">
        <div v-for="donation in approvedPointDonations" :key="donation.id" class="flex items-center justify-between rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-gray-900/40">
          <span class="text-sm text-gray-700 dark:text-gray-200">{{ donation.donor_display_name || 'ผู้สนับสนุนไม่ประสงค์ออกนาม' }}</span>
          <span class="text-sm font-bold text-vikinger-purple">{{ donation.points_amount }} แต้ม</span>
        </div>
      </div>
      <p v-else class="mt-3 text-sm text-gray-500 dark:text-gray-400">ยังไม่มีผู้สนับสนุน</p>
    </section>

    <NuxtLink :to="`/Learn/Courses/${course.id}/support`" class="mt-5 block text-center text-sm font-semibold text-vikinger-purple hover:underline">ดูทั้งหมด</NuxtLink>
    <CourseDonationModal v-if="!isCourseAdmin" v-model:visible="showDonationModal" :course-id="Number(course.id)" :course-name="course.name" :course-owner-id="Number(course.user_id)" :balance="authStore.user?.points ?? authStore.user?.pp" @update:visible="loadSupportData" />
  </section>
</template>
